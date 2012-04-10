<?php
/* Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011	   Juanjo Menent		<jmenent@2byte.es>
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
*/

/**
 *		\file 		htdocs/admin/tools/export.php
 *		\brief      Page to export a database into a dump file
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load("admin");

$action=GETPOST('action','alpha');
$what=GETPOST('what','alpha');
$export_type=GETPOST('export_type','alpha');
$file=GETPOST('filename_template','alpha');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST("page");
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="date";
if ($page < 0) { $page = 0; }
$limit = $conf->liste_limit;
$offset = $limit * $page;

if (! $user->admin) accessforbidden();

if ($file && ! $what)
{
    //print DOL_URL_ROOT.'/dolibarr_export.php';
    header("Location: ".DOL_URL_ROOT.'/admin/tools/dolibarr_export.php?msg='.urlencode($langs->trans("ErrorFieldRequired",$langs->transnoentities("ExportMethod"))));
    exit;
}


/*
 * Actions
 */

if ($action == 'delete')
{
    dol_delete_file($conf->admin->dir_output.'/backup/'.GETPOST('urlfile'),1);
    $action='';
}


/*
 * View
 */

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
if (!empty($MemoryLimit))
{
    @ini_set('memory_limit', $MemoryLimit);
}

$form=new Form($db);
$formfile = new FormFile($db);

$help_url='EN:Backups|FR:Sauvegardes|ES:Copias_de_seguridad';
llxHeader('','',$help_url);

print_fiche_titre($langs->trans("Backup"),'','setup');


// Start with empty buffer
$dump_buffer = '';
$dump_buffer_len = 0;

// We will send fake headers to avoid browser timeout when buffering
$time_start = time();


