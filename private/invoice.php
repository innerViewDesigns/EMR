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

		private $services = [];
		private $payments = [];
		private $dates    = [];


			function __construct($args=[])
			{

				$pdf = New PDF();
				$this->parseData($args['data']);
				//echo print_r($this->services, true);
				//echo print_r($this->payments, true);

				$tmp = $pdf->getData($this->services, $this->payments, $this->dates);
				
				if( gettype($tmp) === "string")
				{
						echo $tmp;
						die;
				}
				
				$pdf->prepare();
				$label = $pdf->getLabel();
				$pdf->Output('F', "/Users/Apple/SkyDrive/Therapy Business/BandM Commune/Invoices/".$pdf->getInvoiceDate().$label.$pdf->getName().".pdf");


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

							}
					
					}


			}

	}