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

require("./pre.inc.php3");
require("../propal.class.php3");

llxHeader();

$db = new Db();
$mesg = '';

if ($action == 'add')
{
  $product = new Product($db);

  $product->ref = $ref;
  $product->libelle = $libelle;
  $product->price = $price;
  $product->tva_tx = $HTTP_POST_VARS["tva_tx"];
  $product->description = $desc;

  $id = $product->create($user);
  $action = '';
}

if ($action == 'addinpropal')
{
  $propal = New Propal($db);

  $propal->fetch($HTTP_POST_VARS["propalid"]);
  $propal->insert_product($id, $HTTP_POST_VARS["qty"]);

  $action = '';
  $mesg = 'Produit ajouté à la proposition ';
  $mesg .= '<a href="../comm/propal.php3?propalid='.$propal->id.'">'.$propal->ref.'</a>';
}


if ($action == 'update' && $cancel <> 'Annuler')
{
  $product = new Product($db);

  $product->ref = $ref;
  $product->libelle = $libelle;
  $product->price = $price;
  $product->tva_tx = $HTTP_POST_VARS["tva_tx"];
  $product->description = $desc;

  $product->update($id, $user);
  $action = '';
  $mesg = 'Fiche mise à jour';
}
/*
 *
 *
 */
if ($action == 'create')
{
  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";

  print '<div class="titre">Nouveau produit</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>Référence</td><td><input name="ref" size="20" value=""></td></tr>';
  print '<td>Libellé</td><td><input name="libelle" size="40" value=""></td></tr>';
  print '<tr><td>Prix</td><TD><input name="price" size="10" value=""></td></tr>';    
  print '<tr><td>Taux TVA</td><TD>';
  $html = new Form($db);
  print $html->select_tva("tva_tx");
  print '</td></tr>';    
  print "<tr><td valign=\"top\">Description</td><td>";
  print '<textarea name="desc" rows="8" cols="50">';
  print "</textarea></td></tr>";
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
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
	  print_fiche_titre('Fiche produit : '.$product->ref, $mesg);
      
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Référence</td><td width="40%">'.$product->ref.'</td>';
	  print '<td>Statistiques</td></tr>';
	  print "<td>Libellé</td><td>$product->label</td>";
	  print '<td valign="top" rowspan="4">';
	  print "Propositions commerciales : ".$product->count_propale();
	  print "<br>Proposé à <b>".$product->count_propale_client()."</b> clients";
	  print "<br>Factures : ".$product->count_facture();
	  print '</td></tr>';
	  print '<tr><td>Prix</td><TD>'.price($product->price).'</td></tr>';
	  print '<tr><td>Taux TVA</td><TD>'.$product->tva_tx.'</td></tr>';
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
  print_titre("Ajouter à la proposition");

  $htmls = new Form($db);
  $propal = New Propal($db);

  print '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr><td width="50%" valign="top">';

  $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price - p.remise as price, p.ref,".$db->pdate("p.datep")." as dp";
  $sql .= " FROM llx_societe as s, llx_propal as p";
  $sql .=" WHERE p.fk_soc = s.idp AND p.fk_statut = 0";  
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
      
      
      print "</TABLE>";
      $db->free();
    }



  print '</td><td width="50%" valign="top">';

  print '<form method="POST" action="fiche.php3?id='.$id.'">';
  print '<input type="hidden" name="action" value="addinpropal">';
  print '<table border="1" width="100%" cellpadding="3" cellspacing="0">';
  print "<tr><td>Autres Propositions</td><td>";
  $htmls->select_array("propalid",  $propal->liste_array(1, '<>'.$user->id));
  print '</td><td>';
  print '<input type="text" name="qty" size="3" value="1">';
  print '</td><td>';
  print '<input type="submit" value="Ajouter">';
  print "</td></tr>";
  print '</table></form>';

  print '</td></tr></table>';
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
