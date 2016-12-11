#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       scripts/withdrawals/build_withdrawal_file.php
 *      \ingroup    prelevement
 *      \brief      Script de prelevement
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/bonprelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");

// Global variables
$version=DOL_VERSION;
$error=0;


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',',$argv));

$datetimeprev = dol_now();

$month = strftime("%m", $datetimeprev);
$year = strftime("%Y", $datetimeprev);

$user = new user($db);
$user->fetch($conf->global->PRELEVEMENT_USER);

if (! isset($argv[1])) {	// Check parameters
    print "This script check invoices with a withdrawal request and\n";
    print "then create payment and build a withdraw file.\n";
	print "Usage: ".$script_file." simu|real\n";
    exit(-1);
}


$withdrawreceipt=new BonPrelevement($db);
// $conf->global->PRELEVEMENT_CODE_BANQUE and $conf->global->PRELEVEMENT_CODE_GUICHET should be empty
$result=$withdrawreceipt->create($conf->global->PRELEVEMENT_CODE_BANQUE,$conf->global->PRELEVEMENT_CODE_GUICHET,$argv[1]);


$db->close();

exit(0);
