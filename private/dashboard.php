<?php
	
	require_once( __DIR__ . "/Mysqldump/src/Ifsnop/Mysqldump/Mysqldump.php");
	
	class dashboard{

		private  $db;
		public $flash = [];
		public $lastWeeksServices = [];
		public $user_param;
		public $startDate;
		public $endDate;

		
		function __construct($args=[]){

			$this->db = new dbObj;

			if(gettype($args) === 'array')
			{

				if(array_key_exists('user_param', $args))
				{

					$this->user_param = $args['user_param'];
			
					switch($args['user_param'])
					{
						case 'database-backup':
							$this->databaseBackup();
							break;

						case 'add-claims-to-file':
							$this->addClaimsToFile();
							break;

						default:
							break;
					}


				}else
				{
					$this->setDates($args);
					$this->setLastWeeksServices($args);
				}
				
			}

			


		}    

		public function getLastWeeksServices(){

			return $this->lastWeeksServices;

		}

		private function databaseBackup()
		{

			$dbHelper = new dbHelper();
			$password = $dbHelper->password;

			$date = date("Ymd-His");
			$path = "/Users/Lembaris/Skydrive/Therapy Business/Business/Backup/";
			$zip_file_with_path = $path.$date.".zip";
			$sql_file_with_path = $path.$date.".sql";
			$sql_file = $date.".sql";
			$zip_file = $date.".zip";
			

			
			try
			{
					$dump = new Ifsnop\Mysqldump\Mysqldump('mysql:host=127.0.0.1;dbname=therapy_practice','lembaris', $password);
					$dump->start($sql_file_with_path);

			}catch(Exception $e)
			{
				fb($e->getMessage());
			}
			
			
			if(file_exists($sql_file_with_path))
			{			

				try
				{
						$zip = new ZipArchive();
						$zip->open($zip_file_with_path, ZIPARCHIVE::CREATE);
						$zip->addFile($sql_file_with_path, $sql_file);
						$zip->setEncryptionName($sql_file, ZipArchive::EM_AES_256, $password);
						$zip->close();
						unlink($sql_file_with_path);

				}catch(Exception $e)
				{
					$this->setFlash(array("Error", $e->getMessage()));
				}


			

			}else
			{
				fb("There was no file.");
			}

			

		}
		
		private function addClaimsToFile()
		{
				$needle1 = "Claims submitted through ";
				$needle2 = "———————————————\nClaims:\n——————————————-\n";
				$needle3 = "——————————————————\nDelayed Claim, patients not entered yet:";

				$file = file_get_contents("/Users/Lembaris/SkyDrive/Therapy Business/BandM Commune/To do.txt", false);
				
				if($file)
				{

					/*
							The file was found. Now get the date up to which claims have been submitted.
							Then format that date and assign it to a property of this object. 

					*/

					$offset = stripos($file, $needle1) + strlen($needle1);
					$lastDateEntered = substr($file, $offset, 5);
					$lastDateEntered = $lastDateEntered.'/19';
					$lastDateEntered = DateTime::createFromFormat("m/d/y", $date);
					$lastDateEntered = $date->modify( '+1 day');
 

					/*
							Then make sure that there arn't claims in there waiting to be submitted. If there
							are, then grap the last date and use that to set the new start date.
					*/

					$offset    = stripos($file, $needle2) + strlen($needle2);
					$offset1   = stripos($file, $needle3);
					$inbetween = substr($file, $offset, $offset1 - $offset);
					$lastDateGiven = '';

					if(preg_match('/[a-zA-Z]/', $inbetween))
					{
							preg_match_all('/(\d{4}\-\d{2}\-\d{2})/', $inbetween, $matches, PREG_OFFSET_CAPTURE);
							$lastDateGiven = DateTime::createFromFormat("Y-m-d", $matches[0][count($matches[0])-1][0] );
							$this->startDate = $lastDateGiven('+1 day');
					}else
					{
						$this->startDate = $date->format("Y-m-d");
					}





				}else
				{
					fb('addClaimsToFile::date file note found');
				}
				





		}

		private function setDates($args){


			if(gettype($args) === 'array')
			{

				if( array_key_exists('start_date', $args) )
				{
				
					$startDate = $args['start_date'];
				
				}

				if( array_key_exists('end_date', $args) )
				{
				
					$endDate = $args['end_date'];

				}

				if( empty($args) )
				{
					$startDate = new DateTime('-1 weeks');
					$startDate = $startDate->format('Y-m-d');

					$endDate = new DateTime('now');
					$endDate = $endDate->format('Y-m-d');
				}

			}

			$this->startDate = $startDate;
			$this->endDate = $endDate;

		}
		private function setLastWeeksServices($args=[]){

			$db = $this->db;

			$in = [];

			if( !empty($args) ){
				
				if(	array_key_exists('include_insurance', $args) ){
					array_push($in, '1');
					$include_insurance = true;

				}

				if(	array_key_exists('include_cash', $args) ){
					array_push($in, '0');
					$include_cash = true;

				}

			}

			if( count($in) > 0 ){

				$in = implode(", ", $in);

			}else{

				$in = "1,0";
				$include_cash = $include_insurance = true;

			}			

			$sql = <<<EOT

							SELECT CONCAT(patient.last_name, ', ', substr(patient.first_name, 1, 2)) AS name,
							DATE(services.dos) as dos,
							 services.cpt,
							 services.dx1,
							 services.dx2,
							 services.dx3,
							 services.patient_id,
							 notes.completed AS completed

							FROM

								(SELECT patient_id_services AS patient_id,
												dos,
												id_services,
							          cpt_code AS cpt,
							          dx1,
							          dx2,
							          dx3
							    FROM services 
							    WHERE dos >= CAST(:startDate AS DATE) AND 
							          dos <= CAST(:endDate AS DATE)
							    AND insurance_used IN ( $in ) )
							    AS services

							JOIN therapy_practice.patients AS patient
							 ON patient.patient_id = services.patient_id

							LEFT JOIN therapy_practice.notes AS notes
							 ON notes.service_id_notes = services.id_services;
EOT;
			
			$sql = preg_replace('/(\$in)/', $in, $sql);

			if( isset($this->startDate) ) {

				try{

					$stmt = $db->db->prepare($sql);
					
					$stmt->bindParam(":startDate", $this->startDate);
					$stmt->bindParam(":endDate", $this->endDate);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						if(isset($include_cash)){

							$result = array_merge(array('include_cash' => 'true'), $result);
						
						}

						if( isset($include_insurance)){

							$result = array_merge(array('include_insurance' => 'true'), $result);
						
						}
						
						$this->lastWeeksServices = $result;

					}else{

						$this->lastWeeksServices = array("no results" => 'no results');

					}


				}catch(PDOException $e){

					$this->setFlash(array('Error', "Set last week's services failed ".$e->getMessage()));

				}

			}else{

				$this->lastWeeksServices = array('no results' => 'no results');
			

			}




		}

		private function setCurrentBalancesDue(){

			$db = $this->db;			



		}


		private function setFlash($flash){

			array_push($this->flash, $flash);
			
		}

		public function getFlash(){

			return $this->flash;
		}
		

	} //class


?>