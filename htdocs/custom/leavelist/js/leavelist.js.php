<?php
/* Copyright (C) 2019 SuperAdmin
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB')) define('NOREQUIREDB', '1');
if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN')) define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');


/**
 * \file    leavelist/js/leavelist.js.php
 * \ingroup leavelist
 * \brief   JavaScript file for module LeaveList.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include($_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php");
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];$tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include(substr($tmp, 0, ($i + 1)) . "/main.inc.php");
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include(substr($tmp, 0, ($i + 1)) . "/../main.inc.php");
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php")) $res = @include("../../../main.inc.php");
if (!$res) die("Include of main fails");

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

?>

/* Javascript library of module LeaveList */
/* js included file by me  below :*/
window.onload = function () {


    $("#employee").select2({
       // minimumResultsForSearch: -1,
        formatResult: format,
        formatSelection: format,
        escapeMarkup: function(m) { return m; }
       // placeholder: function(){
         //   $(this).data('placeholder');
        //}

    });
    today = new Date();
	months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
	monthAndYear = document.getElementById("monthAndYear");
	monthAndYear2 = document.getElementById("monthAndYear2");
	monthAndYear3 = document.getElementById("monthAndYear3");
	var currentMonth = localStorage.getItem("monthIs");
	var currentYear = localStorage.getItem("yearIs");
	var arrayOfLeaves = JSON.parse(localStorage.getItem("arrayIs"));
	showCalendar(currentMonth, currentYear,arrayOfLeaves);
}

function next(arrayOfLeaves) {
	currentYear = (currentMonth === 11) ? currentYear + 1 : currentYear;
	currentMonth = (currentMonth + 1) % 12;
	showCalendar(currentMonth, currentYear,arrayOfLeaves);

}
function nextClicked() {
	// changes the month and the year of the first calendar
	currentYear = ((currentMonth === 0)) ? currentYear - 1 : currentYear;
	switch (currentMonth) {
		case 0 :
			currentMonth = 11;
			break;
		default :
			currentMonth--;
			break;
	}
	window.value = 0;
	var arrayOfLeaves = JSON.parse(localStorage.getItem("arrayIs"));
	showCalendar(currentMonth, currentYear,arrayOfLeaves);

}

function previousClicked() {
	currentYear = ((currentMonth === 0) || (currentMonth === 1) || (currentMonth === 2)) ? currentYear - 1 : currentYear;
	switch (currentMonth) {
		case 0 :
			currentMonth = 9;
			break;
		case 1 :
			currentMonth = 10;
			break;
		case 2 :
			currentMonth = 11;
			break;
		default :
			currentMonth = currentMonth - 3;
			break;
	}
	window.value = 0;
	var arrayOfLeaves = JSON.parse(localStorage.getItem("arrayIs"));
	showCalendar(currentMonth, currentYear,arrayOfLeaves);

}

function jump(sel) {
	//
	//
	// modif
	selectYear = document.getElementById("start"); // used for jump section
	selectYear = selectYear.value.toString().slice(0, 4);
	selectMonth = document.getElementById("start"); // used for jump section
	selectMonth = selectMonth.value.toString().slice(5, 7);
	// this is how to get employee name from html
	if (sel) {
		selectEmployee = sel.options[sel.selectedIndex].text
		alert(selectEmployee);
	}
	//


	currentYear = parseInt(selectYear);


	currentMonth = parseInt(selectMonth) - 1;
	window.value = 0;
	showCalendar(currentMonth, currentYear);
}

