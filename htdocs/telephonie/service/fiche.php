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
 *
 */

require "./pre.inc.php";

if (!$user->rights->telephonie->service->lire)
  accessforbidden();

$mesg = '';


if ($_POST["action"] == 'add')
{
  $service = new TelephonieService($db);

  $service->ref             = $_POST["ref"];
  $service->libelle         = $_POST["libelle"];
  $service->libelle_facture = $_POST["libelle_facture"];
  $service->montant         = $_POST["montant"];

  if ( $service->create($user) == 0)
    {
      Header("Location: fiche.php?id=".$service->id);
    }
  else
    {
      $_GET["action"] = 'create';
    }
  
}


if ($_GET["action"] == 'active')
{
  $service = new TelephonieService($db);
  $service->id = $_GET["id"];

  if ( $service->active($user) == 0)
    {
      Header("Location: fiche.php?id=".$service->id);
    }

}

if ($_POST["action"] == 'update')
{
  $service = new TelephonieService($db);
  $service->id = $_GET["id"];

  $service->ref             = $_POST["ref"];
  $service->libelle         = $_POST["libelle"];
  $service->libelle_facture = $_POST["libelle_facture"];
  $service->montant         = $_POST["montant"];

  if ( $service->update($user) == 0)

    {
      $action = '';
      $mesg = 'Fiche mise à jour';
    }
  else
    {
      $action = 're-edit';
      $mesg = 'Fiche non mise à jour !' . "<br>" . $entrepot->mesg_error;
    }
}


llxHeader("","Téléphonie - Fiche Service");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}

/*
 * Création
 *
 */
if ($_GET["action"] == 'create')
{
  $form = new Form($db);
  print_titre("Nouveau service");

  print '<form action="fiche.php" method="POST">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print '<input type="hidden" name="action" value="add">';
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td>Libellé</td><td>';
  print '<input type="text" size="30" name="libelle">';
  print '</td></tr>';

  print '<tr><td>Libellé Facture</td><td>';
  print '<input type="text" size="50" name="libelle_facture">';
  print '</td></tr>';

  print '<tr><td>Montant HT</td><td>';
  print '<input type="text" size="8" name="montant">';
  print '&nbsp; euros HT</td></tr>';

  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>'."\n";
  print '</table>'."\n";
  print '</form>';



}
else
{
  if ($_GET["id"])
    {

      $service = new TelephonieService($db);

      if ( $service->fetch($_GET["id"]) == 0 )
	{ 
	  if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	    {

	      $h=0;
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/service/fiche.php?id=".$service->id;
	      $head[$h][1] = $langs->trans("Service");
	      $hselected = $h;
	      $h++;
	      
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/service/contrats.php?id=".$service->id;
	      $head[$h][1] = "Contrats";
	      $h++;

	      dol_fiche_head($head, $hselected, 'Service : '.$service->id);

	      print_fiche_titre('Fiche Service', $mesg);
      
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

	      print '<tr><td width="20%">Libellé</td><td>'.$service->libelle.'</td></tr>';
	      print '<tr><td width="20%">Libellé Facture</td><td>'.$service->libelle_facture.'</td></tr>';

	      print '<tr><td width="20%">Montant mensuel HT</td><td>'.$service->montant.'</td></tr>';
	      print '<tr><td width="20%">Statut</td><td>'.$service->statuts[$service->statut].'</td></tr>';
	      print "</table><br />";
	      print '</div>';
	    }
	

	  /*
	   * Edition
	   *
	   *
	   *
	   */
	  
	  if ($_GET["action"] == 'edit' || $action == 're-edit')
	    {

	      $h=0;
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/service/fiche.php?id=".$service->id;
	      $head[$h][1] = $langs->trans("Service");
	      $hselected = $h;
	      $h++;

	      dol_fiche_head($head, $hselected, 'Service : '.$service->numero);

	      print_fiche_titre('Edition du service', $mesg);
	      
	      print "<form action=\"fiche.php?id=$service->id\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="update">';
	      
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	      

	      print '<tr><td width="20%">Libellé</td><td>';
	      print '<input name="libelle" size="20" value="'.$service->libelle.'">';
	      print '</td></tr>';

	      print '<tr><td width="20%">Libellé Facture</td><td>';
	      print '<input name="libelle_facture" size="20" value="'.$service->libelle_facture.'">';
	      print '</td></tr>';

	      print '<tr><td width="20%">Montant mensuel HT</td><td>';
	      print '<input name="montant" size="20" value="'.$service->montant.'">&nbsp; euros HT';
	      print '</td></tr>';

	      print '<tr><td align="center" colspan="2"><input type="submit">';
	      print '</td></tr>';

	      print '</table>';

	      print '</div>';

	    }
	}
      else
	{
	  print "Error";
	}
    }
}



/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '' && $service->statut == 0)
{
  print "<a class=\"butAction\" href=\"fiche.php?action=active&amp;id=$service->id\">".$langs->trans("Active")."</a>";
}


if ($_GET["action"] == '')
{
  print "<a class=\"butAction\" href=\"fiche.php?action=edit&amp;id=$service->id\">".$langs->trans("Modify")."</a>";
}



print "</div>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
