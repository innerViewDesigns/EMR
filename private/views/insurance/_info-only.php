<?php

	$insurance = $this->model;
	if(!$insurance->setInsurance()){
		echo json_encode($insurance->getFlash());
	}else{
		echo json_encode($insurance->insurance_claim);
	}



?>