function showCalendar(month, year,arrayOfLeaves) {
	console.log('in showCalendar function : ');
	console.log(month);
	console.log(year);
	let firstDay = (new Date(year, month)).getDay();
	tbl = document.getElementById("calendar-body");
	tba = document.getElementById("calendar-body2");
	tbb = document.getElementById("calendar-body3");// body of the calendar
	console.log(arrayOfLeaves);
	// clearing all previous cells
	switch (window.value) {
		case 0 : {
			tbl.innerHTML = "";
			tba.innerHTML = "";
			tbb.innerHTML = "";
		}

			break;
		case 1 : {
			tba.innerHTML = "";
			tbb.innerHTML = "";
		}
			break;
		case 2 :
			tbb.innerHTML = "";
			break;
		default :
			break;
	}
	// filing data about month and in the page via DOM.
	switch (window.value) {
		case 0 : {

				monthAndYear.innerHTML = months[month] + " " + year;

			document.getElementById("calendars-container").style.display="block"
			document.getElementById("table-responsive").style.display="none"
		}
			;
			break;
		case 1 :
			monthAndYear2.innerHTML = months[month] + " " + year;
			break;
		case 2 :
			monthAndYear3.innerHTML = months[month] + " " + year;
			break;
		default :
			break;
	}
	// creating all cells
	let date = 1;
	for (let i = 0; i < 6; i++) {
		// creates a table row
		let row = document.createElement("tr");

		//creating individual cells, filing them up with data.
		for (let j = 0; j < 7; j++) {
			if (i === 0 && j < firstDay) {
				cell = document.createElement("td");
				cellText = document.createTextNode("");
				cell.appendChild(cellText);
				row.appendChild(cell);
			} else if (date > daysInMonth(month, year)) {
				break;
			} else {
				cell = document.createElement("td");
				cellText = document.createTextNode(date);
				// add some style to the cells background
				cell.classList.add("cell-back-style");
				/*if (date === today.getDate() && year === today.getFullYear() && month === today.getMonth()) {
					// style for today's date
					cell.style.color="red";
				}*/
				/*var arrayOfMonths = Array([]) ; // for displaying or hide other calendars or buttons occurring to searched results
                for (var k=0;k<arrayOfLeaves.length;k++){
                    if (!arrayOfMonths[k]) arrayOfMonths[k] = []
                    arrayOfMonths[k][0]=parseInt(arrayOfLeaves[k][0].slice(5, 7));
                    arrayOfMonths[k][1]=parseInt(arrayOfLeaves[k][0].slice(0, 4));
                    arrayOfMonths[k][2]=parseInt(arrayOfLeaves[k][1].slice(5, 7));
                    arrayOfMonths[k][3]=parseInt(arrayOfLeaves[k][1].slice(0, 4));
                }*/
              /*  var first=arrayOfLeaves[0][0];
                var last=arrayOfLeaves[0][1];
                MSperDay=24*60*60*1000;
                for (var k=0;k<arrayOfLeaves.length;k++){
                    var firstDayMilliseconds = Date.parse(arrayOfLeaves[k][0]);
                    var secondDayMilliseconds = Date.parse(arrayOfLeaves[k][1]);
                    if (firstDayMilliseconds<Date.parse(first)){
                        first = arrayOfLeaves[k][0];
                        console.log(Date.parse(first));
                        console.log('millis');
                        console.log(firstDayMilliseconds);
					}
                    if(secondDayMilliseconds>Date.parse(last)){
                        last = arrayOfLeaves[k][0];
                        console.log(Date.parse(last));
                        console.log('millis2');
                        console.log(secondDayMilliseconds);
					}
                }
                if((Math.ceil(Math.abs(Date.parse(last)-Date.parse(first))/MSperDay))>daysInMonth(parseInt(first.slice(5, 7)),parseInt(first.slice(0, 4))))
				{
                    document.getElementById("previous").style.display="none"
                    document.getElementById("next").style.display="none"
                    document.getElementById("card3").style.display="none"
                    document.getElementById("card2").style.display="none"
                    document.getElementById("calendars-container").style.textAlign="center"
				}*/
                /*for (var k=0;k<arrayOfLeaves.length;k++){
                    for (var h=0;h<4;h++){
                        for (var j=0;j<arrayOfLeaves.length;j++){
                            for (var l=0;l<4;l++){

							}
						}
					}
                }*/



				let i = 0;
				var contenu = '';
				for (var k=0;k<arrayOfLeaves.length;k++)
				{   MSperDay=24*60*60*1000;
					var firstDayMilliseconds = Date.parse(arrayOfLeaves[k][0]);
					var secondDayMilliseconds = Date.parse(arrayOfLeaves[k][1]);
					var d = new Date(year,month,date+1);
					if (d.getTime()<=(secondDayMilliseconds+MSperDay) && d.getTime()>=firstDayMilliseconds)
					{
						if ((arrayOfLeaves[k][2]==1)&&((i == 0 )||(i==1))) {    /*style cell display with priorities */
                            cell.classList.add("draft-cell");
                            contenu +='<strong>Nom: </strong>'+arrayOfLeaves[k][3]+'</br><strong style="color:#ececeb;">Statut: </strong>Brouillon</br>';
                            i=1;
                        }
						if ((arrayOfLeaves[k][2]==2)&&(i!=3)){
                            if(i==1) cell.classList.remove("draft-cell");
                            cell.classList.add("awaiting-cell");
                            contenu +='<strong>Nom: </strong>'+arrayOfLeaves[k][3]+'</br><strong style="color:yellow">Statut: </strong>En attente</br>';
                            i=2;
                        }
						if (arrayOfLeaves[k][2]==3) {
						    if(i==1) cell.classList.remove("draft-cell");
						    if(i==2) cell.classList.remove("awaiting-cell");
                            cell.classList.add("approved-cell");
                            contenu +='<strong>Nom: </strong>'+arrayOfLeaves[k][3]+'</br><strong style="color:#29ea63;">Statut: </strong>Approuvé</br>';
                            i=3;
                        }
					}


				} // color some days date
                tippy(cell,{content : contenu, arrow: true ,animateFill: true,
                    animation: 'scale'});
				cell.appendChild(cellText);
				row.appendChild(cell);
				date++;
			}


		}

		switch (window.value) {

			case 0 :
				tbl.appendChild(row);
				break;
			case 1 : {
				//let row2 = row.cloneNode(true);

				tba.appendChild(row);
				break;
			}
			case 2 : {
				tbb.appendChild(row);
				break;
			}
			default :
				break;
		}// appending each row into calendar body.
	}

	while (window.value < 2) {
		window.value++;
		next(arrayOfLeaves);
	}
}


