<?php
  
  set_include_path(__DIR__ . "/views");

	require_once(__DIR__ . "/FirePHPCore/fb.php");
  require_once(__DIR__ . "/validations.php");
  require_once(__DIR__ . "/SplClassLoader.php");
  $classLoader = new SplClassLoader(NULL, __DIR__);
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
     //echo "<br>appController::create::args: " . print_r($args, true) . "<br>";
      
      $this->action = 'create';

      if ( !empty($args) ) { 

        if( is_array($args) ) {

          if (array_key_exists('data', $args) ){

             $data  = $args['data'];
             $this->model = new $this->model_name($data);

          }elseif (array_key_exists('template_name', $args) ){
         
              if( preg_match('/\//', $args['template_name']) ){

                list($this->model_name, $this->template_name) = explode("/", $args['template_name']);
                //$this->flash = array_merge_cust($this->flash, $this->model->getFlash());
                $this->model = new $this->model_name();
                $this->renderView($this->template_name);
                return true;

              }else{

                $this->template_name = $args['template_name'];
                $this->renderView($this->template_name);
                return true;

              }

          }else{

            //if neither array key "data" or array_key "template_name" exist then...
            $this->model = new $this->model_name;

          }

          //if data was not set (i.e., this wasn't an ajax call)
          //then try to consolidate the params. This would be the case
          //for add patient, for instance, or add services

          if( !isset($data) ){
          //  echo "<br>made it into data not being set";
            if (is_multi($args) ){
        //      echo "<br>made it into multi";
              $data = consolidateParams($args);
       //       echo "<br>".print_r($data, true);
            }else{

              $data = $args;

            }

          }

          $lastInsertIds = [];
          foreach($data as $key => $value){

              $id = $this->model->create($value);

              if($id){

                array_push( $lastInsertIds, $id);

              }
              
          }


        }else{ 

          //if args wasn't an array:
          $id = $this->model->create($args);

          if($id){

            $lastInsertIds = [];
            array_push( $lastInsertIds, $id);

          }

        }

        //Args wasn't empty and we already dealth with wether it was an array or not

        if( isset($lastInsertIds) ){

          $this->lastInsertIds = $lastInsertIds;
          $this->flash = array("success" => count($lastInsertIds)." items were successfully created.");

        }else{

           $this->flash = array("success" => "One item was successfully created.");

        }
        

        if( array_key_exists('template_name', $args) ){

          if( is_array($args['template_name'])){

            $args['template_name'] = $args['template_name'][0];

          }
         
          if( preg_match('/\//', $args['template_name']) ){
            if(count(explode('/', $args['template_name'])) > 2){

              list($this->model_name, $this->template_name, $params) = explode("/", $args['template_name']);

            }else{

              list($this->model_name, $this->template_name) = explode("/", $args['template_name']);
              $params = null;

            }

            $this->flash = array_merge_cust($this->flash, $this->model->getFlash());
            $this->model = new $this->model_name($params);
            $this->renderView($this->template_name);

          }else{

            $this->template_name = $args['template_name'];
            $this->renderView($this->template_name);

          }


        }else{

          //"template_name" didn't exist

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

            $data = consolidateParams($data);

          }

          if(array_key_exists('0', $data)){

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