<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Auguria SARL         <info@auguria.org>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011 	   Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/admin/produit.php
 *  \ingroup    produit
 *  \brief      Page d'administration/configuration du module Produit
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formbarcode.class.php");

$langs->load("admin");
$langs->load("products");

// Security check
if (! $user->admin) accessforbidden();

$action = GETPOST("action");
$value = GETPOST("value");


/*
 * Actions
 */

if ($action == 'nbprod')
{
	$res = dolibarr_set_const($db, "PRODUIT_LIMIT_SIZE", $value,'chaine',0,'',$conf->entity);
}
else if ($action == 'multiprix_num')
{
	$res = dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", $value,'chaine',0,'',$conf->entity);
}
if ($action == 'multiprix')
{
	$multiprix = GETPOST("activate_multiprix");

	$res = dolibarr_set_const($db, "PRODUIT_MULTIPRICES", $multiprix,'chaine',0,'',$conf->entity);
	$res =dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", "5",'chaine',0,'',$conf->entity);
}
else if ($action == 'sousproduits')
{
	$sousproduits = GETPOST("activate_sousproduits");
	$res = dolibarr_set_const($db, "PRODUIT_SOUSPRODUITS", $sousproduits,'chaine',0,'',$conf->entity);
}
else if ($action == 'viewProdDescInForm')
{
	$view = GETPOST("activate_viewProdDescInForm");
	$res = dolibarr_set_const($db, "PRODUIT_DESC_IN_FORM", $view,'chaine',0,'',$conf->entity);
}
else if ($action == 'viewProdDescInThirdpartyLanguage')
{
	$view = GETPOST("activate_viewProdDescInThirdpartyLanguage");
	$res = dolibarr_set_const($db, "PRODUIT_DESC_IN_THIRDPARTY_LANGUAGE", $view,'chaine',0,'',$conf->entity);
}
else if ($action == 'usesearchtoselectproduct')
{
	$usesearch = GETPOST("activate_usesearchtoselectproduct");
	$res = dolibarr_set_const($db, "PRODUIT_USE_SEARCH_TO_SELECT", $usesearch,'chaine',0,'',$conf->entity);
}
else if ($action == 'set')
{
	$const = "PRODUCT_SPECIAL_".strtoupper($_GET["spe"]);
	if ($_GET["value"]) $res = dolibarr_set_const($db, $const, $value,'chaine',0,'',$conf->entity);
	else $res = dolibarr_del_const($db, $const,$conf->entity);
}
/*else if ($action == 'useecotaxe')
{
	$ecotaxe = GETPOST("activate_useecotaxe");
	$res = dolibarr_set_const($db, "PRODUIT_USE_ECOTAXE", $ecotaxe,'chaine',0,'',$conf->entity);
}*/

if($action)
{
	if (! $res > 0) $error++;

 	if (! $error)
    {
        $mesg = '<font class="ok">'.$langs->trans("SetupSaved").'</font>';
    }
    else
    {
        $mesg = '<font class="error">'.$langs->trans("Error").'</font>';
    }
}

/*
 * View
 */

$formbarcode=new FormBarCode($db);

$title = $langs->trans('ProductServiceSetup');
$tab = $langs->trans("ProductsAndServices");
if (empty($conf->produit->enabled))
{
	$title = $langs->trans('ServiceSetup');
	$tab = $langs->trans('Services');
}
else if (empty($conf->service->enabled))
{
	$title = $langs->trans('ProductSetup');
	$tab = $langs->trans('Products');
}

llxHeader('',$title);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($title,$linkback,'setup');

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/product/admin/produit.php";
$head[$h][1] = $tab;
$hselected=$h;
$h++;

dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

$form=new Form($db);
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '<td width="80">&nbsp;</td></tr>'."\n";

/*
 * Formulaire parametres divers
 */

