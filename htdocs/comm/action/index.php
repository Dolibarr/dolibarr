<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/comm/action/index.php
        \ingroup    commercial
		\brief      Page accueil des actions commerciales
		\version    $Revision$
*/
 
require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");

$langs->load("companies");

$socid = isset($_GET["socid"])?$_GET["socid"]:$_POST["socid"];
$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="a.datep";

$status=isset($_GET["status"])?$_GET["status"]:$_POST["status"];


llxHeader();

/*
 *  Affichage liste des actions
 *
 */

$sql = "SELECT s.nom as societe, s.rowid as socid, s.client,";
$sql.= " a.id,".$db->pdate("a.datep")." as dp, ".$db->pdate("a.datea")." as da, a.fk_contact, a.note, a.label, a.percent as percent,";
$sql.= " c.code as acode, c.libelle,";
$sql.= " u.login, u.rowid as userid,";
$sql.= " sp.name, sp.firstname";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."user as u,";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX."actioncomm as a";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople sp ON a.fk_contact = sp.rowid";
$sql.= " WHERE a.fk_soc = s.rowid AND c.id = a.fk_action AND a.fk_user_author = u.rowid";
if ($_GET["type"])
{
  $sql .= " AND c.id = ".$_GET["type"];
}
if ($_GET["time"] == "today")
{
  $sql .= " AND date_format(a.datep, '%d%m%Y') = ".strftime("%d%m%Y",time());
}
if ($socid) 
{
  $sql .= " AND s.rowid = ".$socid;
}
if (!$user->rights->commercial->client->voir && !$socid) //restriction
{
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
}
if ($status == 'done') { $sql.= " AND a.percent = 100"; }
if ($status == 'todo') { $sql.= " AND a.percent < 100"; }
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit( $limit + 1, $offset);

dolibarr_syslog("comm/action/index.php sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
    $actionstatic=new ActionComm($db);
    $societestatic=new Societe($db);
    
    $num = $db->num_rows($resql);
    $title="DoneAndToDoActions";
    if ($status == 'done') $title="DoneActions";
    if ($status == 'todo') $title="ToDoActions";
	$param="&status=$status";

    if ($socid)
    {
        $societe = new Societe($db);
        $societe->fetch($socid);

        print_barre_liste($langs->trans($title."For",$societe->nom), $page, "index.php",$param,$sortfield,$sortorder,'',$num);
    }
    else
    {
        print_barre_liste($langs->trans($title), $page, "index.php",$param,$sortfield,$sortorder,'',$num);
    }
    $i = 0;
    print "<table class=\"noborder\" width=\"100%\">";
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("DatePlanShort"),$_SERVER["PHP_SELF"],"a.datep",$param,'','',$sortfield);
    print_liste_field_titre($langs->trans("DateRealShort"),$_SERVER["PHP_SELF"],"a.datea",$param,'','',$sortfield);
    print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"acode",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Title"),$_SERVER["PHP_SELF"],"a.label",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Contact"),$_SERVER["PHP_SELF"],"a.fk_contact",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"],"u.login",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"a.percent",$param,"",'align="right"',$sortfield);
    print "</tr>\n";
	
    $contactstatic = new Contact($db);

    $var=true;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);

        $var=!$var;

        print "<tr $bc[$var]>";

       	print '<td align="left" nowrap="nowrap">';
		print dolibarr_print_date($obj->dp,"dayhour");
		print '</td>';

		print '<td align="left" nowrap="nowrap">';
		print dolibarr_print_date($obj->da,"dayhour");
		print '</td>';

        // Action (type)
        print '<td>';
		$actionstatic->id=$obj->id;
		$actionstatic->code=$obj->acode;
		$actionstatic->libelle=$obj->libelle;
		print $actionstatic->getNomUrl(1,12);
        print '</td>';

        // Titre
        print '<td>';
       	print $obj->label;
        print '</td>';

        // Société
        print '<td>';
        $societestatic->id=$obj->socid;
		$societestatic->client=$obj->client;
		$societestatic->nom=$obj->societe;
        print $societestatic->getNomUrl(1,'',16);
		print '</td>';

        // Contact
        print '<td>';
        if ($obj->fk_contact > 0)
        {
			$contactstatic->name=$obj->name;
			$contactstatic->firstname=$obj->firstname;
			$contactstatic->id=$obj->fk_contact;
            print $contactstatic->getNomUrl(1,'',16);
        }
        else
        {
            print "&nbsp;";
        }
        print '</td>';

        // Auteur
        print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->userid.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';

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
