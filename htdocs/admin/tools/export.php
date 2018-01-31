<?php
/* Copyright (C) 2006-2014  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2011       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 *		\file 		htdocs/admin/tools/export.php
 *		\brief      Page to export a database into a dump file
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("admin");

$action=GETPOST('action','alpha');
$what=GETPOST('what','alpha');
$export_type=GETPOST('export_type','alpha');
$file=GETPOST('filename_template','alpha');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST("page",'int');
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="date";
if ($page < 0) { $page = 0; }
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$offset = $limit * $page;

if (! $user->admin) accessforbidden();

if ($file && ! $what)
{
    //print DOL_URL_ROOT.'/dolibarr_export.php';
    header("Location: ".DOL_URL_ROOT.'/admin/tools/dolibarr_export.php?msg='.urlencode($langs->trans("ErrorFieldRequired",$langs->transnoentities("ExportMethod"))));
    exit;
}

$errormsg='';


/*
 * Actions
 */

if ($action == 'delete')
{
	$file=$conf->admin->dir_output.'/'.GETPOST('urlfile');
	$ret=dol_delete_file($file, 1);
	if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	$action='';
}


/*
 * View
 */

$_SESSION["commandbackuplastdone"]='';
$_SESSION["commandbackuptorun"]='';
$_SESSION["commandbackupresult"]='';

// Increase limit of time. Works only if we are not in safe mode
$ExecTimeLimit=600;
if (!empty($ExecTimeLimit))
{
    $err=error_reporting();
    error_reporting(0);     // Disable all errors
    //error_reporting(E_ALL);
    @set_time_limit($ExecTimeLimit);   // Need more than 240 on Windows 7/64
    error_reporting($err);
}
$MemoryLimit=0;
if (!empty($MemoryLimit))
{
    @ini_set('memory_limit', $MemoryLimit);
}

$form=new Form($db);
$formfile = new FormFile($db);

//$help_url='EN:Backups|FR:Sauvegardes|ES:Copias_de_seguridad';
//llxHeader('','',$help_url);

//print load_fiche_titre($langs->trans("Backup"),'','title_setup');


// Start with empty buffer
$dump_buffer = '';
$dump_buffer_len = 0;

// We will send fake headers to avoid browser timeout when buffering
$time_start = time();


$outputdir  = $conf->admin->dir_output.'/backup';
$result=dol_mkdir($outputdir);


$utils = new Utils($db);


// MYSQL
if ($what == 'mysql')
{

    $cmddump=GETPOST("mysqldump");	// Do not sanitize here with 'alpha', will be sanitize later by dol_sanitizePathName and escapeshellarg
    $cmddump=dol_sanitizePathName($cmddump);

    if (! empty($dolibarr_main_restrict_os_commands))
    {
        $arrayofallowedcommand=explode(',', $dolibarr_main_restrict_os_commands);
        $ok=0;
        dol_syslog("Command are restricted to ".$dolibarr_main_restrict_os_commands.". We check that on of this command is inside ".$cmddump);
        foreach($arrayofallowedcommand as $allowedcommand)
        {
            if (preg_match('/'.preg_quote($allowedcommand,'/').'/', $cmddump))
            {
                $ok=1;
                break;
            }
        }
        if (! $ok)
        {
            $errormsg=$langs->trans('CommandIsNotInsideAllowedCommands');
        }
    }

    if (! $errormsg && $cmddump)
    {
        dolibarr_set_const($db, 'SYSTEMTOOLS_MYSQLDUMP', $cmddump,'chaine',0,'',$conf->entity);
    }

    if (! $errormsg)
    {
        $utils->dumpDatabase(GETPOST('compression','alpha'), $what, 0, $file);
        $errormsg=$utils->error;
        $_SESSION["commandbackuplastdone"]=$utils->result['commandbackuplastdone'];
        $_SESSION["commandbackuptorun"]=$utils->result['commandbackuptorun'];
    }
}

// MYSQL NO BIN
if ($what == 'mysqlnobin')
{
    $utils->dumpDatabase(GETPOST('compression','alpha'), $what, 0, $file);

    $errormsg=$utils->error;
    $_SESSION["commandbackuplastdone"]=$utils->result['commandbackuplastdone'];
    $_SESSION["commandbackuptorun"]=$utils->result['commandbackuptorun'];
}

// POSTGRESQL
if ($what == 'postgresql')
{
    $cmddump=GETPOST("postgresqldump");	// Do not sanitize here with 'alpha', will be sanitize later by dol_sanitizePathName and escapeshellarg
    $cmddump=dol_sanitizePathName($cmddump);

    if (! $errormsg && $cmddump)
    {
        dolibarr_set_const($db, 'SYSTEMTOOLS_POSTGRESQLDUMP', $cmddump,'chaine',0,'',$conf->entity);
    }

    if (! $errormsg)
    {
        $utils->dumpDatabase(GETPOST('compression','alpha'), $what, 0, $file);
        $errormsg=$utils->error;
        $_SESSION["commandbackuplastdone"]=$utils->result['commandbackuplastdone'];
        $_SESSION["commandbackuptorun"]=$utils->result['commandbackuptorun'];
    }

    $what='';   // Clear to show message to run command
}



