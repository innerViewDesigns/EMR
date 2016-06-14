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
			foreach($rows[0] as $key => $value){
				
				echo "<th class='text-center'>$key</th>";
				
			}

			echo "</tr>";

			foreach( $rows as $value ){
				echo "<tr>";
				foreach( $value as $r => $v){
					echo "<td class='text-center'>$v</td>";
				}
				echo "</tr>";

			}

			echo "</table>";
			

		}else{
			echo "No results<br>";
			//echo print_r($services->getFlash(), true);
		}

	?>


</article>