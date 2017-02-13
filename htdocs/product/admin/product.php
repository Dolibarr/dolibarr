<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Auguria SARL         <info@auguria.org>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2016      Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2016	   Ferran Marcet		<fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *  \file       htdocs/product/admin/product.php
 *  \ingroup    produit
 *  \brief      Setup page of product module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';

$langs->load("admin");
$langs->load("products");

// Security check
if (! $user->admin || (empty($conf->product->enabled) && empty($conf->service->enabled)))
	accessforbidden();

$action = GETPOST('action','alpha');
$value = GETPOST('value','alpha');
$type = GETPOST('type','alpha');
$label = GETPOST('label','alpha');
$scandir = GETPOST('scandir','alpha');

// Pricing Rules
$select_pricing_rules=array(
	'PRODUCT_PRICE_UNIQ'=>$langs->trans('PriceCatalogue'),				// Unique price
	'PRODUIT_MULTIPRICES'=>$langs->trans('MultiPricesAbility'),			// Several prices according to a customer level
	'PRODUIT_CUSTOMER_PRICES'=>$langs->trans('PriceByCustomer'),		// Different price for each customer
);
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
{
    $langs->load("admin");
	$select_pricing_rules['PRODUIT_CUSTOMER_PRICES_BY_QTY'] = $langs->trans('PriceByQuantity').' ('.$langs->trans("VersionExperimental").')';	// TODO If this is enabled, price must be hidden when price by qty is enabled, also price for quantity must be used when adding product into order/propal/invoice
	$select_pricing_rules['PRODUIT_CUSTOMER_PRICES_BY_QTY&PRODUIT_MULTIPRICES'] = $langs->trans('MultiPricesAbility') . '+' . $langs->trans('PriceByQuantity').' ('.$langs->trans("VersionExperimental").')';
}

// Clean param
if (! empty($conf->global->PRODUIT_MULTIPRICES) && empty($conf->global->PRODUIT_MULTIPRICES_LIMIT)) {
	dolibarr_set_const($db, 'PRODUIT_MULTIPRICES_LIMIT', 5, 'chaine', 0, '', $conf->entity);
}



/*
 * Actions
 */

