<?php
/* Copyright (C) 2013-2021 Laurent Destaileur	<ely@users.sourceforge.net>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
require_once DOL_DOCUMENT_ROOT.'/core/modules/import/import_csv.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/import.lib.php';

$confirm = GETPOST('confirm', 'alpha');
$filetoimport = GETPOST('filetoimport');

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders', 'productbatch'));

//init Hook
$hookmanager->initHooks(array('massstockmove'));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service');

//checks if a product has been ordered

$action = GETPOST('action', 'aZ09');
$id_product = GETPOST('productid', 'int');
$id_sw = GETPOST('id_sw', 'int');
$id_tw = GETPOST('id_tw', 'int');
$batch = GETPOST('batch');
$qty = GETPOST('qty');
$idline = GETPOST('idline');

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1

if (!$sortfield) {
	$sortfield = 'p.ref';
}

if (!$sortorder) {
	$sortorder = 'ASC';
}
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;

$listofdata = array();
if (!empty($_SESSION['massstockmove'])) {
	$listofdata = json_decode($_SESSION['massstockmove'], true);
}


/*
 * Actions
 */

if ($action == 'addline' && !empty($user->rights->stock->mouvement->creer)) {
	if (!($id_product > 0)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
	}
	if (!($id_sw > 0)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseSource")), null, 'errors');
	}
	if (!($id_tw > 0)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseTarget")), null, 'errors');
	}
	if ($id_sw > 0 && $id_tw == $id_sw) {
		$error++;
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorWarehouseMustDiffers"), null, 'errors');
	}
	if (!$qty) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
	}

	// Check a batch number is provided if product need it
	if (!$error) {
		$producttmp = new Product($db);
		$producttmp->fetch($id_product);
		if ($producttmp->hasbatch()) {
			if (empty($batch)) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorTryToMakeMoveOnProductRequiringBatchData", $producttmp->ref), null, 'errors');
			}
		}
	}

	// TODO Check qty is ok for stock move. Note qty may not be enough yet, but we make a check now to report a warning.
	// What is more important is to have qty when doing action 'createmovements'
	if (!$error) {
		// Warning, don't forget lines already added into the $_SESSION['massstockmove']
		if ($producttmp->hasbatch()) {
		} else {
		}
	}

	if (!$error) {
		if (count(array_keys($listofdata)) > 0) {
			$id = max(array_keys($listofdata)) + 1;
		} else {
			$id = 1;
		}
		$listofdata[$id] = array('id'=>$id, 'id_product'=>$id_product, 'qty'=>$qty, 'id_sw'=>$id_sw, 'id_tw'=>$id_tw, 'batch'=>$batch);
		$_SESSION['massstockmove'] = json_encode($listofdata);

		//unset($id_sw);
		//unset($id_tw);
		unset($id_product);
		unset($batch);
		unset($qty);
	}
}

if ($action == 'delline' && $idline != '' && !empty($user->rights->stock->mouvement->creer)) {
	if (!empty($listofdata[$idline])) {
		unset($listofdata[$idline]);
	}
	if (count($listofdata) > 0) {
		$_SESSION['massstockmove'] = json_encode($listofdata);
	} else {
		unset($_SESSION['massstockmove']);
	}
}