// multiprix activation/desactivation
$var=!$var;
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="multiprix">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("MultiPricesAbility").'</td>';
print '<td width="60" align="right">';
print $form->selectyesno("activate_multiprix",$conf->global->PRODUIT_MULTIPRICES,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';


// multiprix nombre de prix a proposer
if($conf->global->PRODUIT_MULTIPRICES)
{
	$var=!$var;
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="multiprix_num">';
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("MultiPricesNumPrices").'</td>';
	print '<td align="right"><input size="3" type="text" class="flat" name="value" value="'.$conf->global->PRODUIT_MULTIPRICES_LIMIT.'"></td>';
	print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
	print '</tr>';
	print '</form>';
}

// sousproduits activation/desactivation
$var=!$var;
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="sousproduits">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AssociatedProductsAbility").'</td>';
print '<td width="60" align="right">';
print $form->selectyesno("activate_sousproduits",$conf->global->PRODUIT_SOUSPRODUITS,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';

// utilisation formulaire Ajax sur choix produit
$var=!$var;
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="usesearchtoselectproduct">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UseSearchToSelectProduct").'</td>';
if (! $conf->use_javascript_ajax)
{
	print '<td nowrap="nowrap" align="right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print '</td>';
}
else
{
	print '<td width="60" align="right">';
	$arrval=array('0'=>$langs->trans("No"),
	'1'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",1).')',
    '2'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",2).')',
    '3'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",3).')',
	);
	print $form->selectarray("activate_usesearchtoselectproduct",$arrval,$conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
	print '</td><td align="right">';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</td>';
}
print '</tr>';
print '</form>';

if (empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT))
{
	$var=!$var;
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="nbprod">';
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("NumberOfProductShowInSelect").'</td>';
	print '<td align="right"><input size="3" type="text" class="flat" name="value" value="'.$conf->global->PRODUIT_LIMIT_SIZE.'"></td>';
	print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
	print '</tr>';
	print '</form>';
}

// Visualiser description produit dans les formulaires activation/desactivation
$var=!$var;
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="viewProdDescInForm">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ViewProductDescInFormAbility").'</td>';
print '<td width="60" align="right">';
print $form->selectyesno("activate_viewProdDescInForm",$conf->global->PRODUIT_DESC_IN_FORM,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';

// View product description in thirdparty language
if (! empty($conf->global->MAIN_MULTILANGS))
{
	$var=!$var;
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="viewProdDescInThirdpartyLanguage">';
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("ViewProductDescInThirdpartyLanguageAbility").'</td>';
	print '<td width="60" align="right">';
	print $form->selectyesno("activate_viewProdDescInThirdpartyLanguage",$conf->global->PRODUIT_DESC_IN_THIRDPARTY_LANGUAGE,1);
	print '</td><td align="right">';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</td>';
	print '</tr>';
	print '</form>';
}


if ($conf->global->PRODUCT_CANVAS_ABILITY)
{
	// Add canvas feature
	$dir = DOL_DOCUMENT_ROOT . "/product/canvas/";
	$var = false;

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("ProductSpecial").'</td>'."\n";
	print '<td align="right" width="60">'.$langs->trans("Value").'</td>'."\n";
	print '<td width="80">&nbsp;</td></tr>'."\n";

	if (is_dir($dir))
	{
		require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");

		$handle=opendir($dir);
        if (is_resource($handle))
        {
    		while (($file = readdir($handle))!==false)
    		{
    			if (file_exists($dir.$file.'/product.'.$file.'.class.php'))
    			{
    				$classfile = $dir.$file.'/product.'.$file.'.class.php';
    				$classname = 'Product'.ucfirst($file);

    				require_once($classfile);
    				$object = new $classname();

    				$module = $object->module;

    				if ($conf->$module->enabled)
    				{
    					$var=!$var;
    					print "<tr ".$bc[$var]."><td>";

    					print $object->description;

    					print '</td><td align="right">';

    					$const = "PRODUCT_SPECIAL_".strtoupper($file);

    					if ($conf->global->$const)
    					{
    						print img_picto($langs->trans("Active"),'tick');
    						print '</td><td align="right">';
    						print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;spe='.$file.'&amp;value=0">'.$langs->trans("Disable").'</a>';
    					}
    					else
    					{
    						print '&nbsp;</td><td align="right">';
    						print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;spe='.$file.'&amp;value=1">'.$langs->trans("Activate").'</a>';
    					}

    					print '</td></tr>';
    				}
    			}
    		}
		    closedir($handle);
        }
	}
	else
	{
		print "<tr><td><b>ERROR</b>: $dir is not a directory !</td></tr>\n";
	}

	print '</table>';
}

dol_htmloutput_mesg($mesg);

llxFooter();

$db->close();

?>
