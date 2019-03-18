<?php
/* Copyright (C) 2013-2018 Laurent Destaileur	<ely@users.sourceforge.net>
 * Copyright (C) 2014	   Regis Houssin		<regis.houssin@inodbox.com>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders', 'productbatch'));

// Security check
if ($user->societe_id) {
    $socid = $user->societe_id;
}
$result=restrictedArea($user, 'produit|service');

//checks if a product has been ordered

$action = GETPOST('action', 'alpha');
$id_product = GETPOST('productid', 'int');
$id_sw = GETPOST('id_sw', 'int');
$id_tw = GETPOST('id_tw', 'int');
$batch = GETPOST('batch');
$qty = GETPOST('qty');
$idline = GETPOST('idline');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1

if (!$sortfield) {
    $sortfield = 'p.ref';
}

if (!$sortorder) {
    $sortorder = 'ASC';
}
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$offset = $limit * $page ;

$listofdata=array();
if (! empty($_SESSION['massstockmove'])) $listofdata=json_decode($_SESSION['massstockmove'], true);


/*
 * Actions
 */

if ($action == 'addline')
{
	if (! ($id_product > 0))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
	}
	if (! ($id_sw > 0))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseSource")), null, 'errors');
	}
	if (! ($id_tw > 0))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseTarget")), null, 'errors');
	}
	if ($id_sw > 0 && $id_tw == $id_sw)
	{
		$error++;
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorWarehouseMustDiffers"), null, 'errors');
	}
	if (! $qty)
	{
		$error++;
	    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
	}

	// Check a batch number is provided if product need it
	if (! $error)
	{
		$producttmp=new Product($db);
		$producttmp->fetch($id_product);
		if ($producttmp->hasbatch())
		{
			if (empty($batch))
			{
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorTryToMakeMoveOnProductRequiringBatchData", $producttmp->ref), null, 'errors');
			}
		}
	}

	// TODO Check qty is ok for stock move. Note qty may not be enough yet, but we make a check now to report a warning.
	// What is important is to have qty when doing action 'createmovements'
	if (! $error)
	{
		// Warning, don't forget lines already added into the $_SESSION['massstockmove']
		if ($producttmp->hasbatch())
		{

		}
		else
		{

		}
	}

	if (! $error)
	{
		if (count(array_keys($listofdata)) > 0) $id=max(array_keys($listofdata)) + 1;
		else $id=1;
		$listofdata[$id]=array('id'=>$id, 'id_product'=>$id_product, 'qty'=>$qty, 'id_sw'=>$id_sw, 'id_tw'=>$id_tw, 'batch'=>$batch);
		$_SESSION['massstockmove']=json_encode($listofdata);

		unset($id_product);
		//unset($id_sw);
		//unset($id_tw);
		unset($qty);
	}
}

if ($action == 'delline' && $idline != '')
{
	if (! empty($listofdata[$idline])) unset($listofdata[$idline]);
	if (count($listofdata) > 0) $_SESSION['massstockmove']=json_encode($listofdata);
	else unset($_SESSION['massstockmove']);
}

