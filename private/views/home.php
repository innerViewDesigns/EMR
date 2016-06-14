<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
	
	<title>My Database Driven App</title>
	

	<meta name="description" content="Michael Lembaris practices psychodynamic psychotherapy in
																		downtown San Diego." />
	
	<?php include 'public_html/snippets/metaLinkScriptTags.php'; ?>


</head>

<!--  Begin content -->

<body class="container">


	<?php include 'public_html/snippets/modal.php'; ?>

	<!-- include header -->
	<?php include 'public_html/snippets/header.php'; ?>


	<section class="row">

		<?php 

			if(isset($_GET['page_type'])){

				switch ($_GET['page_type']) {
					case 'patient_names':
						include_once 'public_html/pages/patient_names.php';
						break;

					case 'analytics':
						include_once 'public_html/pages/analytics.php';
						break;
					
					default:
						break;
				}
			
			}else{

				include_once $this->getBaseFilePath() . '/private/views/patient_names.php';

			}
			

		?>


	</section>



	<!-- include footer -->
	<?php include 'public_html/snippets/footer.php'; ?>
							

</body>


</html>