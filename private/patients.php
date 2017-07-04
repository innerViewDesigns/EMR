<?php

	require_once(__DIR__ . "/FirePHPCore/fb.php");
	
	require_once(__DIR__ . "/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, __DIR__);
  $classLoader->register();

  class patients{

  	public   $patients;
  	private  $db;
  	private  $rawData;
  	private  $namesAndIds = [];
  	public   $args;
  	public   $flash = [];

		
		function __construct($args=[]){

			$this->db = new dbObj();
			$this->args = $args;


		} 

		public function getAll(){
			
			$db = $this->db;
			
			try{

					$stmt = $db->db->prepare("SELECT * FROM patients");
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						$this->rawData = $result;

					}else{
						echo "no results";
					}
					

				}catch (PDOException $e){

					$this->setFlash('error', "in patients::getAll. The database error: ".$e->getMessage());

				}

		}   

		public function getByActive($arg=1){
			
			$db = $this->db;
			
			try{

					$stmt = $db->db->prepare("SELECT * FROM patients WHERE active = ?");
					$stmt->bindParam(1, $arg);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						$this->rawData = $result;

					}else{

						echo "no results from getByActive in patients";

					}
					

				}catch (PDOException $e){

					//echo "in patients::getByActive. The database error: ".$e->getMessage();
					$this->setFlash('error', "in patients::getByActive. The database error: ".$e->getMessage());

				}

		}   

		public function getPatients()
		{

				return $this->rawData;

		}

		public function getnamesAndIds(){

			return $this->namesAndIds;

		}

		public function setNamesAndIds(){

			
			if( is_array($this->rawData) ){
				foreach($this->rawData as $value){

					$this->namesAndIds[$value['patient_id']] = $value['first_name'] . " " . $value['last_name'];
							
				} 
			}else{

				$d = $this->rawData;
				$this->namesAndIds[$d['patient_id']] = $d['first_name'] . " " . $d['last_name'];

			}

		}

		public function getSome($args){

			$db = $this->db;
			$result = [];
			
			try{

					$stmt = $db->db->prepare("SELECT * FROM patients WHERE patient_id = ?");

					if( is_array($args) ){

						foreach( $args as $value){

							$stmt->bindParam(1, $value);
							$stmt->execute();
							$temp = $stmt->fetchAll(PDO::FETCH_ASSOC);
							array_push( $result, $temp[0] );

						}
					}else{

						$stmt->bindParam(1, $args);
						$stmt->execute();
						$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					}
					

					if(empty($result)){

						$this->setFlash('error', "no results after trying to fetch those patient ids");

					}else{

						$this->rawData = $result;

					}
					

				}catch (PDOException $e){

					$this->setFlash('error', "in patients::getSome. The database error: ".$e->getMessage());

				}


		}

		private function setFlash($status, $message){

			$this->flash = array($status=>$message);

		}

		public function getFlash(){
			return $this->flash;
		}



 }
	