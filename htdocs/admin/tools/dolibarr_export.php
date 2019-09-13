<?php
/* Copyright (C) 2006-2018	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2018	Regis Houssin		<regis.houssin@inodbox.com>
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
 *		\file 		htdocs/admin/tools/dolibarr_export.php
 *		\ingroup	core
 *		\brief      Page to export database
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("admin");

$action=GETPOST('action', 'alpha');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="date";
if (empty($page) || $page == -1) { $page = 0; }
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$offset = $limit * $page;

if (! $user->admin)
	accessforbidden();


/*
 * Actions
 */

if ($action == 'delete')
{
	if (preg_match('/^backup\//', GETPOST('urlfile', 'alpha')))
	{
		$file=$conf->admin->dir_output.'/backup/'.basename(GETPOST('urlfile', 'alpha'));
		$ret=dol_delete_file($file, 1);
		if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	}
	else
	{
		$file=$conf->admin->dir_output.'/documents/'.basename(GETPOST('urlfile', 'alpha'));
		$ret=dol_delete_file($file, 1);
		if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	}
    $action='';
}


/*
 * View
 */

$form=new Form($db);
$formfile = new FormFile($db);

$label=$db::LABEL;
$type=$db->type;
//var_dump($db);

$help_url='EN:Backups|FR:Sauvegardes|ES:Copias_de_seguridad';
llxHeader('', '', $help_url);

?>
<script type="text/javascript">
jQuery(document).ready(function() {

	function hideoptions () {
		jQuery("#mysql_options").hide();
		jQuery("#mysql_nobin_options").hide();
		jQuery("#postgresql_options").hide();
	}

	hideoptions();
	jQuery("#radio_dump_mysql").click(function() {
		hideoptions();
		jQuery("#mysql_options").show();
	});
	jQuery("#radio_dump_mysql_nobin").click(function() {
		hideoptions();
		jQuery("#mysql_nobin_options").show();
	});
	jQuery("#radio_dump_postgresql").click(function() {
		hideoptions();
		jQuery("#postgresql_options").show();
	});
	jQuery("#select_sql_compat").click(function() {
		if (jQuery("#select_sql_compat").val() == 'POSTGRESQL')
		{
			jQuery("#checkbox_dump_disable-add-locks").prop('checked',true);
		}
	});

	<?php
	    if (in_array($type, array('mysql', 'mysqli')))  print 'jQuery("#radio_dump_mysql").click();';
	    if (in_array($type, array('pgsql'))) print 'jQuery("#radio_dump_postgresql").click();';
	?>
});
</script>
<?php

print load_fiche_titre($langs->trans("Backup"), '', 'title_setup');
//print_barre_liste($langs->trans("Backup"), '', '', '', '', '', $langs->trans("BackupDesc",DOL_DATA_ROOT), 0, 0, 'title_setup');

print '<div class="center opacitymedium">';
print $langs->trans("BackupDesc", DOL_DATA_ROOT);
print '</div>';
print '<br>';

?>

<!-- Dump of a server -->
<form method="post" action="export.php" name="dump"><input type="hidden"
	name="token" value="<?php echo $_SESSION['newtoken']; ?>" /> <input
	type="hidden" name="export_type" value="server" />

<fieldset id="fieldsetexport"><legend class="legendforfieldsetstep" style="font-size: 3em">1</legend>

<?php
print $langs->trans("BackupDesc3", $dolibarr_main_db_name).'<br>';
//print $langs->trans("BackupDescY").'<br>';
print '<br>';
?>

<div id="backupdatabaseleft" class="fichehalfleft" >

<?php

print load_fiche_titre($title?$title:$langs->trans("BackupDumpWizard"));

