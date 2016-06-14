<?php

	if(!isset($patient)){
		
		$patient = $this->model;
		
	}
	$personalInfo = $patient->getPersonalInfo();


?>
<article class="col-md-12 drop6">

		<?php


			include(dirname(__DIR__)."/_flash.php");

			//Print Info from patient table....

			echo "<h3>Personal Information</h3>";
			echo "<div class='pull-left' style='width=100px; margin: 10px 25px;'>";
			foreach( $personalInfo as $key => $value){
				echo "<p class='text-right'>$key:</p>";
			}
			echo "</div>";
			echo "<div class='pull-left' style='margin-top: 10px;'>";
			foreach( $personalInfo as $key => $value){
				if(isset($value) and !empty($value)){
					echo "<p class='text-left'>$value</p>";
				}else{
					echo "<p class='text-left'>--</p>";
				}
			}
			echo "</div>";

			//Print address info....

		?>

</article>