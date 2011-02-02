<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	    \file       htdocs/comm/action/listactions.php
 *      \ingroup    agenda
 *		\brief      Page to list actions
 *		\version    $Id$
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

$langs->load("companies");
$langs->load("agenda");
$langs->load("commercial");

$action=GETPOST('action','alpha');
$year=GETPOST("year",'int');
$month=GETPOST("month",'int');
$day=GETPOST("day",'int');
$pid=GETPOST("projectid",'int');
$status=GETPOST("status",'alpha');

$filtera = GETPOST("userasked","int")?GETPOST("userasked","int"):GETPOST("filtera","int");
$filtert = GETPOST("usertodo","int")?GETPOST("usertodo","int"):GETPOST("filtert","int");
$filterd = GETPOST("userdone","int")?GETPOST("userdone","int"):GETPOST("filterd","int");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder)
{
	$sortorder="ASC";
	if ($status == 'todo') $sortorder="ASC";
	if ($status == 'done') $sortorder="DESC";
}
if (! $sortfield)
{
	$sortfield="a.percent";
	if ($status == 'todo') $sortfield="a.datep";
	if ($status == 'done') $sortfield="a.datep2";
}

// Security check
$socid = GETPOST("socid",'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'agenda', 0, '', 'myactions');

$canedit=1;
if (! $user->rights->agenda->myactions->read) accessforbidden();
if (! $user->rights->agenda->allactions->read) $canedit=0;
if (! $user->rights->agenda->allactions->read || $_GET["filter"]=='mine')	// If no permission to see all, we show only affected to me
{
	$filtera=$user->id;
	$filtert=$user->id;
	$filterd=$user->id;
}



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

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);

$form=new Form($db);

$param='';
if ($status) $param="&status=".$status;
if ($filter) $param.="&filter=".$filter;
if ($filtera) $param.="&filtera=".$filtera;
if ($filtert) $param.="&filtert=".$filtert;
if ($filterd) $param.="&filterd=".$filterd;
if ($socid) $param.="&socid=".$socid;
if ($pid) $param.="&projectid=".$pid;
if ($_GET["type"]) $param.="&type=".$_REQUEST["type"];

$sql = "SELECT s.nom as societe, s.rowid as socid, s.client,";
$sql.= " a.id, a.datep as dp, a.datep2 as dp2,";
//$sql.= " a.datea as da, a.datea2 as da2,";
$sql.= " a.fk_contact, a.note, a.label, a.percent as percent,";
$sql.= " c.code as acode, c.libelle,";
$sql.= " ua.login as loginauthor, ua.rowid as useridauthor,";
$sql.= " ut.login as logintodo, ut.rowid as useridtodo,";
$sql.= " ud.login as logindone, ud.rowid as useriddone,";
$sql.= " sp.name, sp.firstname";
$sql.= " FROM (".MAIN_DB_PREFIX."c_actioncomm as c,";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX.'user as u,';
$sql.= " ".MAIN_DB_PREFIX."actioncomm as a)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as ua ON a.fk_user_author = ua.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as ut ON a.fk_user_action = ut.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as ud ON a.fk_user_done = ud.rowid";
$sql.= " WHERE c.id = a.fk_action";
$sql.= ' AND a.fk_user_author = u.rowid';			// To limit to entity
$sql.= ' AND u.entity in (0,'.$conf->entity.')';	// To limit to entity
if ($pid) $sql.=" AND a.fk_project=".addslashes($pid);
if (!$user->rights->societe->client->voir && !$socid)	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
if ($_GET["type"]) $sql.= " AND c.id = ".$_GET["type"];
if ($status == 'done') { $sql.= " AND a.percent = 100"; }
if ($status == 'todo') { $sql.= " AND a.percent < 100"; }
if ($filtera > 0 || $filtert > 0 || $filterd > 0)
{
	$sql.= " AND (";
	if ($filtera > 0) $sql.= " a.fk_user_author = ".$filtera;
	if ($filtert > 0) $sql.= ($filtera>0?" OR ":"")." a.fk_user_action = ".$filtert;
	if ($filterd > 0) $sql.= ($filtera>0||$filtert>0?" OR ":"")." a.fk_user_done = ".$filterd;
	$sql.= ")";
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1, $offset);
//print $sql;