print '<table width="100%" class="'.($useinecm?'nobordernopadding':'liste').' nohover">';
print '<tr class="liste_titre">';
print '<td class="liste_titre">';
print $langs->trans("DatabaseName").' : <b>'.$dolibarr_main_db_name.'</b><br>';
print '</td>';
print '</tr>';
print '<tr '.$bc[false].'><td style="padding-left: 8px">';
?>
<table class="centpercent">
	<tr>
		<td class="tdtop">

		<div id="div_container_exportoptions">
		<fieldset id="exportoptions"><legend><?php echo $langs->trans("ExportMethod"); ?></legend>
		<?php
		if (in_array($type, array('mysql', 'mysqli')))
		{
			?>
			<div class="formelementrow"><input type="radio" name="what" value="mysql" id="radio_dump_mysql" />
			<label for="radio_dump_mysql">MySQL	Dump (mysqldump)</label>
			</div>
			<br>
			<div class="formelementrow"><input type="radio" name="what" value="mysqlnobin" id="radio_dump_mysql_nobin" />
			<label for="radio_dump_mysql">MySQL Dump (php) <?php print img_warning($langs->trans('BackupPHPWarning')) ?></label>
			</div>
			<?php
		}
		elseif (in_array($type, array('pgsql')))
		{
			?>
			<div class="formelementrow"><input type="radio" name="what"	value="postgresql" id="radio_dump_postgresql" />
			<label for="radio_dump_postgresql">PostgreSQL Dump (pg_dump)</label>
			</div>
			<?php
		}
		else
		{
			print 'No method available with database '.$label;
		}
		?>
		</fieldset>
		</div>

		</td>
		<td class="tdtop">


		<div id="div_container_sub_exportoptions">
		<?php
		if (in_array($type, array('mysql', 'mysqli')))
		{
			?> <!--  Fieldset mysqldump -->
			<fieldset id="mysql_options"><legend><?php echo $langs->trans("MySqlExportParameters"); ?></legend>

			<div class="formelementrow"><?php echo $langs->trans("FullPathToMysqldumpCommand");
			if (empty($conf->global->SYSTEMTOOLS_MYSQLDUMP))
			{
				$fullpathofmysqldump=$db->getPathOfDump();
			}
			else
			{
				$fullpathofmysqldump=$conf->global->SYSTEMTOOLS_MYSQLDUMP;
			}
			?><br>
			<input type="text" name="mysqldump" style="width: 80%"
				value="<?php echo $fullpathofmysqldump; ?>" /></div>

			<br>
			<fieldset><legend><?php echo $langs->trans("ExportOptions"); ?></legend>
			<div class="formelementrow"><input type="checkbox"
				name="use_transaction" value="yes" id="checkbox_use_transaction" /> <label
				for="checkbox_use_transaction"> <?php echo $langs->trans("UseTransactionnalMode"); ?></label>

			</div>

			<?php if (! empty($conf->global->MYSQL_OLD_OPTION_DISABLE_FK)) { ?>
			<div class="formelementrow"><input type="checkbox" name="disable_fk"
				value="yes" id="checkbox_disable_fk" checked /> <label
				for="checkbox_disable_fk"> <?php echo $langs->trans("CommandsToDisableForeignKeysForImport"); ?> <?php print img_info($langs->trans('CommandsToDisableForeignKeysForImportWarning')); ?></label>
			</div>
			<?php } ?>

			<label for="select_sql_compat"> <?php echo $langs->trans("ExportCompatibility"); ?></label>

			<select name="sql_compat" id="select_sql_compat" class="flat">
				<option value="NONE" selected>NONE</option>
				<option value="ANSI">ANSI</option>
				<option value="DB2">DB2</option>
				<option value="MAXDB">MAXDB</option>
				<option value="MYSQL323">MYSQL323</option>
				<option value="MYSQL40">MYSQL40</option>
				<option value="MSSQL">MSSQL</option>
				<option value="ORACLE">ORACLE</option>
				<option value="POSTGRESQL">POSTGRESQL</option>
			</select> <br>
			<!-- <input type="checkbox" name="drop_database" value="yes"
				id="checkbox_drop_database" /> <label for="checkbox_drop_database"><?php echo $langs->trans("AddDropDatabase"); ?></label>
			-->
			</fieldset>

			<br>
			<fieldset><legend> <input type="checkbox" name="sql_structure"
				value="structure" id="checkbox_sql_structure" checked /> <label
				for="checkbox_sql_structure"> <?php echo $langs->trans('ExportStructure') ?></label> </legend> <input
				type="checkbox" name="drop"<?php echo ((! isset($_GET["drop"]) && ! isset($_POST["drop"])) || GETPOST('drop'))?' checked':''; ?> id="checkbox_dump_drop" /> <label
				for="checkbox_dump_drop"><?php echo $langs->trans("AddDropTable"); ?></label><br>
			</fieldset>

			<br>
			<fieldset><legend> <input type="checkbox" name="sql_data" value="data"
				id="checkbox_sql_data" checked /> <label for="checkbox_sql_data">
				<?php echo $langs->trans("Datas"); ?></label> </legend> <input
				type="checkbox" name="showcolumns" value="yes"
				id="checkbox_dump_showcolumns" checked /> <label
				for="checkbox_dump_showcolumns"> <?php echo $langs->trans("NameColumn"); ?></label><br>

			<input type="checkbox" name="extended_ins" value="yes"
				id="checkbox_dump_extended_ins" checked /> <label
				for="checkbox_dump_extended_ins"> <?php echo $langs->trans("ExtendedInsert"); ?></label><br>

			<input type="checkbox" name="disable-add-locks" value="no"
				id="checkbox_dump_disable-add-locks" /> <label
				for="checkbox_dump_disable-add-locks"> <?php echo $langs->trans("NoLockBeforeInsert"); ?></label><br>

			<input type="checkbox" name="delayed" value="yes"
				id="checkbox_dump_delayed" /> <label for="checkbox_dump_delayed"> <?php echo $langs->trans("DelayedInsert"); ?></label><br>

			<input type="checkbox" name="sql_ignore" value="yes"
				id="checkbox_dump_ignore" /> <label for="checkbox_dump_ignore"> <?php echo $langs->trans("IgnoreDuplicateRecords"); ?></label><br>

			<input type="checkbox" name="hexforbinary" value="yes"
				id="checkbox_hexforbinary" checked /> <label
				for="checkbox_hexforbinary"> <?php echo $langs->trans("EncodeBinariesInHexa"); ?></label><br>

			<input type="checkbox" name="charset_utf8" value="yes"
				id="checkbox_charset_utf8" checked disabled /> <label
				for="checkbox_charset_utf8"> <?php echo $langs->trans("UTF8"); ?></label><br>

			</fieldset>
			</fieldset>

                        <!--  Fieldset mysql_nobin -->
			<fieldset id="mysql_nobin_options"><legend><?php echo $langs->trans("MySqlExportParameters"); ?></legend>
                            <fieldset>
                                <legend><?php echo $langs->trans("ExportOptions"); ?></legend>
                                <div class="formelementrow"><input type="checkbox"
                                        name="nobin_use_transaction" value="yes" id="checkbox_use_transaction" /> <label
                                        for="checkbox_use_transaction"> <?php echo $langs->trans("UseTransactionnalMode"); ?></label>

                                </div>
								<?php if (! empty($conf->global->MYSQL_OLD_OPTION_DISABLE_FK)) { ?>
                                <div class="formelementrow"><input type="checkbox" name="nobin_disable_fk"
                                        value="yes" id="checkbox_disable_fk" checked /> <label
                                        for="checkbox_disable_fk"> <?php echo $langs->trans("CommandsToDisableForeignKeysForImport"); ?> <?php print img_info($langs->trans('CommandsToDisableForeignKeysForImportWarning')); ?></label>
                                </div>
								<?php } ?>
                            </fieldset>

                            <br>
                            <fieldset><legend><?php echo $langs->trans('ExportStructure') ?></legend> <input
                                    type="checkbox" name="nobin_drop"<?php echo ((! isset($_GET["nobin_drop"]) && ! isset($_POST["nobin_drop"])) || GETPOST('nobin_drop'))?' checked':''; ?> id="checkbox_dump_drop" /> <label
                                    for="checkbox_dump_drop"><?php echo $langs->trans("AddDropTable"); ?></label><br>
                            </fieldset>

                            <br>
                            <fieldset>
                                <legend><?php echo $langs->trans("Datas"); ?></legend>

                                <input type="checkbox" name="nobin_nolocks" value="no"
                                        id="checkbox_dump_disable-add-locks" /> <label
                                        for="checkbox_dump_disable-add-locks"> <?php echo $langs->trans("NoLockBeforeInsert"); ?></label><br>

                                <input type="checkbox" name="nobin_delayed" value="yes"
                                        id="checkbox_dump_delayed" /> <label for="checkbox_dump_delayed"> <?php echo $langs->trans("DelayedInsert"); ?></label><br>

                                <input type="checkbox" name="nobin_sql_ignore" value="yes"
                                        id="checkbox_dump_ignore" /> <label for="checkbox_dump_ignore"> <?php echo $langs->trans("IgnoreDuplicateRecords"); ?></label><br>

                                <input type="checkbox" name="nobin_charset_utf8" value="yes"
                                        id="checkbox_charset_utf8" checked disabled /> <label
                                        for="checkbox_charset_utf8"> <?php echo $langs->trans("UTF8"); ?></label><br>

                            </fieldset>
			</fieldset>

		<?php
		}

		if (in_array($type, array('pgsql')))
		{
			?> <!--  Fieldset pg_dump -->
			<fieldset id="postgresql_options"><legend><?php echo $langs->trans("PostgreSqlExportParameters"); ?></legend>

			<div class="formelementrow"><?php echo $langs->trans("FullPathToPostgreSQLdumpCommand");
			if (empty($conf->global->SYSTEMTOOLS_POSTGRESQLDUMP))
			{
				$fullpathofpgdump=$db->getPathOfDump();
			}
			else
			{
				$fullpathofpgdump=$conf->global->SYSTEMTOOLS_POSTGRESQLDUMP;
			}
			?><br>
			<input type="text" name="postgresqldump" style="width: 80%"
				value="<?php echo $fullpathofpgdump; ?>" /></div>


			<br>
			<fieldset><legend><?php echo $langs->trans("ExportOptions"); ?></legend>
			<label for="select_sql_compat"> <?php echo $langs->trans("ExportCompatibility"); ?></label>
			<select name="sql_compat" id="select_sql_compat" class="flat">
				<option value="POSTGRESQL" selected>POSTGRESQL</option>
				<option value="ANSI">ANSI</option>
			</select><br>
			<!-- <input type="checkbox" name="drop_database" value="yes"
				id="checkbox_drop_database" /> <label for="checkbox_drop_database"><?php echo $langs->trans("AddDropDatabase"); ?></label>
			-->
			</fieldset>

			<br>
			<fieldset><legend> <input type="checkbox" name="sql_structure"
				value="structure" id="checkbox_sql_structure" checked /> <label
				for="checkbox_sql_structure"> <?php echo $langs->trans('ExportStructure') ?></label> </legend></fieldset>

			<br>
			<fieldset><legend> <input type="checkbox" name="sql_data" value="data"
				id="checkbox_sql_data" checked /> <label for="checkbox_sql_data">
				<?php echo $langs->trans("Datas"); ?></label> </legend> <input
				type="checkbox" name="showcolumns" value="yes"
				id="checkbox_dump_showcolumns" checked /> <label
				for="checkbox_dump_showcolumns"> <?php echo $langs->trans("NameColumn"); ?></label><br>

			</fieldset>
			</fieldset>
		<?php
		}
		?>
		</div>

		</td>
	</tr>
