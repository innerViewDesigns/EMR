<?php

  class appController{

    public $model;
    public $model_name;
    public $action;
    public $template_name = null;
    public $lastInsertIds;
    public $lastUpdatedIds;
    public $flash = [];

  	public function __construct($model = null){

      $this->model_name = $model;

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
            patient   - patient_id
            patients  - active | inactive | all
      */

      $this->model = new $this->model_name($params);


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
      
      $databaseFeedback = [];
      
      if ( !empty($args) ) { 


          if (array_key_exists('data', $args) ){


                /*

                  if this was an ajax call. then snag the data and send 
                  it to the model. It will have needed informaiton for 
                  constructing the model object.

                
                */

                $data  = $args['data'];



          }else{

                /*
                  If data was not set (i.e., this wasn't an ajax call) then 
                  try to consolidate the params. This will be the case for 
                  
                    add patient
                    add services

                  First, check to see if this is a nested array. If so, then run
                  the consolidateParams function. If not, then just grab the data

                */
                

                if (is_multi($args) ){
                 
                  $data = consolidateParams($args);
                  $args['template_name'] = $this->model_name . 's//';

                }else{

                  $data = is_array($args) ? $args : array($args);

                }

          }

          $this->model = new $this->model_name($data);


        /*
            Loop through $data and send values to be created in the database.
            Keep track of how many you've entered, and then log the flash message.

        */

        foreach($data as $key => $value){

          $id = call_user_func(array($this->model, $this->action), $value);

          if($id){

            array_push( $databaseFeedback, $id );

          }
        
        }    



        if(count($databaseFeedback) === 0){

            array_push($this->flash, array("Error", "Something went wrong."));
            return array($databaseFeedback, $args);

        }else{

            array_push($this->flash, array("Success", count($databaseFeedback)." items were successfully {$this->action}d."));
            return array($databaseFeedback, $args);
        }

         
      }


    }

    public function create($args=[]){


      list($this->lastInsertIds, $args) = $this->workHorse($args);
      $this->renderView($args);



    } //create


    public function update($args=null){


      fb("Update function.");
      list($this->lastUpdatedIds, $args) = $this->workHorse($args);
      $this->renderView($args);

      
    }

    public function delete(){
      
    }

    public function renderView($args=NULL){

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

      fb('rederView::$args -- ' . gettype($args) );

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