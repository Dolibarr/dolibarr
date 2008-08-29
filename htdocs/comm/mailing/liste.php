<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/comm/mailing/liste.php
        \ingroup    mailing
        \brief      Liste des mailings
        \version    $Id$
*/

require("./pre.inc.php");

if (!$user->rights->mailing->lire) accessforbidden();

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $_GET["page"] ;
$pageprev = $_GET["page"] - 1;
$pagenext = $_GET["page"] + 1;

$sall=isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"];
$sref=isset($_GET["sref"])?$_GET["sref"]:$_POST["sref"];


llxHeader();


$sql = "SELECT m.rowid, m.titre, m.nbemail, m.statut, m.date_creat as datec";
$sql.= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql.= " WHERE 1=1";
if ($sref) $sql.= " AND m.rowid = '".$sref."'";
if ($sall) $sql.= " AND (m.titre like '%".$sall."%' OR m.sujet like '%".$sall."%' OR m.body like '%".$sall."%')";
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="m.rowid";
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit +1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);

  print_barre_liste($langs->trans("ListOfEMailings"), $page, "liste.php","",$sortfield,$sortorder,"",$num);

  $i = 0;
  
  $addu = "&amp;sall=".$sall;
  print '<table class="liste">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Ref"),"liste.php","m.rowid",$addu,"","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Title"),"liste.php","m.titre",$addu,"","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("DateCreation"),"liste.php","m.date_creat",$addu,"",'align="center"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("NbOfEMails"),"liste.php","m.nbemail",$addu,"",'align="center"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Status"),"liste.php","m.statut",$addu,"",'align="right"',$sortfield,$sortorder);
  print "</tr>\n";

  print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';
  print '<tr class="liste_titre">';
  print '<td class="liste_titre">';
  print '<input type="text" class="flat" name="sref" value="'.$sref.'" size="6">';
  print '</td><td class="liste_titre">';
  print '<input type="text" class="flat" name="sall" value="'.$sall.'" size="40">';
  print '</td>';
  print '<td class="liste_titre">&nbsp;</td>';
  print '<td class="liste_titre">&nbsp;</td>';
  print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
  print "</td>";
  print "</tr>\n";
  print '</form>';
  
  $var=True;

  $email=new Mailing($db);
  
  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($result);
      
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/comm/mailing/fiche.php?id='.$obj->rowid.'">';
      print img_object($langs->trans("ShowEMail"),"email").' '.stripslashes($obj->rowid).'</a></td>';
      print '<td>'.$obj->titre.'</td>';
      print '<td align="center">'.dolibarr_print_date($obj->datec).'</td>';
      print '<td align="center">'.$obj->nbemail.'</td>';
      print '<td align="right">'.$email->LibStatut($obj->statut,5).'</td>';
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($result);
}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
