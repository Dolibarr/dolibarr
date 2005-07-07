<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
   \file       htdocs/admin/boxes.php
   \brief      Page d'administration/configuration des boites
   \version    $Revision$
*/

require("./pre.inc.php");
$langs->load("admin");

if (!$user->admin)
  accessforbidden();

// Définition des positions possibles pour les boites
$pos_array = array(0);                          // Positions possibles pour une boite (0,1,2,...)
$pos_name = array($langs->trans("Home"));       // Nom des positions 0=Homepage, 1=...
$boxes = array();

llxHeader();

print_titre($langs->trans("Boxes"));

print "<br>".$langs->trans("BoxesDesc")."<br>\n";


/*
 * Actions
 */

if ($_POST["action"] == 'add')
{
  $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."boxes WHERE box_id=".$_POST["boxid"]." AND position=".$_POST["pos"];
  $result = $db->query($sql);
  
  $num = $db->num_rows($result);
  if ($num == 0)
    {
      // Si la boite n'est pas deja active
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes (box_id, position) values (".$_POST["boxid"].",".$_POST["pos"].");";
      $result = $db->query($sql);
    }
}

if ($_GET["action"] == 'delete')
{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes WHERE rowid=".$_GET["rowid"];
    $result = $db->query($sql);
}

if ($_GET["action"] == 'switch')
{
  // On permute les valeur du champ box_order des 2 lignes de la table boxes
  $db->begin();

  $sql="SELECT box_order FROM ".MAIN_DB_PREFIX."boxes WHERE rowid=".$_GET["switchfrom"];
  $resultfrom = $db->query($sql);
  if ($resultfrom)
    {
      $objfrom = $db->fetch_object($resultfrom);
    }
  else
    {
      dolibarr_print_error($db);
    }
  
  $sql="SELECT box_order FROM ".MAIN_DB_PREFIX."boxes WHERE rowid=".$_GET["switchto"];
  $resultto = $db->query($sql);

  if ($resultto)
    {
      $objto = $db->fetch_object($resultto);
    }
  else
    {
      dolibarr_print_error($db);
    }

  if ($objfrom && $objto) {
    $sql="UPDATE ".MAIN_DB_PREFIX."boxes set box_order=".$objto->box_order." WHERE rowid=".$_GET["switchfrom"];
    $resultupdatefrom = $db->query($sql);
    if (! $resultupdatefrom) { dolibarr_print_error($db); }
    $sql="UPDATE ".MAIN_DB_PREFIX."boxes set box_order=".$objfrom->box_order." WHERE rowid=".$_GET["switchto"];
    $resultupdateto = $db->query($sql);
    if (! $resultupdateto) { dolibarr_print_error($db); }
  }

  if ($resultupdatefrom && $resultupdateto)
    {
      $db->commit();
    }
  else
    {
      $db->rollback();
    }

}

/*
 * Recherche des boites actives par position possible
 * On stocke les boites actives par $boxes[position][id_boite]=1
 */

$sql  = "SELECT b.rowid, b.box_id, b.position, b.box_order, d.name";
$sql .= " FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d";
$sql .= " where b.box_id = d.rowid";
$sql .= " ORDER by position, box_order";
$result = $db->query($sql);

if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;
  $decalage=0;
  while ($i < $num)
    {
      $var = ! $var;
      $obj = $db->fetch_object($result);
      $boxes[$obj->position][$obj->box_id]=1;
      $i++;
      
      // On renumérote l'ordre des boites si l'une d'elle est à 0 (Ne doit arriver que sur des anciennes versions)
      if ($obj->box_order==0) $decalage++;
      if ($decalage)
	{
	  $sql="UPDATE ".MAIN_DB_PREFIX."boxes set box_order=box_order+".$decalage." WHERE rowid=".$obj->rowid;
	  $db->query($sql);
	}
    }
  
  $db->free($result);
}


/*
 * Boites disponibles
 */
