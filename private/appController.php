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

  		//echo "appController::construct. model: $model";
      $this->model_name = $model;
      $this->basePath = $basePath;
  	}

  	public function post($args=[]){

  		//echo "<br>appController::post. ";
      //echo "<br> " . print_r($args, true);
      //parse args and dispatch to appropriate method

      echo "post function";
      $this->model = new $this->model_name($args);

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

     //echo "<br>appController::create.";
     
      
      $this->action = 'create';
      $this->lastInsertIds = [];
      
      if ( !empty($args) ) { 

        //echo "<br><pre>appController::create::args: " . print_r($args, true) . "</pre>";
        //echo "<br><pre>" . print_r(consolidateParams($args), true) . "</pre></br>";


              if (array_key_exists('data', $args) ){


                    //if this was an ajax call. then snag the data and send it to the model. It will have needed informaiton for constructing the model object.

                    $data  = $args['data'];
                    $this->model = new $this->model_name($data);



              }else{

                    //if data was not set (i.e., this wasn't an ajax call) then try to consolidate the params. This would be the case for add patient, for instance, or add services
                 

                    $this->model = new $this->model_name;

                    if (is_multi($args) ){
                     

                        $data = consolidateParams($args);
                     

                    }else{

                        //this want's an ajax call, but you still only want to deal with the $data variable, so load it and make sure that it's an array

                        $data = is_array($args) ? $args : array($args);


                    }

              }


        //Loop through $data and send values to be created in the database. Save the ids.

        foreach($data as $key => $value){

            $id = $this->model->create($value);

            if($id){

                array_push( $this->lastInsertIds, $id );

            }
        
        }        



        $new_record_count = count( $this->lastInsertIds);



        switch($new_record_count){

            case 1:

                $this->flash = array("success" => "One item was successfully created.");
                break;

            case ($new_record_count > 1):

                $this->flash = array("success" => count($this->lastInsertIds) . " items were successfully created.");
                break;

            default:

                $this->flash = array("error" => "Something went wrong. Nothing added to the database");
                break;

        }

        

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