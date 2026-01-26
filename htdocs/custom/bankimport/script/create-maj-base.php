<?php
/*
 * Script to create and verify that required fields are added correctly
 */

if(!defined('INC_FROM_DOLIBARR')) {
	define('INC_FROM_CRON_SCRIPT', true);

	require('../config.php');

}


dol_include_once('/bankimport/class/bankimport.class.php');

$PDOdb=new TPDOdb;

$o=new TBankImportHistory;
$o->init_db_by_vars($PDOdb);
