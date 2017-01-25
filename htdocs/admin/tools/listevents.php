<?php
/* Copyright (C) 2004-2015  Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2015       Bahfir Abbes		<bafbes@gmail.com>
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
 *		\file       htdocs/admin/tools/listevents.php
 *      \ingroup    core
 *      \brief      List of security events
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

if (! $user->admin)
	accessforbidden();

$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm','alpha');

// Security check
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

$langs->load("admin");
$langs->load("companies");
$langs->load("users");
$langs->load("other");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="dateevent";

$search_code = GETPOST("search_code");
$search_ip   = GETPOST("search_ip");
$search_user = GETPOST("search_user");
$search_desc = GETPOST("search_desc");
$search_ua   = GETPOST("search_ua");

if (!isset($_REQUEST["date_startmonth"]) || $_REQUEST["date_startmonth"] > 0) $date_start=dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);
else $date_start=-1;
if (!isset($_REQUEST["date_endmonth"]) || $_REQUEST["date_endmonth"] > 0) $date_end=dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);
else $date_end=-1;

// checks:if date_start>date_end  then date_end=date_start + 24 hours
if ($date_start > 0 && $date_end > 0 && $date_start > $date_end) $date_end=$date_start+86400;

$now = dol_now();
$nowarray = dol_getdate($now);

$params = "&amp;search_code=$search_code&amp;search_ip=$search_ip&amp;search_user=$search_user&amp;search_desc=$search_desc&amp;search_ua=$search_ua";
$params.= "&amp;date_startmonth=".$_REQUEST["date_startmonth"];
$params.= "&amp;date_startday=".$_REQUEST["date_startday"];
$params.= "&amp;date_startyear=".$_REQUEST["date_startyear"];
$params.= "&amp;date_endmonth=".$_REQUEST["date_endmonth"];
$params.= "&amp;date_endday=".$_REQUEST["date_endday"];
$params.= "&amp;date_endyear=".$_REQUEST["date_endyear"];

if (empty($date_start)) // We define date_start and date_end
{
    $date_start=dol_get_first_day($nowarray['year'],$nowarray['mon'],false);
}
if (empty($date_end))
{
    $date_end=dol_mktime(23,59,59,$nowarray['mon'],$nowarray['mday'],$nowarray['year']);
}


/*
 * Actions
 */

$now=dol_now();

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $date_start=-1;
    $date_end=-1;
    $search_code='';
    $search_ip='';
    $search_user='';
    $search_desc='';
    $search_ua='';
}

// Purge audit events
if ($action == 'confirm_purge' && $confirm == 'yes' && $user->admin)
{
	$error=0;

	$db->begin();
	$securityevents=new Events($db);

	// Delete events
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."events";
	$sql.= " WHERE entity = ".$conf->entity;

	dol_syslog("listevents purge", LOG_DEBUG);
	$resql = $db->query($sql);
	if (! $resql)
	{
		$error++;
		setEventMessages($db->lasterror(), null, 'errors');
	}

	// Add event purge
	$text=$langs->trans("SecurityEventsPurged");
	$securityevent=new Events($db);
	$securityevent->type='SECURITY_EVENTS_PURGE';
	$securityevent->dateevent=$now;
	$securityevent->description=$text;
	$result=$securityevent->create($user);
	if ($result > 0)
	{
	    $db->commit();
		dol_syslog($text, LOG_WARNING);
	}
	else
	{
		$error++;
		dol_syslog($securityevent->error, LOG_ERR);
		$db->rollback();
	}
}


/*
 *	View
 */

llxHeader('',$langs->trans("Audit"));

$form=new Form($db);

$userstatic=new User($db);
$usefilter=0;

$sql = "SELECT e.rowid, e.type, e.ip, e.user_agent, e.dateevent,";
$sql.= " e.fk_user, e.description,";
$sql.= " u.login";
$sql.= " FROM ".MAIN_DB_PREFIX."events as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = e.fk_user";
$sql.= " WHERE e.entity IN (".getEntity('event', 1).")";
if ($date_start > 0) $sql.= " AND e.dateevent >= '".$db->idate($date_start)."'";
if ($date_end > 0)   $sql.= " AND e.dateevent <= '".$db->idate($date_end)."'";
if ($search_code) { $usefilter++; $sql.=natural_search("e.type", $search_code, 0); }
if ($search_ip)   { $usefilter++; $sql.=natural_search("e.ip", $search_ip, 0); }
if ($search_user) { $usefilter++; $sql.=natural_search("u.login", $search_user, 0); }
if ($search_desc) { $usefilter++; $sql.=natural_search("e.description", $search_desc, 0); }
if ($search_ua)   { $usefilter++; $sql.=natural_search("e.user_agent", $search_ua, 0); }
$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
/*if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}*/

