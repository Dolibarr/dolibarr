#!/usr/bin/env php
<?php
/* Copyright (C) 2026 Frédéric France <frederic.france@free.fr>
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
 * \file       test/phpunit/init_data.php
 * \ingroup    tests
 * \brief      Seed, using Dolibarr's own code, the minimal data that the
 *             PHPUnit suite needs when it runs on a "from zero" install
 *             (empty database created by the install wizard, with no demo
 *             dump loaded).
 *
 *             It:
 *               1. enables the modules the suite requires (and leaves
 *                  debugbar / ldap / mailmanspip OFF - AllTests.php and
 *                  several tests abort otherwise);
 *               2. sets the configuration constants the suite asserts;
 *               3. creates the few real fixtures some tests hard-depend on
 *                  (product "PINKDRESS", a thirdparty, a contact, a project).
 *
 *             The script is idempotent: running it again is a no-op.
 *
 *             Usage:  php test/phpunit/init_data.php
 */

if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: this script must be run from the CLI, not PHP-CGI.\n";
	exit(1);
}

require_once __DIR__.'/../../htdocs/master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/orderline.class.php';

/**
 * @var Conf      $conf
 * @var DoliDB    $db
 * @var Translate $langs
 */

$langs->loadLangs(array('main', 'admin', 'errors', 'products', 'companies', 'projects'));

// Use the admin user #1 created by the install wizard (step5)
$user = new User($db);
if ($user->fetch(1) <= 0) {
	echo "FATAL: admin user #1 not found. Run the install wizard first.\n";
	exit(1);
}
$user->loadRights();

$error = 0;

/*
 * -------------------------------------------------------------------------
 * 1) Define the running company ($mysoc)
 * -------------------------------------------------------------------------
 * Several module activations (modBlockedLog) and many tests need a company
 * with a country set. The demo database uses a French company, so do the
 * same here.
 */
dolibarr_set_const($db, 'MAIN_INFO_SOCIETE_NOM', 'PHPUnit Test Company', 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, 'MAIN_INFO_SOCIETE_COUNTRY', '1:FR:France', 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, 'MAIN_INFO_SOCIETE_ADDRESS', '1 rue du Test', 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, 'MAIN_INFO_SOCIETE_ZIP', '75000', 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, 'MAIN_INFO_SOCIETE_TOWN', 'Test City', 'chaine', 0, '', $conf->entity);

$conf->setValues($db);
$mysoc = new Societe($db);
$mysoc->setMysoc($conf);
echo "INFO  running company = '".$mysoc->name."' country_code='".$mysoc->country_code."'\n";

/*
 * -------------------------------------------------------------------------
 * 2) Enable the modules required by the test suite
 * -------------------------------------------------------------------------
 * NOTE: keep modDebugBar, modLdap, modMailmanSpip and modGravatar OUT of
 * this list - AllTests.php, AdherentTest and UserTest abort if they are on.
 */
$modules = array(
	'modUser', 'modSociete', 'modProduct', 'modService', 'modStock', 'modProductBatch',
	'modCategorie', 'modPropale', 'modCommande', 'modFacture', 'modFournisseur',
	'modSupplierProposal', 'modContrat', 'modFicheinter', 'modExpedition', 'modReception',
	'modProjet', 'modAgenda', 'modResource', 'modBanque', 'modTax', 'modPrelevement',
	'modPaymentByBankTransfer', 'modComptabilite', 'modAccounting', 'modAdherent', 'modDon',
	'modHoliday', 'modExpenseReport', 'modSalaries', 'modLoan', 'modMargin', 'modMrp', 'modBom',
	'modWorkstation', 'modMailing', 'modNotification', 'modBookmark', 'modExport', 'modImport',
	'modCron', 'modFckeditor', 'modWorkflow', 'modApi', 'modWebServices', 'modWebsite',
	'modTicket', 'modKnowledgeManagement', 'modEventOrganization', 'modPartnership',
	'modEmailCollector', 'modBarcode', 'modIncoterm', 'modMultiCurrency', 'modSocialNetworks',
	'modPaypal', 'modStripe', 'modECM', 'modBlockedLog', 'modOpenSurvey', 'modRecruitment',
	'modHRM', 'modAsset',
);

