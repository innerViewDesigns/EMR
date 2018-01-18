<?php
//require("fpdf.php");
//require("/Users/Apple/Sites/therapyBusiness/private/insurances.php");
// require("/Users/Apple/Sites/therapyBusiness/private/Services.php");
//require("/Users/Apple/Sites/therapyBusiness/private/otherPayments.php");
//require("/Users/Apple/Sites/therapyBusiness/private/patient.php");
//require("/Users/Apple/Sites/therapyBusiness/private/note.php");


class PDF extends FPDF
{
    public $claims          = array();
    public $pt              = array();
    protected $leftMargin   = 10;
    protected $tableRowLeft = 0;
    protected $lineRowLeft  = 0;
    protected $footerWidth  = 130;
    protected $lineRowRight = 0;
    protected $footerText   = "You may pay by check, charge, or cash. If you pay by check, please make it payable to: Michael Lembaris, Psy.D., Psychologist, inc.\n\nMy Federal Tax ID # is: 81-1857287.\n\nThank you in advance for your prompt attention to this invoice. Please contact me if you have any questions. Thank you... Dr. Lembaris";
    protected $localBalance = false;
    protected $paymentTotal = 0;
    protected $paymentRecord = false; //Are you looking to display the total number of payments made during this date range?
    protected $customDate   = false;//" 2018-01-01";
    protected $addOn        = ' (co-pay)'; 
    protected $notesOnly    = false;
    protected $insurancePayments = 0;
    protected $corrections  = false;



    function getData($inServices = array(), $inPayments=array(), $dates = array())
    {   
        $claims      = array();
        $outServices = array();
        $outPayments = array();

        if(!empty($inServices))
        {

            //get the list
            $insurancesObj = New insurances();
            $serviceObj    = New services();

            $claims       = $insurancesObj->setSomeByServiceId($inServices);
            $outServices  = $serviceObj->getSomeByServiceId($inServices);


            //When intantiated, all other_payments for this patient that were passed in are retrieved. 
            $paymentsObj   = New otherPayments($claims[0]['patient_id_insurance_claim']);
            $paymentsObj->getSomeById($inPayments);

            //When instantiated, all info for this pt is set.
            $patientObj    = New patient($claims[0]['patient_id_insurance_claim']);            
            
            $outPayments  = $paymentsObj->getPayments();
            $patientObj->setBalance();

            //echo print_r($outServices, true);
            //echo print_r($claims, true);

            $this->claims = $insurancesObj->pairClaimsAndPayments($claims, $outServices, $outPayments, true);

            $this->pt['pt_info']    = $patientObj->getPersonalInfo();
            $this->pt['pt_info']['balance'] = $patientObj->getBalance();
            //$this->pt['pt_info']['preBalance'] = $this->setPreBalance();

            //echo print_r($this->claims, true);
            //echo print_r($this->pt, true);

            $file = __DIR__ . '/errors.txt';
            file_put_contents($file, print_r($this->pt, true), FILE_APPEND);

            $this->claims = $this->PrepareData($this->claims);
        
        }else if(!empty($dates))
        {
            if(array_key_exists('startDate', $dates) && !array_key_exists('endDate', $dates))
            {

                //get the list based on this month

            }else if(array_key_exists('startDate', $dates) && array_key_exists('endDate', $dates))
            {

                //get the list based on this date range

            }


        }else
        {

            return "Error Message. getData didn't have any data.";

        }

    }

    public function getName()
    {

        return $this->pt['pt_info']['last_name']."_".$this->pt['pt_info']["first_name"];

    }

    function PrepareData($claims)
    {
        $localTotalExpected=0;
        $localTotalAllowed=0;

        foreach($claims as &$value)
        {

            //if this item represents a service, dos will be a string. Otherwise, it will be a dateTime object.
            if(gettype($value['dos']) == 'string')
            {
                $value['dos'] = preg_replace('/\s\d{2}:\d{2}:\d{2}/', '', $value['dos']);
                $localTotalExpected += $value['expected_copay_amount'];
                $localTotalAllowed  += $value['allowable_insurance_amount'];

            }else
            {
                $value['dos'] = $value['dos']->format("Y-m-d");
            }


            if(array_key_exists('cpt_code', $value))
            {
                switch($value['cpt_code'])
                {

                    case 90834:
                        $value['cpt_code'] = 'Psychotherapy - 45mins';
                        $value['standard_fee'] = 150.00;
                        break;

                    case 90791:
                        $value['cpt_code'] = 'Psychotherapy Intake - 60mins';
                        $value['standard_fee'] = 200.00;
                        break;

                    case 90847:
                        $value['cpt_code'] = 'Family Therapy w/ Patient - 45mins';
                        $value['standard_fee'] = 150.00;
                        break;

                    case 90846:
                        $value['cpt_code'] = 'Family Therapy w/o Patient - 45mins';
                        $value['standard_fee'] = 150.00;
                        break;   

                    case 90837:
                        $value['cpt_code'] = 'Psychotherapy - 60mins';
                        $value['standard_fee'] = 175.00;
                        break; 

                    case 'late cancel':
                        $value['cpt_code'] = 'Late Cancel';
                        $value['standard_fee'] = 150.00;
                        break;                        
                }



            }

        }


        $this->pt['pt_info']['local_balance_expected'] = 0 - $localTotalExpected;
        $this->pt['pt_info']['local_balance_allowable'] = 0 - $localTotalAllowed;
        return $claims;
        
        

    }

