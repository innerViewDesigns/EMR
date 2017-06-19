<?php 
	if( !isset($insurances) ){
		$insurances = $this->model;
	}

	if( isset($this->lastUpdatedIds) ) {
		$rows = $insurances->setSomeByServiceId($this->lastUpdatedIds);
		$this->flash = array_merge_cust($this->flash, $insurances->getFlash());
	}

?>

<article class='drop6' style="margin-left: -15px;">

	<?php 

	include(dirname(__DIR__)."/_flash.php"); 

		if(isset($rows)){
			
			echo "<table class='table'>";
			echo "<tr>";
				
				echo "<th class='text-center'>Name</th>";
				echo "<th class='text-center'>DOS</th>";
				echo "<th class='text-center'>Allowable</th>";
				echo "<th class='text-center'>Expected Copay</th>";
				echo "<th class='text-center'>Recieved</th>";

			echo "</tr>";

			foreach( $rows as $v ){
				echo "<tr>";
	
					echo "<td class='text-center'>".$v['Name']."</td>";
					echo "<td class='text-center'>".$v['dos']."</td>";
					echo "<td class='text-center'>".$v['allowable_insurance_amount']."</td>";
					echo "<td class='text-center'>".$v['expected_copay_amount']."</td>";
					echo "<td class='text-center'>".$v['recieved_insurance_amount']."</td>";
				
				echo "</tr>";

			}

			echo "</table>";
			

		}else{
			echo "No results<br>";
			//echo print_r($services->getFlash(), true);
		}

	?>


</article>