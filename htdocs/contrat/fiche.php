<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../facture.class.php3");

llxHeader();

$db = new Db();
$mesg = '';

if ($action == 'add')
{
  $contrat = new Contrat($db);

  $id = $product->create($user);
  $action = '';
}

if ($action == 'update' && $cancel <> 'Annuler')
{
  $product = new Product($db);

  $product->ref = $HTTP_POST_VARS["ref"];
  $product->libelle = $HTTP_POST_VARS["libelle"];
  $product->price = $HTTP_POST_VARS["price"];
  $product->tva_tx = $HTTP_POST_VARS["tva_tx"];
  $product->description = $HTTP_POST_VARS["desc"];
  $product->envente = $HTTP_POST_VARS["statut"];
  $product->duration_value = $HTTP_POST_VARS["duration_value"];
  $product->duration_unit = $HTTP_POST_VARS["duration_unit"];

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
if ($action == 'create')
{
  print "<form action=\"$PHP_SELF?type=$type\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">\n";
  print '<input type="hidden" name="type" value="'.$type.'">'."\n";
  print '<div class="titre">Nouveau '.$types[$type].'</div><br>'."\n";
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>Référence</td><td><input name="ref" size="20" value=""></td></tr>';
  print '<td>Libellé</td><td><input name="libelle" size="40" value=""></td></tr>';
  print '<tr><td>Prix de vente</td><TD><input name="price" size="10" value=""></td></tr>';    
  print '<tr><td>Taux TVA</td><TD>';
  $html = new Form($db);
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
	  print_fiche_titre('Fiche contrat : '.$contrat->id, $mesg);
      
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Service</td><td width="40%">'.$product->ref.'</td>';
	  print '<td>';
	  if ($product->enservice)
	    {
	      print "En service";
	    }
	  else
	    {
	      print "<b>Ce contrat n'est pas en service</b>";
	    }
	  print '</td></tr>';
	  print '<td>Société</td><td>'.$contrat->societe->nom_url.'</td>';
	  print '<td><a href="stats/fiche.php?id='.$id.'">Statistiques</a></td></tr>';

	  print "<tr><td valign=\"top\">Description</td><td>".nl2br($product->description)."</td></tr>";

	  if ($product->type == 1)
	    {
	      print '<tr><td>Durée</td><TD>'.$product->duration_value.'&nbsp;';
	      if ($product->duration_value > 1)
		{
		  $plu = "s";
		}
	      switch ($product->duration_unit) 
		{
		case "d":
		  print "jour$plu&nbsp;";
		  break;
		case "w":
		  print "semaine$plu&nbsp;";
		  break;
		case "m":
		  print 'mois&nbsp;';
		  break;
		case "y":
		  print "an$plu&nbsp;";
		  break;
		}
	      print '</td></tr>';
	    }

	  print "</table>";
	}
    
      if ($action == 'edit')
	{
	  print '<hr><div class="titre">Edition de la fiche '.$types[$product->type].' : '.$product->ref.'</div><br>';

	  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
	  print '<input type="hidden" name="action" value="update">';
	  
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4"><tr>';
	  print '<td width="20%">Référence</td><td><input name="ref" size="20" value="'.$product->ref.'"></td></tr>';
	  print '<td>Libellé</td><td><input name="libelle" size="40" value="'.$product->label.'"></td></tr>';
	  print '<tr><td>Prix de vente</td><TD><input name="price" size="10" value="'.$product->price.'"></td></tr>';    
	  print '<tr><td>Taux TVA</td><TD>';
	  $html = new Form($db);
	  print $html->select_tva("tva_tx", $product->tva_tx);
	  print '</td></tr>';
	  print '<tr><td>Statut</td><TD>';
	  print '<select name="statut">';
	  if ($product->envente)
	    {
	      print '<option value="1" SELECTED>En vente</option>';
	      print '<option value="0">Hors Vente</option>';
	    }
	  else
	    {
	      print '<option value="1">En vente</option>';
	      print '<option value="0" SELECTED>Hors Vente</option>';
	    }
	  print '</td></tr>';
	  print "<tr><td valign=\"top\">Description</td><td>";
	  print '<textarea name="desc" rows="8" cols="50">';
	  print $product->description;
	  print "</textarea></td></tr>";

	  if ($product->type == 1)
	    {
	      print '<tr><td>Durée</td><TD><input name="duration_value" size="6" value="'.$product->duration_value.'">';
	      print '<input name="duration_unit" type="radio" value="d" selected>jour&nbsp;';
	      print '<input name="duration_unit" type="radio" value="w">semaine&nbsp;';
	      print '<input name="duration_unit" type="radio" value="m">mois&nbsp;';
	      print '<input name="duration_unit" type="radio" value="y">année';
	      print '</td></tr>';
	    }

	  print '<tr><td>&nbsp;</td><td><input type="submit" value="Enregistrer">&nbsp;';
	  print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
	  print '</table>';
	  print '</form>';
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

print '<br><table width="100%" border="1" cellspacing="0" cellpadding="3">';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';

if ($action == 'create')
{
  print '<td width="20%" align="center">-</td>';
}
else
{
  print '<td width="20%" align="center">[<a href="fiche.php3?action=edit&id='.$id.'">Editer</a>]</td>';
}
print '<td width="20%" align="center">-</td>';    
print '</table><br>';

if ($id && $action == '' && $product->envente)
{

  $htmls = new Form($db);
  $propal = New Propal($db);

  print '<table width="100%" border="0" cellpadding="3" cellspacing="0">';

  print '<tr><td width="50%" valign="top">';
  print_titre("Ajouter ma proposition");
  print '</td><td width="50%" valign="top">';
  print_titre("Ajouter aux autres propositions");
  print '</td></tr>';
  print '<tr><td width="50%" valign="top">';
  $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price - p.remise as price, p.ref,".$db->pdate("p.datep")." as dp";
  $sql .= " FROM llx_societe as s, llx_propal as p";
  $sql .=" WHERE p.fk_soc = s.idp AND p.fk_statut = 0 AND p.fk_user_author = ".$user->id;
  $sql .= " ORDER BY p.datec DESC, tms DESC";

  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
      print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
      $var=True;      
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<td><a href=\"../comm/propal.php3?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
	  print "<td><a href=\"../comm/fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";      	 
	  print "<td>". strftime("%d %B %Y",$objp->dp)."</td>\n";
	  print '<form method="POST" action="fiche.php3?id='.$id.'">';
	  print '<input type="hidden" name="action" value="addinpropal">';
	  print '<td><input type="hidden" name="propalid" value="'.$objp->propalid.'">';
	  print '<input type="text" name="qty" size="3" value="1">';
	  print '</td><td>';
	  print '<input type="submit" value="Ajouter">';
	  print "</td>";
	  print '</form></tr>';
	  $i++;
	}      
      print "</table>";
      $db->free();
    }

  print '</td><td width="50%" valign="top">';

  $otherprop = $propal->liste_array(1, '<>'.$user->id);
  if (sizeof($otherprop))
  {
    print '<form method="POST" action="fiche.php3?id='.$id.'">';
    print '<input type="hidden" name="action" value="addinpropal">';
    print '<table border="1" width="100%" cellpadding="3" cellspacing="0">';
    print "<tr><td>Autres Propositions</td><td>";
    $htmls->select_array("propalid", $otherprop);
    print '</td><td>';
    print '<input type="text" name="qty" size="3" value="1">';
    print '</td><td>';
    print '<input type="submit" value="Ajouter">';
    print "</td></tr>";
    print '</table></form>';
  }
  print '</td></tr>';

  print '<tr><td width="50%" valign="top">';
  print_titre("Ajouter ma facture");
  print '</td><td width="50%" valign="top">';
  print_titre("Ajouter aux autres factures");
  print '</td></tr>';
  print '<tr><td width="50%" valign="top">';
  $sql = "SELECT s.nom, s.idp, f.rowid as factureid, f.facnumber,".$db->pdate("f.datef")." as df";
  $sql .= " FROM llx_societe as s, llx_facture as f";
  $sql .=" WHERE f.fk_soc = s.idp AND f.fk_statut = 0 AND f.fk_user_author = ".$user->id;
  $sql .= " ORDER BY f.datec DESC, f.rowid DESC";

  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
      print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
      $var=True;      
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<td><a href=\"../compta/facture.php3?facid=$objp->factureid\">$objp->facnumber</a></TD>\n";
	  print "<td><a href=\"../comm/fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";      	 
	  print "<td>". strftime("%d %B %Y",$objp->df)."</td>\n";
	  print '<form method="POST" action="fiche.php3?id='.$id.'">';
	  print '<input type="hidden" name="action" value="addinfacture">';
	  print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
	  print '<input type="text" name="qty" size="3" value="1">';
	  print '</td><td>';
	  print '<input type="submit" value="Ajouter">';
	  print "</td>";
	  print '</form></tr>';
	  $i++;
	}      
      print "</table>";
      $db->free();
    }
  else
    {
      print $db->error() . "<br>" . $sql;
    }
  print '</td><td width="50%" valign="top">';
  print '</td></tr></table>';

}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
