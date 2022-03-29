#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2009-2012 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file scripts/invoices/rebuild_merge_pdf.php
 * \ingroup facture
 * \brief Script to rebuild PDF and merge PDF files into one
 */

if (!defined('NOSESSION')) define('NOSESSION', '1');

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

// Include Dolibarr environment
require_once $path."../../htdocs/master.inc.php";
// After this $db is an opened handler to database. We close it at end of file.
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/core/modules/facture/modules_facture.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice2.lib.php';

// Load main language strings
$langs->load("main");

// Global variables
$version = DOL_VERSION;
$error = 0;

/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

// Check parameters
if (!isset($argv[1])) {
	usage();
	exit(-1);
}

$diroutputpdf = $conf->facture->dir_output.'/temp';
$newlangid = 'en_EN'; // To force a new lang id
$filter = array();
$regenerate = ''; // Ask regenerate (contains name of model to use)
$option = '';
$fileprefix = 'mergedpdf';

foreach ($argv as $key => $value) {
	$found = false;

	// Define options
	if (preg_match('/^lang=/i', $value)) {
		$found = true;
		$valarray = explode('=', $value);
		$newlangid = $valarray[1];
		print 'Use language '.$newlangid.".\n";
	}
	if (preg_match('/^prefix=/i', $value)) {
		$found = true;
		$valarray = explode('=', $value);
		$fileprefix = $valarray[1];
		print 'Use prefix for filename '.$fileprefix.".\n";
	}

	if (preg_match('/^regenerate=(.*)/i', $value, $reg)) {
		if (!in_array($reg[1], array('', '0', 'no'))) {
			$found = true;
			$regenerate = $reg[1];
			print 'Regeneration of PDF is requested with template '.$regenerate."\n";
		}
	}

	if ($value == 'filter=all') {
		$found = true;
		$option .= (empty($option) ? '' : '_').'all';
		$filter[] = 'all';

		print 'Rebuild PDF for all invoices'."\n";
	}

	if ($value == 'filter=date') {
		$found = true;
		$option .= (empty($option) ? '' : '_').'date_'.$argv[$key + 1].'_'.$argv[$key + 2];
		$filter[] = 'date';

		$dateafterdate = dol_stringtotime($argv[$key + 1]);
		$datebeforedate = dol_stringtotime($argv[$key + 2]);
		print 'Rebuild PDF for invoices validated between '.dol_print_date($dateafterdate, 'day', 'gmt')." and ".dol_print_date($datebeforedate, 'day', 'gmt').".\n";
	}

	if ($value == 'filter=payments') {
		$found = true;
		$option .= (empty($option) ? '' : '_').'payments_'.$argv[$key + 1].'_'.$argv[$key + 2];
		$filter[] = 'payments';

		$paymentdateafter = dol_stringtotime($argv[$key + 1].'000000');
		$paymentdatebefore = dol_stringtotime($argv[$key + 2].'235959');
		if (empty($paymentdateafter) || empty($paymentdatebefore)) {
			print 'Error: Bad date format or value'."\n";
			exit(-1);
		}
		print 'Rebuild PDF for invoices with at least one payment between '.dol_print_date($paymentdateafter, 'day', 'gmt')." and ".dol_print_date($paymentdatebefore, 'day', 'gmt').".\n";
	}

	if ($value == 'filter=nopayment') {
		$found = true;
		$option .= (empty($option) ? '' : '_').'nopayment';
		$filter[] = 'nopayment';

		print 'Rebuild PDF for invoices with no payment done yet.'."\n";
	}

	if ($value == 'filter=bank') {
		$found = true;
		$option .= (empty($option) ? '' : '_').'bank_'.$argv[$key + 1];
		$filter[] = 'bank';

		$paymentonbankref = $argv[$key + 1];
		$bankaccount = new Account($db);
		$result = $bankaccount->fetch(0, $paymentonbankref);
		if ($result <= 0) {
			print 'Error: Bank account with ref "'.$paymentonbankref.'" not found'."\n";
			exit(-1);
		}
		$paymentonbankid = $bankaccount->id;
		print 'Rebuild PDF for invoices with at least one payment on financial account '.$bankaccount->ref."\n";
	}

	if ($value == 'filter=nodeposit') {
		$found = true;
		$option .= (empty($option) ? '' : '_').'nodeposit';
		$filter[] = 'nodeposit';

		print 'Exclude deposit invoices'."\n";
	}
	if ($value == 'filter=noreplacement') {
		$found = true;
		$option .= (empty($option) ? '' : '_').'noreplacement';
		$filter[] = 'noreplacement';

		print 'Exclude replacement invoices'."\n";
	}
	if ($value == 'filter=nocreditnote') {
		$found = true;
		$option .= (empty($option) ? '' : '_').'nocreditnote';
		$filter[] = 'nocreditnote';

		print 'Exclude credit note invoices'."\n";
	}

	if ($value == 'filter=excludethirdparties') {
		$found = true;
		$filter[] = 'excludethirdparties';

		$thirdpartiesid = explode(',', $argv[$key + 1]);
		print 'Exclude thirdparties with id in list ('.join(',', $thirdpartiesid).").\n";

		$option .= (empty($option) ? '' : '_').'excludethirdparties'.join('-', $thirdpartiesid);
	}
	if ($value == 'filter=onlythirdparties') {
		$found = true;
		$filter[] = 'onlythirdparties';

		$thirdpartiesid = explode(',', $argv[$key + 1]);
		print 'Only thirdparties with id in list ('.join(',', $thirdpartiesid).").\n";

		$option .= (empty($option) ? '' : '_').'onlythirdparty'.join('-', $thirdpartiesid);
	}

	if (!$found && preg_match('/filter=/i', $value)) {
		usage();
		exit(-1);
	}
}

