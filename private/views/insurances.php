<?php $insurances = $this->model; ?>

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
					include "insurances/_get.php";
					break;

				case 'update':
					include "insurances/_update.php";
					break;

				default:
					echo "Default statement reached in views/insurances.php";
					break;

			} 
		?>

	</section>


	<!-- include footer -->
	<?php include 'public_html/snippets/footer.php'; ?>
							

</body>

<script>

</script>


</html>