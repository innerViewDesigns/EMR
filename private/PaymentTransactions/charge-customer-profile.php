<?php
  
  require '/Users/Apple/Sites/therapyBusiness/private/vendor/autoload.php';

  use net\authorize\api\contract\v1 as AnetAPI;
  use net\authorize\api\controller as AnetController;
  define("AUTHORIZENET_LOG_FILE", "phplog");
  
  // Common setup for API credentials

  $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
  $merchantAuthentication->setName("59aLCmhw34");
  $merchantAuthentication->setTransactionKey("9y68M32ZP275pwZ7");
  $refId = 'ref' . time();
  
  $profileToCharge = new AnetAPI\CustomerProfilePaymentType();
  $profileToCharge->setCustomerProfileId("1500037235");
  
  $paymentProfile = new AnetAPI\PaymentProfileType();
  $paymentProfile->setPaymentProfileId("1500038655");
  $profileToCharge->setPaymentProfile($paymentProfile);
  
  $transactionRequestType = new AnetAPI\TransactionRequestType();
  $transactionRequestType->setTransactionType( "authCaptureTransaction"); 
  $transactionRequestType->setAmount(75.00);
  $transactionRequestType->setProfile($profileToCharge);
 
  $request = new AnetAPI\CreateTransactionRequest();
  $request->setMerchantAuthentication($merchantAuthentication);
  $request->setRefId( $refId );
  $request->setTransactionRequest( $transactionRequestType);
  $controller = new AnetController\CreateTransactionController($request);
  
  $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
 
  if ($response != null)
  {
    $tresponse = $response->getTransactionResponse();
    if (($tresponse != null) && ($tresponse->getResponseCode()=="1") )   
    {
      echo  "Charge Customer Profile APPROVED  :" . "\n";
      echo " Charge Customer Profile AUTH CODE : " . $tresponse->getAuthCode() . "\n";
      echo " Charge Customer Profile TRANS ID  : " . $tresponse->getTransId() . "\n";
    }
    elseif (($tresponse != null) && ($tresponse->getResponseCode()=="2") )
    {
      echo  "ERROR" . "\n";
      echo var_dump($response);
    }
    elseif (($tresponse != null) && ($tresponse->getResponseCode()=="4") )
    {
        echo  "ERROR: HELD FOR REVIEW:"  . "\n";
    }
    else
    {

      echo "none of the above happened\n";
      echo var_dump($response);
      echo "\n";

    }
  }
  else
  {
    echo "no response returned";
  }


?>