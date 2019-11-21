<?php
	
	/*
			Grab the model that should already have been instaciated by the appController.
			It was passed the user_params which should be available in the $args property, 
			a string.
	*/

	$patients = $this->model;
	
	switch($patients->args) {
		
		case 'all':
			$patients->getAll();
			break;
		
		case 'active':
			$patients->getByActive(1);
			break;
		
		case 'inactive':
			$patients->getByActive(0);
			break;

		default:
			$patients->getAll();
			break;

	}
	

	$patients->setNamesAndIds();

	$names = $patients->getNamesAndIds();
	$newNames = [];
	$count = 0;

	$keys = array_keys($names);

	foreach($names as $key => $value){
		$newNames[$count] = array('patient_id' => $key, "patient_name" => $value);
		$count++;
	}

	echo json_encode($newNames);
	
?>