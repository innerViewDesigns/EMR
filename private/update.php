<?php

set_include_path("/Users/Lembaris/Sites/therapyBusiness/private/");
require_once("validations.php");
require_once("get.php");
require_once("FirePHPCore/fb.php");
require_once("includes/db.php");


class update
{
	public  $table_name;
	private $service_id;
	public  $fillExpectedCopayFields;
	private $safe_to_fill_copay_fields;
	public  $patient_id;

	public  $stmt;
	private $rowCount;


	public function __Construct(){

		fb("New update instance");


		$this->table_name = isset($_POST['table_name']) ? $_POST['table_name'] : null;
		$this->patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : null;

		if($this->table_name === null || $this->patient_id === null){

			$this->fail("service_id or table_name wasn't set", "Failed in the construct statement");

		}



		/////////////////////////////////////
		// Set other needed properties
		/////////////////////////////////////

		$this->fillExpectedCopayFields = isset($_POST['fillExpectedCopayFields']) ? $_POST['fillExpectedCopayFields'] : 'false';
		$this->safe_to_fill_copay_fields = true;



		////////////////////////////////////
		// Set the post operation in motion
		////////////////////////////////////

		$this->prepare_stmt();
			////////////////////////////////////////////////////////////////////////////////
			// The prepare_stmt function will call the appropriate bind_params function
		  ////////////////////////////////////////////////////////////////////////////////

		$this->execute();
			////////////////////////////////////////////////////////////////////////////
			// The exectue function will call the apppropriate assing variables function
			// The assign variables funciton will then pull the old values
			// You have not yet tested the service function update
		  ////////////////////////////////////////////////////////////////////////////

	}

