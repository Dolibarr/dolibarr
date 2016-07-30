#!/usr/bin/env php
<?php
/* Copyright (C) 2012 Laurent Destailleur	<eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 * 
 * Get a distant dump file and load it into a mysql database
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}

// Global variables
$error=0;

$confirm=isset($argv[1])?$argv[1]:'';

// Include Dolibarr environment
$res=0;
if (! $res && file_exists($path."../../master.inc.php")) $res=@include($path."../../master.inc.php");
if (! $res && file_exists($path."../../htdocs/master.inc.php")) $res=@include($path."../../htdocs/master.inc.php");
if (! $res && file_exists("../master.inc.php")) $res=@include("../master.inc.php");
if (! $res && file_exists("../../master.inc.php")) $res=@include("../../master.inc.php");
if (! $res && file_exists("../../../master.inc.php")) $res=@include("../../../master.inc.php");
if (! $res && preg_match('/\/nltechno([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include($path."../../../dolibarr".$reg[1]."/htdocs/master.inc.php"); // Used on dev env only
if (! $res && preg_match('/\/nltechno([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../dolibarr".$reg[1]."/htdocs/master.inc.php"); // Used on dev env only
if (! $res) die ("Failed to include master.inc.php file\n");
include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');


/*
 *	Main
 */

if (empty($confirm))
{
	print "Usage: $script_file confirm\n";
	print "Return code: 0 if success, <>0 if error\n";
	exit(-1);
}


$tmp=dol_getdate(dol_now());



$year=2010;
$currentyear=$tmp['year'];
while ($year < ($currentyear - 1))      // We want to keep 2 years of data 
{
    $delta=($currentyear - $year);
    
    print "Correct proposal for year ".$year." and move them to current year ".$currentyear."\n"; 
    $sql="select rowid from ".MAIN_DB_PREFIX."propal where datep between '".$year."-01-01' and '".$year."-12-31'";
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i=0;
        while ($i < $num)
        {
            $obj=$db->fetch_object($resql);
            if ($obj)
            {
                print ".";
            
                $sql2="UPDATE ".MAIN_DB_PREFIX."propal set ";
                $sql2.= "datep        = DATE_ADD(datep,        INTERVAL ".$delta." YEAR),"; 
                $sql2.= "fin_validite = DATE_ADD(fin_validite, INTERVAL ".$delta." YEAR),";
                $sql2.= "date_valid   = DATE_ADD(date_valid,   INTERVAL ".$delta." YEAR),";
                $sql2.= "date_cloture = DATE_ADD(date_cloture, INTERVAL ".$delta." YEAR)";
                $sql2.=" WHERE rowid = ".$obj->rowid;
                //print $sql2."\n";
                
                $resql2 = $db->query($sql2);
                if (! $resql2) dol_print_error($db);
            }            
            $i++;
        }
    }
    else dol_print_error($db);

    print "Correct order for year ".$year." and move them to current year ".$currentyear."\n";
    $sql="select rowid from ".MAIN_DB_PREFIX."commande where date_commande between '".$year."-01-01' and '".$year."-12-31'";
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i=0;
        while ($i < $num)
        {
            $obj=$db->fetch_object($resql);
            if ($obj)
            {
                print ".";
    
                $sql2="UPDATE ".MAIN_DB_PREFIX."commande set ";
                $sql2.= "date_commande = DATE_ADD(date_commande,        INTERVAL ".$delta." YEAR),";
                $sql2.= "date_valid    = DATE_ADD(date_valid,   INTERVAL ".$delta." YEAR),";
                $sql2.= "date_cloture  = DATE_ADD(date_cloture, INTERVAL ".$delta." YEAR)";
                $sql2.=" WHERE rowid = ".$obj->rowid;
                //print $sql2."\n";
    
                $resql2 = $db->query($sql2);
                if (! $resql2) dol_print_error($db);
            }
            $i++;
        }
    }
    else dol_print_error($db);
    
    print "Correct invoice for year ".$year." and move them to current year ".$currentyear."\n";
    $sql="select rowid from ".MAIN_DB_PREFIX."facture where datef between '".$year."-01-01' and '".$year."-12-31'";
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i=0;
        while ($i < $num)
        {
            $obj=$db->fetch_object($resql);
            if ($obj)
            {
                print ".";
    
                $sql2="UPDATE ".MAIN_DB_PREFIX."facture set ";
                $sql2.= "datef        = DATE_ADD(datef,        INTERVAL ".$delta." YEAR),";
                $sql2.= "date_valid   = DATE_ADD(date_valid,   INTERVAL ".$delta." YEAR),";
                $sql2.= "date_lim_reglement = DATE_ADD(date_lim_reglement,   INTERVAL ".$delta." YEAR)";
                $sql2.=" WHERE rowid = ".$obj->rowid;
                //print $sql2."\n";
    
                $resql2 = $db->query($sql2);
                if (! $resql2) dol_print_error($db);
            }
            $i++;
        }
    }
    else dol_print_error($db);
    
    $year++;
}

print "\n";

exit(0);
