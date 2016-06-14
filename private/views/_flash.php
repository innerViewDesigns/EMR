<?php
//echo "<br>Flash goes here.";
//echo "<br>".print_r($this->flash, true);

if( !empty($this->flash) ){

	if( is_array($this->flash)){

		foreach( $this->flash as $key => $value){

			if(is_array($value)){
				foreach($value as $key1 => $value1){
					$status = preg_match('/error/', $key1) ? 'error' : 'success';
					echo "<div class='flash $status'>".$value1."</div>";
				}
			}else{
				$status = preg_match('/error/', $key) ? 'error' : 'success';
				echo "<div class='flash $status'>".$value."</div>";
			}

			
			
		}

	}

}

?>