if ($action == 'createmovements' && !empty($user->rights->stock->mouvement->creer)) {
	$error = 0;

	if (!GETPOST("label")) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired"), $langs->transnoentitiesnoconv("MovementLabel"), null, 'errors');
	}

	$db->begin();

	if (!$error) {
		$product = new Product($db);

		foreach ($listofdata as $key => $val) {	// Loop on each movement to do
			$id = $val['id'];
			$id_product = $val['id_product'];
			$id_sw = $val['id_sw'];
			$id_tw = $val['id_tw'];
			$qty = price2num($val['qty']);
			$batch = $val['batch'];
			$dlc = -1; // They are loaded later from serial
			$dluo = -1; // They are loaded later from serial

			if (!$error && $id_sw <> $id_tw && is_numeric($qty) && $id_product) {
				$result = $product->fetch($id_product);

				$product->load_stock('novirtual'); // Load array product->stock_warehouse

				// Define value of products moved
				$pricesrc = 0;
				if (!empty($product->pmp)) {
					$pricesrc = $product->pmp;
				}
				$pricedest = $pricesrc;

				//print 'price src='.$pricesrc.', price dest='.$pricedest;exit;

				if (empty($conf->productbatch->enabled) || !$product->hasbatch()) {		// If product does not need lot/serial
					// Remove stock
					$result1 = $product->correct_stock(
						$user,
						$id_sw,
						$qty,
						1,
						GETPOST("label"),
						$pricesrc,
						GETPOST("codemove")
					);
					if ($result1 < 0) {
						$error++;
						setEventMessages($product->error, $product->errors, 'errors');
					}

					// Add stock
					$result2 = $product->correct_stock(
						$user,
						$id_tw,
						$qty,
						0,
						GETPOST("label"),
						$pricedest,
						GETPOST("codemove")
					);
					if ($result2 < 0) {
						$error++;
						setEventMessages($product->error, $product->errors, 'errors');
					}
				} else {
					$arraybatchinfo = $product->loadBatchInfo($batch);
					if (count($arraybatchinfo) > 0) {
						$firstrecord = array_shift($arraybatchinfo);
						$dlc = $firstrecord['eatby'];
						$dluo = $firstrecord['sellby'];
						//var_dump($batch); var_dump($arraybatchinfo); var_dump($firstrecord); var_dump($dlc); var_dump($dluo); exit;
					} else {
						$dlc = '';
						$dluo = '';
					}

					// Remove stock
					$result1 = $product->correct_stock_batch(
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
					if ($result1 < 0) {
						$error++;
						setEventMessages($product->error, $product->errors, 'errors');
					}

					// Add stock
					$result2 = $product->correct_stock_batch(
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
					if ($result2 < 0) {
						$error++;
						setEventMessages($product->error, $product->errors, 'errors');
					}
				}
			} else {
				// dol_print_error('',"Bad value saved into sessions");
				$error++;
			}
		}
	}

	if (!$error) {
		unset($_SESSION['massstockmove']);

		$db->commit();
		setEventMessages($langs->trans("StockMovementRecorded"), null, 'mesgs');
		header("Location: ".DOL_URL_ROOT.'/product/stock/index.php'); // Redirect to avoid pb when using back
		exit;
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'importCSV' && !empty($user->rights->stock->mouvement->creer)) {
	dol_mkdir($conf->stock->dir_temp);
	$nowyearmonth = dol_print_date(dol_now(), '%Y%m%d%H%M%S');

	$fullpath = $conf->stock->dir_temp."/".$user->id.'-csvfiletotimport.csv';
	if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $fullpath, 1) > 0) {
		dol_syslog("File ".$fullpath." was added for import");
	} else {
		$error++;
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
	}

	if (!$error) {
		$importcsv = new ImportCsv($db, 'massstocklist');
		//print $importcsv->separator;

		$nblinesrecord = $importcsv->import_get_nb_of_lines($fullpath)-1;
		$importcsv->import_open_file($fullpath);
		$labelsrecord = $importcsv->import_read_record();

		if ($nblinesrecord < 1) {
			setEventMessages($langs->trans("BadNumberOfLinesMustHaveAtLeastOneLinePlusTitle"), null, 'errors');
		} else {
			$i=0;
			$data = array();
			$productstatic = new Product($db);
			$warehousestatics = new Entrepot($db);
			$warehousestatict = new Entrepot($db);
			while (($i < $nblinesrecord) && !$error) {
				$data[] = $importcsv->import_read_record();
				if (count($data[$i]) == 1) {
					// Only 1 empty line
					unset($data[$i]);
					$i++;
					continue;
				}
				//var_dump($data);
				$tmp_id_sw = $data[$i][0]['val'];
				$tmp_id_tw = $data[$i][1]['val'];
				$tmp_id_product = $data[$i][2]['val'];
				$tmp_qty = $data[$i][3]['val'];
				$tmp_batch = $data[$i][4]['val'];

				if (!is_numeric($tmp_id_product)) {
					$result = fetchref($productstatic, $tmp_id_product);
					$tmp_id_product = $result;
					$data[$i][2]['val'] = $result;
				}
				if (!($tmp_id_product > 0)) {
					$error++;
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
				}

				if (!is_numeric($tmp_id_sw)) {
					$result = fetchref($warehousestatics, $tmp_id_sw);
					$tmp_id_sw = $result;
					$data[$i][0]['val'] = $result;
				}
				if (!($tmp_id_sw > 0)) {
					$error++;
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseSource")), null, 'errors');
				}

				if (!is_numeric($tmp_id_tw)) {
					$result = fetchref($warehousestatict, $tmp_id_tw);
					$tmp_id_tw = $result;
					$data[$i][1]['val'] = $result;
				}
				if (!($tmp_id_tw > 0)) {
					$error++;
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseTarget")), null, 'errors');
				}

				if ($tmp_id_sw > 0 && $tmp_id_tw == $tmp_id_sw) {
					$error++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorWarehouseMustDiffers"), null, 'errors');
				}
				if (!$tmp_qty) {
					$error++;
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
				}

				// Check a batch number is provided if product need it
				if (!$error) {
					$producttmp = new Product($db);
					$producttmp->fetch($tmp_id_product);
					if ($producttmp->hasbatch()) {
						if (empty($tmp_batch)) {
							$error++;
							$langs->load("errors");
							setEventMessages($langs->trans("ErrorTryToMakeMoveOnProductRequiringBatchData", $producttmp->ref), null, 'errors');
						}
					}
				}

				$i++;
			}

			if (!$error) {
				foreach ($data as $key => $value) {
					if (count(array_keys($listofdata)) > 0) {
						$id = max(array_keys($listofdata)) + 1;
					} else {
						$id = 1;
					}
					$tmp_id_sw = $data[$key][0]['val'];
					$tmp_id_tw = $data[$key][1]['val'];
					$tmp_id_product = $data[$key][2]['val'];
					$tmp_qty = $data[$key][3]['val'];
					$tmp_batch = $data[$key][4]['val'];
					$listofdata[$key] = array('id'=>$key, 'id_sw'=>$tmp_id_sw, 'id_tw'=>$tmp_id_tw, 'id_product'=>$tmp_id_product, 'qty'=>$tmp_qty, 'batch'=>$tmp_batch);
				}
			}
		}
	}

	$_SESSION['massstockmove'] = json_encode($listofdata);
}

