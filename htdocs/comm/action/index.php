<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
	    \file       htdocs/comm/action/index.php
        \ingroup    commercial
		\brief      Page accueil des actions commerciales
		\version    $Revision$
*/
 
require_once("./pre.inc.php");
require_once("../../contact.class.php");
require_once("../../actioncomm.class.php");

$langs->load("companies");


// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="a.datea";

$status=isset($_GET["status"])?$_GET["status"]:$_POST["status"];


llxHeader();

/*
 *  Affichage liste des actions
 *
 */

$sql = "SELECT s.nom as societe, s.idp as socidp, s.client, a.id,".$db->pdate("a.datea")." as da, a.datea, c.code as acode, c.libelle, u.code, a.fk_contact, a.note, a.percent as percent";
$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE a.fk_soc = s.idp AND c.id=a.fk_action AND a.fk_user_author = u.rowid";
if ($_GET["type"])
{
  $sql .= " AND c.id = ".$_GET["type"];
}
if ($_GET["time"] == "today")
{
  $sql .= " AND date_format(a.datea, '%d%m%Y') = ".strftime("%d%m%Y",time());
}
if ($socid) 
{
  $sql .= " AND s.idp = $socid";
}
if ($status == 'done') { $sql.= " AND a.percent = 100"; }
if ($status == 'todo') { $sql.= " AND a.percent < 100"; }
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit( $limit + 1, $offset);
  
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $title="DoneAndToDoActions";
    if ($status == 'done') $title="DoneActions";
    if ($status == 'todo') $title="ToDoActions";

    if ($socid)
    {
        $societe = new Societe($db);
        $societe->fetch($socid);

        print_barre_liste($langs->trans($title."For",$societe->nom), $page, "index.php",'',$sortfield,$sortorder,'',$num);
    }
    else
    {
        print_barre_liste($langs->trans($title), $page, "index.php",'',$sortfield,$sortorder,'',$num);
    }
    $i = 0;
    print "<table class=\"noborder\" width=\"100%\">";
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"a.datea","&status=$status",'','colspan="4"',$sortfield);
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"a.percent","&status=$status","","",$sortfield);
    print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"acode","&status=$status","","",$sortfield);
    print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","&status=$status","","",$sortfield);
    print_liste_field_titre($langs->trans("Contact"),$_SERVER["PHP_SELF"],"a.fk_contact","&status=$status","","",$sortfield);
    print '<td>'.$langs->trans("Comments").'</td>';
    print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"],"u.code","&status=$status","","",$sortfield);
    print "</tr>\n";
    $var=true;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);

        $var=!$var;

        print "<tr $bc[$var]>";

        if ($oldyear == strftime("%Y",$obj->da) )
        {
            print '<td>&nbsp;</td>';
        }
        else
        {
            print "<td width=\"30\">" .strftime("%Y",$obj->da)."</td>\n";
            $oldyear = strftime("%Y",$obj->da);
        }

        if ($oldmonth == strftime("%Y%b",$obj->da) )
        {
            print '<td width=\"20\">&nbsp;</td>';
        }
        else
        {
            print "<td width=\"20\">" .strftime("%b",$obj->da)."</td>\n";
            $oldmonth = strftime("%Y%b",$obj->da);
        }

        print "<td width=\"20\">" .strftime("%d",$obj->da)."</td>\n";
        print "<td width=\"30\">" .strftime("%H:%M",$obj->da)."</td>\n";

        if ($obj->percent < 100) {
            print "<td align=\"center\">".$obj->percent."%</td>";
        }
        else {
            print "<td align=\"center\">".$langs->trans("Done")."</td>";
        }

        print '<td><a href="fiche.php?id='.$obj->id.'">'.img_object($langs->trans("ShowTask"),"task").' ';
        $transcode=$langs->trans("Action".$obj->acode);
        $libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
        print dolibarr_trunc($libelle,16);
        print '</a></td>';

        // Sociét
        print '<td>';
        if ($obj->client == 1) $url=DOL_URL_ROOT.'/comm/fiche.php?socid=';
        elseif ($obj->client == 2) $url=DOL_URL_ROOT.'/comm/prospect/fiche.php?id=';
        else $url=DOL_URL_ROOT.'/soc.php?socid=';
        print '&nbsp;<a href="'.$url.$obj->socidp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->societe,32).'</a></td>';

        // Contact
        print '<td>';
        if ($obj->fk_contact)
        {
            $cont = new Contact($db);
            $cont->fetch($obj->fk_contact);
            print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$cont->id.'">'.img_object($langs->trans("ShowContact"),"contact").' '.dolibarr_trunc($cont->fullname,32).'</a>';
        }
        else
        {
            print "&nbsp;";
        }
        print '</td>';

        // Note
        print '<td>'.dolibarr_trunc($obj->note, 16).'</td>';

        // Auteur
        print '<td align="center">'.$obj->code.'</td>';

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
