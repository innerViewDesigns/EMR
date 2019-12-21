<?php

  ini_set("error_log", "/Users/Lembaris/Sites/therapyBusiness/private/errorlog.txt");
  date_default_timezone_set('America/Los_Angeles');
  set_include_path(__DIR__ . "/private/views/");

  require_once(__DIR__ . "/vendor/autoload.php");
  require_once(__DIR__ . "/private/validations.php");

  require_once(__DIR__ . "/private/SplClassLoader.php");
  $classLoader = new SplClassLoader(NULL, __DIR__ . '/private');
  $classLoader->register();


  $frontEndController = new frontEndController();
  $model_name = $frontEndController->model_name;
  $action = $frontEndController->action;
  $params = $frontEndController->params;

  $model = new $frontEndController->model_name($frontEndController->params);
  
  $app = new appController($model, $model_name, $action);
  $app->{$action}($params);



?>