foreach ($modules as $modName) {
	// 3rd arg = noconfverification: force init() to run even if the module
	// already looks enabled.
	$res = activateModule($modName, 1, 1);
	if (!empty($res['errors'])) {
		echo "WARN  activate ".$modName.": ".implode(' / ', $res['errors'])."\n";
	} else {
		echo "OK    activate ".$modName."\n";
	}
}

/*
 * -------------------------------------------------------------------------
 * 2b) Create every module table
 * -------------------------------------------------------------------------
 * step2.php only creates the core tables - it skips every
 * "llx_<table>-<module>.sql" file (dash in the name). Those are meant to be
 * created by each module's init()/_load_tables(), but that path proved
 * unreliable from a CLI bootstrap (llx_don, llx_website stayed missing), so
 * load them explicitly here with the same run_sql() step2 uses.
 */
$tablesdir = DOL_DOCUMENT_ROOT.'/install/mysql/tables/';
$modtablefiles = glob($tablesdir.'llx_*-*.sql');
sort($modtablefiles);
$nbok = 0;
$modtablefails = array();
// non-key files first, then the .key.sql files (constraints need the tables)
foreach (array(false, true) as $keypass) {
	foreach ($modtablefiles as $sqlfile) {
		if ((bool) preg_match('/\.key\.sql$/', $sqlfile) !== $keypass) {
			continue;
		}
		if (run_sql($sqlfile, 1, $conf->entity, 1, '', 'default') > 0) {
			$nbok++;
		} else {
			$modtablefails[] = basename($sqlfile);
		}
	}
}
echo "INFO  module table files loaded: ".$nbok." / ".count($modtablefiles)."\n";
if ($modtablefails) {
	echo "INFO  module table files with no/failed statement: ".implode(', ', $modtablefails)."\n";
}

// Reload conf so isModEnabled() sees the freshly enabled modules
$conf->setValues($db);
$mysoc->setMysoc($conf);

/*
 * -------------------------------------------------------------------------
 * 3) Configuration constants asserted by the test suite
 * -------------------------------------------------------------------------
 */
// Must stay empty (SocieteTest / AdherentTest die() otherwise)
foreach (array('MAIN_DISABLEPROFIDRULES', 'MAIN_FIRSTNAME_NAME_POSITION') as $c) {
	dolibarr_del_const($db, $c, -1);
}

// Test-friendly third party code numbering (SocieteTest requires exactly this)
dolibarr_set_const($db, 'SOCIETE_CODECLIENT_ADDON', 'mod_codeclient_monkey', 'chaine', 0, '', $conf->entity);
// Keep the company language on autodetect so $langs->defaultlang stays en_US
dolibarr_set_const($db, 'MAIN_LANG_DEFAULT', 'auto', 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, 'MAIN_DISABLE_ALL_MAILS', '1', 'chaine', 0, '', $conf->entity);
// Key shared by the WebservicesXxxTest SOAP clients (any non-empty value works,
// server side compares it to the "dolibarrkey" sent by the test)
dolibarr_set_const($db, 'WEBSERVICES_KEY', 'phpunit', 'chaine', 0, '', $conf->entity);
// Write the syslog to documents/dolibarr.log so a failing CI run is diagnosable
dolibarr_set_const($db, 'SYSLOG_FILE', 'DOL_DATA_ROOT/dolibarr.log', 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, 'SYSLOG_LEVEL', '7', 'chaine', 0, '', $conf->entity);
dolibarr_set_const($db, 'SYSLOG_HANDLERS', '["mod_syslog_file"]', 'chaine', 0, '', $conf->entity);

$conf->setValues($db);

/*
 * -------------------------------------------------------------------------
 * 4) Fixtures created through the business classes
 * -------------------------------------------------------------------------
 */

// Resolve the France country id once (used by the product / thirdparty)
$fr_country_id = 0;
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_country WHERE code = 'FR'";
$resql = $db->query($sql);
if ($resql && ($obj = $db->fetch_object($resql))) {
	$fr_country_id = (int) $obj->rowid;
}