    function prepare($args=[])
    {

        //////////////
        //Definitions
        //////////////

        $this->tableRowLeft = ($this->GetPageWidth() - 125) / 2;
        $this->lineRowLeft  = $this->tableRowLeft - 10;
        $this->footerWidth  = 130;
        $this->lineRowRight = $this->tableRowLeft + $this->footerWidth;



        //$pdf->AddFont('AppleGaramondLight','', 'AppleGaramond-Light.php');
        $this->AddFont('Cormorant','','CormorantGaramond-Light.php');
        $this->AddFont('CormorantBold','','CormorantGaramond-Bold.php');

        $this->AliasNbPages();
        $this->AddPage();

        $this->setMargins(15, 15, 15);

        //You need these lines when you're getting the notes....
        //$patientObj = New patient(238);  
        //$this->pt['pt_info']  = $patientObj->getPersonalInfo();

        $this->firstPageHeader();
        $this->addTitle();
        $this->addName();

        if(!$this->notesOnly)
        {
            $this->addTable();
            $this->addFooter();

        }else
        {

            $this->addNotes();

        }
        


    }

    function addNotes()
    {
        $patient_id = 238;
        $patient = New patient($patient_id);

        $patient->setServices();
        $patient->setOtherNotes();
        $servicesAndNotes = $patient->combineOtherNotesAndServices($patient->getOtherNotes(), $patient->getServices());

        //echo "at least you made it to the addNotes funciton.";
        //echo print_r($notes, true);

        ///////////
        //Add title
        ///////////

        $this->Ln(25);
        $this->SetFont('CormorantBold','',15);

        //figure out how to center the text:
        $strWidth = $this->GetStringWidth('Confidential Medical Record');
        $this->SetX( ($this->GetPageWidth() / 2) - ($strWidth / 2));

        //print the title
        $this->Cell( $strWidth, 8, 'Confidential Medical Record', 'B', 2,'C');  
        $this->Ln(15);
        $this->SetFont('Cormorant','',12);

        foreach($servicesAndNotes as $key => $value)
        {
            if(array_key_exists('note', $value))
            {

                $this->write(5, $value['note']);

            }else
            {

                $noteObj = New note(array('service_id' => $value['id_services']));
                $noteObj->setNoteByServiceId();
                //echo print_r($noteObj->getNote());
                $this->write(5, $noteObj->getNote()['note']);

            }
            
                $this->Ln(15);
                $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
                $this->Ln(2);
                $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
                $this->Ln(15);
            
        }

    }

    // Page header
    function firstPageHeader()
    {
        // Logo
        $this->Image('/Users/Apple/sites/therapyBusiness/public_html/media/ML-Garamond-Psychoanalysis(full).png',null,null,75);
        $this->SetFont('Cormorant','',12);


        $this->SetXY(-50,12);

        // Title
        $this->Cell(0,0,'3252 Holiday CT., STE 102',0,2,'R');
        
        // Line break
        $this->Ln(4);
        $this->Cell(0,0,'La Jolla, CA 92037',0,2,'R');

        $this->Ln(7);
        $this->Cell(0,0,'(p) 619-887-4068',0,2,'R');

        $this->Ln(5);
        $this->Cell(0,0,'(f) 866-687-9706',0,2,'R');

        $this->Ln(7);
        $this->Cell(0,0,'lembarispsyd@gmail.com',0,2,'R');

    }

    function addTitle()
    {
        $label = $this->paymentRecord ? "Record of Payments in 2017" : 'Invoice for professional Services';

        //coming from firstPageHeader drop down some space. 
        $this->Ln(25);
        $this->SetFont('CormorantBold','',15);

        //figure out how to center the text:
        $strWidth = $this->GetStringWidth($label);
        $this->SetX( ($this->GetPageWidth() / 2) - ($strWidth / 2));


        $this->Cell( $strWidth, 8, $label, 'B', 2,'C');        

    }

