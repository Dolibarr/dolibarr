<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	    \file       htdocs/comm/action/listactions.php
 *      \ingroup    agenda
 *		\brief      Page to list actions
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

$langs->load("users");
$langs->load("companies");
$langs->load("agenda");
$langs->load("commercial");

$action=GETPOST('action','alpha');
$year=GETPOST("year",'int');
$month=GETPOST("month",'int');
$day=GETPOST("day",'int');
$actioncode=GETPOST("actioncode","alpha",3);
$pid=GETPOST("projectid",'int',3);
$status=GETPOST("status",'alpha');
$type=GETPOST('type');
$optioncss = GETPOST('optioncss','alpha');
$actioncode=GETPOST("actioncode","alpha",3)?GETPOST("actioncode","alpha",3):(GETPOST("actioncode")=='0'?'0':(empty($conf->global->AGENDA_USE_EVENT_TYPE)?'AC_OTH':''));
$dateselect=dol_mktime(0, 0, 0, GETPOST('dateselectmonth'), GETPOST('dateselectday'), GETPOST('dateselectyear'));
$datestart=dol_mktime(0, 0, 0, GETPOST('datestartmonth'), GETPOST('datestartday'), GETPOST('datestartyear'));
$dateend=dol_mktime(0, 0, 0, GETPOST('dateendmonth'), GETPOST('dateendday'), GETPOST('dateendyear'));

if ($actioncode == '') $actioncode=(empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE)?'':$conf->global->AGENDA_DEFAULT_FILTER_TYPE);
if ($status == ''   && ! isset($_GET['status']) && ! isset($_POST['status'])) $status=(empty($conf->global->AGENDA_DEFAULT_FILTER_STATUS)?'':$conf->global->AGENDA_DEFAULT_FILTER_STATUS);
if (empty($action) && ! isset($_GET['action']) && ! isset($_POST['action'])) $action=(empty($conf->global->AGENDA_DEFAULT_VIEW)?'show_month':$conf->global->AGENDA_DEFAULT_VIEW);

$filter=GETPOST("filter",'',3);
$filtert = GETPOST("usertodo","int",3)?GETPOST("usertodo","int",3):GETPOST("filtert","int",3);
$usergroup = GETPOST("usergroup","int",3);
$showbirthday = empty($conf->use_javascript_ajax)?GETPOST("showbirthday","int"):1;

// If not choice done on calendar owner, we filter on user.
if (empty($filtert) && empty($conf->global->AGENDA_ALL_CALENDARS))
{
	$filtert=$user->id;
}

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder)
{
	$sortorder="DESC";
	if ($status == 'todo') $sortorder="DESC";
	//if ($status == 'done') $sortorder="DESC";
}
if (! $sortfield)
{
	$sortfield="a.datep";
	if ($status == 'todo') $sortfield="a.datep";
	//if ($status == 'done') $sortfield="a.datep2";
}

// Security check
$socid = GETPOST("socid",'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'agenda', 0, '', 'myactions');
if ($socid < 0) $socid='';

$canedit=1;
if (! $user->rights->agenda->myactions->read) accessforbidden();
if (! $user->rights->agenda->allactions->read) $canedit=0;
if (! $user->rights->agenda->allactions->read || $filter=='mine')	// If no permission to see all, we show only affected to me
{
	$filtert=$user->id;
}

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $datestart='';
    $dateend='';
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('agendalist'));


/*
 *	Actions
 */

if (GETPOST("viewcal") || GETPOST("viewweek") || GETPOST("viewday"))
{
	$param='';
	foreach($_POST as $key => $val)
	{
		$param.='&'.$key.'='.urlencode($val);
	}
	//print $param;
	header("Location: ".DOL_URL_ROOT.'/comm/action/index.php?'.$param);
	exit;
}



/*
 *  View
 */

$form=new Form($db);
$userstatic=new User($db);

$nav='';
$nav.='<form name="dateselect" action="'.$_SERVER["PHP_SELF"].'?action=show_peruser'.$param.'">';
if ($optioncss != '') $nav.= '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
if ($actioncode || isset($_GET['actioncode']) || isset($_POST['actioncode'])) $nav.='<input type="hidden" name="actioncode" value="'.$actioncode.'">';
if ($status || isset($_GET['status']) || isset($_POST['status']))  $nav.='<input type="hidden" name="status" value="'.$status.'">';
if ($filter)  $nav.='<input type="hidden" name="filter" value="'.$filter.'">';
if ($filtert) $nav.='<input type="hidden" name="filtert" value="'.$filtert.'">';
if ($socid)   $nav.='<input type="hidden" name="socid" value="'.$socid.'">';
if ($showbirthday)  $nav.='<input type="hidden" name="showbirthday" value="1">';
if ($pid)    $nav.='<input type="hidden" name="projectid" value="'.$pid.'">';
if ($type)   $nav.='<input type="hidden" name="type" value="'.$type.'">';
if ($usergroup) $nav.='<input type="hidden" name="usergroup" value="'.$usergroup.'">';
$nav.=$form->select_date($dateselect, 'dateselect', 0, 0, 1, '', 1, 0, 1);
$nav.=' <input type="submit" name="submitdateselect" class="button" value="'.$langs->trans("Refresh").'">';
$nav.='</form>';

