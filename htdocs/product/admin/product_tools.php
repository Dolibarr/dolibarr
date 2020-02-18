<?php
/* Copyright (C) 2012	   Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2015 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/admin/product_tools.php
 *  \ingroup    product
 *  \brief      Setup page of product module
 */

// TODO We must add a confirmation on button because this will make a mass change
// FIXME Should also change table product_price for price levels

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'products'));

// Security check
if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$oldvatrate=GETPOST('oldvatrate', 'alpha');
$newvatrate=GETPOST('newvatrate', 'alpha');
//$price_base_type=GETPOST('price_base_type');



/*
 * Actions
 */

if ($action == 'convert')
{
	$error=0;

	if ($oldvatrate == $newvatrate)
	{
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorNewValueCantMatchOldValue"), null, 'errors');
		$error++;
	}

	if (! $error)
	{
		$country_id=$mysoc->country_id;	// TODO Allow to choose country into form

		$nbrecordsmodified=0;

		$db->begin();

		// Clean vat code old
		$vat_src_code_old='';
		if (preg_match('/\((.*)\)/', $oldvatrate, $reg))
		{
			$vat_src_code_old = $reg[1];
			$oldvatrateclean = preg_replace('/\s*\(.*\)/', '', $oldvatrate);    // Remove code into vatrate.
		} else $oldvatrateclean=$oldvatrate;

		// Clean vat code new
		$vat_src_code_new='';
		if (preg_match('/\((.*)\)/', $newvatrate, $reg))
		{
			$vat_src_code_new = $reg[1];
			$newvatrateclean = preg_replace('/\s*\(.*\)/', '', $newvatrate);    // Remove code into vatrate.
		} else $newvatrateclean=$newvatrate;

		// If country to edit is my country, so we change customer prices
		if ($country_id == $mysoc->country_id)
		{
			$sql = 'SELECT rowid';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product';
			$sql.= ' WHERE entity IN ('.getEntity('product').')';
			$sql.= " AND tva_tx = '".$db->escape($oldvatrateclean)."'";
			if ($vat_src_code_old) $sql.= " AND default_vat_code = '".$vat_src_code_old."'";
			else " AND default_vat_code = IS NULL";

			$resql=$db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);

				$i = 0;
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);

					$objectstatic = new Product($db);          // Object init must be into loop to avoid to get value of previous step
					$ret=$objectstatic->fetch($obj->rowid);
					if ($ret > 0)
					{
						$ret=0; $retm=0; $updatelevel1=false;

						// Update multiprice
						$listofmulti=array_reverse($objectstatic->multiprices, true);	// To finish with level 1
						foreach ($listofmulti as $level => $multiprices)
						{
							$price_base_type = $objectstatic->multiprices_base_type[$level];	// Get price_base_type of product/service to keep the same for update
							if (empty($price_base_type)) continue;	// Discard not defined price levels

							if ($price_base_type == 'TTC')
							{
								$newprice=price2num($objectstatic->multiprices_ttc[$level], 'MU');    // Second param must be MU (we want a unit price so 'MU'. If unit price was on 4 decimal, we must keep 4 decimals)
								$newminprice=$objectstatic->multiprices_min_ttc[$level];
							}
							else
							{
								$newprice=price2num($objectstatic->multiprices[$level], 'MU');    // Second param must be MU (we want a unit price so 'MU'. If unit price was on 4 decimal, we must keep 4 decimals)
								$newminprice=$objectstatic->multiprices_min[$level];
							}
							if ($newminprice > $newprice) $newminprice=$newprice;

							$newvat=str_replace('*', '', $newvatrate);
							$localtaxes_type=getLocalTaxesFromRate($newvat, 0, $mysoc, $mysoc);
							$newnpr=$objectstatic->multiprices_recuperableonly[$level];
							$newdefaultvatcode=$vat_src_code_new;
							$newlevel=$level;

							//print "$objectstatic->id $newprice, $price_base_type, $newvat, $newminprice, $newlevel, $newnpr<br>\n";
							$retm=$objectstatic->updatePrice($newprice, $price_base_type, $user, $newvatrateclean, $newminprice, $newlevel, $newnpr, 0, 0, $localtaxes_type, $newdefaultvatcode);
							if ($retm < 0)
							{
								$error++;
								break;
							}

							if ($newlevel == 1) $updatelevel1=true;
						}

						// Update single price
						$price_base_type = $objectstatic->price_base_type;	// Get price_base_type of product/service to keep the same for update
						if ($price_base_type == 'TTC')
						{
							$newprice=price2num($objectstatic->price_ttc, 'MU');    // Second param must be MU (we want a unit price so 'MU'. If unit price was on 4 decimal, we must keep 4 decimals)
							$newminprice=$objectstatic->price_min_ttc;
						}
						else
						{
							$newprice=price2num($objectstatic->price, 'MU');    // Second param must be MU (we want a unit price so 'MU'. If unit price was on 4 decimal, we must keep 4 decimals)
							$newminprice=$objectstatic->price_min;
						}
						if ($newminprice > $newprice) $newminprice=$newprice;
						$newvat=str_replace('*', '', $newvatrate);
						$localtaxes_type=getLocalTaxesFromRate($newvat, 0, $mysoc, $mysoc);
						$newnpr=$objectstatic->recuperableonly;
						$newdefaultvatcode=$vat_src_code_new;
						$newlevel=0;
						if (! empty($price_base_type) && ! $updatelevel1)
						{
							//print "$objectstatic->id $newprice, $price_base_type, $newvat, $newminprice, $newlevel, $newnpr<br>\n";
							$ret=$objectstatic->updatePrice($newprice, $price_base_type, $user, $newvatrateclean, $newminprice, $newlevel, $newnpr, 0, 0, $localtaxes_type, $newdefaultvatcode);
						}

						if ($ret < 0 || $retm < 0) $error++;
						else $nbrecordsmodified++;
					}
                    unset($objectstatic);

					$i++;
				}
			}
			else dol_print_error($db);
		}

		$fourn = new Fournisseur($db);

		// Change supplier prices
		$sql = 'SELECT pfp.rowid, pfp.fk_soc, pfp.price as price, pfp.quantity as qty, pfp.fk_availability, pfp.ref_fourn';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'product_fournisseur_price as pfp, '.MAIN_DB_PREFIX.'societe as s';
		$sql.= ' WHERE pfp.fk_soc = s.rowid AND pfp.entity IN ('.getEntity('product').')';
		$sql.= " AND tva_tx = '".$db->escape($oldvatrate)."'";
		if ($vat_src_code_old) $sql.= " AND default_vat_code = '".$vat_src_code_old."'";
		else " AND default_vat_code = IS NULL";
		$sql.= " AND s.fk_pays = '".$country_id."'";
		//print $sql;
		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);

			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

                $objectstatic2 = new ProductFournisseur($db);          // Object init must be into loop to avoid to get value of previous step
				$ret=$objectstatic2->fetch_product_fournisseur_price($obj->rowid);
				if ($ret > 0)
				{
					$ret=0; $retm=0; $updatelevel1=false;

					$price_base_type='HT';
					//$price_base_type = $objectstatic2->price_base_type;	// Get price_base_type of product/service to keep the same for update
					//if ($price_base_type == 'TTC')
					//{
					//	$newprice=price2num($objectstatic2->price_ttc,'MU');    // Second param must be MU (we want a unit price so 'MU'. If unit price was on 4 decimal, we must keep 4 decimals)
					//	$newminprice=$objectstatic2->price_min_ttc;
					//}
					//else
					//{
						$newprice=price2num($obj->price, 'MU');    // Second param must be MU (we want a unit price so 'MU'. If unit price was on 4 decimal, we must keep 4 decimals)
						//$newminprice=$objectstatic2->fourn_price_min;
					//}
					//if ($newminprice > $newprice) $newminprice=$newprice;
					$newvat=str_replace('*', '', $newvatrate);
					$localtaxes_type=getLocalTaxesFromRate($newvat, 0, $mysoc, $mysoc);
					//$newnpr=$objectstatic2->recuperableonly;
					$newnpr=0;
					$newdefaultvatcode=$vat_src_code_new;

					$newpercent = $objectstatic2->fourn_remise_percent;
					$newdeliverydelay = $objectstatic2->delivery_time_days;
					$newsupplierreputation = $objectstatic2->supplier_reputation;

					$newlevel=0;
					if (! empty($price_base_type) && ! $updatelevel1)
					{
						//print "$objectstatic2->id $newprice, $price_base_type, $newvat, $newminprice, $newlevel, $newnpr<br>\n";
						$fourn->id=$obj->fk_soc;
						$ret=$objectstatic2->update_buyprice($obj->qty, $newprice, $user, $price_base_type, $fourn, $obj->fk_availability, $obj->ref_fourn, $newvat, '', $newpercent, 0, $newnpr, $newdeliverydelay, $newsupplierreputation, $localtaxes_type, $newdefaultvatcode);
					}

					if ($ret < 0 || $retm < 0) $error++;
					else $nbrecordsmodified++;
				}
				unset($objectstatic2);

				$i++;
			}
		}
		else dol_print_error($db);

		if (! $error)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();
		}

		// Output result
		if (! $error)
		{
			if ($nbrecordsmodified > 0) setEventMessages($langs->trans("RecordsModified", $nbrecordsmodified), null, 'mesgs');
			else setEventMessages($langs->trans("NoRecordFound"), null, 'warnings');
		}
		else
		{
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
}

