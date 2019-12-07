<?php
//require("fpdf.php");
//require("/Users/Apple/Sites/therapyBusiness/private/insurances.php");
// require("/Users/Apple/Sites/therapyBusiness/private/Services.php");
//require("/Users/Apple/Sites/therapyBusiness/private/otherPayments.php");
//require("/Users/Apple/Sites/therapyBusiness/private/patient.php");
//require("/Users/Apple/Sites/therapyBusiness/private/note.php");


class PDF extends FPDF
{
    public $localItems      = array();

    protected $leftMargin   = 10;
    protected $tableRowLeft = 0;
    protected $lineRowLeft  = 0;
    protected $footerWidth  = 130;
    protected $lineRowRight = 0;
    protected $footerText   = "You may pay by check, charge, or cash. If you pay by check, please make it payable to: Michael Lembaris, Psy.D., Psychologist, inc.\n\nMy Federal Tax ID # is: 81-1857287.\n\nThank you in advance for your prompt attention to this invoice. Please contact me if you have any questions. Thank you... Dr. Lembaris";
    
    
    private $docType = 'invoice'; //options are 'invoice' , 'medical_record' , 'payment_record'
    protected $paymentRecordTitle = 'Record of Payments since January 1st';
    protected $notesOnly    = false;
    protected $notesPtId    = 238;

    protected $customDate   = " 2019-12-01";
    protected $localBalance = false;
    protected $displayPreviousBalance = false;
    protected $displayCourtesy = false;

    protected $insurancePayments = 0; 
    

    function myConstruct($serviceIds = array(), $paymentIds=array(), $patientId='')
    {   

        /*

            You have a list of service_ids and payment_ids. Here's what to do next:

            1. Get all insurance claims for this patient. Include DOS, CPT, and whether insurance was used from the services table.
               Make sure it's ordered by DOS descending. All of the columns will be: 

                id_insurance_claim,
                service_id_insurance_claim,
                allowable_insurance_amount,
                expected_copay_amount,
                recieved_insurance_amount,
                recieved_copay_amount,
                recieved_insurance_amount,
                services.dos,
                services.cpt_code,
                services.insurance_used


            2. Do some calculations and keep these in a different array.
                The sum-total of all expected co-pays - expected_copays
                The sum-total of all recieved co-pays - recieved_copays
                The sum-total of all allowed_amounts  - allowed_amounts
                The sum-total of all recieved insurances - recieved_insurances

            6. Get all the other_payments for this patient and calculate the sum-total of all payments.
               Then put that value in the same array as above ($sumTotals)

            7. Combine the other_payments array with claims array making sure that it's ordered by descending date

            8. Get the selected services and payments out of the claims array and put them into a new array ($localClaims)

            9. Get the last date from the descending $localClaims list, and use that to loop back through the $sumTotals
               array to calculate a "previous balance"

            9. Then calculate the local totals from the $localClaims array
                5a. Sum of allowed_amount
                5b. Sum of co-pay_amounts (local_balance)
            
            -------------------------------------------------------------------

            At bottom you're interested in:
                local_balance = sum of expected_copays from local date range
                overall_balance = (sum of all expected_copays) - (sum of all other_payments) - (sum of all recieved_copays)
                previous_balance = (sum of all expected_copays before a certain date) - (sum of all other_payments before a certain date) - (sum of all recieved_copays before a certain date)


        */


        $allInsurances = $this->get_all_insurances($patientId);

        $allDirectPayments = $this->get_all_direct_payments($patientId);
        
        /*
            sumTotals will now be structured like this:

                'expected_copays' => $expected_copays, 
                'recieved_copays' => $recieved_copays, 
                'allowed_amounts' => $allowed_amounts,
                'recieved_insurances' => $recieved_insurances,
                'direct_payments' => $direct_payments)

            A 'dos' key is added to the $allDirectPayments array to help with the usort function later

        */

        $combinedClaimsAndPayments = array_merge($allInsurances, $allDirectPayments);

        $totals['sum_totals'] = $this->calculate_totals($combinedClaimsAndPayments);
        $sumTotals = $totals['sum_totals'];

        $balances['overall_balance'] = $this->calculate_balance($sumTotals['recieved_copays'], $sumTotals['direct_payments'], $sumTotals['expected_copays']);

        $combinedClaimsAndPayments = $this->combine_claims_and_payments($combinedClaimsAndPayments);

        /*
            This leaves $combinedClaimsAndPayments intact. It includes everything, even beyond the submitted date
            range at this point.
        */

        $localItems = $this->pull_local_items($combinedClaimsAndPayments, $serviceIds, $paymentIds);
        $totals['local_totals'] = $this->calculate_totals($localItems);
        $localTotals = $totals['local_totals'];

        /*
            $totals['previous_totals'] will now be structured like this:

                'expected_copays' => $expected_copays, 
                'recieved_copays' => $recieved_copays, 
                'allowed_amounts' => $allowed_amounts,
                'recieved_insurances' => $recieved_insurances,
                'direct_payments' => $direct_payments)

            $combinedClaimsAndPayments now only goes up to and not including or overlapping the submitted items

        */

        $previousClaimsandPayments = $this->create_previous_claims_list($combinedClaimsAndPayments, $localItems);
        $totals['previous_totals'] = $this->calculate_totals($previousClaimsandPayments);
        $previousTotals = $totals['previous_totals'];


        /*
            $balances['previous_balance'] what's owed or credited upto the first date in the supplied
                                          service list

            $balances['overall_balance'] what's owed or credited inclusive of all entered services and payments
                                         not just the ones sent by the user to this function.

            $balances['local_balance'] what's owed or credited upto and including what's been sent to this function
        */


        $balances['previous_balance'] = $this->calculate_balance($previousTotals['recieved_copays'], $previousTotals['direct_payments'], $previousTotals['expected_copays']);

        $balances['local_balance'] = $this->calculate_balance(
                                                                ( $previousTotals['recieved_copays'] + $localTotals['recieved_copays'] ),
                                                                ( $previousTotals['direct_payments'] + $localTotals['direct_payments'] ),
                                                                ( $previousTotals['expected_copays'] + $localTotals['expected_copays'] )
                                                             );


        $localItems = $this->PrepareData($localItems);
        $this->patient = $this->setPatientInfo($patientId);

        return array($localItems, $balances);
   



    }


