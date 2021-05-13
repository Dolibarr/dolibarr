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
 */

/**
 * \file    leavelist/css/leavelist.css.php
 * \ingroup leavelist
 * \brief   CSS file for module LeaveList.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/../main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

session_cache_limiter(false);

// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/*if (empty($user->id) && ! empty($_SESSION['dol_login']))
{
    $user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

?>
/* date placeholder */
.dateclass {
	width: 60%;
}

.dateclass.placeholderclass::before {
	width: 100%;
	content: attr(placeholder);
}

.dateclass.placeholderclass:hover::before {
	width: 0%;
	content: "";
}
#information-for-icons{
	text-align: center;
	font-size: medium;
	margin-bottom: 2em;
}

#circle-awaiting {

	margin-left: 2em;
}
#circle-draft {

	margin-left: 2em;
}
#selEmployee{
	margin-left: 1em;
}
#selTeam{
	margin-left: 1em;
}
.liste_titre{
	padding-top: 20px ;
	padding-bottom: 20px ;
}


.search-f{
    margin-top: 2em;
	margin-bottom: 3em;
	margin-right: 6em;
	color:#3c4664;
	font-size: 1em;
	font-weight: bold;
	decoration: inderline;
	display: inline-block;

}
.search-fields{
	text-align: center;
}
.filter_for_req{
	margin-left: 2em;
}

body{
	padding:0px 0px 0px 0px;
	margin: 0px 0px 0px 0px;

}
input{
	display: inline-block;
}
#search_button{
	/*margin-left: 45%;*/
	margin-bottom: 0.5em;
}
.search-header
{
	background-color:  #3c4664;
	width: 100%;
	color: white;
	margin-right:0px;
}

.awaiting-cell{
	background-color: #bc9600 !important;
	border-radius: 50%;
	padding : 4px 4px 4px 4px !important;
	color : darkslategrey ;
	text-align: center !important;
}
.approved-cell{
	background-color: #ccd3d3 !important;
	border-radius: 50%;
	padding : 4px 4px 4px 4px !important;
	color : darkslategrey ;
	text-align: center !important;
}
.draft-cell{
	background-color: white !important;
	border-radius: 50%;
	border:2px solid #eaeded;
	padding : 4px 4px 4px 4px !important;
	color : darkslategrey ;
	text-align: center !important;
}

 .cell-back-style{
	background-color:  rgb(248,248,248)  ;
}
 .card , .calendar-button
 {
	 display: inline-block;
 }
.card{

	margin-left: 0.5%;
	margin-right: 0.5%;
	border-style: solid;
	border-width: 3px;
	border-color: #cdcdcd;
	padding : 5px 30px 5px;
    color : rgba(158,158,158);
	box-shadow: slategrey;

}
/* #f8f8f8 for cells back ground
   #cce2cc for green days highlighed*/


.card-header{
	text-align: center;
	color :black;
}
#calendars-container{
	margin-bottom: 4em;
}
.search-f-endtime{
	margin-right: 3em;
	margin-top: 2em;
	margin-bottom: 3em;
	color:#3c4664;
	font-size: 1em;
	font-weight: bold;
	decoration: inderline;
	display: inline-block;
}
/* for info and warning messages */
@import url('//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
.isa_info, .isa_warning, {
	text-align: center;
	padding:12px;


}
.isa_info {
	color: #00529B;
	background-color: #BDE5F8;
	text-align: center;
	margin-bottom: 2em;

}
.isa_warning {
	color: #9F6000;
	background-color: #FEEFB3;
	text-align: center;
	margin-bottom: 2em;
}
.isa_info i, .isa_warning i {
	margin:10px 22px;
	font-size:1em;
	vertical-align:middle;
}
.checkbox_option{
	color: black;
	font-family: "Courier New", verdana, arial, helvetica, sans-serif;
}




path {  stroke: #fff; }
path:hover {  opacity:0.9; }
rect:hover {  fill:blue; }
.axis {  font: 10px sans-serif; }
.legend tr{    border-bottom:1px solid grey; }
.legend tr:first-child{    border-top:1px solid grey; }

.axis path,
.axis line {
	fill: none;
	stroke: #000;
	shape-rendering: crispEdges;
}

.x.axis path {  display: none; }
.legend{
	margin-bottom:76px;
	display:inline-block;
	border-collapse: collapse;
	border-spacing: 0px;
}
.legend td{
	padding:4px 5px;
	vertical-align:bottom;
}
.legendFreq, .legendPerc{
	align:right;
	width:50px;
}
#dashboard{
	text-align: center;
}


/* multiselect style */


.container-fluid {
	padding-top: 30px;
	color:#3c4664;
	font-size: 1em;
	font-weight: bold;
	text-align: center;
}

.select2-results__option { /* do the columns */
	float: left;
	width: 23%;
	display: inline !important;
}


/* move close cross [x] from left to right on the selected value (tag) */
#s2id_e2_2.select2-container-multi .select2-choices .select2-search-choice {
	padding: 3px 18px 3px 5px;
}
#s2id_e2_2.select2-container-multi .select2-search-choice-close {
	left: auto;
	right: 3px;
}

