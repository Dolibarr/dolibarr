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

require("./pre.inc.php");
require("../propal.class.php");
require("../facture.class.php");

$langs->load("products");

$user->getrights('produit');
$user->getrights('propale');
$user->getrights('facture');
$mesg = '';

if (!$user->rights->produit->lire)
{
  accessforbidden();
}


if ($_POST["action"] == 'add' && $user->rights->produit->creer)
{
  $product = new Product($db);

  $product->ref            = $_POST["ref"];
  $product->libelle        = $_POST["libelle"];
  $product->price          = $_POST["price"];
  $product->tva_tx         = $_POST["tva_tx"];
  $product->type           = $_POST["type"];
  $product->envente        = $_POST["statut"];
  $product->description    = $_POST["desc"];
  $product->duration_value = $_POST["duration_value"];
  $product->duration_unit  = $_POST["duration_unit"];
  $product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
 
  $e_product = $product;

  $id = $product->create($user);

  if ($id > 0)
    {
      Header("Location: fiche.php?id=$id");
    }
  else
    {
      if ($id == -3)
	{
	  $_error = 1;
	  $_GET["action"] = "create";
	  $_GET["type"] = $_POST["type"];
	}
    }
}

if ($_POST["action"] == 'update' && 
    $_POST["cancel"] <> 'Annuler' && 
    ( $user->rights->produit->modifier || $user->rights->produit->creer))
{
  $product = new Product($db);
  if ($product->fetch($_POST["id"]))
    {

      $product->ref                = $_POST["ref"];
      $product->libelle            = $_POST["libelle"];
      $product->price              = $_POST["price"];
      $product->tva_tx             = $_POST["tva_tx"];
      $product->description        = $_POST["desc"];
      $product->envente            = $_POST["statut"];
      $product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
      $product->duration_value = $_POST["duration_value"];
      $product->duration_unit = $_POST["duration_unit"];
      
      if ($product->check())
	{
	  if ($product->update($product->id, $user))
	    {
	      $_GET["action"] = '';
	      $mesg = 'Fiche mise à jour';
	    }
	  else
	    {
	      $_GET["action"] = 're-edit';
	      $mesg = 'Fiche non mise à jour !' . "<br>" . $product->mesg_error;
	    }
	}
      else
	{
	  $_GET["action"] = 're-edit';
	  $mesg = 'Fiche non mise à jour !' . "<br>" . $product->mesg_error;
	}
    }
  Header("Location: fiche.php?id=".$product->id);
}


if ($_POST["action"] == 'addinpropal')
{
  $propal = New Propal($db);
  $propal->fetch($_POST["propalid"]);

  $result =  $propal->insert_product($_GET["id"], $_POST["qty"], $_POST["remise_percent"]);
  if ( $result < 0)
    {
      $mesg = "erreur $result";
    }

  Header("Location: ../comm/propal.php?propalid=".$propal->id);
}

if ($_POST["action"] == 'addinfacture' && 
    ( $user->rights->facture->modifier || $user->rights->facture->creer))
{
  $product = new Product($db);
  $result = $product->fetch($_GET["id"]);

  $facture = New Facture($db);

  $facture->fetch($_POST["factureid"]);
  $facture->addline($_POST["factureid"], 
		    addslashes($product->libelle), 
		    $product->price, 
		    $_POST["qty"], 
		    $product->tva_tx, $product->id);

  Header("Location: ../compta/facture.php?facid=".$facture->id);

}

if ($_POST["action"] == 'add_fourn' && $_POST["cancel"] <> 'Annuler')
{

  $product = new Product($db);
  if( $product->fetch($_GET["id"]) )
    {
      if ($product->add_fournisseur($user, $_POST["id_fourn"], $_POST["ref_fourn"]) > 0)
	{
	  $action = '';
	  $mesg = 'Founisseur ajouté';
	}
      else
	{
	  $action = '';
	}
    }
}
if ($_GET["action"] == 'remove_fourn')
{
  $product = new Product($db);
  if( $product->fetch($_GET["id"]) )
    {
      if ($product->remove_fournisseur($user, $_GET["id_fourn"]) > 0)
	{
	  $_GET["action"] = '';
	  $mesg = 'Founisseur supprimé';
	}
      else
	{
	  $_GET["action"] = '';
	}
    }
}


