<?php 

	$patients = new patients;
	$patients->getAll();
	$patients->setNamesAndIds();
	$list = $patients->getNamesAndIds();

?>
<a class="pull-left" href="http://localhost/~Apple/therapyBusiness/">dashboard</a>
<header class="row">
	

	<input id='easyAuto' onClick="this.select()">
	<input id='easyAutoHidden' type="hidden">

	<button id="get-patient" class="btn btn-default">submit</button>
	<a href="services/create"><button class="btn btn-default">Add Services</button></a>
	<a href="insurances/update"><button class="btn btn-default">Ins Payments</button></a>
	<a href="otherPayments/create"><button class="btn btn-default">Other Payments</button></a>
	

	<div class="pull-right">
		<a href="patient/create" ><button id="add_patient" class="btn btn-default">Add Patient</button></a>
	</div>
</header>

<script src="public_html/scripts/jquery.easy-autocomplete.min.js"></script>
<script>

 $(document).ready(function(){

		var options = {};

		$.ajax({

			url : g.basePath + "patients/get",

			data: {

				template_name : "_info-only",
				remote				: 'true'

			},

			dataType : 'json',

			complete : function(jqXHR, status){
				
				options = {
					
					data 			: jqXHR.responseJSON,
					
					getValue	: "patient_name",

					placeholder : "Choose Patient",

					list 			: {

						onSelectItemEvent: function(){

							var value = $("#easyAuto").getSelectedItemData().patient_id;

							console.log("value: " + value);

							$("#easyAutoHidden").val(value).trigger("change");

						},

						onKeyEnterEvent: function(){

							

						},

						onShowListEvent: function(){

							$('#easyAuto').off('keydown.custom');

							$(document.activeElement).on('keydown', function(e){

								
								if(e.keyCode == 9){

									var ev = $.Event('keydown');
									ev.keyCode = 13; // enter

									$('#easyAuto').trigger(ev);
									console.log("tab key pressed");
									e.preventDefault();
								}
							
							});

						},

						onHideListEvent: function(){

							g.easyAutoOpen = false;

							$('#easyAuto').on('keydown.custom', function(e){

								if(e.keyCode == 13){
									console.log("about the redirect");
									window.location = g.basePath + "patient/get/" + $('#easyAutoHidden').val();
								}

							});
							
						},

						match: {

							enabled: true

						}
					}
				}

				g.easyAutoData = options.data;
				$("#easyAuto").easyAutocomplete(options).parent('div').css('float', 'left');
			
			}

		});

		$('button#get-patient').click(function(){

			var id = $('input[type="hidden"]').val();
			window.location = g.basePath + "patient/get/" + id;
		});

	});

	

</script>