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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * WARNING, THIS WILL LOAD MASS DATA ON YOUR INSTANCE
 */

/**
 *      \file       dev/initdata/import-product.php
 *		\brief      Script example to insert products from a csv file.
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
$path=preg_replace('/import-products.php/i', '', $_SERVER["PHP_SELF"]);
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
$defaultlang = empty($argv[3])?'en_US':$argv[3];
$startlinenb = empty($argv[4])?1:$argv[4];
$endlinenb = empty($argv[5])?0:$argv[5];

if (empty($mode) || ! in_array($mode, array('test','confirm','confirmforced')) || empty($filepath)) {
    print "Usage:  $script_file (test|confirm|confirmforced) filepath.csv [defaultlang] [startlinenb] [endlinenb]\n";
    print "Usage:  $script_file test myfilepath.csv fr_FR 2 1002\n";
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

$langs->setDefaultLang($defaultlang);


$db->begin();

$i=0;
$nboflines++;
while ($fields=fgetcsv($fhandle, $linelength, $delimiter, $enclosure, $escape))
{
    $i++;
    $errorrecord=0;

    if ($startlinenb && $i < $startlinenb) continue;
    if ($endlinenb && $i > $endlinenb) continue;

    $nboflines++;

    $produit = new Product($db);
    $produit->type = 0;
    $produit->status = 1;
    $produit->ref = trim($fields[0]);

    print "Process line nb ".$i.", ref ".$produit->ref;
    $produit->label = trim($fields[2]);
    $produit->description = trim($fields[4]."\n".($fields[5] ? $fields[5].' x '.$fields[6].' x '.$fields[7] : ''));
    $produit->volume = price2num($fields[8]);
    $produit->volume_unit = 0;
    $produit->weight = price2num($fields[9]);
    $produit->weight_units = 0;          // -3 = g

    $produit->customcode = $fields[10];
    $produit->barcode = $fields[1];

    $produit->status = 1;
    $produit->status_buy = 1;

    $produit->finished = 1;

    $produit->price_min = null;
    $produit->price_min_ttc = null;
    $produit->price = price2num($fields[11]);
    $produit->price_ttc = price2num($fields[12]);
    $produit->price_base_type = 'TTC';
    $produit->tva_tx = price2num($fields[13]);
    $produit->tva_npr = 0;

    $produit->cost_price = price2num($fields[16]);

    // Extrafields
    $produit->array_options['options_ecotaxdeee']=price2num($fields[17]);

    $ret=$produit->create($user);
    if ($ret < 0)
    {
        print " - Error in create result code = ".$ret." - ".$produit->errorsToString();
        $errorrecord++;
    }
	else
	{
	    print " - Creation OK with ref ".$produit->ref." - id = ".$ret;
	}

	dol_syslog("Add prices");

    // If we use price level, insert price for each level
	if (! $errorrecord && 1)
	{
	    $ret1=$produit->updatePrice($produit->price_ttc, $produit->price_base_type, $user, $produit->tva_tx, $produit->price_min, 1, $produit->tva_npr, 0, 0, array());
	    $ret2=$produit->updatePrice(price2num($fields[14]), 'HT', $user, $produit->tva_tx, $produit->price_min, 2, $produit->tva_npr, 0, 0, array());
	    if ($ret1 < 0 || $ret2 < 0)
        {
            print " - Error in updatePrice result code = ".$ret1." ".$ret2." - ".$produit->errorsToString();
            $errorrecord++;
        }
    	else
    	{
    	    print " - updatePrice OK";
    	}
	}

	dol_syslog("Add multilangs");

	// Add alternative languages
	if (! $errorrecord && 1)
	{
    	$produit->multilangs['fr_FR']=array('label'=>$produit->label, 'description'=>$produit->description, 'note'=>$produit->note_private);
	    $produit->multilangs['en_US']=array('label'=>$fields[3], 'description'=>$produit->description, 'note'=>$produit->note_private);

    	$ret=$produit->setMultiLangs($user);
        if ($ret < 0)
        {
            print " - Error in setMultiLangs result code = ".$ret." - ".$produit->errorsToString();
            $errorrecord++;
        }
    	else
    	{
    	    print " - setMultiLangs OK";
    	}
	}

	print "\n";

	if ($errorrecord)
	{
	    fwrite($fhandleerr, 'Error on record nb '.$i." - ".$produit->errorsToString()."\n");
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