$sql.= $db->plimit($conf->liste_limit+1, $offset);
//print $sql;
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	$param='';
	if ($search_code) $param.='&search_code='.$search_code;
	if ($search_ip)   $param.='&search_ip='.$search_ip;
	if ($search_user) $param.='&search_user='.$search_user;
	if ($search_desc) $param.='&search_desc='.$search_desc;
	if ($search_ua)   $param.='&search_ua='.$search_ua;

    $langs->load('withdrawals');
    if ($num)
    {
        $center='<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=purge">'.$langs->trans("Purge").'</a>';
    }
    
	print_barre_liste($langs->trans("ListOfSecurityEvents"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $center, $num, $nbtotalofrecords, 'setup');

	if ($action == 'purge')
	{
		$formquestion=array();
		print $form->formconfirm($_SERVER["PHP_SELF"].'?noparam=noparam', $langs->trans('PurgeAuditEvents'), $langs->trans('ConfirmPurgeAuditEvents'),'confirm_purge',$formquestion,'no',1);
	}
	
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

	print '<div class="div-table-responsive">';
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"e.dateevent","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Code"),$_SERVER["PHP_SELF"],"e.type","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("IP"),$_SERVER["PHP_SELF"],"e.ip","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("User"),$_SERVER["PHP_SELF"],"u.login","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"),$_SERVER["PHP_SELF"],"e.description","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre('');
	print "</tr>\n";


	// Lignes des champs de filtres
	print '<tr class="liste_titre">';

	print '<td class="liste_titre" width="15%">'.$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).$form->select_date($date_end,'date_end',0,0,0,'',1,0,1).'</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_code" value="'.$search_code.'">';
	print '</td>';

	// IP
	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_ip" value="'.$search_ip.'">';
	print '</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_user" value="'.$search_user.'">';
	print '</td>';

	print '<td align="left" class="liste_titre">';
	//print '<input class="flat" type="text" size="10" name="search_desc" value="'.$search_desc.'">';
	print '</td>';

	print '<td align="right" class="liste_titre">';
	$searchpitco=$form->showFilterAndCheckAddButtons(0);
	print $searchpitco;
	print '</td>';

	print "</tr>\n";

	$var=True;

	while ($i < min($num, $conf->liste_limit))
	{
		$obj = $db->fetch_object($result);

		$var=!$var;

		print '<tr '.$bc[$var].'>';

		// Date
		print '<td align="left" class="nowrap">'.dol_print_date($db->jdate($obj->dateevent),'%Y-%m-%d %H:%M:%S').'</td>';

		// Code
		print '<td>'.$obj->type.'</td>';

		// IP
		print '<td class="nowrap">';
		print dol_print_ip($obj->ip);
		print '</td>';

		// Login
		print '<td class="nowrap">';
		if ($obj->fk_user)
		{
			$userstatic->id=$obj->fk_user;
			$userstatic->login=$obj->login;
			print $userstatic->getLoginUrl(1);
		}
		else print '&nbsp;';
		print '</td>';

		// Description
		print '<td>';
		$text=$langs->trans($obj->description);
		if (preg_match('/\((.*)\)(.*)/i',$obj->description,$reg))
		{
			$val=explode(',',$reg[1]);
			$text=$langs->trans($val[0], isset($val[1])?$val[1]:'', isset($val[2])?$val[2]:'', isset($val[3])?$val[3]:'', isset($val[4])?$val[4]:'');
			if (! empty($reg[2])) $text.=$reg[2];
		}
		print $text;
		print '</td>';

		// More informations
		print '<td align="right">';
		$htmltext='<b>'.$langs->trans("UserAgent").'</b>: '.($obj->user_agent?$obj->user_agent:$langs->trans("Unknown"));
		print $form->textwithpicto('',$htmltext);
		print '</td>';

		print "</tr>\n";
		$i++;
	}

	if ($num == 0)
	{
		if ($usefilter) print '<tr><td colspan="6">'.$langs->trans("NoEventFoundWithCriteria").'</td></tr>';
		else print '<tr><td colspan="6">'.$langs->trans("NoEventOrNoAuditSetup").'</td></tr>';
	}
	print "</table>";
	print "</div>";
	
	print "</form>";
	$db->free($result);
}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();
