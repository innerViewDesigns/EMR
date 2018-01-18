<?php

	require_once(__DIR__ . "/FirePHPCore/fb.php");
	
	require_once("/Users/Apple/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Apple/Sites/therapyBusiness/private');
  $classLoader->register();

  class insurances{

  	public   $services;
  	private  $db;
  	private  $rawData;
  	private  $flash = [];

		function __construct($args=[]){

			$this->db = new dbObj();

		}

		public function update($service_id, $values=[]){

			//echo print_r($service_id, true)."<br>";
			//deal with the values coming in structured as 0=>[service_id=>[] allowable_insurance_amount=>[]]
			//These will be from insurances::_update
			
			if(empty($values))
			{

					if( array_key_exists('service_id', $service_id) ){

							$values = $service_id;
							$service_id = $values['service_id'];

					}

			}

			$db = $this->db;
			//echo "<br>$service_id";
			//echo "<br>".print_r($values);

			$values = deal_with_null_case($values);

			try{

					$stmt = $db->db->prepare("UPDATE insurance_claim
						SET allowable_insurance_amount = :allowable,
				    		expected_copay_amount 		 = :expected, 
				    		recieved_insurance_amount  = :recieved, 
				   		  recieved_copay_amount 		 = :copay

						WHERE service_id_insurance_claim = :service_id");

					$stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
					$stmt->bindParam(':allowable', $values['allowable_insurance_amount']);
					$stmt->bindParam(':expected', $values['expected_copay_amount']);
					$stmt->bindParam(':recieved', $values['recieved_insurance_amount']);
					$stmt->bindParam(':copay', $values['recieved_copay_amount']);
					

					if($stmt->execute()){

						return $service_id;

					}else{

						$this->setFlash("error", "Something went wrong when trying to update insurance claim for service #" . $service_id.".");
						return false;
					}
					

				}catch (PDOException $e){

					$this->setFlash('error', $e->getMessage());
					return false;


				}


		} 

		public function setAllForPatientIncludeDOS($args)
		{
			
			$db = $this->db;

			$sql = <<<EOD

SELECT id_insurance_claim,
	   service_id_insurance_claim,
     recieved_insurance_amount,
     services.dos,
     services.cpt_code,
     services.insurance_used
       
FROM insurance_claim

JOIN therapy_practice.services AS services
on id_services = service_id_insurance_claim

WHERE patient_id_insurance_claim = 255 ORDER BY services.dos DESC;

EOD;
			
			try{

					$stmt = $db->db->prepare($sql);
					$stmt->bindParam(1, $args['patient_id'], PDO::PARAM_INT);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						$this->rawData = $result;

					}else{

						$this->setFlash("error", "No results from the getAllForPatient function in services.php");
						$this->rawData = [];
					}
					

				}catch (PDOException $e){

					$this->setFlash('error', $e);

				}			

		}

		public function setAllForPatient($args){
			
			$db = $this->db;
			
			try{

					$stmt = $db->db->prepare("SELECT * FROM insurance_claim WHERE patient_id_insurance_claim = ?");
					$stmt->bindParam(1, $args['patient_id'], PDO::PARAM_INT);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

					if($result){

						$this->rawData = $result;

					}else{

						$this->setFlash("error", "No results from the getAllForPatient function in services.php");
					
					}
					

				}catch (PDOException $e){

					$this->setFlash('error', $e);

				}

		}   

		public function setSomeByServiceId($args){

			$db = $this->db;
			$result = [];

			//build sql

			if(is_array($args)){
				
			$sql = <<<EOD

SELECT CONCAT(patient.first_name, ' ', patient.last_name) AS Name,
	   	 services.dos,
       services.cpt_code,
       services.insurance_used,
       services.in_network,
       insurances.allowable_insurance_amount,
       insurances.expected_copay_amount,
       insurances.recieved_copay_amount,
       insurances.recieved_insurance_amount,
       insurances.service_id_insurance_claim,
       insurances.patient_id_insurance_claim


FROM 

	(SELECT patient_id_services AS patient_id,
						dos,
            cpt_code,
            id_services,
            insurance_used,
            in_network
    FROM therapy_practice.services 
    WHERE id_services IN (

EOD;
				//then loop through that many while building the sql statement

				for($c = 0; $c < count($args); $c++){
					$sql .= $args[$c] . ", ";
				}

				$sql = trim($sql, ', ');
				$sql .= <<<EOD
								) ) AS services
    
    JOIN therapy_practice.patients AS patient
	ON patient.patient_id = services.patient_id

	JOIN therapy_practice.insurance_claim AS insurances
	ON service_id_insurance_claim = services.id_services;
EOD;

			}else{
				//add to the flash message and return false. 
				$this->setFlash("error", "the args variable in services::getSomeByServiceId was not an array");
				return false;
			}
			
			try{

					$stmt = $db->db->prepare($sql);
					$stmt->execute();
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				
					if(empty($result)){

						$this->setFlash('error', "No results when trying to pull the following services ids: " . print_r($args, true));
						return false;

					}else{

						return $result;

					}
					

				}catch (PDOException $e){

					echo "\n*************$e";
					$this->setFlash('error', "This from insurances::getSomeByServiceId - ".$e->getMessage());
					return false;

				}


		}

	public function pairClaimsAndPayments($claims, $services, $payments=array(), $invoice = false)
	{

			///////////////////////////////////
			//Payments will need an index for 
		  //the usort function below. That index will
		  //be 'datetime'
		  ///////////////////////////////////

			if( !empty( $payments )){

				foreach( $payments as &$p ){

					$p['datetime'] = strtotime( $p['date_recieved'] );
					$p['dos'] = new DateTime( $p['date_recieved'] );

				}

			}

			///////////////////////////////////
			//pair each claim with it's own DOS
		  ///////////////////////////////////

			foreach($claims as &$c){


				//if this WILL NOT be going to an invoice,
				//deal with null cases which come in blank

				if(!$invoice){


						if( $c['allowable_insurance_amount'] == "" ){

							$c['allowable_insurance_amount'] = "- ? -";

						}

						if( $c['expected_copay_amount'] == ""){

							$c['expected_copay_amount'] = "- ? -";

						}
				}


				///////////////////////////////////////////////
				//Then loop through $services and match them up
				///////////////////////////////////////////////

				foreach($services as &$s){

					
					if($c['service_id_insurance_claim'] == $s['id_services']){

						if(!$invoice)
						{

								//record whether insurance was used for this claim, and assign a contextual background class
								$c['insurance_used'] = ( $s['insurance_used'] == 1 ? 'bg-info' : '' );

								$timezone = new DateTimeZone('America/Los_Angeles');
								$c['dos'] = DateTime::createFromFormat('Y-m-d H:i:s', $s['dos'], $timezone);

								$c['cpt_code'] = $s['cpt_code'];

						}



						//Then give it something to compare with other claims by creating
						//a unix timestamp	
						$c['datetime'] = strtotime( $s['dos'] );


						//Then create another array indexed arbitrarly by service id. This will
						//be used for array multisort below
						$date[$s['id_services']] = strtotime( $s['dos'] );

						//Then shorten your loop and increase efficency
						unset($s);

						//Then break this loop and search for the next match.
						break;
					
					}
				}	
			}

			$claims =	array_merge($claims, $payments);


			///////////////////
			//Then sort by date
		  ///////////////////

		  usort($claims, function($a, $b) {

			  $ab = $a['datetime'];
			  $bd = $b['datetime'];

			  if ($ab == $bd) {
			    return 0;
			  }

			  return $ab < $bd ? 1 : -1;

			});

		  //now you have an ordered list of services with associated financial information and payments
			return $claims;

	}

	public function getClaims(){

		return $this->rawData;

	}

	private function setFlash($status, $message, $rowCount=null){

			if(empty($rowCount)){
				$this->flash[$status] = $message;
			}else{
				$this->rowCount += $rowCount;
				$this->flash[$status] = $rowCount . " " . $message;
			}

	}

	public function getFlash(){

		return $this->flash;

	}



 }
	