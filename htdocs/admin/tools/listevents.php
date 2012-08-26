<?php
/* Copyright (C) 2004-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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


/*
 * Actions
 */

$now=dol_now();

// Purge audit events
if ($action == 'confirm_purge' && $confirm == 'yes' && $user->admin)
{
	$error=0;

	$db->begin();
	$securityevents=new Events($db);

	// Delete events
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."events";
	$sql.= " WHERE entity = ".$conf->entity;

	dol_syslog("listevents purge sql=".$sql);
	$resql = $db->query($sql);
	if (! $resql)
	{
		$error++;
		setEventMessage($db->lasterror(), 'errors');
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
		$db->rolback();
	}
}


/*
 *	View
 */

llxHeader();

$form=new Form($db);

$userstatic=new User($db);
$usefilter=0;

$sql = "SELECT e.rowid, e.type, e.ip, e.user_agent, e.dateevent,";
$sql.= " e.fk_user, e.description,";
$sql.= " u.login";
$sql.= " FROM ".MAIN_DB_PREFIX."events as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = e.fk_user";
$sql.= " WHERE e.entity = ".$conf->entity;
if ($search_code) { $usefilter++; $sql.=" AND e.type LIKE '%".$search_code."%'"; }
if ($search_ip)   { $usefilter++; $sql.=" AND e.ip LIKE '%".$search_ip."%'"; }
if ($search_user) { $usefilter++; $sql.=" AND u.login LIKE '%".$search_user."%'"; }
if ($search_desc) { $usefilter++; $sql.=" AND e.description LIKE '%".$search_desc."%'"; }
if ($search_ua)   { $usefilter++; $sql.=" AND e.user_agent LIKE '%".$search_ua."%'"; }
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);
//print $sql;
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	print_barre_liste($langs->trans("ListOfSecurityEvents"), $page, $_SERVER["PHP_SELF"],"",$sortfield,$sortorder,'',$num,0,'setup');

	if ($action == 'purge')
	{
		$formquestion=array();
		$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?noparam=noparam', $langs->trans('PurgeAuditEvents'), $langs->trans('ConfirmPurgeAuditEvents'),'confirm_purge',$formquestion,'no',1);
		if ($ret == 'html') print '<br>';
	}

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"e.dateevent","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Code"),$_SERVER["PHP_SELF"],"e.type","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("IP"),$_SERVER["PHP_SELF"],"e.ip","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("User"),$_SERVER["PHP_SELF"],"u.login","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"),$_SERVER["PHP_SELF"],"e.description","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre('','','');
	print "</tr>\n";


	// Lignes des champs de filtres
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	print '<tr class="liste_titre">';

	print '<td class="liste_titre">&nbsp;</td>';

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
	print '<input class="flat" type="text" size="10" name="search_desc" value="'.$search_desc.'">';
	print '</td>';

	print '<td align="right" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';

	print "</tr>\n";
	print '</form>';

	$var=True;

	while ($i < min($num, $conf->liste_limit))
	{
		$obj = $db->fetch_object($result);

		$var=!$var;

		print '<tr '.$bc[$var].'>';

		// Date
		print '<td align="left" nowrap="nowrap">'.dol_print_date($db->jdate($obj->dateevent),'%Y-%m-%d %H:%M:%S').'</td>';

		// Code
		print '<td>'.$obj->type.'</td>';

		// IP
		print '<td nowrap="nowrap">';
		print dol_print_ip($obj->ip);
		print '</td>';

		// Login
		print '<td nowrap="nowrap">';
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
		if (preg_match('/\((.*)\)/i',$obj->description,$reg))
		{
			$val=explode(',',$reg[1]);
			$text=$langs->trans($val[0], isset($val[1])?$val[1]:'', isset($val[2])?$val[2]:'', isset($val[3])?$val[3]:'', isset($val[4])?$val[4]:'');
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
	$db->free($result);

	if ($num)
	{
		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=purge">'.$langs->trans("Purge").'</a>';
		print '</div>';
	}
}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();
?>