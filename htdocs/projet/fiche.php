<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./project.class.php");
require("../propal.class.php");
require("../facture.class.php");
require("../commande/commande.class.php");

if ($HTTP_POST_VARS["action"] == 'update')
{
  $projet = new Project($db);
  $projet->id = $id;
  $projet->ref = $HTTP_POST_VARS["ref"];
  $projet->title = $HTTP_POST_VARS["title"];
  $projet->update();
}

if ($HTTP_POST_VARS["action"] == 'confirm_delete' && $HTTP_POST_VARS["confirm"] == yes)
{
  $projet = new Project($db);
  $projet->id = $id;
  $projet->delete();
  Header("Location: index.php");
}

llxHeader("","../");

if ($action == 'delete')
{

  print '<form method="post" action="'.$PHP_SELF.'?id='.$id.'">';
  print '<input type="hidden" name="action" value="confirm_delete">';
  print '<table id="actions" cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print '<tr><td colspan="3">Supprimer le projet</td></tr>';
  
  print '<tr><td class="delete">Etes-vous sur de vouloir supprimer ce projet ?</td><td class="delete">';
  $htmls = new Form($db);
  
  $htmls->selectyesno("confirm","no");
  
  print "</td>\n";
  print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
  print '</table>';
  print "</form>\n";  
}



