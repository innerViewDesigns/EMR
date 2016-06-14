<?php 
	if($this->action !== 'create'){
		$patient = $this->model; 
	}
?>
<!DOCTYPE html>
<html class="no-js" lang="en">

<?php include 'public_html/snippets/head.php'; ?>

<!--  Begin content -->

<body class="container">


	<?php include 'public_html/snippets/modal.php'; ?>

	<!-- include header -->
	<?php include 'public_html/snippets/header.php'; ?>


	<section class="row">

		<?php

			switch($this->action){

				case 'get':
					include "patient/get.php";
					break;

				case 'create':
					include "patient/create.php";
					break;

				default:
					echo "Default statement reached in views/patient.php";
					break;

			} 
		?>
		
	</section>


	<!-- include footer -->
	<?php include 'public_html/snippets/footer.php'; ?>
							

</body>


</html>