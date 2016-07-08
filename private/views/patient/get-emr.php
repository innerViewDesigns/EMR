<?php

	if(!isset($patient)){
		$patient = $this->model;
	}

	$patient->setServices();
	$patient->setOtherNotes();
	$servicesAndNotes = $patient->combineOtherNotesAndServices($patient->getOtherNotes(), $patient->getServices());
	$this->flash = array_merge_cust($this->flash, $patient->getFlash());


?>
<article class="col-md-12 drop6">
	<button class='btn btn-default' id="add-note">Add Note</button>
		<?php


			include(dirname(__DIR__)."/_flash.php");

			echo "<h3>Services</h3>";
			//echo "<ul class='service-list col-md-3' style='border: 1px solid grey;'>";
			echo "<table id='service-list' class='col-md-3' style='border: 1px solid grey;'>";
			if( $servicesAndNotes ){
				//echo print_r($servicesAndNotes, true);
				foreach( $servicesAndNotes as &$value){
					
					if( isset($value['cpt_code']) ){
						if($value['cpt_code'] == ""){
							$value["cpt_code"] = '(none)';
						}
					}else{
						$value['cpt_code'] = "(".$value['type'].")";
					}

					if( isset($value['dos']) ){

						$value['dos'] = $value['dos']->format('Y-m-d');

					}else if( isset($value['associated_date']) ){

						$value['dos'] = $value['associated_date']->format('Y-m-d');

					}

					if( isset($value['id_services']) ){

						$dataAttr = " data-service-id=".$value['id_services'];

					}else if( isset($value['notes_id']) ){

						$dataAttr = " data-notes-id=".$value['notes_id'];

					}

					if( isset($value['completed']) && $value['completed'] == '0'){
						//$class = "class='text-center relative not-complete'";
						$completed = " background-color: #d9534f; color: white; box-shadow: inset 0px 0px 0px 2px white;'>!";
					}else{
						//$class = "class='text-center'";
						$completed = "'>";
					}

					//echo "<li ". $class .$dataAttr.">".$value['dos']."<span class='cpt text-center' style='font-size: .8em; color: blue; margin-left: 10px;'>".$value['cpt_code']."</span></li>";
					//echo "<tr ". $class .$dataAttr." style='height: 1.6em;'><td>".$value['dos']."</td><td class='cpt text-center' style='font-size: .8em; color: blue;''>".$value['cpt_code']."</td></tr>";
					echo "<tr class='text-center'" .$dataAttr." style='height: 1.6em;'><td style='width: 20%;".$completed."</td><td>".$value['dos']."</td><td class='cpt text-center' style='font-size: .8em; color: blue;''>".$value['cpt_code']."</td></tr>";
				}

			}

		?>
	</table>
</article>

<script>
$(document).ready(function(){

	$('table tr').each(function(){
		
		$li = $(this);

		////////////
		//set styles
		////////////


		$li.hover(function(){
			$(this).css('background-color', '#eee');
		}, function(){
			if( !$(this).hasClass('active') ){
				$(this).css('background-color', '');
			}
		});

		$li.css('cursor', 'pointer');


		////////////
		// on click
		////////////

		$li.click(function(){

			//fetch the note corresponding to this service and display it in a text box.

			var $activeTr = $('table tr.active');

			if( $activeTr.size() == 1){

				$activeTr.removeClass('active').css('background-color', "");

			}

			$(this).css('background-color', "#eee").addClass('active');


			$.ajax({

						url : g.basePath + "note/get",

						data: {

							template_name : "_get",
							remote				: 'true',
							service_id    : $(this).attr('data-service-id'),
							notes_id 			: $(this).attr('data-notes-id')

						},


						beforeSend : function(){

							if( $('div#note').size() > 0 ){
								$('div#note').remove();
							}

							if( $('div.flash').size() > 0 ){
								
								$('div.flash').each(function(){
									$(this).remove();
								});
							
							}
						},

						complete : function(jqXHR, status){

							$('article').append(jqXHR.responseText);
							$('div.flash').addClass('col-md-9');
							
						}
			});

		});

	});

	
	$('button#add-note').click(function(e){

		$("div.modal-body").load(g.basePath + "note/create", function(){
			
			$('#myModal').modal();
		
		});

	});



});
</script>