if ($errormsg)
{
	setEventMessages($langs->trans("Error")." : ".$errormsg, null, 'errors');

	$resultstring='';
    $resultstring.='<div class="error">'.$langs->trans("Error")." : ".$errormsg.'</div>';

    $_SESSION["commandbackupresult"]=$resultstring;
}
else
{
	if ($what)
	{
        setEventMessages($langs->trans("BackupFileSuccessfullyCreated").'.<br>'.$langs->trans("YouCanDownloadBackupFile"), null, 'mesgs');

        $resultstring='<div class="ok">';
        $resultstring.=$langs->trans("BackupFileSuccessfullyCreated").'.<br>';
        $resultstring.=$langs->trans("YouCanDownloadBackupFile");
        $resultstring.='<div>';

        $_SESSION["commandbackupresult"]=$resultstring;
	}
	/*else
	{
		setEventMessages($langs->trans("YouMustRunCommandFromCommandLineAfterLoginToUser",$dolibarr_main_db_user,$dolibarr_main_db_user), null, 'warnings');
	}*/
}


/*
$filearray=dol_dir_list($conf->admin->dir_output.'/backup','files',0,'','',$sortfield,(strtolower($sortorder)=='asc'?SORT_ASC:SORT_DESC),1);
$result=$formfile->list_of_documents($filearray,null,'systemtools','',1,'backup/',1,0,($langs->trans("NoBackupFileAvailable").'<br>'.$langs->trans("ToBuildBackupFileClickHere",DOL_URL_ROOT.'/admin/tools/dolibarr_export.php')),0,$langs->trans("PreviousDumpFiles"));

print '<br>';
*/

// Redirect t backup page
header("Location: dolibarr_export.php");

$time_end = time();

$db->close();



// MYSQL NO BINARIES (only php)
/**	Backup the db OR just a table without mysqldump binary (does not require any exec permission)
 *	Author: David Walsh (http://davidwalsh.name/backup-mysql-database-php)
 *	Updated and enhanced by Stephen Larroque (lrq3000) and by the many commentators from the blog
 *	Note about foreign keys constraints: for Dolibarr, since there are a lot of constraints and when imported the tables will be inserted in the dumped order, not in constraints order, then we ABSOLUTELY need to use SET FOREIGN_KEY_CHECKS=0; when importing the sql dump.
 *	Note2: db2SQL by Howard Yeend can be an alternative, by using SHOW FIELDS FROM and SHOW KEYS FROM we could generate a more precise dump (eg: by getting the type of the field and then precisely outputting the right formatting - in quotes, numeric or null - instead of trying to guess like we are doing now).
 *
 *	@param	string	$outputfile		Output file name
 *	@param	string	$tables			Table name or '*' for all
 *	@return	int						<0 if KO, >0 if OK
 */
function backup_tables($outputfile, $tables='*')
{
    global $db, $langs;
    global $errormsg;

    // Set to UTF-8
	if (is_a($db, 'DoliDBMysqli')) {
		/** @var DoliDBMysqli $db */
		$db->db->set_charset('utf8');
	} else {
		/** @var DoliDB $db */
		$db->query('SET NAMES utf8');
		$db->query('SET CHARACTER SET utf8');
	}

    //get all of the tables
    if ($tables == '*')
    {
        $tables = array();
        $result = $db->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'');
        while($row = $db->fetch_row($result))
        {
            $tables[] = $row[0];
        }
    }
    else
    {
        $tables = is_array($tables) ? $tables : explode(',',$tables);
    }

    //cycle through
    $handle = fopen($outputfile, 'w+');
    if (fwrite($handle, '') === FALSE)
    {
        $langs->load("errors");
        dol_syslog("Failed to open file ".$outputfile,LOG_ERR);
        $errormsg=$langs->trans("ErrorFailedToWriteInDir");
        return -1;
    }

    // Print headers and global mysql config vars
    $sqlhead = '';
    $sqlhead .= "-- ".$db::LABEL." dump via php with Dolibarr ".DOL_VERSION."
--
-- Host: ".$db->db->host_info."    Database: ".$db->database_name."
-- ------------------------------------------------------
-- Server version	".$db->db->server_info."

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

