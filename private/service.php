<?php

	require_once(__DIR__ . "/FirePHPCore/fb.php");
	require_once(__DIR__ . "/validations.php");
	require_once("/Users/Apple/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Apple/Sites/therapyBusiness/private');
  $classLoader->register();

	class service{

		public   $patient_id, $id_services, $type, $dos;
		public   $charged, $insurance_used, $cpt_code, $dx1, $dx2, $dx3;
		public   $service = [];
		private  $flash = [];

		public 	 $rowCount = 0;
		private  $db;

		
		public function __construct($args = null) {
			
			//echo "<br>service::__construct";
			//echo "<br>service::id = ".print_r($id, true);
			$this->db = new dbObj();
			
			//fb("service::__construct");
			//fb("id: " . $args['patient_id']);

			if( isset($args) ){

				if(gettype($args) === 'array'){

					if(array_key_exists('patient_id', $args)){
						$this->patient_id = $args['patient_id'];
					}

					if(array_key_exists('dos', $args)){
						$this->dos = $args['dos'];
					}


				}elseif( gettype($args) === 'string'){
					$this->patient_id = $args;
				}

			}


		}    
		

		public function setService(){

			$db = $this->db;

			try{

				$stmt = $db->db->prepare("SELECT * FROM services WHERE patient_id_services = ? ORDER BY dos DESC LIMIT 1");
				$stmt->bindParam(1, $this->patient_id, PDO::PARAM_INT);
				$stmt->execute();
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

				$this->setServiceVariables($result);
					
					

			}catch(PDOException $e){

				$this->setFlash('error', "This from service::setService, ".$e->getMessage());

			}
	}

	private function setServiceVariables($result=null){

		if($result){

			foreach($result[0] as $key => $value){
				if($key == 'id_services' || 'patient_id_services'){
					continue;
				}else{
					$this->$key = $value;
				}

			}

			$this->service = $result[0];


		}else{

			$this->setFlash('error', 'Could not find the service with patient_id: ' . $this->patient_id . ' and dos: ' . $this->dos . ".");
			
		}

	}

	public function setServiceByDOS(){

		$db = $this->db;

			try{

					$stmt = $db->db->prepare("SELECT * FROM services WHERE patient_id_services = ? AND dos = CAST(? AS DATE);");
					$stmt->bindParam(1, $this->patient_id, PDO::PARAM_INT);
					$stmt->bindParam(2, $this->dos);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					$this->setServiceVariables($result);
					

			}catch(PDOException $e){

				$this->setFlash('error', $e);

			}		

	}


	public function create($args){

		//echo "<br>service::create, args: " . print_r($args, true);
		$db = $this->db;
		$args = $this->sanatizeParams($args);
		$args = deal_with_null_case($args);
		//echo "<br>service::create, args: " . print_r($args, true);

		if($args){

			try{

					$stmt = $db->db->prepare("INSERT INTO services (patient_id_services, type, dos, charged, insurance_used, cpt_code, dx1, dx2, dx3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
					$stmt->bindParam(1, $args['patient_id'], PDO::PARAM_INT);
					$stmt->bindParam(2, $args['type'], PDO::PARAM_STR);
					$stmt->bindParam(3, $args['dos']);
					$stmt->bindParam(4, $args['charged']);
					$stmt->bindParam(5, $args['insurance_used'], PDO::PARAM_INT);
					$stmt->bindParam(6, $args['cpt_code'], PDO::PARAM_INT);
					$stmt->bindParam(7, $args['dx1'], PDO::PARAM_STR);
					$stmt->bindParam(8, $args['dx2'], PDO::PARAM_STR);
					$stmt->bindParam(9, $args['dx3'], PDO::PARAM_STR);	

					$stmt->execute();
					$newId = $db->db->lastInsertId();

					if($newId){

						return $newId;


					}else{

						$this->setFlash('error', 'Something went wrong when adding {$args["patient_id"]}\'s services.' );
						return false;
					}

				}catch(PDOException $e){

					$this->setFlash('error', "This from services::create - ".$e->getMessage());
					return false;

				}

			}else{

				$this->setFlash('error', "The args conditional in services.php::create failed");
				return false;

			} 


	}


	public function getService(){

		return $this->service;

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


	private function sanatizeParams($args){

		//make sure that all files are present
		$requiredKeys = ['type', 'dos', 'cpt_code', 'dx1', 'dx2', 'dx3'];


		//deal with the special case of patient_id
		if( !array_key_exists('patient_id', $args) ){

			$this->setFlash("error", "Failed in service.php::create - No patient id given");
			return false;

		}
		

		foreach( $requiredKeys as $value){
			
			if( !array_key_exists($value, $args) ){

				$args[$value] = null;

			}

		}

		return $args;

	}
		
	
} //class


?>