if ($action == 'confirm_deletefile' && $confirm == 'yes') {
	$langs->load("other");

	$param = '&datatoimport='.urlencode($datatoimport).'&format='.urlencode($format);
	if ($excludefirstline) {
		$param .= '&excludefirstline='.urlencode($excludefirstline);
	}
	if ($endatlinenb) {
		$param .= '&endatlinenb='.urlencode($endatlinenb);
	}

	$file = $conf->stock->dir_temp.'/'.GETPOST('urlfile'); // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	$ret = dol_delete_file($file);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	}
	Header('Location: '.$_SERVER["PHP_SELF"]);
	exit;
}


/*
 * View
 */

$now = dol_now();
$error = 0;

$form = new Form($db);
$formproduct = new FormProduct($db);
$productstatic = new Product($db);
$warehousestatics = new Entrepot($db);
$warehousestatict = new Entrepot($db);

$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:Módulo_Stocks|DE:Modul_Bestände';

$title = $langs->trans('MassMovement');

llxHeader('', $title, $help_url);

print load_fiche_titre($langs->trans("MassStockTransferShort"), '', 'stock');

$titletoadd = $langs->trans("Select");
$buttonrecord = $langs->trans("RecordMovement");
$titletoaddnoent = $langs->transnoentitiesnoconv("Select");
$buttonrecordnoent = $langs->transnoentitiesnoconv("RecordMovement");
print '<span class="opacitymedium">'.$langs->trans("SelectProductInAndOutWareHouse", $titletoaddnoent, $buttonrecordnoent).'</span><br>';

print '<br>';

// Form to upload a file
print '<form name="userfile" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" METHOD="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="importCSV">';
print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';
print '<span class="opacitymedium">';
print $langs->trans("or").' ';
$importcsv = new ImportCsv($db, 'massstocklist');
print $form->textwithpicto($langs->trans('SelectAStockMovementFileToImport'), $langs->transnoentitiesnoconv("InfoTemplateImport", $importcsv->separator));
print '</span>';

