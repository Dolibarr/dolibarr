<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./contrat.class.php");
require("../facture.class.php");

llxHeader();

$mesg = '';

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


if ($action == 'add')
{
  $contrat = new Contrat($db);

  $id = $product->create($user);
  $action = '';
}

if ($_POST["action"] == 'miseenservice')
{
  $contrat = new Contrat($db);
  $contrat->id = $id;
  $contrat->fetch($id);
  $contrat->mise_en_service($user, 
			    mktime($_POST["rehour"],
				   $_POST["remin"],
				   0,
				   $_POST["remonth"],
				   $_POST["reday"],
				   $_POST["reyear"]),
			    $_POST["duration"]
			    );
}

if ($_GET["action"] == 'cloture')
{
  $contrat = new Contrat($db);
  $contrat->id = $id;
  $contrat->cloture($user);
}

if ($_GET["action"] == 'annule')
{
  $contrat = new Contrat($db);
  $contrat->id = $id;
  $contrat->annule($user);
}


if ($action == 'update' && $cancel <> 'Annuler')
{
  $product = new Product($db);

  $product->ref = $_POST["ref"];
  $product->libelle = $_POST["libelle"];
  $product->price = $_POST["price"];
  $product->tva_tx = $_POST["tva_tx"];
  $product->description = $_POST["desc"];
  $product->envente = $_POST["statut"];
  $product->duration_value = $_POST["duration_value"];
  $product->duration_unit = $_POST["duration_unit"];

  if (  $product->update($id, $user))
    {
      $action = '';
      $mesg = 'Fiche mise à jour';
    }
  else
    {
      $action = 'edit';
      $mesg = 'Fiche non mise à jour !' . "<br>" . $product->mesg_error;
    }
}
/*
 *
 *
 */
$html = new Form($db);


if ($action == 'create')
{
  print "<form action=\"$PHP_SELF?type=$type\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">\n";
  print '<input type="hidden" name="type" value="'.$type.'">'."\n";
  print '<div class="titre">Nouveau '.$types[$type].'</div><br>'."\n";
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>Référence</td><td><input name="ref" size="20" value=""></td></tr>';
  print '<td>Libellé</td><td><input name="libelle" size="40" value=""></td></tr>';
  print '<tr><td>Prix de vente</td><TD><input name="price" size="10" value=""></td></tr>';    
  print '<tr><td>Taux TVA</td><TD>';
  print $html->select_tva("tva_tx");
  print ' %</td></tr>';    
  print "<tr><td valign=\"top\">Description</td><td>";
  print '<textarea name="desc" rows="8" cols="50">';
  print "</textarea></td></tr>";
  if ($type == 1)
    {
      print '<tr><td>Durée</td><TD><input name="duration_value" size="6" value="'.$product->duree.'">';
      print '<input name="duration_unit" type="radio" value="d">jour&nbsp;';
      print '<input name="duration_unit" type="radio" value="w">semaine&nbsp;';
      print '<input name="duration_unit" type="radio" value="m">mois&nbsp;';
      print '<input name="duration_unit" type="radio" value="y">année';
      print '</td></tr>';
    }
  
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';      
}
else
{
  if ($id)
    {
      $contrat = new Contrat($db);
      $result = $contrat->fetch($id);

      if ( $result )
	{ 
	  $facture = new Facture($db);
	  $facture->fetch($contrat->factureid);


	  print_fiche_titre('Fiche contrat : '.$contrat->id, $mesg);
      
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Service</td><td colspan=4>'.($contrat->product->ref).' - '.($contrat->product->label_url).'</td>';
	  print '</tr><tr>';	
	  if ($contrat->factureid)
	    {
	      print '<td>Société</td><td>'.$contrat->societe->nom_url.'</td>';
	      print '<td>Facture</td><td><a href="../compta/facture.php?facid='.$contrat->factureid.'">Facture</td>';
	    }
	  else
	    {
	      print '<td>Société</td><td colspan="4">'.$contrat->societe->nom_url.'</td></tr>';
	    }

	  print '<tr><td>Etat</td><td colspan="3">';
	  if ($contrat->enservice)
	    {
	      print "En service";
	    }
	  else
	    {
	      print "<b>Ce contrat n'est pas en service</b>";
	    }
      print '</td></tr>';
	  if ($request == 'miseenservice')
	    {
	      print '<form action="fiche.php?id='.$id.'" method="post">';
	      print '<input type="hidden" name="action" value="miseenservice">';
	      print '<input type="hidden" name="duration" value="'.$contrat->product->duration.'">';
	      print '<tr><td>Mis en service</td><td colspan="3">';
	      print $html->select_date('','re',1,1);
	      print "&nbsp;";
	      print '<input type="submit" value="Enregistrer"></td></tr>';
	      print '</form>';
	    }

	  if ($contrat->enservice > 0)
	    {
	      print "<tr><td valign=\"top\">Mis en service</td><td>".strftime("%A %e %B %Y à %H:%M",$contrat->mise_en_service);
	      print "</td>";
	      $contrat->user_service->fetch();
	      print '<td>par</td><td>'.$contrat->user_service->fullname.'</td></tr>';
	      
	      print '<tr><td valign="top">Fin de validité</td><td colspan="3">'.strftime("%A %e %B %Y à %H:%M",$contrat->date_fin_validite);
	    }
	  
	  if ($contrat->enservice == 2)
	    {
	      print "<tr><td valign=\"top\">Cloturé</td><td>".strftime("%A %e %B %Y à %H:%M",$contrat->date_cloture)."</td>";
	      $contrat->user_cloture->fetch();
	      print '<td>par</td><td>'.$contrat->user_cloture->fullname.'</td></tr>';
	    }


	  print "</table>";
	}
      
    }
  else
    {
      print "Error";
    }
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */


print '<br>';
print '<div class="tabsAction">';

if (! $contrat->enservice)
{
    print '<a class="tabAction" href="fiche.php?request=miseenservice&id='.$id.'">Mise en service</a>';
}
elseif ($contrat->enservice == 1)
{
  print '<a class="tabAction" href="fiche.php?action=annule&id='.$id.'">Annuler</a>';
  print '<a class="tabAction" href="fiche.php?action=cloture&id='.$id.'">Clôturer</a>';
}
print '</div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
