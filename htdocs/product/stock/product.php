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

$user->getrights('produit');
$mesg = '';

if (!$user->rights->produit->lire)
{
  accessforbidden();
}

llxHeader("","","Fiche produit");

if ($HTTP_POST_VARS["action"] == "create_stock")
{
  $product = new Product($db);
  $product->id = $_GET["id"];
  $product->create_stock($HTTP_POST_VARS["id_entrepot"], $HTTP_POST_VARS["nbpiece"]);
}

if ($HTTP_POST_VARS["action"] == "correct_stock")
{
  $product = new Product($db);
  $product->id = $_GET["id"];
  $product->correct_stock($user, 
			  $HTTP_POST_VARS["id_entrepot"], 
			  $HTTP_POST_VARS["nbpiece"],
			  $HTTP_POST_VARS["mouvement"]);
}


if ($cancel == 'Annuler')
{
  $action = '';
}
/*
 *
 *
 */
if ($id)
{
  if ($action <> 're-edit')
    {
      $product = new Product($db);
      $result = $product->fetch($id);
    }
  
  if ( $result )
    { 
      if ($action <> 'edit' && $action <> 're-edit')
	{
	  
	  print_fiche_titre('Fiche stock : '.$product->ref, $mesg);
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Référence</td><td width="40%"><a href="../fiche.php?id='.$product->id.'">'.$product->ref.'</a></td>';
	  print '<td width="40%">';
	  if ($product->envente)
	    {
	      print "En vente";
	    }
	  else
	    {
	      print "<b>Cet article n'est pas en vente</b>";
	    }
	  print '</td></tr>';
	  print "<tr><td>Libellé</td><td>$product->libelle</td>";
	  print '<td><a href="'.DOL_URL_ROOT.'/product/stats/fiche.php?id='.$id.'">Statistiques</a></td></tr>';
	  print '<tr><td>Prix de vente</td><td>'.price($product->price).'</td>';
	  print '<td valign="top" rowspan="2">';
	  print 'Fournisseurs [<a href="../fiche.php?id='.$id.'&amp;action=ajout_fourn">Ajouter</a>]';
	  
	  $sql = "SELECT s.nom, s.idp";
	  $sql .= " FROM llx_societe as s, llx_product_fournisseur as pf";
	  $sql .=" WHERE pf.fk_soc = s.idp AND pf.fk_product =$id";
	  $sql .= " ORDER BY lower(s.nom)";
	  
	  if ( $db->query($sql) )
	    {
	      $num = $db->num_rows();
	      $i = 0;
	      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
	      $var=True;      
	      while ($i < $num)
		{
		  $objp = $db->fetch_object( $i);	  
		  $var=!$var;
		  print "<TR $bc[$var]>";
		  print '<td><a href="../fourn/fiche.php?socid='.$objp->idp.'">'.$objp->nom.'</a></td></tr>';
		  $i++;
		}
	      print '</table>';
	      $db->free();
	    }
	  
	  print '</td></tr>';
	  
	  print '<tr><td>Stock seuil</td><td>'.$product->seuil_stock_alerte.'</td></tr>';
	  
	  print "</table>";
	}
      /* 
       * Contenu des stocks
       *
       */
      print '<br><table class="border" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre"><td width="40%">Entrepôt</td><td width="60%">Valeur du stock</td></tr>';
      $sql = "SELECT e.rowid, e.label, ps.reel FROM llx_entrepot as e, llx_product_stock as ps";
      $sql .= " WHERE ps.fk_entrepot = e.rowid AND ps.fk_product = $product->id";
      $sql .= " ORDER BY lower(e.label)";
      
      if ($db->query($sql))
	{
	  $num = $db->num_rows();
	  $i = 0; $total = 0;  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object( $i);
	      print '<tr><td width="40%">'.$obj->label.'</td><td>'.$obj->reel.'</td></tr>'; ;
	      $total = $total + $obj->reel;
	      $i++;
	    }
	}      
      print '<tr><td align="right">Total : </td><td>'.$total."</td></tr></table>";



    }
  /*
   * Correction du stock
   *
   *
   */
  if ($_GET["action"] == "correction")
    {
      print_titre ("Correction du stock");
      print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
      print '<input type="hidden" name="action" value="correct_stock">';
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4"><tr>';
      print '<td width="20%">Entrepôt</td><td width="20%"><select name="id_entrepot">';
      
      $sql = "SELECT e.rowid, e.label FROM llx_entrepot as e WHERE statut = 1";    
      $sql .= " ORDER BY lower(e.label)";
      
      if ($db->query($sql))
	{
	  $num = $db->num_rows();
	  $i = 0;		  		  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object( $i);
	      print '<option value="'.$obj->rowid.'">'.$obj->label ;
	      $i++;
	    }
	}
      print '</select></td>';
      print '<td width="20%"><select name="mouvement">';
      print '<option value="0">Ajouter</option>';
      print '<option value="1">Supprimer</option>';
      print '</select></td>';
      print '<td width="20%">Nb de pièce</td><td width="20%"><input name="nbpiece" size="10" value=""></td></tr>';
      print '<tr><td colspan="5" align="center"><input type="submit" value="Enregistrer">&nbsp;';
      print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
      print '</table>';
      print '</form>';

    }
  /*
   * Correction du stock
   *
   *
   */
  if ($_GET["action"] == "definir")
    {
      print_titre ("Créer un stock");
      print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
      print '<input type="hidden" name="action" value="create_stock">';
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4"><tr>';
      print '<td width="20%">Entrepôt</td><td width="40%"><select name="id_entrepot">';
      
      $sql = "SELECT e.rowid, e.label FROM llx_entrepot as e";    
      $sql .= " ORDER BY lower(e.label)";
      
      if ($db->query($sql))
	{
	  $num = $db->num_rows();
	  $i = 0;		  		  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object( $i);
	      print '<option value="'.$obj->rowid.'">'.$obj->label ;
	      $i++;
	    }
	}
      print '</select></td><td width="20%">Nb de pièce</td><td width="20%"><input name="nbpiece" size="10" value=""></td></tr>';
      print '<tr><td colspan="4" align="center"><input type="submit" value="Enregistrer">&nbsp;';
      print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
      print '</table>';
      print '</form>';
    }
}
else
{
  print "Error";
}


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print '<br><table id="actions" width="100%" cellspacing="0" cellpadding="3">';

print '<td width="20%" align="center">-</td>';

print '<td width="20%" align="center">-</td>';

if ($action == '')
{
  if ($user->rights->produit->modifier || $user->rights->produit->creer)
    {
      print '<td width="20%" align="center"><a href="../fiche.php?action=edit&amp;id='.$id.'">Editer</a></td>';
    }
  else
    {
      print '<td width="20%" align="center">-</td>';    
    }
}
else
{
  print '<td width="20%" align="center">-</td>';
}
print '<td width="20%" align="center">-</td>';    
print '<td width="20%" align="center"><a href="product.php?id='.$id.'&amp;action=correction">Correction stock</a></td>';

print '</table><br>';


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
