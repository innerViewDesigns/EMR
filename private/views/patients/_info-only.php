<?php
	
	$patients = $this->model;
	$patients->getAll();
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