</table>


<!--<fieldset>
<legend><?php echo $langs->trans("Destination"); ?></legend> -->
<br>
<label for="filename_template"> <?php echo $langs->trans("FileNameToGenerate"); ?></label><br>
<input type="text" name="filename_template" style="width: 90%"
	id="filename_template"
	value="<?php
$prefix='dump';
$ext='.sql';
if (in_array($type, array('mysql', 'mysqli')))  { $prefix='mysqldump'; $ext='sql'; }
//if ($label == 'PostgreSQL') { $prefix='pg_dump'; $ext='dump'; }
if (in_array($type, array('pgsql'))) { $prefix='pg_dump'; $ext='sql'; }
$file=$prefix.'_'.$dolibarr_main_db_name.'_'.dol_sanitizeFileName(DOL_VERSION).'_'.strftime("%Y%m%d%H%M").'.'.$ext;
echo $file;
?>" /> <br>
<br>

<?php

// Define compressions array
$compression=array();
if (in_array($type, array('mysql', 'mysqli')))
{
	$compression['none'] = array('function' => '',       'id' => 'radio_compression_none', 'label' => $langs->trans("None"));
	$compression['gz'] = array('function' => 'gzopen', 'id' => 'radio_compression_gzip', 'label' => $langs->trans("Gzip"));
	//	$compression['zip']= array('function' => 'dol_compress', 'id' => 'radio_compression_zip',  'label' => $langs->trans("FormatZip"));		// Not open source format. Must implement dol_compress function
    $compression['bz'] = array('function' => 'bzopen',       'id' => 'radio_compression_bzip', 'label' => $langs->trans("Bzip2"));
}
else
{
	$compression['none'] = array('function' => '',       'id' => 'radio_compression_none', 'label' => $langs->trans("Default"));
	$compression['gz'] = array('function' => 'gzopen', 'id' => 'radio_compression_gzip', 'label' => $langs->trans("Gzip"));
}

