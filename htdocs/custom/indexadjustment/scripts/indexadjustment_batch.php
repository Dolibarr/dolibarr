#!/usr/bin/env php
<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       scripts/indexadjustment_batch.php
 * \ingroup    indexadjustment
 * \brief      CLI script for batch index adjustments
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

// Load Dolibarr environment
$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__ . '/';

if (substr($sapi_type, 0, 3) != 'cli') {
	echo "Error: This script must be run from command line.\n";
	exit(1);
}

// Include Dolibarr environment
require_once $path . '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment_service.class.php';

// CLI options
$help = false;
$preview = false;
$execute = false;
$percent = null;
$customerId = null;
$label = null;

// Parse arguments
$args = $argv;
array_shift($args); // Remove script name

foreach ($args as $arg) {
	if ($arg == '--help' || $arg == '-h') {
		$help = true;
	} elseif ($arg == '--preview') {
		$preview = true;
	} elseif ($arg == '--execute') {
		$execute = true;
	} elseif (strpos($arg, '--percent=') === 0) {
		$percent = (float)str_replace(',', '.', substr($arg, 10));
	} elseif (strpos($arg, '--customer=') === 0) {
		$customerId = (int)substr($arg, 11);
	} elseif (strpos($arg, '--label=') === 0) {
		$label = substr($arg, 8);
	}
}

// Show help
if ($help || (!$preview && !$execute)) {
	echo "Index Adjustment Batch Script\n";
	echo "==============================\n\n";
	echo "Usage: php indexadjustment_batch.php [options]\n\n";
	echo "Options:\n";
	echo "  --help, -h            Show this help message\n";
	echo "  --percent=X.X         Adjustment percentage (required)\n";
	echo "  --customer=ID         Specific customer ID (optional, default: all)\n";
	echo "  --label=\"Text\"        Label for the adjustment (optional)\n";
	echo "  --preview             Preview changes without executing\n";
	echo "  --execute             Execute the adjustment\n\n";
	echo "Examples:\n";
	echo "  php indexadjustment_batch.php --percent=4.5 --preview\n";
	echo "  php indexadjustment_batch.php --percent=4.5 --customer=123 --execute\n";
	echo "  php indexadjustment_batch.php --percent=-2.0 --label=\"Deflation 2025\" --execute\n\n";
	exit(0);
}

// Validate percent
if ($percent === null) {
	echo "Error: --percent is required\n";
	exit(1);
}

// Initialize
$service = new IndexAdjustmentService($db);

// Get user for execution
$user = new User($db);
$user->fetch(1); // Use admin user
if (!$user->id) {
	echo "Error: Could not load user\n";
	exit(1);
}

echo "Index Adjustment Batch\n";
echo "======================\n";
echo "Percent: " . ($percent >= 0 ? '+' : '') . number_format($percent, 2) . "%\n";
echo "Customer: " . ($customerId ? $customerId : "All") . "\n";
echo "\n";

// Fetch contracts
echo "Fetching active contracts...\n";
$contracts = $service->fetchActiveContracts($customerId);

if (empty($contracts)) {
	echo "No active contracts found.\n";
	exit(0);
}

$contractIds = array_keys($contracts);
echo "Found " . count($contractIds) . " contracts with active service lines.\n\n";

// Preview
echo "Calculating preview...\n";
$preview_data = $service->previewAdjustments($contractIds, $percent);

echo "\nPreview Summary:\n";
echo "----------------\n";
echo "Contracts:       " . $preview_data['totals']['total_contracts'] . "\n";
echo "Lines:           " . $preview_data['totals']['total_lines'] . "\n";
echo "Total HT Before: " . number_format($preview_data['totals']['total_ht_before'], 2) . " EUR\n";
echo "Total HT After:  " . number_format($preview_data['totals']['total_ht_after'], 2) . " EUR\n";
$diff = $preview_data['totals']['total_diff'];
echo "Difference:      " . ($diff >= 0 ? '+' : '') . number_format($diff, 2) . " EUR\n";
echo "\n";

// Detail
echo "Detail per Contract:\n";
echo "--------------------\n";
foreach ($preview_data['contracts'] as $contractId => $contractData) {
	$contractRef = $contracts[$contractId]['ref'] ?? 'Unknown';
	$socName = $contracts[$contractId]['socname'] ?? 'Unknown';

	echo "\n$contractRef ($socName):\n";
	foreach ($contractData['lines'] as $line) {
		printf(
			"  - %s: %.2f EUR -> %.2f EUR (%+.2f EUR)\n",
			substr($line['product_label'], 0, 30),
			$line['subprice_before'],
			$line['subprice_after'],
			$line['price_diff']
		);
	}
}

// Execute
if ($execute) {
	echo "\n";
	echo "=================================\n";
	echo "EXECUTING ADJUSTMENT\n";
	echo "=================================\n";

	// Create adjustment object
	$adjustment = new IndexAdjustment($db);
	$adjustment->label = $label ?: 'Batch Adjustment ' . date('Y-m-d H:i:s');
	$adjustment->adjustment_percent = $percent;
	$adjustment->adjustment_date = dol_now();
	$adjustment->fk_soc = $customerId ?: null;

	$result = $adjustment->create($user);
	if ($result < 0) {
		echo "Error creating adjustment: " . $adjustment->error . "\n";
		exit(1);
	}

	echo "Created adjustment: " . $adjustment->ref . "\n";

	// Validate
	$adjustment->validate($user);
	echo "Validated adjustment.\n";

	// Execute
	$result = $service->execute($adjustment, $user, $contractIds);

	if ($result > 0) {
		// Reload to get stats
		$adjustment->fetch($adjustment->id);

		echo "\n";
		echo "SUCCESS!\n";
		echo "--------\n";
		echo "Reference:       " . $adjustment->ref . "\n";
		echo "Contracts:       " . $adjustment->total_contracts . "\n";
		echo "Lines:           " . $adjustment->total_lines . "\n";
		echo "Total HT Before: " . number_format($adjustment->total_ht_before, 2) . " EUR\n";
		echo "Total HT After:  " . number_format($adjustment->total_ht_after, 2) . " EUR\n";
		$diff = $adjustment->total_ht_after - $adjustment->total_ht_before;
		echo "Difference:      " . ($diff >= 0 ? '+' : '') . number_format($diff, 2) . " EUR\n";
	} else {
		echo "Error executing adjustment: " . $service->error . "\n";
		exit(1);
	}
} else {
	echo "\n";
	echo "This was a PREVIEW only. Use --execute to apply changes.\n";
}

echo "\nDone.\n";
exit(0);
