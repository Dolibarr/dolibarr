<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/product/barcode.php
 *       \ingroup    product
 *       \brief      Page with bar code informations of product
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/barcode/html.formbarcode.class.php");

$langs->load("products");
$langs->load("bills");

// Security check
if (isset($_GET["id"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["id"])?$_GET["id"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service&barcode',$id,'product','','',$fieldid);


/*
 * Actions
 */

// Modification du type de code barre
if ($_POST['action'] ==	'setbarcodetype'	&& $user->rights->barcode->creer)
{
	$product =	new	Product($db);
	$product->fetch($_GET["id"]);
	$product->barcode_type = $_POST['barcodetype_id'];
	$result = $product->update_barcode_type($user);
	Header("Location: barcode.php?id=".$_GET["id"]);
	exit;
}

// Modification du code barre
if ($_POST['action'] ==	'setbarcode'	&& $user->rights->barcode->creer)
{
	$product =	new	Product($db);
	$product->fetch($_GET["id"]);
	$product->barcode = $_POST['barcode']; //Todo: ajout verification de la validite du code barre en fonction du type
	$result = $product->update_barcode($user);
	Header("Location: barcode.php?id=".$_GET["id"]);
	exit;
}


/*
 *   View
 */

llxHeader("","",$langs->trans("BarCode"));

$form = new Form($db);
$formbarcode = new FormBarCode($db);

$product = new Product($db);
if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

$head=product_prepare_head($product, $user);
$titre=$langs->trans("CardProduct".$product->type);
$picto=($product->type==1?'service':'product');
dol_fiche_head($head, 'barcode', $titre, 0, $picto);


print '<table class="border" width="100%">'."\n";

// Reference
print '<tr>';
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="3">';
print $form->showrefnav($product,'ref','',1,'ref');
print '</td>';
print '</tr>'."\n";

// Libelle
print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td>';

// Barcode image
$url=DOL_URL_ROOT.'/viewimage.php?modulepart=barcode&generator='.urlencode($product->barcode_type_coder).'&code='.urlencode($product->barcode).'&encoding='.urlencode($product->barcode_type_code);
print '<td width="300" align="center" rowspan="5">';
print '<!-- url barcode = '.$url.' -->';
print '<img src="'.$url.'">';
print '</td>';

print '</tr>'."\n";

// Status (to sell)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')'.'</td><td>';
print $product->getLibStatut(2,0);
print '</td></tr>';

// Status (to buy)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')'.'</td><td>';
print $product->getLibStatut(2,1);
print '</td></tr>';

// Barcode type
print '<tr><td nowrap>';
print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
print $langs->trans("BarcodeType");
print '<td>';
if (($_GET['action'] != 'editbarcodetype') && $user->rights->barcode->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbarcodetype&amp;id='.$product->id.'">'.img_edit($langs->trans('SetBarcodeType'),1).'</a></td>';
print '</tr></table>';
print '</td><td colspan="2">';
if ($_GET['action'] == 'editbarcodetype')
{
	$formbarcode->form_barcode_type($_SERVER['PHP_SELF'].'?id='.$product->id,$product->barcode_type,'barcodetype_id');
}
else
{
	print $product->barcode_type_label?$product->barcode_type_label:'<div class="warning">'.$langs->trans("SetDefaultBarcodeType").'<div>';
}
print '</td></tr>'."\n";

// Barcode value
print '<tr><td nowrap>';
print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
print $langs->trans("BarcodeValue");
print '<td>';
if (($_GET['action'] != 'editbarcode') && $user->rights->barcode->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbarcode&amp;id='.$product->id.'">'.img_edit($langs->trans('SetBarcode'),1).'</a></td>';
print '</tr></table>';
print '</td><td colspan="2">';
if ($_GET['action'] == 'editbarcode')
{
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="setbarcode">';
	print '<input size="40" type="text" name="barcode" value="'.$product->barcode.'">';
	print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
}
else
{
	print $product->barcode;
}
print '</td></tr>'."\n";

print "</table>\n";
print "</div>\n";

$db->close();

llxFooter();
?>
