<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013 	   Florian Henry        <florian.henry@open-concept.pro>
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
 *      \file       htdocs/expedition/note.php
 *      \ingroup    expedition
 *      \brief      Note card expedition
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
if(! empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('sendings', 'companies', 'bills', 'deliveries', 'orders', 'stocks', 'other', 'propal', 'productbatch'));

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
$socid = '';
$error=0;
if($user->socid) $socid = $user->socid;
$result = restrictedArea($user, $origin, $origin_id);
$form = new Form($db);
$object = new Expedition($db);
if($id > 0 || ! empty($ref)) {
    $object->fetch($id, $ref);
    $object->fetch_thirdparty();
}

/*
 * Actions
 */

if($action == 'add') {
    $TQty = GETPOST('qty', 'array');
    $TExpeditionDetIds = GETPOST('fk_expeditiondet', 'array');
    $db->begin();
    if(! empty($TQty) && ! empty($TExpeditionDetIds)) {
        foreach($TQty as $fk_productbatch => $qty) {

            if($qty > 0) {

                $lineExp = new ExpeditionLigne($db);
                $lotStock = new Productbatch($db);
                $expBatch = new ExpeditionLineBatch($db);
                $lotStock->fetch($fk_productbatch);
                foreach($object->lines as $line) {
                    if($line->id == $TExpeditionDetIds[$fk_productbatch]) $lineExp = $line;
                }

                $res = $expBatch->fetchByExpDetSerial($object, $lotStock->batch, $lotStock->fk_product, $lotStock->warehouseid);
                /**
                 * CASE LOT ALREADY EXISTS : WE ONLY UPDATE QTY
                 */
                if($res > 0) {
                    $expBatch->qty += $qty;
                    if($expBatch->updateQty() < 0) {
                        setEventMessages($expBatch->error, $expBatch->errors, 'errors');
                        $error++;
                    }
                    $lineExp->fetch($expBatch->fk_expeditiondet);
                    $lineExp->qty += $qty;
                    $tmpBatch = $lineExp->detail_batch;
                    unset($lineExp->detail_batch);
                    if($lineExp->update($user) < 0) {
                        setEventMessages($lineExp->error, $lineExp->errors, 'errors');
                        $error++;
                    }
                    $lineExp->detail_batch = $tmpBatch;
                } else {
                    /**
                     * Check if line or batch with same warehouse exists
                     */
                    $lineIdToAddLot = 0;
                    if($lineExp->entrepot_id > 0) {
                        // single warehouse shipment line
                        if($lineExp->entrepot_id == $lotStock->warehouseid) {
                            $lineIdToAddLot = $lineExp->id;
                        }
                    }
                    else if(count($lineExp->details_entrepot) > 1) {
                        // multi warehouse shipment lines
                        foreach($lineExp->details_entrepot as $detail_entrepot) {
                            if($detail_entrepot->entrepot_id == $lotStock->warehouseid) {
                                $lineIdToAddLot = $detail_entrepot->line_id;
                            }
                        }
                    }

                    /**
                     * CASE NEW SERIAL NUMBER FOR EXISTING SHIPPING LINE
                     */
                    if($lineIdToAddLot > 0) {
                        $lineExp->fetch($lineIdToAddLot);
                        $lineExp->qty += $qty;

                        $tmpBatch = $lineExp->detail_batch;
                        unset($lineExp->detail_batch);
                        /** UPDATE EXP LINE */
                        if($lineExp->update($user) < 0) {
                            setEventMessages($lineExp->error, $lineExp->errors, 'errors');
                            $error++;
                        }
                        $lineExp->detail_batch = $tmpBatch;
                        /** UPDATE EXP BATCH */
                        $expBatch->sellby = $lotStock->sellby;
                        $expBatch->eatby = $lotStock->eatby;
                        $expBatch->batch = $lotStock->batch;
                        $expBatch->qty = $qty;
                        $expBatch->fk_origin_stock = $fk_productbatch;
                        if($expBatch->create($lineIdToAddLot)< 0) {
                            setEventMessages($lineExp->error, $lineExp->errors, 'errors');
                            $error++;
                        }

                    } else {
                        /**
                         * CASE NO LINE WITH SAME WAREHOUSE
                         */
                        $lineExp->origin_line_id = $lineExp->fk_origin_line;
                        $lineExp->entrepot_id = $lotStock->warehouseid;
                        $tmpBatch = $lineExp->detail_batch;
                        unset($lineExp->detail_batch);
                        $lineExp->detail_batch[0] = new ExpeditionLineBatch($db);
                        $lineExp->detail_batch[0]->fk_origin_stock = $fk_productbatch;
                        $lineExp->detail_batch[0]->batch = $lotStock->batch;
                        $lineExp->detail_batch[0]->entrepot_id = $lotStock->warehouseid;
                        $lineExp->detail_batch[0]->qty = $qty;
                        if($object->create_line_batch($lineExp, $lineExp->array_options) < 0) {
                            setEventMessages($object->error, $object->errors, 'errors');
                            $error++;
                        }
                        $lineExp->detail_batch = $tmpBatch;
                    }
                }

                /**
                 * HANDLE TO DEFINE LINE
                 */
                if(! $error && ! empty($object->lines)) {
                    foreach($object->lines as $line) {
                        if(! empty($line->detail_batch) && $line->product_tobatch) {
                            if(is_array($line->detail_batch)) {
                                foreach($line->detail_batch as $dbatch) {
                                    if(empty($dbatch->fk_origin_stock) && $lotStock->fk_product == $line->fk_product) {
                                        $tmpLine = new ExpeditionLigne($db);
                                        $tmpLine->fetch($dbatch->fk_expeditiondet);
                                        $tmpLine->qty -= $qty;
                                        if(empty($tmpLine->entrepot_id)) $tmpLine->entrepot_id = 0;
                                        unset($tmpLine->detail_batch);
                                        if($tmpLine->qty > 0) $res = $tmpLine->update($user);
                                        else $res = $tmpLine->delete($user);
                                        if($res < 0) {
                                            $error++;
                                            setEventMessage($tmpLine->errors, 'errors');
                                        }
                                        else {
                                            $dbatch->qty -= $qty;
                                            if($dbatch->qty > 0) $res = $dbatch->updateQty();
                                            else $res = $dbatch->deleteCommon($user);
                                            if($res < 0) {
                                                $error++;
                                                setEventMessage($dbatch->errors, 'errors');
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    if(! $error) {
        $db->commit();
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        exit();
    }
    else {
        $db->rollback();
    }
}
if($action == 'deleteline') {
    $db->begin();
    $fkExpBatch = GETPOST('fk_exp_batch', 'int');
    $fk_product = GETPOST('fk_product','int');
    $expBatch = new ExpeditionLineBatch($db);
    $expBatch->fetchCommon($fkExpBatch);
    $expLine = new ExpeditionLigne($db);

    $expBatchToDefine = $object->getBatchToDefineLine($fk_product);
    if(!empty($expBatchToDefine)) {
        //MAJ TO Define batch qty
        $expBatchToDefine->qty+=$expBatch->qty;
        $res = $expBatchToDefine->updateQty();
        if($res <= 0) {
            $error++;
            setEventMessage($expBatchToDefine->errors, 'errors');
        }
        //MAJ To define line qty
        $expLine->fetch($expBatchToDefine->fk_expeditiondet);
        $expLine->qty += $expBatch->qty;
        $expLine->entrepot_id = 0;
        $res = $expLine->update($user);
        if($res <= 0) {
            $error++;
            setEventMessage($expLine->errors, 'errors');
        }
    } else {
        $expLine->fetch($expBatch->fk_expeditiondet);
        //Create to define if not exists
        $expLine->origin_line_id = $expLine->fk_origin_line;
        $expLine->entrepot_id = 0;
        $expLine->detail_batch[0] = new ExpeditionLineBatch($db);
        $expLine->detail_batch[0]->fk_origin_stock = 0;
        $expLine->detail_batch[0]->batch = '';
        $expLine->detail_batch[0]->entrepot_id = 0;
        $expLine->detail_batch[0]->qty = $expBatch->qty;
        if($object->create_line_batch($expLine, $expLine->array_options) < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $error++;
        }

    }


    $expLine->fetch($expBatch->fk_expeditiondet);

    //MAJ ExpLine Qty
    if($expBatch->qty >= $expLine->qty) {
        $res = $expLine->delete($user);
    } else {
        $expLine->qty -= $expBatch->qty;
        $res = $expLine->update($user);
    }
    if($res <= 0) {
        $error++;
        setEventMessage($expLine->errors, 'errors');
    }

    $res = $expBatch->deleteCommon($user);
    if($res <= 0) {
        $error++;
        setEventMessage($expLine->errors, 'errors');
    }

     if(! $error) {
        $db->commit();
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        exit();
    }
    else {
        $db->rollback();
    }
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

if($id > 0 || ! empty($ref)) {
    $head = shipping_prepare_head($object);
    print dol_get_fiche_head($head, 'detail_batch', $langs->trans("DetailBatch"), -1, 'sending');

    // Shipment card
    $linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1'.(! empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<div class="refidno">';
    // Ref customer shipment
    $morehtmlref .= $form->editfieldkey("RefCustomer", '', $object->ref_customer, $object, $user->rights->expedition->creer, 'string', '', 0, 1);
    $morehtmlref .= $form->editfieldval("RefCustomer", '', $object->ref_customer, $object, $user->rights->expedition->creer, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
    // Project
    if(! empty($conf->projet->enabled)) {
        $langs->load("projects");
        $morehtmlref .= '<br>'.$langs->trans('Project').' ';
        if(0) {    // Do not change on shipment
            if($action != 'classify') {
                $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
            }
            if($action == 'classify') {
                // $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
                $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref .= '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
                $morehtmlref .= '</form>';
            }
            else {
                $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
        }
        else {
            // We don't have project on shipment, so we will use the project or source object instead
            // TODO Add project on shipment
            $morehtmlref .= ' : ';
            if(! empty($objectsrc->fk_project)) {
                $proj = new Project($db);
                $proj->fetch($objectsrc->fk_project);
                $morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$objectsrc->fk_project.'" title="'.$langs->trans('ShowProject').'">';
                $morehtmlref .= $proj->ref;
                $morehtmlref .= '</a>';
            }
            else {
                $morehtmlref .= '';
            }
        }
    }
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
    $TDetailBatch = $TToDefine = array();

    if(! empty($object->lines)) {
        foreach($object->lines as $line) {
            if(! empty($line->detail_batch) && $line->product_tobatch) {
                if(empty($line->product)) $line->fetch_product();
                foreach($line->detail_batch as $dbatch) {
                    $dbatch->expLine = $line;
                    if(! empty($dbatch->fk_origin_stock)) $TDetailBatch[$dbatch->id] = $dbatch;
                    else $TToDefine[$dbatch->id] = $dbatch;
                }
            }
        }
    }

    print '<div class="underbanner clearboth"></div>';

    $fullColspan = 4;
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="id" value="'.$id.'">';
    print '
	<table width="100%" class="noborder">
		<tr class="liste_titre">';
    print'<td>'.$langs->trans('Product').'</td>';
    print'<td>'.$langs->trans('Batch').'</td>';
    print'<td>'.$langs->trans('Quantity').'</td>';
    print'<td></td>';

    $prod = new Product($db);

    $canEdit = $object->statut == Expedition::STATUS_DRAFT && $user->rights->expedition->creer;
    $TQtyUsed = array();
    if(! empty($TDetailBatch)) {

        foreach($TDetailBatch as $k => $line) {
            $productLot = new Productlot($db);
            $expLine = new ExpeditionLigne($db);
            $warehouse = new Entrepot($db);
            $productLot->fetch(0, $line->expLine->fk_product, $line->batch);
            $expLine->fetch($line->fk_expeditiondet);
            $warehouse->fetch($expLine->entrepot_id);
            print '<tr class="oddeven">';
            print '<td>'.$productLot->showOutputField($productLot->fields['fk_product'], 'fk_product', $productLot->fk_product).'</td>';
            print '<td>'.$productLot->getNomUrl(1).' / '.$warehouse->getNomUrl(1).'</td>';
            print '<td>'.$line->qty.'</td>';
            $TQtyUsed[$productLot->id] += $line->qty;
            print '<td align="right">';
            if($canEdit) echo '<a href="?action=deleteline&id='.$object->id.'&fk_exp_batch='.$line->id.'&fk_product='.$productLot->fk_product.'">'.img_delete().'</a>';
            print '</td>';
            print '</tr>';
        }
    }
    else print '<tr><td colspan="'.$fullColspan.'" class="center">'.$langs->trans('NoBatch').'</td></tr>';

    if($canEdit && ! empty($TToDefine)) {
        print '<tr class="liste_titre">';
        print '<td colspan="'.$fullColspan.'">'.$langs->trans('NewBatch').'</td>';
        print '</tr>';
        foreach($TToDefine as $line) {
            if(empty($line->expLine->product->stock_warehouse)) $line->expLine->product->load_stock();
            print '<tr class="oddeven">';
            print '<td>'.$line->expLine->product->getNomUrl(1).' ('.$langs->trans('ToDefine').' : '.$line->qty.')</td>';
            print '<td></td>';
            print '<td></td>';
            print '<td></td>';
            print '</tr>';
            foreach($line->expLine->product->stock_warehouse as $warehouse_id => $stock_warehouse) {
                $tmpwarehouseObject = new Entrepot($db);
                $productlotObject = new Productlot($db);
                $tmpwarehouseObject->fetch($warehouse_id);
                if(($stock_warehouse->real > 0) && (count($stock_warehouse->detail_batch))) {
                    foreach($stock_warehouse->detail_batch as $dbatch) {

                        $result = $productlotObject->fetch(0, $line->expLine->fk_product, $dbatch->batch);
                        if(!empty($TQtyUsed[$productlotObject->id])) $batchqty = $dbatch->qty - $TQtyUsed[$productlotObject->id];
                        else $batchqty = $dbatch->qty;
                        if($batchqty <= 0) continue;

                        $batchStock = +$dbatch->qty; // To get a numeric
                        print '<tr class="oddeven">';
                        print '<td></td>';
                        print '<td class="left">';
                        print $tmpwarehouseObject->getNomUrl(0).' / ';
                        print $langs->trans("Batch").': ';
                        if($result > 0) print $productlotObject->getNomUrl(1);
                        else print 'TableLotIncompleteRunRepairWithParamStandardEqualConfirmed';
                        print ' ('.$batchqty.')';
                        print '</td><td >';
                        print '<input class="qtyl" name="qty['.$dbatch->id.']" id="qty['.$dbatch->id.']" type="text" size="4" value="0">';
                        print '<input type="hidden" name="fk_expeditiondet['.$dbatch->id.']" value="'.$line->expLine->id.'">';
                        print '</td>';
                        print '<td></td></tr>';
                    }
                }
            }
        }
        print '</table>';
        print '<div align="center"><input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Add")).'"></div>';
    }
    print '</form>';

    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
