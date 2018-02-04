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
					

					/*
							This will return an array with the names of all the zip files in this directory.
							The class variable $folderNames will also be populated with these values which
							will match the folders created after unzip
					*/

					$this->zipNames = $this->setFolderNames();


					/*
							If there were no zip files, i.e. all files have already been unzipped,
							then record that. Otherwise, unzip the files. 
					*/

					if(count($this->zipNames) === 0 )
					{
							$this->zipsPresent = false;

					}else
					{
							$this->unzipAll();
					}

					$patients = new patients();
					$patients->getByActive();

					$this->patients = $patients->getPatients();

					/*

							Get the payments and then group by patient name
					
					*/

					$this->services = $this->readInFiles();


					//add the patient id to the new services. If you fail to pair up an entry log that in a file
					//in the remits directory

					$this->linkNamesAndIds();


					
					//Record all payments in a CSV file
					$this->recordAllClaims();

					//log zero dollar payments and those patients with no patientId set
					//each in their own txt file and unset those entries from services,

					$this->recordDenials();


					//then get the service IDs you need to update the database

					$this->setServiceIds();
					//echo print_r($this->setServiceIds(), true);

					
					//$this->nullCases();
					//then update the database and check for null cases
					//$this->updateClaims();
					

			}

			public function nullCases()
			{	
					$file = $this->dir."/".date("Y-m-d_")."Missing_Payments.txt";
					$pts_with_missing_payments = [];
					$insurances = new insurances();
					$duplicates = [];

					foreach($this->services as $key => $value)
					{

							/*
									
									Make sure that you only move through each patient once. 

							*/

							if( in_array($value['name'], $duplicates) )
							{

									continue;

							}else
							{

									array_push($duplicates, $value['name']);

							}

							/*
									
									1) Get each of the insurance claims with dos and insurance used information for this patient
									2) Set a variable to false to keep track of whether a claim was paid
									3) Loop through each claim
									4) If insurance wasn't used, skip it
									5) Check to see if a payment was recieved
									6) If not, check to see if any previous payments have been recieved
									7) If so, then set the name of this patient as a key in an array, and make that 
									    key point to another array consisting of each dos that has not been paid

							*/
							
							$insurances->setAllForPatientIncludeDOS($value);
							$claims = $insurances->getClaims();
							
							//echo print_r($claims, true);

							if( count($claims) != 0 )
							{
									$tmp = false;

									foreach($claims as $keyi => $valuei)
									{	
											if($valuei['insurance_used'] === 0)
												continue;

											if(floatval($valuei['recieved_insurance_amount']) === 0.00)
											{
													if($tmp === true)
													{	
															if(array_key_exists($value['name'], $pts_with_missing_payments))
															{
															
																	array_push($pts_with_missing_payments[$value['name']], $value['dos']);
															
															}
															else
															{

																	$pts_with_missing_payments[$value['name']] = array($value['dos']);

															}
													}else
														continue;

											}else{

													$tmp = true;

											}

									} 

							}else
							{		
									echo "\n\nThere was a problem when trying to set and get the insurance claims for ".$value['name'].". Here's the error message:\n";
									echo print_r($insurances->getFlash(), true);
							}

					}

					file_put_contents($file, print_r($pts_with_missing_payments, true));


			}

			public function updateClaims()
			{

					$insurances = new insurances();
					$success = [];
					$failed = [];
					$duplicates = [];

					foreach ($this->services as $key => &$value)
					{		


							//update the recieved insurance amount

							$value['claim_values']['recieved_insurance_amount'] = floatval($value['claim_values']['recieved_insurance_amount']);
							$value['payment'] = floatval($value['payment']);

							if($value['claim_values']['recieved_insurance_amount'] === 0.00)
							{
									$value['claim_values']['recieved_insurance_amount'] += $value['payment'];

							}else
							{

									array_push($duplicates, $value);
									continue;

							}
							
							$service_id = $insurances->update($value['service_id'], $value['claim_values']);
							
							if($service_id)
							{
									array_push($success, $value);			

							}else
							{
									array_push($failed, $value);

							}

					}

					if(count($failed) != 0)
						$this->listResults("Failed", $failed);

					if(count($success) != 0)
						$this->listResults("Success", $success);

					if(count($duplicates) != 0)
						$this->listResults("Duplicates", $duplicates);

					//check to see if you're missing any payments for these patients
					$this->nullCases();
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

									echo "There was a problem when trying to get the service id for: \n".print_r($value, true)."\n";

							}

							if($insurance->setInsurance())
							{

									$this->services[$key]['claim_values'] = $insurance->getInsuranceClaim();

							}else
							{
									echo "There was a problem when trying to get the Insurance Claim for: \n".print_r($value, true)."\n";

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

					file_put_contents($file, print_r($denials, true), FILE_APPEND);
					file_put_contents($this->dir."/No Patient ID.txt", print_r($noIds, true));

					//echo print_r($this->services, true);

			}

			private function linkNamesAndIds()
			{	

					/*
							
							1. Get all last names into an array
							2. Get all first names into an array
							3. Run array count values on each and assign to new arrays
							4. loop through each service
							5. test the last name against the count of the appropriate key
							6. if it's more than 1, do the same thing with the first name
							7. if

					*/


					foreach($this->patients as $key => $value)
					{	

							$this->patients[$key]['name'] = strtoupper($value['last_name']).",".strtoupper($value['first_name']);

					}

					foreach($this->services as &$value)
					{
							//grab the last name from the new service
							//preg_match('/[^,]+/', $value['name'], $lastName);
							
							//then loop through the patients and match them up.
							foreach($this->patients as &$value1)
							{		
									//correct for names with spaces
									$value1['name'] = preg_replace('/\s/', '', $value1['name']);

									//if(preg_match("/".$value['name']."/", $value1['name']))
									if($value['name'] == $value1['name'])
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
					/*
							This first bit is to be used when there are no zip files. You haven't yet collected the 
							folder names, so do that. 

					*/	
					if( $this->zipsPresent === false )
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
					$ctr      = 0;

					//loop through files in folder
					foreach($this->folderNames as $remit)
					{

							$file = file($this->dir."/".$remit.".txt", FILE_IGNORE_NEW_LINES);

							//loop through each line in the file
							foreach($file as $lineNum => $line)
							{

									/*
											If this is the header row, drop down a line and extract the name
									*/

									$tmp = strpos($line, 'Last,First');
									if( $tmp != FALSE)
									{

											/*
														
														Match everything but a space: /[^\s]+/
														Match everything up to a comma: /[^,]+/


											*/

											preg_match('/[^\s]+/', substr($file[$lineNum+1], $tmp), $matches);
											$services[$ctr] = array('name' => $matches[0]);

											$services[$ctr]['file'] = $remit;
											continue;

									}

									/*

											If this is the header row, drop down a line and extract the date and payment
									
									*/

									$tmp = strpos($line, 'Svc Date');
									if($tmp != FALSE)
									{	
											$tmp1 = strpos($line, 'Payment Amt');

											preg_match('/[^\s]+/', substr($file[$lineNum+1], $tmp), $date);
											preg_match('/[^\s]+/', substr($file[$lineNum+1], $tmp1), $payment);

											$date[0] = DateTime::createFromFormat('m/d/Y', $date[0]);


											/*
											
													Check to see if this is a new patient (i.e., whether the above if statement fired)
													if not, this is another line-item and so use the name of the last patient. 

											*/

											if(!array_key_exists($ctr, $services))
											{
													$services[$ctr]['name'] = $matches[0];
											}

											$services[$ctr]['dos'] = $date[0]->format('Y-m-d');
											$services[$ctr]['payment'] = $payment[0];
											$services[$ctr]['filename'] = $remit;

											$ctr++;
											
											continue;
									}

							}

					}

					//organize the array by grouping patients and then return it.

					return $this->sortByPatient($services);


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
					/*
								1) gathering the zip files so that you can unzip them

								2) gathering the name of the zip files that you can then use
								   to gather the txt files after unzip.

							return value = array with zip names

					*/

					$allFiles  = scandir($this->dir);
					$onlyZips  = [];

					foreach($allFiles as $value)
					{

							$tmp = explode('.', $value);
							
							/*
									Check to make sure that this isn't a hidden file
									in which case there would only be one item in the 
									returned array. 

									Also check to make sure that this is a zip file
							*/
						
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

					/*

							loop through files in this directory and unzip them.
					
					*/
					$zip = new ZipArchive;

					foreach($this->zipNames as $value)
					{

							if ($zip->open($this->dir."/".$value) === TRUE)
							{

							    $zip->extractTo($this->dir.'/');
							    $zip->close();
							    unlink($this->dir."/".$value);

							}else 
							{

							    echo 'Failed to unzip ' . $value;
							
							}


					}



					/*

							Then clean up the folder leaving the '.' the '..' and '.DS_Store'
					
					*/

					$files = scandir($this->dir);
					
					foreach($files as $value)
					{	

							if(strpos($value, '.') === 0)
								continue;

							$tmp = explode('.', $value);

							if(count($tmp) === 2 && $tmp[1] != 'txt')
							{	
									//delete the file

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

			public function listResults($key='', $array=[])
			{

					echo "**********************\n      $key\n**********************\n\n";
					foreach($this->services as $key => $value)
					{

							echo $value['name']." - ".$value['dos']." - ".$value['payment']." - ".$value['file']."\n";

					}



			}

			public function recordAllClaims()
			{

				$file = "/Users/Apple/SkyDrive/Therapy Business/BandM Commune/remits/data/history.txt";

				file_put_contents($file, "******************************************************\n\n", FILE_APPEND);

				foreach($this->services as $key => $value)
				{

						file_put_contents($file, $value['name']." - ".$value['dos']." - ".$value['payment']." - ".$value['file']."\n", FILE_APPEND);

				}

				file_put_contents($file, "******************************************************\n\n", FILE_APPEND);


			}
		

	}