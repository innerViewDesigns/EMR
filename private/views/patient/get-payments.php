<?php

	if(!isset($patient)){
		$patient = $this->model;
	}

	//If you are displaying this after an update then collect the service IDs...

	if( isset($this->lastUpdatedIds) ){
		if( array_key_exists('service_id', $this->lastUpdatedIds) ){
			$lastUpdatedIds = [];
			foreach($this->lastUpdatedIds as $key => $value){
				if($key === 'service_id'){
					array_push( $lastUpdatedIds, $value);
				}
			}
		}
	}

	$insurance_claims = new insurances();
	$insurance_claims->setAllForPatient(array('patient_id'=>$patient->patient_id));
	$claims = $insurance_claims->getClaims();

	$patient->setServices();
	$services = $patient->getServices();

	
	$other_payments = new otherPayments(array('patient_id'=>$patient->patient_id));
	$payments = $other_payments->getPayments();

	if(is_array($payments)){
		foreach( $payments as &$p ){

			$p['datetime'] = strtotime( $p['date_recieved'] );
			$p['dos'] = new DateTime( $p['date_recieved'] );

		}
	}else{
		$payments = [];
	}


	///////////////////////////////////
	//pair each claim with it's own DOS
  ///////////////////////////////////

	foreach($claims as &$c){

		//////////////////////////////////////////
		//deal with null cases which come in blank
		//////////////////////////////////////////

		if( $c['allowable_insurance_amount'] == "" ){

			$c['allowable_insurance_amount'] = "- ? -";

		}

		if( $c['expected_copay_amount'] == ""){

			$c['expected_copay_amount'] = "- ? -";

		}

		
		///////////////////////////////////////////////
		//Then loop through $services and match them up
		///////////////////////////////////////////////

		foreach($services as &$s){

			
			if($c['service_id_insurance_claim'] == $s['id_services']){

				//give this insurance claim a date and make it a date object

				$c['dos'] = new DateTime( $s['dos'] );

				//Then give it something to compare with other claims by creating
				//a unix timestamp

				$c['datetime'] = strtotime( $s['dos'] );

				//Then create another array indexed arbitrarly by service id. This will
				//be used for array multisort below

				$date[$s['id_services']] = strtotime( $s['dos'] );

				//Then shorten your loop and increase efficency

				unset($s);

				//Then break this loop and search for the next match.
				break;
			
			}
		}	
	}

	$sessionCount = count($claims);
	$claims =	array_merge($claims, $payments);


	///////////////////
	//Then sort by date
  ///////////////////

  usort($claims, function($a, $b) {

	  $ab = $a['datetime'];
	  $bd = $b['datetime'];

	  if ($ab == $bd) {
	    return 0;
	  }

	  return $ab < $bd ? 1 : -1;

	});


?>