    private function setPatientInfo($patientId)
    {
        $patientObject = New Patient(array('user_param' => $patientId));
        $patient = $patientObject->getPersonalInfo();
        return $patient; 
    }

    private function create_previous_claims_list($combinedClaimsAndPayments, $localItems)
    {   
        /*
            To get the previous_balance you need to separate out the $localItems from the combinedItems.

            Start by checking to see if the final item in the $localItems array is a claim or a payment.
            Then get the associated ID and log the appropriate array key. Then slice the combined array. 

            Also grap the first item to detect if a service or payment gets left out during "display local
            balance" option. 
        */

        $fullCount = count($combinedClaimsAndPayments);
        $localCount = count($localItems);
        $previousCount = 0;    


        if(array_key_exists('service_id_insurance_claim', $localItems[count($localItems)-1]))
        {   

            $claimCutOff = $localItems[count($localItems)-1]['service_id_insurance_claim'];
            $needle = 'service_id_insurance_claim';

        }else
        {
            $paymentCutOff = $localItems[count($localItems)-1]['id_other_payments'];
            $needle = 'id_other_payments';
        }


        $cutOff = isset($claimCutOff) ? $claimCutOff : $paymentCutOff;

        foreach($combinedClaimsAndPayments as $key => $value)
        {   
            if(array_key_exists($needle, $value) && $value[$needle] == $cutOff)
            {
                $index = $key + 1;
                break;
            }

        }



        $previousClaimsandPayments = array_slice($combinedClaimsAndPayments, $index);

        $previousCount = count($previousClaimsandPayments);
        
        if($previousCount + $localCount != $fullCount)
        {
            /*
                There was once service or payment missing.
            */
            $dif = $fullCount - ($previousCount + $localCount);

            echo "Ops. You missed " . $dif . " items. Did you mean to?";

        }


        return $previousClaimsandPayments;

    }

