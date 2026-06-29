#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2026		Quentin Vial-Gouteyron	<quentin.vial-gouteyron@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file scripts/product/init_currency_prices.php
 * \ingroup scripts
 * \brief Bulk-initialize missing catalog per-currency sell prices from the company price and current rate (issue #32379)
 */

if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(1);
}

@set_time_limit(0); // No timeout for this script
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1); // Set this define to 0 if you want to lock your script when dolibarr setup is "locked to admin user only".

// Include and load Dolibarr environment variables
require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/product/class/productpricecurrency.class.php";
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */
$langs->load("main");

$version = DOL_VERSION;
$error = 0;

/*
 * Main
 */

print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".implode(',', $argv));

if (empty($argv[1]) || !in_array($argv[1], array('test', 'confirm'), true)) {
	print "Create the missing catalog (any-customer) fixed sell prices per currency.\n";
	print "Each created price = company price converted at the current exchange rate.\n";
	print "Existing per-currency prices are NEVER modified (safe to run again).\n\n";
	print "Usage:   $script_file (test|confirm) [currencycode] [pricelevel]\n";
	print "  test      Show what would be done without writing anything.\n";
	print "  confirm   Actually create the missing per-currency prices.\n";
	print "  currencycode  Optional ISO code to restrict to a single currency (e.g. USD).\n";
	print "  pricelevel    Optional price level to restrict to (0 = all levels).\n";
	exit(1);
}

if (!isModEnabled('multicurrency')) {
	print "Error: the multicurrency module is not enabled.\n";
	exit(1);
}

$dryrun = ($argv[1] == 'test');
$onecode = !empty($argv[2]) ? preg_replace('/[^A-Za-z]/', '', $argv[2]) : '';
$onelevel = isset($argv[3]) ? (int) $argv[3] : 0;
$codes = ($onecode !== '') ? array(strtoupper($onecode)) : array();

// Make sure we have a user to stamp the rows (author)
if (empty($user->id)) {
	$user->fetch(1);
	$user->loadRights();
}

// Mass price initialization is an administrative operation
if (empty($user->admin)) {
	print "Error: this script must run as an administrator user.\n";
	exit(1);
}

print '--- start'.($dryrun ? ' (test mode, no write)' : '')."\n";

$ppc = new ProductPriceCurrency($db);

if ($dryrun) {
	// Test mode: count exactly what would be created, without writing anything.
	$wouldcreate = $ppc->initCatalogCurrencyPrices($user, $codes, $onelevel, true);
	if ($wouldcreate < 0) {
		print "Error: ".$ppc->error."\n";
		$db->close();
		exit(1);
	}
	print "Would create ".$wouldcreate." missing per-currency price(s)";
	print ($onelevel > 0 ? " on price level ".$onelevel : " on all price levels").".\n";
	print "Re-run with 'confirm' to create them.\n";
	$db->close();
	exit(0);
}

$created = $ppc->initCatalogCurrencyPrices($user, $codes, $onelevel);
if ($created < 0) {
	print "Error: ".$ppc->error."\n";
	$error++;
} else {
	print "Created ".$created." missing per-currency price(s).\n";
}

print '--- end'."\n";

$db->close(); // Close $db database opened handler

exit($error);