// MYSQL
if ($what == 'mysql')
{
    $cmddump=GETPOST("mysqldump");	// Do not sanitize here with 'alpha', will be sanitize later by escapeshellarg
    if ($cmddump)
    {
        dolibarr_set_const($db, 'SYSTEMTOOLS_MYSQLDUMP', $cmddump,'chaine',0,'',$conf->entity);
    }

    $outputdir  = $conf->admin->dir_output.'/backup';
    $outputfile = $outputdir.'/'.$file;
    // for compression format, we add extension
    $compression=GETPOST('compression') ? GETPOST('compression','alpha') : 'none';
    if ($compression == 'gz') $outputfile.='.gz';
    if ($compression == 'bz') $outputfile.='.bz2';
    $outputerror = $outputfile.'.err';
    dol_mkdir($conf->admin->dir_output.'/backup');

    // Parameteres execution
    $command=$cmddump;
    if (preg_match("/\s/",$command)) $command=escapeshellarg($command);	// Use quotes on command

    //$param=escapeshellarg($dolibarr_main_db_name)." -h ".escapeshellarg($dolibarr_main_db_host)." -u ".escapeshellarg($dolibarr_main_db_user)." -p".escapeshellarg($dolibarr_main_db_pass);
    $param=$dolibarr_main_db_name." -h ".$dolibarr_main_db_host;
    $param.=" -u ".$dolibarr_main_db_user;
    if (! empty($dolibarr_main_db_port)) $param.=" -P ".$dolibarr_main_db_port;
    if (! GETPOST("use_transaction"))    $param.=" -l --single-transaction";
    if (GETPOST("disable_fk"))           $param.=" -K";
    if (GETPOST("sql_compat") && GETPOST("sql_compat") != 'NONE') $param.=" --compatible=".escapeshellarg(GETPOST("sql_compat","alpha"));
    if (GETPOST("drop_database"))        $param.=" --add-drop-database";
    if (GETPOST("sql_structure"))
    {
        if (GETPOST("drop"))			 $param.=" --add-drop-table";
    }
    else
    {
        $param.=" -t";
    }
    if (GETPOST("disable-add-locks")) $param.=" --add-locks=FALSE";
    if (GETPOST("sql_data"))
    {
        $param.=" --tables";
        if (GETPOST("showcolumns"))	 $param.=" -c";
        if (GETPOST("extended_ins")) $param.=" -e";
        else $param.=" --skip-extended-insert";
        if (GETPOST("delayed"))	 	 $param.=" --delayed-insert";
        if (GETPOST("sql_ignore"))	 $param.=" --insert-ignore";
        if (GETPOST("hexforbinary")) $param.=" --hex-blob";
    }
    else
    {
        $param.=" -d";    // No row information (no data)
    }
    $param.=" --default-character-set=utf8";    // We always save output into utf8 charset
    $paramcrypted=$param;
    $paramclear=$param;
    if (! empty($dolibarr_main_db_pass))
    {
        $paramcrypted.=' -p"'.preg_replace('/./i','*',$dolibarr_main_db_pass).'"';
        $paramclear.=' -p"'.str_replace('"','\"',$dolibarr_main_db_pass).'"';
    }

    print '<b>'.$langs->trans("RunCommandSummary").':</b><br>'."\n";
    print '<textarea rows="'.ROWS_2.'" cols="120">'.$command." ".$paramcrypted.'</textarea><br>'."\n";
    print '<br>';
    //print $paramclear;

    // Now run command and show result
    print '<b>'.$langs->trans("BackupResult").':</b> ';

    $errormsg='';

    $result=dol_mkdir($outputdir);

    // Debut appel methode execution
    $fullcommandcrypted=$command." ".$paramcrypted." 2>&1";
    $fullcommandclear=$command." ".$paramclear." 2>&1";
    if ($compression == 'none') $handle = fopen($outputfile, 'w');
    if ($compression == 'gz')   $handle = gzopen($outputfile, 'w');
    if ($compression == 'bz')   $handle = bzopen($outputfile, 'w');

    if ($handle)
    {
        $ok=0;
        dol_syslog("Run command ".$fullcommandcrypted);
        $handlein = popen($fullcommandclear, 'r');
        while (!feof($handlein))
        {
            $read = fgets($handlein);
            fwrite($handle,$read);
            if (preg_match('/'.preg_quote('-- Dump completed').'/i',$read)) $ok=1;
            elseif (preg_match('/'.preg_quote('SET SQL_NOTES=@OLD_SQL_NOTES').'/i',$read)) $ok=1;
        }
        pclose($handlein);

        if ($compression == 'none') fclose($handle);
        if ($compression == 'gz')   gzclose($handle);
        if ($compression == 'bz')   bzclose($handle);

        if (! empty($conf->global->MAIN_UMASK))
        @chmod($outputfile, octdec($conf->global->MAIN_UMASK));
    }
    else
    {
        $langs->load("errors");
        dol_syslog("Failed to open file ".$outputfile,LOG_ERR);
        $errormsg=$langs->trans("ErrorFailedToWriteInDir");
    }

    // Get errorstring
    if ($compression == 'none') $handle = fopen($outputfile, 'r');
    if ($compression == 'gz')   $handle = gzopen($outputfile, 'r');
    if ($compression == 'bz')   $handle = bzopen($outputfile, 'r');
    if ($handle)
    {
        // Get 2048 first chars of error message.
        $errormsg = fgets($handle,2048);
        // Close file
        if ($compression == 'none') fclose($handle);
        if ($compression == 'gz')   gzclose($handle);
        if ($compression == 'bz')   bzclose($handle);
        if ($ok && preg_match('/^-- MySql/i',$errormsg)) $errormsg='';	// Pas erreur
        else
        {
            // Renommer fichier sortie en fichier erreur
            //print "$outputfile -> $outputerror";
            @dol_delete_file($outputerror,1);
            @rename($outputfile,$outputerror);
            // Si safe_mode on et command hors du parametre exec, on a un fichier out vide donc errormsg vide
            if (! $errormsg)
            {
            	$langs->load("errors");
            	$errormsg=$langs->trans("ErrorFailedToRunExternalCommand");
            }
        }
    }
    // Fin execution commande
}

