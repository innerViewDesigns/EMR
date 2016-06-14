<?php 

	if( !isset($patients) ){
		$patients = $this->model;
	}
	
	if( isset($this->lastInsertIds) ) {
		$rows = $patients->getSome($this->lastInsertIds);
	}else{
		$rows = false;
	}

?>

<article class='drop6' style="margin-left: -15px;">

	<?php 

	include(dirname(__DIR__)."/_flash.php"); 

		if($rows){
			
			echo "<table class='table col-md-12'>";
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
			echo print_r($services->getFlash(), true);
		}

	?>


</article>