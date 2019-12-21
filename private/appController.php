<?php

  class appController{

    public $model;
    public $model_name;
    public $action;
    public $template_name = null;
    public $lastInsertIds;
    public $lastUpdatedIds;
    public $flash = [];

  	public function __construct($model, $model_name, $action){

      $this->model = $model;
      $this->model_name = $model_name;
      $this->action = $action;

  	}

  	public function post($args=[]){

      //$this->model = new $this->model_name($args);

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
            patient   - patient_id
            patients  - active | inactive | all
      */

      //$this->model = new $this->model_name($params);


      if( !empty($params) ){

        if( array_key_exists('remote', $params) ){

          $this->renderView($params['template_name']);

        }else{

          $this->renderView();

        }

      }else{

        $this->renderView();

      }

      
      

    }

    private function workHorse($args=[])
    {
      /*
        The url contained the 'create' action and this method was executed by 
        index.php and passed the parameters. First, check to see if this was an
        ajax POST request. If it was, it will contain the array key 'data.' Data
        will include all the model information. Other necessary information, like
        <template_name> and <remote>, if present, will be included at the top level
        of the $args variable. 
      */
      
      $updated_or_created_ids = [];
      $failures = [];
      
      if ( !empty($args) ) { 


          if (array_key_exists('data', $args) ){


                /*

                  if this was an ajax call. then snag the data and send 
                  it to the model. It will have needed informaiton for 
                  constructing the model object.

                
                */

                $data  = $args['data'];
                unset($args['data']);



          }else{

                /*
                  If data was not set (i.e., this wasn't an ajax call) then 
                  try to consolidate the params. This will be the case for 
                  
                    add patient
                    add services

                  First, check to see if this is a nested array. If so, then run
                  the consolidateParams function. If not, then just grab the data.


                */
                

                if (is_multi($args) ){
                 
                  $data = consolidateParams($args);
                  unset($args);
                  $args['template_name'] = $this->model_name . 's//';

                }else{

                  $data = is_array($args) ? $args : array($args);
                  error_log("\nappController::workHorse - ".$_SERVER["REQUEST_URI"]." - no data key and args wasn't a nested array: \n", 3, __DIR__ . "/private/errorlog.txt");

                }

          }

          /*
            
            At this point. All application data should be stripped from $args and assigned to $data.

            Do you need to send $data to the model?

              service/create - no
                In this case the params have been consolodated and there is no usable key in the array
                (It's just array([0]=><first service> [1]=><second_service>))

              note/update - no

          */

          //$this->model = new $this->model_name($data);


        /*
            Loop through $data and send values to be created in the database.
            Keep track of how many you've entered, and then log the flash message.

        */

        foreach($data as $key => $value){

          $id = call_user_func(array($this->model, $this->action), $value);

          if($id){

            array_push( $updated_or_created_ids, $id );

          }
        
        }    

        /*
          You're trying to account for three cases here.
            1. It was a complete failure and nothing was updated or created. In that case, get the flash message from the model and 
            add it to the appController flash.

            2. It was a complete success. In that case, add a custome flash to the appController flash including the number of 
            database entries affected. 

            3. It was a partial success. In that case, add a custom flash message to the appController flash, but also get the flash
            from the model. This is sort of a hack, but at least you'll be sending back information. 


          In all cases, send back the IDs of successful database rows, along with the original params with the data striped out.
          Then start by combining the flash message from the model with the flash message of the appController object. 

        */

        $flash_from_model = $this->model->getFlash();
        if(count($flash_from_model) > 0)
        {
          foreach($flash_from_model as $flash)
          {
            array_push( $this->flash, $flash );
          }  
        }
        

        if(count($updated_or_created_ids) === 0){

            return array($updated_or_created_ids, $args);

        }elseif(count($updated_or_created_ids) === count($data)){

            array_push($this->flash, array("Success", count($updated_or_created_ids)." items were successfully {$this->action}d. No errors detected."));
            return array($updated_or_created_ids, $args);
        
        }else{

            array_push($this->flash, array("Success", count($updated_or_created_ids)." items were successfully {$this->action}d. However, some errors were detected..."));
            return array($updated_or_created_ids, $args);


        }

         
      }


    }

    public function create($args=[]){


      list($this->lastInsertIds, $args) = $this->workHorse($args);
      $this->renderView($args);



    } //create


    public function update($args=null){

      list($this->lastUpdatedIds, $args) = $this->workHorse($args);
      $this->renderView($args);
      
    }

    public function delete(){
      
    }

    public function renderView($args=NULL){

      if(gettype($args) == 'array')
      {
     
        error_log("\nappController::renderView - args WAS an array: \n".print_r($args, true), 0);
     
      }else
      {
        error_log("\nappController::renderView - args was NOT an array: ".$args, 0);
      }

      /*
        Render view may be passed an array, a string, or nothing at all.
        The default behavior is to return the file from private/model_name.
        If there's a template passed, then return private/model_name/template_name

        A special case is when the template name is a full path that does not 
        correspond with the originating model. This occures when from the get-payments.php
        file from the patient model, we submit form to insurance/update, but want to return
        patient/get-payments.php for a particular patient (patient/get-payments/<patient_id>)

      */


      $template = "";


      if(gettype($args) === 'array' && array_key_exists('template_name', $args))
      {

        if(preg_match('#\/#', $args['template_name']))
        {
          
          list($this->model_name, $template_name, $user_params) = explode('/', $args['template_name']);
          $this->model = new $this->model_name(array('user_param' => $user_params));
          if(!empty($template_name))
          {
            include($this->model_name . "/" .$template_name . ".php");  
            exit;
          
          }else
          {
            $this->action = 'get';
            include($this->model_name.'.php');
            exit;
          }
          


        }

        $template = $args['template_name'].".php";

      }elseif(gettype($args === 'string') && !empty($args))
      {

        $template = $args.".php";
        
      }


      if(empty($template))
      {

        include($this->model_name . ".php");


      }else
      {

        include($this->model_name . "/" . $template);  

      }
      
      
    }

  }