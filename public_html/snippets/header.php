<?php 
	

?>

<header class="row">
	
	<a class="pull-right" id="dashboard-link" href="http://localhost/therapyBusiness/">dashboard</a>

	<div class="dropdown">
	  
	  <button id="choosePatients" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	    Choose Patients
	    <span class="caret"></span>
	  </button>

	  <ul class="dropdown-menu" aria-labelledby="choosePatients">
	    <li data-target='all'><a>All Patients</a></li>
	    <li data-target='active'><a>Active Patients</a></li>
	    <li data-target='inactive'><a>Inactive Patients</a></li>
	  </ul>

	</div>

	<input id='easyAuto' data-easy-auto="true" onClick="this.select()">
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

 		$('.dropdown-toggle').dropdown();

		var options = {};

		$.ajax({

			url : "patients/get/active",

			data: {

				template_name : "_info-only",
				remote				: 'true'

			},

			dataType : 'json',

			complete : function(jqXHR, status){
				
				//console.log(jqXHR.responseJSON);
				$('.dropdown li[data-target="active"').addClass('active');

				options = {
					
					data 			: jqXHR.responseJSON,
					
					getValue	: "patient_name",

					placeholder : "Choose Patient",

					list 			: {

						onSelectItemEvent: function(){

							var value = $("#easyAuto").getSelectedItemData().patient_id;

							//console.log("value: " + value);

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
									//console.log("tab key pressed");
									e.preventDefault();
								}
							
							});

						},

						onHideListEvent: function(){

							g.easyAutoOpen = false;

							$('#easyAuto').on('keydown.custom', function(e){

								if(e.keyCode == 13){
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


		////////////////////////////////////////////
		//Add event handler for all_patient dropdown
		////////////////////////////////////////////

		$('.dropdown li').click(function(){
			
			if(!$(this).hasClass('active')){
				
				var action = $(this).attr('data-target');

				$.ajax({

					url : "patients/get/" + action,

					data: {

						template_name : "_info-only",
						remote				: 'true'

					},

					dataType : 'json',

					complete : function(jqXHR, status){
						
						options.data = jqXHR.responseJSON;
						g.easyAutoData = jqXHR.responseJSON;

						$('input[data-easy-auto=true]').each(function(){

							$(this).easyAutocomplete(options).parent('div').css('float', 'left');	

						});
						

						$('.dropdown li.active').removeClass('active');
						$('.dropdown li[data-target='+action).addClass('active');
						
					}
				
				});//Ajax

			}

		}); //click


	});

	

</script>