<?php
/* Copyright (C) 2004-2005 Laurent Destailleur       <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani          <acianfa@free.fr>
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
 */

/**
	    \file       htdocs/admin/produit.php
		\ingroup    produit
		\brief      Page d'administration/configuration du module Produit
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("propal");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'nbprod')
{
    dolibarr_set_const($db, "PRODUIT_LIMIT_SIZE", $_POST["value"]);
    Header("Location: produit.php");
    exit;
}
else if ($_POST["action"] == 'multiprix_num')
{
    dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", $_POST["value"]);
    Header("Location: produit.php");
    exit;
}
if ($_POST["action"] == 'multiprix')
{
	if ($_POST["activate_multiprix"] == 1)
	{
		$res=$db -> desc_table(MAIN_DB_PREFIX."product_price","price_level");
	  if(! $db -> fetch_row())
	  {
		  // on ajoute le champ price_level dans la table societe
		  if(! $db -> add_field(MAIN_DB_PREFIX."societe","price_level",$field_desc))
		  {
			  dolibarr_print_error($db);
			  print "<script language='JavaScript'>setTimeout(\"document.location='./produit.php'\",5000);</script>";
		  }
		  // on crée la table societe_prices
		  else
		  {
			  $table = MAIN_DB_PREFIX."societe_prices";
			  $fields['rowid'] = array('type'=>'int','value'=>'11','null'=>'not null','extra'=> 'auto_increment');
			  $fields['fk_soc'] = array('type'=>'int','value'=>'11','null'=>'not null','default'=> '0');
			  $fields['tms'] = array('type'=>'timestamp','value'=>'14','null'=>'not null');
			  $fields['datec'] = array('type'=>'datetime','default'=> 'null');
			  $fields['fk_user_author'] = array('type'=>'int','value'=>'11','default'=> 'null');
			  $fields['price_level'] = array('type'=>'tinyint','value'=>'4','default'=> '1');
			  if(! $db -> create_table($table,$fields,"rowid","InnoDB"))
			  {
				  dolibarr_print_error($db);
				  print "<script language='JavaScript'>setTimeout(\"document.location='./produit.php'\",5000);</script>";
			  }
			}
			else
			{
				dolibarr_set_const($db, "PRODUIT_MULTIPRICES", $_POST["activate_multiprix"]);
				dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", "6");
				Header("Location: produit.php");
			}
		}
	}
	else
	{
			dolibarr_set_const($db, "PRODUIT_MULTIPRICES", $_POST["activate_multiprix"]);
			Header("Location: produit.php");
	}
    exit;
}
else if ($_POST["action"] == 'sousproduits')
{
  if ($_POST["activate_sousproduits"] == 1)
  {
  	$res=$db -> desc_table(MAIN_DB_PREFIX."product_association");
	  if(! $db -> fetch_row())
	  {
		  $table = MAIN_DB_PREFIX."product_association";
		  $fields['fk_product_pere'] = array('type'=>'int','value'=>'11','null'=> 'not null','default'=> '0');
		  $fields['fk_product_fils'] = array('type'=>'int','value'=>'11','null'=> 'not null','default'=> '0');
		  $fields['qty'] = array('type'=>'double','default'=> 'null');
		  $keys['idx_product_association_fk_product_pere'] = "fk_product_pere" ;
		  $keys['idx_product_association_fk_product_fils'] = "fk_product_fils" ;
		  if(! $db -> create_table($table,$fields,"","InnoDB","","",$keys))
		  {
			  dolibarr_print_error($db);
			  print "<script language='JavaScript'>setTimeout(\"document.location='./produit.php'\",5000);</script>";
		  }
		}
		else
	  {
	    dolibarr_set_const($db, "PRODUIT_SOUSPRODUITS", $_POST["activate_sousproduits"]);
		  Header("Location: produit.php");
	  }
	}
	else
	{
		dolibarr_set_const($db, "PRODUIT_SOUSPRODUITS", $_POST["activate_sousproduits"]);
		Header("Location: produit.php");
	}
}
else if ($_POST["action"] == 'confirmdeleteline')
{
    dolibarr_set_const($db, "PRODUIT_CONFIRM_DELETE_LINE", $_POST["activate_confirmdeleteline"]);
    Header("Location: produit.php");
    exit;
}
else if ($_POST["action"] == 'changeproductdesc')
{
    dolibarr_set_const($db, "PRODUIT_CHANGE_PROD_DESC", $_POST["activate_changeproductdesc"]);
    dolibarr_set_const($db, "FORM_ADD_PROD_DESC", 0);
    Header("Location: produit.php");
    exit;
}
else if ($_POST["action"] == 'viewProdDescInForm')
{
    dolibarr_set_const($db, "FORM_ADD_PROD_DESC", $_POST["activate_viewProdDescInForm"]);
    Header("Location: produit.php");
    exit;
}


/*
 * Affiche page
 */

