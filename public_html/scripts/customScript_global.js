var g = {

	'basePath'  : "http://localhost/therapyBusiness/",

	'patient_id' : undefined,

	'easyAutoData' : undefined,   //This is filled with the header auto complete and checked by other functions

	timeOptions : function(){

		var options = [],
		hour = 7,
		min = 0,
		newTime, strH, strM;

		while(hour < 21){

			for(var i = 0; i<4; i++){

				strH = hour.toString();
				strM = min.toString();

				if(strH.length == 1){
					strH = '0'.concat(strH);
				}

				if(strM.length == 1){
					strM += '0';
				}

				newTime = strH.concat(":", strM);
				options.push(newTime);

				min += 15;

			}

			min = 0;
			hour++;	

		}

		return options;

	}

}