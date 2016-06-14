<?php

	$demo = $patient->getPersonalInfo();

?>

<h1 id="patient-name" data-patient-id='<?=$patient->patient_id?>'><?= $demo['last_name'] ?><span style='font-size: .3em; color: blue; margin-left: 10px;'><?= $patient->patient_id ?></span></h1>

<div>

  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist" id="patientTabs">
    <li role="presentation" class="active"><a data-target="#emr" role="tab" data-toggle="tab">EMR</a></li>
    <li role="presentation"><a data-target="#services" role="tab" data-toggle="tab">Services</a></li>
    <li role="presentation"><a data-target="#payments" role="tab" data-toggle="tab">Payments</a></li>
    <li role="presentation"><a data-target="#personal" role="tab" data-toggle="tab">Personal Info</a></li>
  </ul>

  <!-- Tab panes -->
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="emr"></div>
    <div role="tabpanel" class="tab-pane" id="services">services</div>
    <div role="tabpanel" class="tab-pane" id="payments">payments</div>
    <div role="tabpanel" class="tab-pane" id="personal">personal</div>
  </div>

</div>

<script>


//$('a[data-target="#emr"]').click(function(e){
$(document).ready(function(){

	//////////////////////////
	//Load tabs and patient_id
	//////////////////////////


	jQuery( function () {

		$('#patientTabs a').css('cursor', 'pointer').click(function (e) {
		  
		  var template_name = $(this).attr('data-target').replace('#', "");
		  
		  getTabData(template_name);
		  
		  $(this).tab('show');
			
			e.preventDefault();
		
		});

		g.patient_id = $('h1#patient-name').attr('data-patient-id');

	});



	////////////////////////////////////
	// Define the function to load tabs
	////////////////////////////////////

	function getTabData(templateName='emr'){


		$.ajax({
			
			'url'  : g.basePath + 'patient/get/' + g.patient_id,
			
			'data' : {
				'remote'  			: 'true',
				'template_name' : 'get-'+ templateName
			},

			'complete' : function(data, textStatus){

				$('div#' + templateName).html(data.responseText);
				//console.log(textStatus);
				//console.log(JSON.stringify(data));
			}
			
		}); //ajax 'patient/get/'

	} //getTabData

}); //documentReady

</script>