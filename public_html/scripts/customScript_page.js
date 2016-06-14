var page = {

    patients        : {},

    infoType        : "", //set in the AddPopover function or in the addPatientsSubmitEventHandler function
    
    article         : "<article id='infoTable'></article>",

    activePatientId : "",

    dataKey         : "Database Results",

    basepath        : g.basePath,

    $table           : {}, //set in displayGetResults
    
    placeCleanArticle   : function(){

        if( $("article#infoTable").size() > 0 ){
            
            $("article#infoTable").html("");

        }else{

            if( $('article#patient_names').size > 0){
                $(this.article).insertAfter("article#patient_names");
            }else{
                $(this.article).appendTo('section');
            }
        
        }

    },

    insertTable     : function(){

        var tableShell = "<table data-table-name=" + this.infoType;
        tableShell = tableShell + "></table>";
        $(tableShell).appendTo('article#infoTable');

    },

    clearTableOnly : function(){

        if($('table').size() > 0){ $('table').remove(); }

    },

    displayFlash : function(jqXHR, ajaxStatus){ 

        if( $('div.flash').size() > 0 ){

            $('div.flash').each(function(){
                $(this).remove();
            });
        }

        var flash;

        console.log("Flash message. jqXHR: ");
        console.log(jqXHR);
        console.log("ajaxStatus: " + ajaxStatus);


            
        if( ajaxStatus === "success" ){

            var message =  "Everything looks good.",
                status = 'success';

            if( jqXHR.responseJSON !== undefined){

                if( jqXHR.responseJSON.hasOwnProperty('Database Status') ){

                    if( jqXHR.responseJSON['Database Status']['From Post'] !== undefined ){
                        
                        if( jqXHR.responseJSON['Database Status']['From Post']["Status"] !== undefined ){

                            if( jqXHR.responseJSON['Database Status']['From Post']["Status"].match(/error/ig) ){
                                var message = "Ops. Looks like a database error";
                                var status = 'error';
                            }
                        }
                    }
                }
            }

         }else{

            var message =  "Ops, looks like an ajax error (" + ajaxStatus + ")";
            status = 'error';

         }


        flash = ["<div class='flash ", status, "'><h2>", message, "</h2><ul>"];
            
        if( jqXHR.responseJSON !== undefined && jqXHR.responseJSON['Database Status'] !== undefined ){

            if( jqXHR.responseJSON['Database Status']['From Post'] !== undefined ){

                flash.push("From Post: ");

                for(var prop in jqXHR.responseJSON['Database Status']['From Post']){
                   
                    if( typeof jqXHR.responseJSON['Database Status']['From Post'][prop] !== 'object'){
                        flash.push('<li>', prop, ": ", jqXHR.responseJSON['Database Status']["From Post"][prop], "</li>");
                    }
                    else{
                        flash.push('<ul>', prop, ": ");
                        for(var prop_i in jqXHR.responseJSON['Database Status']['From Post'][prop]){

                            flash.push('<li>', prop_i, ": ", jqXHR.responseJSON['Database Status']["From Post"][prop][prop_i], "</li>");
                        }
                        flash.push('</ul>');
                        //console.log(JSON.stringify(jqXHR.responseJSON['Database Status']['From Post'][prop]));
                    }
                }
            }


        }else if(typeof jqXHR === "string"){

            flash.push("<li>", jqXHR, "</li>");

        }else if( jqXHR.responseText !== undefined){

            flash.push("<li>", jqXHR.responseText, "</li>");

        }

         flash.push("</ul></div>");

        $( flash.join('') ).prependTo('article#infoTable');
    
    },

    beautifyColumnNames : function(name){

        var newName = name;

        if( name.match(/first_name/) ){ newName = "First Name";}
        else if(name.match(/last_name/) ){newName = "Last Name";}

        return newName;

    }

};
    
page.reloadPatients = function(){

    $('article#patient_names').html("").load("public_html/pages/patient_names.php ul", function(){
        page.loadPatientNamesAndIds();    
    });
    

}

page.loadPatientNamesAndIds = function(){
    
    //this.patients = []; preparing here for Add patient function

    page.patientLinks = $("a[data-link-to='popover']");
    page.patientLinks.each(function(){

        page.patients[ $(this).attr("data-patient-id") ] = {

            'name' : $(this).text(),
            'el'   : $(this)

        };

    });

    this.loadPatientNamesAndIds.addPopovers();

};