	function prepare_stmt(){
		global  $db;

		switch( $this->table_name ){

			case 'services':
				try{

					$this->stmt = $db->db->prepare( "UPDATE services 
						SET type = :type, dos = :dos, note = :note, charged = :charged, insurance_used = :insurance_used, cpt_code = :cpt_code 
						WHERE id_services = :service_id" );
				
				}catch(PDOException $e){

					$this->fail($e, 'prepare services statement');

				}

				$this->bind_params_services();
				break;

			case 'insurance_claim':
				try{
					
					$this->stmt = $db->db->prepare( "UPDATE insurance_claim
						SET insurance_name = :name, allowable_insurance_amount = :allowable,
				    	expected_copay_amount = :expected, recieved_insurance_amount = :recieved, 
				   		 recieved_copay_amount = :copay
						WHERE service_id_insurance_claim = :service_id");
				
				}catch(PDOException $e){

					$this->fail($e, 'prepare insurance_claim statement');

				}

				$this->bind_params_insurance_claim();
				break;

		}
	
	}

	function bind_params_services(){
		try{

			$this->stmt->bindParam(':service_id', $this->service_id, PDO::PARAM_INT);
			$this->stmt->bindParam(':type', $this->type, PDO::PARAM_STR);
			$this->stmt->bindParam(':dos', $this->dos);
			$this->stmt->bindParam(':note', $this->note, PDO::PARAM_STR);
			$this->stmt->bindParam(':charged', $this->charged, PDO::PARAM_INT);
			$this->stmt->bindParam(':insurance_used', $this->insurance_used, PDO::PARAM_INT);
			$this->stmt->bindParam(':cpt_code', $this->cpt_code, PDO::PARAM_INT);

		}catch(PDOException $e){

			$this->fail($e, 'bind_params_services');

		}

	}

	function bind_params_insurance_claim(){

		try{

			$this->stmt->bindParam(':service_id', $this->service_id, PDO::PARAM_INT);
			$this->stmt->bindParam(':name', $this->insurance_name);
			$this->stmt->bindParam(':allowable', $this->allowable);
			$this->stmt->bindParam(':expected', $this->expected);
			$this->stmt->bindParam(':recieved', $this->recieved);
			$this->stmt->bindParam(':copay', $this->copay);

		}catch(PDOException $e){

			$this->fail($e, 'bind_params_insurance_claim');

		}

	}

	function load_values_services($values){

		if($values){

			foreach($values as $key => $value){

				if( preg_match('/type/', $key) ){

						$this->type = $value;
					
					}

					elseif( preg_match('/dos/', $key) ){
						$this->dos = $value;

					}

					elseif( preg_match('/note/', $key) ){
						$this->note = $value;

					}

					elseif( preg_match('/charged/', $key) ){
						$this->charged = $value;

					}

					elseif( preg_match('/insurance_used/', $key) ){
						$this->insurance_used = $value;

					}

					elseif( preg_match('/cpt_code/', $key) ){
						$this->cpt_code = $value;

					}
			
			}

		}

	}

	function load_values_insurance_claim($values){

			if($values){

				foreach($values as $key => $value){

					if( preg_match('/insurance_name/', $key) ){
						$this->insurance_name = $value;
					}

					elseif( preg_match('/allowable_insurance_amount/', $key) ){
						$this->allowable = $value;
					}

					elseif( preg_match('/expected_copay_amount/', $key) ){
						$this->expected = $value;
					}

					elseif( preg_match('/recieved_insurance_amount/', $key) ){
						$this->recieved = $value;

					}

					elseif( preg_match('/recieved_copay_amount/', $key) ){
						$this->copay = $value;

					}

				}//for loop

			}//if result


	}


	function assign_variables_services($key, $value){

			
			$this->service_id = $key;
			$value = deal_with_null_case($value);

			fb("$key: " . $key);

			//pull existing record
			if( !isset($get_services_and_claims) ){
				$get_services_and_claims = new get_services_and_claims();
			}

			$oldValues = $get_services_and_claims->service($key);

			//load old values into this classes properties
			$this->load_values_services($oldValues[0]);

			//now load the new values
			$this->load_values_services($value);


	}

	function assign_variables_insurance_claim($key, $value){

		/////////////////////////////////////////////////////////////////////////
		// $key is the service id
		// $value is the update values array associated withat that service id
		/////////////////////////////////////////////////////////////////////////

		global $db;
		global $get_services_and_claims;

		$this->service_id = $key;
		$value = deal_with_null_case($value);

		///////////////////////
		// Get the old values
		///////////////////////

		
		$oldValues = $get_services_and_claims->insurance_claim($key);

		/////////////////////////////////////////////
		//load old values into this classes properties
		/////////////////////////////////////////////

		$this->load_values_insurance_claim($oldValues[0]);

		/////////////////////////
		//now load the new values
		/////////////////////////

		$this->load_values_insurance_claim($value);

		/////////////////////////////////////////////
		//Fill all of the expected copays if needed
		/////////////////////////////////////////////

		if( isset($value['expected_copay_amount']) && $this->fillExpectedCopayFields === 'true'){

			if($this->safe_to_fill_copay_fields){

				try{
					$stmt_ii = $db->db->prepare("UPDATE insurance_claim
										SET expected_copay_amount = ?
										WHERE patient_id_insurance_claim = ?");

					//////////////////////////////////////////////////
					// if you want to reset these values, pass in a 0
					//////////////////////////////////////////////////

					$this->expected = $value['expected_copay_amount'] === "0" ? null : $this->expected;
					$stmt_ii->bindParam(1, $this->expected);
					$stmt_ii->bindParam(2, $this->patient_id);
					$stmt_ii->execute();

					$this->result['Database Status']['Expected Copay Rows Altered'] = $stmt_ii->rowCount();

				}catch(PDOException $ee){

					fb("Effort to set other expected_copay_amounts didn't work. Here's the error: ");
					fb($ee);

				}

				//reset the trigger to prevent this sub-function from firing twice
				$this->safe_to_fill_copay_fields = false;

			}

		}
		
	}

	function execute(){

		$this->rowCount = 0;

		try{

			foreach($_POST['update_data'] as $key => &$value){

				switch($this->table_name){
					case "services":
						$this->assign_variables_services($key, $value);
						break;

					case 'insurance_claim':
						$this->assign_variables_insurance_claim($key, $value);
						break;
				}

				if(	$this->stmt->execute() ){

					$this->rowCount++;
				
				}else{

					$this->fail( $this->stmt->errorCode(), 'execute from the switch statement' );

				}

			}


		}catch(PDOException $e){

			$this->fail($e, 'execute in the update file');

		}
		///////////////////////////////////////////////////
		// push the success message onto the results object
		//   (This message was carefully formatted)
		///////////////////////////////////////////////////
		$this->success();

	} 

	function fail($e, $function_name){

		global $post_instance;

		$this->result['Database Status']['Status'] = 'Update Error';
		$this->result['Database Status']['where'] = $function_name;
		$this->result['Database Status']['what'] = $e;

		$post_instance->from_update($this->result);

		$this->__deconstruct();

		die();
		

	}

	function success(){

		global $post_instance;
		fb(gettype($post_instance));

		$this->result['Database Status']['Status'] = 'Update Success';
		$this->result['Database Status']['Row Count'] = $this->rowCount;

		$post_instance->from_update($this->result);

	}


	function __destruct(){

		//fb("deconstruct the udate object");


	}

}


?>