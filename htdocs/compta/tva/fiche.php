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
require("./pre.inc.php3");
require("../../tva.class.php3");


$mesg = '';

if ($HTTP_POST_VARS["action"] == 'add' && $HTTP_POST_VARS["cancel"] <> 'Annuler')
{
  $tva = new Tva($db);

  $tva->add_payement(mktime(12,0,0,
			    $HTTP_POST_VARS["datevmonth"],
			    $HTTP_POST_VARS["datevday"],
			    $HTTP_POST_VARS["datevyear"]
			    ),
		     mktime(12,0,0,
			    $HTTP_POST_VARS["datepmonth"],
			    $HTTP_POST_VARS["datepday"],
			    $HTTP_POST_VARS["datepyear"]
			    ),
		     $HTTP_POST_VARS["amount"]
		     );
  Header ( "Location: reglement.php");
}

llxHeader();

/*
 *
 *
 */
$html = new Form($db);
if ($action == 'create')
{
  print "<form action=\"fiche.php\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';

  print '<div class="titre">Nouveau réglement TVA</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>Date de paiement</td><td>';
  print $html->select_date("","datev");
  print '</td></tr>';
  print '<td>Date de valeur</td><td>';
  print $html->select_date("","datep");
  print '</td></tr>';
  print '<tr><td>Montant</td><TD><input name="amount" size="10" value=""></td></tr>';    
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Enregistrer">&nbsp;';
  print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
  print '</table>';
  print '</form>';      
}
else
{
  if ($id)
    {
      $product = new Product($db);
      $result = $product->fetch($id);

      if ( $result )
	{ 
	  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr class="liste_titre">';
	  print '<form action="index.php" method="post">';
	  print '<td valign="center">Réf : <input class="flat" type="text" size="10" name="sref">&nbsp;<input class="flat" type="submit" value="go"></td>';
	  print '</form><form action="index.php" method="post">';
	  print '<td>Libellé : <input class="flat" type="text" size="20" name="snom">&nbsp;<input class="flat" type="submit" value="go"></td>';
	  print '</form><td>&nbsp;</td></tr></table>';


	  print_fiche_titre('Fiche produit : '.$product->ref, $mesg);
      
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Référence</td><td width="40%">'.$product->ref.'</td>';
	  print '<td>';
	  if ($product->envente)
	    {
	      print "En vente";
	    }
	  else
	    {
	      print "Cet article n'est pas en vente";
	    }
	  print '</td></tr>';
	  print "<td>Libellé</td><td>$product->label</td>";
	  print '<td><a href="stats/fiche.php?id='.$id.'">Statistiques</a></td></tr>';
	  print '<tr><td>Prix</td><TD>'.price($product->price).'</td>';
	  print '<td valign="top" rowspan="4">';
	  print "Propositions commerciales : ".$product->count_propale();
	  print "<br>Proposé à <b>".$product->count_propale_client()."</b> clients";
	  print "<br>Factures : ".$product->count_facture();
	  print '</td></tr>';

	  print '<tr><td>Taux TVA</td><TD>'.$product->tva_tx.' %</td></tr>';
	  print "<tr><td valign=\"top\">Description</td><td>".nl2br($product->description)."</td></tr>";
	  print "</table>";
	}
    
      if ($action == 'edit')
	{
	  print '<hr><div class="titre">Edition de la fiche produit : '.$product->ref.'</div><br>';

	  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
	  print '<input type="hidden" name="action" value="update">';
	  
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4"><tr>';
	  print '<td width="20%">Référence</td><td><input name="ref" size="20" value="'.$product->ref.'"></td></tr>';
	  print '<td>Libellé</td><td><input name="libelle" size="40" value="'.$product->label.'"></td></tr>';
	  print '<tr><td>Prix</td><TD><input name="price" size="10" value="'.$product->price.'"></td></tr>';    
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

if ($id && $action == '')
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
	  
	  print "<td>";
	  
	  print strftime("%d %B %Y",$objp->dp)."</td>\n";

	  print '<form method="POST" action="fiche.php3?id='.$id.'">';
	  print '<input type="hidden" name="action" value="addinpropal">';

	  print "<td>";
	  print '<input type="hidden" name="propalid" value="'.$objp->propalid.'">';
	  print '<input type="text" name="qty" size="3" value="1">';
	  print '</td><td>';
	  print '<input type="submit" value="Ajouter">';
	  print "</td>";
	  print '</form>';

	  
	  print "</tr>\n";
	  
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
  print '</td></tr></table>';
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
