<?php
//require(__DIR__ . "/fpdf.php");
//require($_SERVER['DOCUMENT_ROOT']."/therapyBusiness/private/insurances.php");
//require($_SERVER['DOCUMENT_ROOT']."/therapyBusiness/private/Services.php");
//require($_SERVER['DOCUMENT_ROOT']."/therapyBusiness/private/otherPayments.php");
//require($_SERVER['DOCUMENT_ROOT']."/therapyBusiness/private/patient.php");


class PDF extends FPDF
{
    public $claims = array();
    public $pt     = array();


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


            //When intantiated, all other_payments for this patient are set
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


            //echo print_r($this->claims, true);
            //echo print_r($this->pt, true);
        
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

            //set the default

        }

    }

    function prepare($args=[])
    {
        //$pdf->getData($services, array());

        //$pdf->AddFont('AppleGaramondLight','', 'AppleGaramond-Light.php');
        $this->AddFont('Cormorant','','CormorantGaramond-Light.php');
        $this->AddFont('CormorantBold','','CormorantGaramond-Bold.php');

        $this->AliasNbPages();
        $this->AddPage();

        $this->setMargins(2, 2, 15);

        $this->firstPageHeader();
        $this->addTitle();
        $this->addName();

        echo "Testing the line of calls.".print_r($args, true);

    }

    // Page header
    function firstPageHeader()
    {
        // Logo
        $this->Image('/Users/Apple/sites/therapyBusiness/public_html/media/ML-Garamond-Psychoanalysis(full).png',null,null,75);
        $this->SetFont('Cormorant','',12);


        $this->SetXY(-50,12);

        // Title
        $this->Cell(0,0,'5405 Morehouse Dr. STE 120',0,2,'R');
        
        // Line break
        $this->Ln(4);
        $this->Cell(0,0,'San Diego, CA 92121',0,2,'R');

        $this->Ln(7);
        $this->Cell(0,0,'(p) 619-887-4068',0,2,'R');

        $this->Ln(5);
        $this->Cell(0,0,'(f) 866-687-9706',0,2,'R');

        $this->Ln(7);
        $this->Cell(0,0,'lembarispsyd@gmail.com',0,2,'R');

    }

    function addTitle()
    {
        //coming from firstPageHeader drop down some space. 
        $this->Ln(25);
        $this->SetFont('CormorantBold','',15);

        //figure out how to center the text:
        $strWidth = $this->GetStringWidth('Invoice for professional Services');
        $this->SetX( ($this->GetPageWidth() / 2) - ($strWidth / 2));


        $this->Cell( $strWidth, 13, 'Invoice for professional Services', 'B', 2,'C');        

    }

    function addName()
    {

        //coming from addTitle drop down some space. 
        $this->Ln(15);
        $this->SetX(0);

        $strWidth = $this->GetStringWidth('Patient Name: ');

        $this->Cell( $strWidth, 10, 'Patient Name: ', 0, 0,'L'); 

        $this->SetFont('Cormorant','',12);
        $this->Cell( 0, 10, 'John Doe', 0, 0, 'L');

        $this->Ln(7);
        $this->SetX(0);

        $this->SetFont('CormorantBold','',12);
        $strWidth = $this->GetStringWidth('Patient Name: ');

        $this->SetFont('Cormorant','',12);
        $this->Cell( $strWidth, 10, 'Invoice Date: ', 0, 0,'L'); 



    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Cormorant','',8);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }


}



?>