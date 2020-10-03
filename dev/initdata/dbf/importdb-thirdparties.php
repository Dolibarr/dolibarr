#!/usr/bin/env php
<?php
/* Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016 Juanjo Menent        <jmenent@2byte.es>
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
 *
 * WARNING, THIS WILL LOAD MASS DATA ON YOUR INSTANCE
 */

/**
 *      \file       dev/initdata/import-product.php
 * 		\brief      Script example to insert products from a csv file.
 *                  To purge data, you can have a look at purge-data.php
 */
// Test si mode batch
$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Recupere root dolibarr
$path = preg_replace('/importdb-thirdparties.php/i', '', $_SERVER["PHP_SELF"]);
require $path . "../../htdocs/master.inc.php";
require $path . "includes/dbase.class.php";
include_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

//$delimiter = ',';
//$enclosure = '"';
//$linelength = 10000;
//$escape = '/';
// Global variables
$version = DOL_VERSION;
$confirmed = 1;
$error = 0;

$civilPrivate = array("MLLE",
    "MM",
    "MM/MADAME",
    "MME",
    "MME.",
    "MME²",
    "MMONSIEUR",
    "MMR",
    "MOBNSIEUR",
    "MOMSIEUR",
    "MON SIEUR",
    "MONDIAL",
    "MONIEUR",
    "MONJSIEUR",
    "MONNSIEUR",
    "MONRIEUR",
    "MONS",
    "MONSIEÕR",
    "MONSIER",
    "MONSIERU",
    "MONSIEU",
    "monsieue",
    "MONSIEUR",
    "Monsieur     \"",
    "MONSIEUR    \"",
    "MONSIEUR   E",
    "MONSIEUR  DENIS",
    "MONSIEUR ET MME",
    "MONSIEUR!",
    "MONSIEUR.",
    "MONSIEUR.MADAME",
    "MONSIEUR3",
    "MONSIEURN",
    "MONSIEURT",
    "MONSIEUR£",
    "MONSIEYR",
    "Monsigur",
    "MONSIIEUR",
    "MONSIUER",
    "MONSIZEUR",
    "MOPNSIEUR",
    "MOSIEUR",
    "MR",
    "Mr  Mme",
    "Mr - MME",
    "MR BLANC",
    "MR ET MME",
    "mr mm",
    "MR OU MME",
    "Mr.",
    "MR/MME",
    "MRME",
    "MRR",
    "Mrs",
    "Mademoiselle",
    "MADAOME",
    "madamme",
    "MADAME",
    "M0NSIEUR",
    "M.et Madame",
    "M. ET MR",
    "M.",
    "M%",
    "M MME",
    "M ET MME",
    "M",
    "M CROCE",
    "M DIEVART",
);

/*
 * Main
 */

@set_time_limit(0);
print "***** " . $script_file . " (" . $version . ") pid=" . dol_getmypid() . " *****\n";
dol_syslog($script_file . " launched with arg " . implode(',', $argv));

$table = $argv[1];

if (empty($argv[1])) {
    print "Error: Quelle table ?\n";
    print "\n";
    exit(-1);
}

$ret = $user->fetch('', 'admin');
if (!$ret > 0) {
    print 'A user with login "admin" and all permissions must be created to use this script.' . "\n";
    exit;
}

