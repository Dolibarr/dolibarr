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
$commercial = new CommercialTelephonie($db);

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

if($_POST["action"] == 'add_commercial' && $user->admin)
{
  $commercial->nom = $_POST["nom"];
  $commercial->prenom = $_POST["prenom"];
  $commercial->distri = $_GET["distri"];
  $commercial->email = $_POST["email"];
  
  if ($commercial->create() <> 0)
    {
      $_GET["action"] = "create_commercial";
    }
  else
    {
      Header("Location: distributeur.php?id=".$_GET["distri"]);
    }
}

llxHeader('','Telephonie - Statistiques - Distributeurs');

/*
 *
 *
 *
 */

$h = 0;


if ($_GET["action"] == 'create_commercial')
{
  $distri = new DistributeurTelephonie($db);
  $distri->fetch($_GET["distri"]);


  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/fiche.php?action=create';
  $head[$h][1] = "Nouveau commercial";
  $hselected = $h;
  $h++;
  
  dol_fiche_head($head, $hselected, $distri->nom);

  print '<form method="POST" action="fiche.php?distri='.$_GET["distri"].'">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print '<input type="hidden" name="action" value="add_commercial"></td></tr>';

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="20%">Prénom</td>';
  print '<td><input type="text" size="20" maxlength="20" name="prenom" value="'.$commercial->prenom.'"></td>';
  print '<td>'.$commercial->error_string["prenom"].'</td></tr>';
  print '<tr><td width="20%">Nom</td>';
  print '<td><input type="text" size="30" maxlength="30" name="nom" value="'.$commercial->nom.'"></td>';
  print '<td>'.$commercial->error_string["nom"].'</td></tr>';
 
  print '<tr><td width="20%">Email</td>';
  print '<td><input type="text" size="50" maxlength="70" name="email" value="'.$commercial->email.'"></td>';
  print '<td>'.$commercial->error_string["email"].'</td></tr>';

  print '<tr><td colspan="2"><input type="submit"></td></tr>';

  print '</table><br />';

  print "</form>";

}

if ($_GET["action"] == 'create')
{

$head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/fiche.php?action=create';
$head[$h][1] = "Nouveau distributeur";
$hselected = $h;
$h++;

dol_fiche_head($head, $hselected, "Distributeurs");

  print '<form method="POST" action="fiche.php">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
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
