<?php

	//require_once(__DIR__ . "/validations.php");
	//require_once("/Users/Lembaris/Sites/therapyBusiness/private/SplClassLoader.php");
	//$classLoader = new SplClassLoader(NULL, '/Users/Lembaris/Sites/therapyBusiness/private');
  //$classLoader->register();

	class insurance{

		private $patient_id, $dos, $service_id;
		private $flash = [];
		public $insurance_claim;
		private  $db;

		
		public function __construct($args = null) {
			
			//echo "<br>service::__construct";
			
			$this->db = new dbObj();
			
			//fb("service::__construct");
			//fb("id: " . $args['patient_id']);

			if( isset($args) ){

				if(gettype($args) === 'array'){

					//echo "<br>service::args = ".print_r($args, true);

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
		

		public function populate(){

			$this->setInsurance();

		}

		public function setServiceId(){

			//You'll need the service id to retrieve specific insurance claims.
			//This is being called from this class's setInsurance() method

			if(isset($this->patient_id) && isset($this->dos)){

				$service = new service(array('patient_id' => $this->patient_id, 'dos' => $this->dos));
				$service->setServiceByDOS();
				$patientService = $service->getService();

				//check to make sure there wasn't an error
				$flash = $service->getFlash();
			
				if(array_key_exists('error', $flash)){
					
					array_merge($this->flash, $flash);
					return false;

				}else{

					$this->service_id = $patientService['id_services'];
					return true;

				}

			}else{

				$this->setFlash(array('Error', 'The service_id and dos seem not to have been sent to insurance.php.'));

				return false;
			}
			

		}

		public function setInsurance(){


			//This first section is to accomodate views::insurance::_info-only 
			//which in the go between between views::insurances::_update
			//__update sends date and patient_id to insurance/get which appController 
			//uses to create a new insurance model and passing it $args. 

			//Also, though, you are calling setInsurance from the update function
			//in the patient::payments tab. From there you pass in service_id without
			//constructing a new model directly.


			if( empty($this->service_id) ){
				
				if(!$this->setServiceId()){

					echo $this->patient_id." ".$this->dos." From insurance::setInsurance - It didn't work.\n";
					return false;

				}
			
			}

			$db = $this->db;

			try{

					$stmt = $db->db->prepare("SELECT * FROM insurance_claim WHERE service_id_insurance_claim = ?");
					$stmt->bindParam(1, $this->service_id, PDO::PARAM_INT);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						foreach($result[0] as $key => $value){

								$this->$key = $value;

						}

						$this->insurance_claim = $result[0];
						return true;

					}else{
						
						$this->setFlash(array('Error', 'There was a problem in fetching the service with id #' . $this->service_id . "."));
						return false;

					}
					

			}catch(PDOException $e){

				$this->setFlash(array('Error', "Something went wrong with insurance::setInsurance. Here's the message: " . $e->getMessage()));

			}
	}


	public function update($args){

		//echo "<br>insurance::update, args: " . print_r($args, true);
		$db = $this->db;


		if( array_key_exists('service_id', $args) ){

			$this->service_id = $args['service_id'];
			
			if( !$this->setInsurance() ){

				$this->setFlash(array('Error', "Tried to call setInsurance in insurance::update but that failed for some reason."));

			}


		}else{

			$this->setFlash(array('Error', "Can't update this insurance claim without a service_id. Here's what was passed to this function: ".print_r($args, true)));

		}

		
		$args = $this->mergeNewData($args);
		$args = deal_with_null_case($args);


			try{

					$stmt = $db->db->prepare("UPDATE insurance_claim SET insurance_name = ?, allowable_insurance_amount = ?, expected_copay_amount=?, recieved_insurance_amount=?, recieved_copay_amount=? WHERE service_id_insurance_claim = ?;");
					$stmt->bindParam(1, $args['insurance_name'], PDO::PARAM_INT);
					$stmt->bindParam(2, $args['allowable_insurance_amount'], PDO::PARAM_STR);
					$stmt->bindParam(3, $args['expected_copay_amount']);
					$stmt->bindParam(4, $args['recieved_insurance_amount']);
					$stmt->bindParam(5, $args['recieved_copay_amount'], PDO::PARAM_INT);
					$stmt->bindParam(6, $args['service_id_insurance_claim'], PDO::PARAM_INT);


					if( $stmt->execute() ){

						return $this->service_id;


					}else{

						$this->setFlash(array('Error', 'Something went wrong when updating '.$this->service_id.'s insurance claim.' ));

					}

				}catch(PDOException $e){

					$this->setFlash(array('Error', "Something went wrong when updating ".$this->service_id."'s insurance claim.".$e->getMessage()));

				}


	}

	public function getServiceId()
	{

			return $this->service_id;

	}

	public function getInsuranceClaim()
	{

			return $this->insurance_claim;

	}

	public function getService(){

		return $this->service;

	}

	private function setFlash(array $flash){

		array_push($this->flash, $flash);

	}

	public function getFlash(){

		return $this->flash;

	}

	private function mergeNewData($args){

		$newArgs = [];

		if($args){

			foreach($args as $key => $value){

				if( preg_match('/insurance_name/', $key) ){
					$this->insurance_claim[$key] = $value;
					//$this->insurance_name = $value;
				}

				elseif( preg_match('/allowable_insurance_amount/', $key) ){
					$this->insurance_claim[$key] = $value;
				}

				elseif( preg_match('/expected_copay_amount/', $key) ){
					$this->insurance_claim[$key] = $value;
				}

				elseif( preg_match('/recieved_insurance_amount/', $key) ){
					$this->insurance_claim[$key] = $value;

				}

				elseif( preg_match('/recieved_copay_amount/', $key) ){
					$this->insurance_claim[$key] = $value;

				}

			}//for loop

			return $this->insurance_claim;

		}//if result

	}

	private function sanatizeParams($args){

		//make sure that all files are present
		$requiredKeys = ['type', 'dos', 'cpt_code', 'dx1', 'dx2', 'dx3'];


		//deal with the special case of patient_id
		if( !array_key_exists('patient_id', $args) ){

			$this->setFlash(array("Error", "Failed in service.php::create - No patient id given"));
			return false;

		}

		$args['charged'] = !array_key_exists("charged", $args) ? 0 : 1;
		$args['insurance_used'] = !array_key_exists("insurance_used", $args) ? 0 : 1;
		

		foreach( $requiredKeys as $value){
			
			if( !array_key_exists($value, $args) ){

				$args[$value] = null;

			}

		}

		return $args;

	}
		
	
} //class


?>