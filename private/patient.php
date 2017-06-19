<?php

	require_once(__DIR__ . "/FirePHPCore/fb.php");
	require_once(__DIR__ . "/validations.php");
	require_once("/Users/Apple/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Apple/Sites/therapyBusiness/private');
  $classLoader->register();

	class patient{

		private  $personalInfo;
		public   $services, $balance, $otherNotes, $allNotes;
		private  $flash = [];

		public 	 $rowCount = 0;
		private  $db;

		
		public function __construct($id = null) {
			
			//echo "<br>patient::__construct";
			//echo "<br>patient::id = ".print_r($id, true);
			$this->db = new dbObj();
			
			if( isset($id) ){

				if(gettype($id) === 'array'){

					$this->patient_id = $id[0];

				}elseif( gettype($id) == 'string' || gettype($id) == 'integer'){

					$this->patient_id = $id;

				}

				$this->populate();
			}


		}    
		

		private function populate(){

			//dispatch various setter methods
			//$this->setServices();
			//$this->setInsurance_claims();
			//$this->setInvoices();
			//$this->setBalanceOwed();
			$this->setPersonalInfo();
			$this->setBalance();

		
		} //function populate

		private function setPersonalInfo(){

			$db = $this->db;

			try{

					$stmt = $db->db->prepare("SELECT * FROM patients WHERE patient_id = ?");
					$stmt->bindParam(1, $this->patient_id, PDO::PARAM_INT);
					$stmt->execute();
					$result = $stmt->fetch(PDO::FETCH_ASSOC);

					if($result){

						$this->personalInfo = $result;

					}else{
						$this->setFlash('error', "No results from fetch patient with id: ".$this->patient_id.".");
					}
					

			}catch(PDOException $e){

				$this->setFlash('error', 'From trying to pull patient #'.$this->patient_id.": ".$e->getMessage());

			}
	}

	public function getPersonalInfo(){

		return $this->personalInfo;

	}

	public function getBalance(){
		return $this->balance;
	}

	public function setBalance(){

		$db = $this->db;
		$sql =<<<EOT
							SELECT  coalesce(SUM(other.amount),0) +
											coalesce(insurance.recievedCopay,0) -
       							  coalesce(insurance.expectedCopay,0) AS 'balance'
           
							FROM

								(SELECT	patient_id_insurance_claim AS patient_id,
										coalesce(SUM(expected_copay_amount),0) AS 'expectedCopay',
									    coalesce(SUM(recieved_insurance_amount),0) AS 'recievedInsurance',
							            coalesce(SUM(recieved_copay_amount),0) AS 'recievedCopay'
							    FROM insurance_claim 
							    WHERE patient_id_insurance_claim = ?) AS insurance

							LEFT JOIN therapy_practice.other_payments AS other
								on other.patient_id_other_payments = insurance.patient_id;
EOT;
		

			try{

					$stmt = $db->db->prepare($sql);
					$stmt->bindParam(1, $this->patient_id, PDO::PARAM_INT);
					$stmt->execute();
					$result = $stmt->fetch(PDO::FETCH_ASSOC);

					if($result){

						$this->balance = $result['balance'];

					}else{
						$this->setFlash('error', "No balance could be calculated");
					}
					

			}catch(PDOException $e){

				$this->fail($e, 'From trying to set the balance in patient.php: '.$e->getMessage());

			}

	}


	public function create($args){

		//echo "<br>patient::create, args: " . print_r($args, true);
		$db = $this->db;

		$args = $this->sanatizeParams($args);

		try{

				$stmt = $db->db->prepare("INSERT INTO patients (first_name, middle_name, last_name, dob, phone1_type, phone1, phone2_type, phone2, email, ss) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $args["first_name"]);
				$stmt->bindParam(2, $args["middle_name"]);
				$stmt->bindParam(3, $args["last_name"]);
				$stmt->bindParam(4, $args["dob"]);
				$stmt->bindParam(5, $args["phone1_type"]);
				$stmt->bindParam(6, $args["phone1"]);
				$stmt->bindParam(7, $args["phone2_type"]);
				$stmt->bindParam(8, $args["phone2"]);
				$stmt->bindParam(9, $args["email"]);
				$stmt->bindParam(10, $args["ss"]);
				
				$stmt->execute();
				$newId = $db->db->lastInsertId();

				if($newId){

					$this->setFlash('success', 'New patients added', $stmt->rowCount());
					return $newId;

				}else{

					$this->setFlash('error', 'Something went wrong when adding those patients.' );
				}

			}catch(PDOException $e){

				$this->setFlash('error', "This from patient create: ".$e->getMessage());

			}


	}

	public function setAllNotes()
	{
			$db = $this->db;

			$sql = "SELECT * FROM notes WHERE patient_id_notes = :patient_id ORDER BY dos DESC;";

			try{

					$stmt = $db->db->prepare($sql);
					$stmt->bindParam(':patient_id', $this->patient_id, PDO::PARAM_INT);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						$this->allNotes = $result;

					}

				}catch(PDOException $e){

					$this->setFlash('error', "This went wrong when trying to fetch all of that person's notes: ".$e->getMessage());

				}



	}

	public function getAllNotes()
	{
		if(!empty($this->allNotes))
		{	
			return $this->allNotes;

		}else
		{
			return $this->flash;
		}
	}

	private function sanatizeParams($args){

		$requiredKeys = ["first_name", "middle_name", "last_name", "dob", "phone1_type", "phone1", "phone2_type", "phone2", "email", "ss"];

		foreach( $requiredKeys as $value){
			
			if( !array_key_exists($value, $args) ){

				$args[$value] = null;

			}

		}

		return $args;

	}

	public function setOtherNotes(){
		$db = $this->db;

		$sql = <<<EOT
			 SELECT * FROM notes
			 WHERE patient_id_notes = :patient_id 
			 AND associated_date IS NOT NULL
			 AND service_id_notes IS NULL;
EOT;

		try{

				$stmt = $db->db->prepare($sql);
				$stmt->bindParam(':patient_id', $this->patient_id, PDO::PARAM_INT);
				$stmt->execute();
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

				if($result){

					$this->otherNotes = $result;

				}

			}catch(PDOException $e){

				$this->setFlash('error', "This went wrong when trying to fetch that person's other notes: ".$e->getMessage());

			}


	}

	public function setServices(){
		$db = $this->db;

		$sql = <<<EOT
			 SELECT services.id_services,
	     services.patient_id_services,
       services.goal_id_services,
       services.type,
       services.dos,
       services.charged,
       services.insurance_used,
       services.cpt_code,
       services.dx1,
       services.dx2,
       services.dx3,
       notes.completed

			 FROM services


			 LEFT JOIN notes AS notes ON notes.service_id_notes = services.id_services

			 WHERE patient_id_services = :patient_id
			 ORDER BY services.dos DESC;
EOT;

		try{

				$stmt = $db->db->prepare($sql);
				$stmt->bindParam(':patient_id', $this->patient_id, PDO::PARAM_INT);
				$stmt->execute();
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

				if($result){

					//echo "<pre>".print_r($result,true)."</pre>";
					$this->services = $result;
					//$this->services = $this->setSessionCount($this->services);

				}else{
					$this->setFlash('error', 'Something went wrong when fetching this patients services.' );
				}

			}catch(PDOException $e){

				$this->setFlash('error', $e->getMessage());

			}
	}

	public function setSessionCount($services=[]){

		$lateCancels = 0;
		$individual  = 0;
		$familyW     = 0;
		$familyWO    = 0;

		$total_I       = 0;
		$lateCancels_I = 0;
		$individual_I  = 0;
		$familyW_I     = 0;
		$familyWO_I    = 0;

		$total_NI       = 0;
		$lateCancels_NI = 0;
		$individual_NI  = 0;
		$familyW_NI     = 0;
		$familyWO_NI    = 0;

		foreach($services as $value){
			
			if( preg_match('/late/', strval($value['cpt_code']) ) ){
				$lateCancels++;
			}

			switch($value['cpt_code']){

				case 90834:
					$individual++;
					if( $value['insurance_used'] == 1 ){$individual_I++; $total_I++;}
					else{$individual_NI++; $total_NI++;}
					break;

				case 90791:
					$individual++;
					if( $value['insurance_used'] == 1 ){$individual_I++; $total_I++;}
					else{$individual_NI++; $total_NI++;}
					break;

				case 90832:
					$individual++;
					if( $value['insurance_used'] == 1 ){$individual_I++; $total_I++;}
					else{$individual_NI++; $total_NI++;}
					break;

				case 90846:
					$familyWO++;
					if( $value['insurance_used'] == 1 ){$familyWO_I++; $total_I++;}
					else{$familyWO_NI++; $total_NI++;}					
					break;

				case 90847:
					++$familyW;
					if( $value['insurance_used'] == 1 ){$familyW_I++; $total_I++;}
					else{$familyW_NI++; $total_NI++;}	
					break;

				default:
					break;

			}


		}

		$sessionCounts = array(
				'total'          => count($services),
				'total_I'			   => $total_I,
				'total_NI'       => $total_NI,
				"lateCancels"    => $lateCancels,
				"individual"     => $individual,
				'individual_I'   => $individual_I,
				'individual_NI'  => $individual_NI,
				"familyW"        => $familyW,
				'familyW_I'      => $familyW_I,
				'familyW_NI'     => $familyW_NI,
				"familyWO"	     => $familyWO,
				"familyWO_I"     => $familyWO_I,
				"familyWO_NI"     => $familyWO_NI
			);

		//$services['sessionCounts'] = $sessionCounts;

		return $sessionCounts;
		




	}

	public function combineOtherNotesAndServices($otherNotes=[], $services=[]){

		if(!empty($otherNotes)){
			
			foreach( $otherNotes as &$n ){

				$n['dos']							= new DateTime( $n['associated_date'] );
				$n['associated_date'] = new DateTime( $n['associated_date'] );
				$n['datetime'] 			  = strtotime( $n['associated_date']->format("Y-m-d H:i:s") );

			}

		}

		if(!empty($services)){
			
			foreach( $services as &$s ){

				$s['dos'] 		 = new DateTime( $s['dos'] );
				$s['datetime'] = strtotime( $s['dos']->format("Y-m-d H:i:s") );

			}

		}else{

			//other notes was empty and therefore there is nothing to combine. 
			$this->setFlash('error', "Service variable was empty. Nothing to combine.");
			return false;
		
		}

	if( !empty($otherNotes) ){

		foreach($otherNotes as &$value){

			array_push($services, $value);

		}

	}


	///////////////////
	//Then sort by date
  ///////////////////

  usort($services, function($a, $b) {

	  $ab = $a['datetime'];
	  $bd = $b['datetime'];

	  if ($ab == $bd) {
	    return 0;
	  }

	  return $ab < $bd ? 1 : -1;

	});

	return $services;

	}

	public function getServices($dateFormat=null){

		///////////////////////////////////////////////
		//if a string was passed in, then
		//move to format the date. Then return it
		///////////////////////////////////////////////

		if(isset($dateFormat)){

			switch($dateFormat){
				
				case "y-m-d":
					$newFormat = "Y-m-d";
					break;

				case "y-m-d h:m":
					$newFormat = "Y-m-d g:ia";
					break;
				
				default:
					$newFormat = "Y-m-d";
					break;

			}

			return $this->formatDate($newFormat);

		}


		/////////////////////////////////////
		//Otherwise return the services as is
		/////////////////////////////////////

		return $this->services;

	}

	public function getOtherNotes(){

		return $this->otherNotes;

	}

	private function formatDate($format){

		/////////////////////////
		//called from getServices
		/////////////////////////


		//////////////////////////////////////
		//loop through each service and 
		//replace the date with a formated date
		///////////////////////////////////////

		$withNewDate = [];
		//echo "<br>".print_r($this->services);

		$len = count($this->services);
		$c = 0;

		foreach($this->services as $value){

			//check to see if this is the last key and therefore the session counts
				
			$timezone = new DateTimeZone('America/Los_Angeles');
			$date = DateTime::createFromFormat('Y-m-d H:i:s', $value['dos'], $timezone);	
			$value['dos'] = $date->format($format);
			array_push($withNewDate, $value);

			$c++;

		}
		//echo "<pre>".print_r($withNewDate, true)."</pre>";
		return $withNewDate;


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

	private function fail($e, $message){

		echo $e . "<br>";
		echo $message;

	}
		
	
} //class


?>