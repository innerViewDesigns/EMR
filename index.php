<?php

  ini_set("error_log", __DIR__ . "/private/errorlog.txt");
	set_include_path(__DIR__ . "/private/views/");

	require_once(__DIR__ . "/private/FirePHPCore/fb.php");
	require_once(__DIR__ . "/private/SplClassLoader.php");
  require_once(__DIR__ . "/private/validations.php");
	$classLoader = new SplClassLoader(NULL, __DIR__ . '/private');
  $classLoader->register();

  class frontEndController{

    public   $baseFilePath;

    public   $model  = 'dashboard';
    public   $action = 'get';
    public   $params = [];

    public   $basePath        = "therapyBusiness";
    private  $possibleModels  = ['patient', 'patients', 'service', 'services', 'insurance', 'insurances', 'dashboard', 'otherPayments', 'note', 'notes', 'invoice'];
    private  $possibleActions = ['post', 'get', 'create', 'update'];

  	function __construct(){
     
      // echo "Step 1 frontEndController::__construct<br>";

      $this->parseUri();
      $this->run();

  	}

    protected function setModel($model=null, $id=null){
        

        if( in_array($model, $this->possibleModels)){

          $this->model = $model;
          $this->id    = $id;

        }
        else{

          echo "'$model' was not a valid model.<br>";

        }

    }
    
    protected function setAction($action=null){
    
      if(isset($action)){
    
        if( in_array($action, $this->possibleActions)){

          $this->action = $action;
          // echo "Step 4 frontEndController::setAction, action: ".$this->action."<br>";

        }else{
          echo "'$action' was not a valid action.<br>";
        }
    
      }
    
    }
    
    protected function setParams($user_params=[]){

      /*
          
          What got passed was an array with possition '0' set
          to whatever came after model/action/<user_params>.
          Check to see if there was actually something there and
          if so assign it to a property of this object.

          Then check to see if there is any POST or GET data and
          add that to the property if there was. 

      */

      if(!empty($user_params[0])){

        $this->params['user_param'] = $user_params[0];

      }

      
      if( !empty($_POST) ){
                
        foreach($_POST as $key => $value){
      
          $this->params[$key] = $value;
      
        }
      }

      
      if( !empty($_GET) ){        

        foreach($_GET as $key => $value){
          
          $this->params[$key] = $value;
        
        }
      }

      //fb("From index.php::params: ".print_r($this->params, true));

    }

    protected function parseUri(){

      fb($_SERVER["REQUEST_URI"]);
      //fb("GET variable: ". print_r($_GET, true) );
      //fb("POST variable: ".print_r($_POST, true) );


      /*

          We're expecting: model/action/params. Start by
          stripping everything but the base path - i.e.,
          all the url encoded variables, if any there should
          be, get gone.

          Then split the resulting string up by the backslash

      */


      $path = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");


      if(strpos($path, $this->basePath) === 0) {
      
          $path = trim( substr($path, strlen($this->basePath)), "/");

      }else{
    
        echo "<p style='color: black;'>Error in the parseUri funciton of index.php</p>";
    
        include('home.php');

      }

      @list($model, $action, $params) = explode("/", $path, 3);


      //fb("After explode. Model: ".$model." --- Action: ".$action." --- Params: ".$params);

      /*
          If there was a model and an action passed, send each
          to a function to make sure that they are valid options.
          If there was no model or action passed, the defaults will
          persist.

          Then convert params to an array send it to a method to be 
          dealt with. 

      */

      if( !empty($model) && !preg_match('/index/', $model) ) {

        $this->setModel($model);
          
      }else
      {
        $this->setModel('dashboard');
      }


      if (!empty($action)){
        
        $this->setAction($action);
      
      }else
      {
        $this->setAction('get');
      }

      $newParams[0] = $params;
      $this->setParams($newParams);



    }

    public function run(){

      $app = new appController($this->model);
      $app->action = $this->action;


      call_user_func(array($app, $this->action), $this->params);  
      
      

    }

  }

  $frontEndController = new frontEndController();

?>