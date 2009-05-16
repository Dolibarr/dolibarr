<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
    \file       htdocs/telephonie/config/index.php
    \ingroup    telephonie
    \brief      Page configuration telephonie
    \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin) accessforbidden();

if ($_GET["action"] == "set")
{

  dolibarr_set_const($db, 'TELEPHONIE_COMPTE_VENTILATION', $_POST["cg"],'chaine',0,'',$conf->entity);


  Header("Location: compta.php");
}

/*
 *
 *
 *
 */
llxHeader('','Telephonie - Configuration');
print_titre("Configuration du module de Telephonie");

print "<br>";

/*
 *
 *
 */
print_titre("Emails");
print '<form method="post" action="compta.php?action=set">';
print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print '<tr class="liste_titre">';
print '<td>Nom</td>';
print '<td>Valeur</td><td>&nbsp;</td><td>&nbsp;</td>';
print "</tr>\n";

print '<tr class="pair"><td>';
print 'Compte de ventilation</td><td>';
print TELEPHONIE_COMPTE_VENTILATION;
print '</td><td>TELEPHONIE_EMAIL_FACTURATION_EMAIL</td></tr>';

$sql = "SELECT rowid, numero, intitule";
$sql .= " FROM ".MAIN_DB_PREFIX."compta_compte_generaux";
$sql .= " ORDER BY numero ASC";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0; 
  
  while ($i < $num)
    {
      $row = $db->fetch_row($result);
      $cgs[$row[0]] = '['.$row[0].'] '.$row[1] . ' ' . $row[2];
      $i++;
    }
}

$html = new Form($db);
print '<tr><td>Compte</td><td>';

print $html->select_array("cg",$cgs);

print '</td><td><input type="submit"></td></tr>';

print '</table>';
print '</form>';

$db->close();

llxFooter();
?>