page.loadPatientNamesAndIds.addPopovers = function(){

    page.patientLinks.each(function(){

        $(this).popover({

               html: true,
            content: function(){
               
                 var el = "<ul><li data-patient-id='" + $(this).attr('data-patient-id') + "'><a href=#>Services</a></li>";
                 el = el + "<li data-patient-id='" + $(this).attr('data-patient-id') + "'><a href=#>insurance_claim</a></li></ul>";
                
                 return el;
            
            },

            trigger: 'focus'

        }).on('shown.bs.popover', function(){

            page.activePatientId = $(this).attr('data-patient-id');

            $(this)
            .siblings()
            .find('div.popover-content')
            .find('li')
            .each(function(){
                var el = $(this);
                el.on('click', function(){

                    //highlight active link
                    page.loadPatientNamesAndIds.manageActiveLink.call(page.loadPatientNamesAndIds);
                    
                    //log the type of table being accessed
                    page.infoType = el.text();

                    //get the information
                    page.get();
                });
            });
        })
        .click(function(e){e.preventDefault();});

    });

};

page.loadPatientNamesAndIds.manageActiveLink = function(patientId){

    ////////////////////////////////
    //manage the active patient name
    ////////////////////////////////

    var id = patientId ? patientId : page.activePatientId;


    if( $('a[data-patient-id="' + id + '"' + ']' ).parent().siblings('.active').size() > 0){

        $('a[data-patient-id="' + id + '"' + ']' ).parent().siblings('.active').removeClass('active');
    }

    $('a[data-patient-id="' + id + '"' + ']').parent().addClass('active');
    

};

page.displayGetResults = function(data){

    //construct appropriate table
    //if the referrer was a post request then display a flash message

    page.insertTable();

    this.displayGetResults.buildHeaders.call(page.displayGetResults, data);

    $(this.displayGetResults.headerRow).appendTo('table');

    this.displayGetResults.buildDataCells.call(page.displayGetResults, data);

    $(this.displayGetResults.dataCells).appendTo('table');


    page.$table = $('table');
    page.createButtons();
    

};

page.displayGetResults.buildHeaders = function(data){

    //this = displayGetResults

    var count=0, dataKey=page.dataKey;
    var length = Object.keys(data[dataKey][0]).length;

    this.headerRow = "<tr><th data-column-name='undefined' id='number'>#</th>";

    for(prop in data[dataKey][0]){

        if( this.buildHeaders.skipCertainColumns(prop) )
            continue;

        prop  = "<th data-column-name='" + prop + "'>" + prop;
        
        if(count < (length-1)){
            
            this.headerRow = this.headerRow.concat(prop, "</th>");

        }else{

            this.headerRow = this.headerRow.concat(prop, "</th></tr>");

        }

        page.displayGetResults.formatHeaders.apply(page.displayGetResults);

    }


};

page.displayGetResults.buildHeaders.skipCertainColumns = function(prop){

    if(    prop.match(/^(id_insurance_claim)/gi)
        || prop.match(/(service_id_insurance_claim)/ig)
        || prop.match(">id_services<") 
        || prop.match(/invoice_id/ig) 
        || prop.match(/insurance_name/ig) )

        return true;

    else

        return false;    

};

page.displayGetResults.buildDataCells = function(data){

    var dataKey = page.dataKey;
    this.dataCells = [];

    ///////////////////////////////////////////////////////////////
    //record appropriate id (either for the service or the patient)
    ///////////////////////////////////////////////////////////////

    if(page.infoType.match(/services/i)){

        var keyForServiceId = "id_services";
        var keyForPatientId = "patient_id_services";

    }else if(page.infoType === 'insurance_claim'){

        var keyForServiceId = "service_id_insurance_claim";
        var keyForPatientId = "patient_id_insurance_claim";

    }


    for(var i=0; i<data[dataKey].length; i++){

        this.dataCells.push("<tr data-database-status='update' data-patient-id=" + data[dataKey][i][keyForPatientId] + " data-service-id=" + data[dataKey][i][keyForServiceId] + "><td>" + (i+1) + ".</td>");
       
        for(var prop in data[dataKey][i]){
    
            if( this.buildHeaders.skipCertainColumns(prop) ){
                continue;
            }
            
            this.dataCells.push("<td data-original-data="+ data[dataKey][i][prop] + ">" +  data[dataKey][i][prop] + "</td>");
        }
    }

    this.dataCells.push("</tr>");

    this.dataCells = this.dataCells.join("");



};

