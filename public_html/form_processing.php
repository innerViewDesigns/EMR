<?php

set_include_path("../private/includes/");
require_once 'validations.php';
//require_once("/home2/ichaenv0/private/includes/validations.php");
require 'return_response.php';
//require_once("/home2/ichaenv0/private/includes/return_response.php");


$errors           = array();      // array to hold validation errors
$allowed_params   = array();
$safe_params      = array();
$nameTracker      = 0;
$contactTracker   = 0;

    
    /////////////////////////
    //Get white listed params
    /////////////////////////

    $allowed_params = allowed_params(['first_name', 'last_name', 'email', 'phone', 
                                        'phone_pref', 'email_pref', 'message', 'token']);

    /////////////
    //check token
    /////////////

    function kill(){
        return_response();
    }


    if( ! validate_presence( $allowed_params['token'] ) || ! validate_token( $allowed_params['token'] ) ){

        $errors['token'] = "Something's wrong here. Either you're not human, or you don't have cookies enabled. If you are indeed human, please enable cookies and reload the page in order to use this form. Thanks!";
        kill();
        return false;

    }

    /////////////////////
    // Validate Params
    /////////////////////

        //First Name

    if( ! validate_presence( $allowed_params['first_name'] ) )
        $errors['first_name'] = 'First name is required.';

    elseif( ! validate_length($allowed_params['first_name'], 1, 15) )
        $errors['first_name'] = 'First name must be between 1 and 15 characters.';
    
    else
        $safe_params['first_name'] = sanatize_names($allowed_params['first_name']);
    

        //Last Name

    if( ! validate_presence( $allowed_params['last_name'] ) )
        $errors['last_name'] = 'Last name is required.';

    elseif( ! validate_length($allowed_params['last_name'], 1, 20) )
        $errors['last_name'] = 'Last name must be between 1 and 20 characters.';
    else
        $safe_params['last_name'] = sanatize_names($allowed_params['last_name']);

    

        //Contact Information

    if( validate_presence( $allowed_params['phone'] ) ){
        
        ++$contactTracker;
        if( ! validate_phone_number( $allowed_params["phone"] ) ){
            $errors['phone'] = "Please enter a valid 10 digit phone number. Or leave this field blank and enter a valid email address.";
        }else
            $safe_params['phone'] = sanatize_phone( $allowed_params['phone'] );  
    }

    if( validate_presence( $allowed_params['email'] ) ){
        
        ++$contactTracker;
        if( ! validate_email( $allowed_params['email'] ) ){
            $errors['email'] = "Please enter a valid email address. Or leave this field
                blank and enter a valid 10 digit phone number";
        }else
            $safe_params['email'] = strip_tags( strtolower( trim( $allowed_params['email'] ) ) );

    }

    if( ! $contactTracker )
        $errors['contact'] = "Please enter either a valid 10 digit phone number or a valid email address or both.";


        //Message

    if( ! validate_presence( $allowed_params['message'] ) )
        $errors['message'] = "Please enter a message.";
    else
        $safe_params['message'] = strip_tags( trim( $allowed_params['message'] ) );

    $safe_params['phone_pref'] = $allowed_params['phone_pref'];
    $safe_params['email_pref'] = $allowed_params['email_pref'];

    return_response();


?>