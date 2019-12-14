<h1>Add Payments</h1>

<article class="drop6 col-md-12">

	<form action="otherPayments/create" class="col-md-12" method="post" id="add-otherPayments-form">

			<table id="payments" class="table row">
				<tr>
					<th class="text-center col-md-3">Patient</th> 
					<th class="text-center col-md-2">Date</th>
					<th class="text-center col-md-2">Amount</th>
					<th class="text-center col-md-2">type</th>
					<th class="text-center col-md-2">data</th>
				</tr>

				<tr data-clone="true" data-type="insert_data">
					<td >
						<input type="text" class="form-control" data-easy-auto="true" name='patient[]' onClick="this.select()"></input>
					  <input type="hidden" name='patient_id[]'></input>
					</td>
					
					<td>
						<input type="text" class="form-control" name='date_recieved[]'></input>
					</td>
					
					<td>
						<input type="text" class="relative form-control" name='amount[]'></input>
					</td>
					<td>
						<input type="text" class="relative form-control" name='type[]'></input>
					</td>
					<td>
						<input type="text" class="relative form-control" name='associated_data[]'></input>
					</td>
				</tr>
			</table>

		  <button type="submit" class="btn btn-default" id="submit">Submit</button>
		  <button class="btn btn-default" id="add-more-payments">Add Another</button>
	</form>

</article>

<script>

$(document).ready(function(){

	function attachRemoveEventHandler()
	{
		$('div.glyphicon-minus').css("cursor", "pointer").click(function(){

			$(this).parents('tr').remove();

		});
	}

	function attachDateHandler()
	{
		$('input[name="date_recieved[]"]').datetimepicker({

			validateOnBlur:false,
			timepicker:false,
			format:"Y-m-d"

		});
	}

	function waitForElement(){

		if( typeof g.easyAutoData !== 'undefined'){

				$('table#payments').find('tr').last().find("input[data-easy-auto='true']").each(function(){

					var $el    		= $(this),
							$row			= $el.parents('tr'),
							$hiddenEl = $row.find("input[name='patient_id[]']");

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

					$(this).easyAutocomplete(options).parent('div').css('margin', '0 auto').css('width', '100%');

				});


				////////////////////////////
				//attach ajax event handler
				////////////////////////////

				
		}else{

			window.setTimeout(function(){
	       waitForElement();
	    },250);

		}
	
	} //waitForElement

	waitForElement();
	attachDateHandler();


	//////////////////////////////////
	//Attach add-service event handler
	//////////////////////////////////


	$('#add-more-payments').click(function(e){

		var $row = $('table#payments tr:last');
		var clone = $row.clone();

		clone.find("input[data-easy-auto='true']").after("<input type='text' style='width: 100%' data-easy-auto='true' name='patient[]' onClick='this.select()'></input>").remove();
		clone.find("input:last").after("<div class='glyph glyphicon glyphicon-minus'></div>")
		clone.find("input").slice(2, 6).each(function(){

			$(this).val("");

		});	
		clone.appendTo('table');

		$row  = null;
		clone = null;

		
		e.preventDefault();
		waitForElement();
		attachRemoveEventHandler();
		attachDateHandler();

	});

	/*

	$("button#submit").click(function(e){

		e.preventDefault();

		//go row by row and pick up the data.
		//select data by data-type attr (where = update-data);
		var $rows = $('tr[data-type="insert_data"]');
		var tempData = {};
		var data = [];


		$rows.each(function(){

			var $tr = $(this);
			var patient_id = $tr.find("input[name='patient_id[]']").val();

			
			tempData = {
				'patient_id'    : patient_id,
				date_recieved   : $tr.find('input[name="date_recieved[]"]').val(),
				amount          : $tr.find('input[name="amount[]"]').val(),
				type 					  : $tr.find('input[name="type[]"]').val(),
				associated_data : $tr.find('input[name="associated_data[]"]').val()

			} 

			data.push(tempData);

		});


		$.ajax({

				url : g.basePath + "otherPayments/create",

				method: 'POST',

				data: {

					remote          : 'true',
					template_name   : '_get',
					data 						: data

				},

				beforeSend : function(){

				},

				complete : function(jqXHR, status){

					$('section').html(jqXHR.responseText);


				} 

		}); //ajax 


	}); //submit button click

	*/


});



	

</script>