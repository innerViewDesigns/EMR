<script>

		///////////////////////////////////////////
		//Make sure the checkbox start synched up...
		///////////////////////////////////////////

		$('input.checkbox-custom').each(function(){

			if($(this).attr('data-checked') == "true"){

				var i = $(this).attr('name');
				$(this).prop('checked', true);
				$(this).siblings('div.checkbox-custom[data-target='+i+']').addClass('checked');

			}

		});

		/////////////////////////
		//Then keep them synched
		////////////////////////

		$('div.checkbox-custom').each(function(){

			$(this).click(function(){

				var i = $(this).attr('data-target');
				var $checkbox = $(this).siblings('input[name='+i+']');

				if( $checkbox.prop('checked') ){

					$checkbox.prop('checked', false);
					$(this).toggleClass('checked');

				}else{

					$checkbox.prop('checked', true);
					$(this).toggleClass('checked');			

				}

			});

		});

</script>