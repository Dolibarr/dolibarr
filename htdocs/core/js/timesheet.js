/* Copyright (C) 2014      delcroip            <delcroip@gmail.com>
 * Copyright (C) 2015-2017 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2021      Josep Lluís Amador  <joseplluis@lliuretic.cat>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */


/* Parse en input data for time entry into timesheet */
function regexEvent(objet,evt,type)
{
	console.log('regexEvent type='+type);
    switch(type)
    {
          case 'days':
              var regex= /^[0-9]{1}([.,]{1}[0-9]{1})?$/;

              if(regex.test(objet.value) )
              {
                var tmp=objet.value.replace(',','.');
                if(tmp<=1.5){
                    var tmpint=parseInt(tmp);
                    if(tmp-tmpint>=0.5){
                        objet.value= tmpint+0.5;
                    }else{
                        objet.value= tmpint;
                    }
                }else{
                    objet.value= '1.5';
                }
              }else{
                 objet.value= '0';
            }
          break;
          case 'hours':
              var regex= /^[0-9]{1,2}:[0-9]{2}$/;
              var regex2=/^[0-9]{1,2}$/;
              var regex3= /^[0-9]{1}([.,]{1}[0-9]{1,2})?$/;
              if(!regex.test(objet.value))
              {
                  if(regex2.test(objet.value))
                    objet.value=objet.value+':00';
                  else if(regex3.test(objet.value)) {
                    var tmp=parseFloat(objet.value.replace(',','.'));
                    var rnd=Math.trunc(tmp);
                    objet.value=rnd+':'+ Math.round(60*(tmp-rnd));
                  } else
                    objet.value='';
              }
              /* alert(jQuery("#"+id).val()); */
              break;
          case 'timeChar':
              //var regex= /^[0-9:]{1}$/;
              //alert(event.charCode);
              var charCode = (evt.which) ? evt.which : event.keyCode;

              if(((charCode >= 48) && (charCode <= 57)) || //num
                    (charCode===46) || (charCode===8)||// comma & periode
                    (charCode === 58) || (charCode==44) )// : & all charcode
              {
                  // ((charCode>=96) && (charCode<=105)) || //numpad
            	  return true;

              }else
              {
                  return false;
              }

              break;
          default:
              break;
      }
}


function pad(n) {
    return (n < 10) ? ("0" + n) : n;
}



/* function from http://www.timlabonne.com/2013/07/parsing-a-time-string-with-javascript/ */
/* timeStr must be a duration with format XX:YY (AM/PM not supported) */
/* return: nbofextradays (0, 1, ...) */
function parseTime(timeStr, dt)
{
    if (!dt) {
        dt = new Date();
    }

    //var time = timeStr.match(/(\d+)(?::(\d\d))?\s*(p?)/i);
    var time = timeStr.match(/(\d+)(?::(\d\d))?/i);
    if (!time) {
        return -1;
    }
    var hours = parseInt(time[1], 10);
    dt.setHours(hours);
    dt.setMinutes(parseInt(time[2], 10) || 0);
    dt.setSeconds(0, 0);
 	//console.log("hours="+hours+" => return nbofextradays="+Math.floor(hours / 24)+" hours="+dt.getHours());
    return Math.floor(hours / 24);
}