    function addName()
    {

        $label = $this->paymentRecord ? "Printed on: " : 'Invoice Date: ';

        //coming from addTitle drop down some space. 
        $this->Ln(10);
        $this->SetX($this->leftMargin);

        $this->SetFont('CormorantBold','',12);
        $strWidth = $this->GetStringWidth('Patient Name: ');
        $this->Cell( $strWidth, 10, 'Patient Name: ', 0, 0,'L'); 

        $this->SetFont('Cormorant','',12);
        $this->Cell( $strWidth, 10, $this->pt['pt_info']['first_name']." ".$this->pt['pt_info']['last_name'], 0, 0, 'L');

        $this->Ln(7);
        $this->SetX($this->leftMargin);

        $this->SetFont('CormorantBold','',12);
        $strWidth = $this->GetStringWidth($label);
        $this->Cell( $strWidth, 10, $label, 0, 0,'L');


        $date = $this->customDate ? $this->customDate : date("Y-m-d");
        $this->SetFont('Cormorant','',12);
        $this->SetX($this->GetX() + 2);
        $this->Cell( 0, 10, $date, 0, 0,'L'); 


    }

    function addTable()
    {
        $dif          = 0;
        $addOn        = '';
        $paymentTotal = 0;
        
        $this->Ln(15);

        $this->SetFont('CormorantBold','',12);
        
        //Center the table title
        $strWidth = $this->GetStringWidth('Description of Services, Dates of Service, Associated Fees or Account Activity');
        $this->SetX( (($this->GetPageWidth() - $strWidth) / 2) - 23 );

        //Set the table title
        $this->Cell(0 , 13, 'Description of Services, Dates of Service, Associated Fees or Account Activity', 0, 2,'C');    

        $this->Ln(3);
        
        $this->SetFont('Cormorant','',12);
        

        /*      
        $file = __DIR__ . '/feedback.txt';
        file_put_contents($file, print_r($this->claims, true), FILE_APPEND);
        */

        //Create table
        foreach($this->claims as $row)
        {   

            /*
                Check to see if this was a service or a payment. If it was a service
                collect the data you need and set the appropriate variables
            */

            if(array_key_exists('insurance_used', $row))
            {   

                /*
                    If you're trying to print a payment record. Skip the claims.
                */

                if($this->paymentRecord)
                {
                    continue;
                }

                if( $row['insurance_used'] )
                {

                    if( $row['in_network'] )
                    {

                        /*
                            Insurance was used and you are in-network. Lable this a "co-pay" or 
                            "co-insurance" and show the expected copay amount.

                            You've added code here to chage the add on depending on if this patient is
                            working toward their deductible

                        */
                        if($row['expected_copay_amount'] == 88)
                        {
                            $addOn = ' (deductible)';

                        }else
                        {
                            $addOn = $this->addOn;
                        }
                        
                        $chargeAmount = 'expected_copay_amount'; 

                    }else
                    {
                        if(!isset($insurancePayments))
                        {
                           $insurancePayments = 0;
                        }

                        /*
                            Insurance was used but you are out of network.

                            Don't label this as a "co-pay" or 
                            "co-insurance" and show the allowable amount for this session (what you've agreed
                            on with the patient), and keep track of the difference between the standard fee
                            and the allowable.

                        */

                        $addOn = "";
                        $chargeAmount = 'standard_fee'; 
                        $dif += $row['standard_fee'] - $row['allowable_insurance_amount'];
                        $insurancePayments += abs( $row['recieved_insurance_amount'] );

                    }

                }else
                {
                    /*
                        Insurance was not used 

                        Check to see whether this is a late cancel or whether
                        this claim just can't go to insurance. 

                    */
                    
                    


                    if (strpos(strtolower($row['cpt_code']), 'late') === 0)
                    {
                        /*
                            If this is a late cancel, use the co-pay amount and reset the
                            label
                        */

                        $chargeAmount = 'allowable_insurance_amount';
                        $addOn = "";

                        /*
                            If you are not charging for the late cancel. Show the courtesy adjust
                        */

                        if( $row['allowable_insurance_amount'] != $row['expected_copay_amount'] )
                        {
                            $dif += $row['allowable_insurance_amount'] - $row['expected_copay_amount'];
                        }
                        

                    }else
                    {
                        /*
                            This was a service, but the claim just can't go to insurance.
                            use the standard fee amount
                        */

                        $chargeAmount = 'standard_fee';
                        $dif += $row['standard_fee'] - $row['allowable_insurance_amount'];
                    }


                }


                    
            }  // end if( array_key_exists('insurance_used', $row) )

            
            $this->SetX($this->tableRowLeft);
            $this->Cell( 20, 10, $row['dos'], 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            
            
            
            if(!array_key_exists('id_other_payments', $row))
            {
                
                $this->Cell( 60, 10, $row['cpt_code'].$addOn, 0, 0,'L');
                $this->SetX( $this->GetX() + 10);
                $this->Cell( 25, 10, sprintf("%.2f", $row[$chargeAmount]), 0, 1,'R');
                

            }else
            {   
                //this is actually a payment with 'date_recieved'

                $this->Cell( 60, 10, "Payment - Thank You.", 0, 0,'L');
                $this->SetX( $this->GetX() + 10);
                $this->Cell( 25, 10, "-".sprintf("%.2f", $row['amount']), 0, 1,'R');

                //add the payment amount for use in case this is needed.

                $paymentTotal += abs( $row['amount'] );

            }
            


        }

        
        if(isset($dif) && $dif > 0)
        {
            
            $this->SetX($this->tableRowLeft);
            $this->Cell( 20, 10, "", 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 60, 10, 'Courtesy Adjust', 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 25, 10, "-".sprintf("%.2f", $dif), 0, 1,'R');

        }

        /*
            
            Custom Line Item:

        

        $temp = 205.00;
        $this->SetX($this->tableRowLeft);
        $this->Cell( 20, 10, "", 0, 0,'L');
        $this->SetX( $this->GetX() + 10);
        $this->Cell( 60, 10, 'Insurance Payments (recieved)', 0, 0,'L');
        $this->SetX( $this->GetX() + 10);
        $this->Cell( 25, 10, "-".sprintf("%.2f", $temp), 0, 1,'R');

        */

        if( isset($insurancePayments) )
        {

            $insurancePayments += $this->insurancePayments;
            $this->SetX($this->tableRowLeft);
            $this->Cell( 20, 10, "", 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 60, 10, 'Insurance Payments (recieved)', 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 25, 10, "-".sprintf("%.2f", $insurancePayments), 0, 1,'R');   
                     

        }

        if( $this->corrections)
        {
            $this->SetX($this->tableRowLeft);
            $this->Cell( 20, 10, "", 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 60, 10, 'Underbilled from last invoice', 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 25, 10, sprintf("%.2f", $this->corrections), 0, 1,'R');  

        }


        

        //Draw a line. 
        $this->Line($this->lineRowLeft, $this->GetY() + 2, $this->lineRowRight, $this->GetY() + 2);


        //move down 2 units to account for the border around this row
        $this->SetY($this->GetY() + 3);

        //put x to far right of table, then subtract the width of 'total due: ' + the last cell width + 
        //the space to the left of the last cell width.
        $this->SetX(( $this->tableRowLeft + 100) );


        
        
        //check to see if there is a balance due or an account credit. Then proceed accordingly
        //$balance = $this->pt['pt_info']['balance'] > 0 ? "Account Credit: " : "Total Due: ";
        
        if(!$this->paymentRecord)
        {
            //if you're looking for the sum of just the services included in this invoice, grab that value
            //otherwise, grab the overall balance of all services

            $balance = $this->localBalance ? $this->pt['pt_info']['local_balance_expected'] : $this->pt['pt_info']['balance'];
            
            //Subtract insurancePayments if it's set.
            if(isset($insurancePayments))
            {
                $balance += $insurancePayments;
            }

            if($this->corrections)
            {
                $balance -= $this->corrections;

            }
            

            $lable = $balance > 0 ? "Account Credit: " : "Total Due: ";

        }else
        {   
            $balance = $paymentTotal;
            $lable = "Total paid in 2017: ";
        }

        $this->Cell( 25, 10, "$".sprintf("%.2f", abs($balance)), 0, 0,'R');

        $strWidth = $this->GetStringWidth($lable);

        $this->SetX( $this->GetX() - 35 - $strWidth);
        $this->Cell( $strWidth, 10, $lable, 0, 1,'R');
        
    

        $this->Line($this->lineRowLeft, $this->GetY()+2, $this->lineRowRight, $this->GetY()+2);
        
    }

    function addFooter()
    {
        $this->Ln(3);
        $this->SetX($this->lineRowLeft);
        $this->MultiCell( $this->footerWidth + 10, 6, $this->footerText, 0, 'L', false );
        $this->Ln(3);

        if($this->footerText != "")
        {
            $this->Line($this->lineRowLeft, $this->GetY(), $this->lineRowRight, $this->GetY());
        }


    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Cormorant','',10);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }

    public function getInvoiceDate()
    {

        if($this->customDate)
            return trim($this->customDate);
        else
            return date("Y-m-d");


    }

    public function getLabel()
    {
        if($this->paymentRecord)
        {
            return "_PaymentRecord-";
        }else
        {
            return "_Invoice-";
        }

    }


}

//$pdf = New PDF();
//$pdf->getData();
//$pdf->addTable();
//$pdf->prepare();



?>