<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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

$socidp = isset($_GET["socid"])?$_GET["socid"]:$_POST["socid"];
$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
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

$sql = "SELECT s.nom as societe, s.idp as socidp, s.client,";
$sql.= " a.id,".$db->pdate("a.datep")." as dp, a.fk_contact, a.note, a.percent as percent,";
$sql.= " c.code as acode, c.libelle, u.code, u.rowid as userid";
if (!$user->rights->commercial->client->voir && !$socidp) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."user as u";
if (!$user->rights->commercial->client->voir && !$socidp) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE a.fk_soc = s.idp AND c.id=a.fk_action AND a.fk_user_author = u.rowid";
if ($_GET["type"])
{
  $sql .= " AND c.id = ".$_GET["type"];
}
if ($_GET["time"] == "today")
{
  $sql .= " AND date_format(a.datep, '%d%m%Y') = ".strftime("%d%m%Y",time());
}
if ($socidp) 
{
  $sql .= " AND s.idp = $socidp";
}
if (!$user->rights->commercial->client->voir && !$socidp) //restriction
{
	$sql .= " AND s.idp = sc.fk_soc AND sc.fk_user = " .$user->id;
}
if ($status == 'done') { $sql.= " AND a.percent = 100"; }
if ($status == 'todo') { $sql.= " AND a.percent < 100"; }
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit( $limit + 1, $offset);
  
$resql=$db->query($sql);
if ($resql)
{
    $actionstatic=new ActionComm($db);
    
    $num = $db->num_rows($resql);
    $title="DoneAndToDoActions";
    if ($status == 'done') $title="DoneActions";
    if ($status == 'todo') $title="ToDoActions";
	$param="&status=$status";

    if ($socidp)
    {
        $societe = new Societe($db);
        $societe->fetch($socidp);

        print_barre_liste($langs->trans($title."For",$societe->nom), $page, "index.php",$param,$sortfield,$sortorder,'',$num);
    }
    else
    {
        print_barre_liste($langs->trans($title), $page, "index.php",$param,$sortfield,$sortorder,'',$num);
    }
    $i = 0;
    print "<table class=\"noborder\" width=\"100%\">";
    print '<tr class="liste_titre">';
//    print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"a.datep",$param,'','colspan="4"',$sortfield);
    print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"a.datep",$param,'','',$sortfield);
    print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"acode",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Contact"),$_SERVER["PHP_SELF"],"a.fk_contact",$param,"","",$sortfield);
    print '<td>'.$langs->trans("Comments").'</td>';
    print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"],"u.code",$param,"","",$sortfield);
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"a.percent",$param,"","",$sortfield);
    print "</tr>\n";
    $var=true;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);

        $var=!$var;

        print "<tr $bc[$var]>";

		print '<td align="right">';
        if ($oldyear == strftime("%Y",$obj->dp) )
        {
        }
        else
        {
            print strftime("%Y",$obj->dp)."-";
            $oldyear = strftime("%Y",$obj->dp);
        }

        if ($oldmonth == strftime("%Y%m",$obj->dp) )
        {
        }
        else
        {
            print strftime("%m",$obj->dp)."-";
            $oldmonth = strftime("%Y%m",$obj->dp);
        }

        if ($oldday == strftime("%Y%m%d",$obj->dp) )
        {
        }
        else
        {
	        print strftime("%d",$obj->dp)." ";
            $oldday = strftime("%Y%m%d",$obj->dp);
        }

        print strftime("%H:%M",$obj->dp);
        print "</td>\n";

//        print '<td align="center">'.dolibarr_print_date($obj->dp)."</td>\n";

        // Action
        print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.img_object($langs->trans("ShowTask"),"task").' ';
        $transcode=$langs->trans("Action".$obj->acode);
        $libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
        print dolibarr_trunc($libelle,16);
        print '</a></td>';

        // Société
        print '<td>';
        if ($obj->client == 1) $url=DOL_URL_ROOT.'/comm/fiche.php?socid=';
        elseif ($obj->client == 2) $url=DOL_URL_ROOT.'/comm/prospect/fiche.php?id=';
        else $url=DOL_URL_ROOT.'/soc.php?socid=';
        print '&nbsp;<a href="'.$url.$obj->socidp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->societe,24).'</a></td>';

        // Contact
        print '<td>';
        if ($obj->fk_contact)
        {
            $cont = new Contact($db);
            $cont->fetch($obj->fk_contact);
            print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$cont->id.'">'.img_object($langs->trans("ShowContact"),"contact").' '.dolibarr_trunc($cont->fullname,24).'</a>';
        }
        else
        {
            print "&nbsp;";
        }
        print '</td>';

        // Note
        print '<td>'.dolibarr_trunc($obj->note, 16).'</td>';

        // Auteur
        print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->userid.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->code.'</a></td>';

        // Status/Percent
        print '<td align="right">'.$actionstatic->LibStatut($obj->percent,5).'</td>';

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
