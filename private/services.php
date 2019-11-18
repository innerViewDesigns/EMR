<?php

	require_once(__DIR__ . "/FirePHPCore/fb.php");
	
	require_once("/Users/Lembaris/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Lembaris/Sites/therapyBusiness/private');
  $classLoader->register();

  class services{

  	public   $services;
  	private  $db;
  	private  $rawData;
  	private  $flash;

		function __construct($args=[]){

			$this->db = new dbObj();



		} 

		public function getAllForPatient(){
			
			$db = $this->db;
			
			try{

					$stmt = $db->db->prepare("SELECT * FROM services WHERE patient_id = ?");
					$stmt->bindParam(1, $args['patient_id'], PDO::PARAM_INT);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						$this->rawData = $result;

					}else{
						$this->setFlash("error", "No results from the getAllForPatient function in services.php");
					}
					

				}catch (PDOException $e){

					$this->setFlash('error', $e);
					//fb($e);

				}

		}   


		public function getSomeByServiceId($args){

			/*
			echo "**********************\n";
			echo "get some by service ID\n";
			print_r($args);
			echo "\n**********************\n";
			*/

			$db = $this->db;
			$result = [];

			//build sql

			if(is_array($args)){
				
			$sql = <<<EOD

SELECT CONCAT(patient.first_name, ' ', patient.last_name) AS name,
			 services.patient_id,
	   	 services.dos,
       services.charged,
       services.insurance_used,
       services.in_network,
       services.cpt_code,
       services.dx1,
       services.dx2,
       services.dx3,
       services.id_services

FROM 

	(SELECT patient_id_services AS patient_id,
						dos,
            charged,
            insurance_used,
            in_network,
            cpt_code,
            dx1,
            dx2,
            dx3,
            id_services
    FROM therapy_practice.services 
    WHERE id_services IN (

EOD;
				//then loop through that many while building the sql statement

				for($c = 0; $c < count($args); $c++){
					$sql .= $args[$c] . ", ";
				}

				$sql = trim($sql, ', ');
				$sql .= <<<EOD
								) ) AS services
    
								JOIN therapy_practice.patients AS patient
								ON patient.patient_id = services.patient_id;
EOD;

			}else{
				//add to the flash message and return false. 
				$this->setFlash("error", "the args variable in services::getSomeByServiceId was not an array");
				return false;
			}
			
			try{

					$stmt = $db->db->prepare($sql);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				
					if(empty($result)){

						$this->setFlash('error', "No results when trying to pull the following services ids: " . print_r($args, true));
						return false;

					}else{

						return $result;

					}
					

				}catch (PDOException $e){

					$this->setFlash('error', $e);
					return false;

				}


		}


	private function setFlash($status, $message, $rowCount=null){

			if(empty($rowCount)){
				$this->flash[$status] = $message;
			}else{
				$this->rowCount += $rowCount;
				$this->flash[$status] = $rowCount . " " . $message;
			}

	}

	public function getFlash(){

		return $this->flash;

	}



 }
	