/*
 * View
 */

$form=new Form($db);

$title = $langs->trans('ProductVatMassChange');

llxHeader('', $title);

print load_fiche_titre($title, '', 'title_setup');

print $langs->trans("ProductVatMassChangeDesc").'<br><br>';

if (empty($mysoc->country_code))
{
	$langs->load("errors");
	$warnpicto=img_error($langs->trans("WarningMandatorySetupNotComplete"));
	print '<br><a href="'.DOL_URL_ROOT.'/admin/company.php?mainmenu=home">'.$warnpicto.' '.$langs->trans("WarningMandatorySetupNotComplete").'</a>';
}
else
{
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.newToken().'" />';
	print '<input type="hidden" name="action" value="convert" />';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").'</td>'."\n";
	print '<td class="right" width="60">'.$langs->trans("Value").'</td>'."\n";
	print '</tr>'."\n";


	print '<tr class="oddeven">'."\n";
	print '<td>'.$langs->trans("OldVATRates").'</td>'."\n";
	print '<td width="60" class="right">'."\n";
	print $form->load_tva('oldvatrate', $oldvatrate, $mysoc, null, 0, 0, '', false, 1);
	print '</td>'."\n";
	print '</tr>'."\n";


	print '<tr class="oddeven">'."\n";
	print '<td>'.$langs->trans("NewVATRates").'</td>'."\n";
	print '<td width="60" class="right">'."\n";
	print $form->load_tva('newvatrate', $newvatrate, $mysoc, null, 0, 0, '', false, 1);
	print '</td>'."\n";
	print '</tr>'."\n";

	/*

	print '<tr class="oddeven">'."\n";
	print '<td>'.$langs->trans("PriceBaseTypeToChange").'</td>'."\n";
	print '<td width="60" class="right">'."\n";
	print $form->selectPriceBaseType($price_base_type);
	print '</td>'."\n";
	print '</tr>'."\n";
	*/

	print '</table>';

	print '<br>';

	// Buttons for actions

	print '<div class="center">';
	print '<input type="submit" id="convert_vatrate" name="convert_vatrate" value="'.$langs->trans("MassConvert").'" class="button" />';
	print '</div>';

	print '</form>';
}

// End of page
llxFooter();
$db->close();
