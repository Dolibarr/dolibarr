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
 *      \file       dev/initdata/import-thirdparties.php
 *		\brief      Script example to insert thirdparties from a csv file.
 *                  To purge data, you can have a look at purge-data.php
 */

// Test si mode batch
$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Recupere root dolibarr
$path=preg_replace('/import-thirdparties.php/i', '', $_SERVER["PHP_SELF"]);
require $path."../../htdocs/master.inc.php";
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$delimiter=',';
$enclosure='"';
$linelength=10000;
$escape='/';

// Global variables
$version=DOL_VERSION;
$confirmed=1;
$error=0;


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".implode(',', $argv));

$mode = $argv[1];
$filepath = $argv[2];
$filepatherr = $filepath.'.err';
//$defaultlang = empty($argv[3])?'en_US':$argv[3];
$startlinenb = empty($argv[3])?1:$argv[3];
$endlinenb = empty($argv[4])?0:$argv[4];

if (empty($mode) || ! in_array($mode, array('test','confirm','confirmforced')) || empty($filepath)) {
    print "Usage:  $script_file (test|confirm|confirmforced) filepath.csv [startlinenb] [endlinenb]\n";
    print "Usage:  $script_file test myfilepath.csv 2 1002\n";
    print "\n";
    exit(-1);
}
if (! file_exists($filepath)) {
    print "Error: File ".$filepath." not found.\n";
    print "\n";
    exit(-1);
}

$ret=$user->fetch('', 'admin');
if (! $ret > 0)
{
	print 'A user with login "admin" and all permissions must be created to use this script.'."\n";
	exit;
}
$user->getrights();

// Ask confirmation
if (! $confirmed)
{
    print "Hit Enter to continue or CTRL+C to stop...\n";
    $input = trim(fgets(STDIN));
}

// Open input and output files
$fhandle = fopen($filepath, 'r');
if (! $fhandle)
{
    print 'Error: Failed to open file '.$filepath."\n";
    exit(1);
}
$fhandleerr = fopen($filepatherr, 'w');
if (! $fhandleerr)
{
    print 'Error: Failed to open file '.$filepatherr."\n";
    exit(1);
}

//$langs->setDefaultLang($defaultlang);


$db->begin();

