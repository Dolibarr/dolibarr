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
require_once(DOL_DOCUMENT_ROOT.'/telephonie/adsl/fournisseurxdsl.class.php');

$langs->load("admin");
$langs->load("suppliers");
$langs->load("products");

if (!$user->admin) accessforbidden();

if ($_GET["action"] == "addfourn")
{

  $fourn = new FournisseurXdsl($db);
  $fourn->socid = $_POST["fourn"];
  $fourn->create($user);

  Header("Location: xdsl.php");
}

if ($_GET["action"] == "switch")
{
  $fourn = new FournisseurXdsl($db);
  $fourn->SwitchCommandeActive($_GET['id']);

  Header("Location: xdsl.php");
}

/*
 *
 *
 *
 */
llxHeader('','Téléphonie - Configuration - Liens xDSL');

$h=0;
$head[$h][0] = DOL_URL_ROOT."/telephonie/config/xdsl.php";
$head[$h][1] = $langs->trans("Suppliers");
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT."/telephonie/config/xdsl_product.php";
$head[$h][1] = $langs->trans("Products");
$h++;

$head[$h][0] = DOL_URL_ROOT."/telephonie/config/xdsl_wkf.php";
$head[$h][1] = $langs->trans("Workflow");
$h++;


dol_fiche_head($head, $hselected, "Configuration des liens xDSL");

print_titre("Fournisseurs");
print '<form method="post" action="xdsl.php?action=addfourn">';
print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';

$form = new Form($db);
$fourn = new Fournisseur($db,0,$user);
$fourns = $fourn->ListArray();

$xfourn = new FournisseurXdsl($db,0,$user);
$xfourns = $xfourn->ListArray();

/* ***************************************** */

print '<tr class="pair"><td>Ajouter un fournisseur</td>';
print '<td align="left">';

$form->select_array("fourn",$fourns);

print '</td><td><input type="submit" value="'.$langs->trans('Add').'">';
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td>Nom</td>';
print '<td align="center">Commande possible</td><td>&nbsp;</td>';
print "</tr>\n";

foreach ($xfourns as $id => $name)
{
  $var=!$var;
  print "<tr $bc[$var]><td>".$name['name'].'</td>';
  print '<td align="center">'.$langs->trans($yesno[$name['commande_active']]).'</td>';
  print '<td><a href="xdsl.php?action=switch&amp;id='.$id.'">Changer</a>';
  print '</tr>';
}

print '</table>';
print '</form></div>';

$db->close();

llxFooter();
?>
