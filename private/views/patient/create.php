<article class="drop6" id="add-patient-form-fields">
	
	<form action="patient/create" method="post" id="add-patient-form">
	 <div id="add-patient-form-fields">
	 
	  <div class="row clearfix">
		 
		  <div class="form-group col-md-4">
		    <label for="first_name">First Name</label>
		    <input type="text" class="form-control" id="" name='first_name[]' placeholder="First"></input>
		  </div>
		  <div class="form-group col-md-4">
		    <label for="first_name">Middle Name</label>
		    <input type="text" class="form-control" id="" name='middle_name[]' placeholder="Middle"></input>
		  </div>
		  <div class="form-group col-md-4">
		    <label for="last_name">Last Name</label>
		    <input type="text" class="form-control" id="" name='last_name[]' placeholder="Last"></input>
		  </div>
		
		</div>

		<div class="row clearfix ">

			<div class="form-group col-md-4">
		  	<label for="dob">DOB</label>
		    <input type="text" class="form-control" id="" name='dob[]'></input>
		  </div>
			<div class="form-group col-md-4">
				<label for="first_name">Phone1 type</label>
		    <input type="text" class="form-control" id="" name='phone1_type[]' placeholder="type"></input>
			</div>
			<div class="form-group col-md-4">
				<label for="first_name">Phone1</label>
		    <input type="text" class="form-control" id="" name='phone1[]' placeholder="phone1"></input>
			</div>

		</div>

		<div class="row clearfix ">

			<div class="form-group col-md-4">
				<label for="first_name">Email</label>
		    <input type="text" class="form-control" id="" name='email[]' placeholder="example@example.com"></input>
			</div>
			<div class="form-group col-md-4">
				<label for="first_name">Phone2 type</label>
		    <input type="text" class="form-control" id="" name='phone2_type[]' placeholder="type"></input>
			</div>
			<div class="form-group col-md-4">
				<label for="first_name">Phone2</label>
		    <input type="text" class="form-control" id="" name='phone2[]' placeholder="phone2"></input>
			</div>

		</div>

		<div class="row clearfix">

			<div class="form-group col-md-4">
				<label for="first_name">Social Sec. #</label>
		    <input type="text" class="form-control" id="" name='ss[]' placeholder="social"></input>
			</div>

		</div>

	</div>

  <button type="submit" class="btn btn-default">Submit</button>
  <button class="btn btn-default" id="add-more-patients">Add Another</button>
	  
	
	</form>
</article>

<script>
(function(){

	var $addButton    = $("button#add-more-patients"),
			$form         = $("div#add-patient-form-fields"),
			$removeButton = $('div.remove-patient').css('cursor', 'pointer');

	function remove(el){

		el.click(function(e){

			el.parent('#add-patient-form-fields').remove();
			e.preventDefault();

		});
	}

	$removeButton.height(function(){
			var height = $(this).parent('#add-patient-form-fields').height();
			var padding = (height/2) - ( $(this).height() / 2 );
			$(this).css('padding-top', padding);
			return height;
		});

	$addButton.click(function(e){
		
		$newForm = $form.clone();
		$newForm
			.insertBefore('button[type="submit"]')
			.find('input')
			.val('');

		remove($newForm.find('div.remove-patient'));

		e.preventDefault();

	});

	remove($removeButton);

})();
	


</script>