if ($action == 'create')
{
  print_titre("Nouveau projet");

  print '<form action="index.php?socidp='.$socidp.'" method="post">';
  ?>
  <table class="border" border="1" cellpadding="4" cellspacing="0">
  <input type="hidden" name="action" value="create">
  <tr><td>Société</td><td>
  <?PHP 
  $societe = new Societe($db);
  $societe->get_nom($socidp); 
  print '<a href="../comm/fiche.php?socid='.$socidp.'">'.$societe->nom.'</a>'; 

  ?>
  </td></tr>
  <?PHP
  print '<tr><td>Créateur</td><td>'.$user->fullname.'</td></tr>';
  ?>
  <tr><td>Référence</td><td><input size="10" type="text" name="ref"></td></tr>
  <tr><td>Titre</td><td><input size="30" type="text" name="title"></td></tr>
  <tr><td colspan="2"><input type="submit" value="Enregistrer"></td></tr>
  </table>
  </form>
  <?PHP

} else {
  /*
   *
   *
   *
   */

  print_titre("Fiche projet");

  $propales = array();
  $projet = new Project($db);
  $projet->fetch($id);

  $projet->societe->fetch($projet->societe->id);
  
  if ($action == 'edit')
    {  
      print '<form method="post" action="fiche.php?id='.$id.'">';
      print '<input type="hidden" name="action" value="update">';
      print '<table class="border" border="1" cellpadding="4" cellspacing="0">';
      print '<tr><td>Société</td><td>'.$projet->societe->nom.'</td></tr>';      
      print '<tr><td>Ref</td><td><input name="ref" value="'.$projet->ref.'"></td></tr>';
      print '<tr><td>Titre</td><td><input name="title" value="'.$projet->title.'"></td></tr>';
      print '</table><input type="submit" Value="Enregistrer"></form>';
    }
  else
    {
      print '<table class="border" border="1" cellpadding="4" cellspacing="0" width="100%">';
      print '<tr><td>Société</td><td><a href="../comm/fiche.php?socid='.$projet->societe->id.'">'.$projet->societe->nom.'</a></td></tr>';
      
      print '<tr><td width="20%">Réf</td><td>'.$projet->ref.'</td></tr>';
      print '<tr><td width="20%">Titre</td><td>'.$projet->title.'</td></tr>';
      print '</table>';
    }


  print '<p><table id="actions" border="1" width="100%" cellspacing="0" cellpadding="4"><tr>';
  
  if ($action == "edit")
    {
      print "<td align=\"center\" width=\"20%\"><a href=\"fiche.php?id=$id\">Annuler</a></td>";
    }
  else
    {
      print "<td align=\"center\" width=\"20%\"><a href=\"fiche.php?id=$id&amp;action=edit\">Editer</a></td>";
    }
  
  print '<td align="center" width="20%">-</td>';
  print '<td align="center" width="20%">-</td>';
  print '<td align="center" width="20%">-</td>';
  
  print "<td align=\"center\" width=\"20%\"><a href=\"fiche.php?id=$id&amp;action=delete\">Supprimer</a></td>";

  print "</tr></table>";

  if ($_GET["action"] == '')
    {
      $propales = $projet->get_propal_list();
      
      if (sizeof($propales)>0 && is_array($propales))
	{
	  print_titre('Listes des propositions commerciales associées au projet');
	  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
	  
	  print '<TR class="liste_titre">';
	  print '<td width="15%">Réf</td><td width="25%">Date</td><td align="right">Prix</td></tr>';
	  
	  for ($i = 0; $i<sizeof($propales);$i++)
	    {
	      $propale = new Propal($db);
	      $propale->fetch($propales[$i]);
	      
	      $var=!$var;
	      print "<tr $bc[$var]>";
	      print "<td><a href=\"../comm/propal.php?propalid=$propale->id\">$propale->ref</a></td>\n";
	      
	      print '<td>'.strftime("%d %B %Y",$propale->datep).'</td>';
	      
	      print '<td align="right">'.price($propale->price).'</td></tr>';
	      $total = $total + $propale->price;
	    }
	  
	  print '<tr><td>'.$i.' propales</td>';
	  print '<td align="right">Total : '.price($total).'</td>';
	  print '<td align="left">'.MAIN_MONNAIE.' HT</td></tr></table>';
	}
      /*
       * Commandes
       *
       */
      $commandes = $projet->get_commande_list();
      $total = 0 ;
      if (sizeof($commandes)>0 && is_array($commandes))
	{
	  print_titre('Listes des commandes associées au projet');
	  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
	  
	  print '<TR class="liste_titre">';
	  print '<td width="15%">Réf</td><td width="25%">Date</td><td align="right">Prix</td></tr>';
	  
	  for ($i = 0; $i<sizeof($commandes);$i++)
	    {
	      $commande = new Commande($db);
	      $commande->fetch($commandes[$i]);
	      
	      $var=!$var;
	      print "<TR $bc[$var]>";
	      print "<TD><a href=\"../fiche.php?id=$commande->id\">$commande->ref</a></TD>\n";	      
	      print '<td>'.strftime("%d %B %Y",$commande->date).'</td>';	      
	      print '<TD align="right">'.price($commande->total_ht).'</td></tr>';
	      
	      $total = $total + $commande->total_ht;
	    }
	  
	  print '<tr><td>'.$i.' commandes</td>';
	  print '<td align="right">Total : '.price($total).'</td>';
	  print '<td align="left">'.MAIN_MONNAIE.' HT</td></tr>';
	  print "</table>";
	}
    

      /*
       * Factures
       *
       */
      $factures = $projet->get_facture_list();

      if (sizeof($factures)>0 && is_array($factures))
	{
	  print_titre('Listes des factures associées au projet');
	  print '<table border="0" width="100%" cellspacing="0" cellpadding="4">';
	  
	  print '<tr class="liste_titre">';
	  print '<td width="15%">Réf</td><td width="25%">Date</td><td align="right">Prix</td></tr>';
	  
	  for ($i = 0; $i<sizeof($factures);$i++)
	    {
	      $facture = new Facture($db);
	      $facture->fetch($factures[$i]);
	      
	      $var=!$var;
	      print "<TR $bc[$var]>";
	      print "<TD><a href=\"../compta/facture.php?facid=$facture->id\">$facture->ref</a></TD>\n";	      
	      print '<td>'.strftime("%d %B %Y",$facture->date).'</td>';	      
	      print '<TD align="right">'.price($facture->total_ht).'</td></tr>';
	      
	      $total = $total + $facture->total_ht;
	    }
	  
	  print '<tr><td>'.$i.' factures</td>';
	  print '<td align="right">Total : '.price($total).'</td>';
	  print '<td align="left">'.MAIN_MONNAIE.' HT</td></tr>';
	  print "</TABLE>";
	}
    }
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
