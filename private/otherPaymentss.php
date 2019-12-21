<?php
  class otherPaymentss{

  	public   $services;
  	private  $db;
  	private  $rawData;
  	private  $flash = [];

		function __construct($args=[]){

			$this->db = new dbObj();

		}

		public function update($service_id, $values){

			$db = $this->db;

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

						$this->setFlash(array("Error", "Something went wrong when trying to update insurance claim for service #" . $service_id."."));
						return false;
					}
					

				}catch (PDOException $e){

					$this->setFlash(array('Error', $e->getMessage()));
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

						$this->setFlash(array("error", "No results from the getAllForPatient function in services.php"));
					
					}
					

				}catch (PDOException $e){

					$this->setFlash(array('error', $e));

				}

		}   

		public function setSomeById($args){

			$db = $this->db;
			$result = [];

			//build sql

			if(is_array($args)){
				
			$sql = <<<EOD

SELECT CONCAT(patient.first_name, ' ', patient.last_name) AS Name,
	   	 payments.date,
	   	 payments.amount
FROM 

	(SELECT patient_id_other_payments AS patient_id,
			DATE_FORMAT(date_recieved, '%a %b %d') AS date,
            amount AS Amount
    FROM therapy_practice.other_payments 
    WHERE id_other_payments IN (

EOD;
				//then loop through that many while building the sql statement

				for($c = 0; $c < count($args); $c++){
					$sql .= $args[$c] . ", ";
				}

				$sql = trim($sql, ', ');
				$sql .= <<<EOD
								) ) AS payments
    
    JOIN therapy_practice.patients AS patient
	ON patient.patient_id = other_payments.patient_id_other_payments;
EOD;

			}else{
				//add to the flash message and return false. 
				$this->setFlash(array("error", "the args variable in services::getSomeByServiceId was not an array"));
				return false;
			}
			
			try{

					$stmt = $db->db->prepare($sql);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				
					if(empty($result)){

						$this->setFlash('error', "No results when trying to pull the following payment ids: " . print_r($args, true));
						return false;

					}else{

						return $result;

					}
					

				}catch (PDOException $e){

					$this->setFlash(array('error', "This from other_paymentss setSomeById: " . $e->getMessage()));
					return false;

				}


		}

	public function getClaims(){

		return $this->rawData;

	}

	private function setFlash($flash){

		array_push($this->flash, $flash);

	}

	public function getFlash(){

		return $this->flash;

	}



 }
	