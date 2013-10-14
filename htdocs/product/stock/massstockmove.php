<?php
/* Copyright (C) 2013   Laurent Destaileur	<ely@users.sourceforge.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/stock/massstockmove.php
 *  \ingroup    stock
 *  \brief      This page allows to select several products, then incoming warehouse and 
 *  			outgoing warehouse and create all stock movements for this.  
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("orders");

// Security check
if ($user->societe_id) {
    $socid = $user->societe_id;
}
$result=restrictedArea($user,'produit|service');

//checks if a product has been ordered

$action = GETPOST('action','alpha');
$id_product = GETPOST('productid', 'productid');
$id_sw = GETPOST('id_sw', 'id_sw');
$id_tw = GETPOST('id_tw', 'id_tw');
$qty = GETPOST('qty');
$idline = GETPOST('idline');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');

if (!$sortfield) {
    $sortfield = 'p.ref';
}

if (!$sortorder) {
    $sortorder = 'ASC';
}
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$listofdata=array();
if (! empty($_SESSION['massstockmove'])) $listofdata=dol_json_decode($_SESSION['massstockmove'],true);


/*
 * Actions
 */

if ($action == 'addline')
{
	if (! ($id_product > 0)) 
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Product")),'errors');
	}
	if (! $qty)
	{
		$error++;
	    setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Qty")),'errors');
	}
	if (! ($id_sw > 0))
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("WarehouseSource")),'errors');
	}
	if (! ($id_tw > 0))
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("WarehouseTarget")),'errors');
	}
	if ($id_sw > 0 && $id_tw == $id_sw)	
	{
		$error++;
		$langs->load("errors");
		setEventMessage($langs->trans("ErrorWarehouseMustDiffers"),'errors');
	}
	
	if (! $error)
	{
		$id=count($listofdata);
		$listofdata[$id]=array('id'=>$id, 'id_product'=>$id_product, 'qty'=>$qty, 'id_sw'=>$id_sw, 'id_tw'=>$id_tw);
		$_SESSION['massstockmove']=dol_json_encode($listofdata);
	}
}

if ($action == 'delline' && $idline != '')
{
	if (! empty($listofdata[$idline])) unset($listofdata[$idline]);
	var_dump($listofdata);
	var_dump(dol_json_encode($listofdata)); exit;
	if (count($listofdata) > 0) $_SESSION['massstockmove']=dol_json_encode($listofdata);
	else unset($_SESSION['massstockmove']);
}

if ($action == 'createmovement' && isset($_POST['valid']))
{




}



/*
 * View
 */

$form=new Form($db);
$formproduct=new FormProduct($db);
$productstatic = new Product($db);
$warehousestatics = new Entrepot($db);
$warehousestatict = new Entrepot($db);

$title = $langs->trans('MassMovement');

llxHeader('', $title, $helpurl, '');

print_fiche_titre($langs->trans("MassStockMovement")).'<br><br>';

print $langs->trans("SelectProductInAndOutWareHouse").'<br>'; 


// Form to add a line
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="' .$_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="addline">';

print '<table class="liste" width="100%">';

print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('Product'),$_SERVER["PHP_SELF"],'',$param,'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Qty'),$_SERVER["PHP_SELF"],'',$param,'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('WarehouseSource'),$_SERVER["PHP_SELF"],'',$param,'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('WarehouseTarget'),$_SERVER["PHP_SELF"],'',$param,'','align="center"',$sortfield,$sortorder);
print_liste_field_titre('');
print '</tr>';

print '<tr>';
// Product
print '<td>';
$filtertype=0;
if (! empty($conf->global->STOCK_SUPPORTS_SERVICES)) $filtertype='';
print $form->select_produits($id_product,'productid',$filtertype);
print '</td>';
// Qty
print '<td align="center"><input type="input" size="4" class="flat" name="qty" value="'.$qty.'"></td>';
// In warehouse
print '<td align="center">';
print $formproduct->selectWarehouses($id_sw,'id_sw','',1);
print '</td>';
// Out warehouse
print '<td align="center">';
print $formproduct->selectWarehouses($id_tw,'id_tw','',1);
print '</td>';
// Button to add line
print '<td align="right"><input type="submit" class="button" name="addline" value="'.dol_escape_htmltag($langs->trans("Add")).'"></td>';

print '</tr>';
print '</table>';

print '</form>';

print '<br>';

// List movement prepared
print '<table class="liste" width="100%">';

// Lignes des titres
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ProductRef'),$_SERVER["PHP_SELF"],'p.ref',$param,'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('ProductLabel'),$_SERVER["PHP_SELF"],'p.label',$param,'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Qty'),$_SERVER["PHP_SELF"],'',$param,'','align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('WarehouseSource'),$_SERVER["PHP_SELF"],'',$param,'','align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('WarehouseTarget'),$_SERVER["PHP_SELF"],'',$param,'','align="right"',$sortfield,$sortorder);
print_liste_field_titre('');
print '</tr>';

$var=false;
foreach($listofdata as $key => $val)
{
	$var=!$var;
	
	$productstatic->fetch($val['id_product']);
	$warehousestatics->fetch($val['id_sw']);
	$warehousestatict->fetch($val['id_tw']);
	
	print '<tr '.$bc[$var].'>';
	print '<td>'.$productstatic->getNomUrl(1).'</td>';
	print '<td>';
	$oldref=$productstatic->ref;
	$productstatic->ref=$productstatic->label;
	print $productstatic->getNomUrl(1);
	$productstatic->ref=$oldref;
	print '</td>';
	print '<td align="right">'.$val['qty'].'</td>';
	print '<td align="right">';
	print $warehousestatics->getNomUrl(1);
	print '</td>';
	print '<td align="right">';
	print $warehousestatict->getNomUrl(1);
	print '</td>';
	print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=delline&idline='.$val['id'].'">'.img_delete($langs->trans("Remove")).'</a></td>';
	
	print '</tr>';
}

print '</table>';
		
// Generate
$value=$langs->trans("RecordMovement");
print '<div class="center"><input class="button" type="submit" name="valid" value="'.$value.'"></div>';


print '</form>';


llxFooter();

$db->close();
?>