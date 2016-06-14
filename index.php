<?php

	set_include_path(__DIR__ . "/private/views/");

	require_once(__DIR__ . "/private/FirePHPCore/fb.php");
	require_once("/Users/Apple/Sites/therapyBusiness/private/SplClassLoader.php");
	$classLoader = new SplClassLoader(NULL, '/Users/Apple/Sites/therapyBusiness/private');
  $classLoader->register();


  class frontEndController{

    public   $baseFilePath;

    public   $model;
    public   $action;
    public   $params;

    public   $basePath        = "~Apple/therapyBusiness";
    private  $possibleModels  = ['patient', 'patients', 'service', 'services', 'insurance', 'insurances', 'dashboard', 'otherPayments', 'note', 'notes'];
    private  $possibleActions = ['post', 'get', 'create', 'update'];

  	function __construct(){

      $this->setBaseFilePath();
      $this->parseUri();
      $this->run();

  	}

    private function setBaseFilePath(){

      $this->baseFilePath = __DIR__ ;

    }

    public function getBaseFilePath(){

      return $this->baseFilePath;

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
        }else{
          echo "'$action' was not a valid action.<br>";
        }
      }
    }
    
    protected function setParams($arr=[]){
      //if it's actually a query string, parse it

      //echo "<br>index::setparams - " . print_r($arr, true);

      if(!empty($arr) && preg_match('/\?/', $arr)){
        
        parse_str($arr, $this->params);
        
      }elseif(!empty($arr)){

        $this->params = $arr;

      }
      //if what got passed was just a string. Make it an array to satisfy the run method
      if(gettype($this->params) === 'string'){

        $this->params = [$this->params];

      }

      if( !empty($_POST) ){
        //otherwise check to see if a form was posted.
        
        foreach($_POST as $key => $value){
          $this->params[$key] = $value;
        }

      }

      if( !empty($_GET) ){
        
        foreach($_GET as $key => $value){
          $this->params[$key] = $value;
        }

      }
    
    }

    protected function parseUri(){

      //expecting: model/action(post or get)/params (prefaced with the CRUD verb)
      $path = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");


      if (strpos($path, $this->basePath) === 0) {
          $path = trim( substr($path, strlen($this->basePath)), "/");
      }else{
        echo "<p style='color: black;'>Error in the parseUri funciton of index.php</p>";
        include('home.php');

      }

      @list($model, $action, $params) = explode("/", $path, 3);

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

      //echo "<br>still in index. params: ".print_r($this->params, true).", model: ".print_r($this->model, true).", action: $this->action<br>";
      call_user_func_array( array(new appController($this->model), $this->action), array($this->params) );

    }

  }

  $frontEndController = new frontEndController();

?>