<?php

	require_once(__DIR__ . "/FirePHPCore/fb.php");
	require_once("/Users/Apple/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Apple/Sites/therapyBusiness/private');
  $classLoader->register();

class insert
{
	public  $table_name;
	public  $stmt;
	private $rowCount;
	private $result;

	public function __Construct(){
		
		$this->table_name = isset($_POST['table_name']) ? $_POST['table_name'] : null;
		$this->patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : null;


		$this->prepare_stmt();
		$this->execute();

		fb('new insert object constructed');

	}

	function prepare_stmt(){
		global  $db;

		switch( $this->table_name ){

			case 'patients':
				try{

					$this->stmt = $db->db->prepare( "INSERT INTO patients (first_name, last_name) VALUES (?, ?)");
				
				}catch(PDOException $e){

					$this->fail($e, 'prepare patients statement');

				}

				$this->bind_params_patients();
				break;

			case 'services':
				try{
					
					$this->stmt = $db->db->prepare( "INSERT INTO services (patient_id_services, type, dos, note, charged, insurance_used, cpt_code) 
												VALUES (:patient_id_services, :type, :dos, :note, :charged, :insurance_used, :cpt_code)");
				
				}catch(PDOException $e){

					$this->fail($e, 'prepare services statement');

				}

				$this->bind_params_services();
				break;

		}
	
	}

	function bind_params_patients(){
		try{

			$this->stmt->bindParam(1, $this->first_name);
			$this->stmt->bindParam(2, $this->last_name);

		}catch(PDOException $e){

			$this->fail($e, 'bind_params_patients');

		}

	}

	function bind_params_services(){
		try{

			$this->stmt->bindParam(':patient_id_services', $this->patient_id, PDO::PARAM_INT);
			$this->stmt->bindParam(':type', $this->type, PDO::PARAM_STR);
			$this->stmt->bindParam(':dos', $this->dos, PDO::PARAM_STR);
			$this->stmt->bindParam(':note', $this->note, PDO::PARAM_STR);
			$this->stmt->bindParam(':charged', $this->charged, PDO::PARAM_INT);
			$this->stmt->bindParam(':insurance_used', $this->insurance_used, PDO::PARAM_INT);
			$this->stmt->bindParam(':cpt_code', $this->cpt_code, PDO::PARAM_INT);

		}catch(PDOException $e){

			$this->fail($e, 'bind_params_services');

		}

	}

	function assign_variables_services($row){

		$row = deal_with_null_case($row);

		$this->type = $row['type'];
		$this->dos = $row['dos'];
		$this->note = $row['note'];
		$this->charged = $row['charged'];
		$this->insurance_used = $row['insurance_used'];
		$this->cpt_code = $row['cpt_code'];

	}

	function assign_variables_patients($row){

		$row = deal_with_null_case($row);

		$this->first_name = $row['first_name'];
		$this->last_name = $row['last_name'];


	}

	function execute(){

		$this->rowCount = 0;

		try{

			foreach($_POST['insert_data'] as &$row){

				switch($this->table_name){
					case "services":
						$this->assign_variables_services($row);
						break;

					case 'patients':
						$this->assign_variables_patients($row);
						break;
				}

				if(	$this->stmt->execute() && $this->stmt->rowCount() ){

					$this->rowCount++;
				
				}else{

					$this->fail( $this->stmt->errorCode(), 'execute' );

				}

			}


		}catch(PDOException $e){

			$this->fail($e, 'execute');

		}

		$this->success();
	} //execute

	function fail($e, $function_name){

		global $post_instance;

		$this->result['Database Status']['Status'] = 'Insert Error';
		$this->result['Database Status']['Where'] = $function_name;
		$this->result['Database Status']['What'] = $e;

		//echo json_encode($this->result);
		$post_instance->from_insert($this->result);

		$this->__destruct();

		die();

	}

	function success(){

		global $post_instance;

		$this->result['Database Status']['Status'] = 'Insert Success';
		$this->result['Database Status']['Row Count'] = $this->rowCount;
		//echo json_encode($this->result);

		$post_instance->from_insert($this->result);

	}

	function __destruct(){



	}

}

?>