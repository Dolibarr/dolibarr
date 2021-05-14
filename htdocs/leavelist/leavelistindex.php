<?php
//session_start();
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */



/**
 *	\file       htdocs/leavelist/template/leavelistindex.php
 *	\ingroup    leavelist
 *	\brief      Home page of leavelist top menu
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
// coz we need hrm classes we need to include it
require_once DOL_DOCUMENT_ROOT.'/hrm/class/establishment.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
// Load translation files required by the page
$langs->loadLangs(array("leavelist@leavelist"));
$langs->loadLangs(array('holidays'));
$langs->load("boxes");

$action=GETPOST('action', 'alpha');

// Securite acces client
// path to the general css style

if (! $user->rights->leavelist->read) accessforbidden();
$socid=GETPOST('socid', 'int');
if (isset($user->societe_id) && $user->societe_id > 0) {
	$action = '';
	$socid = $user->societe_id;
}

$max=5;
$now=dol_now();

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("LeaveListArea"));
// print load_fiche_titre($langs->trans("LeaveListArea"),'','leavelist.png@leavelist');
//
//
// search section
//
//

 // k is the variable reserved for switching between menus
print '
<div class="search-header">
	<table class="noborder boxtable boxtablenobottom boxworkingboard">
		<tbody>
			<tr class="liste_titre">
			<th class="liste_titre">
			'.$langs->trans("Searchfor:").'
			</th>
			</tr>
		</tbody>
	</table>
</div>';
/*
// radio buttons
echo '<span class="search-f">selection per:
  <input type="radio" name="selType" id="selEmployee" value="team" onchange="callGroup()" checked> employee
  <input type="radio" name="selType" id="selTeam" value="employee" onchange="callGroup()"> team<br>
  </span>
';*/
/*if($user->rights->holiday->read_all||$user->rights->holiday->approve) // condition on permissions
{print 'permission for super admin';
}*/
// // creating an array to insert hr team coz we can't get them using sql request calling another one
$array_of_hr_workers=array();
$sql = "SELECT u.rowid as ref ,x.fk_user , x.entity, x.fk_id";
$sql.= " FROM ".MAIN_DB_PREFIX."user_rights as x, ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE u.rowid = x.fk_user ";
$sql.= " AND x.entity = ".$conf->entity;
$req=$db->query($sql);
$hr_array_index = 0 ;
if ($req) {
	// $holidaystatic = new \stdClass();

	$k = $db->num_rows($req);

	$j = 0;
	if ($k) {
		while ($j < $k) {
			$db_var = $db->fetch_object($req);
			if ($db_var) {    // You can use here results
				// search if the current element has the admin permission or it is in the array of hr team
				if ((($db_var->fk_id) == 20007 || ($db_var->fk_id) == 20004)&&(!in_array($db_var->ref, $array_of_hr_workers))) {
					$array_of_hr_workers[$hr_array_index] = ($db_var->ref); // insert hr team in array
					$hr_array_index++; // incrementing index of array
				}
			}

			$j++;
		}
		$db->free($req);
	}
}
if (isset($_POST['button1'])) { // could be placed here to change session after first submit for placeholder
	$_SESSION['button1'] = $_POST['button1'];
	$_SESSION['start'] = $_POST['start'];
	$_SESSION['end'] = $_POST['end'];
	$_SESSION['employee'] = $_POST['employee'];
	$_SESSION['checkbox_option'] = $_POST['checkbox_option'];
}
// database connection for employee names display in input fields
			$sql = "SELECT u.rowid as uid, u.lastname, u.firstname";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$resql=$db->query($sql);
			print '<div class="search-fields" >  <!-- les inputs du form -->
	<form action="leavelistindex.php" method="post" id="formData" style="display: inline;">

		<div class="container-fluid" >
  			'.$langs->trans("Employeename").' 
  			<select placeholder="Yours Placeholder" id="employee" multiple="multiple" style="width:80%" name="employee[]" class="select2-multi-col" required>'; // select employee premiére valeur par défaut vide
