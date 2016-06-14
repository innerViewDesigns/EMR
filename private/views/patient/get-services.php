<?php

	if(!isset($patient)){
		$patient = $this->model;
	}

	$patient->setServices();
	$pServices = $patient->getServices('y-m-d h:m');

?>


<article class="col-md-12 drop6">
	
	<div class="row" style="margin-bottom: 30px;">
		
		<div class="col-md-3" style="margin-top: 25px;">
			<p>Number of sessions: <?= count($pServices); ?></p>
		</div>	
		
		<div class="form-group col-md-3">
	    <label for="start_date">Start</label>
	    <input type="text" class="form-control" id="" name='start_date' value='' onClick="this.select();"></input>
	  </div>
	  
	  <div class="form-group col-md-3">
	    <label for="end_date">End</label>
	    <input type="text" class="form-control" id="" name='end_date' value='' onClick="this.select();"></input>
	  </div>
	  
	  <button name="export" class="btn btn-default col-md-2" style="margin-top: 28px;" >Export</button>  
	
	</div>

	

		<?php

			//echo "<br>".print_r($claims, true);

			include(dirname(__DIR__)."/_flash.php");


			echo "<table class='col-md-12 table'>
							<tr>
								<th id='print' class='text-center' style='margin-left: -20px;'>Print</th>
								<th class='text-center'>Date</th>
								<th class='text-center'>type</th>
								<th class='text-center'>charged</th>
								<th class='text-center'>insurance_used</th>
								<th class='text-center'>cpt</th>
								<th class='text-center'>dx1</th>
								<th class='text-center'>dx2</th>
								<th class='text-center'>dx3</th>
							</tr>";

			if(is_array($pServices)){

				foreach( $pServices as $key => $value ){

					echo "<tr><td><div class='checkbox-custom' style='margin-left: 15px;' data-service-id =". $value['id_services'] ."></div></td>
									<td class='text-center'>".$value['dos']."</td>";

					echo	"<td class='text-center'>".$value['type']."</td>"
								."<td class='text-center'>".$value['charged']."</td>"
								."<td class='text-center'>".$value['insurance_used']."</td>"
								."<td class='text-center'>".$value['cpt_code']."</td>"
								."<td class='text-center'>".$value['dx1']."</td>"
								."<td class='text-center'>".$value['dx2']."</td>"
								."<td class='text-center'>".$value['dx3']."</td>".

							"</tr>";
					}

				
			}else{
				echo $claims;
			}

		?>

</article>

<script>

$(document).ready(function(){
	

	////////////
	//Set styles
	////////////

	$('table tr').each(function(i){

		if( i == 0 ){return true;}

		$(this).hover(function(){
			$(this).css('background-color', '#eee');
		}, function(){
			$(this).css('background-color', '');
		});

	});

	$('table tr td').slice(1, 6).each(function(){

		$(this).hover(function(){
			$(this).css('border', '1px solid black');
		}, function(){
			$(this).css('border', '');
		});


	});

	////////////////////
	//Set event handlers
	////////////////////

	$('table tr td').each(function(){



	});


	///////////////////////
	//Print notes to pdf...
	///////////////////////

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

	$('div.checkbox-custom').each(function(){

		$(this).click(function(){

			$(this).toggleClass('checked');

		});
	
	});

	$('button[name="export"]').click(function(){

		console.log('button clicked');
		var $start = $('input[name="start_date"]');
		var   $end = $('input[name="end_date"]');


		if( $('div.checked').size == 0 ){

			//check to make sure a date range was picked

			if( $start.val().length == 0 || $end.val().length == 0){

				window.alert("No data given.");
				console.log("No data given.");

			}

		}


	});

	$('#print').css('cursor', 'pointer').click(function(){

		$('div.checkbox-custom').each(function(){

			if ( !$(this).hasClass('checked') ){
			
				$(this).addClass('checked');
			
			}else {

				$(this).removeClass('checked');
			
			}

		});

	});




});

</script>