// check how many days in a month code from https://dzone.com/articles/determining-number-days-month
function daysInMonth(iMonth, iYear) {
	return 32 - new Date(iYear, iMonth, 32).getDate();
}
//
//
//
//
// for second section
function searchJS(arrayOfDays) {

jumpToLeaveDate(arrayOfDays);

//	alert(output);
}
function jumpToLeaveDate(arrayOfLeaveDates) {
	//
	//
	// modif
	console.log(arrayOfLeaveDates);
	/* function to sort array of dates with first index */
    arrayOfLeaveDates.sort(function(a,b) {
        return Date.parse(a[0])-Date.parse(b[0]) //Date.parse(arrayOfLeaves[0])
    });
		selectYear = arrayOfLeaveDates[0][0].slice(0, 4); // get the year of first cell
		selectMonth = arrayOfLeaveDates[0][0].slice(5, 7); // get the month of first cell
	currentYear = parseInt(selectYear);
	currentMonth = parseInt(selectMonth) - 1;
	console.log(currentMonth);
	window.value = 0; // global variable to increment and decrement months using previous and next
	localStorage.setItem("monthIs", currentMonth); // set month value into local storage

	localStorage.setItem("yearIs", currentYear); // set year value into local storage
	var myJsonArray = JSON.stringify(arrayOfLeaveDates); // to save the array into local storage
	localStorage.setItem("arrayIs", myJsonArray); // to save the array into local storage
}
/*function callGroup()
{    if(document.getElementById("search-f-group").style.display=="none")
{
    document.getElementById("search-f-group").style.display="inline"
    document.getElementById("search-f-group").setAttribute("required", "");
    document.getElementById("search-f-employee").style.display="none"
    document.getElementById("search-f-employee").removeAttribute("required");
} else {
    document.getElementById("search-f-group").style.display="none"
    document.getElementById("search-f-group").removeAttribute("required");
    document.getElementById("search-f-employee").style.display="inline"
    document.getElementById("search-f-employee").setAttribute("required", "");
}
}*/
/* make condition on empty checkbox input */
function conditionOnCheckbox()
{
    if($('span.search-f.required :checkbox:checked').length > 0){
        $(".filter_for_req").prop('required', false);
    }
    else{
        $(".filter_for_req").prop('required', true);
	}
}
/* switching display by menu selection */
function switchMenu(k)
{console.log("in switch");
    if(k==1){
        document.getElementById("calendars-container").style.display="block"
        document.getElementById("table-responsive").style.display="none"
        document.getElementById("dashboard").style.display="none"
	}
    else if (k==2){
        document.getElementById("calendars-container").style.display="none"
        document.getElementById("table-responsive").style.display="block"
        document.getElementById("dashboard").style.display="none"
	}
    else if (k==3){
        document.getElementById("calendars-container").style.display="none"
        document.getElementById("table-responsive").style.display="none"
        document.getElementById("dashboard").style.display="block"
       
	}
}
function dashboard(id, fData){
    var barColor = '#3c4664';
    function segColor(c){ return {approved:"#ccd3d3", awaiting:"#bc9600",draft:"#f5f5f5"}[c]; }

    // compute total for each state.
    fData.forEach(function(d){d.total=d.freq.approved+d.freq.awaiting+d.freq.draft;});

    // function to handle histogram.
    function histoGram(fD){
        var hG={},    hGDim = {t: 60, r: 0, b: 30, l: 0};
        hGDim.w = 500 - hGDim.l - hGDim.r,
            hGDim.h = 300 - hGDim.t - hGDim.b;

        //create svg for histogram.
        var hGsvg = d3.select(id).append("svg")
            .attr("width", hGDim.w + hGDim.l + hGDim.r)
            .attr("height", hGDim.h + hGDim.t + hGDim.b).append("g")
            .attr("transform", "translate(" + hGDim.l + "," + hGDim.t + ")");

        // create function for x-axis mapping.
        var x = d3.scale.ordinal().rangeRoundBands([0, hGDim.w], 0.1)
            .domain(fD.map(function(d) { return d[0]; }));

        // Add x-axis to the histogram svg.
        hGsvg.append("g").attr("class", "x axis")
            .attr("transform", "translate(0," + hGDim.h + ")")
            .call(d3.svg.axis().scale(x).orient("bottom"));

        // Create function for y-axis map.
        var y = d3.scale.linear().range([hGDim.h, 0])
            .domain([0, d3.max(fD, function(d) { return d[1]; })]);

        // Create bars for histogram to contain rectangles and freq labels.
        var bars = hGsvg.selectAll(".bar").data(fD).enter()
            .append("g").attr("class", "bar");

        //create the rectangles.
        bars.append("rect")
            .attr("x", function(d) { return x(d[0]); })
            .attr("y", function(d) { return y(d[1]); })
            .attr("width", x.rangeBand())
            .attr("height", function(d) { return hGDim.h - y(d[1]); })
            .attr('fill',barColor)
            .on("mouseover",mouseover)// mouseover is defined below.
            .on("mouseout",mouseout);// mouseout is defined below.

        //Create the frequency labels above the rectangles.
        bars.append("text").text(function(d){ return d3.format(",")(d[1])})
            .attr("x", function(d) { return x(d[0])+x.rangeBand()/2; })
            .attr("y", function(d) { return y(d[1])-5; })
            .attr("text-anchor", "middle");

        function mouseover(d){  // utility function to be called on mouseover.
            // filter for selected state.
            var st = fData.filter(function(s){ return s.State == d[0];})[0],
                nD = d3.keys(st.freq).map(function(s){ return {type:s, freq:st.freq[s]};});

            // call update functions of pie-chart and legend.
            pC.update(nD);
            leg.update(nD);
        }

        function mouseout(d){    // utility function to be called on mouseout.
            // reset the pie-chart and legend.
            pC.update(tF);
            leg.update(tF);
        }

        // create function to update the bars. This will be used by pie-chart.
        hG.update = function(nD, color){
            // update the domain of the y-axis map to reflect change in frequencies.
            y.domain([0, d3.max(nD, function(d) { return d[1]; })]);

            // Attach the new data to the bars.
            var bars = hGsvg.selectAll(".bar").data(nD);

            // transition the height and color of rectangles.
            bars.select("rect").transition().duration(500)
                .attr("y", function(d) {return y(d[1]); })
                .attr("height", function(d) { return hGDim.h - y(d[1]); })
                .attr("fill", color);

            // transition the frequency labels location and change value.
            bars.select("text").transition().duration(500)
                .text(function(d){ return d3.format(",")(d[1])})
                .attr("y", function(d) {return y(d[1])-5; });
        }
        return hG;
    }

    // function to handle pieChart.
    function pieChart(pD){
        var pC ={},    pieDim ={w:250, h: 250};
        pieDim.r = Math.min(pieDim.w, pieDim.h) / 2;

        // create svg for pie chart.
        var piesvg = d3.select(id).append("svg")
            .attr("width", pieDim.w).attr("height", pieDim.h).append("g")
            .attr("transform", "translate("+pieDim.w/2+","+pieDim.h/2+")");

        // create function to draw the arcs of the pie slices.
        var arc = d3.svg.arc().outerRadius(pieDim.r - 10).innerRadius(0);

        // create a function to compute the pie slice angles.
        var pie = d3.layout.pie().sort(null).value(function(d) { return d.freq; });

        // Draw the pie slices.
        piesvg.selectAll("path").data(pie(pD)).enter().append("path").attr("d", arc)
            .each(function(d) { this._current = d; })
            .style("fill", function(d) { return segColor(d.data.type); })
            .on("mouseover",mouseover).on("mouseout",mouseout);

        // create function to update pie-chart. This will be used by histogram.
        pC.update = function(nD){
            piesvg.selectAll("path").data(pie(nD)).transition().duration(500)
                .attrTween("d", arcTween);
        }
        // Utility function to be called on mouseover a pie slice.
        function mouseover(d){
            // call the update function of histogram with new data.
            hG.update(fData.map(function(v){
                return [v.State,v.freq[d.data.type]];}),segColor(d.data.type));
        }
        //Utility function to be called on mouseout a pie slice.
        function mouseout(d){
            // call the update function of histogram with all data.
            hG.update(fData.map(function(v){
                return [v.State,v.total];}), barColor);
        }
        // Animating the pie-slice requiring a custom function which specifies
        // how the intermediate paths should be drawn.
        function arcTween(a) {
            var i = d3.interpolate(this._current, a);
            this._current = i(0);
            return function(t) { return arc(i(t));    };
        }
        return pC;
    }

    // function to handle legend.
    function legend(lD){
        var leg = {};

        // create table for legend.
        var legend = d3.select(id).append("table").attr('class','legend');

        // create one row per segment.
        var tr = legend.append("tbody").selectAll("tr").data(lD).enter().append("tr");

        // create the first column for each segment.
        tr.append("td").append("svg").attr("width", '16').attr("height", '16').append("rect")
            .attr("width", '16').attr("height", '16')
            .attr("fill",function(d){ return segColor(d.type); });

        // create the second column for each segment.
        tr.append("td").text(function(d){ return d.type;});

        // create the third column for each segment.
        tr.append("td").attr("class",'legendFreq')
            .text(function(d){ return d3.format(",")(d.freq);});

        // create the fourth column for each segment.
        tr.append("td").attr("class",'legendPerc')
            .text(function(d){ return getLegend(d,lD);});

        // Utility function to be used to update the legend.
        leg.update = function(nD){
            // update the data attached to the row elements.
            var l = legend.select("tbody").selectAll("tr").data(nD);

            // update the frequencies.
            l.select(".legendFreq").text(function(d){ return d3.format(",")(d.freq);});

            // update the percentage column.
            l.select(".legendPerc").text(function(d){ return getLegend(d,nD);});
        }

        function getLegend(d,aD){ // Utility function to compute percentage.
            return d3.format("%")(d.freq/d3.sum(aD.map(function(v){ return v.freq; })));
        }

        return leg;
    }

    // calculate total frequency by segment for all state.
    var tF = ['approved','awaiting','draft'].map(function(d){
        return {type:d, freq: d3.sum(fData.map(function(t){ return t.freq[d];}))};
    });

    // calculate total frequency by state for all segment.
    var sF = fData.map(function(d){return [d.State,d.total];});

    var hG = histoGram(sF), // create the histogram.
        pC = pieChart(tF), // create the pie-chart.
        leg= legend(tF);  // create the legend.
}
function countStatusPerMonth (arrayOfLeaves){
    console.log('entered');
    /* create new multidimensional array to insert request status number for each kind per month*/
	var arrayOfStatusPerMonth = []; // first index contains the month second contains number of status ( 0 index for approved / 1 for awaiting / 2 for drafts )
	for (var i=0;i<12;i++){ /* initialise the array with zeros ( we must initialise each sub array explicitly in javascript*/
	    if (!arrayOfStatusPerMonth[i]) {
            arrayOfStatusPerMonth.push([]);
        }
	    for(var j=0;j<3;j++){
            arrayOfStatusPerMonth[i][j]=0;
		}
	}
	console.log(arrayOfLeaves);
	var yearSearchedInCharts = parseInt(arrayOfLeaves[0][1].slice(0, 4));
    for (var k=0;k<arrayOfLeaves.length;k++) {
        MSperDay = 24 * 60 * 60 * 1000;
        var firstDayMilliseconds = Date.parse(arrayOfLeaves[k][0]);
        var secondDayMilliseconds = Date.parse(arrayOfLeaves[k][1]);
        var daysNumberByStatus = Math.ceil(Math.abs(secondDayMilliseconds - firstDayMilliseconds) / MSperDay) ;
        var monthOfFirstCell = parseInt(arrayOfLeaves[k][0].slice(5, 7));
        var monthOfSecondCell = parseInt(arrayOfLeaves[k][1].slice(5, 7));
        if ((yearSearchedInCharts == parseInt(arrayOfLeaves[k][0].slice(0, 4)))&&( monthOfFirstCell === monthOfSecondCell)) { /* to search only in the following year */
            var monthNumber = parseInt(arrayOfLeaves[k][0].slice(5, 7)) - 1 ; /* the month number to be inserted like index in arrayOfStatusPerMonth ( - 1 because month array begans from 0 )*/
			if (arrayOfLeaves[k][2] == 1) { /* for draft cell */
                arrayOfStatusPerMonth[monthNumber][2] += daysNumberByStatus + 1;
            }
            if (arrayOfLeaves[k][2] == 2) { /* for awaiting cell */
                arrayOfStatusPerMonth[monthNumber][1] += daysNumberByStatus + 1;
            }
            if (arrayOfLeaves[k][2] == 3) { /* for approved cell */
                arrayOfStatusPerMonth[monthNumber][0] += daysNumberByStatus + 1;
            }

        }
        else if ((yearSearchedInCharts == parseInt(arrayOfLeaves[k][0].slice(0, 4)))&&( monthOfFirstCell != monthOfSecondCell)){
        var monthDiff = monthOfSecondCell - monthOfFirstCell ;/* month difference between first day of leave and last one */
            var daysNumberByStatus = Math.ceil(Math.abs(secondDayMilliseconds - firstDayMilliseconds) / MSperDay) ;
            var monthNumber = parseInt(arrayOfLeaves[k][0].slice(5, 7)) - 1 ;
            if(monthOfSecondCell>(monthOfFirstCell)){
                if (arrayOfLeaves[k][2] == 1) { /* for draft cell */
                    arrayOfStatusPerMonth[monthOfFirstCell-1][2] += daysInMonth(monthOfFirstCell-1,yearSearchedInCharts) - parseInt(arrayOfLeaves[k][0].slice(-2)) + 1;
                    arrayOfStatusPerMonth[monthOfSecondCell-1][2] += parseInt(arrayOfLeaves[k][1].slice(-2)) ;
                    var indexOfMonths = monthOfFirstCell+1;
                    while (indexOfMonths < monthOfSecondCell){
                        arrayOfStatusPerMonth[indexOfMonths][2] += daysInMonth(indexOfMonths,yearSearchedInCharts) + 1;
                        indexOfMonths ++;
					}
                }
                if (arrayOfLeaves[k][2] == 2) { /* for awaiting cell */
                    arrayOfStatusPerMonth[monthOfFirstCell-1][1] += daysInMonth(monthOfFirstCell-1,yearSearchedInCharts) - parseInt(arrayOfLeaves[k][0].slice(-2)) + 1;
                    arrayOfStatusPerMonth[monthOfSecondCell-1][1] += parseInt(arrayOfLeaves[k][1].slice(-2)) ;
                    var indexOfMonths = monthOfFirstCell+1;
                    while (indexOfMonths < monthOfSecondCell){
                        arrayOfStatusPerMonth[indexOfMonths][1] += daysInMonth(indexOfMonths,yearSearchedInCharts) + 1;
                        indexOfMonths ++;
                    }
                }
                if (arrayOfLeaves[k][2] == 3) { /* for approved cell */
                    console.log(daysInMonth(monthOfFirstCell-1,yearSearchedInCharts));
                    console.log(parseInt(arrayOfLeaves[k][0].slice(-2)));
                    console.log(parseInt(arrayOfLeaves[k][1].slice(-2)));
                    arrayOfStatusPerMonth[monthOfFirstCell-1][0] += daysInMonth(monthOfFirstCell-1,yearSearchedInCharts) - parseInt(arrayOfLeaves[k][0].slice(-2)) + 1;
                    arrayOfStatusPerMonth[monthOfSecondCell-1][0] += parseInt(arrayOfLeaves[k][1].slice(-2)) ;
                    var indexOfMonths = monthOfFirstCell+1;
                    while (indexOfMonths < monthOfSecondCell){
                        arrayOfStatusPerMonth[indexOfMonths][0] += daysInMonth(indexOfMonths,yearSearchedInCharts) + 1;
                        indexOfMonths ++;
                    }

                }
			}
		}
    }
    console.log(arrayOfStatusPerMonth);
    var freqData=[
        {State:'JAN',freq:{approved:arrayOfStatusPerMonth[0][0], awaiting:arrayOfStatusPerMonth[0][1], draft:arrayOfStatusPerMonth[0][2]}}
        ,{State:'FEB',freq:{approved:arrayOfStatusPerMonth[1][0], awaiting:arrayOfStatusPerMonth[1][1], draft:arrayOfStatusPerMonth[1][2]}}
        ,{State:'MAR',freq:{approved:arrayOfStatusPerMonth[2][0], awaiting:arrayOfStatusPerMonth[2][1], draft:arrayOfStatusPerMonth[2][2]}}
        ,{State:'APR',freq:{approved:arrayOfStatusPerMonth[3][0], awaiting:arrayOfStatusPerMonth[3][1], draft:arrayOfStatusPerMonth[3][2]}}
        ,{State:'MAI',freq:{approved:arrayOfStatusPerMonth[4][0], awaiting:arrayOfStatusPerMonth[4][1], draft:arrayOfStatusPerMonth[4][2]}}
        ,{State:'JUN',freq:{approved:arrayOfStatusPerMonth[5][0], awaiting:arrayOfStatusPerMonth[5][1], draft:arrayOfStatusPerMonth[5][2]}}
        ,{State:'JUL',freq:{approved:arrayOfStatusPerMonth[6][0], awaiting:arrayOfStatusPerMonth[6][1], draft:arrayOfStatusPerMonth[6][2]}}
        ,{State:'AUG',freq:{approved:arrayOfStatusPerMonth[7][0], awaiting:arrayOfStatusPerMonth[7][1], draft:arrayOfStatusPerMonth[7][2]}}
        ,{State:'SEP',freq:{approved:arrayOfStatusPerMonth[8][0], awaiting:arrayOfStatusPerMonth[8][1], draft:arrayOfStatusPerMonth[8][2]}}
        ,{State:'OCT',freq:{approved:arrayOfStatusPerMonth[9][0], awaiting:arrayOfStatusPerMonth[9][1], draft:arrayOfStatusPerMonth[9][2]}}
        ,{State:'NOV',freq:{approved:arrayOfStatusPerMonth[10][0], awaiting:arrayOfStatusPerMonth[10][1], draft:arrayOfStatusPerMonth[10][2]}}
        ,{State:'DEC',freq:{approved:arrayOfStatusPerMonth[11][0], awaiting:arrayOfStatusPerMonth[11][1], draft:arrayOfStatusPerMonth[11][2]}}
    ];

    dashboard('#dashboard',freqData);

}
// for multiselect
function format(state) {
    if (!state.id) return state.text; // optgroup
    return  state.text;
}
