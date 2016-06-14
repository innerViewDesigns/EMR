<?php $services = $this->model; ?>

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
					include "services/_get.php";
					break;

				case 'create':
					include "services/_create.php";
					break;

				default:
					echo "Default statement reached in views/services.php";
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