if ($action == 'createmovements')
{
	$error=0;

	if (! GETPOST("label"))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired"), $langs->transnoentitiesnoconv("MovementLabel"), null, 'errors');
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
			$batch=$val['batch'];
			$dlc=-1;		// They are loaded later from serial
			$dluo=-1;		// They are loaded later from serial

			if (! $error && $id_sw <> $id_tw && is_numeric($qty) && $id_product)
			{
				$result=$product->fetch($id_product);

				$product->load_stock('novirtual');	// Load array product->stock_warehouse

				// Define value of products moved
				$pricesrc=0;
				if (! empty($product->pmp)) $pricesrc=$product->pmp;
				$pricedest=$pricesrc;

				//print 'price src='.$pricesrc.', price dest='.$pricedest;exit;

				if (empty($conf->productbatch->enabled) || ! $product->hasbatch())		// If product does not need lot/serial
				{
					// Remove stock
    $result1=$product->correct_stock(
		    			$user,
		    			$id_sw,
		    			$qty,
		    			1,
		    			GETPOST("label"),
		    			$pricesrc,
						GETPOST("codemove")
					);
					if ($result1 < 0)
					{
						$error++;
						setEventMessages($product->errors, $product->errorss, 'errors');
					}

					// Add stock
    $result2=$product->correct_stock(
		    			$user,
		    			$id_tw,
		    			$qty,
		    			0,
		    			GETPOST("label"),
		    			$pricedest,
						GETPOST("codemove")
					);
					if ($result2 < 0)
					{
						$error++;
						setEventMessages($product->errors, $product->errorss, 'errors');
					}
				}
				else
				{
					$arraybatchinfo=$product->loadBatchInfo($batch);
					if (count($arraybatchinfo) > 0)
					{
						$firstrecord = array_shift($arraybatchinfo);
						$dlc=$firstrecord['eatby'];
						$dluo=$firstrecord['sellby'];
						//var_dump($batch); var_dump($arraybatchinfo); var_dump($firstrecord); var_dump($dlc); var_dump($dluo); exit;
					}
					else
					{
						$dlc='';
						$dluo='';
					}

					// Remove stock
    $result1=$product->correct_stock_batch(
		    			$user,
		    			$id_sw,
		    			$qty,
		    			1,
		    			GETPOST("label"),
		    			$pricesrc,
						$dlc,
						$dluo,
						$batch,
						GETPOST("codemove")
					);
					if ($result1 < 0)
					{
						$error++;
						setEventMessages($product->errors, $product->errorss, 'errors');
					}

					// Add stock
    $result2=$product->correct_stock_batch(
		    			$user,
		    			$id_tw,
		    			$qty,
		    			0,
		    			GETPOST("label"),
		    			$pricedest,
						$dlc,
						$dluo,
						$batch,
						GETPOST("codemove")
					);
					if ($result2 < 0)
					{
						$error++;
						setEventMessages($product->errors, $product->errorss, 'errors');
					}
				}
			}
			else
			{
				// dol_print_error('',"Bad value saved into sessions");
				$error++;
			}
		}
	}

	if (! $error)
	{
		unset($_SESSION['massstockmove']);

		$db->commit();
		setEventMessages($langs->trans("StockMovementRecorded"), null, 'mesgs');
		header("Location: ".DOL_URL_ROOT.'/product/stock/index.php');		// Redirect to avoid pb when using back
		exit;
	}
	else
	{
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
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

llxHeader('', $title);

print load_fiche_titre($langs->trans("MassStockTransferShort"));

$titletoadd=$langs->trans("Select");
$buttonrecord=$langs->trans("RecordMovement");
$titletoaddnoent=$langs->transnoentitiesnoconv("Select");
$buttonrecordnoent=$langs->transnoentitiesnoconv("RecordMovement");
print '<span class="opacitymedium">'.$langs->trans("SelectProductInAndOutWareHouse", $titletoaddnoent, $buttonrecordnoent).'</span><br>';
print '<br>'."\n";

// Form to add a line
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="' .$_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="addline">';


print '<div class="div-table-responsive-no-min">';
print '<table class="liste" width="100%">';
//print '<div class="tagtable centpercent">';

$param='';

print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('ProductRef'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
if ($conf->productbatch->enabled) {
	print getTitleFieldOfList($langs->trans('Batch'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
}
print getTitleFieldOfList($langs->trans('WarehouseSource'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('WarehouseTarget'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Qty'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'center tagtd maxwidthonsmartphone ');
print getTitleFieldOfList('', 0);
print '</tr>';


print '<tr class="oddeven">';
// Product
print '<td class="titlefield">';
$filtertype=0;
if (! empty($conf->global->STOCK_SUPPORTS_SERVICES)) $filtertype='';
if ($conf->global->PRODUIT_LIMIT_SIZE <= 0) {
	$limit='';
}
else
{
	$limit = $conf->global->PRODUIT_LIMIT_SIZE;
}

print $form->select_produits($id_product, 'productid', $filtertype, $limit, 0, -1, 2, '', 0, array(), 0, '1', 0, 'minwidth200imp maxwidth300', 1);
print '</td>';
// Batch number
if ($conf->productbatch->enabled)
{
	print '<td>';
	print '<input type="text" name="batch" class="flat maxwidth50" value="'.$batch.'">';
	print '</td>';
}
// In warehouse
print '<td>';
print $formproduct->selectWarehouses($id_sw, 'id_sw', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200');
print '</td>';
// Out warehouse
print '<td>';
print $formproduct->selectWarehouses($id_tw, 'id_tw', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200');
print '</td>';
// Qty
print '<td class="center"><input type="text" class="flat maxwidth50" name="qty" value="'.$qty.'"></td>';
// Button to add line
print '<td class="right"><input type="submit" class="button" name="addline" value="'.dol_escape_htmltag($titletoadd).'"></td>';

print '</tr>';


foreach($listofdata as $key => $val)
{
	$productstatic->fetch($val['id_product']);
	$warehousestatics->fetch($val['id_sw']);
	$warehousestatict->fetch($val['id_tw']);

	print '<tr class="oddeven">';
	print '<td>';
	print $productstatic->getNomUrl(1).' - '.$productstatic->label;
	print '</td>';
	if ($conf->productbatch->enabled)
	{
		print '<td>';
		print $val['batch'];
		print '</td>';
	}
	print '<td>';
	print $warehousestatics->getNomUrl(1);
	print '</td>';
	print '<td>';
	print $warehousestatict->getNomUrl(1);
	print '</td>';
	print '<td class="center">'.$val['qty'].'</td>';
	print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=delline&idline='.$val['id'].'">'.img_delete($langs->trans("Remove")).'</a></td>';

	print '</tr>';
}

print '</table>';
print '</div>';

print '</form>';


print '<br>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire2">';
print '<input type="hidden" name="token" value="' .$_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="createmovements">';

// Button to record mass movement
$codemove=(isset($_POST["codemove"])?GETPOST("codemove", 'alpha'):dol_print_date(dol_now(), '%Y%m%d%H%M%S'));
$labelmovement=GETPOST("label")?GETPOST('label'):$langs->trans("StockTransfer").' '.dol_print_date($now, '%Y-%m-%d %H:%M');

print '<table class="noborder" width="100%">';
	print '<tr>';
	print '<td class="titlefield fieldrequired">'.$langs->trans("InventoryCode").'</td>';
	print '<td>';
	print '<input type="text" name="codemove" size="15" value="'.dol_escape_htmltag($codemove).'">';
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>'.$langs->trans("MovementLabel").'</td>';
	print '<td>';
	print '<input type="text" name="label" class="quatrevingtpercent" value="'.dol_escape_htmltag($labelmovement).'">';
	print '</td>';
	print '</tr>';
print '</table><br>';

print '<div class="center"><input class="button" type="submit" name="valid" value="'.dol_escape_htmltag($buttonrecord).'"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
