<?php
/* Copyright (C) 2015		Maxime Kohlhaas		<maxime@atm-consulting.fr>
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
 *      \file       htdocs/product/info.php
 *      \ingroup    product
 *      \brief      Information page for product
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load("product");
$langs->load("other");
if (! empty($conf->notification->enabled)) $langs->load("mails");

// Security check
$id = GETPOST('id','int');
$ref = GETPOST('ref','alpha');

$result=restrictedArea($user,'produit|service',$id,'product&product');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('infoproduct'));

$object = new Product($db);


/*
 *	Actions
 */

$parameters=array('id'=>$id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 *	View
 */

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label,16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
{
	$title = $langs->trans('Product')." ". $shortlabel ." - ".$langs->trans('Info');
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
{
	$title = $langs->trans('Service')." ". $shortlabel ." - ".$langs->trans('Info');
	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $helpurl);

$form=new Form($b);

if ($id > 0 || $ref)
{
	$result = $object->fetch($id,$ref);
	if (! $result)
	{
		$langs->load("errors");
		print $langs->trans("ErrorRecordNotFound");

		llxFooter();
		$db->close();

		exit;
	}
	
	$head=product_prepare_head($object);
    $titre=$langs->trans("CardProduct".$object->type);
    $picto=($object->type== Product::TYPE_SERVICE?'service':'product');
    dol_fiche_head($head, 'info', $titre, 0, $picto);

	$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref');
	
	$object->info($object->id);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	print '<br>';
	
	dol_print_object_info($object);

	print '</div>';
	
	dol_fiche_end();
}


llxFooter();

$db->close();
