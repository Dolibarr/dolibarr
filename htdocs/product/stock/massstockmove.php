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
		if (count(array_keys($listofdata)) > 0) $id=max(array_keys($listofdata)) + 1;
		else $id=1;
		$listofdata[$id]=array('id'=>$id, 'id_product'=>$id_product, 'qty'=>$qty, 'id_sw'=>$id_sw, 'id_tw'=>$id_tw);
		$_SESSION['massstockmove']=dol_json_encode($listofdata);
		
		unset($id_product);
		//unset($id_sw);
		//unset($id_tw);
		unset($qty);
	}
}

if ($action == 'delline' && $idline != '')
{
	if (! empty($listofdata[$idline])) unset($listofdata[$idline]);
	if (count($listofdata) > 0) $_SESSION['massstockmove']=dol_json_encode($listofdata);
	else unset($_SESSION['massstockmove']);
}

if ($action == 'createmovements')
{
	$error=0;
	
	if (! GETPOST("label"))
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired"),$langs->transnoentitiesnoconv("LabelMovement"));
	}
	
	$db->begin();
	
	if (! $error)
	{
		$product = new Product($db);

		foreach($listofdata as $key => $val)	// Loop on each movement to do
		{	
			$id=$val['id'];
			$id_product=$val['id_product'];
			$id_sw=$val['id_sw'];
			$id_tw=$val['id_tw'];
			$qty=price2num($val['qty']);
		
			if (! $error && $id_sw <> $id_tw && is_numeric($qty) && $id_product)
			{
				$result=$product->fetch($id_product);

				$product->load_stock();	// Load array product->stock_warehouse

				// Define value of products moved
				$pricesrc=0;
				if (isset($product->stock_warehouse[$id_sw]->pmp)) $pricesrc=$product->stock_warehouse[$id_sw]->pmp;
				$pricedest=$pricesrc;

				//print 'price src='.$pricesrc.', price dest='.$pricedest;exit;

				// Remove stock
				$result1=$product->correct_stock(
	    			$user,
	    			$id_sw,
	    			$qty,
	    			1,
	    			GETPOST("label"),
	    			$pricesrc
				);
				if ($result1 < 0)
				{
					$error++;
					setEventMessage($product->errors,'errors');	
				}
				
				// Add stock
				$result2=$product->correct_stock(
	    			$user,
	    			$id_tw,
	    			$qty,
	    			0,
	    			GETPOST("label"),
	    			$pricedest
				);
				if ($result2 < 0)
				{
					$error++;
					setEventMessage($product->errors,'errors');	
				}
			}
			else
			{
				dol_print_error('',"Bad value saved into sessions");
				$error++;	
			}
		}
	}
	
	if (! $error)
	{
		unset($_SESSION['massstockmove']);
		
		$db->commit();
		setEventMessage($langs->trans("StockMovementRecorded"),'mesgs');
		header("Location: ".DOL_URL_ROOT.'/product/stock/index.php');		// Redirect to avoid pb when using back
		exit;
	}
	else
	{
		$db->rollback();
		setEventMessage($langs->trans("Error"),'errors');
	}
}



/*
 * View
 */

$now=dol_now();

$form=new Form($db);
$formproduct=new FormProduct($db);
$productstatic = new Product($db);
$warehousestatics = new Entrepot($db);
$warehousestatict = new Entrepot($db);

$title = $langs->trans('MassMovement');

llxHeader('', $title, $helpurl, '');

print_fiche_titre($langs->trans("MassStockMovement")).'<br><br>';

$titletoadd=$langs->trans("Select");
$titletoaddnoent=$langs->transnoentitiesnoconv("Select");
$buttonrecord=$langs->trans("RecordMovement");
$buttonrecordnoent=$langs->trans("RecordMovement");
print $langs->trans("SelectProductInAndOutWareHouse",$titletoaddnoent,$buttonrecordnoent).'<br>';
print '<br>'."\n"; 

$var=true;

// Form to add a line
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="' .$_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="addline">';

print '<table class="liste" width="100%">';
//print '<div class="tagtable centpercent">';

print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('ProductRef'),0,$_SERVER["PHP_SELF"],'',$param,'','class="tagtd"',$sortfield,$sortorder);
print getTitleFieldOfList($langs->trans('ProductLabel'),0,$_SERVER["PHP_SELF"],'',$param,'','class="tagtd"',$sortfield,$sortorder);
print getTitleFieldOfList($langs->trans('WarehouseSource'),0,$_SERVER["PHP_SELF"],'',$param,'','class="tagtd"',$sortfield,$sortorder);
print getTitleFieldOfList($langs->trans('WarehouseTarget'),0,$_SERVER["PHP_SELF"],'',$param,'','class="tagtd"',$sortfield,$sortorder);
print getTitleFieldOfList($langs->trans('Qty'),0,$_SERVER["PHP_SELF"],'',$param,'','align="center" class="tagtd"',$sortfield,$sortorder);
print getTitleFieldOfList('',0);
print '</tr>';


print '<tr '.$bc[$var].'>';
// Product
print '<td colspan="2">';
$filtertype=0;
if (! empty($conf->global->STOCK_SUPPORTS_SERVICES)) $filtertype='';
print $form->select_produits($id_product,'productid',$filtertype);
print '</td>';
// In warehouse
print '<td>';
print $formproduct->selectWarehouses($id_sw,'id_sw','',1);
print '</td>';
// Out warehouse
print '<td>';
print $formproduct->selectWarehouses($id_tw,'id_tw','',1);
print '</td>';
// Qty
print '<td align="center"><input type="text" size="4" class="flat" name="qty" value="'.$qty.'"></td>';
// Button to add line
print '<td align="right"><input type="submit" class="button" name="addline" value="'.dol_escape_htmltag($titletoadd).'"></td>';

print '</tr>';


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
	print '<td>';
	print $warehousestatics->getNomUrl(1);
	print '</td>';
	print '<td>';
	print $warehousestatict->getNomUrl(1);
	print '</td>';
	print '<td align="center">'.$val['qty'].'</td>';
	print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=delline&idline='.$val['id'].'">'.img_delete($langs->trans("Remove")).'</a></td>';
	
	print '</tr>';
}

print '</table>';

print '</form>';


print '<br>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire2">';
print '<input type="hidden" name="token" value="' .$_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="createmovements">';

// Button to record mass movement
$labelmovement=GETPOST("label")?GETPOST('label'):$langs->trans("MassStockMovement").' '.dol_print_date($now,'%Y-%m-%d %H:%M');

print '<table class="border" width="100%">';
	print '<tr>';
	print '<td width="20%">'.$langs->trans("LabelMovement").'</td>';
	print '<td colspan="5">';
	print '<input type="text" name="label" size="80" value="'.dol_escape_htmltag($labelmovement).'">';
	print '</td>';
	print '</tr>';
print '</table>';	

print '<div class="center"><input class="button" type="submit" name="valid" value="'.dol_escape_htmltag($buttonrecord).'"></div>';

print '</form>';


llxFooter();

$db->close();
?>