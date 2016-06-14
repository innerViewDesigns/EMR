<?php

	$service = $this->model;
	$service->setService();
	echo json_encode($service->getService());
	
?>