llxHeader('',$langs->trans("ProductSetup"));


print_fiche_titre($langs->trans("ProductSetup"),'','setup');

$html=new Form($db);
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>".$langs->trans("Parameters")."</td>\n";
print "  <td align=\"right\" width=\"60\">".$langs->trans("Value")."</td>\n";
print "  <td width=\"80\">&nbsp;</td></tr>\n";

/*
 * Formulaire parametres divers
 */

$var=!$var;
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"nbprod\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("NumberOfProductShowInSelect").'</td>';
print "<td align=\"right\"><input size=\"3\" type=\"text\" class=\"flat\" name=\"value\" value=\"".$conf->global->PRODUIT_LIMIT_SIZE."\"></td>";
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';


// multiprix activation/desactivation
$var=!$var;
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"multiprix\">";
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("MultiPricesAbility").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("activate_multiprix",$conf->global->PRODUIT_MULTIPRICES,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print '</form>';


// multiprix nombre de prix a proposer
if($conf->global->PRODUIT_MULTIPRICES == 1)
{
	$var=!$var;
	print "<form method=\"post\" action=\"produit.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"multiprix_num\">";
	print "<tr ".$bc[$var].">";
	print '<td>'.$langs->trans("MultiPricesNumPrices").'</td>';
	print "<td align=\"right\"><input size=\"3\" type=\"text\" class=\"flat\" name=\"value\" value=\"".$conf->global->PRODUIT_MULTIPRICES_LIMIT."\"></td>";
	print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
	print '</tr>';
	print '</form>';
}

// sousproduits activation/desactivation
$var=!$var;
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"sousproduits\">";
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("AssociatedProductsAbility").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("activate_sousproduits",$conf->global->PRODUIT_SOUSPRODUITS,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print '</form>';

// confirmation de suppression ligne produit activation/desactivation
$var=!$var;
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"confirmdeleteline\">";
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("ConfirmDeleteProductLineAbility").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("activate_confirmdeleteline",$conf->global->PRODUIT_CONFIRM_DELETE_LINE,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print '</form>';

// Modification description produit activation/desactivation
$var=!$var;
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"changeproductdesc\">";
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("ModifyProductDescAbility").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("activate_changeproductdesc",$conf->global->PRODUIT_CHANGE_PROD_DESC,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print '</form>';

if ($conf->global->PRODUIT_CHANGE_PROD_DESC == 0)
{
	// Visualiser description produit dans les formulaires activation/desactivation
  $var=!$var;
  print "<form method=\"post\" action=\"produit.php\">";
  print "<input type=\"hidden\" name=\"action\" value=\"viewProdDescInForm\">";
  print "<tr ".$bc[$var].">";
  print '<td width="80%">'.$langs->trans("ViewProductDescInFormAbility").'</td>';
  print '<td width="60" align="right">';
  print $html->selectyesno("activate_viewProdDescInForm",$conf->global->FORM_ADD_PROD_DESC,1);
  print '</td><td align="right">';
  print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
  print "</td>";
  print '</tr>';
  print '</form>';
}

print '</table>';

$db->close();

llxFooter();
?>