// condition on permissions
if ($user->rights->holiday->read_all||$user->rights->holiday->approve) { // condition on permissions
	if ($resql) {
		$userstatic = new User($db);
		$num = $db->num_rows($resql);


		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				if ($obj) {
					// check if session array is already used
					if (isset($_SESSION['employee'])) {
						print '<option value="' . $obj->uid . '" ';
						echo (in_array($obj->uid, $_SESSION['employee'])) ? ' selected' : '';
						echo '>' . $obj->firstname . ' ' . $obj->lastname . '</option>';  // display employee first and last name in options
					} else {
						print '<option value="' . $obj->uid . '" >' . $obj->firstname . ' ' . $obj->lastname . '</option>';
					}
				}
				$i++;
			}
			$db->free($resql);
		}
	}
} else {
	if ($resql) {
		$userstatic = new User($db);
		$num = $db->num_rows($resql);


		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				if ($obj) {
					// check if employee is one of the hr team (user rights verif)
					if (!in_array($obj->uid, $array_of_hr_workers)) {
						// check if session array is already used
						if (isset($_SESSION['employee'])) {
							print '<option value="' . $obj->uid . '" ';
							echo (in_array($obj->uid, $_SESSION['employee'])) ? ' selected' : '';
							echo '>' . $obj->firstname . ' ' . $obj->lastname . '</option>'; // display employee first and last name in options
						} else {
							print '<option value="' . $obj->uid . '" >' . $obj->firstname . ' ' . $obj->lastname . '</option>';
						}
					}
				}
				$i++;
			}
			$db->free($resql);
		}
	}
}


			print ' 	</select>
  		</div>';
// section for search per group initially hidden
/*$sql = "SELECT g.rowid, g.nom as name";
$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$nbtotalofrecords = $num;
	$i = 0;
	$text = $langs->trans("ListOfGroups");
	$grouptemp = new UserGroup($db);
	print'<span class="search-f" id="search-f-group" style="display:none;">
	Group name :
	  <select  id="group" name="group" ><option value="">None</option>';
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		$grouptemp->id = $obj->rowid;
		$grouptemp->name = $obj->name;
		if ($obj) {
			print '<option value="' . $grouptemp->id . '" >' . $grouptemp->name . '</option>';  // display employee first and last name in options
		}
		$i++;
	}
	$db->free($resql);
}
print '	</select>
		  </span>';*/
// date inputs :  //'; echo empty($_SESSION['start']) ? '' : 'placeholder="'.$_SESSION['start'].'"'; echo '
print'  <!-- date inputs -->';

	print '
  <div>
  		<span class="search-f">
		 '.$langs->trans("From:").' 
​			<input type="date" '; echo empty($_SESSION['start']) ? '' : 'placeholder="'.$_SESSION['start'].'"'; echo ' onClick="$(this).removeClass(\'placeholderclass\')" id="start"  class="dateclass placeholderclass" name="start"   required>  <!-- onchange="jump();" -->
		</span>
 		<span class="search-f-endtime"> 
		 '.$langs->trans("To:").'  
			​<input type="date" '; echo empty($_SESSION['end']) ? '' : 'placeholder="'.$_SESSION['end'].'"'; echo ' onClick="$(this).removeClass(\'placeholderclass\')"  class="dateclass placeholderclass" id="end" name="end"  required>
		</span>
  </div>
 		<span class="search-f required" > '.$langs->trans("Searchfor:").'  <!-- requests filter using checkbox-->
 			 <input type="checkbox" class="filter_for_req" class="checkbox_option" name="checkbox_option[]"  value="3" onchange="conditionOnCheckbox();" '; if (isset($_SESSION['checkbox_option'])) {print (in_array(3, $_SESSION['checkbox_option'])) ? ' checked' : '';} print ' required> '.$langs->trans("Approved").'
 			 <input type="checkbox" class="filter_for_req" class="checkbox_option" name="checkbox_option[]" value="2" onchange="conditionOnCheckbox();"  '; if (isset($_SESSION['checkbox_option'])) {print (in_array(2, $_SESSION['checkbox_option'])) ? ' checked' : '';} print ' required> '.$langs->trans("Awaitingforapproval").'
 			 <input type="checkbox" class="filter_for_req" class="checkbox_option" name="checkbox_option[]" value="1" onchange="conditionOnCheckbox();"  '; if (isset($_SESSION['checkbox_option'])) {print (in_array(1, $_SESSION['checkbox_option'])) ? ' checked' : '';} print ' required> '.$langs->trans("Draft").'
 		</span></br>
			<input type="hidden" name="k" value="'; echo htmlentities($_GET['k']); echo '" >
			<input type="submit" class="button" id="search_button" name="button1" value="'.$langs->trans("Search").'"  required> 
	
