<?php
	
	set_include_path("/Users/Apple/Sites/therapyBusiness/private/");

	require("insurance.php");
	require("insurances.php");
	require("Services.php");
	require("otherPayments.php");
	require("patients.php");


	class fileReader
	{
			private $dir = "/Users/Apple/SkyDrive/Therapy Business/BandM Commune/remits";
			private $zipNames;
			private $zipsPresent = true;
			private $folderNames = [];
			private $services = [];
			public  $patients = [];

			function __construct($args=[])
			{
					
					$this->zipNames = scandir($this->dir);

					//this will get only zips and set folder names
					$this->zipNames = $this->setFolderNames();

					echo "zipNames:\n";
					echo print_r($this->zipNames, true);

					if(count($this->zipNames) === 0 )
					{
							$this->unzipAll();
							$this->zipsPresent = false;

					}

					$patients = new patients();
					$patients->getByActive();

					$this->patients = $patients->getPatients();
					$this->services = $this->readInFiles();

					
					//no duplicates

					//add the patient id to the new services. If you fail to pair up an entry log that in a file
					//in the remits directory
					$this->linkNamesAndIds();

					//log zero dollar payments in a txt file and unset those entries from services
					$this->recordDenials();

					//then get the service IDs you need to update the database
					$this->setServiceIds();

					//then update the database
					//$this->updateClaims();
					echo print_r($this->services, true);
					

			}

			public function updateClaims()
			{

					$insurances = new insurances();
					$success = [];
					$failed = [];

					foreach ($this->services as $key => &$value)
					{		

							//update the recieved insurance amount
							
							//echo $value['payment']." ".$value['claim_values']['recieved_insurance_amount']."\n";
							//echo gettype($value['payment'])." ".gettype($value['claim_values']['recieved_insurance_amount'])."\n";

							$value['claim_values']['recieved_insurance_amount'] = floatval($value['claim_values']['recieved_insurance_amount']);
							$value['payment'] = floatval($value['payment']);

							$value['claim_values']['recieved_insurance_amount'] += $value['payment'];

							
							$service_id = $insurances->update($value['service_id'], $value['claim_values']);
							
							if($service_id)
							{
									array_push($success, $value);
									

							}else
							{
									array_push($failed, $value);

							}

					}

					echo "success\n".print_r($success, true);
					echo "failed\n".print_r($failed, true);

			}

			private function setServiceIds()
			{

					foreach($this->services as $key => $value)
					{

							$insurance = new insurance($value);

							if($insurance->setServiceId())
							{

									$this->services[$key]['service_id'] = $insurance->getServiceId();


							}else
							{

									echo "there was a problem when trying to get the service id for".print_r($value, true)."\n";

							}

							if($insurance->setInsurance())
							{

									$this->services[$key]['claim_values'] = $insurance->getInsuranceClaim();

							}else
							{
									echo "there was a problem when trying to get the insurance Claim for".print_r($value, true)."\n";

							}

					}

					

			}

			private function recordDenials()
			{

					$file = $this->dir."/".date("Y-m-d_")."Denials.txt";
					$denials = [];
					$noIds = [];

					foreach($this->services as $key => $value)
					{	
							if($value['payment'] === '0.00')
							{
									array_push($denials, $value);
									unset($this->services[$key]);
							}

							if(!array_key_exists('patient_id', $value))
							{
									array_push($noIds, $value['name']);
									unset($this->services[$key]);
							}

					}

					file_put_contents($file, print_r($denials, true));
					file_put_contents($this->dir."/No Patient ID.txt", print_r($noIds, true));

					//echo print_r($this->services, true);

			}

			private function linkNamesAndIds()
			{

					foreach($this->patients as $key => $value)
					{	

							$this->patients[$key]['name'] = strtoupper($value['last_name']).",".strtoupper($value['first_name']);

					}

					foreach($this->services as &$value)
					{
							//grab the last name from the new service
							preg_match('/[^,]+/', $value['name'], $lastName);
							
							//then loop through the patients and match them up.
							foreach($this->patients as &$value1)
							{		
									//correct for names with spaces
									$value1['name'] = preg_replace('/\s/', '', $value1['name']);

									if(preg_match("/".$lastName[0]."/", $value1['name']))
									{
											$value['patient_id'] = $value1['patient_id'];
											continue;
									}

							} 

					}


			}

			public function readInFiles()
			{

					//////////////////////////////////////////////////////////////////////////////////////////
					//this first bit is to be used when there are no zip files

					if(!$this->zipsPresent)
					{
							$fileNames = scandir($this->dir);

							foreach($fileNames as $value)
							{
									if(strpos($value, '.') === 0)
										continue;

									array_push($this->folderNames, trim($value, ".txt"));

							}
					}

					
					//////////////////////////////////////////////////////////////////////////////////////////

					$services = [];
					$ctr = 0;

					//loop through files in folder
					foreach($this->folderNames as $remit)
					{

							$file = file($this->dir."/".$remit.".txt", FILE_IGNORE_NEW_LINES);

							//loop through each line in the file
							foreach($file as $lineNum => $line)
							{
									//////////////////////////////////////////////////////////////////
									//If this is the header row, drop down a line and extract the name
									//////////////////////////////////////////////////////////////////

									$tmp = strpos($line, 'Last,First');
									if( $tmp != FALSE)
									{

											preg_match('/[^\s]+/', substr($file[$lineNum+1], $tmp), $matches);
											$services[$ctr] = array('name' => $matches[0]);

											continue;

									}

									//////////////////////////////////////////////////////////////////////////////
									//If this is the header row, drop down a line and extract the date and payment
									//////////////////////////////////////////////////////////////////////////////

									$tmp = strpos($line, 'Svc Date');
									if($tmp != FALSE)
									{	
											$tmp1 = strpos($line, 'Payment Amt');

											preg_match('/[^\s]+/', substr($file[$lineNum+1], $tmp), $date);
											preg_match('/[^\s]+/', substr($file[$lineNum+1], $tmp1), $payment);

											$date[0] = DateTime::createFromFormat('m/d/Y', $date[0]);

											$services[$ctr]['dos'] = $date[0]->format('Y-m-d');
											$services[$ctr]['payment'] = $payment[0];
											$services[$ctr]['filename'] = $remit;

											$ctr++;
											
											continue;
									}

							}

					}

					$services = $this->sortByPatient($services);
					//echo print_r($services, true);
					return $services;



			}

			private function sortByPatient($services)
			{
					$organized = [];

					foreach($services as $key => $value)
					{
							$organized[$key] = $value['name'];

					}

					array_multisort($organized, SORT_ASC, $services);

					//echo print_r($services, true);
					return $services;

			}

			public function setFolderNames()
			{
					$onlyZips  = [];

					foreach($this->zipNames as $value)
					{

							$tmp = explode('.', $value);
							
						
							if(count($tmp) == 2 && $tmp[1] == 'zip')
							{
									array_push($onlyZips, $value);
									array_push($this->folderNames, $tmp[0]);

							}
								

					}


					return $onlyZips;



			}

			public function unzipAll()
			{

					//loop through files in this directory and unzip them.
					$zip = new ZipArchive;

					foreach($this->zipNames as $value)
					{

							if ($zip->open($this->dir."/".$value) === TRUE) {

							    $zip->extractTo($this->dir.'/');
							    $zip->close();
							    unlink($this->dir."/".$value);

							} else {
							    echo 'failed';
							}


					}

					//Then clean up the folder leaving the '.' the '..' and '.DS_Store'
					$files = scandir($this->dir);
					
					foreach($files as $value)
					{	
							if(strpos($value, '.') === 0)
								continue;

							$tmp = explode('.', $value);

							if(count($tmp) === 2 && $tmp[1] != 'txt')
							{		
									unlink($this->dir."/".$value);
							}
					}
							

			}

			public function listDir()
			{

					foreach($this->folderNames as $value)
					{

							echo $value."\n";

					}


			}

		

	}