/* Update total. days = column nb starting from 0 */
function updateTotal(days,mode)
{
	console.log('updateTotal days='+days+' mode='+mode);
    if (mode=="hours")
    {
        var total = new Date(0);
        total.setHours(0);
        total.setMinutes(0);
        var nbline = document.getElementById('numberOfLines').value;
        var startline = 0;
        if (document.getElementById('numberOfFirstLine')) {
        	startline = parseInt(document.getElementById('numberOfFirstLine').value);
        }
        var nbextradays = 0;
        for (var i=-1; i < nbline; i++)
        {
        	/* get value into timespent cell */
        	
            var id='timespent['+i+']['+days+']';
            var taskTime = new Date(0);
            var element = document.getElementById(id);
            if (element)
            {
            	/* alert(element.value);*/
                if (element.value)
                {
                	result=parseTime(element.value,taskTime);
                }
                else
                {
                	result=parseTime(element.innerHTML,taskTime);
                }
                if (result >= 0)
                {
                	nbextradays = nbextradays + Math.floor((total.getHours()+taskTime.getHours() + result*24) / 24);
                	//console.log("i="+i+" result="+result);
			    	total.setHours(total.getHours()+taskTime.getHours());
                	total.setMinutes(total.getMinutes()+taskTime.getMinutes());
            		//console.log("i="+i+" nbextradays cumul="+nbextradays+" h="+total.getHours()+" "+taskTime.getHours());
                }
            }
			
			/* get value into timeadded cell */
			
            var id='timeadded['+i+']['+days+']';
            var taskTime= new Date(0);
            var element=document.getElementById(id);
            if(element)
            {
            	/* alert(element.value);*/
                if (element.value)
                {
                	result=parseTime(element.value,taskTime);
                }
                else
                {
                	result=parseTime(element.innerHTML,taskTime);
                }
                if (result >= 0)
                {
                	nbextradays = nbextradays + Math.floor((total.getHours()+taskTime.getHours() + result*24) / 24);
                	//console.log("i="+i+" result="+result);
                	total.setHours(total.getHours()+taskTime.getHours());
                	total.setMinutes(total.getMinutes()+taskTime.getMinutes());
                	//console.log("i="+i+" nbextradays cumul="+nbextradays+" h="+total.getHours()+" "+taskTime.getHours());
                }
            }
        }

        // Add data on the perday view
        jQuery('.inputhour').each(function( index ) {
        	if (this.value)
        	{
                var taskTime= new Date(0);
        		/*console.log(total.getHours())
        		console.log(this.value)
            	alert(element.value);*/
                if (this.value)
                {
                	console.log(this.value+':00')
                	result=parseTime(this.value+':00',taskTime);
                }
                else
                {
                	result=parseTime(this.innerHTML+':00',taskTime);
                }
                if (result >= 0)
                {
                	total.setHours(total.getHours()+taskTime.getHours());
                }
        		console.log(total.getHours())
            }
        });
        // Add data on the perday view
        jQuery('.inputminute').each(function( index ) {
        	if (this.value)
        	{
                var taskTime= new Date(0);
        		/* console.log(total.getHours())
        		console.log(this.value)
            	alert(element.value);*/
                if (this.value)
                {
                	console.log('00:'+this.value)
                	result=parseTime('00:'+"00".substring(0, 2 - this.value.length) + this.value,taskTime);
                }
                else
                {
                	result=parseTime('00:'+"00".substring(0, 2 - this.innerHTML) + this.innerHTML,taskTime);
                }
                if (result >= 0)
                {
                	total.setMinutes(total.getMinutes()+taskTime.getMinutes());
                }
        		console.log(total.getMinutes())
            }
        });
        
        var stringdays = days;
        if (startline >= 1 && startline <= 9 && stringdays < 10) {
        	stringdays = '0'+stringdays;
        }
        
        /* Output total in top of column */
        
        if (total.getHours() || total.getMinutes()) jQuery('.totalDay'+stringdays).addClass("bold");
        else jQuery('.totalDay'+stringdays).removeClass("bold");
        var texttoshow = pad(nbextradays * 24 + total.getHours())+':'+pad(total.getMinutes());
    	jQuery('.totalDay'+stringdays).text(texttoshow);

		/* Output total of all total */
		
    	var totalhour = 0;
    	var totalmin = 0;
        for (var i=0; i<7; i++)
        {
        	stringdays = (i + startline);
            if (startline >= 1 && startline <= 9 && stringdays < 10) {
            	stringdays = '0'+stringdays;
            }

        	var taskTime= new Date(0);
       		result=parseTime(jQuery('.totalDay'+stringdays).text(),taskTime);
        	if (result >= 0)
        	{
        		totalhour = totalhour + taskTime.getHours() + result*24;
        		totalmin = totalmin + taskTime.getMinutes();
        	}
        }
        morehours = Math.floor(totalmin / 60);
        totalmin = totalmin % 60;
    	jQuery('.totalDayAll').text(pad(morehours + totalhour)+':'+pad(totalmin));
    }
    else
    {
        var total =0;
        var nbline = document.getElementById('numberOfLines').value;
        for (var i=-1; i<nbline; i++)
        {
            var id='timespent['+i+']['+days+']';
            var taskTime= new Date(0);
            var element=document.getElementById(id);
            if(element)
            {
                if (element.value)
                {
                    total+=parseInt(element.value);

                   }
                else
                {
                    total+=parseInt(element.innerHTML);
                }
            }

            var id='timeadded['+i+']['+days+']';
            var taskTime= new Date(0);
            var element=document.getElementById(id);
            if(element)
            {
                if (element.value)
                {
                    total+=parseInt(element.value);

                   }
                else
                {
                    total+=parseInt(element.innerHTML);
                }
            }
        }

        var stringdays = days;
        if (startline >= 1 && startline <= 9 && stringdays < 10) {
        	stringdays = '0'+stringdays;
        	console.log(stringdays);
        }
        
        if (total) jQuery('.totalDay'+stringdays).addClass("bold");
        else jQuery('.totalDay'+stringdays).removeClass("bold");
    	jQuery('.totalDay'+stringdays).text(total);
    }
}
