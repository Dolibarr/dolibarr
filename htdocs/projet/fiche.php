<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*! \file htdocs/projet/fiche.php
        \ingroup    projet
		\brief      Fiche projet
		\version    $Revision$
*/

require("./pre.inc.php");
require("../propal.class.php");
require("../facture.class.php");
require("../commande/commande.class.php");

$langs->load("projects");

$user->getrights('projet');

if (!$user->rights->projet->lire)
  accessforbidden();


if ($_POST["action"] == 'add' && $user->rights->projet->creer)
{
  $pro = new Project($db);
  $pro->socidp = $_GET["socidp"];
  $pro->ref = $_POST["ref"];
  $pro->title = $_POST["title"];
  $pro_id = $pro->create( $user->id);

  if ($pro_id)
    {
      Header("Location:fiche.php?id=$pro_id");
    }
}

if ($_POST["action"] == 'update' && $user->rights->projet->creer)
{
  $projet = new Project($db);
  $projet->id = $_POST["id"];
  $projet->ref = $_POST["ref"];
  $projet->title = $_POST["title"];
  $projet->update();

  $_GET["id"]=$projet->id;  // On retourne sur la fiche projet
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == yes)
{
  $projet = new Project($db);
  $projet->id = $_POST["id"];
  $projet->delete();
  Header("Location: index.php");
}

llxHeader("","Projet","Projet");


if ($_GET["action"] == 'delete')
{

  print '<form method="post" action="fiche.php?id='.$_GET["id"].'">';
  print '<input type="hidden" name="action" value="confirm_delete">';
  print '<table class="border" id="actions" cellspacing="0" width="100%" cellpadding="3">';
  
  print '<tr><td colspan="3">Supprimer le projet</td></tr>';
  
  print '<tr><td class="delete">Etes-vous sur de vouloir supprimer ce projet ?</td><td class="delete">';
  $htmls = new Form($db);
  
  $htmls->selectyesno("confirm","no");
  
  print "</td>\n";
  print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
  print '</table>';
  print "</form>\n";  
}

if ($_GET["action"] == 'create' && $user->rights->projet->creer)
{
  print_titre("Nouveau projet");

  print '<form action="fiche.php?socidp='.$_GET["socidp"].'" method="post">';
  ?>
  <table class="border" cellpadding="3" cellspacing="0">
  <input type="hidden" name="action" value="add">
  <tr><td>Société</td><td>
  <?PHP 
  $societe = new Societe($db);
  $societe->fetch($_GET["socidp"]); 
  print $societe->nom_url;

  ?>
  </td></tr>
  <?PHP
  print '<tr><td>Créateur</td><td>'.$user->fullname.'</td></tr>';
  ?>
  <tr><td><?php echo $langs->trans("Ref") ?></td><td><input size="10" type="text" name="ref"></td></tr>
  <tr><td>Titre</td><td><input size="30" type="text" name="title"></td></tr>
  <tr><td colspan="2"><input type="submit" value="Enregistrer"></td></tr>
  </table>
  </form>
  <?PHP

} else {
  /*
   * Fiche projet en mode visu
   *
   */

  $projet = new Project($db);
  $projet->fetch($_GET["id"]);
  $projet->societe->fetch($projet->societe->id);
  
  $h=0;
  $head[$h][0] = DOL_URL_ROOT.'/projet/fiche.php?id='.$projet->id;
  $head[$h][1] = 'Fiche projet';
  $hselected=$h;
  $h++;
  
  if ($conf->propal->enabled) {
      $head[$h][0] = DOL_URL_ROOT.'/projet/propal.php?id='.$projet->id;
      $head[$h][1] = 'Prop. Commerciales';
      $h++;
  }  

  if ($conf->commande->enabled) {
      $head[$h][0] = DOL_URL_ROOT.'/projet/commandes.php?id='.$projet->id;
      $head[$h][1] = 'Commandes';
      $h++;
  }
  
  if ($conf->facture->enabled) {
      $head[$h][0] = DOL_URL_ROOT.'/projet/facture.php?id='.$projet->id;
      $head[$h][1] = 'Factures';
      $h++;
  }
 
  dolibarr_fiche_head($head,  $hselected);

  if ($_GET["action"] == 'edit')
    {  
      print '<form method="post" action="fiche.php">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="id" value="'.$_GET["id"].'">';

      print '<table class="border" cellpadding="3" cellspacing="0" width="50%">';
      print '<tr><td>Société</td><td>'.$projet->societe->nom.'</td></tr>';      
      print '<tr><td>'.$langs->trans("Title").'</td><td><input name="title" value="'.$projet->title.'"></td></tr>';      
      print '<tr><td>'.$langs->trans("Ref").'</td><td><input name="ref" value="'.$projet->ref.'"></td></tr>';
      print '</table>';
      print '<p><input type="submit" Value="'.$langs->trans("Modify").'"></p>';
      print '</form>';
    }
  else
    {
      print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
      print '<tr><td width="20%">'.$langs->trans("Title").'</td><td>'.$projet->title.'</td>';  
      print '<td width="20%">'.$langs->trans("Ref").'</td><td>'.$projet->ref.'</td></tr>';
      print '<tr><td>Société</td><td colspan="3">'.$projet->societe->nom_url.'</a></td></tr>';
      print '</table>';
      print '<br>';
    }

  print '</div>';

  if ($user->rights->projet->creer == 1)
    {
      print '<div class="tabsAction">';
      if ($_GET["action"] == "edit")
	{
	  print '<a class="tabAction" href="fiche.php?id='.$projet->id.'">'.$langs->trans("Cancel").'</a>';
	}
      else
	{
	  print '<a class="tabAction" href="fiche.php?id='.$projet->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
	}
      
      print '<a class="tabAction" href="fiche.php?id='.$projet->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
      
      print "</div>";
    }

}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