// Check if an option and a filter has been provided
if (empty($option) && count($filter) <= 0) {
	usage();
	exit(-1);
}
// Check if there is no uncompatible choice
if (in_array('payments', $filter) && in_array('nopayment', $filter)) {
	usage();
	exit(-1);
}
if (in_array('bank', $filter) && in_array('nopayment', $filter)) {
	usage();
	exit(-1);
}

// Define SQL and SQL request to select invoices
// Use $filter, $dateafterdate, datebeforedate, $paymentdateafter, $paymentdatebefore
$result = rebuild_merge_pdf($db, $langs, $conf, $diroutputpdf, $newlangid, $filter, $dateafterdate, $datebeforedate, $paymentdateafter, $paymentdatebefore, 1, $regenerate, $option, $paymentonbankid, $thirdpartiesid, $fileprefix);

// -------------------- END OF YOUR CODE --------------------

if ($result >= 0) {
	$error = 0;
	print '--- end ok'."\n";
} else {
	$error = $result;
	print '--- end error code='.$error."\n";
}

$db->close();

exit($error);

/**
 * Show usage of script
 *
 * @return void
 */
function usage()
{
	global $script_file;

	print "Rebuild PDF files for some invoices and merge PDF files into one.\n";
	print "\n";
	print "To build/merge PDF for invoices in a date range:\n";
	print "Usage:   ".$script_file." filter=date dateafter datebefore\n";
	print "To build/merge PDF for invoices with at least one payment in a date range:\n";
	print "Usage:   ".$script_file." filter=payments dateafter datebefore\n";
	print "To build/merge PDF for invoices with at least one payment onto a bank account:\n";
	print "Usage:   ".$script_file." filter=bank bankref\n";
	print "To build/merge PDF for all invoices, use filter=all\n";
	print "Usage:   ".$script_file." filter=all\n";
	print "To build/merge PDF for invoices with no payments, use filter=nopayment\n";
	print "Usage:   ".$script_file." filter=nopayment\n";
	print "To exclude credit notes, use filter=nocreditnote\n";
	print "To exclude replacement invoices, use filter=noreplacement\n";
	print "To exclude deposit invoices, use filter=nodeposit\n";
	print "To exclude some thirdparties, use filter=excludethirdparties id1,id2...\n";
	print "To limit to some thirdparties, use filter=onlythirdparties id1,id2...\n";
	print "To regenerate existing PDF, use regenerate=templatename\n";
	print "To generate invoices in a language, use lang=xx_XX\n";
	print "To set prefix of generated file name, use prefix=myfileprefix\n";
	print "\n";
	print "Example: ".$script_file." filter=payments 20080101 20081231 lang=fr_FR regenerate=crabe\n";
	print "Example: ".$script_file." filter=all lang=en_US\n";
	print "\n";
	print "Note that some filters can be cumulated.\n";
}