​​	</form> 
</div>    <!-- les inputs du form -->
';
//
//
// storing form variable into sessions to be accessible even without sending them through $_get
//
//

//
//
// calendars
//
//
if (isset($_SESSION['button1'])) { // can't insert condition on k variable here coz it may make a displaying error
	   $holidaystatic = new Holiday($db); // for the types of leaves with icons on the head of calendars
		// calendars displaying
		print '<div class="search-header">
		<table class="noborder boxtable boxtablenobottom boxworkingboard">
				<tbody>
				<tr class="liste_titre">
				<th class="liste_titre">
				'.$langs->trans("Results:").'
				</th>
				</tr>
				</tbody>
		</table>
				</div>';
		print '</br></br>
		<div class="container col-sm-4 col-md-7 col-lg-4 mt-5" id="calendars-container" style="display:none"> <!-- using display none to avoid displaying without results -->
		<div id="information-for-icons">
		<span id="circle-approved"></span>'. $holidaystatic->LibStatut(3, 5).'<span id="circle-awaiting"></span>'. $holidaystatic->LibStatut(2, 5).'<span id="circle-draft"></span>'. $holidaystatic->LibStatut(1, 5).'
</div>
		<!--  first calendar display -->
		<div class="calendar-button" >
        <button   class="button" id="previous" onclick="previousClicked()">'.$langs->trans("Previous").'</button><!-- previous button on calendar 
		--></div><!--
    	--><div class="card" id="card1">
        	<h3 class="card-header" id="monthAndYear"></h3>
        	<table class="table table-bordered table-responsive-sm" id="calendar" >
            <thead>
            <tr>
                <th >'.$langs->trans("Sun").'</th>
                <th >'.$langs->trans("Mon").'</th>
                <th >'.$langs->trans("Tue").'</th>
                <th >'.$langs->trans("Wed").'</th>
                <th >'.$langs->trans("Thu").'</th>
                <th >'.$langs->trans("Fri").'</th>
                <th >'.$langs->trans("Sat").'</th>
            </tr>
            </thead>

            <tbody id="calendar-body">

            </tbody>
        	</table>
		</div><!--
    --><!-- second calendar display : --><!--
    	--><div class="card" id="card2">
        	<h3 class="card-header" id="monthAndYear2"></h3>
        	<table class="table table-bordered table-responsive-sm" id="calendar2">
            <thead>
            <tr>
               <th >'.$langs->trans("Sun").'</th>
                <th >'.$langs->trans("Mon").'</th>
                <th >'.$langs->trans("Tue").'</th>
                <th >'.$langs->trans("Wed").'</th>
                <th >'.$langs->trans("Thu").'</th>
                <th >'.$langs->trans("Fri").'</th>
                <th >'.$langs->trans("Sat").'</th>
            </tr>
            </thead>

            <tbody id="calendar-body2">

            </tbody>
        	</table>
		</div><!--
    
    --><!-- third calendar display : --><!--
    	--><div class="card" id="card3">
        	<h3 class="card-header" id="monthAndYear3"></h3>
        	<table class="table table-bordered table-responsive-sm" id="calendar3">
            <thead>
            <tr>
                <th >'.$langs->trans("Sun").'</th>
                <th >'.$langs->trans("Mon").'</th>
                <th >'.$langs->trans("Tue").'</th>
                <th >'.$langs->trans("Wed").'</th>
                <th >'.$langs->trans("Thu").'</th>
                <th >'.$langs->trans("Fri").'</th>
                <th >'.$langs->trans("Sat").'</th>
            </tr>
            </thead>

            <tbody id="calendar-body3">

            </tbody>
        	</table>
		</div><!--
    	--><div class="calendar-button">
            <button  class="button" id="next" onclick="nextClicked()">'.$langs->trans("Next").'</button> <!-- next button on calendar-->
		</div>
		</div> ';
}

print '
<!-- button jump implementation <button name="jump" onclick="jump()">Go</button>-->
<!--<script src="scripts.js"></script>--><!-- file is already attached to php -->
<!-- including charts scripts -->
<script src="https://d3js.org/d3.v3.min.js"></script>
<!-- Optional JavaScript for bootstrap -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<!--<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" 
integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://unpkg.com/tippy.js@4"></script> <!-- including tippyes -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>  
        <!-- multiselect source cdn -->
     <!--   <script src="https://cdnjs.cloudflare.com/ajax/libs/multi-select/0.9.12/js/jquery.multi-select.js" type="text/javascript"></script>-->
     
       ';