// Show compression choices
print '<div class="formelementrow">';
print "\n";

print $langs->trans("Compression").': &nbsp; ';

foreach($compression as $key => $val)
{
	if (! $val['function'] || function_exists($val['function']))	// Enabled export format
	{
		print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'" checked>';
		print ' <label for="'.$val['id'].'">'.$val['label'].'</label>';
	}
	else	// Disabled export format
	{
		print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'" disabled>';
		print ' <label for="'.$val['id'].'">'.$val['label'].'</label>';
		print ' ('.$langs->trans("NotAvailable").')';
	}
	print ' &nbsp; &nbsp; ';
}

print '</div>';
print "\n";

?><!--</fieldset>--> <!-- End destination -->


<br>
<div class="center">
	<input type="submit" class="button reposition" value="<?php echo $langs->trans("GenerateBackup") ?>" id="buttonGo">
	<input type="hidden" name="page_y" value="<?php echo GETPOST('page_y', 'int'); ?>">
	<br>
<br>

<?php
if (! empty($_SESSION["commandbackuplastdone"]))
{
	print '<br><b>'.$langs->trans("RunCommandSummary").':</b><br>'."\n";
    print '<textarea rows="'.ROWS_2.'" class="centpercent">'.$_SESSION["commandbackuplastdone"].'</textarea><br>'."\n";
    print '<br>';

    //print $paramclear;

    // Now show result
    print '<b>'.$langs->trans("BackupResult").':</b> ';
	print $_SESSION["commandbackupresult"];

	$_SESSION["commandbackuplastdone"]='';
	$_SESSION["commandbackuptorun"]='';
	$_SESSION["commandbackupresult"]='';
}
if (! empty($_SESSION["commandbackuptorun"]))
{
	print '<br><font class="warning">'.$langs->trans("YouMustRunCommandFromCommandLineAfterLoginToUser", $dolibarr_main_db_user, $dolibarr_main_db_user).':</font><br>'."\n";
	print '<textarea id="commandbackuptoruntext" rows="'.ROWS_2.'" class="centpercent">'.$_SESSION["commandbackuptorun"].'</textarea><br>'."\n";
	print ajax_autoselect("commandbackuptoruntext", 0);
	print '<br>';

	//print $paramclear;

	$_SESSION["commandbackuplastdone"]='';
	$_SESSION["commandbackuptorun"]='';
	$_SESSION["commandbackupresult"]='';
}
?>

