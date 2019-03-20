<?php
/* Copyright (C) 2014-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018  	   Ferran Marcet 		<fmarcet@2byte.es>
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
 *	\file 		htdocs/barcode/codeinit.php
 *	\ingroup    member
 *	\brief      Page to make mass init of barcode
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'members', 'errors', 'other'));

// Choice of print year or current year.
$now = dol_now();
$year=dol_print_date($now, '%Y');
$month=dol_print_date($now, '%m');
$day=dol_print_date($now, '%d');
$forbarcode=GETPOST('forbarcode');
$fk_barcode_type=GETPOST('fk_barcode_type');
$eraseallbarcode=GETPOST('eraseallbarcode');

$action=GETPOST('action', 'aZ09');

$producttmp=new Product($db);
$thirdpartytmp=new Societe($db);

$modBarCodeProduct='';

$maxperinit=1000;


/*
 * Actions
 */

// Define barcode template for products
if (! empty($conf->global->BARCODE_PRODUCT_ADDON_NUM))
{
	$dirbarcodenum=array_merge(array('/core/modules/barcode/'), $conf->modules_parts['barcode']);

	foreach ($dirbarcodenum as $dirroot)
	{
		$dir = dol_buildpath($dirroot, 0);

		$handle = @opendir($dir);
	    if (is_resource($handle))
	    {
	    	while (($file = readdir($handle))!==false)
	    	{
	    		if (preg_match('/^mod_barcode_product_.*php$/', $file))
	    		{
	    			$file = substr($file, 0, dol_strlen($file)-4);

	    		    try {
	        			dol_include_once($dirroot.$file.'.php');
	    			}
	    			catch(Exception $e)
	    			{
	    			    dol_syslog($e->getMessage(), LOG_ERR);
	    			}

	    			$modBarCodeProduct = new $file();
	    			break;
	    		}
	    	}
	    	closedir($handle);
	    }
	}
}

if ($action == 'initbarcodeproducts')
{
	if (! is_object($modBarCodeProduct))
	{
		$error++;
		setEventMessages($langs->trans("NoBarcodeNumberingTemplateDefined"), null, 'errors');
	}

	if (! $error)
	{
		$productstatic=new Product($db);

		$db->begin();

		$nbok=0;
		if (! empty($eraseallbarcode))
		{
			$sql ="UPDATE ".MAIN_DB_PREFIX."product";
			$sql.=" SET barcode = NULL";
			$resql=$db->query($sql);
			if ($resql)
			{
				setEventMessages($langs->trans("AllBarcodeReset"), null, 'mesgs');
			}
			else
			{
				$error++;
				dol_print_error($db);
			}
		}
		else
		{
			$sql ="SELECT rowid, ref, fk_product_type";
			$sql.=" FROM ".MAIN_DB_PREFIX."product";
			$sql.=" WHERE barcode IS NULL or barcode = ''";
			$sql.=$db->order("datec", "ASC");
			$sql.=$db->plimit($maxperinit);

			dol_syslog("codeinit", LOG_DEBUG);
			$resql=$db->query($sql);
			if ($resql)
			{
				$num=$db->num_rows($resql);

				$i=0; $nbok=$nbtry=0;
				while ($i < min($num, $maxperinit))
				{
					$obj=$db->fetch_object($resql);
					if ($obj)
					{
						$productstatic->id=$obj->rowid;
						$productstatic->ref=$obj->ref;
						$productstatic->type=$obj->fk_product_type;
						$nextvalue=$modBarCodeProduct->getNextValue($productstatic, '');

						//print 'Set value '.$nextvalue.' to product '.$productstatic->id." ".$productstatic->ref." ".$productstatic->type."<br>\n";
						$result=$productstatic->setValueFrom('barcode', $nextvalue, '', '', 'text', '', $user, 'PRODUCT_MODIFY');

						$nbtry++;
						if ($result > 0) $nbok++;
					}

					$i++;
				}
			}
			else
			{
				$error++;
				dol_print_error($db);
			}

			if (! $error)
			{
				setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
			}
		}

		if (! $error)
		{
			//$db->rollback();
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
	}

	$action='';
}



/*
 * View
 */

if (!$user->admin) accessforbidden();
if (empty($conf->barcode->enabled)) accessforbidden();

$form=new Form($db);

llxHeader('', $langs->trans("MassBarcodeInit"));

print load_fiche_titre($langs->trans("MassBarcodeInit"), '', 'title_setup.png');
print '<br>';

print $langs->trans("MassBarcodeInitDesc").'<br>';
print '<br>';

//print img_picto('','puce').' '.$langs->trans("PrintsheetForOneBarCode").'<br>';
//print '<br>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="mode" value="label">';
print '<input type="hidden" name="action" value="initbarcodeproducts">';

print '<br>';

// For thirdparty
if ($conf->societe->enabled)
{
	$nbno=$nbtotal=0;

	print load_fiche_titre($langs->trans("BarcodeInitForThirdparties"), '', 'title_companies');

	print '<br>'."\n";
	$sql="SELECT count(rowid) as nb FROM ".MAIN_DB_PREFIX."societe where barcode IS NULL or barcode = ''";
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$nbno=$obj->nb;
	}
	else dol_print_error($db);

	$sql="SELECT count(rowid) as nb FROM ".MAIN_DB_PREFIX."societe";
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$nbtotal=$obj->nb;
	}
	else dol_print_error($db);

	print $langs->trans("CurrentlyNWithoutBarCode", $nbno, $nbtotal, $langs->transnoentitiesnoconv("ThirdParties")).'<br>'."\n";

	print '<br><input class="button" type="submit" id="submitformbarcodethirdpartygen" '.((GETPOST("selectorforbarcode") && GETPOST("selectorforbarcode"))?'':'disabled ').'value="'.$langs->trans("InitEmptyBarCode", $nbno).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("FeatureNotYetAvailable")).'" disabled';
	print '>';
	print '<br><br><br><br>';
}


