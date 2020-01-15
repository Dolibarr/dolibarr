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
 *  \file       dev/initdata/import-dbf.php
 * 	\brief      Script example to create a table from a large DBF file (openoffice)
 *              To purge data, you can have a look at purge-data.php
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
$path = dirname($_SERVER["PHP_SELF"]);
require $path . "./../htdocs/master.inc.php";
require $path . "/includes/dbase.class.php";

// Global variables
$version = DOL_VERSION;
$confirmed = 1;
$error = 0;


/*
 * Main
 */

@set_time_limit(0);
print "***** " . $script_file . " (" . $version . ") pid=" . dol_getmypid() . " *****\n";
dol_syslog($script_file . " launched with arg " . implode(',', $argv));


$filepath = $argv[1];
$filepatherr = $filepath . '.err';
$startchar = empty($argv[2]) ? 0 : (int) $argv[2];
$deleteTable = empty($argv[3]) ? 1 : 0;
$startlinenb = empty($argv[3]) ? 1 : (int) $argv[3];
$endlinenb = empty($argv[4]) ? 0 : (int) $argv[4];

if (empty($filepath)) {
    print "Usage: php $script_file myfilepath.dbf [removeChatColumnName] [startlinenb] [endlinenb]\n";
    print "Example: php $script_file myfilepath.dbf 0 2 1002\n";
    print "\n";
    exit(-1);
}
if (!file_exists($filepath)) {
    print "Error: File " . $filepath . " not found.\n";
    print "\n";
    exit(-1);
}

$ret = $user->fetch('', 'admin');
if (!$ret > 0) {
    print 'A user with login "admin" and all permissions must be created to use this script.' . "\n";
    exit;
}
$user->getrights();

// Ask confirmation
if (!$confirmed) {
    print "Hit Enter to continue or CTRL+C to stop...\n";
    $input = trim(fgets(STDIN));
}

// Open input and output files
$fhandle = dbase_open($filepath, 0);
if (!$fhandle) {
    print 'Error: Failed to open file ' . $filepath . "\n";
    exit(1);
}
$fhandleerr = fopen($filepatherr, 'w');
if (!$fhandleerr) {
    print 'Error: Failed to open file ' . $filepatherr . "\n";
    exit(1);
}

$langs->setDefaultLang($defaultlang);

$record_numbers = dbase_numrecords($fhandle);
$table_name = substr(basename($filepath), 0, strpos(basename($filepath), '.'));
print 'Info: ' . $record_numbers . " lines in file \n";
$header = dbase_get_header_info($fhandle);
if ($deleteTable) {
    $db->query("DROP TABLE IF EXISTS `$table_name`");
}
$sqlCreate = "CREATE TABLE IF NOT EXISTS `$table_name` ( `id` INT(11) NOT NULL AUTO_INCREMENT ";
$fieldArray = array("`id`");
foreach ($header as $value) {
    $fieldName = substr(str_replace('_', '', $value['name']), $startchar);
    $fieldArray[] = "`$fieldName`";
    $sqlCreate .= ", `" . $fieldName . "` VARCHAR({$value['length']}) NULL DEFAULT NULL ";
}
$sqlCreate .= ", PRIMARY KEY (`id`)) ENGINE = InnoDB";
$resql = $db->query($sqlCreate);
if ($resql !== false) {
    print "Table $table_name created\n";
} else {
    var_dump($db->errno());
    print "Impossible : " . $sqlCreate . "\n";
    die();
}

$i = 0;
$nboflines++;

$fields = implode(',', $fieldArray);
//var_dump($fieldArray);die();
$maxLength = 0;
for ($i = 1; $i <= $record_numbers; $i++) {
    if ($startlinenb && $i < $startlinenb)
        continue;
    if ($endlinenb && $i > $endlinenb)
        continue;
    $row = dbase_get_record_with_names($fhandle, $i);
    if ($row === false || (isset($row["deleted"]) && $row["deleted"] == '1'))
        continue;
    $sqlInsert = "INSERT INTO `$table_name`($fields) VALUES (null,";
    array_shift($row); // remove delete column
    foreach ($row as $value) {
        $sqlInsert .= "'" . $db->escape(utf8_encode($value)) . "', ";
    }
    replaceable_echo(implode("\t", $row));
    $sqlInsert = rtrim($sqlInsert, ', ');
    $sqlInsert .= ")";
    $resql = $db->query($sqlInsert);
    if ($resql === false) {
        print "Impossible : " . $sqlInsert . "\n";
        var_dump($row, $db->errno());
        die();
    }
	//    $fields = (object) $row;
	//    var_dump($fields);
    continue;
}
die();





// commit or rollback
print "Nb of lines qualified: " . $nboflines . "\n";
print "Nb of errors: " . $error . "\n";
if ($mode != 'confirmforced' && ($error || $mode != 'confirm')) {
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


/**
 * replaceable_echo
 *
 * @param string 	$message			Message
 * @param int 		$force_clear_lines	Force clear messages
 * @return void
 */
function replaceable_echo($message, $force_clear_lines = null)
{
    static $last_lines = 0;

    if (!is_null($force_clear_lines)) {
        $last_lines = $force_clear_lines;
    }

    $toss = array();
    $status = 0;
    $term_width = exec('tput cols', $toss, $status);
    if ($status) {
        $term_width = 64; // Arbitrary fall-back term width.
    }

    $line_count = 0;
    foreach (explode("\n", $message) as $line) {
        $line_count += count(str_split($line, $term_width));
    }

    // Erasure MAGIC: Clear as many lines as the last output had.
    for ($i = 0; $i < $last_lines; $i++) {
        // Return to the beginning of the line
        echo "\r";
        // Erase to the end of the line
        echo "\033[K";
        // Move cursor Up a line
        echo "\033[1A";
        // Return to the beginning of the line
        echo "\r";
        // Erase to the end of the line
        echo "\033[K";
        // Return to the beginning of the line
        echo "\r";
        // Can be consolodated into
        // echo "\r\033[K\033[1A\r\033[K\r";
    }

    $last_lines = $line_count;

    echo $message . "\n";
}