print "<br>\n";
print_titre($langs->trans("BoxesAvailable"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Box").'</td>';
print '<td>'.$langs->trans("SourceFile").'</td>';
print '<td align="center" width="180">'.$langs->trans("ActivateOn").'</td>';
print '<td align="center" width="80">&nbsp;</td>';
print "</tr>\n";

$sql = "SELECT rowid, name, file FROM ".MAIN_DB_PREFIX."boxes_def";
$result = $db->query($sql);
$var=True;

if ($result)
{
  $html=new Form($db);
  
  $num = $db->num_rows($result);
  $i = 0;
  
  // Boucle sur toutes les boites
  while ($i < $num)
    {
      $var = ! $var;
      $obj = $db->fetch_object($result);
      
      $module=eregi_replace('.php$','',$obj->file);
      include_once(DOL_DOCUMENT_ROOT."/includes/boxes/".$module.".php");
      $box=new $module();
      
      print '<form action="boxes.php" method="POST">';
      $logo=eregi_replace("^object_","",$box->boximg);
      print '<tr '.$bc[$var].'><td>'.img_object("",$logo).' '.$box->boxlabel.'</td><td>' . $obj->file . '</td>';
      
      // Pour chaque position possible, on affiche un lien
      // d'activation si boite non deja active pour cette position
      print '<td align="center">';
      print $html->select_array("pos",$pos_name);
      print '<input type="hidden" name="action" value="add">';
      print '<input type="hidden" name="boxid" value="'.$obj->rowid.'">';
      print ' <input type="submit" class="button" name="button" value="'.$langs->trans("Activate").'">';
      print '</td>';
      
      print '<td>&nbsp;</td>';      
      print '</tr></form>';
      
      $i++;
    }
  
  $db->free($result);
}

print '</table>';

/*
 * Boites activées
 *
 */

print "<br>\n\n";
print_titre($langs->trans("BoxesActivated"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Box").'</td>';
print '<td>&nbsp;</td>';
print '<td align="center" width="180">'.$langs->trans("ActiveOn").'</td>';
print '<td align="center" width="60" colspan="2">'.$langs->trans("Position").'</td>';
print '<td align="center" width="80">'.$langs->trans("Disable").'</td>';
print "</tr>\n";

$sql  = "SELECT b.rowid, b.box_id, b.position, d.name, d.file";
$sql .= " FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d";
$sql .= " where b.box_id = d.rowid";
$sql .= " ORDER by position, box_order";

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  $box_order=1;
  $foundrupture=1;
  
  // On lit avec un coup d'avance
  $obj = $db->fetch_object($resql);
  
  while ($obj && $i < $num)
    {
      $var = ! $var;
      $objnext = $db->fetch_object($resql);
      
      $module=eregi_replace('.php$','',$obj->file);
      include_once(DOL_DOCUMENT_ROOT."/includes/boxes/".$module.".php");
      $box=new $module();
      
      $logo=eregi_replace("^object_","",$box->boximg);
      print '<tr '.$bc[$var].'><td>'.img_object("",$logo).' '.$box->boxlabel.'</td>';
      print '<td>&nbsp;</td>';
      print '<td align="center">' . $pos_name[$obj->position] . '</td>';
      $hasnext=true;
      $hasprevious=true;
      if ($foundrupture) { $hasprevious=false; $foundrupture=0; }
      if (! $objnext || $obj->position != $objnext->position) { $hasnext=false; $foundrupture=1; }
      print '<td align="center" width="10">'.$box_order.'</td>';
      print '<td align="center" width="50">';
      print ($hasnext?'<a href="boxes.php?action=switch&switchfrom='.$obj->rowid.'&switchto='.$objnext->rowid.'">'.img_down().'</a>&nbsp;':'');
      print ($hasprevious?'<a href="boxes.php?action=switch&switchfrom='.$obj->rowid.'&switchto='.$objprevious->rowid.'">'.img_up().'</a>':'');
      print '</td>';
      print '<td align="center">';
      print '<a href="boxes.php?rowid='.$obj->rowid.'&amp;action=delete">'.img_delete().'</a>';
      print '</td>';
      
      print "</tr>\n";
      $i++;
      
      $box_order++;
      
      if (! $foundrupture) $objprevious = $obj;
      else $box_order=1;
      $obj=$objnext;
    }
  
  $db->free($resql);
}


print '</table><br>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