/**
 * Return the rowid of the first row matching a WHERE clause, or 0.
 *
 * @param string $table  Table name without prefix
 * @param string $where   SQL WHERE content (already escaped)
 * @return int
 */
function seed_existing_id($table, $where)
{
	global $db;
	$resql = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX.$table." WHERE ".$where." LIMIT 1");
	if ($resql && ($obj = $db->fetch_object($resql))) {
		return (int) $obj->rowid;
	}
	return 0;
}

// 3.a) A thirdparty, so that tests referencing societe rowid 1 have a target
$socid = seed_existing_id('societe', "nom = 'PHPUNIT SEED COMPANY'");
if ($socid <= 0) {
	$soc = new Societe($db);
	$soc->name = 'PHPUNIT SEED COMPANY';
	$soc->client = 3;         // customer + prospect
	$soc->fournisseur = 1;    // and supplier
	$soc->code_client = -1;   // auto
	$soc->code_fournisseur = -1;
	$soc->country_id = $fr_country_id;
	$soc->tva_assuj = 1;
	if ($soc->create($user) > 0) {
		$socid = $soc->id;
		echo "OK    thirdparty created id=".$socid."\n";
	} else {
		echo "WARN  thirdparty: ".$soc->errorsToString()."\n";
		$error++;
	}
} else {
	echo "SKIP  thirdparty already present id=".$socid."\n";
}

// 3.b) Product "PINKDRESS" (PdfDocTest / CommandeFournisseurTest hard-require it)
if (seed_existing_id('product', "ref = 'PINKDRESS'") <= 0) {
	$product = new Product($db);
	$product->ref = 'PINKDRESS';
	$product->label = 'Label 1';
	$product->description = "This is a description with a \xc3\xa9 accent\n(Country of origin: France)";
	$product->type = Product::TYPE_PRODUCT;
	$product->status = 1;         // on sale
	$product->status_buy = 1;     // can be bought
	$product->price_base_type = 'HT';
	$product->price = 100;
	$product->tva_tx = 20;
	$product->country_id = $fr_country_id;
	if ($product->create($user) > 0) {
		echo "OK    product PINKDRESS created id=".$product->id."\n";
	} else {
		echo "WARN  product PINKDRESS: ".$product->errorsToString()."\n";
		$error++;
	}
} else {
	echo "SKIP  product PINKDRESS already present\n";
}

// 3.c) A contact attached to the seed thirdparty
if (seed_existing_id('socpeople', "email = 'phpunit-seed@example.com'") <= 0) {
	$contact = new Contact($db);
	$contact->lastname = 'SEEDCONTACT';
	$contact->firstname = 'Phpunit';
	$contact->email = 'phpunit-seed@example.com';
	$contact->socid = ($socid > 0 ? $socid : 0);
	$contact->country_id = $fr_country_id;
	$contact->statut = 1;
	if ($contact->create($user) > 0) {
		echo "OK    contact created id=".$contact->id."\n";
	} else {
		echo "WARN  contact: ".$contact->errorsToString()."\n";
		$error++;
	}
} else {
	echo "SKIP  contact already present\n";
}

// 3.d) A project
if (seed_existing_id('projet', "ref = 'PHPUNIT-SEED'") <= 0) {
	$project = new Project($db);
	$project->ref = 'PHPUNIT-SEED';
	$project->title = 'PHPUnit seed project';
	$project->socid = ($socid > 0 ? $socid : 0);
	$project->statut = 1;   // open
	$project->date_start = dol_now();
	if ($project->create($user) > 0) {
		echo "OK    project created id=".$project->id."\n";
	} else {
		echo "WARN  project: ".$project->errorsToString()."\n";
		$error++;
	}
} else {
	echo "SKIP  project already present\n";
}

