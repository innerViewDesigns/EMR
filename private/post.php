<?php


set_include_path("/Users/Lembaris/Sites/therapyBusiness/private/");
require_once("validations.php");
require_once("update.php");
require_once("insert.php");
require_once("get.php");
require_once("FirePHPCore/fb.php");
require_once("includes/db.php");

class post{

	public $ctr_from_post;
	public $ctr_from_php;

	public $insert_message;
	public $update_message;

	public $insert_data;
	public $update_data;

	private $table_name;
	private $patient_id;
	private $service_id;


	function __Construct(){

		fb('New post instance created.');
		fb("POST data: ");
		fb($_POST);

		$this->ctr_from_post = 0;
		$this->ctr_from_php = 0;

		$this->patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : null;
		$this->servive_id = isset($_POST['servive_id']) ? $_POST['servive_id'] : null;
		$this->table_name = isset($_POST['table_name']) ? $_POST['table_name'] : null;

		//three functions submit to post:
			// 			  services : patient_id and table_name will be set
			// 					 	   can be update or insert
		  // insurance_claim : service_id and table_name will be set
			//							 can be update or insert
			//			  patients : table_name will be set
			//					 		 can be update or insert

		if( isset($_POST['insert_data']) ){ //could be insurance, service, or patients

			fb('insert data detected by php');

			++$this->ctr_from_post;
			$this->insert_data = true;

		}

		if( isset($_POST['update_data'] ) ){ //could be insurance, service, or patients
			
			fb('update data was detected by php');

			++$this->ctr_from_post;
			$this->update_data = true;
		}


	}

	public function from_update($update_message){

		$this->update_message = $update_message;
		++$this->ctr_from_php;

		if( $this->ctr_from_php === $this->ctr_from_post){

			$this->call_get();

		}

	}

	public function from_insert($insert_message){

		$this->insert_message = $insert_message;
		++$this->ctr_from_php;

		if( $this->ctr_from_php === $this->ctr_from_post){

			$this->call_get();

		}

	}

	function call_get(){

		if( $this->ctr_from_post === 2 ){

			$this->update_message[] = $this->insert_message;
			$this->outgoing_message = $this->update_message;


		}elseif( $this->ctr_from_post === 1 ){

			$this->outgoing_message = isset( $this->update_message ) ? $this->update_message : $this->insert_message;

		}
		
		if($this->table_name !== 'patients'){
			$get_services_and_claims = new get_services_and_claims($this->outgoing_message, $this->patient_id, $this->table_name);
		}else{
			echo json_encode($this->outgoing_message);
		}

	}

	

}

	$post_instance = new post();

	$update_instance = $post_instance->update_data ? new update() : null;
	$insert_instance = $post_instance->insert_data ? new insert() : null;





?>