    private function calculate_totals(&$list)
    {
        $expected_copays = 0;
        $recieved_copays = 0;
        $allowed_amounts = 0;
        $recieved_insurances = 0;
        $direct_payments = 0;

        foreach($list as &$value)
        {
            if(array_key_exists('service_id_insurance_claim', $value))
            {
                $expected_copays += $value['expected_copay_amount'];
                $recieved_copays += $value['recieved_copay_amount'];
                $allowed_amounts += $value['allowable_insurance_amount'];
                $recieved_insurances += $value['recieved_insurance_amount'];

            }else
            {

                if( !array_key_exists('dos', $value) )
                {
                    /*
                        Add a 'dos' key to help the usort function later on
                    */

                    $value['dos'] = $value['date_recieved'];
                    $value['dos'] = $value['dos'] . " 00:00:00";

                }

                $direct_payments += $value['amount'];
            }
        }

        $totals = array('expected_copays' => $expected_copays, 
                            'recieved_copays' => $recieved_copays, 
                            'allowed_amounts' => $allowed_amounts,
                            'recieved_insurances' => $recieved_insurances,
                            'direct_payments' => $direct_payments);



        return $totals;
    }

    private function pull_local_items($combinedClaimsAndPayments, $serviceIds, $paymentIds)
    {
        $localItems = [];

        foreach($combinedClaimsAndPayments as $value)
        {
            if(array_key_exists('service_id_insurance_claim', $value) && in_array($value['service_id_insurance_claim'], $serviceIds))
            {
                array_push($localItems, $value);

            }elseif(array_key_exists('id_other_payments', $value) && in_array($value['id_other_payments'], $paymentIds))
            {
                array_push($localItems, $value);
            }
        }

        return $localItems;

    }


    private function get_all_insurances($patientId)
    {
        $insurancesObject = New insurances();
        $insurancesObject->setAllForPatientIncludeDOS($patientId);
        $insurances = $insurancesObject->getClaims();
        return $insurances;
    }

    private function get_all_direct_payments($patientId)
    {
        $directPaymentsObject = New otherPayments($patientId);
        $directPaymentsObject->setPayments();
        $directPayments = $directPaymentsObject->getPayments();
        return $directPayments;

    }

    private function combine_claims_and_payments($list)
    {

        function cmp($a, $b)
        {   

            $aa = DateTime::createFromFormat('Y-m-d H:i:s', $a['dos']);
            $aa = $aa->format('Y-m-d H:i:s');

            $bb = DateTime::createFromFormat('Y-m-d H:i:s', $b['dos']);
            $bb = $bb->format('Y-m-d H:i:s');

            $aa = strtotime($aa);
            $bb = strtotime($bb);

            if($aa == $bb)
            {
                return 0;
            }

            return ($aa < $bb) ? 1 : -1;
        }

        usort($list, 'cmp');

        return $list;
    }

    public function getName()
    {
        return $this->patient['last_name'] . "_" . $this->patient['first_name'];
    }


    private function calculate_balance($moneyIn1, $moneyIn2, $moneyOwed)
    {   
        /*
            Negative values mean that more is owed than has been paid.
            Positive values connote an account credit or date_range surplus
        */

        $balance = ($moneyIn1 + $moneyIn2) - $moneyOwed;
        return $balance;

    }


