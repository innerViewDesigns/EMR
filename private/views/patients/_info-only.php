<?php
	
	$patients = $this->model;

	
	switch($patients->args[0]) {
		
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