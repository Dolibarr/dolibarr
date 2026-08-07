<?php
/* Copyright (C) 2024 Accellier Ltd.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */
/**
 * \file    htdocs/product/ajax/get_next_batch.php
 * \brief   AJAX endpoint: return N sequential serial/lot numbers for a product.
 *
 * GET parameters:
 *   fk_product     (int)    Product ID
 *   qty            (int)    Number of values to generate. For SN: one per unit. For LOT: one per split row.
 *   existing_lots  (string) Comma-separated lot/serial values already in the current form
 *                           (not yet committed). The endpoint skips these to avoid duplicates
 *                           within the same session.
 *
 * JSON response:
 *   { "batches": ["SN-0001", "SN-0002", ...], "type": "sn"|"lot" }
 *   { "error": "message" }
 */
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
require '../../main.inc.php';
/**
 * @var Conf        $conf
 * @var DoliDB      $db
 * @var Translate   $langs
 * @var User        $user
 */
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
header('Content-Type: application/json');
$fk_product = GETPOSTINT('fk_product');
$qty        = max(1, GETPOSTINT('qty'));
// Lot/serial values already displayed in the form that have not been submitted yet.
// The endpoint must not return any of these to avoid duplicate lot numbers within
// the same form session (getNextValue() reads MAX from the DB and cannot see
// values that are only in the browser).
$existing_raw  = GETPOST('existing_lots', 'alphanohtml');
$existing_lots = ($existing_raw !== '') ? array_filter(array_map('trim', explode(',', $existing_raw))) : array();
// Permission check
if (!$user->hasRight('produit', 'lire') && !$user->hasRight('service', 'lire')) {
	echo json_encode(array('error' => 'Access denied'));
	exit;
}
if ($fk_product <= 0) {
	echo json_encode(array('error' => 'Missing fk_product'));
	exit;
}
if (!isModEnabled('productbatch')) {
	echo json_encode(array('error' => 'Product batch module not enabled'));
	exit;
}
$product = new Product($db);
if ($product->fetch($fk_product) <= 0) {
	echo json_encode(array('error' => 'Product not found'));
	exit;
}
if (empty($product->status_batch)) {
	echo json_encode(array('error' => 'Product does not use batch/serial tracking'));
	exit;
}
$isSN = ($product->status_batch == 2);
// Resolve addon name
if ($isSN) {
	$addonName = getDolGlobalString('PRODUCTBATCH_SN_ADDON');
} else {
	$addonName = getDolGlobalString('PRODUCTBATCH_LOT_ADDON');
}
$supportedAddons = array('mod_sn_advanced', 'mod_sn_standard', 'mod_lot_advanced', 'mod_lot_standard');
if (!in_array($addonName, $supportedAddons)) {
	echo json_encode(array('error' => 'Unsupported batch addon: '.$addonName));
	exit;
}
dol_include_once('/core/modules/product_batch/'.$addonName.'.php');
$addonMod = new $addonName();
// Use a real Productlot object (not a bare stdClass) so it matches the ?Productlot
// phpdoc type declared by getNextValue() in mod_sn_advanced/mod_lot_advanced.
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
$batchObj             = new Productlot($db);
$batchObj->fk_product = $fk_product;
// Determine mask and step (used by incrementBatchValue() and the sequence loop)
$isAdvanced = in_array($addonName, array('mod_sn_advanced', 'mod_lot_advanced'));
$step       = getDolGlobalString('SN_ADVANCED_INCREMENT') ? (int) getDolGlobalString('SN_ADVANCED_INCREMENT') : 1;
$mask       = '';
if ($isAdvanced) {
	if ($isSN && getDolGlobalString('PRODUCTBATCH_SN_USE_PRODUCT_MASKS') && !empty($product->batch_mask)) {
		$mask = $product->batch_mask;
	} elseif (!$isSN && getDolGlobalString('PRODUCTBATCH_LOT_USE_PRODUCT_MASKS') && !empty($product->batch_mask)) {
		$mask = $product->batch_mask;
	} elseif ($isSN) {
		$mask = getDolGlobalString('SN_ADVANCED_MASK');
	} else {
		$mask = getDolGlobalString('LOT_ADVANCED_MASK');
	}
}
/**
 * Return true if $value already exists in llx_product_lot.
 * Both lot addons use this table as their counter source, so any value found here
 * must be skipped to avoid issuing a duplicate lot/serial number.
 *
 * @param DoliDB $db     Database handler
 * @param string $value  Batch/serial value to look up
 * @return bool          True if the value already exists in llx_product_lot
 */
