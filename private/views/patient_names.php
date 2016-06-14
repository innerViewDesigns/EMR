<?php 
	
	if( !isset($patients) ){
		$patients = new patients(); 
		$patients->setNamesAndIds();
	}

?>

<article id="patient_names">
		<ul>

			<?php 

				foreach($patients->getNamesAndIds() as $key => $value){
					
					echo "<li><a href='#' data-patient-id='{$key}' data-toggle='popover' data-placement='right' data-link-to='popover'>$value</a></li>";

				} 

			?>

		</ul>

</article>