dol_syslog("comm/action/listactions.php sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
	$actionstatic=new ActionComm($db);
	$societestatic=new Societe($db);

	$num = $db->num_rows($resql);

	$title=$langs->trans("DoneAndToDoActions");
	if ($status == 'done') $title=$langs->trans("DoneActions");
	if ($status == 'todo') $title=$langs->trans("ToDoActions");

	if ($socid)
	{
		$societe = new Societe($db);
		$societe->fetch($socid);

		print_barre_liste($langs->trans($title).' '.$langs->trans("For").' '.$societe->nom, $page, $_SERVER["PHP_SELF"], $param,$sortfield,$sortorder,'',$num);
	}
	else
	{
		print_barre_liste($langs->trans($title), $page, $_SERVER["PHP_SELF"], $param,$sortfield,$sortorder,'',$num);
	}

	//print '<br>';

	print_actions_filter($form,$canedit,$status,$year,$month,$day,$showborthday,$filtera,$filtert,$filterd,$pid,$socid);

	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"acode",$param,"","",$sortfield,$sortorder);
	//print_liste_field_titre($langs->trans("Title"),$_SERVER["PHP_SELF"],"a.label",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"],"a.datep",$param,'','align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateEnd"),$_SERVER["PHP_SELF"],"a.datep2",$param,'','align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Contact"),$_SERVER["PHP_SELF"],"a.fk_contact",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ActionUserAsk"),$_SERVER["PHP_SELF"],"ua.login",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AffectedTo"),$_SERVER["PHP_SELF"],"ut.login",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DoneBy"),$_SERVER["PHP_SELF"],"ud.login",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"a.percent",$param,"",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	$contactstatic = new Contact($db);
	$now=gmmktime();
	$delay_warning=$conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;

	$var=true;
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);

		$var=!$var;

		print "<tr $bc[$var]>";

		// Action (type)
		print '<td>';
		$actionstatic->id=$obj->id;
		$actionstatic->type_code=$obj->acode;
		$actionstatic->libelle=$obj->label;
		print $actionstatic->getNomUrl(1,12);
		print '</td>';

		// Titre
		//print '<td>';
		//print dol_trunc($obj->label,12);
		//print '</td>';

		print '<td align="center" nowrap="nowrap">';
		print dol_print_date($db->jdate($obj->dp),"day");
		$late=0;
		if ($obj->percent == 0 && $obj->dp && $db->jdate($obj->dp) < ($now - $delay_warning)) $late=1;
		if ($obj->percent == 0 && ! $obj->dp && $obj->dp2 && $db->jdate($obj->dp) < ($now - $delay_warning)) $late=1;
		if ($obj->percent > 0 && $obj->percent < 100 && $obj->dp2 && $db->jdate($obj->dp2) < ($now - $delay_warning)) $late=1;
		if ($obj->percent > 0 && $obj->percent < 100 && ! $obj->dp2 && $obj->dp && $db->jdate($obj->dp) < ($now - $delay_warning)) $late=1;
		if ($late) print img_warning($langs->trans("Late")).' ';
		print '</td>';

		print '<td align="center" nowrap="nowrap">';
		print dol_print_date($db->jdate($obj->dp2),"day");
		print '</td>';

		// Third party
		print '<td>';
		if ($obj->socid)
		{
			$societestatic->id=$obj->socid;
			$societestatic->client=$obj->client;
			$societestatic->nom=$obj->societe;
			print $societestatic->getNomUrl(1,'',6);
		}
		else print '&nbsp;';
		print '</td>';

		// Contact
		print '<td>';
		if ($obj->fk_contact > 0)
		{
			$contactstatic->name=$obj->name;
			$contactstatic->firstname=$obj->firstname;
			$contactstatic->id=$obj->fk_contact;
			print $contactstatic->getNomUrl(1,'',6);
		}
		else
		{
			print "&nbsp;";
		}
		print '</td>';

		// User author
		print '<td align="left">';
		if ($obj->useridauthor)
		{
			$userstatic=new User($db);
			$userstatic->id=$obj->useridauthor;
			$userstatic->login=$obj->loginauthor;
			print $userstatic->getLoginUrl(1);
		}
		else print '&nbsp;';
		print '</td>';

		// User to do
		print '<td align="left">';
		if ($obj->useridtodo)
		{
			$userstatic=new User($db);
			$userstatic->id=$obj->useridtodo;
			$userstatic->login=$obj->logintodo;
			print $userstatic->getLoginUrl(1);
		}
		else print '&nbsp;';
		print '</td>';

		// User did
		print '<td align="left">';
		if ($obj->useriddone)
		{
			$userstatic=new User($db);
			$userstatic->id=$obj->useriddone;
			$userstatic->login=$obj->logindone;
			print $userstatic->getLoginUrl(1);
		}
		else print '&nbsp;';
		print '</td>';

		// Status/Percent
		print '<td align="right" nowrap="nowrap">'.$actionstatic->LibStatut($obj->percent,5).'</td>';

		print "</tr>\n";
		$i++;
	}
	print "</table>";
	$db->free($resql);

}
else
{
	dol_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