$sql = "SELECT * FROM `$table` WHERE 1 "; //ORDER BY REMISE DESC,`LCIVIL` DESC";
$resql = $db->query($sql);
//$db->begin();
if ($resql)
while ($fields = $db->fetch_array($resql)) {
	$i++;
	$errorrecord = 0;

	if ($startlinenb && $i < $startlinenb)
		continue;
	if ($endlinenb && $i > $endlinenb)
		continue;

	$nboflines++;

	$object = new Societe($db);
	$object->import_key = $fields['CODE'];
	$object->state = 1;
	$object->client = 3;
	$object->fournisseur = 0;

	$object->name = $fields['FCIVIL'] . ' ' . $fields['FNOM'];
	//$object->name_alias = $fields[0] != $fields[13] ? trim($fields[0]) : '';

	$date = $fields['DATCREA'] ? $fields['DATCREA'] : ($fields['DATMOD'] ? $fields['DATMOD'] : '20200101');
	$object->code_client = 'CU' . substr($date, 2, 2) . substr($date, 4, 2) . '-' . str_pad(substr($fields['CODE'], 0, 5), 5, "0", STR_PAD_LEFT);


	$object->address = trim($fields['FADR1']);
	if ($fields['FADR2'])
		$object->address .= "\n" . trim($fields['FADR2']);
	if ($fields['FADR3'])
		$object->address .= "\n" . trim($fields['FADR3']);

	$object->zip = trim($fields['FPOSTE']);
	$object->town = trim($fields['FVILLE']);
	if ($fields['FPAYS'])
		$object->country_id = dol_getIdFromCode($db, trim(ucwords(strtolower($fields['FPAYS']))), 'c_country', 'label', 'rowid');
	else $object->country_id = 1;
	$object->phone = trim($fields['FTEL']) ? trim($fields['FTEL']) : trim($fields['FCONTACT']);
	$object->phone = substr($object->phone, 0, 20);
	$object->fax = trim($fields['FFAX']) ? trim($fields['FFAX']) : trim($fields['FCONTACT']);
	$object->fax = substr($object->fax, 0, 20);
	$object->email = trim($fields['FMAIL']);
	//        $object->idprof2 = trim($fields[29]);
	$object->tva_intra = str_replace(['.', ' '], '', $fields['TVAINTRA']);
	$object->tva_intra = substr($object->tva_intra, 0, 20);
	$object->default_lang = 'fr_FR';

	$object->cond_reglement_id = dol_getIdFromCode($db, 'PT_ORDER', 'c_payment_term', 'code', 'rowid', 1);
	$object->multicurrency_code = 'EUR';

	if ($fields['REMISE'] != '0.00') {
		$object->remise_percent = abs($fields['REMISE']);
	}

	//        $object->code_client = $fields[9];
	//        $object->code_fournisseur = $fields[10];


	if ($fields['FCIVIL']) {
		$labeltype = in_array($fields['FCIVIL'], $civilPrivate) ? 'TE_PRIVATE' : 'TE_SMALL';
		$object->typent_id = dol_getIdFromCode($db, $labeltype, 'c_typent', 'code');
	}

	// Set price level
	$object->price_level = $fields['TARIF'] + 1;
	//        if ($labeltype == 'Revendeur')
	//            $object->price_level = 2;

	print "Process line nb " . $i . ", code " . $fields['CODE'] . ", name " . $object->name;


	// Extrafields
	$object->array_options['options_banque'] = $fields['BANQUE'];
	$object->array_options['options_banque2'] = $fields['BANQUE2'];
	$object->array_options['options_banquevalid'] = $fields['VALID'];

	if (!$errorrecord) {
		$ret = $object->create($user);
		if ($ret < 0) {
			print " - Error in create result code = " . $ret . " - " . $object->errorsToString();
			$errorrecord++;
			var_dump($object->code_client, $db);
			die();
		} else {
			print " - Creation OK with name " . $object->name . " - id = " . $ret;
		}
	}

	if (!$errorrecord) {
		dol_syslog("Set price level");
		$object->set_price_level($object->price_level, $user);
	}
	if (!$errorrecord && @$object->remise_percent) {
		dol_syslog("Set remise client");
		$object->set_remise_client($object->remise_percent, 'Importé', $user);
	}

	dol_syslog("Add contact");
	// Insert an invoice contact if there is an invoice email != standard email
	if (!$errorrecord && ($fields['LCIVIL'] || $fields['LNOM'])) {
		$madame = array("MADAME",
			"MADEMOISELLE",
			"MELLE",
			"MLLE",
			"MM",
			"Mme",
			"MNE",
		);
		$monsieur = array("M",
			"M ET MME",
			"M MME",
			"M.",
			"M. MME",
			"M. OU Mme",
			"M.ou Madame",
			"MONSEUR",
			"MONSIER",
			"MONSIEU",
			"MONSIEUR",
			"monsieur:mme",
			"MONSIEUR¨",
			"MONSIEZUR",
			"MONSIUER",
			"MONSKIEUR",
			"MR",
		);
		$ret1 = $ret2 = 0;

		$contact = new Contact($db);
		if (in_array($fields['LCIVIL'], $madame)) {
			// une dame
			$contact->civility_id = 'MME';
			$contact->lastname = $fields['LNOM'];
		} elseif (in_array($fields['LCIVIL'], $monsieur)) {
			// un monsieur
			$contact->civility_id = 'MR';
			$contact->lastname = $fields['LNOM'];
		} elseif (in_array($fields['LCIVIL'], ['DOCTEUR'])) {
			// un monsieur
			$contact->civility_id = 'DR';
			$contact->lastname = $fields['LNOM'];
		} else {
			// un a rattraper
			$contact->lastname = $fields['LCIVIL'] . " " . $fields['LNOM'];
		}
		$contact->address = trim($fields['LADR1']);
		if ($fields['LADR2'])
			$contact->address .= "\n" . trim($fields['LADR2']);
		if ($fields['LADR3'])
			$contact->address .= "\n" . trim($fields['LADR3']);

		$contact->zip = trim($fields['LPOSTE']);
		$contact->town = trim($fields['LVILLE']);
		if ($fields['FPAYS'])
			$contact->country_id = dol_getIdFromCode($db, trim(ucwords(strtolower($fields['LPAYS']))), 'c_country', 'label', 'rowid');
		else $contact->country_id = 1;
		$contact->email = $fields['LMAIL'];
		$contact->phone = trim($fields['LTEL']) ? trim($fields['LTEL']) : trim($fields['LCONTACT']);
		$contact->fax = trim($fields['LFAX']) ? trim($fields['LFAX']) : trim($fields['LCONTACT']);
		$contact->socid = $object->id;

		$ret1 = $contact->create($user);
		if ($ret1 > 0) {
			//$ret2=$contact->add_contact($object->id, 'BILLING');
		}
		if ($ret1 < 0 || $ret2 < 0) {
			print " - Error in create contact result code = " . $ret1 . " " . $ret2 . " - " . $contact->errorsToString();
			$errorrecord++;
		} else {
			print " - create contact OK";
		}
	}


	//update date créa
	if (!$errorrecord) {
		$datec = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
		$retd = $db->query("UPDATE `llx_societe` SET `datec` = '$datec 00:00:00' WHERE `rowid` = $object->id");
		if ($retd < 1) {
			print " - Error in update date créa result " . $object->errorsToString();
			$errorrecord++;
		} else {
			print " - update date créa OK";
		}
	}
	print "\n";

	if ($errorrecord) {
		print( 'Error on record nb ' . $i . " - " . $object->errorsToString() . "\n");
		var_dump($db, $object, $contact);
		//            $db->rollback();
		die();
		$error++;    // $errorrecord will be reset
	}
	$j++;
} else die("error : $sql");

$db->commit();



// commit or rollback
print "Nb of lines qualified: " . $nboflines . "\n";
print "Nb of errors: " . $error . "\n";
$db->close();

exit($error);
