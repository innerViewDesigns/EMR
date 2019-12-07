<?php

	require_once(__DIR__ . "/FirePHPCore/fb.php");
	require_once(__DIR__ . "/validations.php");
	require_once("/Users/Lembaris/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Lembaris/Sites/therapyBusiness/private');
  $classLoader->register();

  class note{

		private $service_id, $patient_id, $insurance_claim_id, $invoice_id, $other_payments_id, $notes_id;
		private $note, $notes;
		private $flash = [];
		private $db;

		
		public function __construct($args = null) {

			$this->db = new dbObj();

			/*

				args will sometimes be a nested array. If so, detect that and correct for it.
				Then search for some of the needed information for various operations. 
				Apparently, there is a case for when the service_id is sent on it's own. 

			*/

			if( !empty($args) ){

				if(gettype($args) === 'array'){

					if(array_key_exists(0, $args))
					{
						$args = $args[0];
					}

					if(array_key_exists('service_id', $args)){
						$this->service_id = $args['service_id'];
					}
					if(array_key_exists('notes_id', $args)){
						$this->notes_id = $args['notes_id'];
					}

				}

			}elseif( gettype($args) === 'string'){

				$this->service_id = $args;
			
			}

		}    
		
		public function setNotebyId(){

			$db = $this->db;

			if($this->notes_id){
				
				try{

						$stmt = $db->db->prepare("SELECT * FROM notes WHERE notes_id = ?");
						$stmt->bindParam(1, $this->notes_id, PDO::PARAM_INT);
						$stmt->execute();
						$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

						if($result){

							$this->note = $result[0];
							return true;

						}else{
							
							$this->setFlash( array('Error', 'There was a problem in fetching the note with notes_id #' . $this->note_id . "."));
							return false;

						}
						

				}catch(PDOException $e){

					$this->setFlash(array('Error', "Something went wrong with note::setNotebyId. Here's the message: " . $e->getMessage()));

				}
			}else{

				return false;

			}
	
	}

		public function setNotebyServiceId(){

			$db = $this->db;

			if($this->service_id){
				
				try{

						$stmt = $db->db->prepare("SELECT * FROM notes WHERE service_id_notes = ?");
						$stmt->bindParam(1, $this->service_id, PDO::PARAM_INT);
						$stmt->execute();
						$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

						if($result){

							$this->note = $result[0];
							return true;

						}else{
							
							$this->setFlash(array('Error', 'There was a problem in fetching the note with service_id #' . $this->service_id . "."));
							return false;

						}
						

				}catch(PDOException $e){

					$this->setFlash(array('error', "Something went wrong with note::setNotebyServiceId. Here's the message: " . $e->getMessage()));

				}
			}else{
				return false;
			}
	
	}


	public function update($args){

		fb(print_r($args, true));

		$db = $this->db;


		if( isset($this->service_id) ){
			
			$sql = "UPDATE notes set note = ? WHERE service_id_notes = ?";
			$serviceId = true;

		}elseif( isset($this->notes_id) ){
			
			$sql = "UPDATE notes set note = ? WHERE notes_id = ?";
			$serviceId = false;
		
		}
		
		try{

			$stmt = $db->db->prepare($sql);
			$stmt->bindParam(1, $args['note']);

			if($serviceId)
			{	
				
				$stmt->bindParam(2, $this->service_id, PDO::PARAM_INT); 
			
			}else
			{ 
				$stmt->bindParam(2, $this->notes_id, PDO::PARAM_INT); 
			}

			if( $stmt->execute() )
			{

				if( $serviceId )
				{
					return $this->service_id;
				
				}else
				{
					return $this->notes_id;
				}

			}else{
				
				if( $serviceId )
				{
				
					$this->setFlash(array('Error', 'There was a problem in fetching the note with service_id #' . $this->service_id . "."));
				
				}else
				{ 
				
					$this->setFlash(array('error', 'There was a problem in fetching the note with notes_id #' . $this->notes_id . "."));

				}
				
				return false;

			}
					

			}catch(PDOException $e){

				$this->setFlash(array('error', "Something went wrong with note::update. Here's the message: " . $e->getMessage()));
				return false;
			}

	}

	public function create($args){

		$db = $this->db;
		$args = $this->sanatizeParams($args);

		if($args){

			try{

					$stmt = $db->db->prepare("INSERT INTO notes (patient_id_notes, service_id_notes,
																											insurance_claim_id_notes, invoice_id_notes,
																											other_payments_id_notes, associated_date,
																											type, note, completed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
					$stmt->bindParam(1, $args['patient_id_notes'], PDO::PARAM_INT);
					$stmt->bindParam(2, $args['service_id_notes'], PDO::PARAM_INT);
					$stmt->bindParam(3, $args['insurance_claim_id_notes'], PDO::PARAM_INT);
					$stmt->bindParam(4, $args['invoice_id_notes'], PDO::PARAM_INT);
					$stmt->bindParam(5, $args['other_payments_id_notes'], PDO::PARAM_INT);
					$stmt->bindParam(6, $args['associated_date'] );
					$stmt->bindParam(7, $args['type'], PDO::PARAM_STR);
					$stmt->bindParam(8, $args['note'], PDO::PARAM_STR);
					$stmt->bindParam(9, $args['completed']);
					

					$stmt->execute();
					$newId = $db->db->lastInsertId();

					if($newId){

						return $newId;

					}

				}catch(PDOException $e){

					$this->setFlash(array('Error', "This from notes::create - ".$e->getMessage()));
					return false;

				}

			}else{

				$this->setFlash( array('Error', "The args conditional in notes.php::create failed. Are you sure you passed something?"));
				return false;

			} 

	}


	public function getNote(){

		if(!empty($this->note))
		{
				return $this->note;
				
		}else
		{

			return $this->flash;

		}

	}

	private function setFlash(array $flash){

		array_push($this->flash, $flash);

	}

	public function getFlash(){

		return $this->flash;

	}

	private function mergeNewData($args){

		

	}

	private function sanatizeParams($args){

		//make sure that all files are present
		$requiredKeys = ['patient_id_notes', 'service_id_notes', 'insurance_claim_id_notes', 'invoice_id_notes', 'other_payments_id_notes', 'associated_date', 'type', 'note'];


		//deal with the special case of patient_id
		if( !array_key_exists('patient_id_notes', $args) ){

			$this->setFlash( array("Error", "Failed in notes.php::create - No patient ID given"));
			return false;

		}elseif( !array_key_exists('note', $args ) ){

			$this->setFlash( array("error", "Failed in notes.php::create - No note given"));
			return false;

		}
		
		$args['completed'] = 1;

		foreach( $requiredKeys as $value){
			
			if( !array_key_exists($value, $args) ){

				$args[$value] = null;

			}

		}

		return $args;


	}

		
	
} //class


?>