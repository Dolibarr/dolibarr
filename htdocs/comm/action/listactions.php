<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
	    \file       htdocs/comm/action/listactions.php
        \ingroup    agenda
		\brief      Page liste des actions commerciales
		\version    $Id$
*/
 
require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");

$langs->load("companies");
$langs->load("agenda");

$filtera = isset($_REQUEST["userasked"])?$_REQUEST["userasked"]:(isset($_REQUEST["filtera"])?$_REQUEST["filtera"]:'');
$filtert = isset($_REQUEST["usertodo"])?$_REQUEST["usertodo"]:(isset($_REQUEST["filtert"])?$_REQUEST["filtert"]:'');
$filterd = isset($_REQUEST["userdone"])?$_REQUEST["userdone"]:(isset($_REQUEST["filterd"])?$_REQUEST["filterd"]:'');

$socid = isset($_GET["socid"])?$_GET["socid"]:$_POST["socid"];
$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];

$status=isset($_GET["status"])?$_GET["status"]:$_POST["status"];

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid,'');

$canedit=1;
if (! $user->rights->agenda->myactions->read) accessforbidden();
if (! $user->rights->agenda->allactions->read) $canedit=0;
if (! $user->rights->agenda->allactions->read || $_GET["filter"]=='mine')
{
	$filtera=$user->id;
	$filtert=$user->id;
	$filterd=$user->id;
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder)
{ 
	$sortorder="ASC";
	if ($status == 'todo') $sortorder="DESC";
	if ($status == 'done') $sortorder="DESC";
}
if (! $sortfield) 
{
	$sortfield="a.percent";
	if ($status == 'todo') $sortfield="a.datep";
	if ($status == 'done') $sortfield="a.datea2";
}


/*
*	Actions
*/
if (! empty($_POST["viewcal"]))
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

llxHeader();
$form=new Form($db);

$sql = "SELECT s.nom as societe, s.rowid as socid, s.client,";
$sql.= " a.id, ".$db->pdate("a.datep")." as dp, ".$db->pdate("a.datep2")." as dp2,";
$sql.= " ".$db->pdate("a.datea")." as da, ".$db->pdate("a.datea2")." as da2,";
$sql.= " a.fk_contact, a.note, a.label, a.percent as percent,";
$sql.= " c.code as acode, c.libelle,";
$sql.= " ua.login as loginauthor, ua.rowid as useridauthor,";
$sql.= " ut.login as logintodo, ut.rowid as useridtodo,";
$sql.= " ud.login as logindone, ud.rowid as useriddone,";
$sql.= " sp.name, sp.firstname";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s,";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX."actioncomm as a";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as ua ON a.fk_user_author = ua.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as ut ON a.fk_user_action = ut.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as ud ON a.fk_user_done = ud.rowid";
$sql.= " WHERE a.fk_soc = s.rowid AND c.id = a.fk_action";
if ($_GET["type"])
{
  $sql .= " AND c.id = ".$_GET["type"];
}
if ($_REQUEST["time"] == "today")
{
  $sql .= " AND date_format(a.datep, '%d%m%Y') = ".strftime("%d%m%Y",time());
}
if ($socid) 
{
  $sql .= " AND s.rowid = ".$socid;
}
if (!$user->rights->societe->client->voir && !$socid) //restriction
{
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
}
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
$sql .= " ORDER BY ".$sortfield." ".$sortorder;
$sql .= $db->plimit( $limit + 1, $offset);

