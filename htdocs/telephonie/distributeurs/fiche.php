<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

if (!$user->rights->telephonie->lire) accessforbidden();

$distri = new DistributeurTelephonie($db);

if($_POST["action"] == 'add')
{
  $distri->nom = $_POST["nom"];
  $distri->avance = $_POST["avance"];
  $distri->duree = $_POST["duree"];
  $distri->prelev = $_POST["prelev"];
  $distri->autre = $_POST["autre"];

  if ($distri->create() <> 0)
    {
      $_GET["action"] = "create";
    }
  else
    {
      Header("Location: index.php");
    }

}


llxHeader('','Telephonie - Statistiques - Distributeurs');

/*
 *
 *
 *
 */

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/fiche.php?action=create';
$head[$h][1] = "Nouveau distributeur";
$hselected = $h;
$h++;

dolibarr_fiche_head($head, $hselected, "Distributeurs");



if ($_GET["action"] == 'create')
{
  print '<form method="POST" action="fiche.php">';
  print '<input type="hidden" name="action" value="add"></td></tr>';

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="20%">Nom</td>';
  print '<td><input type="text" size="30" name="nom" value="'.$distri->nom.'"></td>';
  print '<td>'.$distri->error_string["nom"].'</td></tr>';
  
  print '<tr><td width="20%">% avance</td>';
  print '<td><input type="text" size="3" maxlength="2" name="avance" value="'.$distri->avance.'"> %</td>';
  print '<td>'.$distri->error_string["avance"].'</td></tr>';
  
  print '<tr><td width="20%">Durée de l\'avance</td>';
  print '<td><input type="text" size="3" maxlength="2" name="duree" value="'.$distri->duree.'"> mois</td>';
  print '<td>'.$distri->error_string["duree"].'</td></tr>';

  print '<tr><td width="20%">% prélèvement</td>';
  print '<td><input type="text" size="3" maxlength="2" name="prelev" value="'.$distri->prelev.'"> %</td>';
  print '<td>'.$distri->error_string["prelev"].'</td></tr>';

  print '<tr><td width="20%">% autre</td>';
  print '<td width="25%"><input type="text" size="3" maxlength="2" name="autre" value="'.$distri->autre.'"> %</td>';

  print '<td>'.$distri->error_string["autre"].'</td></tr>';
  
  print '<tr><td colspan="2"><input type="submit"></td></tr>';

  print '</table><br />';

  print "</form>";

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