</div> <!-- end div center button -->

<?php
print '</td></tr>';
print '</table>';


?>

</div> 	<!-- end div fichehalfleft -->


<div id="backupdatabaseright" class="fichehalfright" style="height:480px; overflow: auto;">
<div class="ficheaddleft">

<?php
$filearray=dol_dir_list($conf->admin->dir_output.'/backup', 'files', 0, '', '', $sortfield, (strtolower($sortorder)=='asc'?SORT_ASC:SORT_DESC), 1);
$result=$formfile->list_of_documents($filearray, null, 'systemtools', '', 1, 'backup/', 1, 0, $langs->trans("NoBackupFileAvailable"), 0, $langs->trans("PreviousDumpFiles"));
print '<br>';
?>


</div>
</div>
</form>
</fieldset>

<br>
<!-- Dump of a server -->

<form method="post" action="export_files.php" name="dump">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="export_type" value="server" />

<fieldset><legend class="legendforfieldsetstep" style="font-size: 3em">2</legend>

<?php
print $langs->trans("BackupDesc2", DOL_DATA_ROOT).'<br>';
print $langs->trans("BackupDescX").'<br><br>';

?>

<div id="backupfilesleft" class="fichehalfleft">

<?php

print load_fiche_titre($title?$title:$langs->trans("BackupDumpWizard"));
?>

