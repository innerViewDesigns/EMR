<?php

	require_once("FirePHPCore/fb.php");
	require_once("includes/db.php");


	$patientId   = $_GET['patient_id'];
	$table_name  = $_GET['table_name'];
	
	global $db;

	try{

		if(preg_match('/services/i', $table) ){

			$stmt = $db->db->prepare("SELECT * 
																FROM services 
																WHERE patient_id_services = ?
																ORDER BY dos ASC");
			fb("it was " + $table);

		}else if($table === 'insurance_claim'){

			$stmt = $db->db->prepare("SELECT * 
																FROM insurance_claim
																WHERE patient_id_insurance_claim = ?");


		}


		$stmt->bindParam(1, $patientId, PDO::PARAM_INT);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);


		if($result){

			/////////////////////////////////////////
			//retrieve dos from the service table for
			//each insurance_claim
			/////////////////////////////////////////

			if($table === 'insurance_claim'){

				foreach($result as & $index){

					$stmt = $db->db->prepare("SELECT dos 
											    FROM services
													WHERE id_services = ?");

					$stmt->bindParam(1, $index['service_id_insurance_claim'], PDO::PARAM_INT);
					$stmt->execute();
					$dos = $stmt->fetchAll(PDO::FETCH_ASSOC);


					//chop time stamp
					$dos[0]['dos'] = preg_replace('/ (\d{2}:){2}\d{2}/', '', $dos[0]['dos'] );

					$index['dos'] = $dos[0]['dos'];


				}

				//order based on dos
				$ordered_result = [];
				$dos = [];

				foreach($result as $key => $value){

					$dos[$key] = $value['dos'];

					
					//get the dos from last row
					//compare last dos to this dos
					//make decion on where to place this dos relative to last dos

				}

				array_multisort($dos, SORT_ASC, $result);

			}else{

				foreach($result as & $index){

					$index['dos'] = preg_replace('/ (\d{2}:){2}\d{2}/', '', $index['dos'] );

				}


			}

			echo json_encode($result); 

		}else{

			$sql = "DESCRIBE " . $table;
			$q = $db->db->prepare($sql);
			$q->execute();
			$table_fields = $q->fetchAll(PDO::FETCH_COLUMN);
			
			$result = [];

			foreach($table_fields as $key => $value){

				$result[$value] = null;

			}

			$new_result[0] = $result;

			fb(json_encode($new_result));

			echo json_encode($new_result);


		}
		

	}catch (PDOException $e){

		fb($e);

	}



?>

