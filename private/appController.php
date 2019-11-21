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

  	public function __construct($model = null, $basePath = null){

      $this->model_name = $model;
      $this->basePath = $basePath;

  	}

  	public function post($args=[]){

      $this->model = new $this->model_name($args);

  	}

    public function get(array $params){

      /*
          There's been a get request relating to the above noted model. The model
          name has already been assigned to a property of this, appController object.
          Now instantiate a new model object and pass it the parameters recieved from
          the initial request. This will contain any user_params as well as any POST
          or GET data in key => value pairs. 

          user_params => 
          template_name =>
          remote => <boolean>
          
          If the 'remote' key is present. It means this is an ajax request and there
          will also be a key for 'template_name.' 

          user_params by model
            patient - patient_id 
      */

        
      $this->model = new $this->model_name($params);
      $this->flash = array_merge_cust($this->flash, $this->model->getFlash());

      if( !empty($params) ){

        if( array_key_exists('remote', $params) ){

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


      /*
        The curl contained the 'create' action and this method was executed by 
        index.php and passed the parameters. First, check to see if this was an
        ajax call. If it was, it will contain the array key 'data.'
      */
      
      $this->action = 'create';
      $this->lastInsertIds = [];
      
      if ( !empty($args) ) { 


          if (array_key_exists('data', $args) ){


                /*

                  if this was an ajax call. then snag the data and send 
                  it to the model. It will have needed informaiton for 
                  constructing the model object.
                
                */

                $data  = $args['data'];
                $this->model = new $this->model_name($data);



          }else{

                /*
                  If data was not set (i.e., this wasn't an ajax call) then 
                  try to consolidate the params. This will be the case for 
                  
                    add patient
                    add services

                  First, check to see if this is a nested array. If so, then run
                  the consolidateParams function. If not, then just grab the data

                */
             

                $this->model = new $this->model_name;

                if (is_multi($args) ){
                 
                  $data = consolidateParams($args);

                }else{

                  $data = is_array($args) ? $args : array($args);

                }

          }


        /*
            Loop through $data and send values to be created in the database.
            Keep track of how many you've entered, and then log the flash message.

        */

        foreach($data as $key => $value){

          $id = $this->model->create($value);

          if($id){

            array_push( $this->lastInsertIds, $id );

          }
        
        }    



        if(count($this->lastInsertIds) === 0){

            array_push($this->flash, array("Error", "Nothing added to the database."));

        }else{

            array_push($this->flash, array("Success", count($this->lastInsertIds)." rows were successfully added to database."));
        }

        /*
            
        */
        

        if( array_key_exists('template_name', $args) ){

            //I'm not sure why template_name would be an array...

            if( is_array($args['template_name'])){

              $args['template_name'] = $args['template_name'][0];

            }
         


            if( preg_match('/\//', $args['template_name']) ){
             
                //if template_name is actually a url with model name included then grab those components

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

            //"template_name" didn't exist. The default action here will be to pluralize the model and return all of the representative objects of that class

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

            echo "is_multi before consolidate: ".print_r($data, true);

            $data = consolidateParams($data);

            echo "is_multi after consolidate: ".print_r($data, true);

          }

          if(array_key_exists('0', $data)){

            //echo "<br><br>array_key_exists '0': ".print_r($data, true);

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