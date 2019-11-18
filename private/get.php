<?php

	require_once(__DIR__ . "/FirePHPCore/fb.php");
	require_once("/Users/Lembaris/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Lembaris/Sites/therapyBusiness/private');
  $classLoader->register();

class get_services_and_claims{

	private $patient_id;
	private $table_name;
	private $service_id;

	function __construct($previous_flash = null, $patient_id = null, $table_name = null){

		fb("get construct");

		if( isset( $_GET['patient_id'] ) ){

			$this->patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;
			$this->table_name = isset($_GET['table_name']) ? strtolower($_GET['table_name']) : null;

		}elseif( isset($patient_id) && isset($table_name) ){

			$this->patient_id = $patient_id;
			$this->table_name = $table_name;

		}
		
		if( isset( $previous_flash ) ){
				
				$this->add_message_from_post( $previous_flash );
		}

		if( isset( $this->table_name ) and isset( $this->patient_id ) )  {

			$this->prepare_stmt_get_by_patientId();
			$this->bind_params_get_by_patientId();
			$this->execute();
			$this->echo_results();
		}

	}

	function service($key){

		$this->table_name  = 'services';
		$this->serviceId_column_name = "id_services";
		return $this->prepare_stmt_get_by_serviceId($key);

	}

	function insurance_claim($key){

		$this->table_name = 'insurance_claim';
		$this->serviceId_column_name = "service_id_insurance_claim";
		return $this->prepare_stmt_get_by_serviceId($key);

	}

	function prepare_stmt_get_by_serviceId($key){

		global $db;

		if( isset($this->table_name) && isset($this->serviceId_column_name))

			try{

				$sql = "SELECT * FROM " . $this->table_name . " WHERE " . $this->serviceId_column_name . " = :service_id";
				$stmt = $db->db->prepare($sql);
				$stmt->bindParam(":service_id", $key);
				$stmt->execute();
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

				return $result;

			}catch(PDOException $e){

				$this->fail($e, 'prepare_stmt_get_by_serviceId');

			}

	}

	function prepare_stmt_get_by_patientId(){

		global $db;

		switch( $this->table_name ){

			case 'services':
				try{

					$this->stmt = $db->db->prepare("SELECT * 
																FROM services 
																WHERE patient_id_services = ?
																ORDER BY dos ASC");
				
				}catch(PDOException $e){

					$this->fail($e, 'GET.php: prepare services statement');

				}
				break;

			case 'insurance_claim':
				try{
					
					$this->stmt = $db->db->prepare("SELECT * 
																FROM insurance_claim
																WHERE patient_id_insurance_claim = ?");
				
				}catch(PDOException $e){

					$this->fail($e, 'GET.php: prepare insurance_claim statement');

				}
				break;

		}
	
	}

	function bind_params_get_by_patientID(){
		
		try{

			$this->stmt->bindParam(1, $this->patient_id, PDO::PARAM_INT);

		}catch(PDOException $e){

			$this->fail($e, 'bind_params_services');

		}

	} 


	function echo_results(){

		global $db;

		if($this->result["Database Results"]){

			/////////////////////////////////////////
			//retrieve dos from the service table for
			//each insurance_claim
			/////////////////////////////////////////

			if($this->table_name === 'insurance_claim'){

				foreach($this->result["Database Results"] as & $index){

					$stmt = $db->db->prepare("SELECT dos 
											    FROM services
													WHERE id_services = ?");

					$stmt->bindParam(1, $index['service_id_insurance_claim'], PDO::PARAM_INT);
					$stmt->execute();
					$dos = $stmt->fetchAll(PDO::FETCH_ASSOC);

					//chop time stamp
					$dos[0]['dos'] = preg_replace('/ (\d{2}:){2}\d{2}/', '', $dos[0]['dos'] );

					$index['dos'] = $dos[0]['dos'];

				}

				foreach($this->result["Database Results"] as $key => $value){

					$dos[$key] = $value['dos'];

				}

				array_multisort($dos, SORT_ASC, $this->result["Database Results"]);

			}else{

				foreach($this->result["Database Results"] as & $index){

					$index['dos'] = preg_replace('/ (\d{2}:){2}\d{2}/', '', $index['dos'] );

				}

			}

			echo json_encode($this->result);

		}else{

			//if there were not results, i.e., this is a new patient, then
			//formate the results to return a parsable array of column names
			//Don't worry about there possibly being a post message.

			$sql = "DESCRIBE " . $table;
			$q = $db->db->prepare($sql);
			$q->execute();
			$table_fields = $q->fetchAll(PDO::FETCH_COLUMN);
			
			$result = [];

			foreach($table_fields as $key => $value){

				$result[$value] = null;

			}

			$new_result[0] = $result;

			echo json_encode($new_result);

		}
	}

	function execute(){
		
		if(	$this->stmt->execute() ){

			$this->result['Database Results'] = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
		
		}else{

			$this->fail( $this->stmt->errorCode(), 'execute' );

		}

	}

	function fail($e, $function_name){

		if( isset( $this->result['Database Status'] ) ){
			array_push($this->result['Database Status'], array("Get Status" => "Error", "Where" => $function_name, "What" => $e));
		}else{

			$this->result['Database Status']['Status'] = 'Error';
			$this->result['Database Status']['where'] = $function_name;
			$this->result['Database Status']['what'] = $e;
		}

		fb("FAIL!!");

		echo json_encode($this->result);

		die();

	}

	function add_message_from_post($previous_flash){

		$this->result['Database Status']['From Post'] = $previous_flash['Database Status'];	

	}

	function __destruct(){

		//fb("destrying the get object");

	}

}

if( !isset($get_services_and_claims) ){

	$get_services_and_claims = new get_services_and_claims();

}
?>