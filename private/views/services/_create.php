<h1>Add Services</h1>

<article class="drop6">

	<form action= "service/create" method="post" id="add-services-form">
		<table class="col-md-12 table" id="myTable">
			<tr>
				<th class="text-center col-md-3">Patient</th> 
				<th class="text-center col-md-3">Date</th>
				<th class="text-center">$?</th>
				<th class="text-center">Ins?</th>
				<th class="text-center">in net?</th>
				<th class="text-center col-md-2">CPT</th>
				<th class="text-center col-md-1">Dx1</th>
				<th class="text-center col-md-1">Dx2</th>
				<th class="text-center col-md-1">Dx3</th>
			</tr>

			<tr data-clone="true">
				<td class="relative">
					<input type="text" data-easy-auto="true" name='patient[]' onClick="this.select()"></input>
				  <input type="hidden" name='patient_id[]'></input>
				  <div class="glyph glyphicon glyphicon-chevron-right"></div>
				</td>
				<td>
					<input type="text" class="form-control" name='dos[]'></input>
				</td>
				<td>
					<input type="hidden" data-type = "former-checkbox" name='charged[]'>
					<div class="checkbox-custom"></div>
				</td>
				<td>	
					<input type="hidden" data-type = "former-checkbox" name='insurance_used[]'>
					<div class="checkbox-custom"></div>
				</td>
				<td>	
					<input type="hidden" data-type = "former-checkbox" name='in_network[]'>
					<div class="checkbox-custom"></div>
				</td>
				<td>
					<input type="text" class="form-control" data-column-name = 'cpt_code' name='cpt_code[]'></input>
				</td>
				<td>
					<input type="text" class="form-control" data-column-name = 'dx1' name='dx1[]'></input>
				</td>
				<td>
					<input type="text" class="form-control" data-column-name = 'dx2' name='dx2[]'></input>
				</td>
				<td class="relative">
					<input type="text" class="form-control" data-column-name = 'dx3' name='dx3[]'></input>
					<div class="glyph glyphicon glyphicon-minus"></div>
				</td>
			</tr>

		</table>
		  <button type="submit" class="btn btn-default">Submit</button>
		  <button class="btn btn-default" id="add-more-services">Add Another</button>
	</form>

</article>

<script>

