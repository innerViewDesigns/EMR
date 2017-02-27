	<form action="http://localhost/~Apple/therapyBusiness/note/create" method="post" id="add-otherNotes-form">
		<div class="row">

			<div class="form-group col-md-4">
	   		<label for="Patient">Patient</label>
	    	<input type="text" data-easy-auto="true" name="patient" class="form-control" id="patientInput">
	    	<input type="hidden" name='patient_id[]'></input>
	  	</div>

		  <div class="form-group col-md-4">
		    <label for="date">Date</label>
		    <input type="text" class="form-control" name="date" id="dateInput" placeholder="date">
		  </div>

		  <div class="form-group col-md-4">
		    <label for="type">Type</label>
		    <input type="text" class="form-control" name="type" id="typeInput">
		  </div>

		</div>

		<div class="row">

			<div class="form-group col-md-12">
		    <label for="type">Note</label>
		    <textarea type="text" id="noteInput" style="width: 100%;" rows="8"></textarea>
		  </div>

		</div>
		<button type="submit" class="btn btn-default" id="submit">Submit</button>
	</form>

<script>

$("#myModal input[data-easy-auto='true']").each(function(){

					var $el    		= $(this),
							$hiddenEl = $("input[name='patient_id[]']");


					var options = {

						data 			: g.easyAutoData,
						
						getValue	: "patient_name",

						placeholder : "Patient",

						list 			: {

							onSelectItemEvent: function(){

								var value = $el.getSelectedItemData().patient_id;

								$hiddenEl.val(value).trigger("change");

							},

							onShowListEvent: function(){

								$(document.activeElement).on('keydown.custom', function(e){

									
									if(e.keyCode == 9){

										var ev = $.Event('keydown');
										ev.keyCode = 13; // enter

										$el.trigger(ev);
										e.preventDefault();
									}
								
								});

							},

							onHideListEvent: function(){

								$el.off('keydown.custom');


							},

							match: {

								enabled: true

							}

						} //list

					} //options

					$(this).easyAutocomplete(options).parent('div').css('width', '100%');

				}); //loop through easyAutos


	$('input[name="date"]').datetimepicker({

					validateOnBlur:false,
					allowTimes:g.timeOptions()


		});

	$('button[type="submit"]').click(function(e){

		e.preventDefault();

		var data = 	{ "data" : [{
					patient_id_notes 	: $('input[name="patient_id[]"]').val(),
					associated_date		: $('input[name="date"]').val(),
					type 							: $('input[name="type"]').val(),
					note 							: $('textarea').val()

				}],
				"template_name": ["note/_info_only"]
				
			};

		
		$.ajax({

			url   		 : g.basePath + "note/create",

			data  		 : data,

			beforesend : function(){

			},

			complete	 : function(jqXHR, status){

				$('#myModal div.modal-body').html(jqXHR.responseText + "<br>"+status);

			},

			error: function (xhr, ajaxOptions, thrownError) {alert("ERROR:" + xhr.responseText+" - "+thrownError);}


		});


	});

</script>