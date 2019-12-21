<?php

  class notes{

  	public   $services;
  	private  $db;
  	private  $rawData;
  	private  $flash = [];

		function __construct($args=[]){

			$this->db = new dbObj();



		}

		public function update($service_id, $values){

			$db = $this->db;
			//echo "<br>$service_id";
			//echo "<br>".print_r($values);

			$values = deal_with_null_case($values);

			try{

					$stmt = $db->db->prepare("UPDATE insurance_claim
						SET allowable_insurance_amount = :allowable,
				    		expected_copay_amount 		 = :expected, 
				    		recieved_insurance_amount  = :recieved, 
				   		  recieved_copay_amount 		 = :copay

						WHERE service_id_insurance_claim = :service_id");

					$stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
					$stmt->bindParam(':allowable', $values['allowable_insurance_amount']);
					$stmt->bindParam(':expected', $values['expected_copay_amount']);
					$stmt->bindParam(':recieved', $values['recieved_insurance_amount']);
					$stmt->bindParam(':copay', $values['recieved_copay_amount']);
					

					if($stmt->execute()){

						return $service_id;

					}else{

						$this->setFlash("error", "Something went wrong when trying to update insurance claim for service #" . $service_id.".");
						return false;
					}
					

				}catch (PDOException $e){

					$this->setFlash('error', $e->getMessage());
					return false;


				}


		} 

		public function setAllForPatient($args){
			
			$db = $this->db;
			
			try{

					$stmt = $db->db->prepare("SELECT * FROM insurance_claim WHERE patient_id_insurance_claim = ?");
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

				}

		}   

		public function setSomeByServiceId($args){

			$db = $this->db;
			$result = [];

			//build sql

			if(is_array($args)){
				
			$sql = <<<EOD

SELECT CONCAT(patient.first_name, ' ', patient.last_name) AS Name,
	   	 services.DOS,
       services.CPT,
       insurances.allowable_insurance_amount AS Allowable,
       insurances.expected_copay_amount AS 'Expected Copay',
       insurances.recieved_copay_amount AS 'Recieved Copay',
       insurances.recieved_insurance_amount AS 'Recieved Insurance'


FROM 

	(SELECT patient_id_services AS patient_id,
			DATE_FORMAT(dos, '%a %b %d') AS DOS,
            cpt_code AS CPT,
            id_services AS id
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
	ON patient.patient_id = services.patient_id

	JOIN therapy_practice.insurance_claim AS insurances
	ON service_id_insurance_claim = services.id;
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

					$this->setFlash('error', "This from insurances::getSomeByServiceId - ".$e->getMessage());
					return false;

				}


		}

	public function getClaims(){

		return $this->rawData;

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
	