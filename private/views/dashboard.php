<?php 
	
	$dashboard = $this->model; 
	//echo print_r($dashboard->getFlash(), true);

	$newList = $dashboard->getLastWeeksServices();


	$include_cashonly = array_key_exists('include_cashonly', $newList) ? "true" : "false";
	$include_insurance = array_key_exists('include_insurance', $newList) ? "true" : "false";

	if($include_cashonly == 'true'){
		unset($newList['include_cashonly']);
	}

	if($include_insurance == 'true'){
		unset($newList['include_insurance']);
	}


	//make date object out of dos colume in order to 
	//compare and group by day

	if(!array_key_exists('no results', $newList)){
		foreach($newList as &$value){

			$value['dos'] = new DateTime($value['dos']);

		}
		unset($value);
	}
	

?>
<!DOCTYPE html>
<html lang="en">

<?php include 'public_html/snippets/head.php'; ?>



<!--  Begin content -->

<body class="container">


	<?php include 'public_html/snippets/modal.php'; ?>

	<!-- include header -->
	<?php include 'public_html/snippets/header.php'; ?>


	<section class="row">
	<!--	<pre> <?php print_r($newList) ?> </pre> -->
		<article class="drop6 col-md-12">

			<button class="upper-right btn" id="collapse-last-week">
				<span class="glyphicon glyphicon-minus"></span>
			</button>
			<h3 id="hide-delete-buttons">Last weeks services</h3>

			<form action="dashboard/get" method="post">
				<div class="row">
				  <div class="form-group col-md-4 col-md-offset-1">
				    <label for="start_date">Start</label>
				    <input type="text" class="form-control" id="" name='start_date' value='<?= $dashboard->startDate ?>' onClick="this.select();"></input>
				  </div>
				  <div class="form-group col-md-4">
				    <label for="end_date">End</label>
				    <input type="text" class="form-control" id="" name='end_date' value='<?= $dashboard->endDate ?>' onClick="this.select();"></input>
				  </div>
				  <button type="submit" class="btn btn-default col-md-2" style="margin-top: 28px;" >Go</button>  
				 </div>

				 <div class="row">
				  <div class="form-group col-md-8 col-md-offset-1">
						<input type="checkbox" class="checkbox-custom" name='include_insurance' data-checked='<?=$include_insurance?>'>
						<div class="checkbox-custom pull-left" data-target = 'include_insurance' ></div>
						<div class='pull-left' style="margin: 5px 0 0 10px;">insurance</div>

					 	<input type="checkbox" class="checkbox-custom" name='include_cashonly' data-checked='<?=$include_cashonly?>'>
						<div class="checkbox-custom pull-left" style="margin-left: 10px;" data-target = 'include_cashonly' ></div>
						<div class='pull-left' style="margin: 5px 0 0 10px;">cashonly</div>

				  </div>
				</div>
			  
			</form>
			
			<?php 

			
				if( !array_key_exists('no results', $newList) ){

					$list_item = "";
					echo "<ul class='col-md-8 col-md-offset-1'>";
					
					for($i=0; $i<count($newList); $i++){

						$list_item = $newList[$i]['name'] . " - " . $newList[$i]['dos']->format('Y-m-d') . " - " . $newList[$i]['cpt'] . " - " . $newList[$i]['dx1'];
						
						
						if ( isset( $newList[$i]['dx2'] )){

							$list_item = $list_item . ", " . $newList[$i]['dx2'];

						}
						if( isset( $newList[$i]['dx3'] ) ){

							$list_item = $list_item . ", " . $newList[$i]['dx3'];

						}
						
						if( isset($newList[$i + 1]) ) {

							$interval = $newList[$i]['dos']->diff($newList[$i+1]['dos']);

							if( ($interval->d > 0) ){

								$list_item = "<li style='margin-bottom: 25px;'>" . $list_item . " /n";

							}else{

								$list_item = "<li>" . $list_item;

							}

						}else{

							$list_item = "<li>" . $list_item;

						}

						if( $newList[$i]['completed'] == '0' ){

							$list_item = $list_item . "<span class='addNote' style='font-size: .8em; color: blue; margin-left: 10px;'><a href='patient/get/".$newList[$i]['patient_id']."'>(add note)</a></span></li>";

						}else{

							$list_item = $list_item . "</li>";

						}

						echo $list_item;		
						$list_item = "";

					} //foreach

					echo "</ul>";
				
				}else{

					echo "No results.";

				}


			?>
		</article>
	</section>


	<!-- include footer -->
	<?php include 'public_html/snippets/footer.php'; ?>
							

</body>

<?php include 'public_html/scripts/customScript_checkbox.js'; ?>
<script>
	
	(function(){

		var $listItems = $('section li');

		
		$listItems.each(function(){

			$(this).hover(
				function(){
					$(this).css('background-color', '#eee');
				}, 
				function(){
					$(this).css('background-color', '');
				}
			);
			$('<div class="delete-list-item">-</div>').appendTo(this);

		});
	

		var $d = $(".delete-list-item");
		var $s = $('span.addNote');
		
		$d.each(function(){
			$(this)
				.css('float', 'right')
				.css('cursor', 'pointer')
				.css('margin', '0 0px 0 55px')
				.css('width', '25px')
				.css('text-align', 'center')
				.click(function(e){
					$(this).parent('li').remove();
				});

		});

		//helper function

		$('h3#hide-delete-buttons').css('cursor', 'pointer').click(function(){

			$d.each(function(){

				$(this).toggleClass("hidden");
				
			});

			$s.each(function(){

				$(this).toggleClass("hidden");

			});

		});

		$('button#collapse-last-week').click(function(){

			$('ul').remove();

		});

		/*
		///////////////////////////////////////////
		//Make sure the checkbox start synched up...
		///////////////////////////////////////////

		$('input.checkbox-custom').each(function(){

			if($(this).attr('data-checked') == "true"){

				var i = $(this).attr('name');
				$(this).prop('checked', true);
				$(this).siblings('div.checkbox-custom[data-target='+i+']').addClass('checked');

			}

		});

		/////////////////////////
		//Then keep them synched
		////////////////////////

		$('div.checkbox-custom').each(function(){

			$(this).click(function(){

				var i = $(this).attr('data-target');
				var $checkbox = $(this).siblings('input[name='+i+']');

				if( $checkbox.prop('checked') ){

					$checkbox.prop('checked', false);
					$(this).toggleClass('checked');

				}else{

					$checkbox.prop('checked', true);
					$(this).toggleClass('checked');			

				}

			});

		});
		
		*/

		$('input[name="start_date"]').datetimepicker({

					validateOnBlur:false,
					timepicker:false,
					format:"Y-m-d"


		});

		$('input[name="end_date"]').datetimepicker({

					validateOnBlur:false,
					timepicker:false,
					format:"Y-m-d"

		});





	})();

</script>



</html>