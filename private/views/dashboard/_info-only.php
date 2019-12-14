<?php
	
	$dashboard = $this->model;
	$flash = $dashboard->getFlash();

	switch($dashboard->user_param)
	{
		case 'database-backup':
			echo json_encode($flash);
			break;

		case 'add-claims-to-file':
			echo "From dashboard/_info-only :: add-claims-to-file";
			break;

		default:
			break;
	}

	

?>