if ($what == 'mysqlnobin')
{
    $outputdir  = $conf->admin->dir_output.'/backup';
    $outputfile = $outputdir.'/'.$file;
    $outputfiletemp = $outputfile.'-TMP.sql';
    // for compression format, we add extension
    $compression=GETPOST('compression') ? GETPOST('compression','alpha') : 'none';
    if ($compression == 'gz') $outputfile.='.gz';
    if ($compression == 'bz') $outputfile.='.bz2';
    $outputerror = $outputfile.'.err';
    dol_mkdir($conf->admin->dir_output.'/backup');

    if ($compression == 'gz' or $compression == 'bz')
    {
        backup_tables($outputfiletemp);
        dol_compress_file($outputfiletemp, $outputfile, $compression);
        unlink($outputfiletemp);
    }
    else
    {
        backup_tables($outputfile);
    }
}

// POSTGRESQL
if ($what == 'postgresql')
{
    $cmddump=GETPOST("postgresqldump");	// Do not sanitize here with 'alpha', will be sanitize later by escapeshellarg
    if ($cmddump)
    {
        dolibarr_set_const($db, 'SYSTEMTOOLS_POSTGRESQLDUMP', $cmddump,'chaine',0,'',$conf->entity);
    }

    $outputdir  = $conf->admin->dir_output.'/backup';
    $outputfile = $outputdir.'/'.$file;
    // for compression format, we add extension
    $compression=GETPOST('compression') ? GETPOST('compression','alpha') : 'none';
    if ($compression == 'gz') $outputfile.='.gz';
    if ($compression == 'bz') $outputfile.='.bz2';
    $outputerror = $outputfile.'.err';
    dol_mkdir($conf->admin->dir_output.'/backup');

    // Parameteres execution
    $command=$cmddump;
    if (preg_match("/\s/",$command)) $command=$command=escapeshellarg($command);	// Use quotes on command

    //$param=escapeshellarg($dolibarr_main_db_name)." -h ".escapeshellarg($dolibarr_main_db_host)." -u ".escapeshellarg($dolibarr_main_db_user)." -p".escapeshellarg($dolibarr_main_db_pass);
    $param=" --no-tablespaces --inserts -h ".$dolibarr_main_db_host;
    $param.=" -U ".$dolibarr_main_db_user;
    if (! empty($dolibarr_main_db_port)) $param.=" -p ".$dolibarr_main_db_port;
    if (GETPOST("sql_compat") && GETPOST("sql_compat") == 'ANSI') $param.="  --disable-dollar-quoting";
    if (GETPOST("drop_database"))        $param.=" -c -C";
    if (GETPOST("sql_structure"))
    {
        if (GETPOST("drop"))			 $param.=" --add-drop-table";
        if (! GETPOST("sql_data"))       $param.=" -s";
    }
    if (GETPOST("sql_data"))
    {
        if (! GETPOST("sql_structure"))	 $param.=" -a";
        if (GETPOST("showcolumns"))	     $param.=" -c";
    }
    $param.=' -f "'.$outputfile.'"';
    //if ($compression == 'none')
    if ($compression == 'gz')   $param.=' -Z 9';
    //if ($compression == 'bz')
    $paramcrypted=$param;
    $paramclear=$param;
    /*if (! empty($dolibarr_main_db_pass))
     {
    $paramcrypted.=" -W".preg_replace('/./i','*',$dolibarr_main_db_pass);
    $paramclear.=" -W".$dolibarr_main_db_pass;
    }*/
    $paramcrypted.=" -w ".$dolibarr_main_db_name;
    $paramclear.=" -w ".$dolibarr_main_db_name;

    print $langs->trans("RunCommandSummaryToLaunch").':<br>'."\n";
    print '<textarea rows="'.ROWS_3.'" cols="120">'.$command." ".$paramcrypted.'</textarea><br>'."\n";

    print '<br>';


    // Now show to ask to run command
    print $langs->trans("YouMustRunCommandFromCommandLineAfterLoginToUser",$dolibarr_main_db_user,$dolibarr_main_db_user);

    print '<br>';
    print '<br>';

    $what='';
}




// Si on a demande une generation
if ($what)
{
    if ($errormsg)
    {
        print '<div class="error">'.$langs->trans("Error")." : ".$errormsg.'</div>';
        //		print '<a href="'.DOL_URL_ROOT.$relativepatherr.'">'.$langs->trans("DownloadErrorFile").'</a><br>';
        print '<br>';
        print '<br>';
    }
    else
    {
        print '<div class="ok">';
        print $langs->trans("BackupFileSuccessfullyCreated").'.<br>';
        print $langs->trans("YouCanDownloadBackupFile");
        print '</div>';
        print '<br>';
    }
}

