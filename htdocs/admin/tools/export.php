<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
		\file 		htdocs/admin/tools/export.php
		\brief      Page export de la base
		\version    $Revision$
*/

require("./pre.inc.php");
include_once $dolibarr_main_document_root."/lib/databases/".$conf->db->type.".lib.php";

$what=$_REQUEST["what"];
$export_type=$_REQUEST["export_type"];
$file=isset($_POST['filename_template']) ? $_POST['filename_template'] : '';

$langs->load("admin");

if (! $user->admin)
  accessforbidden();


if ($file && ! $what) 
{
   //print DOL_URL_ROOT.'/dolibarr_export.php';
	header("Location: ".DOL_URL_ROOT.'/admin/tools/dolibarr_export.php?msg='.urlencode($langs->trans("ErrorFieldRequired",$langs->transnoentities("ExportMethod"))));
/*
	print '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("ExportMethod")).'</div>';
	print '<br>';
*/
	exit;
}



llxHeader();

$html=new Form($db);

print_fiche_titre($langs->trans("Backup"),'','setup');
print '<br>';

/**
 * Increase time limit for script execution and initializes some variables
 */
@set_time_limit($cfg['ExecTimeLimit']);
if (!empty($cfg['MemoryLimit'])) {
    @ini_set('memory_limit', $cfg['MemoryLimit']);
}

// Start with empty buffer
$dump_buffer = '';
$dump_buffer_len = 0;

// We send fake headers to avoid browser timeout when buffering
$time_start = time();


if ($what == 'mysql')
{
	$mysqldump=$_POST["mysqldump"];
	if ($mysqldump)
	{
		dolibarr_set_const($db, 'SYSTEMTOOLS_MYSQLDUMP', $mysqldump, $type='chaine');
	}
	
	create_exdir(DOL_DATA_ROOT.'/admin/temp');
	
	// Parameteres execution	
	$command=escapeshellarg($mysqldump);
	//$param=escapeshellarg($dolibarr_main_db_name)." -h ".escapeshellarg($dolibarr_main_db_host)." -u ".escapeshellarg($dolibarr_main_db_user)." -p".escapeshellarg($dolibarr_main_db_pass);
	$param=$dolibarr_main_db_name." -h ".$dolibarr_main_db_host;
	$param.=" -u ".$dolibarr_main_db_user;
	$compression=isset($_POST['compression']) ? $_POST['compression'] : 'none';
	if (! $_POST["use_transaction"]) $param.=" -l --single-transaction";
	if ($_POST["disable_fk"])        $param.=" -K";
	if ($_POST["sql_compat"] && $_POST["sql_compat"] != 'NONE') $param.=" --compatible=".$_POST["sql_compat"];
	if ($_POST["drop_database"])     $param.=" --add-drop-database";
	if ($_POST["sql_structure"])
	{
		if ($_POST["drop"])			 $param.=" --add-drop-table";
	}
	else
	{
		$param.=" -t";	
	}
	if ($_POST["sql_data"])
	{
		$param.=" --tables";
		if ($_POST["showcolumns"])	$param.=" -c";
		if ($_POST["extended_ins"])	$param.=" -e";
		if ($_POST["delayed"])	 	$param.=" --delayed-insert";
		if ($_POST["sql_ignore"])	$param.=" --insert-ignore";
		if ($_POST["hexforbinary"])	$param.=" --hex-blob";
	}
	else
	{
		$param.=" -d";
	}
	$paramcrypted=$param." -p".eregi_replace('.','*',$dolibarr_main_db_pass);
	$paramclear=$param." -p".$dolibarr_main_db_pass;

	$relativepathdir='/admin/temp';
	$relativepathfile=$relativepathdir.'/'.$file;
	// for compression format, we add extension
	if ($compression == 'gz') $relativepathfile.='.gz';
	if ($compression == 'bz') $relativepathfile.='.bz2';
	$relativepatherr=$relativepathfile.'.err';
	$outputdir=DOL_DATA_ROOT.$relativepathdir;
	$outputfile=DOL_DATA_ROOT.$relativepathfile;
	$outputerror=DOL_DATA_ROOT.$relativepatherr;
	
	print $langs->trans("RunCommandSummary").':<br>'."\n";
	print '<textarea rows="1" cols="120">'.$command." ".$paramcrypted.'</textarea><br>'."\n";

	print '<br>';

	print $langs->trans("BackupResult").': ';

	$errormsg='';

	$result=create_exdir($outputdir);
	
	// Debut appel methode execution
	$fullcommandcrypted=$command." ".$paramcrypted." 2>&1";
	$fullcommandclear=$command." ".$paramclear." 2>&1";
	if ($compression == 'none') $handle = fopen($outputfile, 'w');
	if ($compression == 'gz')   $handle = gzopen($outputfile, 'w');
	if ($compression == 'bz')   $handle = bzopen($outputfile, 'w');

	if ($handle)
	{	
		dolibarr_syslog("Run command ".$fullcommandcrypted);
		$handlein = popen($fullcommandclear, 'r');
		while (!feof($handlein))
		{
			$read = fgets($handlein);
			fwrite($handle,$read);
		}
		pclose($handlein);
		
		if ($compression == 'none') fclose($handle);
		if ($compression == 'gz')   gzclose($handle);
		if ($compression == 'bz')   bzclose($handle);
	}
	else
	{
		dolibarr_syslog("Failed to open file $outputfile",LOG_ERR);
		$errormsg=$langs->trans("ErrorFailedToWriteInDir");
	}
		
	// Get errorstring
	if ($compression == 'none') $handle = fopen($outputfile, 'r');
	if ($compression == 'gz')   $handle = gzopen($outputfile, 'r');
	if ($compression == 'bz')   $handle = bzopen($outputfile, 'r');
	if ($hanlde)
	{
		$errormsg = fgets($handle,10);
		if ($compression == 'none') fclose($handle);
		if ($compression == 'gz')   gzclose($handle);
		if ($compression == 'bz')   bzclose($handle);
		if (eregi('^-- MySql',$errormsg)) $errormsg='';	// Pas erreur
		else
		{
			// Renommer fichier sortie en fichier erreur	
			//print "$outputfile -> $outputerror";
			dol_delete_file($outputerror);
			@rename($outputfile,$outputerror);
			// Si safe_mode on et command hors du parametre exec, on a un fichier out donc errormsg vide
			if (! $errormsg) $errormsg=$langs->trans("ErrorFailedToRunExternalCommand");	
		}
	}
	// Fin execution commande

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

$result=$html->show_documents('systemtools','',DOL_DATA_ROOT.'/admin/temp',$_SERVER['PHP_SELF'],0,1);

if ($result == 0)
{
	print $langs->trans("NoBackupFileAvailable").'<br>';	
	print $langs->trans("ToBuildBackupFileClickHere",DOL_URL_ROOT.'/admin/tools/dolibarr_export.php').'<br>';	
}
	
print '<br>';

$time_end = time();

llxFooter('$Date$ - $Revision$');
?>