if ($_POST["action"] == 'update_price' && 
    $_POST["cancel"] <> 'Annuler' && 
    ( $user->rights->produit->modifier || $user->rights->produit->creer))
{
  $product = new Product($db);

  $result = $product->fetch($_GET["id"]);

  $product->price = ereg_replace(" ","",$_POST["price"]);

  if ( $product->update_price($product->id, $user) > 0 )

    {
      $_GET["action"] = '';
      $mesg = 'Fiche mise à jour';
    }
  else
    {
      $_GET["action"] = 'edit_price';
      $mesg = 'Fiche non mise à jour !' . "<br>" . $product->mesg_error;
    }
}


if ($_POST["cancel"] == 'Annuler')
{
  $action = '';
  Header("Location: fiche.php?id=".$_POST["id"]);
}


llxHeader("","","Fiche produit");

/*
 * Création du produit
 *
 */
if ($_GET["action"] == 'create')
{
  $html = new Form($db);
  $nbligne=0;
  $product = new Product($db);
  if ($_error == 1)
    {
      $product = $e_product;
    }

  print '<form action="fiche.php" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="type" value="'.$_GET["type"].'">'."\n";
  print '<div class="titre">Nouveau '.$types[$_GET["type"]].'</div><br>'."\n";
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr>';
  print '<td>Référence</td><td><input name="ref" size="20" value="'.$product->ref.'">';
  if ($_error == 1)
    {
      print "Cette référence existe déjà";
    }
  print '</td></tr>';
  print '<tr><td>Libellé</td><td><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';
  print '<tr><td>Prix de vente</td><TD><input name="price" size="10" value="'.$product->price.'"></td></tr>';    
  print '<tr><td>Taux TVA</td><TD>';


  print $html->select_tva("tva_tx");
  print '</td></tr>';
  print '<tr><td>Statut</td><td>';
  print '<select name="statut">';
  print '<option value="1">En vente</option>';
  print '<option value="0" SELECTED>Hors Vente</option>';
  print '</td></tr>';
  
  if ($_GET["type"] == 0 && defined("MAIN_MODULE_STOCK"))
    {
      print "<tr>".'<td>Seuil stock</td><td colspan="2">';
      print '<input name="seuil_stock_alerte" size="4" value="0">';
      print '</td></tr>';
    }
  else
    {
      print '<input name="seuil_stock_alerte" type="hidden" value="0">';
    }
  print '<tr><td valign="top">Description</td><td>';
  print '<textarea name="desc" rows="8" cols="50">';
  print "</textarea></td></tr>";
  if ($_GET["type"] == 1)
    {
      print "<tr>".'<td>Durée</td><td><input name="duration_value" size="6" maxlength="5" value="'.$product->duree.'"> &nbsp;';
      print '<input name="duration_unit" type="radio" value="d">jour&nbsp;';
      print '<input name="duration_unit" type="radio" value="w">semaine&nbsp;';
      print '<input name="duration_unit" type="radio" value="m">mois&nbsp;';
      print '<input name="duration_unit" type="radio" value="y">année';
      print '</td></tr>';
    }
  
  print "<tr>".'<td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';      
}
else
{
  if ($_GET["id"])
    {
      if ($_GET["action"] <> 're-edit')
	{
	  $product = new Product($db);
	  $result = $product->fetch($_GET["id"]);
	}

      if ( $result )
	{ 

	  if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	    {
	      /*
	       *  Fiche en visu
	       */
	      
	      // Zone recherche

	      print '<div class="formsearch">';
	      print '<form action="liste.php" method="post">';

	      print '<input type="hidden" name="type" value="'.$product->type.'">';
	      print $langs->trans("Ref").': <input class="flat" type="text" size="10" name="sref">&nbsp;<input class="flat" type="submit" value="go">';

	      print 'Libellé : <input class="flat" type="text" size="20" name="snom">&nbsp;<input class="flat" type="submit" value="go">';
	      print '</form></div>';
	      

	      $head[0][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
	      $head[0][1] = 'Fiche';
	      
	      $head[1][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
	      $head[1][1] = $langs->trans("Price");
	      $h = 2;
	      if($product->type == 0)
		{
		  $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
		  $head[$h][1] = 'Stock';
		  $h++;
		}

	      $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
	      $head[$h][1] = $langs->trans('Statistics');

	      dolibarr_fiche_head($head, 0, 'Fiche '.$types[$product->type].' : '.$product->ref);

	      print($mesg);
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	      print "<tr>";
	      print '<td width="20%">'.$langs->trans("Ref").'</td><td width="40%">'.$product->ref.'</td>';
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
	      print '<tr><td>Libellé</td><td colspan="2">'.$product->libelle.'</td></tr>';
	      print '<tr><td>Prix de vente</td><td>'.price($product->price).'</td>';
	      if ($product->type == 0)
		{
		  $nblignefour=4;
		}
	      else
		{
		  $nblignefour=4;
		} 
		
	      print '<td valign="top" rowspan="'.$nblignefour.'">';
	      print 'Fournisseurs [<a href="fiche.php?id='.$product->id.'&amp;action=ajout_fourn">Ajouter</a>]';

	      $sql = "SELECT s.nom, s.idp";
	      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product_fournisseur as pf";
	      $sql .=" WHERE pf.fk_soc = s.idp AND pf.fk_product = ".$product->id;
	      $sql .= " ORDER BY lower(s.nom)";
	      
	      if ( $db->query($sql) )
		{
		  $num = $db->num_rows();
		  $i = 0;
		  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
		  $var=True;      
		  while ($i < $num)
		    {
		      $objp = $db->fetch_object($i);	  
		      $var=!$var;
		      print "<tr $bc[$var]>";
		      print '<td><a href="../fourn/fiche.php?socid='.$objp->idp.'">'.$objp->nom.'</a></td>';
		      print '<td align="right">';
		      print '<a href="fiche.php?id='.$product->id.'&amp;action=remove_fourn&amp;id_fourn='.$objp->idp.'">';
		      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/editdelete.png" border="0"></a></td></tr>';
		      $i++;
		    }
		  print '</table>';
		  $db->free();
		}

	      print '</td></tr>';

	      print '<tr><td>Taux TVA</td><TD>'.$product->tva_tx.' %</td></tr>';
	      if ($product->type == 0 && defined("MAIN_MODULE_STOCK"))
		{
		  print '<tr><td><a href="stock/product.php?id='.$product->id.'">Stock</a></td>';
		  if ($product->no_stock)
		    {
		      print "<td>Pas de définition de stock pour ce produit";
		    }
		  else
		    {
		      if ($product->stock_reel <= $product->seuil_stock_alerte)
			{
			  print '<td class="alerte">'.$product->stock_reel.' Seuil : '.$product->seuil_stock_alerte;
			}
		      else
			{
		      print "<td>".$product->stock_reel;
			}
		    }
		  print '</td></tr>';
		}
	      print "<tr><td valign=\"top\">Description</td><td>".nl2br($product->description)."</td></tr>";

	      if ($product->type == 1)
		{
		  print "<tr>".'<td>Durée</td><TD>'.$product->duration_value.'&nbsp;';
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

      print "<br></div>\n";
      
    if ($_GET["action"] == 'edit_price' && $user->rights->produit->creer)
	{
	  print '<div class="titre">Nouveau prix</div>';
	  print '<form action="fiche.php?id='.$product->id.'" method="post">';
	  print '<input type="hidden" name="action" value="update_price">';
	  print '<input type="hidden" name="id" value="'.$product->id.'">';
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr><td width="20%">Prix de vente</td><td><input name="price" size="10" value="'.price($product->price).'"></td></tr>';
	  print '<tr><td colspan="3" align="center"><input type="submit" value="Enregistrer">&nbsp;';
	  print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
	  print '</table>';
	  print '</form>';
	}
      
      /*
       * Ajouter un fournisseur
       *
       */
      if ($_GET["action"] == 'ajout_fourn' && $user->rights->produit->creer)
	{
	  print_titre ("Ajouter un fournisseur");
	  print '<form action="fiche.php?id='.$product->id.'" method="post">';
	  print '<input type="hidden" name="action" value="add_fourn">';
	  print '<input type="hidden" name="id" value="'.$product->id.'">';
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4"><tr>';
	  print '<td>Fournisseurs</td><td><select name="id_fourn">';
	  
	  $sql = "SELECT s.idp, s.nom, s.ville FROM ".MAIN_DB_PREFIX."societe as s WHERE s.fournisseur=1";	     
	  $sql .= " ORDER BY lower(s.nom)";
	  
	  if ($db->query($sql))
		{
		  $num = $db->num_rows();
		  $i = 0;		  		  
		  while ($i < $num)
		    {
		      $obj = $db->fetch_object($i);
		      print '<option value="'.$obj->idp.'">'.$obj->nom . ($obj->ville?" ($obj->ville)":"");
		      $i++;
		    }

	    }
	  print '</select></td><td>'.$langs->trans("Ref").'</td><td><input name="ref_fourn" size="25" value=""></td></tr>';
	  print '<tr><td colspan="4" align="center"><input type="submit" value="Enregistrer">&nbsp;';
	  print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
	  print '</table>';
	  print '</form>';
	}    
      
	}
      
    /*
     * Fiche en mode edition
     */
    
      if (($_GET["action"] == 'edit' || $_GET["action"] == 're-edit') && $user->rights->produit->creer)
	{
	  print_fiche_titre('Edition de la fiche '.$types[$product->type].' : '.$product->ref, $mesg);


	  print "<form action=\"fiche.php\" method=\"post\">\n";
	  print '<input type="hidden" name="action" value="update">';
	  print '<input type="hidden" name="id" value="'.$product->id.'">';
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>".'<td width="20%">'.$langs->trans("Ref").'</td><td colspan="2"><input name="ref" size="20" value="'.$product->ref.'"></td></tr>';
	  print '<td>Libellé</td><td colspan="2"><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';

	  print "<tr>".'<td>Taux TVA</td><td colspan="2">';
	  $html = new Form($db);
	  print $html->select_tva("tva_tx", $product->tva_tx);
	  print '</td></tr>';
	  print "<tr>".'<td>Statut</td><td colspan="2">';
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
	  if ($product->type == 0 && defined("MAIN_MODULE_STOCK"))
	    {
	      print "<tr>".'<td>Seuil stock</td><td colspan="2">';
	      print '<input name="seuil_stock_alerte" size="4" value="'.$product->seuil_stock_alerte.'">';
	      print '</td></tr>';
	    }
	  else
	    {
	      print '<input name="seuil_stock_alerte" type="hidden" value="0">';
	    }
	  print "<tr>".'<td valign="top">Description</td><td colspan="2">';
	  print '<textarea name="desc" rows="8" cols="50">';
	  print $product->description;
	  print "</textarea></td></tr>";

	  if ($product->type == 1)
	    {
	      print "<tr>".'<td>Durée</td><td colspan="2"><input name="duration_value" size="6" maxlength="5" value="'.$product->duration_value.'">';
	      switch ($product->duration_unit) 
		{
		case "d":
		  print '<input name="duration_unit" type="radio" value="d" checked>jour&nbsp;';
		  print '<input name="duration_unit" type="radio" value="w">semaine&nbsp;';
		  print '<input name="duration_unit" type="radio" value="m">mois&nbsp;';
		  print '<input name="duration_unit" type="radio" value="y">année';
		  break;
		case "w":
		  print '<input name="duration_unit" type="radio" value="d">jour&nbsp;';
		  print '<input name="duration_unit" type="radio" value="w" checked>semaine&nbsp;';
		  print '<input name="duration_unit" type="radio" value="m">mois&nbsp;';
		  print '<input name="duration_unit" type="radio" value="y">année';
		  break;
		case "m":
		  print '<input name="duration_unit" type="radio" value="d">jour&nbsp;';
		  print '<input name="duration_unit" type="radio" value="w">semaine&nbsp;';
		  print '<input name="duration_unit" type="radio" value="m" checked>mois&nbsp;';
		  print '<input name="duration_unit" type="radio" value="y">année';
		  break;
		case "y":
		  print '<input name="duration_unit" type="radio" value="d">jour&nbsp;';
		  print '<input name="duration_unit" type="radio" value="w">semaine&nbsp;';
		  print '<input name="duration_unit" type="radio" value="m">mois&nbsp;';
		  print '<input name="duration_unit" type="radio" value="y" checked>année';
		  break;
		default:
		  print '<input name="duration_unit" type="radio" value="d">jour&nbsp;';
		  print '<input name="duration_unit" type="radio" value="w">semaine&nbsp;';
		  print '<input name="duration_unit" type="radio" value="m">mois&nbsp;';
		  print '<input name="duration_unit" type="radio" value="y">année';
		  break;
		}
	      print '</td></tr>';
	    }

	  print "<tr>".'<td colspan="3" align="center"><input type="submit" value="Enregistrer">&nbsp;';
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

print "<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{
  if ($user->rights->produit->modifier || $user->rights->produit->creer)
    {
      print '<a class="tabAction" href="fiche.php?action=edit_price&amp;id='.$product->id.'">Changer le prix</a>';
    }
}

if ($_GET["action"] == '')
{
  if ($user->rights->produit->modifier || $user->rights->produit->creer)
    {
      print '<a class="tabAction" href="fiche.php?action=edit&amp;id='.$product->id.'">'.$langs->trans("Edit").'</a>';
    }
}
if ($product->type == 0 && defined("MAIN_MODULE_STOCK"))
{
  print '<a class="tabAction" href="stock/product.php?id='.$product->id.'&amp;action=correction">Correction stock</a>';
}

print "</div>";




if ($_GET["id"] && $_GET["action"] == '' && $product->envente)
{
  $htmls = new Form($db);
  $propal = New Propal($db);

  print '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
  if($user->rights->propale->creer)
    {
      print "<tr>".'<td width="50%" valign="top">';
      print_titre("Ajouter à ma proposition") . '</td>';

      print '<td width="50%" valign="top">';
      print_titre("Ajouter aux autres propositions") . '</td>';

      print '</tr>';
      print "<tr>".'<td width="50%" valign="top">';
      $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.ref,".$db->pdate("p.datep")." as dp";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
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
	      $objp = $db->fetch_object($i);	  
	      $var=!$var;
	      print "<TR $bc[$var]>";
	      print "<td><a href=\"../comm/propal.php?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
	      print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">$objp->nom</a></TD>\n";
	      print "<td>". strftime("%d %b",$objp->dp)."</td>\n";
	      print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	      print '<input type="hidden" name="action" value="addinpropal">';	     
	      print '<td><input type="hidden" name="propalid" value="'.$objp->propalid.'">';
	      print '<input type="text" name="qty" size="3" value="1">&nbsp;Rem.';
	      print '<input type="text" name="remise_percent" size="3" value="0"> %';
	      print " ".$product->stock_proposition;
	      print '</td><td>';
	      print '<input type="submit" value="'.$langs->trans("Add").'">';
	      print "</td>";
	      print '</form></tr>';
	      $i++;
	    }      
	  print "</table>";
	  $db->free();
	}

      print '</td>';

      if($user->rights->propale->creer)
	{
	  print '<td width="50%" valign="top">';

	  $otherprop = $propal->liste_array(1, '<>'.$user->id);
	  if (sizeof($otherprop))
	    {
	      print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	      print '<input type="hidden" name="action" value="addinpropal">';
	      print '<table class="border" width="100%" cellpadding="3" cellspacing="0">';
	      print "<tr>".'<td>Autres Propositions</td><td>';
	      $htmls->select_array("propalid", $otherprop);
	      print '</td><td>';
	      print '<input type="text" name="qty" size="3" value="1">&nbsp;Rem.';
	      print '<input type="text" name="remise_percent" size="3" value="0"> %';
	      print '</td><td>';
	      print '<input type="submit" value="'.$langs->trans("Add").'">';
	      print '</td></tr>';
	      print '</table></form>';
	    }
	  print '</td>';
	}
      print '</tr>';
    }

  if($user->rights->facture->creer)
    {
      print "<tr>".'<td width="50%" valign="top">';
      print_titre("Ajouter à ma facture");
      print '</td><td width="50%" valign="top">';
      print_titre("Ajouter aux autres factures");
      print '</td></tr>';
      print "<tr>".'<td width="50%" valign="top">';
      $sql = "SELECT s.nom, s.idp, f.rowid as factureid, f.facnumber,".$db->pdate("f.datef")." as df";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
      $sql .=" WHERE f.fk_soc = s.idp AND f.fk_statut = 0 AND f.fk_user_author = ".$user->id;
      $sql .= " ORDER BY f.datec DESC, f.rowid DESC";
      
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
	      print "<td><a href=\"../compta/facture.php?facid=$objp->factureid\">$objp->facnumber</a></TD>\n";
	      print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">$objp->nom</a></TD>\n";      	 
	      print "<td>". strftime("%d %b",$objp->df)."</td>\n";
	      print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	      print '<input type="hidden" name="action" value="addinfacture">';
	      print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
	      print '<input type="text" name="qty" size="3" value="1">&nbsp;Rem.';
	      print '<input type="text" name="remise_percent" size="3" value="0"> %';
	      print '</td><td>';
	      print '<input type="submit" value="'.$langs->trans("Add").'">';
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
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