// For products
if ($conf->product->enabled || $conf->product->service)
{
	// Example 1 : Adding jquery code
	print '<script type="text/javascript" language="javascript">
	function confirm_erase() {
		return confirm("'.dol_escape_js($langs->trans("ConfirmEraseAllCurrentBarCode")).'");
	}
	</script>';

	$nbno=$nbtotal=0;

	print load_fiche_titre($langs->trans("BarcodeInitForProductsOrServices"), '', 'title_products');
	print '<br>'."\n";

	$sql ="SELECT count(rowid) as nb, fk_product_type, datec";
	$sql.=" FROM ".MAIN_DB_PREFIX."product";
	$sql.=" WHERE barcode IS NULL OR barcode = ''";
	$sql.=" GROUP BY fk_product_type, datec";
	$sql.=" ORDER BY datec";
	$resql=$db->query($sql);
	if ($resql)
	{
		$num=$db->num_rows($resql);

		$i=0;
		while($i < $num)
		{
			$obj=$db->fetch_object($resql);
			$nbno+=$obj->nb;

			$i++;
		}
	}
	else dol_print_error($db);

	$sql="SELECT count(rowid) as nb FROM ".MAIN_DB_PREFIX."product";
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$nbtotal=$obj->nb;
	}
	else dol_print_error($db);

	print $langs->trans("CurrentlyNWithoutBarCode", $nbno, $nbtotal, $langs->transnoentitiesnoconv("ProductsOrServices")).'<br>'."\n";

	if (is_object($modBarCodeProduct))
	{
		print $langs->trans("BarCodeNumberManager").": ";
		$objproduct=new Product($db);
		print '<b>'.(isset($modBarCodeProduct->name)?$modBarCodeProduct->name:$modBarCodeProduct->nom).'</b> - '.$langs->trans("NextValue").': <b>'.$modBarCodeProduct->getNextValue($objproduct).'</b><br>';
		$disabled=0;
	}
	else
	{
		$disabled=1;
		$titleno=$langs->trans("NoBarcodeNumberingTemplateDefined");
		print '<font class="warning">'.$langs->trans("NoBarcodeNumberingTemplateDefined").'</font> (<a href="'.DOL_URL_ROOT.'/admin/barcode.php">'.$langs->trans("ToGenerateCodeDefineAutomaticRuleFirst").'</a>)<br>';
	}
	if (empty($nbno))
	{
		$disabled1=1;
	}

	print '<br>';
	//print '<input type="checkbox" id="erasealreadyset" name="erasealreadyset"> '.$langs->trans("ResetBarcodeForAllRecords").'<br>';
	$moretags1=(($disabled||$disabled1)?' disabled title="'.dol_escape_htmltag($titleno).'"':'');
	print '<input class="button" type="submit" name="submitformbarcodeproductgen" id="submitformbarcodeproductgen" value="'.$langs->trans("InitEmptyBarCode", min($maxperinit, $nbno)).'"'.$moretags1.'>';
	$moretags2=(($nbno == $nbtotal)?' disabled':'');
	print ' &nbsp; ';
	print '<input class="button" type="submit" name="eraseallbarcode" id="eraseallbarcode" value="'.$langs->trans("EraseAllCurrentBarCode").'"'.$moretags2.' onClick="return confirm_erase();">';
	print '<br><br><br><br>';
}


print '</form>';
print '<br>';

// End of page
llxFooter();
$db->close();