page.displayGetResults.formatHeaders = function(){

    this.headerRow = this.headerRow.replace(">patient_id_services<", ">P<");
    this.headerRow = this.headerRow.replace(">id_services<", ">S<");
    this.headerRow = this.headerRow.replace(">cpt_code<", ">cpt<");
    this.headerRow = this.headerRow.replace(">insurance_used<", ">Ins<");
    this.headerRow = this.headerRow.replace(/(>patient_id_insurance_claim<)/ig, ">P<");
    this.headerRow = this.headerRow.replace(">insurance_name<", ">Ins.<br>Name<");
    this.headerRow = this.headerRow.replace(">allowable_insurance_amount<", ">Allowed<");
    this.headerRow = this.headerRow.replace(">expected_copay_amount<", ">Expected<br>Copay<");
    this.headerRow = this.headerRow.replace(">recieved_insurance_amount<", ">Recieved<br>Insur.<");
    this.headerRow = this.headerRow.replace(">recieved_copay_amount<", ">Recieved<br>Copay<");


};

page.get = function(){

    var data = {
        
        table_name : page.infoType, //either 'services' , 'insurance_claim' , 'patients'
        patient_id  : page.activePatientId
    };


    $.ajax({

        type        : 'GET',
        url         : page.basepath + 'private/get.php', 
        data        : data, 
        dataType    : 'JSON',
        complete    : function(jqXHR, status){

            page.placeCleanArticle();

            if(status === "success"){
                
                page.displayGetResults(jqXHR.responseJSON);
          
            }else{

                page.displayFlash(jqXHR, status);
            }

        }
        
    });//ajax     


};

page.post = function(data){

    /*
    if( page.infoType === "Services" || page.infoType === "patients"){

        var url = "insert.php";

    }else if( page.infoType === "insurance_claim"){

        var url = "update.php";
    }
    */


    $.ajax({

        type        : 'POST',
        url         : page.basepath + 'private/post.php', 
        data        : data, 
        dataType    : 'JSON', 
        complete    : function( jqXHR, status){

            if(page.infoType != 'patients'){

                page.placeCleanArticle();

                if(status === "success"){
                    
                    page.displayGetResults(jqXHR.responseJSON);
                    page.displayFlash(jqXHR, status);
              
                }else{

                    page.displayFlash(jqXHR, status);
                }    

            }else{

                page.reloadPatients();
                page.displayFlash(jqXHR, status);

            }

        }

    });//ajax */
    

}

