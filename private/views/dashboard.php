<?php 
	
	$dashboard = $this->model; 

	fb("dashboard view" . isset($dashboard->startDate) . ", " . $dashboard->endDate);

	$newList = $dashboard->getLastWeeksServices();


	$include_cash = array_key_exists('include_cash', $newList) ? "true" : "false";
	$include_insurance = array_key_exists('include_insurance', $newList) ? "true" : "false";

	if($include_cash == 'true'){
		unset($newList['include_cash']);
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

		<article class="drop6 col-md-6">

			<h3 id="hide-delete-buttons">Last weeks services</h3>

			<form action="dashboard/get" method="post">
				<div class="row">
				  <div class="form-group col-md-4 col-md-offset-1">
				    <label for="start_date">Start</label>
				    <input type="text" class="form-control" id="" placeholder="Start Date" name='start_date' value='<?php if(isset($dashboard->startDate)){echo $dashboard->startDate;} ?>' onClick="this.select();"></input>
				  </div>
				  <div class="form-group col-md-4">
				    <label for="end_date">End</label>
				    <input type="text" class="form-control" id="" placeholder="End Date" name='end_date' value='<?php if(isset($dashboard->endDate)){echo $dashboard->endDate;} ?>' onClick="this.select();"></input>
				  </div>
				  <button type="submit" class="btn btn-default col-md-2" style="margin-top: 28px;" >Go</button>  
				 </div>

				 <div class="row">
				  <div class="form-group col-md-8 col-md-offset-1">
						<input type="checkbox" class="checkbox-custom" name='include_insurance' data-checked='<?=$include_insurance?>'>
						<div class="checkbox-custom pull-left" data-target = 'include_insurance' ></div>
						<div class='pull-left' style="margin: 5px 0 0 10px;">insurance</div>

					 	<input type="checkbox" class="checkbox-custom" name='include_cash' data-checked='<?=$include_cash?>'>
						<div class="checkbox-custom pull-left" style="margin-left: 10px;" data-target = 'include_cash' ></div>
						<div class='pull-left' style="margin: 5px 0 0 10px;">Cash</div>

				  </div>
				</div>
			  
			</form>
			
			<?php 

			
				if( !array_key_exists('no results', $newList) ){

					$list_item = "";
					echo "<ul class='col-md-offset-1'>";
					
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

		<article class='col-md-6' id='data-backup-article'>
			<a href="dashboard/get/database-backup"><button id='data-backup-button' class='center-block'>Make Database Back-up</button></a>
		</article>

		<article class='col-md-6'>
			<a href="dashboard/get/add-claims-to-file"><button id='claims-to-file' class='center-block'>Add Claims to File</button></a>
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

		});
	
		var $s = $('span.addNote');

		//helper function

		$('h3#hide-delete-buttons').css('cursor', 'pointer').click(function(){

			$s.each(function(){

				$(this).toggleClass("hidden");

			});

		});

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

		/*----------------------------------------------------------*/
		/*
			
			Start to handel the make database backup script

		*/

		$('#data-backup-button').click(function(e){

			e.preventDefault();

			$.ajax({

				url: 'dashboard/get',

				dataType: 'json',

				method: "POST",

				data: {

							remote        : 'true',
							template_name : '_info-only',
							user_param    : 'database-backup'

						},

				complete : function(jqXHR, status){

					console.log(jqXHR);
				
				},

				success : function(data, jqXHR, status){

					console.log(data);
					//console.log(jqXHR);
					
					if( typeof jqXHR.responseJSON !== 'undefined'){

						$('article#data-backup-article').append('<p>' + jqXHR.responseJSON + '</p>');	

					}
			
				},

				error : function(jqXHR, textStatus, errorThrown){

				}

			});

		});

		$('#claims-to-file').click(function(e){

			e.preventDefault();

			$.ajax({

				url: 'dashboard/get',

				method: "POST",

				data: {

							remote        : 'true',
							template_name : '_info-only',
							user_param    : 'add-claims-to-file'

						},

				complete : function(jqXHR, status){

					console.log(jqXHR);
					console.log(status);

				}


			});

		});



	})();

</script>



</html>