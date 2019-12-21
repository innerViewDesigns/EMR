<?php

function deal_with_null_case($value){
	
	if( gettype($value) === 'array' ){

		foreach($value as $key => &$value_i){

			if($value_i === "" || $value_i === 'null'){
				$value_i = NULL;
			}

		}

	}elseif( gettype($value) === 'string'){

		if($value === "" || $value === 'null'){
				$value = NULL;
			}
	
	}

	return $value;

}

function is_multi($a) {

	/*
		The primary use case here is an array submitted by POST from the form on "add services"
		or "add other_payments." In these cases the array will be structured like this:

			data == array( patient_id == array([0] => patient_id1, [2] => patient_id2, [3] => patient_id3 )
										        CPT == array([0] => CPT1, [1] => CPT2, [3] => CPT3)
										)

		If this is true. You'll have to consolidate the data. Grouping each nth item of the nested arrays.
		That's what the function consolidate_params does. 
	*/

	if( is_array($a) ){

	  foreach ($a as $v) {

	    if (is_array($v)){

	    	if( array_key_exists('0', $v) ){
	    		
	    		return true;
	    	
	    	}else{

	    		return false;

	    	}

	    }else{

	    	return false;

	    }
	  }

	}else{

	  return false;

	}

}


function consolidateParams($args){

	

	$keys = array_keys($args);						 
	$organized = [];


	for($i=0; $i<count($args[$keys[0]]); $i++){   //loop through objects  

		foreach($args as $key => $value){    //pass in the fields with $value being the full param array 'first_name' => Array( 0 => 'Sam', 1 => 'george')
			
			if(!count($value) <= $i) {

				$organized[$i][$key] = $value[$i];	
			
			}
		
		}

	}

	return $organized;

}

function allowed_params($allowed_params=[]){

	$allowed_array = [];

	foreach($allowed_params as $param){
		fb( $param . ": " . $_POST[$param]);
		if( isset($_POST[$param]) ){
			$allowed_array[$param] = $_POST[$param];
		}else {
			$allowed_array[$param] = NULL;
		}
	}
	return $allowed_array;

} //allowed_params

function validate_token($token=""){

	global  $db;
  $token_from_db;
	$token_given = $token;
	$result;

	$result = $db->get_visitor_row( $_COOKIE['visitor_id'] );


	//////////////////////////////
	//do somethig with the results
	//////////////////////////////
	

	//load up the local properties
	$token_from_db = $result[0]['token'];


	if($token_given === $token_from_db)
		return true;
	else
		return false;

}

function validate_presence($value=""){

	if ( empty($value) || is_null($value) )
		return false;
	else
		return true;

} //validate_presence

function validate_length($str="", $min=1, $max=1){

	$length = strlen($str);

	if( $length >= $min && $length <= $max )
		return true;
	else
		return false;

} //validate_length




function validate_phone_number($num=""){

	$num = preg_replace( "/^[^0-9]$/", "", $num );

	if( strlen($num) === 11 && preg_match( "/^[1]/", $num))
		return true;
	else if (strlen($num) === 10)
		return true;
	else
		return false;


}//validate_phone_number


function validate_email( $email='' ){

	if( preg_match( "/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $email ) )
		return true;
	else
		return false;


}

function sanatize_names($name=""){

	$name = strip_tags($name);
	$name = preg_replace( "/^[^A-Z\sa-z\.\-]$/", "", "$name");
	return $name;

}//sanatize_names

function sanatize_phone( $phone=""){

	$phone = preg_replace("/^[^0-9]$/", "", "$phone");
	return $phone;

} //sanatize_phone




?>