dolibarr_syslog("comm/action/listactions.php sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
    $actionstatic=new ActionComm($db);
    $societestatic=new Societe($db);
    
    $num = $db->num_rows($resql);

	$title=$langs->trans("DoneAndToDoActions");
	if ($status == 'done') $title=$langs->trans("DoneActions");
	if ($status == 'todo') $title=$langs->trans("ToDoActions");

	$param='';
	if ($status) $param="&status=".$status;
	if ($filter) $param.="&filter=".$filter;
	if ($filtera) $param.="&filtera=".$filtera;
	if ($filtert) $param.="&filtert=".$filtert;
	if ($filterd) $param.="&filterd=".$filterd;
	if ($time) $param.="&time=".$_REQUEST["time"];
	if ($socid) $param.="&socid=".$_REQUEST["socid"];
	if ($_GET["type"]) $param.="&type=".$_REQUEST["type"];

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
	
	if ($canedit)
	{
		print '<form name="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="status" value="'.$status.'">';
		print '<input type="hidden" name="time" value="'.$_REQUEST["time"].'">';
		print '<table class="border" width="100%">';
		print '<tr>';
		print '<td>';
		//print '<input type="checkbox" name="userasked" '.($canedit?'':'disabled="true" ').($filtera?'checked="true"':'').'> ';
		print $langs->trans("ActionsAskedBy");
		print '</td><td>';
		print $form->select_users($filtera,'userasked',1,'',!$canedit);
		print '</td>';
		print '<td rowspan="3" align="center" valign="middle">';
		print img_picto($langs->trans("ViewList"),'object_list').' <input type="submit" class="button" name="viewlist" value="'.$langs->trans("ViewList").'" '.($canedit?'':'disabled="true"') .'>';
		print '<br>';
		print '<br>';
		print img_picto($langs->trans("ViewCal"),'object_calendar').' <input type="submit" class="button" name="viewcal" value="'.$langs->trans("ViewCal").'" '.($canedit?'':'disabled="true"') .'>';
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		//print '<input type="checkbox" name="usertodo" '.($canedit?'':'disabled="true" ').($filtert?'checked="true"':'').'> ';
		print $langs->trans("ActionsToDoBy");
		print '</td><td>';
		print $form->select_users($filtert,'usertodo',1,'',!$canedit);
		print '</td></tr>';
		
		print '<tr>';
		print '<td>';
		//print '<input type="checkbox" name="userdone" '.($canedit?'':'disabled="true" ').($filterd?'checked="true"':'').'> ';
		print $langs->trans("ActionsDoneBy");
		print '</td><td>';
		print $form->select_users($filterd,'userdone',1,'',!$canedit);
		print '</td></tr>';

		print '</table>';
		print '</form><br>';
	}
	
	
	$i = 0;
    print "<table class=\"noborder\" width=\"100%\">";
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"acode",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Title"),$_SERVER["PHP_SELF"],"a.label",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DatePlanShort"),$_SERVER["PHP_SELF"],"a.datep",$param,'','',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateRealShort"),$_SERVER["PHP_SELF"],"a.datea2",$param,'','',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Contact"),$_SERVER["PHP_SELF"],"a.fk_contact",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("ActionUserAsk"),$_SERVER["PHP_SELF"],"ua.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AffectedTo"),$_SERVER["PHP_SELF"],"ut.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DoneBy"),$_SERVER["PHP_SELF"],"ud.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"a.percent",$param,"",'align="right"',$sortfield,$sortorder);
    print "</tr>\n";
	
    $contactstatic = new Contact($db);

    $var=true;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);

        $var=!$var;

        print "<tr $bc[$var]>";

        // Action (type)
        print '<td>';
		$actionstatic->id=$obj->id;
		$actionstatic->code=$obj->acode;
		$actionstatic->libelle=$obj->libelle;
		print $actionstatic->getNomUrl(1,4);
        print '</td>';

        // Titre
        print '<td>';
       	print dolibarr_trunc($obj->label,12);
        print '</td>';

       	print '<td align="center" nowrap="nowrap">';
		print dolibarr_print_date($obj->dp,"day");
		$late=0;
		if ($obj->percent == 0 && $obj->dp && date("U",$obj->dp) < time()) $late=1;
		if ($obj->percent == 0 && ! $obj->dp && $obj->dp2 && date("U",$obj->dp) < time()) $late=1;
		if ($obj->percent > 0 && $obj->percent < 100 && $obj->dp2 && date("U",$obj->dp2) < time()) $late=1;
		if ($obj->percent > 0 && $obj->percent < 100 && ! $obj->dp2 && $obj->dp && date("U",$obj->dp) < time()) $late=1;
		if ($late) print img_warning($langs->trans("Late"));
		print '</td>';

		print '<td align="center" nowrap="nowrap">';
		print dolibarr_print_date($obj->da2,"day");
		print '</td>';

        // Société
        print '<td>';
        $societestatic->id=$obj->socid;
		$societestatic->client=$obj->client;
		$societestatic->nom=$obj->societe;
        print $societestatic->getNomUrl(1,'',6);
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
			$userstatic=new User($db,$obj->useridauthor);
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
			$userstatic=new User($db,$obj->useridtodo);
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
			$userstatic=new User($db,$obj->useriddone);
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
    dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