page.attachSubmitEventHandler = function(el){

    ///////////////////////////////////////
    //send the new services row to database
    ///////////////////////////////////////

    var $submitButton = el,
    data = {};


    $submitButton.click(function(){

        var data          = {},
            $insertFields = $(page.$table.find('tr[data-database-status="insert"]')),
            $updateFields = $(page.$table.find('tr[data-database-status="update"]'));

        data['patient_id']  = page.activePatientId;
        data['table_name']  = page.infoType.toLowerCase();

        

        if($insertFields.size() > 0){

            //collect the new fields
            console.log("this many insert fields detected: " + $insertFields.size());

            data['insert_data'] = [];

            $insertFields.each( function(i, e){

                console.log("in the each function");

                var $tr = $(this),
                    obj = {};

                $tr.find('input').each(function(){

                    var column = $(this).attr('data-column-name');

                    if(column != 'undefined'){
                        obj[column] = $(this).val();
                    }

                });

                data.insert_data.push( obj );
                console.log( JSON.stringify(data));

            });
            

        }

        if( page.$table.find('input').size() > 0){

            //collect update fields
            data['update_data'] = {};

            $updateFields.each(function(){
                
                var $tr = $(this),
                obj = {},
                column;
                
                if( !$tr.find('input').size() > 0 ){
                    return true;
                }

                $tr.find('input').each(function(i){

                    column = $(this).attr('data-column-name');

                    //if(column != 'undefined' && column != 'patient_id_insurance_claim'){
                        obj[column] = $(this).val();
                    //}

                });

                data['update_data'][$tr.attr('data-service-id')] = obj; 
                
                //Here you log whether or not to fill in all of the expected
                //copay cells for this patient. 

                if( $("input[type='checkbox']").prop('checked') ){

                    data['fillExpectedCopayFields'] = true;

                }else{

                    data['fillExpectedCopayFields'] = false;

                }

            });

        }

        page.post(data);

    });

        /* old code

        if(page.infoType === 'Services'){

            //find the rows with data-database-status="insert"
            //loop through each of those rows

            data['patient_id']  = page.activePatientId;
            data['table_name']  = 'services';
            data['insert_data'] = [];

            page.$table.find('tr[data-database-status="insert"]').each( function(i, e){

                var $tr = $(this),
                    obj = {};

                $tr.find('input').each(function(){

                    var column = $(this).attr('data-column-name');

                    if(column != 'undefined'){
                        obj[column] = $(this).val();
                    }

                });

                data.insert_data[i] = obj;

            });

            page.post(data);


        }else{

            data.update_data = {};

            page.$table.find('tr[data-database-status="update"]').each(function(){
                
                var $tr = $(this),
                obj = {},
                column;
                
                if( !$tr.find('input').size() > 0 ){
                    return true;
                }

                $tr.find('input').each(function(i){

                    column = $(this).attr('data-column-name');

                    if(column != 'undefined' && column != 'patient_id_insurance_claim'){
                        obj[column] = $(this).val();
                    }

                });

                data['update_data'][$tr.attr('data-service-id')] = obj; 
                data['patient_id'] = page.activePatientId;
                data['table_name'] = 'insurance_claim';
                
                //Here you log whether or not to fill in all of the expected
                //copay cells for this patient. 

                if( $("input[type='checkbox']").prop('checked') ){

                    data['fillExpectedCopayFields'] = true;

                }else{

                    data['fillExpectedCopayFields'] = false;

                }

            });


            page.post(data);
            
        }
        
    }); */


}

page.attachAddRowsEventHandler = function(el){

    //called by createButtons

    var $addRowButton = el;
        
    $addRowButton.click(function(){

        page.displayGetResults.headerRow;
        page.$table;

        var columnInfo     = {},
        $tableHeaders      = page.$table.find('tr').eq(0).find('th'),
        $lastRow           = page.$table.find('tr').eq( page.$table.find('tr').size() - 1 ),
        $clonedRow         = $lastRow.clone();

        //load array of table headers for necessary information
        $tableHeaders.each(function(i){
            var $el = $(this);
            columnInfo[$el.index()] = $el.attr('data-column-name');
        });

        $clonedRow.attr('data-database-status', "insert");

        //replace text nodes with input fields
        $clonedRow.find('td').each(function(i){

            var $el    = $(this),
                text   = $el.text(),
              $table   = $('table'),
            $lastRow   = $table.find('tr').eq( $table.find('tr').size() - 1 ),
                width  = $lastRow.find('td').eq(i).width();

            //if this is the numbered column, add one to it and then skip the rest of the loop.
            if(columnInfo[i] === "undefined"){
                $el.text( parseInt(text) + 1 );
                return true;
            }

            if(columnInfo[i] != 'patient_id_services' && columnInfo[i] != 'id_services'){

                $el.html("<input type='text' value='" + text + "' data-column-name='" + columnInfo[i] + "'>");

            }

            if(columnInfo[i] === 'id_services'){

                $el.text("--");
            }


        });

        //insert cloned row with "insert" data attribute

        $clonedRow.insertAfter($lastRow);


    });

}

page.attachAddPatientSubmitEventHandler = function(){

    var $button = $('#infoTable button');

    $button.click(function(){

        //collect new patients and submit
        //to post function

        page.infoType = 'patients';

        var data = {
            'table_name'  : 'patients',
           'insert_data'  : [],
           'responseJSON' : {
                'error' : []
           }
        };

        $('.add_patients_form').each(function( i ){

            var obj = {};

            $(this).find('input').each(function(){

                
                var columnName = $( this ).attr('data-column-name');

                //deal with blanks
                if( $( this ).val() === "" ){
                    
                    columnName = page.beautifyColumnNames(columnName);
                    data.responseJSON.error.push({ [columnName] : "cannot be blank"});

                }else{

                    //collect info
                    obj[columnName] = $(this).val();

                }
                
            });

            data.insert_data[i] = obj;

        });

        if( data.responseJSON.error.length > 0){
            page.displayFlash(data, "error");
        }else{
           page.post(data);
        }

        $(this).off();

    });

}