<label for="zipfilename_template"> <?php echo $langs->trans("FileNameToGenerate"); ?></label><br>
<input type="text" name="zipfilename_template" style="width: 90%"
	id="zipfilename_template"
	value="<?php
$prefix='documents';
$ext='zip';

$file=$prefix.'_'.$dolibarr_main_db_name.'_'.dol_sanitizeFileName(DOL_VERSION).'_'.strftime("%Y%m%d%H%M").'.'.$ext;
echo $file;
?>" /> <br>
<br>


<?php
// Show compression choices
print '<div class="formelementrow">';
print "\n";

print $langs->trans("Compression").': &nbsp; ';
$filecompression = $compression;
array_shift($filecompression);
$filecompression['zip']= array('function' => 'dol_compress_dir', 'id' => 'radio_compression_zip',  'label' => $langs->trans("FormatZip"));

foreach($filecompression as $key => $val)
{
    if (! $val['function'] || function_exists($val['function']))	// Enabled export format
    {
        print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'" checked>';
        print ' <label for="'.$val['id'].'">'.$val['label'].'</label>';
    }
    else	// Disabled export format
    {
        print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'" disabled>';
        print ' <label for="'.$val['id'].'">'.$val['label'].'</label>';
        print ' ('.$langs->trans("NotAvailable").')';
    }
    print ' &nbsp; &nbsp; ';
}

print '</div>';
print "\n";

?>
<br>
<div class="center"><input type="submit" class="button reposition"
	value="<?php echo $langs->trans("GenerateBackup") ?>" id="buttonGo" /><br>
<br>
</div>

</div>

<div id="backupdatabaseright" class="fichehalfright" style="height:480px; overflow: auto;">
<div class="ficheaddleft">

<?php
$filearray=dol_dir_list($conf->admin->dir_output.'/documents', 'files', 0, '', '', $sortfield, (strtolower($sortorder)=='asc'?SORT_ASC:SORT_DESC), 1);
$result=$formfile->list_of_documents($filearray, null, 'systemtools', '', 1, 'documents/', 1, 0, $langs->trans("NoBackupFileAvailable"), 0, $langs->trans("PreviousDumpFiles"));
print '<br>';
?>


</div>
</div>

</fieldset>
</form>

<?php

// End of page
llxFooter();
$db->close();