// condition and date and display calendars with filter
if (isset($_SESSION['button1'])/*&&(isset($_POST['k'])||isset($_GET['k']))*/) {
	//  if ($_POST['k']==1||$_GET['k']==1)
	if (($_SESSION['start'])>($_SESSION['end'])) { // if starting date entered is higher than ending date
		print '<div class="isa_warning">
     	<i class="fa fa-warning"></i>
     	'.$langs->trans("Enddateshouldbehigherthanstartdate").'
		</div>';
	} //condition on dates
	else {
		if (isset($_SESSION['employee'])) {   // employee search case
			$array_of_searched_dates = array(array()); // array containing leave dates for each employee searched
			$start_string = $_SESSION['start'];// the date is still string type
			$end_string = $_SESSION['end']; // the date is still string type
			$array_of_employee_id = $_SESSION['employee']; // get employee id from post
			$start = date('Y-m-d', strtotime($start_string)); // convert to time to give us the possibility to compare it
			$end = date('Y-m-d', strtotime($end_string));  // convert to time to give us the possibility to compare it
			$array_of_leave_status_filter = $_SESSION['checkbox_option']; // still missing condition on it when empty !!
			$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.statut, x.rowid, x.rowid as ref, x.fk_type, x.date_debut as date_start, x.date_fin as date_end, x.statut as status";
			$sql .= " FROM " . MAIN_DB_PREFIX . "holiday as x, " . MAIN_DB_PREFIX . "user as u";
			$sql .= " WHERE (u.rowid = x.fk_user) AND (u.rowid IN (" . implode(',', $array_of_employee_id) . ")) AND (x.statut IN (" . implode(',', $array_of_leave_status_filter) . "))";
			$sql .= " AND x.entity = " . $conf->entity;
		}
		/*else{ // case of group search
			$array_of_searched_dates = array(array()); // array containing leaves dates for each employee searched
			$start_string = $_POST['start'];// the date is still string type
			$end_string = $_POST['end']; // the date is still string type
			$group_id = $_POST['group']; // get employee id from post
			$start = date('Y-m-d', strtotime($start_string)); // convert to time to give us the possibility to compare it
			$end = date('Y-m-d', strtotime($end_string));  // convert to time to give us the possibility to compare it
			$array_of_leave_status_filter = $_POST['checkbox_option']; // still missing condition on it when empty !!
			$sql = "SELECT g.rowid, g.nom as name, g.note, g.entity, g.datec, ugu.fk_user, ugu.fk_usergroup,  x.rowid, x.rowid as ref, x.fk_type, x.date_debut as date_start, x.date_fin as date_end, x.statut as status";
			$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g, " .MAIN_DB_PREFIX."usergroup_user as ugu, " . MAIN_DB_PREFIX . "holiday as x";
			$sql .= " WHERE (g.rowid = " . $group_id . ") AND (ugu.fk_usergroup = g.rowid) AND (ugu.fk_user = x.fk_user) AND (x.statut IN (" . implode(',', $array_of_leave_status_filter) . "))";
		}*/
			$resql = $db->query($sql);
		if ($resql) {
			$holidaystatic = new Holiday($db);
			$userstatic = new User($db);
			$num = $db->num_rows($resql);
			$typeleaves = $holidaystatic->getTypes(1, -1);
			$i = 0;
			$array_index = 0; // cannot use $i for index because it can't be reached
			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					if ($obj) {
						// You can use here results
						$date_start = date('Y-m-d', strtotime($obj->date_start));
						$date_end = date('Y-m-d', strtotime($obj->date_end));
						if ((($date_start <= $end) && ($date_start >= $start)) && ((($date_end <= $end) && ($date_end >= $start)))) {
							$array_of_searched_dates[$array_index][0] = $obj->date_start;
							$array_of_searched_dates[$array_index][1] = $obj->date_end;
							$array_of_searched_dates[$array_index][2] = $obj->status;
							$array_of_searched_dates[$array_index][3] = $obj->firstname.' '.$obj->lastname;
							$array_index++;
						} elseif ((($date_start <= $end) && ($date_start >= $start)) && !((($date_end <= $end) && ($date_end >= $start)))) {
							$date1 = date_parse_from_format("Y-m-d", $date_end);
							$date2 = date_parse_from_format("Y-m-d", $end);
							if ($date1["month"] == $date2["month"]) {
								$array_of_searched_dates[$array_index][0] = $obj->date_start;
								$array_of_searched_dates[$array_index][1] = $obj->date_end;
								$array_of_searched_dates[$array_index][2] = $obj->status;
								$array_of_searched_dates[$array_index][3] = $obj->firstname.' '.$obj->lastname;
								$array_index++;
							} else {
								$date_end = date("Y-m-t", strtotime($end_string));
								// last takes last day of month if not same month with end
								//$array_of_searched_dates[$array_index][1]=month and last day of last
								$array_of_searched_dates[$array_index][0] = $obj->date_start;
								$array_of_searched_dates[$array_index][1] = $date_end;
								$array_of_searched_dates[$array_index][2] = $obj->status;
								$array_of_searched_dates[$array_index][3] = $obj->firstname.' '.$obj->lastname;
								$array_index++;
							}
						} elseif ((!(($date_start <= $end) && ($date_start >= $start)) && ((($date_end <= $end) && ($date_end >= $start)))) || (((($date_start < $end) && ($date_start < $start)) && ((($date_end > $end) && ($date_end > $start)))))) {
							$date1 = date_parse_from_format("Y-m-d", $date_start);
							$date2 = date_parse_from_format("Y-m-d", $start);
							if ($date1["month"] == $date2["month"]) {
								$array_of_searched_dates[$array_index][0] = $obj->date_start;
								$array_of_searched_dates[$array_index][1] = $obj->date_end;
								$array_of_searched_dates[$array_index][2] = $obj->status;
								$array_of_searched_dates[$array_index][3] = $obj->firstname.' '.$obj->lastname;
								$array_index++;
							} else {
								$date_start = date("Y-m-01", strtotime($start_string));
								$array_of_searched_dates[$array_index][0] = $date_start;
								// first takes first date of month if not same month with last date
								//$array_of_searched_dates[$array_index][0]=month and first day of start
								$array_of_searched_dates[$array_index][1] = $obj->date_end;
								$array_of_searched_dates[$array_index][2] = $obj->status;
								$array_of_searched_dates[$array_index][3] = $obj->firstname.' '.$obj->lastname;
								$array_index++;
							}
						}
					}
					$i++;
				}
			}
			if ((($array_of_searched_dates[0][0] == null) && ($array_of_searched_dates[0][1] == null))) { // display info message on no results
				echo '<div class="isa_info">
    						<i class="fa fa-info-circle"></i>
    						'.$langs->trans("Noelementsrelatedtoyoursearch").'
							</div>';
			}

				$days_array_json = json_encode($array_of_searched_dates); // to send array to javascript using *json*
				$db->free($resql);
			if (($_GET['k'] == 1) || (($_GET['k'] == null) && ($_POST['k'] == null)) || ($_POST['k'] == 1)) { // display only calendars for value of k
				echo '<script type="text/javascript">',
					'jumpToLeaveDate(' . $days_array_json . ');',
				'</script>';
			}
		}
	}
}
//
//
// display charts
//
//
print "<div id='dashboard'></div>";
//
//
// display table on "display table"
//
//
		print '<div class="div-table-responsive" id="table-responsive" style="display:none" >
		<table class="centpercent notopnoleftnoright" >
  			<!-- <caption>list of leaves</caption> -->

   			<thead  > <!-- En-tête du tableau -->
       		<tr class="liste_titre" >
           <th>'.$langs->trans("Employee").'</th>
           <th>'.$langs->trans("Type").'</th>
           <th>'.$langs->trans("Startdate").'</th>
           <th>'.$langs->trans("Enddate").'</th>
           <th>'.$langs->trans("Status").'</th>
           <th>'.$langs->trans("Numberofdays").'</th>
           
       		</tr>
   			</thead>
			<tbody> '; // Corps du tableau
			$start_string = $_SESSION['start'];// the date is still string type
			$end_string = $_SESSION['end']; // the date is still string type
			$array_of_employee_id = $_SESSION['employee'];
			$start = date('Y-m-d', strtotime($start_string));
			$end = date('Y-m-d', strtotime($end_string));
			$sum_of_columns = 0;

			$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.statut, x.rowid, x.rowid as ref, x.fk_type, x.date_debut as date_start, x.date_fin as date_end, x.statut as status";
			$sql .= " FROM " . MAIN_DB_PREFIX . "holiday as x, " . MAIN_DB_PREFIX . "user as u";
			$sql .= " WHERE (u.rowid = x.fk_user) AND (u.rowid IN (" . implode(',', $array_of_employee_id) . ")) AND (x.statut IN (" . implode(',', $array_of_leave_status_filter) . "))";
			$sql .= " AND x.entity = " . $conf->entity;
			$resql = $db->query($sql);
