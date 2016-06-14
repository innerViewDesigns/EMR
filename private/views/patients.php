<?php 
		
	$patients_i = $this->model; 

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

		<article class="drop6">
			<?php 
				include('_flash.php');
			?>
			
				<?php

						if(!is_null($this->lastInsertIds)){

							$patients_i->getSome($this->lastInsertIds);
							$patients_i->setNamesAndIds();
							
							echo "<table class='table table-striped'><tr><th>id</th><th>Name</th></tr>";

							foreach($patients_i->getNamesAndIds() as $key => $value){

								echo "<tr><td>" . $key . "</td><td>" . $value . "</td></tr>";

							}

							echo "</table>";
						}
					
				?>
			</table>
		</article>
		
	</section>


	<!-- include footer -->
	<?php include 'public_html/snippets/footer.php'; ?>
							

</body>


</html>