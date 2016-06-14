<?php
  
  set_include_path(__DIR__ . "/views");

	require_once(__DIR__ . "/FirePHPCore/fb.php");
  require_once(__DIR__ . "/validations.php");
	require_once("/Users/Apple/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Apple/Sites/therapyBusiness/private');
  $classLoader->register();

  class appController{

    public $model;
    public $model_name;
    public $action;
    public $template_name = null;
    public $lastInsertIds;
    public $lastUpdatedIds;
    public $flash = [];

  	public function __construct($model = null){

  		//echo "appController::construct. model: $model";
      $this->model_name = $model;
  	}

  	public function post($args=[]){

  		//echo "<br>appController::post. ";
      //echo "<br> " . print_r($args, true);
      //parse args and dispatch to appropriate method

  	}

    public function get($args=[]){

      //echo "<br>appController::get";
      //echo "</br>args = ". print_r($args, true);
        
      $this->model = new $this->model_name($args);
      $this->flash = array_merge_cust($this->flash, $this->model->getFlash());

      if( !empty($args) ){

        if( array_key_exists('remote', $args) ){

          $this->renderView($args['template_name']);

        }else{

          $this->action = 'get';
          $this->renderView();

        }

      }else{

        $this->action = 'get';
        $this->renderView();

      }

      
      

    }

    public function create($args=[]){

     // echo "<br>appController::create.";
      echo "<br>appController::create::args: " . print_r($args, true) . "<br>";

      $this->action = 'create';
      $lastInsertIds = [];
      $this->model = new $this->model_name();

      if ( !empty($args) ) { 


        /////////////////////////////////////////////////
        //when you send through ajax prunning is required
        /////////////////////////////////////////////////

        if (array_key_exists('data', $args) ){

          
          $this->template_name = $args['template_name'];
          $args                = $args['data'];


        }

        /////////////////////////////////////////////////////
        //Check to see if you need to consolidate the params
        /////////////////////////////////////////////////////

        if( is_array($args) ){

          if( is_multi($args) ){

            //echo "<br>is multi before: ".print_r($args, true);
            $args = consolidateParams($args);
            //echo "<br>is multi after: ".print_r($args, true);

          }

          foreach($args as $key => $value){

            $id = $this->model->create($value);

            if($id){

              array_push( $lastInsertIds, $id);

            }
            
          }

        }else{

          $id = $this->model->create($args);

        }

        if( isset($lastInsertIds) ){

          $this->lastInsertIds = $lastInsertIds;
          $this->flash = array("success" => count($lastInsertIds)." items were successfully added to the database.");

        }else{

           $this->flash = array("success" => "One item was successfully added to the database.");

        }

        

        if( isset($model_name) ){

            $patient_id = $args['patient_id'];
            $this->model_name = $this->model_name($patient_id);
            

        }else{

          $this->model_name = $this->model_name . 's';

        }

         
         $this->action = 'get';
         $this->flash = array_merge_cust($this->flash, $this->model->getFlash());
         $this->model = new $this->model_name;
         $this->renderView($this->template_name);

       
         
      }else{ 

        //if there were no arguments, display the form
        $this->renderView();

      } 
      


    } //create


    public function update($args=null){
      //echo "<br>appController::update.";
      //echo "<br>appController::args: " . print_r($args, true) . "<br>";

      $this->action   = 'update';
      $lastUpdatedIds = [];
      

      if ( !empty($args) ) { 

        if( is_array($args) ){

          if (array_key_exists('data', $args) ){

             $data  = $args['data'];
             $this->model = new $this->model_name($data);

          }else{

            $this->model = new $this->model_name;

          }

          if (is_multi($data) ){

            foreach($data as $key => $value){

                $id = $this->model->update($value);

                if($id){

                  array_push( $lastUpdatedIds, $id);

                }
                
            }

          }else{

            $id = $this->model->update($data);

              if($id){

                array_push( $lastUpdatedIds, $id);

              }

          }

        }else{

          $id = $this->model->update($args);

        }

        if( isset($lastUpdatedIds) ){

          $this->lastUpdatedIds = $lastUpdatedIds;
          $this->flash = array("success" => count($lastUpdatedIds)." items were successfully updated.");

        }else{

           $this->flash = array("success" => "One item was successfully updated.");

        }
        

        if( array_key_exists('template_name', $args) ){
         
          if( preg_match('/\//', $args['template_name']) ){

            list($this->model_name, $this->template_name, $params) = explode("/", $args['template_name']);
            $this->flash = array_merge_cust($this->flash, $this->model->getFlash());
            $this->model = new $this->model_name($params);
            $this->renderView($this->template_name);

          }else{

            $this->template_name = $args['template_name'];
            $this->renderView($this->template_name);

          }

          
          

        }else{

          $this->model_name = $this->model_name . 's';
          $this->action = 'get';
          $this->flash = array_merge_cust($this->flash, $this->model->getFlash());
          $this->model = new $this->model_name;
          $this->renderView();


        }
      
         
      }else{ 

        //if there were no arguments, display the form
        $this->renderView();

      } 

      
    }

    public function delete(){
      
    }

    private function renderView($template_name=null){

      if( !empty($template_name) ){

         include($this->model_name . "/" . $template_name .".php");

      }else{

        include($this->model_name . ".php");

      }

    }

  }