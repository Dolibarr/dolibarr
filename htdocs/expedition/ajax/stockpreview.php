<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file htdocs/expedition/ajax/stockpreview.php
 * \ingroup expedition
 * \brief Read-only stock preview for a standalone shipment catalog line.
 */

define('NOTOKENRENEWAL', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
require '../../main.inc.php';
/**
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';

$object = new Expedition($db);
$permissiontoadd = $user->hasRight('expedition', 'creer');
if (!isModEnabled('shipping') || !isModEnabled('stock') || !$permissiontoadd || !getDolGlobalInt('SHIPMENT_STANDALONE')) {
	accessforbidden();
}
if ($object->fetch(GETPOSTINT('id')) <= 0 || $object->origin_id > 0 || $object->status != Expedition::STATUS_DRAFT) {
	accessforbidden();
}
restrictedArea($user, 'expedition', $object->id, '');

$product = new Product($db);
if ($product->fetch(GETPOSTINT('idprod')) <= 0 || !in_array($product->entity, explode(',', getEntity('product')))) {
	accessforbidden();
}
$combinations = GETPOST('combinations', 'array:alphanohtml');
if (isModEnabled('variants') && !empty($combinations)) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
	$combination = new ProductCombination($db);
	$variant = $combination->fetchByProductCombination2ValuePairs($product->id, $combinations);
	if (!$variant || $product->fetch($variant->fk_product_child) <= 0 || !in_array($product->entity, explode(',', getEntity('product')))) {
		accessforbidden();
	}
}
if (($product->type == Product::TYPE_PRODUCT && !isModEnabled('product'))
	|| ($product->type == Product::TYPE_SERVICE && (!isModEnabled('service') || (!getDolGlobalInt('SHIPMENT_SUPPORTS_SERVICES') && !getDolGlobalInt('STOCK_SUPPORTS_SERVICES'))))) {
	accessforbidden();
}

$preview = shippingGetStockPreview($object, $product, GETPOSTFLOAT('qty', 'MS'), GETPOSTINT('entrepot_id'), (bool) GETPOSTINT('keepwarehouse'));
top_httphead('application/json');
if ($preview === false) {
	http_response_code(500);
	print json_encode(array('error' => 'StockUnavailable'));
} else {
	$preview['stock_available_formatted'] = $preview['stock_available'] === null ? '' : price($preview['stock_available'], 0, '', 0, 0);
	$preview['stock_after_formatted'] = $preview['stock_after'] === null ? '' : price($preview['stock_after'], 0, '', 0, 0);
	print json_encode($preview);
}
$db->close();