if ($resql) {
	$holidaystatic = new Holiday($db);
	$userstatic = new User($db);
	$num = $db->num_rows($resql);
	$typeleaves = $holidaystatic->getTypes(1, -1);
	$i = 0;
	$table_index = 0;
	if ($num) {
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$userstatic->id = $obj->uid;
				$userstatic->lastname = $obj->lastname;
				$userstatic->firstname = $obj->firstname;
				$userstatic->login = $obj->login;
				$userstatic->photo = $obj->photo;
				$userstatic->email = $obj->email;
				$userstatic->statut = $obj->status;
				$userstatic->start_date = $obj->date_start;
				$userstatic->end_date = $obj->date_end;
				$date_start = date('Y-m-d', strtotime($obj->date_start));
				$date_end = date('Y-m-d', strtotime($obj->date_end));
				$dateDiff = (int) date_diff(date_create($date_end), date_create($date_start))->format('%a');
				$dateDiff++ ;
				if ((($date_start <= $end) && ($date_start >= $start)) || ((($date_end <= $end) && ($date_end >= $start))) || (((($date_start < $end) && ($date_start < $start)) && ((($date_end > $end) && ($date_end > $start)))))) { // create new table cell
					echo '    <tr ';
					if ($table_index % 2 == 0) echo ' class="pair" '; else echo ' class="impair" ';
					echo '>
        			   		<td>' . $userstatic->getNomUrl(-1, 'leave') . '</td>
      			  	   		<td>' . $typeleaves[$obj->fk_type]['label'] . '</td>
        			   		<td>' . $obj->date_start . '</td>
        			   		<td>' . $obj->date_end . '</td>
           					<td>' . $holidaystatic->LibStatut($obj->status, 5) . '</td>
           					<td>' . $dateDiff. '</td>
       						</tr> ';
					$sum_of_columns = $dateDiff + $sum_of_columns;
					$table_index++;
				}
			}
			$i++;
		}
				$db->free($resql);
	}
}
		echo '<tr class="pair">   <!-- dsiplay total -->
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td style="text-align: center;padding:0.5em 0.5em 0.5em 0.5em ;background: #ccd3d3;font-family: \'Britannic Bold\';" >'.$langs->trans("Total:").'</td>
		<td>' . $sum_of_columns . '</td>
		</tr>';
		echo '  </tbody>
		</table>
		</div>';