function batchExistsInDb($db, $value)
{
	$sql = "SELECT rowid FROM ".$db->prefix()."product_lot WHERE batch = '".$db->escape($value)."'";
	$res = $db->query($sql);
	return ($res && $db->fetch_object($res) !== null);
}
/**
 * Return the next batch/serial value after $current, using the same increment
 * logic as the configured addon.
 *
 * @param string $current     Current value to increment from
 * @param bool   $isAdvanced  True for mod_*_advanced addons
 * @param string $mask        Advanced mask string (ignored for standard addon)
 * @param int    $step        Increment step
 * @return string             Next value, or $current if the counter cannot be parsed
 */
function incrementBatchValue($current, $isAdvanced, $mask, $step)
{
	if ($isAdvanced) {
		if (preg_match('/\{(0+)([@\+][0-9\-\+\=]+)?([@\+][0-9\-\+\=]+)?\}/i', $mask, $reg)) {
			$maskcounter       = $reg[1];
			$maskNoBraces      = str_replace(array('{', '}'), '', $mask);
			$maskcounter_start = strpos($maskNoBraces, $maskcounter);
			$maskcounter_len   = strlen($maskcounter);
			$batch_pre     = substr($current, 0, $maskcounter_start);
			$batch_suf     = substr($current, $maskcounter_start + $maskcounter_len);
			$batch_counter = substr($current, $maskcounter_start, $maskcounter_len);
			if (!empty($batch_counter) && $batch_pre !== $current) {
				// @phan-suppress-next-line PhanPluginPrintfVariableFormatString
				return $batch_pre.sprintf("%0".$maskcounter_len."d", ((int) $batch_counter + $step)).$batch_suf;
			}
		}
		return $current; // mask has no counter segment
	} else {
		// Standard addon: PREFIX-NNNN
		$parts = explode('-', $current);
		if (count($parts) >= 2) {
			$num = (int) array_pop($parts) + 1;
			return implode('-', $parts).'-'.($num < 9999 ? sprintf('%04d', $num) : (string) $num);
		}
		return $current;
	}
}
// Call getNextValue() ONCE — it queries MAX(batch) from the DB.
// Subsequent values (for SN sequences or duplicate-skipping) are computed arithmetically.
$firstValue = $addonMod->getNextValue(null, $batchObj);
if (empty($firstValue) || $firstValue <= 0) {
	$langs->load('errors');
	echo json_encode(array('error' => !empty($addonMod->error) ? $addonMod->error : 'Could not generate batch number'));
	exit;
}
/**
 * Advance $value past anything in $skipList or already in llx_product_lot.
 * Returns the first candidate that is both absent from the DB and not in $skipList.
 *
 * @param string   $value       Starting value to advance from
 * @param bool     $isAdvanced  True for mod_*_advanced addons
 * @param string   $mask        Advanced mask string (ignored for standard addon)
 * @param int      $step        Increment step
 * @param string[] $skipList    Values to skip even if not yet in the database
 * @param DoliDB   $db          Database handler
 * @return string               First candidate value that is unique
 */
function nextUniqueBatchValue($value, $isAdvanced, $mask, $step, $skipList, $db)
{
	$maxAttempts = 500;
	for ($i = 0; $i < $maxAttempts; $i++) {
		if (!in_array($value, $skipList) && !batchExistsInDb($db, $value)) {
			return $value;
		}
		$next = incrementBatchValue($value, $isAdvanced, $mask, $step);
		if ($next === $value) {
			break; // mask has no counter — cannot advance further
		}
		$value = $next;
	}
	return $value; // best effort if loop exhausted
}
// Advance the first value past anything already in the form or in the DB.
$firstValue = nextUniqueBatchValue($firstValue, $isAdvanced, $mask, $step, $existing_lots, $db);
$batches    = array($firstValue);
$usedValues = array_merge($existing_lots, array($firstValue));
// SN: one serial per unit.  LOT: one per split row (caller passes row count as qty).
$countNeeded = $qty;
if ($countNeeded > 1) {
	$prevValue = $firstValue;
	for ($n = 2; $n <= $countNeeded; $n++) {
		$candidate = incrementBatchValue($prevValue, $isAdvanced, $mask, $step);
		if ($candidate === $prevValue) {
			// Mask has no counter — cannot generate distinct values
			$batches[] = $prevValue;
		} else {
			// Skip past DB duplicates and values already in this batch
			$candidate  = nextUniqueBatchValue($candidate, $isAdvanced, $mask, $step, $usedValues, $db);
			$prevValue  = $candidate;
			$batches[]  = $candidate;
			$usedValues[] = $candidate;
		}
	}
}
echo json_encode(array(
	'batches' => $batches,
	'type'    => $isSN ? 'sn' : 'lot',
));