$i=0;
$nboflines=0;
while ($fields=fgetcsv($fhandle, $linelength, $delimiter, $enclosure, $escape))
{
    $i++;
    $errorrecord=0;

    if ($startlinenb && $i < $startlinenb) continue;
    if ($endlinenb && $i > $endlinenb) continue;

    $nboflines++;

    $object = new Societe($db);
    $object->state = $fields[6];
    $object->client = $fields[7];
    $object->fournisseur = $fields[8];

    $object->name = $fields[13]?trim($fields[13]):$fields[0];
    $object->name_alias = $fields[0]!=$fields[13]?trim($fields[0]):'';

    $object->address = trim($fields[14]);
    $object->zip = trim($fields[15]);
    $object->town = trim($fields[16]);
    $object->country_id = dol_getIdFromCode($db, trim($fields[21]), 'c_country', 'code', 'rowid');
    $object->phone = trim($fields[22]);
    $object->fax = trim($fields[23]);
    $object->email = trim($fields[26]);
    $object->idprof2 = trim($fields[29]);
    $object->tva_intra = trim($fields[34]);
    $object->default_lang = trim($fields[43]);

    //$condpayment = dol_string_unaccent(trim($fields[36]));
    if ($fields[36])
    {
        $condpayment = trim($fields[36]);
        if ($condpayment == 'A la commande') $condpayment = 'A réception de commande';
        if ($condpayment == 'A reception facture') $condpayment = 'Réception de facture';
        $object->cond_reglement_id = dol_getIdFromCode($db, $condpayment, 'c_payment_term', 'libelle_facture', 'rowid', 1);
        if (empty($object->cond_reglement_id))
        {
            print " - Error cant find payment mode for ".$condpayment."\n";
            $errorrecord++;
        }
    }

    $object->code_client = $fields[9];
    $object->code_fournisseur = $fields[10];

    $labeltype = trim($fields[1]);
    $object->typent_id = dol_getIdFromCode($db, $labeltype, 'c_typent', 'libelle');

    // Set price level
    $object->price_level = 1;
    if ($labeltype == 'Revendeur') $object->price_level = 2;

    print "Process line nb ".$i.", name ".$object->name;


    // Extrafields
    $object->array_options['options_anastate']=price2num($fields[20]);
    $object->array_options['options_anaregion']=price2num($fields[17]);

    if (! $errorrecord)
    {
        $ret=$object->create($user);
        if ($ret < 0)
        {
            print " - Error in create result code = ".$ret." - ".$object->errorsToString();
            $errorrecord++;
        } else {
    	    print " - Creation OK with name ".$object->name." - id = ".$ret;
    	}
    }

    if (! $errorrecord)
    {
        dol_syslog("Set price level");
	    $object->set_price_level($object->price_level, $user);
    }

	// Assign sales representative
	if (! $errorrecord && $fields[3])
	{
    	$salesrep=new User($db);

    	$tmp=explode(' ', $fields[3], 2);
    	$salesrep->firstname = trim($tmp[0]);
    	$salesrep->lastname = trim($tmp[1]);
    	if ($salesrep->lastname) $salesrep->login = strtolower(substr($salesrep->firstname, 0, 1)) . strtolower(substr($salesrep->lastname, 0));
    	else $salesrep->login=strtolower($salesrep->firstname);
    	$salesrep->login=preg_replace('/ /', '', $salesrep->login);
    	$salesrep->fetch(0, $salesrep->login);

    	$result = $object->add_commercial($user, $salesrep->id);
    	if ($result < 0)
    	{
    	    print " - Error in create link with sale representative result code = ".$result." - ".$object->errorsToString();
    	    $errorrecord++;
    	} else {
    	    print " - create link sale representative OK";
    	}
	}

	dol_syslog("Add invoice contacts");
	// Insert an invoice contact if there is an invoice email != standard email
	if (! $errorrecord && $fields[27] && $fields[26] != $fields[27])
	{
	    $ret1=$ret2=0;

	    $contact = new Contact($db);
	    $contact->lastname = $object->name;
	    $contact->address=$object->address;
	    $contact->zip=$object->zip;
	    $contact->town=$object->town;
	    $contact->country_id=$object->country_id;
	    $contact->email=$fields[27];
	    $contact->socid=$object->id;

	    $ret1=$contact->create($user);
	    if ($ret1 > 0)
	    {
	        //$ret2=$contact->add_contact($object->id, 'BILLING');
	    }
	    if ($ret1 < 0 || $ret2 < 0)
        {
            print " - Error in create contact result code = ".$ret1." ".$ret2." - ".$object->errorsToString();
            $errorrecord++;
        } else {
    	    print " - create contact OK";
    	}
	}

	dol_syslog("Add delivery contacts");
	// Insert a delivery contact
	if (! $errorrecord && $fields[47])
	{
	    $ret1=$ret2=0;

	    $contact2 = new Contact($db);
	    $contact2->lastname = 'Service livraison - '.$fields[47];
	    $contact2->address = $fields[48];
	    $contact2->zip = $fields[50];
	    $contact2->town = $fields[51];
	    $contact2->country_id=dol_getIdFromCode($db, trim($fields[52]), 'c_country', 'code', 'rowid');
	    $contact2->note_public=$fields[54];
	    $contact2->socid=$object->id;

	    // Extrafields
	    $contact2->array_options['options_anazoneliv']=price2num($fields[53]);

	    $ret1=$contact2->create($user);
	    if ($ret1 > 0)
	    {
	        //$ret2=$contact2->add_contact($object->id, 'SHIPPING');
	    }
	    if ($ret1 < 0 || $ret2 < 0)
        {
            print " - Error in create contact result code = ".$ret1." ".$ret2." - ".$object->errorsToString();
            $errorrecord++;
        } else {
    	    print " - create contact OK";
    	}
	}


	print "\n";

	if ($errorrecord)
	{
	    fwrite($fhandleerr, 'Error on record nb '.$i." - ".$object->errorsToString()."\n");
	    $error++;    // $errorrecord will be reset
	}
}





// commit or rollback
print "Nb of lines qualified: ".$nboflines."\n";
print "Nb of errors: ".$error."\n";
if ($mode != 'confirmforced' && ($error || $mode != 'confirm'))
{
    print "Rollback any changes.\n";
    $db->rollback();
} else {
    print "Commit all changes.\n";
    $db->commit();
}

$db->close();
fclose($fhandle);
fclose($fhandleerr);

exit($error);