$now=dol_now();

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);

// Define list of all external calendars
$listofextcals=array();

$param='';
if ($actioncode || isset($_GET['actioncode']) || isset($_POST['actioncode'])) $param.="&actioncode=".$actioncode;
if ($status || isset($_GET['status']) || isset($_POST['status'])) $param.="&status=".$status;
if ($filter) $param.="&filter=".$filter;
if ($filtert) $param.="&filtert=".$filtert;
if ($socid) $param.="&socid=".$socid;
if ($showbirthday) $param.="&showbirthday=1";
if ($pid) $param.="&projectid=".$pid;
if ($type) $param.="&type=".$type;
if ($usergroup) $param.="&usergroup=".$usergroup;
if ($optioncss != '') $param.='&optioncss='.$optioncss;

$sql = "SELECT";
if ($usergroup > 0) $sql.=" DISTINCT";
$sql.= " s.nom as societe, s.rowid as socid, s.client,";
$sql.= " a.id, a.label, a.datep as dp, a.datep2 as dp2,";
$sql.= ' a.fk_user_author,a.fk_user_action,';
$sql.= " a.fk_contact, a.note, a.percent as percent,";
$sql.= " c.code as type_code, c.libelle as type_label,";
$sql.= " sp.lastname, sp.firstname";
$sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."actioncomm as a";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
// We must filter on assignement table
if ($filtert > 0 || $usergroup > 0) $sql.=", ".MAIN_DB_PREFIX."actioncomm_resources as ar";
if ($usergroup > 0) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
$sql.= " WHERE c.id = a.fk_action";
$sql.= ' AND a.entity IN ('.getEntity('agenda', 1).')';
if ($actioncode) $sql.=" AND c.code='".$db->escape($actioncode)."'";
if ($pid) $sql.=" AND a.fk_project=".$db->escape($pid);
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND (a.fk_soc IS NULL OR sc.fk_user = " .$user->id . ")";
if ($socid > 0) $sql.= " AND s.rowid = ".$socid;
// We must filter on assignement table
if ($filtert > 0 || $usergroup > 0) $sql.= " AND ar.fk_actioncomm = a.id AND ar.element_type='user'";
if ($type) $sql.= " AND c.id = ".$type;
if ($status == '0') { $sql.= " AND a.percent = 0"; }
if ($status == '-1') { $sql.= " AND a.percent = -1"; }	// Not applicable
if ($status == '50') { $sql.= " AND (a.percent > 0 AND a.percent < 100)"; }	// Running already started
if ($status == 'done' || $status == '100') { $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep2 <= '".$db->idate($now)."'))"; }
if ($status == 'todo') { $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep2 > '".$db->idate($now)."'))"; }
// We must filter on assignement table
if ($filtert > 0 || $usergroup > 0)
{
    $sql.= " AND (";
    if ($filtert > 0) $sql.= "ar.fk_element = ".$filtert;
    if ($usergroup > 0) $sql.= ($filtert>0?" OR ":"")." ugu.fk_usergroup = ".$usergroup;
    $sql.= ")";
}

// The second or of next test is to take event with no end date (we suppose duration is 1 hour in such case)
if ($dateselect > 0) $sql.= " AND ((a.datep2 >= '".$db->idate($dateselect)."' AND a.datep <= '".$db->idate($dateselect+3600*24-1)."') OR (a.datep2 IS NULL AND a.datep > '".$db->idate($dateselect-3600)."' AND a.datep <= '".$db->idate($dateselect+3600*24-1)."'))";
if ($datestart > 0) $sql.= " AND a.datep BETWEEN '".$db->idate($datestart)."' AND '".$db->idate($datestart+3600*24-1)."'";
if ($dateend > 0) $sql.= " AND a.datep2 BETWEEN '".$db->idate($dateend)."' AND '".$db->idate($dateend+3600*24-1)."'";
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1, $offset);
//print $sql;

