#!/usr/bin/env php
<?php
/* Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

// Recupere root dolibarr
$path=preg_replace('/import-thirdparties.php/i','',$_SERVER["PHP_SELF"]);
require ($path."../../htdocs/master.inc.php");
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
dol_syslog($script_file." launched with arg ".join(',',$argv));

$mode = $argv[1];
$filepath = $argv[2];
$filepatherr = $filepath.'.err';
//$defaultlang = empty($argv[3])?'en_US':$argv[3];
$startlinenb = empty($argv[3])?1:$argv[3];
$endlinenb = empty($argv[4])?0:$argv[4];

if (empty($mode) || ! in_array($mode,array('test','confirm','confirmforced')) || empty($filepath)) {
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

$ret=$user->fetch('','admin');
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

// Open input and ouput files
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
while ($fields=fgetcsv($fhandle, $linelength, $delimiter, $enclosure, $escape))
{
    $i++;
    $errorrecord=0;

    if ($startlinenb && $i < $startlinenb) continue;
    if ($endlinenb && $i > $endlinenb) continue;

    $object = new Societe($db);
    $object->state = $fields[6];
    $object->client = $fields[7];
    $object->fournisseur = $fields[8];
    
    $object->name = trim($fields[13]);
    $object->name_alias = trim($fields[0]);
    
    $object->address = trim($fields[14]);
    $object->zip = trim($fields[15]);
    $object->town = trim($fields[16]);
    $object->country_code = trim($fields[22]);
    $object->phone = trim($fields[23]);
    $object->fax = trim($fields[24]);
    $object->email = trim($fields[26]);
    $object->siret = trim($fields[29]);
    $object->tva_intra = trim($fields[34]);
    $object->default_lang = trim($fields[43]);
    
    $condpayment = trim($fields[36]);
    $object->cond_reglement_id = dol_getIdFromCode($db, $condpayment, 'c_paiement_term', 'label');
    
    $object->code_client = $fields[9];
    $object->code_fournisseur = $fields[10];

    $labeltype = trim($fields[1]);
    $object->typent_id = dol_getIdFromCode($db, $labeltype, 'c_typent', 'label');

    print "Process line nb ".$i.", name ".$object->name;


    // Extrafields
    //$object->array_options['options_state']=price2num($fields[20]);
    //$object->array_options['options_region']=price2num($fields[18]);
    
    $ret=$object->create($user);
    if ($ret < 0)
    {
        print " - Error in create result code = ".$ret." - ".$object->errorsToString();
        $errorrecord++;
    }
	else 
	{
	    print " - Creation OK with name ".$object->name." - id = ".$ret;
	}

	dol_syslog("Add contacts");
	
    // Insert an invoice contact if there is an invoice email != standard email
	if (! $errorrecord && $fields[27] && $fields[26] != $fields[27])
	{
	    $contact = new Contact($db);
	    $contact->firstname = '';
	    $contact->lastname = '';
	     
	    if ($ret1 < 0 || $ret2 < 0)
        {
            print " - Error in create contact result code = ".$ret1." ".$ret2." - ".$object->errorsToString();
            $errorrecord++;
        }
    	else 
    	{
    	    print " - create contact OK";
    	}
	}
	
	// Insert a delivery contact
	if (! $errorrecord && 1)
	{
	    $contact = new Contact($db);
	    $contact->firstname = '';
	    $contact->lastname = '';
	     
	    if ($ret1 < 0 || $ret2 < 0)
        {
            print " - Error in create contact result code = ".$ret1." ".$ret2." - ".$object->errorsToString();
            $errorrecord++;
        }
    	else 
    	{
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
if ($mode != 'confirmforced' && ($error || $mode != 'confirm'))
{
    print "Rollback any changes.\n";
    $db->rollback();
}
else
{
    print "Commit all changes.\n";
    $db->commit();
}

$db->close();
fclose($fhandle);
fclose($fhandleerr);

exit($error);
