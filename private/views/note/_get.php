<?php 
	
	$noteModel = $this->model;

	if(isset($this->lastInsertIds) && count($this->lastInsertIds) == 1)
	{
		$id = $this->lastInsertIds[0];

		if($noteModel->setNoteById($id))
		{

		}

	}elseif(isset($noteModel->service_id_notes))
	{
		$noteModel->setNoteByServiceId();

	}elseif(isset($noteModel->notes_id))
	{
		$noteModel->setNoteById();
	}

	

	include(dirname(__DIR__)."/_flash.php"); 

?>

<div id="note" <?php if( isset( $noteModel->service_id_notes ) ){

												echo "data-service-id='".$noteModel->service_id_notes."'";
								
											}elseif( isset ($noteModel->notes_id) ){

												echo "data-notes-id='".$noteModel->notes_id."'";

											} ?> class='col-md-9 col-lg-9' >

	
	<textarea class="form-control" name="note" id="noteTextarea" style="width: 100%;" rows="15"><?php if(!empty($noteModel->properties)){echo $noteModel->properties['note'];} ?></textarea>
	<button id="update" class="pull-right btn btn-primary" style="margin-top: 15px;">Update</button>


</div>

<script>

$(document).ready(function(){

	//Get the button and update
	$('button#update').click(function(){

		$.ajax({

				url : "note/update",

				method : "POST",
				
				data: {

					template_name : "_get",
					remote				: 'true',
					data 					: [

						{
							note 			 : $('textarea#noteTextarea').val(),
							service_id : $('div#note').attr('data-service-id'),
							notes_id   : $('div#note').attr('data-notes-id')
						}
					]
					

					

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

					console.log(jqXHR);
					$('article div[data-target=put-note-here').append(jqXHR.responseText);
					$('div.flash').css("width", "70%").addClass('pull-right').css('margin-right', "2.5%");
					
				}
			
		}); 
	
	});

	if( $('div.flash').size() > 0){
		if( parseInt($('div#service-list').height()) < 55){
			$('div#note').addClass('col-md-offset-3');
		}
	}


});

</script>

