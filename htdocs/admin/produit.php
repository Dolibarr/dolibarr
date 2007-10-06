<?php
/* Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C)      2006 Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C)      2007 Auguria SARL         <info@auguria.org>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
  $res=$db->DDLDescTable(MAIN_DB_PREFIX."societe","price_level");
  if(! $db->fetch_row($res))
  {
  	$field_desc = array('type'=>'TINYINT','value'=>'4','default'=>'1');
    if ($_POST["activate_multiprix"])
    {
    	// on ajoute le champ price_level dans la table societe
    	if ($db->DDLAddField(MAIN_DB_PREFIX."societe","price_level",$field_desc) < 0)
	    {
	      dolibarr_print_error($db);
	      exit;
	    }
	  }
	  dolibarr_set_const($db, "PRODUIT_MULTIPRICES", $_POST["activate_multiprix"]);
    dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", "6");
    Header("Location: produit.php");
    exit;
  }
  else
  {
  	dolibarr_syslog("Table definition for ".MAIN_DB_PREFIX."societe already ok");
    dolibarr_set_const($db, "PRODUIT_MULTIPRICES", $_POST["activate_multiprix"]);
    dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", "6");
    Header("Location: produit.php");
    exit;
  }
}
else if ($_POST["action"] == 'sousproduits')
{
  $res=$db->DDLDescTable(MAIN_DB_PREFIX."product_association");
  if(! $db->fetch_row($res))
    {
      $table = MAIN_DB_PREFIX."product_association";
      $fields['fk_product_pere'] = array('type'=>'int','value'=>'11','null'=> 'not null','default'=> '0');
      $fields['fk_product_fils'] = array('type'=>'int','value'=>'11','null'=> 'not null','default'=> '0');
      $fields['qty'] = array('type'=>'double','default'=> 'null');
      $keys['idx_product_association_fk_product_pere'] = "fk_product_pere" ;
      $keys['idx_product_association_fk_product_fils'] = "fk_product_fils" ;
      if ($db->DDLCreateTable($table,$fields,"","InnoDB","","",$keys) < 0)
	{
	  dolibarr_print_error($db);
	  exit;
	}
      else
	{
	  dolibarr_set_const($db, "PRODUIT_SOUSPRODUITS", $_POST["activate_sousproduits"]);
	  Header("Location: produit.php");
	  exit;
	}
    }
  else
    {
      dolibarr_syslog("Table definition already ok");
      dolibarr_set_const($db, "PRODUIT_SOUSPRODUITS", $_POST["activate_sousproduits"]);
      Header("Location: produit.php");
      exit;
    }
}
else if ($_POST["action"] == 'changeproductdesc')
{
  dolibarr_set_const($db, "PRODUIT_CHANGE_PROD_DESC", $_POST["activate_changeproductdesc"]);
  Header("Location: produit.php");
  exit;
}
else if ($_POST["action"] == 'viewProdDescInForm')
{
  dolibarr_set_const($db, "PRODUIT_DESC_IN_FORM", $_POST["activate_viewProdDescInForm"]);
  Header("Location: produit.php");
  exit;
}
else if ($_POST["action"] == 'confirmDeleteProdLineInForm')
{
  dolibarr_set_const($db, "PRODUIT_CONFIRM_DELETE_LINE", $_POST["activate_confirmDeleteProdLineInForm"]);
  Header("Location: produit.php");
  exit;
}
else if ($_POST["action"] == 'ProductCanvasAbility')
{
  dolibarr_set_const($db, "PRODUCT_CANVAS_ABILITY", $_POST["ProductCanvasAbility"]);
  Header("Location: produit.php");
  exit;
}
else if ($_POST["action"] == 'usesearchtoselectproduct')
{
  dolibarr_set_const($db, "PRODUIT_USE_SEARCH_TO_SELECT", $_POST["activate_usesearchtoselectproduct"]);
  Header("Location: produit.php");
  exit;
}
else if ($_GET["action"] == 'set')
{
  $const = "PRODUIT_SPECIAL_".strtoupper($_GET["spe"]);
  dolibarr_set_const($db, $const, $_GET["value"]);
  Header("Location: produit.php");
  exit;
}
else if ($_POST["action"] == 'useecotaxe')
{
  dolibarr_set_const($db, "PRODUIT_USE_ECOTAXE", $_POST["activate_useecotaxe"]);
  Header("Location: produit.php");
  exit;
}
else if ($_POST["action"] == 'setdefaultbarcodetype')
{
  dolibarr_set_const($db, "PRODUIT_DEFAULT_BARCODE_TYPE", $_POST["coder_id"]);
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

// utilisation formulaire Ajax sur choix produit
$var=!$var;
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"usesearchtoselectproduct\">";
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("UseSearchToSelectProduct").'</td>';
if (! $conf->use_ajax)
{
  print '<td nowrap="nowrap" align="right" colspan="2">';
  print $langs->trans("NotAvailableWhenAjaxDisabled");	
  print "</td>";
}
else
{
  print '<td width="60" align="right">';
  print $html->selectyesno("activate_usesearchtoselectproduct",$conf->global->PRODUIT_USE_SEARCH_TO_SELECT,1);
  print '</td><td align="right">';
  print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
  print "</td>";
}
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

// Visualiser description produit dans les formulaires activation/desactivation
$var=!$var;
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"viewProdDescInForm\">";
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("ViewProductDescInFormAbility").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("activate_viewProdDescInForm",$conf->global->PRODUIT_DESC_IN_FORM,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print '</form>';

// Confirmation de suppression d'un ligne produit dans les formulaires activation/desactivation
$var=!$var;
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"confirmDeleteProdLineInForm\">";
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("ConfirmDeleteProductLineAbility").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("activate_confirmDeleteProdLineInForm",$conf->global->PRODUIT_CONFIRM_DELETE_LINE,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print '</form>';

// Utilisation de l'écotaxe
$var=!$var;
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"useecotaxe\">";
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("UseEcoTaxeAbility").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("activate_useecotaxe",$conf->global->PRODUIT_USE_ECOTAXE,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';
print '</form>';

// Barcode
if ($conf->barcode->enabled && $conf->global->PRODUIT_USE_BARCODE)
{
	$var=!$var;
	print "<form method=\"post\" action=\"produit.php\">";
  print "<input type=\"hidden\" name=\"action\" value=\"setdefaultbarcodetype\">";
  print "<tr ".$bc[$var].">";
  print '<td width="80%">'.$langs->trans("SetDefaultBarcodeType").'</td>';
  print '<td width="60" align="right">';
  print $html->select_barcode_type($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE,"coder_id",1);
  print '</td><td align="right">';
  print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
  print "</td>";
  print '</tr>';
  print '</form>';
}

print '<tr class="liste_titre">';
print "  <td>".$langs->trans("ProductSpecial")."</td>\n";
print "  <td align=\"right\" width=\"60\">".$langs->trans("Value")."</td>\n";
print "  <td width=\"80\">&nbsp;</td></tr>\n";

print '<form method="post" action="produit.php">';
print '<input type="hidden" name="action" value="ProductCanvasAbility">';
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("ProductCanvasAbility").'</td>';
print '<td width="60" align="right">';
print $html->selectyesno("ProductCanvasAbility",$conf->global->PRODUCT_CANVAS_ABILITY,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr></form>';

require_once(DOL_DOCUMENT_ROOT . "/product.class.php");
$dir = DOL_DOCUMENT_ROOT . "/product/canvas/";

if ($conf->global->PRODUCT_CANVAS_ABILITY==="1")
{
  if(is_dir($dir) )
    {
      $handle=opendir($dir);
      $var=true;
  
      while (($file = readdir($handle))!==false)
	{
	  if (substr($file, strlen($file) -10) == '.class.php' && substr($file,0,8) == 'product.')
	    {
	      $parts = explode('.',$file);
	      $classname = 'Product'.ucfirst($parts[1]);	  
	      require_once($dir.$file);	  
	      $module = new $classname();
	      
	      $var=!$var;
	      print "<tr $bc[$var]><td>";
	      
	      print $module->description;
	      
	      print '</td><td align="center">';
	      
	      if (defined ("PRODUIT_SPECIAL_LIVRE") && PRODUIT_SPECIAL_LIVRE == 1)
		{
		  print img_tick();
		  print '</td><td align="center">';
		  print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;spe='.$parts[1].'&amp;value=0">'.$langs->trans("Disable").'</a>';	      
		}
	      else
		{
		  print '&nbsp;</td><td align="center">';
		  print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;spe='.$parts[1].'&amp;value=1">'.$langs->trans("Activate").'</a>';
		}
	      
	      print '</td></tr>';
	    }
	}
      closedir($handle);
    }
  else
    {
      print "<tr><td><b>ERROR</b>: $dir is not a directory !</td></tr>\n";
    }
}
print '</table>';

$db->close();

llxFooter();
?>