$(document).ready(function(){

	function manageCheckboxes(inputs, divs){

		///////////////////////////////////////////
		//Make sure the checkbox start synched up...
		///////////////////////////////////////////

		inputs.each(function(){

			if($(this).val() == '1'){
				$(this).siblings('div.checkbox-custom').addClass('checked');
			}else{
				$(this).val('0');
			}


		});

		/////////////////////////
		//Then keep them synched
		////////////////////////

		divs.each(function(){

			$(this).click(function(){

				var $checkbox = $(this).siblings('input');

				if( $checkbox.val() == '1' ){

					$checkbox.val('0');
					$(this).toggleClass('checked');

				}else{

					$checkbox.val('1');
					$(this).toggleClass('checked');			

				}

			});

		});

	}//function manage checkboxes

	function waitForElement(){

		///////////////////////////////////////////
		//After the header loads, set up the row...
		///////////////////////////////////////////

		if( typeof g.easyAutoData !== 'undefined'){

				//Then build the needed options and pass that object to the
				//easyAutoComplete function.

				$row = $('table#myTable').find('tr').last();

				$row.find("input[data-easy-auto='true']").each(function(){

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

					$(this).easyAutocomplete(options).parent('div').css('float', 'left');

				}); //loop through easyAutos
				

				
				///////////////////////////////////////////////////////////////
				//Should only be able to delete rows that are not the first row
				///////////////////////////////////////////////////////////////

				if( $('table#myTable tr').size() > 1){

					$row.find('div.glyphicon-minus').css("cursor", "pointer").click(function(){

						$(this).parents('tr').remove();

					});

				}


				///////////////////////////
				//enable the datetimepicker
				///////////////////////////
				
				$row.find('input[name="dos[]"]').datetimepicker({

					validateOnBlur:false,
					allowTimes:g.timeOptions()

					});

				///////////////////////////
				//enable the checkboxes
				///////////////////////////

				manageCheckboxes( $row.find('input[data-type="former-checkbox"]'), $row.find('div.checkbox-custom') );
				

				///////////////////////////////////////////////////////////////////
				//attach ajax event handler responsible for pulling in previous dxs
				////////////////////////////////////////////////////////////////////

				$('div.glyphicon-chevron-right').on('click', function(){

					var $row = $(this).parents('tr');
					var id   = $row.find("input[name='patient_id[]']").val();

					$.ajax({

						url : g.basePath + "service/get",

						data: {

							template_name : "_info-only",
							remote				: 'true',
							patient_id    : id

						},

						dataType : 'json',

						beforeSend : function(){
							$row.css('background-color', '');
						},

						complete : function(jqXHR, status){

							if( typeof jqXHR.responseJSON !== 'undefined'){

								var s = jqXHR.responseJSON;
								//console.log(s);
								
								$row.find('td').slice(5, 10).each(function(i){

									var $input = $(this).find('input');
									var col   = $input.attr("data-column-name");
									console.log(col);

									$input.val(s[col]);


								});

								/////////////////////////////////////
								//sync up the checkboxes for this row
								/////////////////////////////////////

								if(s['insurance_used'] == 0){

									$row.find('input[name="insurance_used[]"]').val('0');	
									$row.find('input[name="insurance_used[]"]').siblings('div').removeClass('checked');

								}else{

									$row.find('input[name="insurance_used[]"]').val('1');	

									if( !$row.find('input[name="insurance_used[]"]').siblings('div').hasClass('checked') ){

										$row.find('input[name="insurance_used[]"]').siblings('div').addClass('checked');

									}

								}

								if(s['charged'] == 0){

									$row.find('input[name="charged[]"]').val('0');
									$row.find('input[name="charged[]"]').siblings('div').removeClass('checked');

								}else{

									$row.find('input[name="charged[]"]').val('1');	

									if( !$row.find('input[name="charged[]"]').siblings('div').hasClass('checked') ){

										$row.find('input[name="charged[]"]').siblings('div').addClass('checked');

									}
									
								}

								if(s['in_network'] == 0){

									$row.find('input[name="in_network[]"]').val('0');
									$row.find('input[name="in_network[]"]').siblings('div').removeClass('checked');

								}else{

									$row.find('input[name="in_network[]"]').val('1');	

									if( !$row.find('input[name="in_network[]"]').siblings('div').hasClass('checked') ){

										$row.find('input[name="in_network[]"]').siblings('div').addClass('checked');

									}


								}

								//manageCheckboxes( $row.find('input[type="checkbox"]'), $row.find('div.checkbox-custom') );

							}else{

								$row.css('background-color', '#d9534f');

							}

						}

					}); //ajax

				}); //click chevron to trigger ajax

		}else{

			/////////////////////////////////////
			//We need the header to have loaded...
			/////////////////////////////////////

			window.setTimeout(function(){

	       waitForElement();

	    },250);

		}

	}//waitForElement

	waitForElement();


	//////////////////////////////////
	//Attach add-service event handler
	//////////////////////////////////


	$('#add-more-services').click(function(e){

		var $row = $('table#myTable tr').last();
		var clone = $row.clone();
		clone.find("input[data-easy-auto='true']").after("<input type='text' data-easy-auto='true' name='patient[]' onClick='this.select()'></input>").remove();
		clone.find('td').slice(6, 8).find('input').each(function(){$(this).val("")});
		clone.appendTo('table');


		$row  = null;
		clone = null;

		e.preventDefault();
		waitForElement();

	});


});



	

</script>