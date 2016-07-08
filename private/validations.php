<?php


  require_once(__DIR__ . "/dbObj.php");
	require_once(__DIR__ . "/FirePHPCore/fb.php");
	require_once(__DIR__ . "/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, __DIR__);
  $classLoader->register();

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

function array_merge_cust($arr1, $arr2){

	$cnt1 = count($arr1);
	$cnt2 = count($arr2);

	if($cnt1 > $cnt2){

		if($cnt1 == 0){
			return array_merge($arr1, $arr2);
		}

		$keys2 = array_keys($arr1);
		$keys1 = array_keys($arr2);

	}else{

		if($cnt2 == 0){
			return array_merge($arr1, $arr2);
		}

		$keys1 = array_keys($arr1);
		$keys2 = array_keys($arr2);

	}

	$count = 0;

	//loop through the first set of keys
	foreach($keys1 as $key){

		//if this key is equal to the key in the other array at the same position
		if($key == $keys2[$count]){

			//create a new key value pair in the second array by appending a numeral to the key and 
			//preserving the value
			$arr2[$key.$count] = $arr2[$key];

			//then destroy the original key => value pair.
			unset($arr2[$key]);
		}

		$count++;
	}


	return array_merge($arr1, $arr2);

}

function consolidateParams($args){

	$keys = array_keys($args);						 //get all of the field names
	$organized = [];

	//echo "<br>keys: " . print_r($keys, true);
	//echo "<br>count: " . count($keys);
	//echo "<br>count: " . count($keys[0]);

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