<h1>Add Insurance Payments and Copays</h1>

<article class="drop6 col-md-12">

	<form action="" method="post" id="add-services-form">
		<table id="payments" class="table col-md-12">
			<tr>
				<th class="text-center col-md-3">Patient</th> 
				<th class="text-center col-md-2">Date</th>
				<th class="text-center col-md-1">Allowable</th>
				<th class="text-center col-md-2">Expected Copay</th>
				<th class="text-center col-md-2">Recieved Insurance</th>
				<th class="text-center col-md-2">Recieved Copay</th>
			</tr>

			<tr data-clone="true">
				<td >
					<input type="text" data-easy-auto="true" name='patient[]' onClick="this.select()"></input>
				  <input type="hidden" name='patient_id'></input>
				  <input type="hidden" name='service_id'></input>
				</td>
				<td class="relative">
					<input type="text" class="form-control" name='dos'></input>
					<div class="glyph glyphicon glyphicon-chevron-right"></div>
				</td>
				<td data-column-name = "allowable_insurance_amount">
				</td>
				<td data-column-name = "expected_copay_amount">
				</td>
				<td data-column-name = "recieved_insurance_amount">	
				</td>
				<td class="relative" data-column-name = "recieved_copay_amount">
					<div class="glyph glyphicon glyphicon-minus"></div>
				</td>
			</tr>

		</table>
		  <button type="submit" class="btn btn-default" id="submit">Submit</button>
		  <button class="btn btn-default" id="add-more-services">Add Another</button>
		  <button class="btn btn-default" id="remove-last-row">Remove Last Row</button>
	</form>

</article>

<script>

$(document).ready(function(){

	function waitForElement(){

		if( typeof g.easyAutoData !== 'undefined'){

				$('table#payments').find('tr').last().find("input[data-easy-auto='true']").each(function(){

					var $el    		= $(this),
							$row			= $el.parents('tr'),
							$hiddenEl = $row.find("input[name='patient_id']");

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

					$(this).easyAutocomplete(options).parent('div').css('float', 'left');

				});
				
				$('input[name="dos"]').datetimepicker({

					validateOnBlur:false,
					timepicker:false,
					format:"Y-m-d"


				});

				////////////////////////////
				//attach ajax event handler
				////////////////////////////

				$('div.glyphicon-chevron-right').on('click', function(){

					var $row 				 = $(this).parents('tr');
					var patient_id   = $row.find("input[name='patient_id']").val();
					var dos 				 = $row.find('input[name="dos"]').val();


					//console.log(patient_id + ", " + dos);

					$.ajax({

						url : g.basePath + "insurance/get",

						data: {

							template_name : "_info-only",
							remote				: 'true',
							patient_id    : patient_id,
							dos 					: dos

						},

						dataType : 'json',

						beforeSend : function(){
							$row.css('background-color', '');
						},

						complete : function(jqXHR, status){

							if( typeof jqXHR.responseJSON != 'undefined'){

								var s = jqXHR.responseJSON;

								if(s['expected_copay_amount'] === null){ s['expected_copay_amount'] = '0.00';}

								//console.log(s);

								
								$row.find('td').slice(2, 6).each(function(i){

												//place an input with the value pulled from database
												var $td  = $(this); 
												var $row = $td.parents('tr');
												var col  = $td.attr('data-column-name');

												//set input with old data
												if(s[col] != undefined){

													$td.html("<input class='form-control' name=" + col + " type='text' value=" + s[col] + ">"); 
													$row.attr('data-type', 'update-data');
												
													//in case you already turned the background red on this row, turn them back when
													//you finally get it right.
													$td.css('background-color', '').css('box-shadow', '').css('opacity', '');

													if( $td.index() == 5 )
													{

														$td.append('<div class="glyph glyphicon glyphicon-minus"></div>');
														$td.find('.glyphicon-minus').css('cursor', 'pointer').click(function(){

																$(this).parents('tr').remove();

														});

													}

													$row.attr("data-type", "update-data");

												}else{

													//Show me that I messed up.
													$td.css('background-color', '#d9534f')
															.css('box-shadow', 'inset 0px 0px 0px 6px #fff')
															.css('opacity', '.7');
													$row.attr('data-type', 'none');
												}

								}); 

								//set service id into hidden field
								$row.find('input[name="service_id"]').val(s['service_id_insurance_claim']); 

							}else{

								$row.css('background-color', '#d9534f');
								console.log("else. Here's the returned object: ");
								console.log(jqXHR.responseText);

							}

						} //complete

					}); //ajax

				}); //click chevron to trigger ajax

		}else{

			window.setTimeout(function(){
	       waitForElement();
	    },250);

		}
	
	} //waitForElement

	waitForElement();


	//////////////////////////////////
	//Attach add-service event handler
	//////////////////////////////////


	$('#add-more-services').click(function(e){

		e.preventDefault();
		
		var $row = $('table#payments tr').last();
		var clone = $row.clone();
		clone.find("input[data-easy-auto='true']").after("<input type='text' data-easy-auto='true' name='patient' onClick='this.select()'></input>").remove();
		clone.find("td").slice(2, 6).each(function(){

			$(this).html("");

		});	
		clone.appendTo('table#payments');

		$('table#payments tr').last().find('.glyphicon-minus').css('cursor', "pointer").click(function(){

			$(this).parents('tr').remove();

		});

		$row  = null;
		clone = null;

		waitForElement();

	});

	$('button#remove-last-row').click(function(e){

		e.preventDefault();
		$('table#payments tr:last').remove();

	});

	$("button#submit").click(function(e){

		e.preventDefault();

		//go row by row and pick up the data.
		//select data by data-type attr (where = update-data);
		var $rows = $('table#payments tr[data-type="update-data"]');
		var data = {};


		$rows.each(function(i){
			console.log('each function');

			var $tr = $(this); 

			//console.log(service_id);
			data[i] = {
				
				'service_id'                   : $tr.find("input[name='service_id']").val(),
				'allowable_insurance_amount'   : $tr.find('input[name="allowable_insurance_amount"]').val(),
				'expected_copay_amount'        : $tr.find('input[name="expected_copay_amount"]').val(),
				'recieved_insurance_amount'    : $tr.find('input[name="recieved_insurance_amount"]').val(),
				'recieved_copay_amount' 	 	   : $tr.find('input[name="recieved_copay_amount"]').val()
			} 


		});

		console.log(data);

		$.ajax({

				url : g.basePath + "insurances/update",

				method : "POST",

				data: {

					'remote'				: 'true',
					'template_name' : '_get',
					'data' 						: data

				},

				beforeSend : function(){

				},

				complete : function(jqXHR, status){

					$('section').html(jqXHR.responseText);


				} 

		}); //ajax 


	}); //submit button click



});



	

</script>