print '<input type="file" name="userfile" size="20" maxlength="80"> &nbsp; &nbsp; ';
$out = (empty($conf->global->MAIN_UPLOAD_DOC) ? ' disabled' : '');
print '<input type="submit" class="button" value="'.$langs->trans("ImportFromCSV").'"'.$out.' name="sendit">';
$out = '';
if (!empty($conf->global->MAIN_UPLOAD_DOC)) {
	$max = $conf->global->MAIN_UPLOAD_DOC; // In Kb
	$maxphp = @ini_get('upload_max_filesize'); // In unknown
	if (preg_match('/k$/i', $maxphp)) {
		$maxphp = preg_replace('/k$/i', '', $maxphp);
		$maxphp = $maxphp * 1;
	}
	if (preg_match('/m$/i', $maxphp)) {
		$maxphp = preg_replace('/m$/i', '', $maxphp);
		$maxphp = $maxphp * 1024;
	}
	if (preg_match('/g$/i', $maxphp)) {
		$maxphp = preg_replace('/g$/i', '', $maxphp);
		$maxphp = $maxphp * 1024 * 1024;
	}
	if (preg_match('/t$/i', $maxphp)) {
		$maxphp = preg_replace('/t$/i', '', $maxphp);
		$maxphp = $maxphp * 1024 * 1024 * 1024;
	}
	$maxphp2 = @ini_get('post_max_size'); // In unknown
	if (preg_match('/k$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/k$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1;
	}
	if (preg_match('/m$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/m$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1024;
	}
	if (preg_match('/g$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/g$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1024 * 1024;
	}
	if (preg_match('/t$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/t$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1024 * 1024 * 1024;
	}
	// Now $max and $maxphp and $maxphp2 are in Kb
	$maxmin = $max;
	$maxphptoshow = $maxphptoshowparam = '';
	if ($maxphp > 0) {
		$maxmin = min($max, $maxphp);
		$maxphptoshow = $maxphp;
		$maxphptoshowparam = 'upload_max_filesize';
	}
	if ($maxphp2 > 0) {
		$maxmin = min($max, $maxphp2);
		if ($maxphp2 < $maxphp) {
			$maxphptoshow = $maxphp2;
			$maxphptoshowparam = 'post_max_size';
		}
	}

	$langs->load('other');
	$out .= ' ';
	$out .= info_admin($langs->trans("ThisLimitIsDefinedInSetup", $max, $maxphptoshow), 1);
} else {
	$out .= ' ('.$langs->trans("UploadDisabled").')';
}
print $out;

print '</form>';

print '<br><br>';

// Form to add a line
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="addline">';


print '<div class="div-table-responsive-no-min">';
print '<table class="liste centpercent">';

$param = '';

print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('WarehouseSource'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('WarehouseTarget'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('ProductRef'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
if ($conf->productbatch->enabled) {
	print getTitleFieldOfList($langs->trans('Batch'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
}
print getTitleFieldOfList($langs->trans('Qty'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'center tagtd maxwidthonsmartphone ');
print getTitleFieldOfList('', 0);
print '</tr>';

print '<tr class="oddeven">';
// From warehouse
print '<td>';
print img_picto($langs->trans("WarehouseSource"), 'stock', 'class="paddingright"').$formproduct->selectWarehouses($id_sw, 'id_sw', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200');
print '</td>';
// To warehouse
print '<td>';
print img_picto($langs->trans("WarehouseTarget"), 'stock', 'class="paddingright"').$formproduct->selectWarehouses($id_tw, 'id_tw', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200');
print '</td>';
// Product
print '<td>';
$filtertype = 0;
if (!empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
	$filtertype = '';
}
if ($conf->global->PRODUIT_LIMIT_SIZE <= 0) {
	$limit = '';
} else {
	$limit = $conf->global->PRODUIT_LIMIT_SIZE;
}

print img_picto($langs->trans("Product"), 'product', 'class="paddingright"');
print $form->select_produits($id_product, 'productid', $filtertype, $limit, 0, -1, 2, '', 1, array(), 0, '1', 0, 'minwidth200imp maxwidth300', 1, '', null, 1);
print '</td>';
// Batch number
if ($conf->productbatch->enabled) {
	print '<td>';
	print img_picto($langs->trans("LotSerial"), 'lot', 'class="paddingright"');
	print '<input type="text" name="batch" class="flat maxwidth50" value="'.$batch.'">';
	print '</td>';
}
// Qty
print '<td class="center"><input type="text" class="flat maxwidth50" name="qty" value="'.$qty.'"></td>';
// Button to add line
print '<td class="right"><input type="submit" class="button" name="addline" value="'.dol_escape_htmltag($titletoadd).'"></td>';

print '</tr>';

foreach ($listofdata as $key => $val) {
	$productstatic->fetch($val['id_product']);
	$warehousestatics->fetch($val['id_sw']);
	$warehousestatict->fetch($val['id_tw']);

	if ($productstatic->id <= 0) {
		$error++;
		setEventMessages($langs->trans("ObjectNotFound", $langs->transnoentitiesnoconv("Product")), null, 'errors');
	}
	if ($warehousestatics->id <= 0) {
		$error++;
		setEventMessages($langs->trans("ObjectNotFound", $langs->transnoentitiesnoconv("WarehouseSource")), null, 'errors');
	}
	if ($warehousestatics->id <= 0) {
		$error++;
		setEventMessages($langs->trans("ObjectNotFound", $langs->transnoentitiesnoconv("WarehouseTarget")), null, 'errors');
	}

	if (!$error) {
		print '<tr class="oddeven">';
		print '<td>';
		print $warehousestatics->getNomUrl(1);
		print '</td>';
		print '<td>';
		print $warehousestatict->getNomUrl(1);
		print '</td>';
		print '<td>';
		print $productstatic->getNomUrl(1).' - '.$productstatic->label;
		print '</td>';
		if ($conf->productbatch->enabled) {
			print '<td>';
			print $val['batch'];
			print '</td>';
		}
		print '<td class="center">'.$val['qty'].'</td>';
		print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=delline&token='.newToken().'&idline='.$val['id'].'">'.img_delete($langs->trans("Remove")).'</a></td>';
		print '</tr>';
	}
}

print '</table>';
print '</div>';

print '</form>';

print '<br>';

// Form to validate all movements
if (count($listofdata)) {
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire2" class="formconsumeproduce">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="createmovements">';

	// Button to record mass movement
	$codemove = (GETPOSTISSET("codemove") ? GETPOST("codemove", 'alpha') : dol_print_date(dol_now(), '%Y%m%d%H%M%S'));
	$labelmovement = GETPOST("label") ? GETPOST('label') : $langs->trans("StockTransfer").' '.dol_print_date($now, '%Y-%m-%d %H:%M');

	print '<div class="center">';
	print '<span class="fieldrequired">'.$langs->trans("InventoryCode").':</span> ';
	print '<input type="text" name="codemove" class="maxwidth300" value="'.dol_escape_htmltag($codemove).'"> &nbsp; ';
	print '<span class="clearbothonsmartphone"></span>';
	print $langs->trans("MovementLabel").': ';
	print '<input type="text" name="label" class="minwidth300" value="'.dol_escape_htmltag($labelmovement).'"><br>';
	print '<br>';

	print '<div class="center"><input type="submit" class="button" name="valid" value="'.dol_escape_htmltag($buttonrecord).'"></div>';

	print '<br>';
	print '</div>';

	print '</form>';
}

if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?urlfile='.urlencode(GETPOST('urlfile')).'&step=3'.$param, $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
}

// End of page
llxFooter();
$db->close();

/**
 * Verify if $haystack startswith $needle
 *
 * @param string $haystack string to test
 * @param string $needle string to find
 * @return bool false if Ko true else
 */
function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return substr($haystack, 0, $length) === $needle;
}

/**
 * Fetch object with ref
 *
 * @param Object $static_object static object to fetch
 * @param string $tmp_ref ref of the object to fetch
 * @return int <0 if Ko or Id of object
 */
function fetchref($static_object, $tmp_ref)
{
	if (startsWith($tmp_ref, 'ref:')) {
		$tmp_ref = str_replace('ref:', '', $tmp_ref);
	}
	$static_object->fetch('', $tmp_ref);
	return $static_object->id;
}