<article class="col-md-12 drop6">
	<div style="width: 100%; margin-bottom: 25px;">
		<div class="pull-left">
			<p style="margin-bottom: 35px;">Number of sessions: <?= $sessionCount; ?></p>
		</div>

		<div class="pull-right">
			<p style="margin-bottom: 35px;">Balance due:  <span class="balance"><?= $patient->getbalance() ?></span></p>
		</div>
	</div>
	

		<?php

			//echo "<br>".print_r($claims, true);

			include(dirname(__DIR__)."/_flash.php");


			echo "<h3 id='payments' style='padding-top: 25px;'>Payments</h3>";

			echo "<table class='col-md-12 table'>
							<tr>
								<th class='text-center'>Date</th>
								<th class='text-center'>Other Payment</th>
								<th class='text-center'>Allowable</th>
								<th class='text-center'>Expected Copay</th>
								<th class='text-center'>Recieved Insurance</th>
								<th class='text-center'>Recieved Copay</th>
							</tr>";

			if(is_array($claims)){
				foreach( $claims as &$value ){
					if(!array_key_exists('service_id_insurance_claim', $value) ){
						$value['service_id_insurance_claim'] = 'undefined';
					}
					echo "<tr data-service_id = ".$value['service_id_insurance_claim'].">
									<td class='text-center' data-edit='false'>".$value['dos']->format("Y/m/d")."</td>";

					if( array_key_exists('id_other_payments', $value) ){

						echo	"<td class='text-center' ".

										'data-toggle="popover" data-trigger="click" data-html="true" data-title="Payment Info" data-content="<table class=table>'.
												'<tr><td class=text-center>Type:</td><td>'.$value['type'].'</td></tr>'.
												'<tr><td>Associated Data:</td><td>'.$value['associated_data'].'</td></tr></table>"'

									.">".$value['amount']."</td>"
									."<td class='text-center' data-edit='false' style='background-color: #ADADAD; color: white;'>--</td>"
									."<td class='text-center' data-edit='false' style='background-color: #ADADAD; color: white;'>--</td>"
									."<td class='text-center' data-edit='false' style='background-color: #ADADAD; color: white;'>--</td>"
									."<td class='text-center' data-edit='false' style='background-color: #ADADAD; color: white;'>--</td>".

								"</tr>";

					}else{

						echo	"<td class='text-center' data-edit='false' style='background-color: #ADADAD; color: white;'>--</td>"
									."<td class='text-center' data-column-name='allowable_insurance_amount' data-edit='true'>".$value['allowable_insurance_amount']."</td>"
									."<td class='text-center' data-column-name='expected_copay_amount' data-edit='true'>".$value['expected_copay_amount']."</td>"
									."<td class='text-center' data-column-name='recieved_insurance_amount' data-edit='true'>".$value['recieved_insurance_amount']."</td>"
									."<td class='text-center' data-column-name='recieved_copay_amount' data-edit='true'>".$value['recieved_copay_amount']."</td>".

								"</tr>";
					}

				}

				echo "</table>";
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

	$('table tr td[data-edit="true"]').each(function(){

		$(this).on('mouseenter.hoverBorder',function(){
			$(this).css('border', '1px solid black');
		}).on('mouseleave.hoverBorder', function(){
			$(this).css('border', '');
		});


	});

	////////////////////
	//Set event handlers
	////////////////////


	//First collect all of the elidgible table cells

	$('table tr td[data-edit="true"]').each(function(){
			
		///////////////////////////////////////////////////////////////////////
		//This function resets the table cells if they were clicked by mistake.
		//The timeout function makes sure that the original event handlers are 
		//reattached.
		///////////////////////////////////////////////////////////////////////


		function resetTd($el, value){

			//$el is the div.reverseInput element that was just clicked

			$el = $el.parent('td').html('').text(value);
			$el.parent('tr').attr('data-update', 'false');


			//if you don't set a timeout then the click event doesn't get reattached.
			window.setTimeout(addClickHandler, 500, $el);
			window.setTimeout(function(){
				
				if( $('tr[data-update="true"]').size() == 0 ){
					$('#update-button').remove();
				}
				
			}, 500);

		}



		///////////////////////////////////////////////////////////////
		//This is the original function that attaches the event handler
		//Styles for the reset button are here.
		///////////////////////////////////////////////////////////////

		function addClickHandler(el){

			el.css('cursor', 'pointer').on('click.addInput', function(){

				var $td    = $(this);
				var col    = $td.attr('data-column-name');
				var value  = $td.text();

				$td.html("<input type='text' value='"+ value +"' class='form-control text-center'></input><div class='reverseInput text-center'>x</div>").addClass('relative');
				
				if ( $('#update-button').size() == 0 ) {
				
					$('h3#payments').append('<button id="update-button" class="btn btn-default pull-left">Update</button>');
					window.setTimeout(function(){$('#update-button').click(submitData);}, 500);
					
				}
				

				$td.off(".addInput");

				$('td div.reverseInput')
					.css('color', 'white')
					.css('font-size', '.7em')
					.css('width', '15px')
					.css('height', '15px')
					.css('border-radius', '50%')
					.css('background-color', 'black')
					.css('cursor', 'pointer')
					.css('position', 'absolute')
					.css('right', '5px')
					.css('top', '5px')
					.css('line-height', '12px')
					.click(function(){

						resetTd($(this), value);

					});

					///////////////////////////////////////////////////////////////
					//After you click, mark this row as containing update data
					///////////////////////////////////////////////////////////////

					el.parent('tr').attr('data-update', "true"); 

			});



		}


		/////////////////////////////
		// Need to start somewhere...
		/////////////////////////////

		addClickHandler($(this));


	});

	function submitData(){

		var $rows = $('table tr[data-update="true"]');
		var data  = [];

		$rows.each(function(i){

			var     $row = $(this),
			  service_id = $(this).attr('data-service_id'),
				 	 $inputs = $row.find('input');

			data[i] = { 'service_id' : service_id };

			$inputs.each(function(ii){

				var $input = $(this),
							col  = $input.parents('td').attr('data-column-name'),
				    value  = $input.val();

				data[i][col] = value;

			});


		});

		$.ajax({

				url : g.basePath + "insurance/update",

				data: {

					'remote'				: 'true',
					'template_name' : 'patient/get-payments/' + g.patient_id,
					'data' 					: data

				},

				beforeSend : function(){

				},

				complete : function(jqXHR, status){

					$('div#payments').html(jqXHR.responseText);


				} 

		}); //ajax 
		//Ajax

	}

	$(function () {
  	$('[data-toggle="popover"]').popover();
	});

});

</script>