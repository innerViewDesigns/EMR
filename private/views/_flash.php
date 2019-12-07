<?php

/*
	The flash should be an array of arrays:
		$this->flash == array([0] => array([0] => <success> or <error> [1] => <message>)
													[1] => array([0] => <success> or <error> [1] => <message>))
*/



if( !empty($this->flash) ){

	if( is_array($this->flash)){

		foreach( $this->flash as $flash){
			
			$status = preg_match('/error/', $flash[0]) ? 'error' : 'success';
			
			echo "<div class='flash $status'>".$flash[0]." - ".$flash[1]."</div>";
			
		}
			
	}

}


?>