    function PrepareData($localItems)
    {

        /*
            You're getting ready to print out the invoice table. There are a couple of cases to account
            and prepare for. 

            1. If a service is 'in-network,' then the charge to be listed is the co-pay or deductible amount.
               The balance to be shown, then, is the previous_balance + any additional payments through the
               submitted date range - the sum_of_expected_copays in the submitted date range
               

            2. If a service is not 'in-network,' then the charge to be listed is my fee. The balance to be 
               shown should still be the sum of expected co-pays, but a "curtosy adjust" should be listed 
               showing the difference between the sum_of_standard_fees and the sum_of_expected_copays. The
               balance to be shown is the same as above:
                    (previous_balance + any additional payments through the submitted date range) - sum_of_expected_copays in the submitted date range

            

            1. Loop through the given services and payments.

            2. Start by striping the time portion of the DOS value.

            3. Then replace the cpt_code with a language description of the service

            4. Then create a new key for this array to point to the associated standard_fee for the service.

            5. If this is a service, create a 2nd additional key for displayed_charge and set that equal to
               the standard_fee if in_network == 0 and to the expected_copay_amount if in_network == 1

            6. If the former, subtract the expected_copay_amount from the standard_fee and keep track of that difference


        */

        $dif = $courtesy_adjust = 0;


        foreach($localItems as &$value)
        {

            $value['dos'] = preg_replace('/\s\d{2}:\d{2}:\d{2}/', '', $value['dos']);

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

                    case 90839:
                        $value['cpt_code'] = 'Crisis Intervention - 45mins';
                        $value['standard_fee'] = 150.00;
                        break;

                    case 90837:
                        $value['cpt_code'] = 'Psychotherapy - 60mins';
                        $value['standard_fee'] = 175.00;
                        break; 

                    case 11111:
                        $value['cpt_code'] = 'Report writing - 2hrs';
                        $value['standard_fee'] = 300.00;
                        break;

                    case 'late cancel':
                        $value['cpt_code'] = 'Late Cancel';
                        $value['displayed_charge'] = $value['allowable_insurance_amount'];
                        break;    

                    case 'reservation fee':
                        $value['cpt_code'] = 'Reservation Fee';
                        $value['standard_fee'] = 150.00;
                        break;                
                }

                if( isset($value['in_network']) && $value['in_network'] == '1' )
                {
                    $value['displayed_charge'] = $value['expected_copay_amount'];
                    $value['add_on'] = $value['expected_copay_amount'] == 88 ? ' deductible' : ' co-pay';

                }else
                {

                    $value['displayed_charge'] = $value['standard_fee'];
                    $courtesy_adjust += $value['standard_fee'] - $value['expected_copay_amount'];

                }


            }

        }

        if($courtesy_adjust)
        {
            $localItems['courtesy_adjust'] = $courtesy_adjust;
        }


        return $localItems;
        
        

    }

    function prepare($data)
    {

        $localItems = $data[0];
        $balances = $data[1];

        /*
            Definitions
        */

        $this->tableRowLeft = ($this->GetPageWidth() - 125) / 2;
        $this->lineRowLeft  = $this->tableRowLeft - 10;
        $this->footerWidth  = 130;
        $this->lineRowRight = $this->tableRowLeft + $this->footerWidth;



        /*
            Set the Font and page variables up
        */

        $this->AddFont('Cormorant','','CormorantGaramond-Light.php');
        $this->AddFont('CormorantBold','','CormorantGaramond-Bold.php');

        $this->AliasNbPages();
        $this->AddPage();

        $this->setMargins(15, 15, 15);

        /*
            Add the header, Title, and Name
        */

        $this->firstPageHeader();

        $this->addTitle();
        
        $this->addName();

        if( $this->docType != 'medical_record')
        {
            $this->addTable($localItems, $balances);
            $this->addFooter();

        }else
        {

            $this->addNotes();

        }
        


    }

    function addNotes()
    {
        $patient_id = $this->notesPtId;
        $patient = New patient($patient_id);

        $patient->setServices();
        $patient->setOtherNotes();
        $servicesAndNotes = $patient->combineOtherNotesAndServices($patient->getOtherNotes(), $patient->getServices());


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
        $this->Image('/Users/Lembaris/sites/therapyBusiness/public_html/media/ML-Garamond-Psychoanalysis(full).png',null,null,75);
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

        
        switch($this->docType)
        {
            case 'invoice':
                $title = 'Invoice for Professional Services';
                break;

            case 'payment_record':
                $title = $this->paymentRecordTitle;
                break;

            case 'medical_record':
                $title = 'Confidential Medical Record';
                break;

        }

        /*
            Coming from firstPageHeader drop down some space. 
        */

        $this->Ln(25);
        $this->SetFont('CormorantBold','',15);

        /*
            figure out how to center the text:
        */

        $strWidth = $this->GetStringWidth($title);
        $this->SetX( ($this->GetPageWidth() / 2) - ($strWidth / 2));


        $this->Cell( $strWidth, 8, $title, 'B', 2,'C');        

    }

    function addName()
    {

        if($this->docType != 'invoice') 
        {
            $lable = "Printed on: ";
        }else
        {
            $label = 'Invoice Date: ';
        }
        

        /*
            Coming from addTitle drop down some space. 
        */

        $this->Ln(10);
        $this->SetX($this->leftMargin);

        $this->SetFont('CormorantBold','',12);
        $strWidth = $this->GetStringWidth('Patient Name: ');
        $this->Cell( $strWidth, 10, 'Patient Name: ', 0, 0,'L'); 

        $this->SetFont('Cormorant','',12);
        $this->Cell( $strWidth, 10, $this->patient['first_name']." ".$this->patient['last_name'], 0, 0, 'L');

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

    function addTable($localItems, $balances)
    {
        $dif          = 0;
        $addOn        = '';
        $paymentTotal = 0;
        
        $this->Ln(15);

        $this->SetFont('CormorantBold','',12);
        
        /*
            Center the table header
        */

        $tableHeader = 'Description of Services, Dates of Service, Associated Fees or Account Activity';
        
        $strWidth = $this->GetStringWidth($tableHeader);
        $this->SetX( (($this->GetPageWidth() - $strWidth) / 2) - 23 );

        /*
            Set the table title
        */

        $this->Cell(0 , 13, $tableHeader, 0, 2,'C');    
        $this->Ln(3);
        $this->SetFont('Cormorant','',12);
        


        foreach($localItems as $row)
        {   

            
            $this->SetX($this->tableRowLeft);
            $this->Cell( 20, 10, $row['dos'], 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            
            
            
            if( !array_key_exists('id_other_payments', $row) )
            {
                
                $this->Cell( 60, 10, $row['cpt_code'].$addOn, 0, 0,'L');
                $this->SetX( $this->GetX() + 10);
                $this->Cell( 25, 10, sprintf("%.2f", $row['displayed_charge']), 0, 1,'R');
                

            }else
            {   
                //this is actually a payment with 'date_recieved'

                $this->Cell( 60, 10, "Payment - Thank You.", 0, 0,'L');
                $this->SetX( $this->GetX() + 10);
                $this->Cell( 25, 10, "-".sprintf("%.2f", $row['amount']), 0, 1,'R');

            }
            


        }

        if( $this->displayPreviousBalance )
        {

            $label = $balances['previous_balance'] > 0 ? "Previous Balance (credit)" : "Previous Balance (owed)";

            $this->SetX($this->tableRowLeft);
            $this->Cell( 20, 10, "", 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 60, 10, $label, 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 25, 10, sprintf("%.2f", $balances['previous_balance']), 0, 1,'R');   
                     

        }

        
        if(isset($localItems['courtesy_adjust']) && $this->displayCourtesy)
        {
            
            $this->SetX($this->tableRowLeft);
            $this->Cell( 20, 10, "", 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 60, 10, 'Courtesy Adjust', 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 25, 10, "-".sprintf("%.2f", $localItems['courtesy_adjust']), 0, 1,'R');

        }


        if( $this->insurancePayments )
        {   
            /*
                There were some out-of-network claims. List how much you got from the insurance so far.
            */

            $this->SetX($this->tableRowLeft);
            $this->Cell( 20, 10, "", 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 60, 10, 'Insurance Payments (expected)', 0, 0,'L');
            $this->SetX( $this->GetX() + 10);
            $this->Cell( 25, 10, "-".sprintf("%.2f", $this->insurancePayments), 0, 1,'R');   
                     

        }

        //Draw a line. 
        $this->Line($this->lineRowLeft, $this->GetY() + 2, $this->lineRowRight, $this->GetY() + 2);


        //move down 2 units to account for the border around this row
        $this->SetY($this->GetY() + 3);

        //put x to far right of table, then subtract the width of 'total due: ' + the last cell width + 
        //the space to the left of the last cell width.
        $this->SetX(( $this->tableRowLeft + 100) );


        
        if($this->docType == 'invoice')
        {
            //if you're looking for the sum of just the services included in this invoice, grab that value
            //otherwise, grab the overall balance of all services

            $balance = $this->localBalance ?  $balances['local_balance'] : $balances['overall_balance'];
            
            //Subtract insurancePayments if it's set.
            if($this->insurancePayments)
            {
                $balance -= $this->insurancePayments;
            }
            

            $lable = $balance > 0 ? "Account Credit: " : "Total Due: ";

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

        switch($this->docType)
        {
            case 'medical_record':
                return "_MedicalRecord-";
                break;

            case 'payment_record':
                return "_PaymentRecord-";
                break;

            case 'invoice':
                return "_Invoice-";
                break;

            default:
                return "Something_Went_Wrong";
                break;
        }
        

    }



}

//$pdf = New PDF();
//$pdf->getData();
//$pdf->addTable();
//$pdf->prepare();



?>