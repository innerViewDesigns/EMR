<?php

	require_once(__DIR__ . "/FirePHPCore/fb.php");
	
	require_once("/Users/Apple/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Apple/Sites/therapyBusiness/private');
  $classLoader->register();

	class dashboard{

		private  $db;
		public $flash = [];

		
		function __construct($args=[]){
			
			//echo "<br>dashboard::__construct::args: " . print_r($args, true);


			$this->db = new dbObj;
			$this->setLastWeeksServices($args);
			//$this->setCurrentBalancesDue():

		}    

		public function getLastWeeksServices(){

			return $this->lastWeeksServices;

		}
			
		private function setDates($start, $end){

			$this->startDate = $start;
			$this->endDate   = $end;

		}
		private function setLastWeeksServices($args=[]){

			$db = $this->db;

			//echo "<br>".print_r($args, true);

			if( !empty($args) && array_key_exists('start_date', $args) && !empty($args['start_date']) ){
				$startDate = $args['start_date'];
			}

			if( !empty($args) && array_key_exists('end_date', $args) && !empty($args['end_date']) ){
				$endDate = $args['end_date'];
			}else{
				$endDate  = date('Y-m-d');
			}

			$in = [];

			if( !empty($args) ){
				//echo "<br> args was not empty";
				if(	array_key_exists('include_insurance', $args) ){
					array_push($in, '1');
					$include_insurance = true;
					//echo "<br> include insurance was checked";
				}

				if(	array_key_exists('include_cashonly', $args) ){
					array_push($in, '0');
					$include_cashonly = true;
					//echo "<br> include cashonly was checked";
				}

			}

			if( count($in) > 0 ){
				$in = implode(", ", $in);
			}else{
				$in = "1";
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

			if( isset($startDate) ) {

				try{
					$stmt = $db->db->prepare($sql);
					
					$stmt->bindParam(":startDate", $startDate);
					$stmt->bindParam(":endDate", $endDate);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						if(isset($include_cashonly)){

							$result = array_merge(array('include_cashonly' => 'true'), $result);
						
						}

						if( isset($include_insurance)){

							$result = array_merge(array('include_insurance' => 'true'), $result);
						
						}
						
						$this->lastWeeksServices = $result;
						

					}else{

						$this->lastWeeksServices = array("no results" => 'no results');
					
					}

					$this->setDates($startDate, $endDate);

				}catch(PDOException $e){

					$this->setFlash('error', "Set last week's services failed ".$e->getMessage());

				}

			}else{

				$this->lastWeeksServices = array('no results' => 'no results');
				$this->setDates("Start Date", $endDate);
			

			}




		}

		private function setCurrentBalancesDue(){

			$db = $this->db;			



		}


		private function setFlash($e, $function_name){

			$this->flash = array($e => $function_name);
			
		}

		public function getFlash(){
			return $this->flash;
		}
		

	} //class


?>