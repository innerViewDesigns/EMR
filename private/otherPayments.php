<?php

	require_once(__DIR__ . "/FirePHPCore/fb.php");
	require_once(__DIR__ . "/validations.php");
	require_once("/Users/Apple/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Apple/Sites/therapyBusiness/private');
  $classLoader->register();

	class otherPayments{

		public   $patient_id, $id, $date, $amount, $payments;

		public 	 $rowCount = 0;
		private  $db;

		
		public function __construct($args = null) {
			
			//echo "<br>other_payments::__construct";
			//echo "<br>other_paymentst::id = ".print_r($id, true);

			$this->db = new dbObj();
			
			if( isset($args) ){
			
				if(gettype($args) === 'array'){
				
					if(array_key_exists('patient_id', $args) ){
			
						$this->patient_id = $args['patient_id'];
			
					}	
			
				}elseif( gettype($args) === 'string'){
			
					$this->patient_id = $args;
			
				}

				$this->setPayments();
			}


		}    
		
		public function getSomeById($args=[]){

			$sql = "SELECT * FROM other_payments WHERE id_other_payments IN (";
			
			for($c = 0; $c < count($args); $c++){
					$sql .= $args[$c] . ", ";
				}

				$sql = trim($sql, ', ');
				$sql .= ");";

			$db = $this->db;

			try{

					$stmt = $db->db->prepare($sql);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						$this->payments = $result;

					}else{

						$this->setFlash("error", "getSomeById from other_payments failed or there were no payments for these ids: ".print_r($args, true));

					}
					

			}catch(PDOException $e){

				$this->setFlash('error', "From other_payments getSomeById method: ".$e->getMessage() );

			}




		}

		private function setPayments(){

			$db = $this->db;

			try{

					$stmt = $db->db->prepare("SELECT * FROM other_payments WHERE patient_id_other_payments = ? ORDER BY date_recieved DESC;");
					$stmt->bindParam(1, $this->patient_id, PDO::PARAM_INT);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						$this->payments = $result;

					}else{

						$this->setFlash("error", "setPayments failed or there were no payments for this patient");

					}
					

			}catch(PDOException $e){

				$this->setFlash('error', "From other_payments setPayments method: ".$e->getMessage() );

			}
	}


	public function create($args){

		echo "<br>other_payments::create, args: " . print_r($args, true);
		$db = $this->db;

		try{

				$stmt = $db->db->prepare("INSERT INTO other_payments (patient_id_other_payments, date_recieved, amount, type, associated_data) VALUES (?, ?, ?, ?, ?)");

				$stmt->bindParam(1, $args["patient_id"], PDO::PARAM_INT);
				$stmt->bindParam(2, $args["date_recieved"]);
				$stmt->bindParam(3, $args["amount"], PDO::PARAM_INT);	
				$stmt->bindParam(4, $args["type"]);	
				$stmt->bindParam(5, $args["associated_data"]);	
				$stmt->execute();
				$newId = $db->db->lastInsertId();

				if($newId){

					$this->setFlash('success', 'New payments added', $stmt->rowCount());
					return $newId;

				}else{

					$this->setFlash('error', 'Something went wrong when adding those payments.');
					return false;
				}

			}catch(PDOException $e){

				$this->setFlash('error', "from other_payments::create: ".$e->getMessage());
				return false;

			}


	}


	public function getPayments(){


		return $this->payments;

	}



	private function setFlash($status, $message, $rowCount=null){

		if(empty($rowCount)){
			$this->flash = array($status => $message);
		}else{
			$this->rowCount += $rowCount;
			$this->flash = array($status => $rowCount . " " . $message);
		}

	}

	public function getFlash(){

		return $this->flash;

	}

		
	
} //class


?>