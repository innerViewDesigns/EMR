<?php

	require_once(__DIR__ . "/protected/dbHelper.php");

	class dbObj{

		/*** mysql hostname - production **/
		private $hostname 	= '';
		private $username 	= '';
		private $password 	= '';
		private $dbname 		= '';
		public  $table 			= '';
		public  $db;
		public  $databaseProblem = false; 
		public  $result;

		function __construct(){

			if( !isset($dbHelper) ){

				$dbHelper = new dbHelper();

			}

			$this->hostname = $dbHelper->hostname;
			$this->username = $dbHelper->username;
			$this->password = $dbHelper->password;
			$this->dbname   = $dbHelper->dbname;
			$this->table    = $dbHelper->table;


			$this->open();

		}

		public function open(){


			try{
			  	$this->db = new PDO("mysql:host={$this->hostname};dbname={$this->dbname}", $this->username, $this->password);
				
				/*** set the error reporting attribute ***/
		    	$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		    $this->databaseProblem = false;

			}

			catch(PDOException $e){
				$this->databaseProblem = true;
				echo "<br>Database problem";
				echo "<br>".print_r($e, true);
			}

		} // open

		public function get_visitor_row($visitor_id=""){

			$stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE visitor_id = :visitor_id");
			$stmt->bindParam(':visitor_id', $visitor_id);
			$stmt->execute();
			$result = $stmt->fetchAll();
			$stmt = null;

			return $result;

		}


		public function close(){
			$this->db = null;
		}

	} //class

?>