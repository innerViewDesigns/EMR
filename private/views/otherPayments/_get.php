<?php 

	if( !isset($otherPaymentss) ){
		$otherPayments = $this->model;
	}

	if( isset($this->lastInsertIds) ) {
		$rows = $otherPayments->getSomeById($this->lastInsertIds);
		$this->flash = array_merge_cust($this->flash, $otherPayments->getFlash());
	}

?>

<article class='drop6 col-md-12' style="margin-left: -15px;">
	<h3>From otherPayments</h3>

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