//
//
//display by left menu selection
//
//
//if (isset($_GET['k'])||isset($_POST['k']))
{
if (!(($array_of_searched_dates[0][0] == null) && ($array_of_searched_dates[0][1] == null))) { // to call displaying functions only if there is results
	if (($_GET['k'] == 1) || (($_GET['k'] == null) && ($_POST['k'] == null)) || ($_POST['k'] == 1)) { // $_GET['k']==null)&&($_POST['k']==null on first page loading only
		// display calendars
		$k = 1;
		echo '<script type="text/javascript">',
			'switchMenu(' . $k . ');',
		'</script>';
	} elseif (($_GET['k'] == 2) || ($_POST['k'] == 2)) {
		// display table
		$k = 2;
		echo '<script type="text/javascript">',
			'switchMenu(' . $k . ');',
		'</script>';
	} elseif (($_GET['k'] == 3) || ($_POST['k'] == 3)) {
		// display charts
		$k = 3;
		echo '<script type="text/javascript">',
			'switchMenu(' . $k . ');',
		'</script>';
		echo '<script type="text/javascript">',
			'countStatusPerMonth(' . $days_array_json . ');',
		'</script>';
	}
}
}

// les numeros correspondants au demande de congé
//
// 1  Draft
// 2  Awaiting approval
// 3  Approved
// 4  Canceled
// 5  Refused
//
print '<!--</div>--><div class="fichetwothirdright"><div class="ficheaddleft">';


$NBMAX=3;
$max=3;


print '</div></div><!--</div>-->';

llxFooter();

$db->close();