// 3.e) A handful of extra products - FormTest::testSelectProduitsList asserts
//      the product select list returns exactly 5 rows.
for ($n = 1; $n <= 6; $n++) {
	$ref = 'PHPUNIT-PROD-'.$n;
	if (seed_existing_id('product', "ref = '".$db->escape($ref)."'") > 0) {
		continue;
	}
	$p = new Product($db);
	$p->ref = $ref;
	$p->label = 'PHPUnit product '.$n;
	$p->type = Product::TYPE_PRODUCT;
	$p->status = 1;
	$p->status_buy = 1;
	$p->price_base_type = 'HT';
	$p->price = 10 * $n;
	$p->tva_tx = 20;
	if ($p->create($user) > 0) {
		echo "OK    product ".$ref." created id=".$p->id."\n";
	} else {
		echo "WARN  product ".$ref.": ".$p->errorsToString()."\n";
	}
}

// 3.f) A customer order - WebservicesOrdersTest::testWSOrderGetOrder fetches
//      the order with id 1.
if ($socid > 0 && seed_existing_id('commande', "fk_soc = ".((int) $socid)) <= 0) {
	$order = new Commande($db);
	$order->socid = $socid;
	$order->date = dol_now();
	$order->date_commande = dol_now();
	$order->cond_reglement_id = 1;
	$order->mode_reglement_id = 0;
	$pinkid = seed_existing_id('product', "ref = 'PINKDRESS'");
	$line = new OrderLine($db);
	$line->fk_product = $pinkid;
	$line->qty = 2;
	$line->subprice = 100;
	$line->price = 100;
	$line->tva_tx = 20;
	$line->total_ht = 200;
	$line->total_tva = 40;
	$line->total_ttc = 240;
	$order->lines = array($line);
	if ($order->create($user) > 0) {
		$order->valid($user);
		echo "OK    customer order created id=".$order->id."\n";
	} else {
		echo "WARN  customer order: ".$order->errorsToString()."\n";
		$error++;
	}
} else {
	echo "SKIP  customer order already present\n";
}

// 3.g) French chart of accounts entry - AccountingAccountTest creates an
//      account with fk_pcg_version='PCG99-ABREGE', which has a FK to
//      llx_accounting_system(pcg_version). The fresh-install data file only
//      ships the "-NC" (New Caledonia) variant.
if (seed_existing_id('accounting_system', "pcg_version = 'PCG99-ABREGE'") <= 0) {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."accounting_system (fk_country, pcg_version, label, active) VALUES (";
	$sql .= ((int) $fr_country_id).", 'PCG99-ABREGE', 'Plan comptable general (abrege)', 1)";
	$r = $db->query($sql);
	echo ($r ? "OK    accounting_system PCG99-ABREGE created\n" : "WARN  accounting_system: ".$db->lasterror()."\n");
}

// 3.h) Two shipments so mod_expedition_safor::getNextValue() returns
//      SH8001-0003 (NumberingModulesTest::testShipmentSafor).
if ($socid > 0) {
	foreach (array('SH8001-0001', 'SH8001-0002') as $shref) {
		if (seed_existing_id('expedition', "ref = '".$db->escape($shref)."'") > 0) {
			continue;
		}
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expedition (ref, entity, fk_soc, date_creation, fk_user_author, fk_statut) VALUES (";
		$sql .= "'".$db->escape($shref)."', ".((int) $conf->entity).", ".((int) $socid).", ";
		$sql .= "'".$db->idate(dol_mktime(12, 0, 0, 1, 1, 1980))."', ".((int) $user->id).", 1)";
		$r = $db->query($sql);
		echo ($r ? "OK    expedition ".$shref." created\n" : "WARN  expedition ".$shref.": ".$db->lasterror()."\n");
	}
}

echo ($error ? "DONE with ".$error." warning(s)\n" : "DONE\n");

// Hard requirement: AllTests.php aborts the whole suite if the member module
// is not enabled. Fail here (non-zero exit) so CI stops with a clear message.
$conf->setValues($db);
if (!isModEnabled('member')) {
	fwrite(STDERR, "FATAL: the 'member' module is not enabled after seeding\n");
	exit(1);
}

// Other warnings are not fatal: the point is to prepare as much as possible.
exit(0);