$filearray=dol_dir_list($conf->admin->dir_output.'/backup','files',0,'','',$sortfield,(strtolower($sortorder)=='asc'?SORT_ASC:SORT_DESC),1);
$result=$formfile->list_of_documents($filearray,null,'systemtools','',1,'backup/',1,0,($langs->trans("NoBackupFileAvailable").'<br>'.$langs->trans("ToBuildBackupFileClickHere",DOL_URL_ROOT.'/admin/tools/dolibarr_export.php')),0,$langs->trans("PreviousDumpFiles"));

print '<br>';

$time_end = time();

llxFooter();

$db->close();



// MYSQL NO BINARIES (only php)
/**	Backup the db OR just a table without mysqldump binary (does not require any exec permission)
 *	Author: David Walsh (http://davidwalsh.name/backup-mysql-database-php)
 *	Updated and enhanced by Stephen Larroque (lrq3000) and by the many commentators from the blog
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
    $db->query('SET NAMES utf8');
    $db->query('SET CHARACTER SET utf8');

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
    $sqlhead .= "-- ".getStaticMember($db, 'label')." dump via php
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
    fwrite($handle, $sqlhead);

    // Process each table and print their definition + their datas
    foreach($tables as $table)
    {
        // Saving the table structure
        fwrite($handle, "--\n-- Table structure for table `".$table."`\n--\n\n");

        fwrite($handle,"DROP TABLE IF EXISTS `".$table."`;\n");
        fwrite($handle,"/*!40101 SET @saved_cs_client     = @@character_set_client */;\n");
        fwrite($handle,"/*!40101 SET character_set_client = utf8 */;\n");
        $resqldrop=$db->query('SHOW CREATE TABLE '.$table);
        $row2 = $db->fetch_row($resqldrop);
        fwrite($handle,$row2[1].";\n");
        fwrite($handle,"/*!40101 SET character_set_client = @saved_cs_client */;\n\n");


        // Dumping the data (locking the table and disabling the keys check while doing the process)
        fwrite($handle, "--\n-- Dumping data for table `".$table."`\n--\n\n");
        fwrite($handle, "LOCK TABLES `".$table."` WRITE;\n");
        fwrite($handle, "/*!40000 ALTER TABLE `".$table."` DISABLE KEYS */;\n");

        $sql='SELECT * FROM '.$table;
        $result = $db->query($sql);
        $num_fields = $db->num_rows($result);
        while($row = $db->fetch_row($result)) {
            // For each row of data we print a line of INSERT
            fwrite($handle,'INSERT INTO `'.$table.'` VALUES (');
            $columns = count($row);
            $rowsarr = array();
            for($j=0; $j<$columns; $j++) {
                // Processing each columns of the row to ensure that we correctly save the value (eg: add quotes for string - in fact we add quotes for everything, it's easier)
                if ($row[$j] == null and !is_string($row[$j])) {
                    // IMPORTANT: if the field is NULL we set it NULL
                    $row[$j] = 'NULL';
                } elseif(is_string($row[$j]) and $row[$j] == '') {
                    // if it's an empty string, we set it as an empty string
                    $row[$j] = "''";
                } elseif(is_numeric($row[$j])) {
                    // if it's a number, we return it as-is
                    $row[$j] = $row[$j];
                } else { // else for all other cases we escape the value and put quotes around
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = preg_replace("#\n#", "\\n", $row[$j]);
                    $row[$j] = "'".$row[$j]."'";
                }
            }
            fwrite($handle,implode(',', $row).");\n");
        }
        fwrite($handle, "/*!40000 ALTER TABLE `".$table."` ENABLE KEYS */;\n"); // Enabling back the keys/index checking
        fwrite($handle, "UNLOCK TABLES;\n"); // Unlocking the tables
        fwrite($handle,"\n\n\n");
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
    $sqlfooter='';
    $sqlfooter.="
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on ".date('Y-m-d G-i-s');
    fwrite($handle, $sqlfooter);

    fclose($handle);

    return 1;
}
?>
