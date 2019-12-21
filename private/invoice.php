<?php


	require_once("Users/Lembaris/Sites/therapybusiness/vendor/setasign/fpdf/fpdf.php");
	require_once(__DIR__ . "/fpdf_custom.php");
	require_once(__DIR__ . "/insurances.php");
	require_once(__DIR__ . "/Services.php");
	require_once(__DIR__ . "/otherPayments.php");
	require_once(__DIR__ . "/patient.php");


	class invoice
	{

		private $services = [];
		private $payments = [];
		private $patientId = '';


			function __construct($args=[])
			{	

				/*
						
						$args is an array with two key of note: service_ids and payment_ids. It's structured 
						like this:

						count($data) = 1
							count($data[0]) = 3
								data-service-id
								data-payment-id
								data-patient-id

						Get the service and payment ids and assign them to properties of this object. Then send that information to 
						the pdf object. 
				*/

				$this->parseData($args['data']);

				$pdf = New PDF();
				$data = $pdf->myConstruct($this->services, $this->payments, $this->patientId);
				
				
				$pdf->prepare($data);
				$label = $pdf->getLabel();
				$pdf->Output('F', "/Users/Lembaris/SkyDrive/Therapy Business/BandM Commune/Invoices/".$pdf->getInvoiceDate().$label.$pdf->getName().".pdf");


			}

			private function parseData($args=[])
			{

					if(is_array($args))
					{

							if(array_key_exists('data-service-id', $args))
							{
									$this->services = $args['data-service-id'];
							}

							if(array_key_exists('data-payment-id', $args))
							{

									$this->payments = $args['data-payment-id'];					

							}if(array_key_exists('data-patient-id', $args))
							{

									$this->patientId = $args['data-patient-id'];
							}
					
					}

					if(empty($this->services) && empty($this->payments))
					{
						echo "Error. No data given.";
						die;
					}


			}

	}