";

    if (GETPOST("nobin_disable_fk")) $sqlhead .= "SET FOREIGN_KEY_CHECKS=0;\n";
    //$sqlhead .= "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n";
    if (GETPOST("nobin_use_transaction")) $sqlhead .= "SET AUTOCOMMIT=0;\nSTART TRANSACTION;\n";

    fwrite($handle, $sqlhead);

    $ignore = '';
    if (GETPOST("nobin_sql_ignore")) $ignore = 'IGNORE ';
    $delayed = '';
    if (GETPOST("nobin_delayed")) $delayed = 'DELAYED ';

    // Process each table and print their definition + their datas
    foreach($tables as $table)
    {
        // Saving the table structure
        fwrite($handle, "\n--\n-- Table structure for table `".$table."`\n--\n");

        if (GETPOST("nobin_drop")) fwrite($handle,"DROP TABLE IF EXISTS `".$table."`;\n"); // Dropping table if exists prior to re create it
        fwrite($handle,"/*!40101 SET @saved_cs_client     = @@character_set_client */;\n");
        fwrite($handle,"/*!40101 SET character_set_client = utf8 */;\n");
        $resqldrop=$db->query('SHOW CREATE TABLE '.$table);
        $row2 = $db->fetch_row($resqldrop);
        if (empty($row2[1]))
        {
        	fwrite($handle, "\n-- WARNING: Show create table ".$table." return empy string when it should not.\n");
        }
        else
        {
        	fwrite($handle,$row2[1].";\n");
	        //fwrite($handle,"/*!40101 SET character_set_client = @saved_cs_client */;\n\n");

	        // Dumping the data (locking the table and disabling the keys check while doing the process)
	        fwrite($handle, "\n--\n-- Dumping data for table `".$table."`\n--\n");
	        if (!GETPOST("nobin_nolocks")) fwrite($handle, "LOCK TABLES `".$table."` WRITE;\n"); // Lock the table before inserting data (when the data will be imported back)
	        if (GETPOST("nobin_disable_fk")) fwrite($handle, "ALTER TABLE `".$table."` DISABLE KEYS;\n");
	        else fwrite($handle, "/*!40000 ALTER TABLE `".$table."` DISABLE KEYS */;\n");

	        $sql='SELECT * FROM '.$table;
	        $result = $db->query($sql);
	        while($row = $db->fetch_row($result))
	        {
	            // For each row of data we print a line of INSERT
	            fwrite($handle,'INSERT '.$delayed.$ignore.'INTO `'.$table.'` VALUES (');
	            $columns = count($row);
	            for($j=0; $j<$columns; $j++) {
	                // Processing each columns of the row to ensure that we correctly save the value (eg: add quotes for string - in fact we add quotes for everything, it's easier)
	                if ($row[$j] == null && !is_string($row[$j])) {
	                    // IMPORTANT: if the field is NULL we set it NULL
	                    $row[$j] = 'NULL';
	                } elseif(is_string($row[$j]) && $row[$j] == '') {
	                    // if it's an empty string, we set it as an empty string
	                    $row[$j] = "''";
	                } elseif(is_numeric($row[$j]) && !strcmp($row[$j], $row[$j]+0) ) { // test if it's a numeric type and the numeric version ($nb+0) == string version (eg: if we have 01, it's probably not a number but rather a string, else it would not have any leading 0)
	                    // if it's a number, we return it as-is
//	                    $row[$j] = $row[$j];
	                } else { // else for all other cases we escape the value and put quotes around
	                    $row[$j] = addslashes($row[$j]);
	                    $row[$j] = preg_replace("#\n#", "\\n", $row[$j]);
	                    $row[$j] = "'".$row[$j]."'";
	                }
	            }
	            fwrite($handle,implode(',', $row).");\n");
	        }
	        if (GETPOST("nobin_disable_fk")) fwrite($handle, "ALTER TABLE `".$table."` ENABLE KEYS;\n"); // Enabling back the keys/index checking
	        if (!GETPOST("nobin_nolocks")) fwrite($handle, "UNLOCK TABLES;\n"); // Unlocking the table
	        fwrite($handle,"\n\n\n");
	    }
    }

    /* Backup Procedure structure*/
    /*
     $result = $db->query('SHOW PROCEDURE STATUS');
    if ($db->num_rows($result) > 0)
    {
    while ($row = $db->fetch_row($result)) { $procedures[] = $row[1]; }
    foreach($procedures as $proc)
    {
    fwrite($handle,"DELIMITER $$\n\n");
    fwrite($handle,"DROP PROCEDURE IF EXISTS '$name'.'$proc'$$\n");
    $resqlcreateproc=$db->query("SHOW CREATE PROCEDURE '$proc'");
    $row2 = $db->fetch_row($resqlcreateproc);
    fwrite($handle,"\n".$row2[2]."$$\n\n");
    fwrite($handle,"DELIMITER ;\n\n");
    }
    }
    */
    /* Backup Procedure structure*/

    // Write the footer (restore the previous database settings)
    $sqlfooter="\n\n";
    if (GETPOST("nobin_use_transaction")) $sqlfooter .= "COMMIT;\n";
    if (GETPOST("nobin_disable_fk")) $sqlfooter .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $sqlfooter.="\n\n-- Dump completed on ".date('Y-m-d G-i-s');
    fwrite($handle, $sqlfooter);

    fclose($handle);

    return 1;
}
