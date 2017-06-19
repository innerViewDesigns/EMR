<?php
require("fpdf.php");
//require($_SERVER['DOCUMENT_ROOT']."/therapyBusiness/private/insurances.php");
//require($_SERVER['DOCUMENT_ROOT']."/therapyBusiness/private/Services.php");
//require($_SERVER['DOCUMENT_ROOT']."/therapyBusiness/private/otherPayments.php");
require("/Users/Apple/Sites/therapyBusiness/private/patient.php");
require("/Users/Apple/Sites/therapyBusiness/private/note.php");


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

        //////////////
        //Definitions
        //////////////

        $this->tableRowLeft = ($this->GetPageWidth() - 125) / 2;
        $this->lineRowLeft  = $this->tableRowLeft - 10;
        $this->footerWidth  = 130;
        $this->lineRowRight = $this->tableRowLeft + $this->footerWidth;


        //$pdf->getData($services, array());

        //$pdf->AddFont('AppleGaramondLight','', 'AppleGaramond-Light.php');
        $this->AddFont('Cormorant','','CormorantGaramond-Light.php');
        $this->AddFont('CormorantBold','','CormorantGaramond-Bold.php');

        $this->AliasNbPages();
        $this->AddPage();

        $this->setMargins(15, 15, 15);

        $this->firstPageHeader();
        //$this->addTitle();
        //$this->addName();
        $this->addNotes();
        //echo "Testing the line of calls.".print_r($args, true);

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


        $this->Cell( $strWidth, 8, 'Invoice for professional Services', 'B', 2,'C');        

    }

    function addName()
    {

        //coming from addTitle drop down some space. 
        $this->Ln(15);
        $this->SetX($this->leftMargin);

        $strWidth = $this->GetStringWidth('Patient Name: ');
        $this->Cell( $strWidth, 10, 'Patient Name: ', 0, 0,'L'); 

        $this->SetFont('Cormorant','',12);
        $this->Cell( 0, 10, 'John Doe', 0, 0, 'L');

        $this->Ln(7);
        $this->SetX($this->leftMargin);

        $this->SetFont('CormorantBold','',12);
        $strWidth = $this->GetStringWidth('Invoice date: ');
        $this->Cell( $strWidth, 10, 'Invoice Date: ', 0, 0,'L'); 

        $this->addTable();

    }

    function addTable()
    {

        $this->Ln(15);

        $this->SetFont('CormorantBold','',12);
        
        //Center the table title
        $strWidth = $this->GetStringWidth('Description of Services, Dates of Service, Associated Fees or Account Activity');
        $this->SetX( (($this->GetPageWidth() - $strWidth) / 2) - 23 );

        //Set the table title
        $this->Cell(0 , 13, 'Description of Services, Dates of Service, Associated Fees or Account Activity', 0, 2,'C');    

        $this->Ln(3);
        
        $this->SetFont('Cormorant','',12);
        

        //Create table
        for($x=0; $x <= 35; $x++)
        {

            $this->SetX($this->tableRowLeft);
            $this->Cell( 20, 10, '05/05/2017', 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 60, 10, 'Psychotherapy - 45mins (co-pay)', 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 25, 10, '$30.00', 0, 1,'R');


        }

        //Draw a line. 
        $this->Line($this->lineRowLeft, $this->GetY() + 2, $this->lineRowRight, $this->GetY() + 2);


        //move down 2 units to account for the border around this row
        $this->SetY($this->GetY() + 2);

        //put x to far right of table, then subtract the width of 'total due: ' + the last cell width + 
        //the space to the left of the last cell width.
        $this->SetX(( $this->tableRowLeft + 100) );
        $this->Cell( 25, 10, '$100.00', 0, 0,'R');

        $this->SetX( $this->GetX() - 35 - $this->GetStringWidth('Total Due: '));
        $this->Cell( $this->GetStringWidth('Total Due: '), 10, 'Total Due:', 0, 1,'R');
        
    

        $this->Line($this->lineRowLeft, $this->GetY()+2, $this->lineRowRight, $this->GetY()+2);

        $this->addFooter();
    }

    function addFooter()
    {
        $this->Ln(3);
        $this->SetX($this->lineRowLeft);
        $this->MultiCell( $this->footerWidth + 10, 6, $this->footerText, 0, 'L', false );
        $this->Ln(3);

        $this->Line($this->lineRowLeft, $this->GetY(), $this->lineRowRight, $this->GetY());



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


}

$pdf = New PDF();
$pdf->prepare();
$pdf->Output('F', "/Users/Apple/Sites/therapyBusiness/private/fpdf181/test.pdf");


?>