dol_syslog("comm/action/listactions.php", LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
	$actionstatic=new ActionComm($db);
	$societestatic=new Societe($db);

	$num = $db->num_rows($resql);

	/*$title=$langs->trans("DoneAndToDoActions");
	if ($status == 'done') $title=$langs->trans("DoneActions");
	if ($status == 'todo') $title=$langs->trans("ToDoActions");
	*/
	$title=$langs->trans("ListOfEvents");

	$newtitle=$langs->trans($title);

	$tabactive='cardlist';

	$head = calendars_prepare_head($param);

    dol_fiche_head($head, $tabactive, $langs->trans('Agenda'), 0, 'action');
    print_actions_filter($form,$canedit,$status,$year,$month,$day,$showbirthday,0,$filtert,0,$pid,$socid,$action,-1,$actioncode,$usergroup);
    dol_fiche_end();

    // Add link to show birthdays
    $link='';
    /*
    if (empty($conf->use_javascript_ajax))
    {
        $newparam=$param;   // newparam is for birthday links
        $newparam=preg_replace('/showbirthday=[0-1]/i','showbirthday='.(empty($showbirthday)?1:0),$newparam);
        if (! preg_match('/showbirthday=/i',$newparam)) $newparam.='&showbirthday=1';
        $link='<a href="'.$_SERVER['PHP_SELF'];
        $link.='?'.$newparam;
        $link.='">';
        if (empty($showbirthday)) $link.=$langs->trans("AgendaShowBirthdayEvents");
        else $link.=$langs->trans("AgendaHideBirthdayEvents");
        $link.='</a>';
    }
    */

    $s=$newtitle;

	// Calendars from hooks
    $parameters=array(); $object=null;
	$reshook=$hookmanager->executeHooks('addCalendarChoice',$parameters,$object,$action);
    if (empty($reshook))
    {
		$s.= $hookmanager->resPrint;
    }
    elseif ($reshook > 1)
	{
    	$s = $hookmanager->resPrint;
    }

    print_barre_liste($s, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $link, $num, 0, '', 0, $nav);


	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'?'.$param.'">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';

	$i = 0;
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"a.label",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"],"a.datep",$param,'','align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateEnd"),$_SERVER["PHP_SELF"],"a.datep2",$param,'','align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Contact"),$_SERVER["PHP_SELF"],"a.fk_contact",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ActionsOwnedBy"),$_SERVER["PHP_SELF"],"",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"a.percent",$param,"",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre" align="center">';
	print $form->select_date($datestart, 'datestart', 0, 0, 1, '', 1, 0, 1);
	print '</td>';
	print '<td class="liste_titre" align="center">';
	print $form->select_date($dateend, 'dateend', 0, 0, 1, '', 1, 0, 1);
	print '</td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	//print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '</td>';
	print "</tr>\n";

	$contactstatic = new Contact($db);
	$now=dol_now();
	$delay_warning=$conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;

	$var=true;
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);

        // Discard auto action if option is on
        if (! empty($conf->global->AGENDA_ALWAYS_HIDE_AUTO) && $obj->type_code == 'AC_OTH_AUTO')
        {
        	$i++;
        	continue;
        }

		$var=!$var;

		print "<tr ".$bc[$var].">";

		// Action (type)
		print '<td>';
		$actionstatic->id=$obj->id;
		$actionstatic->type_code=$obj->type_code;
		$actionstatic->type_label=$obj->type_label;
		$actionstatic->label=$obj->label;
		print $actionstatic->getNomUrl(1,28);
		print '</td>';

		// Start date
		print '<td align="center" class="nowrap">';
		print dol_print_date($db->jdate($obj->dp),"dayhour");
		$late=0;
		if ($obj->percent == 0 && $obj->dp && $db->jdate($obj->dp) < ($now - $delay_warning)) $late=1;
		if ($obj->percent == 0 && ! $obj->dp && $obj->dp2 && $db->jdate($obj->dp) < ($now - $delay_warning)) $late=1;
		if ($obj->percent > 0 && $obj->percent < 100 && $obj->dp2 && $db->jdate($obj->dp2) < ($now - $delay_warning)) $late=1;
		if ($obj->percent > 0 && $obj->percent < 100 && ! $obj->dp2 && $obj->dp && $db->jdate($obj->dp) < ($now - $delay_warning)) $late=1;
		if ($late) print img_warning($langs->trans("Late")).' ';
		print '</td>';

		// End date
		print '<td align="center" class="nowrap">';
		print dol_print_date($db->jdate($obj->dp2),"dayhour");
		print '</td>';

		// Third party
		print '<td>';
		if ($obj->socid)
		{
			$societestatic->id=$obj->socid;
			$societestatic->client=$obj->client;
			$societestatic->name=$obj->societe;
			print $societestatic->getNomUrl(1,'',10);
		}
		else print '&nbsp;';
		print '</td>';

		// Contact
		print '<td>';
		if ($obj->fk_contact > 0)
		{
			$contactstatic->lastname=$obj->lastname;
			$contactstatic->firstname=$obj->firstname;
			$contactstatic->id=$obj->fk_contact;
			print $contactstatic->getNomUrl(1,'',10);
		}
		else
		{
			print "&nbsp;";
		}
		print '</td>';

		// User to do
		print '<td align="left">';
		if ($obj->fk_user_action > 0)
		{
			$userstatic->fetch($obj->fk_user_action);
			print $userstatic->getLoginUrl(1);
		}
		else print '&nbsp;';
		print '</td>';

		// Status/Percent
		print '<td align="right" class="nowrap">'.$actionstatic->LibStatut($obj->percent,6).'</td>';

		print "</tr>\n";
		$i++;
	}
	print "</table>";

	print '</form>';

	$db->free($resql);

}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();
