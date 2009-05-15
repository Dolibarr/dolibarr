<?PHP
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
    \file       htdocs/telephonie/config/xdsl.php
    \ingroup    telephonie
    \brief      Page configuration telephonie
    \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/fournisseur.class.php');
require_once(DOL_DOCUMENT_ROOT.'/telephonie/adsl/ligneadsl.class.php');
require_once(DOL_DOCUMENT_ROOT.'/telephonie/adsl/fournisseurxdsl.class.php');
require_once(DOL_DOCUMENT_ROOT.'/telephonie/workflowtel.class.php');

$langs->load("admin");
$langs->load("suppliers");
$langs->load("products");

if (!$user->admin) accessforbidden();

$ligne = new LigneAdsl($db);

if ($_GET["action"] == "add")
{
  $wkf = new WorkflowTelephonie($db);
  $wkf->create("xdsl",$_POST["wkf_user"],$_POST["wkf_statut"]); 

  Header("Location: xdsl_wkf.php");
}

if ($_GET["action"] == "delete")
{
  $wkf = new WorkflowTelephonie($db);
  $wkf->delete("xdsl",$_GET["wkf_user"],$_GET["wkf_statut"]); 

  Header("Location: xdsl_wkf.php");
}

/*
 *
 *
 *
 */
llxHeader('','Téléphonie - Configuration - Liens xDSL - Workflow');

$h=0;
$head[$h][0] = DOL_URL_ROOT."/telephonie/config/xdsl.php";
$head[$h][1] = $langs->trans("Suppliers");
$h++;

$head[$h][0] = DOL_URL_ROOT."/telephonie/config/xdsl_product.php";
$head[$h][1] = $langs->trans("Products");
$h++;

$head[$h][0] = DOL_URL_ROOT."/telephonie/config/xdsl_wkf.php";
$head[$h][1] = $langs->trans("Workflow");
$hselected = $h;
$h++;


dol_fiche_head($head, $hselected, "Configuration des liens xDSL");

print_titre("Workflow");
print '<form method="post" action="xdsl_wkf.php?action=add">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';

$form = new Form($db);
$fourn = new Fournisseur($db,0,$user);
$fourns = $fourn->ListArray();

$xfourn = new FournisseurXdsl($db,0,$user);
$xfourns = $xfourn->ListArray();

$uss = array();
$sql = "SELECT u.rowid, u.firstname, u.name";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."usergroup_user as ug";
$sql .= " WHERE u.rowid = ug.fk_user";
$sql .= " AND ug.fk_usergroup = '".TELEPHONIE_GROUPE_COMMERCIAUX_ID."'";
$sql .= " ORDER BY name ";
if ( $resql = $db->query( $sql) )
{
  while ($row = $db->fetch_row($resql))
    {
      $uss[$row[0]] = $row[1] . " " . $row[2];
    }
  $db->free($resql);
}

/* ***************************************** */

print '<tr class="pair"><td>Ajouter une alerte pour l\'utilisateur</td>';
print '<td align="left">';

$form->select_array("wkf_user",$uss);
print ' sur l\'action ';
$form->select_array("wkf_statut",$ligne->statuts);

print '</td><td><input type="submit" value="'.$langs->trans('Add').'">';
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td>Utilisateur</td><td>Email</td>';
print '<td>Statut notifie</td><td>&nbsp;</td>';
print "</tr>\n";

$sql = "SELECT u.rowid, u.firstname, u.name, u.email, w.fk_statut";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."telephonie_workflow as w";
$sql .= " WHERE u.rowid = w.fk_user AND w.module = 'xdsl'";
$sql .= " ORDER BY u.name, u.firstname ";
if ( $resql = $db->query( $sql) )
{
  while ($row = $db->fetch_row($resql))
    {
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td>'.$row[1].' '.$row[2].'</td>';
      print '<td>'.$row[3].'</td>';
      print '<td>'.$ligne->statuts[$row[4]].'</td>';
      print '<td><a href="xdsl_wkf.php?action=delete&amp;wkf_user='.$row[0].'&amp;wkf_statut='.$row[4].'">';
      print img_delete();
      print '</a></td></tr>';
    }
  $db->free($resql);
}


print '</table>';
print '</form></div>';

$db->close();

llxFooter();
?>
