<?php 
	
	if( isset($this->lastInsertIds) ) {
		$rows = $services->getSomeByServiceId($this->lastInsertIds);
	}else{
		$rows = false;
	}

?>

<article class='drop6 col-md-12' style="margin-left: -15px;">

	<?php 

	include(dirname(__DIR__)."/_flash.php"); 

		if($rows){
			
			echo "<table class='table col-md-12'>";
			echo "<tr>";
			foreach($rows[0] as $key => $value){
				
				if(preg_match('/patient_id/', $key)){
					continue;
				}

				echo "<th class='text-center'>$key</th>";
				
				
			}

			echo "</tr>";

			foreach( $rows as $value ){

				echo "<tr>";

				foreach( $value as $r => $v){

					if(preg_match('/patient_id/', $r)){
						continue;
					}

					if(preg_match('/name/', $r)){

						echo "<td class='text-center'><a href='http://localhost/~Apple/therapyBusiness/patient/get/" . $value['patient_id'] . "'>$v</a></td>";

					}else{

						echo "<td class='text-center'>$v</td>";
					
					}

				}

				echo "</tr>";

			}

			echo "</table>";
			

		}else{
			echo "No results<br>";
			echo print_r($services->getFlash(), true);
		}

	?>


</article>