page.attachAddPatientEventHandler = function(){

    //called by initialize.js


    var            $el = $('button#add_patient'),
        newPatientForm = "public_html/snippets/forms/new_patient.php",
          submitButton = "public_html/snippets/submit_button.html";

    $el.click(function(){

        var $addPatientsForm = $('.add_patients_form');

        //prepare infoType article if this is the first click
        if( $addPatientsForm.size() === 0 ){ 

            page.insertArticle();

            var $infoTableArticle = $('#infoTable');

            $infoTableArticle.load(newPatientForm,function(){
                $.get(submitButton, function( data ){
                    $infoTableArticle.append( data );
                    page.attachAddPatientSubmitEventHandler();
                });

            });     

        }else{

            $.get(newPatientForm, function( data ){
                $addPatientsForm.last().after( data );
            });

        }

        

    });




}

page.addInsuranceClaimMiniForms = function(){

    //called by createButtons

    var $th    = page.$table.find('th'),
    widthData  = [];

    //Keep track of column widths and column names
    for(var i=0; i<=$th.size(); i++){

        widthData.push({
            'width'  : parseInt( $th.eq(i).width() ),
            'column' : $th.eq(i).attr('data-column-name')

        });

    }


    function dontConvertTheseFields(col){
        var skip;

        if( col.match(/patient_id/ig)  ||
            col.match(/id_services/ig) ||
            col.match(/undefined/ig)   
           ){

            skip = true;
        }else if(page.infoType === 'insurance_claim' && col === 'dos'){
            skip = true;
        }else{
            skip = false;
        }

        return skip;
    }

    //Loop through table data cells
    //Pass in all the table data

    page.$table.find('td').each( function(){

        var $td  = $(this),
          column = $th.eq( $td.index() ).attr('data-column-name');

        //check the column name to make sure it's not the Px id
        if( !dontConvertTheseFields(column) ){

            //add the event handler to conver the text node into an input
            page.addInsuranceClaimMiniForms.convertTextToInput( this, widthData);
            
        }

    });

} 

page.addInsuranceClaimMiniForms.addCheckBox = function(){


    $("<lable for='fillExpectedCopayFields'><input type='checkbox' name='fillExpectedCopayFields' value='true'/>Fill all expected copay fields?</lable>").appendTo('div#buttons');

}

page.addInsuranceClaimMiniForms.convertTextToInput = function(el, widthData){      

    var $el = $(el);
    
    $el.click(widthData, function(e){

        var $el = $(this),
           text = $el.text();

        $('th').eq($el.index()).width(e.data[$el.index()].width);
        $el.html("<input type='text' value='" + text + "' data-column-name='" + e.data[$el.index()].column + "'>");

        $el.off();

        if( e.data[$el.index()].column === 'expected_copay_amount' && $("input[type='checkbox']").size() === 0){

            page.addInsuranceClaimMiniForms.addCheckBox()
        }
    });
              

}

page.createButtons = function (){

    //called by displayGetResults

    //build 'Add Row' and 'Submit' buttons for
    //services and finances screens

    $('article#infoTable').append(function(){

        var div = "<div class='center-block' id='buttons'>";
        var button  = '<button type="submit" class="btn btn-default">Submit</button>';
        var buttons = '<button class="btn btn-default">Add Row</button><button class="btn btn-default">Submit</button>';
        
        //choose the appropriate button group
        //and return them to be appended

        if(page.infoType === 'Services')
            return div.concat(buttons, "</div>");
        else
            return div.concat(button, "</div>");

    });

    //Attach event handlers to the buttons

    if(page.infoType === 'insurance_claim' || page.infoType.match(/services/ig)){
        page.addInsuranceClaimMiniForms();
    }

    $('article#infoTable button').each(function(i){

        var $el = $(this);

        switch( $el.text() ){

            case 'Add Row':
                //add event handler for "add row"
                page.attachAddRowsEventHandler($el);         
                break;

            case 'Submit':
                //add event handler for "submit"
                page.attachSubmitEventHandler($el);  
                break;

            default:
                break;

        }

    });

}




