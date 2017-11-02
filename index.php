<?php

	set_include_path(__DIR__ . "/private/views/");

	require_once(__DIR__ . "/private/FirePHPCore/fb.php");
	require_once(__DIR__ . "/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, __DIR__ . '/private');
  $classLoader->register();


  class frontEndController{

    public   $baseFilePath;

    public   $model;
    public   $action;
    public   $params;

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

          // echo "Step 3 frontEndController::setModel, model: ".$this->model." id: ".$this->id."<br>";
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
    
    protected function setParams($arr=[]){
      
      //if it's actually a query string, parse it

      /*
      if(!empty($arr) && preg_match('/\?/', $arr)){
        
        parse_str($arr, $this->params);
        // echo "Step 5a frontEndController::setParams, params ".print_r($this->params, true)."<br>";

      }else
      */

      if(!empty($arr)){

          $this->params = $arr;
          // echo "Step 5b frontEndController::setParams, params ".print_r($this->params, true)."<br>";

          //if what got passed was just a string. Make it an array to satisfy the run method
          if(gettype($this->params) === 'string'){

            $this->params = [$this->params];
            // echo "Step 5c frontEndController::setParams, params ".print_r($this->params, true)."<br>";

          }


      }


      


      if( !empty($_POST) ){
        
        //otherwise check to see if a form was posted.
        foreach($_POST as $key => $value){
      
          $this->params[$key] = $value;
          // echo "Step 5d frontEndController::setParams, params ".print_r($this->params, true)."<br>";
      
        }

      }

      if( !empty($_GET) ){
        
        foreach($_GET as $key => $value){
        
          $this->params[$key] = $value;
          // echo "Step 5e frontEndController::setParams, params ".print_r($this->params, true)."<br>";
        
        }

      }

    }

    protected function parseUri(){

      //expecting: model/action(post or get)/params (prefaced with the CRUD verb)
      $path = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");
      
      // echo "Step 2 frontEndController::parseUri, path: ".$path."<br>";

      if (strpos($path, $this->basePath) === 0) {
    
          $path = trim( substr($path, strlen($this->basePath)), "/");
    
      }else{
    
        echo "<p style='color: black;'>Error in the parseUri funciton of index.php</p>";
    
        include('home.php');

      }



      @list($model, $action, $params) = explode("/", $path, 3);
      //echo $params . "<br>";


      if ( !empty($model) && !preg_match('/index/', $model) ) {

          $this->setModel($model);
          
      }else{

        $this->setModel('dashboard');

      }

      if (!is_null($action)) {
        
        $this->setAction($action);
       // echo "action: $action<br>";
      
      }else{

        $this->setAction("get");

      }

      $this->setParams($params);



    }

    public function run(){

      // echo "Step 6 frontEndController::run, params ".print_r($this->params, true)."<br>";
      call_user_func_array( array(new appController($this->model), $this->action), array($this->params) );

    }

  }

  $frontEndController = new frontEndController();

?>