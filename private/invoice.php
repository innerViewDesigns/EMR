<?php
	
	//set_include_path(__DIR__ . "/fpdf181/");

	require(__DIR__ . "/fpdf181/fpdf.php");
	require(__DIR__ . "/fpdf181/fpdf_custom.php");
	require(__DIR__ . "/insurances.php");
	require(__DIR__ . "/Services.php");
	require(__DIR__ . "/otherPayments.php");
	require(__DIR__ . "/patient.php");


	class invoice
	{

			function __construct($args=[])
			{

				$pdf = New PDF();
				$pdf->prepare($args);

			}

	}