if ($action == 'setcodeproduct')
{
	if (dolibarr_set_const($db, "PRODUCT_CODEPRODUCT_ADDON",$value,'chaine',0,'',$conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

// Define constants for submodules that contains parameters (forms with param1, param2, ... and value1, value2, ...)
if ($action == 'setModuleOptions')
{
	$post_size=count($_POST);

	$db->begin();

	for($i=0;$i < $post_size;$i++)
    {
    	if (array_key_exists('param'.$i,$_POST))
    	{
    		$param=GETPOST("param".$i,'alpha');
    		$value=GETPOST("value".$i,'alpha');
    		if ($param) $res = dolibarr_set_const($db,$param,$value,'chaine',0,'',$conf->entity);
	    	if (! $res > 0) $error++;
    	}
    }
	if (! $error)
    {
        $db->commit();
	//setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        $db->rollback();
	// message yet present at the bottom if($action)
	//setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'other' && GETPOST('value_PRODUIT_LIMIT_SIZE') >= 0)
{
	$res = dolibarr_set_const($db, "PRODUIT_LIMIT_SIZE", GETPOST('value_PRODUIT_LIMIT_SIZE'),'chaine',0,'',$conf->entity);
}
if ($action == 'other' && GETPOST('value_PRODUIT_MULTIPRICES_LIMIT') > 0)
{
	$res = dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", GETPOST('value_PRODUIT_MULTIPRICES_LIMIT'),'chaine',0,'',$conf->entity);
}
if ($action == 'other')
{
	$princingrules = GETPOST('princingrule','alpha');
	foreach ($select_pricing_rules as $rule=>$label) // Loop on each possible mode
	{
		if ($rule == $princingrules) // We are on selected rule, we enable it
		{
			if ($princingrules == 'PRODUCT_PRICE_UNIQ') // For this case, we disable entries manually
			{
				$res = dolibarr_set_const($db, 'PRODUIT_MULTIPRICES', 0, 'chaine', 0, '', $conf->entity);
				$res = dolibarr_set_const($db, 'PRODUIT_CUSTOMER_PRICES_BY_QTY', 0, 'chaine', 0, '', $conf->entity);
				$res = dolibarr_set_const($db, 'PRODUIT_CUSTOMER_PRICES', 0, 'chaine', 0, '', $conf->entity);
				dolibarr_set_const($db, 'PRODUCT_PRICE_UNIQ', 1, 'chaine', 0, '', $conf->entity);
			}
			else
			{
				$multirule=explode('&',$princingrules);
				foreach($multirule as $rulesselected)
				{
					$res = dolibarr_set_const($db, $rulesselected, 1, 'chaine', 0, '', $conf->entity);
				}
			}
		}
		else	// We clear this mode
		{
			if (strpos($rule,'&')===false) {
				$res = dolibarr_set_const($db, $rule, 0, 'chaine', 0, '', $conf->entity);
			}
		}

	}
}
if ($action == 'other')
{
	$value = GETPOST('activate_sousproduits','alpha');
	$res = dolibarr_set_const($db, "PRODUIT_SOUSPRODUITS", $value,'chaine',0,'',$conf->entity);
}
if ($action == 'other')
{
	$value = GETPOST('activate_viewProdDescInForm','alpha');
	$res = dolibarr_set_const($db, "PRODUIT_DESC_IN_FORM", $value,'chaine',0,'',$conf->entity);
}
if ($action == 'other')
{
	$value = GETPOST('activate_viewProdTextsInThirdpartyLanguage','alpha');
	$res = dolibarr_set_const($db, "PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE", $value,'chaine',0,'',$conf->entity);
}
if ($action == 'other') {
	$value = GETPOST('activate_mergePropalProductCard','alpha');
	$res = dolibarr_set_const($db, "PRODUIT_PDF_MERGE_PROPAL", $value,'chaine',0,'',$conf->entity);
}
if ($action == 'other')
{
	$value = GETPOST('activate_usesearchtoselectproduct','alpha');
	$res = dolibarr_set_const($db, "PRODUIT_USE_SEARCH_TO_SELECT", $value,'chaine',0,'',$conf->entity);
}

if ($action == 'specimen') // For products
{
	$modele= GETPOST('module','alpha');

	$inter = new Fichinter($db);
	$inter->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
	    $file=dol_buildpath($reldir."core/modules/product/doc/pdf_".$modele.".modules.php",0);
		if (file_exists($file))
		{
			$filefound=1;
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($filefound)
	{
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($inter,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=products&file=SPECIMEN.pdf");
			return;
		}
		else
		{
			setEventMessage($obj->error,'errors');
			dol_syslog($obj->error, LOG_ERR);
		}
	}
	else
	{
		setEventMessage($langs->trans("ErrorModuleNotFound"),'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

// Activate a model
if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

if ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		if ($conf->global->PRODUCT_ADDON_PDF == "$value") dolibarr_del_const($db, 'PRODUCT_ADDON_PDF',$conf->entity);
	}
}

// Set default model
if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "PRODUCT_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->PRODUCT_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}


if ($action == 'set')
{
	$const = "PRODUCT_SPECIAL_".strtoupper(GETPOST('spe','alpha'));
	$value = GETPOST('value');
	if (GETPOST('value','alpha')) $res = dolibarr_set_const($db, $const, $value,'chaine',0,'',$conf->entity);
	else $res = dolibarr_del_const($db, $const,$conf->entity);
}
/*else if ($action == 'useecotaxe')
{
	$ecotaxe = GETPOST("activate_useecotaxe");
	$res = dolibarr_set_const($db, "PRODUIT_USE_ECOTAXE", $ecotaxe,'chaine',0,'',$conf->entity);
}*/

if ($action == 'other')
{
    $value = GETPOST('activate_units', 'alpha');
    $res = dolibarr_set_const($db, "PRODUCT_USE_UNITS", $value, 'chaine', 0, '', $conf->entity);
}

if ($action)
{
	if (! $res > 0) $error++;

 	if (! $error)
    {
	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
	    setEventMessages($langs->trans("Error"), null, 'errors');
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
print load_fiche_titre($title,$linkback,'title_setup');

$head = product_admin_prepare_head();
dol_fiche_head($head, 'general', $tab, 0, 'product');

$form=new Form($db);

/*
 * Module to manage product / services code
 */
$dirproduct=array('/core/modules/product/');

print load_fiche_titre($langs->trans("ProductCodeChecker"), '', '');

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td>'.$langs->trans("Description").'</td>';
print '  <td>'.$langs->trans("Example").'</td>';
print '  <td align="center" width="80">'.$langs->trans("Status").'</td>';
print '  <td align="center" width="60">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

$var = true;
foreach ($dirproduct as $dirroot)
{
	$dir = dol_buildpath($dirroot,0);

    $handle = @opendir($dir);
    if (is_resource($handle))
    {
    	// Loop on each module find in opened directory
    	while (($file = readdir($handle))!==false)
    	{
    		if (substr($file, 0, 16) == 'mod_codeproduct_' && substr($file, -3) == 'php')
    		{
    			$file = substr($file, 0, dol_strlen($file)-4);

    			try {
        			dol_include_once($dirroot.$file.'.php');
    			}
    			catch(Exception $e)
    			{
    			    dol_syslog($e->getMessage(), LOG_ERR);
    			}

    			$modCodeProduct = new $file;

    			// Show modules according to features level
    			if ($modCodeProduct->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
    			if ($modCodeProduct->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

    			$var = !$var;
    			print '<tr '.$bc[$var].'>'."\n";
    			print '<td width="140">'.$modCodeProduct->name.'</td>'."\n";
    			print '<td>'.$modCodeProduct->info($langs).'</td>'."\n";
    			print '<td class="nowrap">'.$modCodeProduct->getExample($langs).'</td>'."\n";

    			if (! empty($conf->global->PRODUCT_CODEPRODUCT_ADDON) && $conf->global->PRODUCT_CODEPRODUCT_ADDON == $file)
    			{
    				print '<td align="center">'."\n";
    				print img_picto($langs->trans("Activated"),'switch_on');
    				print "</td>\n";
    			}
    			else
    			{
    				$disabled = false;
    				if (! empty($conf->multicompany->enabled) && (is_object($mc) && ! empty($mc->sharings['referent']) && $mc->sharings['referent'] == $conf->entity) ? false : true);
    				print '<td align="center">';
    				if (! $disabled) print '<a href="'.$_SERVER['PHP_SELF'].'?action=setcodeproduct&value='.$file.'">';
    				print img_picto($langs->trans("Disabled"),'switch_off');
    				if (! $disabled) print '</a>';
    				print '</td>';
    			}

    			print '<td align="center">';
    			$s=$modCodeProduct->getToolTip($langs,null,-1);
    			print $form->textwithpicto('',$s,1);
    			print '</td>';

    			print '</tr>';
    		}
    	}
    	closedir($handle);
    }
}
print '</table>';


// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'product'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows=$db->num_rows($resql);
	if ($num_rows > 0)
	{
	    print '<br>';
	    print load_fiche_titre($langs->trans("ModelModulesProduct"), '', '');

    	while ($i < $num_rows)
    	{
    		$array = $db->fetch_array($resql);
    		array_push($def, $array[0]);
    		$i++;
    	}
        
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td width="140">'.$langs->trans("Name").'</td>';
        print '<td>'.$langs->trans("Description").'</td>';
        print '<td align="center" width="80">'.$langs->trans("Status").'</td>';
        print '<td align="center" width="60">'.$langs->trans("ShortInfo").'</td>';
        print '<td align="center" width="60">'.$langs->trans("Preview").'</td>';
        print "</tr>\n";
        
        $var=true;
        foreach ($dirproduct as $dirroot)
        {
        	$dir = dol_buildpath($dirroot.'core/modules/product/doc/',0);
        	$handle=@opendir($dir);
        	if (is_resource($handle))
        	{
        		while (($file = readdir($handle))!==false)
        		{
        			if (preg_match('/\.modules\.php$/i',$file))
        			{
        				$name = substr($file, 4, dol_strlen($file) -16);
        				$classname = substr($file, 0, dol_strlen($file) -12);
        
        			    try {
                			dol_include_once($dirroot.'core/modules/product/doc/'.$file);
            			}
            			catch(Exception $e)
            			{
            			    dol_syslog($e->getMessage(), LOG_ERR);
            			}
        
            			$module = new $classname($db);
        
        				$modulequalified=1;
        				if (! empty($module->version)) {
        					if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
        					else if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;
        				}
        
        				if ($modulequalified)
        				{
        					$var = !$var;
        					print '<tr '.$bc[$var].'><td width="100">';
        					print $module->name;
        					print "</td><td>\n";
        					if (method_exists($module,'info')) print $module->info($langs);
        					else print $module->description;
        					print '</td>';
        
        					// Activate / Disable
        					if (in_array($name, $def))
        					{
        						print "<td align=\"center\">\n";
        						print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'&type=product&scandir='.$module->scandir.'&label='.urlencode($module->name).'">';
        						print img_picto($langs->trans("Enabled"),'switch_on');
        						print '</a>';
        						print "</td>";
        					}
        					else
        					{
        						if (versioncompare($module->phpmin,versionphparray()) > 0)
        						{
        							print "<td align=\"center\">\n";
        							print img_picto(dol_escape_htmltag($langs->trans("ErrorModuleRequirePHPVersion",join('.',$module->phpmin))),'switch_off');
        							print "</td>";
        						}
        						else
        						{
        							print "<td align=\"center\">\n";
        							print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&type=product&scandir='.$module->scandir.'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
        							print "</td>";
        						}
        					}
        
        					// Info
        					$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
        					$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
        					if ($module->type == 'pdf')
        					{
        						$htmltooltip.='<br>'.$langs->trans("Height").'/'.$langs->trans("Width").': '.$module->page_hauteur.'/'.$module->page_largeur;
        					}
        					$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
        					$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraft").': '.yn((! empty($module->option_draft_watermark)?$module->option_draft_watermark:''), 1, 1);
        
        					print '<td align="center" class="nowrap">';
        					print $form->textwithpicto('',$htmltooltip,1,0);
        					print '</td>';
        
        					// Preview
        					print '<td align="center" class="nowrap">';
        					if ($module->type == 'pdf')
        					{
        						$linkspec='<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
        					}
        					else
        					{
        						$linkspec=img_object($langs->trans("PreviewNotAvailable"),'generic');
        					}
        					print $linkspec;
        					print '</td>';
        
        					print "</tr>\n";
        				}
        			}
        		}
        		closedir($handle);
        	}
        }
        print '</table>';
	}
}
else
{
    dol_print_error($db);
}
    
    
/*
 * Other conf
 */

print "<br>";

print load_fiche_titre($langs->trans("ProductOtherConf"), '', '');



print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="other">';

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '<td width="80">&nbsp;</td></tr>'."\n";


/*
 * Other parameters
 */

$rowspan = 4;
if (! empty($conf->global->PRODUIT_MULTIPRICES)) $rowspan++;
if (empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)) $rowspan++;
if (! empty($conf->global->MAIN_MULTILANGS)) $rowspan++;

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("PricingRule").'</td>';
print '<td width="60" align="right">';
$current_rule = 'PRODUCT_PRICE_UNIQ';
if (!empty($conf->global->PRODUIT_MULTIPRICES)) $current_rule='PRODUIT_MULTIPRICES';
if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY)) $current_rule='PRODUIT_CUSTOMER_PRICES_BY_QTY';
if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) $current_rule='PRODUIT_CUSTOMER_PRICES';
if ((!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY)) && (!empty($conf->global->PRODUIT_MULTIPRICES))) $current_rule='PRODUIT_CUSTOMER_PRICES_BY_QTY&PRODUIT_MULTIPRICES';
print $form->selectarray("princingrule",$select_pricing_rules,$current_rule);
if ( empty($conf->multicompany->enabled))
{
    print $langs->trans("SamePriceAlsoForSharedCompanies");
}
print '</td><td align="right" rowspan="'.$rowspan.'" class="nohover">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';


// multiprix nombre de prix a proposer
if (! empty($conf->global->PRODUIT_MULTIPRICES))
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("MultiPricesNumPrices").'</td>';
	print '<td align="right"><input size="3" type="text" class="flat" name="value_PRODUIT_MULTIPRICES_LIMIT" value="'.$conf->global->PRODUIT_MULTIPRICES_LIMIT.'"></td>';
	print '</tr>';
}

// sousproduits activation/desactivation
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AssociatedProductsAbility").'</td>';
print '<td width="60" align="right">';
print $form->selectyesno("activate_sousproduits",$conf->global->PRODUIT_SOUSPRODUITS,1);
print '</td>';
print '</tr>';

// Utilisation formulaire Ajax sur choix produit
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$form->textwithpicto($langs->trans("UseSearchToSelectProduct"),$langs->trans('UseSearchToSelectProductTooltip'),1).'</td>';
if (empty($conf->use_javascript_ajax))
{
	print '<td class="nowrap" align="right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print '</td>';
}
else
{
	print '<td width="60" align="right">';
	$arrval=array(
		'0'=>$langs->trans("No"),
		'1'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",1).')',
	    '2'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",2).')',
	    '3'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",3).')',
	);
	print $form->selectarray("activate_usesearchtoselectproduct",$arrval,$conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
	print '</td>';
}
print '</tr>';

if (empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT))
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("NumberOfProductShowInSelect").'</td>';
	print '<td align="right"><input size="3" type="text" class="flat" name="value_PRODUIT_LIMIT_SIZE" value="'.$conf->global->PRODUIT_LIMIT_SIZE.'"></td>';
	print '</tr>';
}

// Visualiser description produit dans les formulaires activation/desactivation
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ViewProductDescInFormAbility").'</td>';
print '<td width="60" align="right">';
print $form->selectyesno("activate_viewProdDescInForm",$conf->global->PRODUIT_DESC_IN_FORM,1);
print '</td>';
print '</tr>';

// Activate propal merge produt card
/* Kept as hidden feature only. PRODUIT_PDF_MERGE_PROPAL can be added manually. Still did not understand how this feature works.
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("MergePropalProductCard").'</td>';
print '<td width="60" align="right">';
print $form->selectyesno("activate_mergePropalProductCard",$conf->global->PRODUIT_PDF_MERGE_PROPAL,1);
print '</td>';
print '</tr>';
*/

// Use units
/* Kept as hidden feature only. PRODUCT_USE_UNITS is hidden for the moment. Because it seems to be a duplicated feature with already existing field to store unit of product
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UseUnits").'</td>';
print '<td width="60" align="right">';
print $form->selectyesno("activate_units",$conf->global->PRODUCT_USE_UNITS,1);
print '</td>';
print '</tr>';
*/

// View product description in thirdparty language
if (! empty($conf->global->MAIN_MULTILANGS))
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("ViewProductDescInThirdpartyLanguageAbility").'</td>';
	print '<td width="60" align="right">';
	print $form->selectyesno("activate_viewProdTextsInThirdpartyLanguage", (! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)?$conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE:0), 1);
	print '</td>';
	print '</tr>';
}


if (! empty($conf->global->PRODUCT_CANVAS_ABILITY))
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
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

		$handle=opendir($dir);
        if (is_resource($handle))
        {
    		while (($file = readdir($handle))!==false)
    		{
    			if (file_exists($dir.$file.'/product.'.$file.'.class.php'))
    			{
    				$classfile = $dir.$file.'/product.'.$file.'.class.php';
    				$classname = 'Product'.ucfirst($file);

    				require_once $classfile;
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
		setEventMessages($dir.' '.$langs->trans("IsNotADir"), null, 'errors');
	}
}

print '</table>';

print '</form>';

llxFooter();

$db->close();

