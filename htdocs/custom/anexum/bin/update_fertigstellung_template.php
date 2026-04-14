#!/usr/bin/env php
<?php
/* Copyright (C) 2026  Florian Hödl  <florian@hoedl.co>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    htdocs/custom/anexum/bin/update_fertigstellung_template.php
 * \ingroup anexum
 * \brief   Tokenize the Fertigstellungsmeldung email template.
 *
 * ClickUp 869ab3xx9: the template at llx_c_email_templates rowid 78 had all
 * values hardcoded from a single example (Porr AG / AT_P-KAP / Jumper / ...).
 * This script replaces those hardcoded values with substitution tokens so the
 * template renders correctly for every contract.
 *
 * Usage: php update_fertigstellung_template.php [-n|--dry-run] [-v|--verbose]
 */

if (!defined('NOTOKENRENEWAL')) { define('NOTOKENRENEWAL', '1'); }
if (!defined('NOREQUIREMENU')) { define('NOREQUIREMENU', '1'); }
if (!defined('NOREQUIREHTML')) { define('NOREQUIREHTML', '1'); }
if (!defined('NOREQUIREAJAX')) { define('NOREQUIREAJAX', '1'); }
if (!defined('NOLOGIN')) { define('NOLOGIN', '1'); }
if (!defined('NOSESSION')) { define('NOSESSION', '1'); }

$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
	fwrite(STDERR, "Run with php-cli, not php-cgi.\n");
	exit(1);
}

$opts    = getopt('nvh', array('dry-run', 'verbose', 'help'));
$dryrun  = isset($opts['n']) || isset($opts['dry-run']);
$verbose = $dryrun || isset($opts['v']) || isset($opts['verbose']);

if (isset($opts['h']) || isset($opts['help'])) {
	print "Usage: php update_fertigstellung_template.php [OPTIONS]\n";
	print "Options:\n";
	print "  -n, --dry-run   Print intended UPDATE without applying it\n";
	print "  -v, --verbose   Show detailed diff\n";
	print "  -h, --help      This help\n";
	exit(0);
}

$script_dir = dirname(__FILE__);
$dolibarr_htdocs = realpath($script_dir . '/../../../');
if ($dolibarr_htdocs && file_exists($dolibarr_htdocs . '/master.inc.php')) {
	chdir($dolibarr_htdocs);
	$res = @include $dolibarr_htdocs . '/master.inc.php';
}
if (empty($res)) {
	fwrite(STDERR, "Could not include Dolibarr master.inc.php\n");
	exit(1);
}

/** @var DoliDB $db */

$targetRowid = 78;

$sql = "SELECT rowid, label, topic, content FROM " . MAIN_DB_PREFIX . "c_email_templates WHERE rowid = " . (int) $targetRowid;
$resql = $db->query($sql);
if (!$resql) {
	fwrite(STDERR, "SQL error: " . $db->lasterror() . "\n");
	exit(1);
}
$row = $db->fetch_object($resql);
$db->free($resql);
if (!$row) {
	fwrite(STDERR, "Template rowid " . $targetRowid . " not found.\n");
	exit(1);
}

print "Found template #{$row->rowid}: {$row->label}\n";

// Replace hardcoded values with tokens. Only the right-column values get
// replaced; left-column German labels stay as-is.
$replacements = array(
	// Topic line
	'Fertigstellungsmeldung zum Auftrag: CO2508-0447' => 'Fertigstellungsmeldung zum Auftrag: __ORDER_REF__',
	// Table cell values (right column)
	'>Porr AG<'                                => '>__THIRDPARTY_NAME__<',
	'>AT_P-KAP<'                               => '>__REF_CLIENT__<',
	'>Krottendorf 10a, 8605 Kapfenberg, Steiermark<' => '>__THIRDPARTY_ADDRESS__, __THIRDPARTY_ZIP__ __THIRDPARTY_TOWN__, __THIRDPARTY_STATE__<',
	'>CO2508-0447<'                            => '>__ORDER_REF__<',
	'>CT2311-0170<'                            => '>__REF__<',
	'>DIA (Direkter Internetzugang)<'          => '>__EXTRAFIELD_TECHNOLOGY_NEW_LABEL__<',
	'>100 Mbit/s (Upgrade von 50 Mbit/s)<'     => '>__EXTRAFIELD_BANDWIDTH_NEW_LABEL__ Mbit/s<',
	'>Jumper<'                                 => '>__EXTRAFIELD_PROVIDER_NEW_LABEL__<',
	'>Stadtwerke Kapfenberg<'                  => '>__EXTRAFIELD_LASTMILEPROVIDER__<',
	'>217.196.79.113<'                         => '>__EXTRAFIELD_IPADRESSE__<',
	'>217.196.79.112 / 29<'                    => '>__EXTRAFIELD_IPRANGE_NEW_LABEL__<',
	'>Ubiquiti EdgeRouter X SFP<'              => '>__EXTRAFIELD_CPE_MODEL_LABEL__<',
	// Body prose lines
	'29. August 2025'                          => '__DATE__',
	'Provider (Jumper)'                        => 'Provider (__EXTRAFIELD_PROVIDER_NEW_LABEL__)',
	'100 Mbit/s aktiv'                         => '__EXTRAFIELD_BANDWIDTH_NEW_LABEL__ Mbit/s aktiv',
);

$newContent = $row->content;
$newTopic   = $row->topic;
$changes    = 0;

foreach ($replacements as $needle => $token) {
	$occurrencesC = substr_count($newContent, $needle);
	$occurrencesT = substr_count($newTopic, $needle);
	if ($occurrencesC > 0) {
		$newContent = str_replace($needle, $token, $newContent);
		$changes += $occurrencesC;
		if ($verbose) {
			print "  content: " . $occurrencesC . "x  " . substr($needle, 0, 60) . "  =>  " . substr($token, 0, 60) . "\n";
		}
	}
	if ($occurrencesT > 0) {
		$newTopic = str_replace($needle, $token, $newTopic);
		$changes += $occurrencesT;
		if ($verbose) {
			print "  topic:   " . $occurrencesT . "x  " . substr($needle, 0, 60) . "  =>  " . substr($token, 0, 60) . "\n";
		}
	}
}

if ($changes === 0) {
	print "No hardcoded values left to replace. Template is already tokenized.\n";
	exit(0);
}

print "Total replacements: {$changes}\n";

if ($dryrun) {
	print "\n--- DRY RUN ---\n";
	print "Would UPDATE " . MAIN_DB_PREFIX . "c_email_templates\n";
	print "  SET topic   = <new topic>\n";
	print "      content = <new content>\n";
	print "  WHERE rowid = {$targetRowid}\n";
	print "\nNew topic: {$newTopic}\n";
	exit(0);
}

$db->begin();
$updateSql = "UPDATE " . MAIN_DB_PREFIX . "c_email_templates"
	. " SET topic = '" . $db->escape($newTopic) . "',"
	. "     content = '" . $db->escape($newContent) . "'"
	. " WHERE rowid = " . (int) $targetRowid;

$upres = $db->query($updateSql);
if (!$upres) {
	$db->rollback();
	fwrite(STDERR, "UPDATE failed: " . $db->lasterror() . "\n");
	exit(1);
}
$db->commit();

print "Template #{$targetRowid} updated.\n";
exit(0);
