<?php
/* Copyright (C) 2012	Regis Houssin	<regis@dolibarr.fr>
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
 *  \file       htdocs/product/admin/product_tools.php
 *  \ingroup    product
 *  \brief      Setup page of product module
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

$langs->load("admin");
$langs->load("products");

// Security check
if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$oldvatrate=GETPOST('oldvatrate');
$newvatrate=GETPOST('newvatrate');
$price_base_type=GETPOST('price_base_type');

/*
 * Actions
 */

if ($action == 'convert')
{
	$error=0;

	$db->begin();

	$sql = 'SELECT rowid';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'product';
	$sql.= ' WHERE entity IN ('.getEntity('product',1).')';
	$sql.= ' AND tva_tx = "'.$oldvatrate.'"';

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		if ($num)
		{
			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$object = new Product($db);

				$ret=$object->fetch($obj->rowid);
				if ($ret)
				{
					if ($price_base_type == 'TTC')
					{
						$newprice=price2num($object->price_ttc,'MU');    // Second param must be MU (we want a unit price so 'MT'. If unit price was on 4 decimal, we must keep 4 decimals)
					}
					else
					{
						$newprice=price2num($object->price,'MU');    // Second param must be MU (we want a unit price so 'MT'. If unit price was on 4 decimal, we must keep 4 decimals)
					}

					$newvat=str_replace('*','',$newvatrate);

					$ret=$object->updatePrice($object->id, $newprice, $price_base_type, $user, $newvat);
					if ($ret < 0) $error++;
				}

				$i++;
			}

			if (! $error)
			{
				$db->commit();
			}
			else
			{
				$db->rollback();
			}
		}
	}
}

/*
 * View
 */

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

$head = product_admin_prepare_head();
dol_fiche_head($head, 'tools', $tab, 0, 'product');

$form=new Form($db);
$var=true;

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="convert" />';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("OldVATRates").'</td>'."\n";
print '<td width="60" align="right">'."\n";
print $form->load_tva('oldvatrate', $oldvatrate);
print '</td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("NewVATRates").'</td>'."\n";
print '<td width="60" align="right">'."\n";
print $form->load_tva('newvatrate', $newvatrate);
print '</td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("PriceBaseType").'</td>'."\n";
print '<td width="60" align="right">'."\n";
print $form->load_PriceBaseType($price_base_type);
print '</td>'."\n";
print '</tr>'."\n";

print '</table>';
print '</div>';

// Boutons actions
print '<div class="tabsAction">';
print '<input type="submit" id="convert_vatrate" name="convert_vatrate" value="'.$langs->trans("Convert").'" />';
print '</div>';

print '</form>';

dol_htmloutput_mesg($mesg);

llxFooter();

$db->close();

?>
