<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if ($_POST["action"] == "create_stock")
{
  $product = new Product($db);
  $product->id = $_GET["id"];
  $product->create_stock($_POST["id_entrepot"], $_POST["nbpiece"]);
}

if ($_POST["action"] == "correct_stock" && $_POST["cancel"] <> "Annuler")
{
  if (is_numeric($_POST["nbpiece"]))
    {

      $product = new Product($db);
      $product->id = $_GET["id"];
      $product->correct_stock($user, 
			      $_POST["id_entrepot"], 
			      $_POST["nbpiece"],
			      $_POST["mouvement"]);
    }
}

/*
 *
 *
 */
if ($_GET["id"])
{

  $product = new Product($db);
  
  if ( $product->fetch($_GET["id"]))
    {
      $head[0][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
      $head[0][1] = 'Fiche';
      
      $head[1][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
      $head[1][1] = 'Prix';
      $h = 2;
      
      $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
      $head[$h][1] = 'Stock';
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
      $head[$h][1] = 'Statistiques';
      
      dolibarr_fiche_head($head, 2, 'Fiche '.$types[$product->type].' : '.$product->ref);
      
      print($mesg);    	      
      
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
      print '<td><a href="'.DOL_URL_ROOT.'/product/stats/fiche.php?id='.$product->id.'">Statistiques</a></td></tr>';
      print '<tr><td>Prix de vente</td><td>'.price($product->price).'</td>';
      print '<td valign="top" rowspan="2">';
      print 'Fournisseurs [<a href="../fiche.php?id='.$product->id.'&amp;action=ajout_fourn">Ajouter</a>]';
      
      $sql = "SELECT s.nom, s.idp";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product_fournisseur as pf";
      $sql .=" WHERE pf.fk_soc = s.idp AND pf.fk_product =$product->id";
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
      
      /* 
       * Contenu des stocks
       *
       */
      print '<br><table class="border" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre"><td width="40%">Entrepôt</td><td width="60%">Valeur du stock</td></tr>';
      $sql = "SELECT e.rowid, e.label, ps.reel FROM ".MAIN_DB_PREFIX."entrepot as e, ".MAIN_DB_PREFIX."product_stock as ps";
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
  print '</div>';
  /*
   * Correction du stock
   *
   *
   */
  if ($_GET["action"] == "correction")
    {
      print_titre ("Correction du stock");
      print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
      print '<input type="hidden" name="action" value="correct_stock">';
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4"><tr>';
      print '<td width="20%">Entrepôt</td><td width="20%"><select name="id_entrepot">';
      
      $sql = "SELECT e.rowid, e.label FROM ".MAIN_DB_PREFIX."entrepot as e WHERE statut = 1";    
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
      print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
      print '<input type="hidden" name="action" value="create_stock">';
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4"><tr>';
      print '<td width="20%">Entrepôt</td><td width="40%"><select name="id_entrepot">';
      
      $sql = "SELECT e.rowid, e.label FROM ".MAIN_DB_PREFIX."entrepot as e";    
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

print "<br><div class=\"tabsAction\">\n";

if ($_GET["action"] <> 'correction')
{
  print '<a class="tabAction" href="product.php?id='.$product->id.'&amp;action=correction">Correction stock</a>';
}
print '</div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
