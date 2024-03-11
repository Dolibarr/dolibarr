<?php
/* Copyright (C) 2006-2018	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2021	Regis Houssin		<regis.houssin@inodbox.com>
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
 */

/**
 *		\file 		htdocs/admin/tools/dolibarr_export.php
 *		\ingroup	core
 *		\brief      Page to export database
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("admin");

$action = GETPOST('action', 'aZ09');

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "date";
}
if (empty($page) || $page == -1) {
	$page = 0;
}
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'deletefile') {
	if (preg_match('/^backup\//', GETPOST('urlfile', 'alpha'))) {
		$file = $conf->admin->dir_output.'/backup/'.basename(GETPOST('urlfile', 'alpha'));
		$ret = dol_delete_file($file, 1);
		if ($ret) {
			setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
		}
	} else {
		$file = $conf->admin->dir_output.'/documents/'.basename(GETPOST('urlfile', 'alpha'));
		$ret = dol_delete_file($file, 1);
		if ($ret) {
			setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
		}
	}
	$action = '';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$label = $db::LABEL;
$type = $db->type;
//var_dump($db);

$help_url = 'EN:Backups|FR:Sauvegardes|ES:Copias_de_seguridad';
llxHeader('', '', $help_url);

print '<script type="text/javascript">
jQuery(document).ready(function() {';
?>

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
if (in_array($type, array('mysql', 'mysqli'))) {
	print 'jQuery("#radio_dump_mysql").click();';
}
if (in_array($type, array('pgsql'))) {
	print 'jQuery("#radio_dump_postgresql").click();';
}
print "});\n";
print "</script>\n";

$title = $langs->trans("Backup");

print load_fiche_titre($title, '', 'title_setup');
//print_barre_liste($langs->trans("Backup"), '', '', '', '', '', $langs->trans("BackupDesc",DOL_DATA_ROOT), 0, 0, 'title_setup');

print '<div class="center">';
print $langs->trans("BackupDesc", DOL_DATA_ROOT);
print '</div>';
print '<br>';

print "<!-- Dump of a server -->\n";
print '<form method="post" action="export.php" name="dump">';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="export_type" value="server" />';
print '<input type="hidden" name="page_y" value="" />';

print '<fieldset id="fieldsetexport"><legend class="legendforfieldsetstep" style="font-size: 3em">1</legend>';

print '<span class="opacitymedium">';
print $langs->trans("BackupDesc3", $dolibarr_main_db_name).'<br>';
//print $langs->trans("BackupDescY").'<br>';
print '</span>';

print '<br>';

print '<div id="backupdatabaseleft" class="fichehalfleft" >';

$title = $langs->trans("BackupDumpWizard");

print load_fiche_titre($title);

//print '<table class="liste nohover centpercent noborderbottom">';
//print '<tr class="liste_titre">';
//print '<td class="liste_titre">';
print '<span class="opacitymedium">'.$langs->trans("DatabaseName").' : </span><b>'.$dolibarr_main_db_name.'</b><br><br>';
//print '</td>';
//print '</tr>';
//print '<tr class="oddeven nohover"><td class="nohover">';

print '<table class="centpercent noborderbottom">';

print '<tr>';
print '<td class="tdtop nopaddingleftimp">';

print '<div id="div_container_exportoptions">';
print '<fieldset id="exportoptions"><legend>'.$langs->trans("ExportMethod").'</legend>';
if (in_array($type, array('mysql', 'mysqli'))) {
	print '<div class="formelementrow"><input type="radio" name="what" value="mysql" id="radio_dump_mysql" />';
	print '<label for="radio_dump_mysql">MySQL	Dump (mysqldump)</label>';
	print '</div>';
	print '<br>';
	print '<div class="formelementrow"><input type="radio" name="what" value="mysqlnobin" id="radio_dump_mysql_nobin" />';
	print '<label for="radio_dump_mysql_nobin">MySQL Dump (php) '.img_warning($langs->trans('BackupPHPWarning')).'</label>';
	print '</div>';
} elseif (in_array($type, array('pgsql'))) {
	print '<div class="formelementrow"><input type="radio" name="what" value="postgresql" id="radio_dump_postgresql" />';
	print '<label for="radio_dump_postgresql">PostgreSQL Dump (pg_dump)</label>';
	print '</div>';
} else {
	print 'No method available with database '.$label;
}
print '</fieldset>';
print '</div>';

print '</td>';
print '</tr>';

print '<tr>';
print '<td class="tdtop nopaddingleftimp">';

print '<div class="centpercent center margintoponly marginbottomonly">';
print img_picto('', 'setup', 'class="pictofixedwidth"').'<a class="classlink" id="lnk">'.$langs->trans("ShowAdvancedOptions").'...</a>';
print '</div>';

print '<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#lnk").click(function() {
		console.log("We click on link");
		hideoptions(this);
	});
});

function hideoptions(domelem) {
	const div = document.getElementById("div_container_sub_exportoptions");

  	if (div.style.display === "none") {
    	div.style.display = "block";
		domelem.innerText="'.dol_escape_js($langs->transnoentitiesnoconv("HideAdvancedoptions")).'";
  	} else {
    	div.style.display = "none";
		domelem.innerText="'.dol_escape_js($langs->transnoentitiesnoconv("ShowAdvancedOptions")).'...";
	}
}
</script>';


print '<div id="div_container_sub_exportoptions" style="display: none;">';

if (in_array($type, array('mysql', 'mysqli'))) {
	print "<!--  Fieldset mysqldump -->\n";
	print '<fieldset id="mysql_options">';

	print '<fieldset class="formelementrow"><legend>'.$langs->trans("FullPathToMysqldumpCommand").'</legend>';
	if (!getDolGlobalString('SYSTEMTOOLS_MYSQLDUMP')) {
		$fullpathofmysqldump = $db->getPathOfDump();
	} else {
		$fullpathofmysqldump = $conf->global->SYSTEMTOOLS_MYSQLDUMP;
	}
	print '<input type="text" name="mysqldump" style="width: 80%" value="'.$fullpathofmysqldump.'">';
	print '</fieldset>';

	print '<br>';
	print '<fieldset><legend>'.$langs->trans("ExportOptions").'</legend>';

	if (getDolGlobalString('MYSQL_OLD_OPTION_DISABLE_FK')) {
		print '<div class="formelementrow">';
		print '<input type="checkbox" name="disable_fk" value="yes" id="checkbox_disable_fk" checked>';
		print '<label for="checkbox_disable_fk">'.$langs->trans("CommandsToDisableForeignKeysForImport").' '.img_info($langs->trans('CommandsToDisableForeignKeysForImportWarning')).'</label>';
		print '</div>';
	}

	print '<label for="select_sql_compat">'.$langs->trans("ExportCompatibility").'</label>';

	print '<select name="sql_compat" id="select_sql_compat" class="flat">';
	print '<option value="NONE" selected>NONE</option>';
	print '<option value="ANSI">ANSI</option>';
	print '<option value="DB2">DB2</option>';
	print '<option value="MAXDB">MAXDB</option>';
	print '<option value="MYSQL323">MYSQL323</option>';
	print '<option value="MYSQL40">MYSQL40</option>';
	print '<option value="MSSQL">MSSQL</option>';
	print '<option value="ORACLE">ORACLE</option>';
	print '<option value="POSTGRESQL">POSTGRESQL</option>';
	print '</select>';
	print '<br><br>';

	print '<div class="formelementrow">';
	print '<input type="checkbox" name="use_transaction" value="yes" id="checkbox_use_transaction" checked="checked">';
	print '<label for="checkbox_use_transaction">'.$langs->trans("UseTransactionnalMode").'</label>';
	print '</div>';

	print '<input type="checkbox" name="use_mysql_quick_param" value="yes" id="checkbox_use_quick" checked="checked" />';
	print '<label for="checkbox_use_quick">';
	print $form->textwithpicto($langs->trans('ExportUseMySQLQuickParameter'), $langs->trans('ExportUseMySQLQuickParameterHelp'));
	print '</label>';
	print '<br>';

	print '<input type="checkbox" name="use_force" value="no" id="checkbox_use_force" />';
	print '<label for="checkbox_use_force">';
	print $form->textwithpicto($langs->trans('ExportUseForce'), $langs->trans('ExportUseForceHelp'));
	print '</label>';
	print '<br>';

	$execmethod = 0;
	if (getDolGlobalString('MAIN_EXEC_USE_POPEN')) {
		$execmethod = $conf->global->MAIN_EXEC_USE_POPEN;
	}
	if (empty($execmethod)) {
		$execmethod = 1;
	}
	if ($execmethod == 1) {
		// If we use the "exec" method for shell, we ask if we need to use the alternative low memory exec mode.
		print '<input type="checkbox" name="lowmemorydump" value="yes" id="lowmemorydump"'.((GETPOSTISSET('lowmemorydump') ? GETPOST('lowmemorydump', 'alpha') : getDolGlobalString('MAIN_LOW_MEMORY_DUMP')) ? ' checked="checked"' : '').'" />';
		print '<label for="lowmemorydump">';
		print $form->textwithpicto($langs->trans('ExportUseLowMemoryMode'), $langs->trans('ExportUseLowMemoryModeHelp'));
		print '</label>';
		print '<br>';
	}

	print '<!-- <input type="checkbox" name="drop_database" value="yes" id="checkbox_drop_database" />';
	print '<label for="checkbox_drop_database">'.$langs->trans("AddDropDatabase").'</label>';
	print '-->';
	print '</fieldset>';

	print '<br>';
	print '<fieldset>';
	print '<legend>';
	print '<input type="checkbox" name="sql_structure" value="structure" id="checkbox_sql_structure" checked />';
	print '<label for="checkbox_sql_structure">'.$langs->trans('ExportStructure').'</label>';
	print '</legend>';

	print '<input type="checkbox" name="drop"'.((!GETPOSTISSET("drop") || GETPOST('drop')) ? ' checked' : '').' id="checkbox_dump_drop" />';
	print '<label for="checkbox_dump_drop">'.$langs->trans("AddDropTable").'</label>';
	print '<br>';
	print '</fieldset>';

	print '<br>';
	print '<fieldset>';
	print '<legend>';
	print '<input type="checkbox" name="sql_data" value="data" id="checkbox_sql_data" checked />';
	print '<label for="checkbox_sql_data">'.$langs->trans("Datas").'</label>';
	print '</legend>';
	print '<input type="checkbox" name="showcolumns" value="yes" id="checkbox_dump_showcolumns" checked />';
	print '<label for="checkbox_dump_showcolumns">'.$langs->trans("NameColumn").'</label>';
	print '<br>';

	print '<input type="checkbox" name="extended_ins" value="yes" id="checkbox_dump_extended_ins" checked />';
	print '<label for="checkbox_dump_extended_ins">'.$langs->trans("ExtendedInsert").'</label>';
	print '<br>';

	print '<input type="checkbox" name="disable-add-locks" value="no" id="checkbox_dump_disable-add-locks" />';
	print '<label for="checkbox_dump_disable-add-locks">'.$langs->trans("NoLockBeforeInsert").'</label>';
	print '<br>';

	print '<input type="checkbox" name="delayed" value="yes" id="checkbox_dump_delayed" />';
	print '<label for="checkbox_dump_delayed">'.$langs->trans("DelayedInsert").'</label>';
	print '<br>';

	print '<input type="checkbox" name="sql_ignore" value="yes" id="checkbox_dump_ignore" />';
	print '<label for="checkbox_dump_ignore">'.$langs->trans("IgnoreDuplicateRecords").'</label>';
	print '<br>';

	print '<input type="checkbox" name="hexforbinary" value="yes" id="checkbox_hexforbinary" checked />';
	print '<label for="checkbox_hexforbinary">'.$langs->trans("EncodeBinariesInHexa").'</label>';
	print '<br>';

	print '<input type="checkbox" name="charset_utf8" value="yes" id="checkbox_charset_utf8" checked disabled />';
	print '<label for="checkbox_charset_utf8">'.$langs->trans("UTF8").'</label>';
	print '<br>';

	print '</fieldset>';
	print '</fieldset>';

	// Export mysql bin
	print "<!--  Fieldset mysql_nobin -->\n";
	print '<fieldset id="mysql_nobin_options">';

	print '<fieldset>';
	print '<legend>'.$langs->trans("ExportOptions").'</legend>';

	print '<div class="formelementrow">';
	print '<input type="checkbox" name="nobin_use_transaction" value="yes" id="checkbox_use_transaction" />';
	print '<label for="checkbox_use_transaction">'.$langs->trans("UseTransactionnalMode").'</label>';
	print '</div>';

	if (getDolGlobalString('MYSQL_OLD_OPTION_DISABLE_FK')) {
		print '<div class="formelementrow">';
		print '<input type="checkbox" name="nobin_disable_fk" value="yes" id="checkbox_disable_fk" checked />';
		print '<label for="checkbox_disable_fk">'.$langs->trans("CommandsToDisableForeignKeysForImport").' '.img_info($langs->trans('CommandsToDisableForeignKeysForImportWarning')).'</label>';
		print '</div>';
	}
	print '</fieldset>';

	print '<br>';
	print '<fieldset><legend>'.$langs->trans('ExportStructure').'</legend>';
	print '<input type="checkbox" name="nobin_drop"'.((!GETPOSTISSET("nobin_drop") || GETPOST('nobin_drop')) ? ' checked' : '').' id="checkbox_dump_drop" />';
	print '<label for="checkbox_dump_drop">'.$langs->trans("AddDropTable").'</label>';
	print '<br>';
	print '</fieldset>';

	print '<br>';
	print '<fieldset>';
	print '<legend>'.$langs->trans("Datas").'</legend>';

	print '<input type="checkbox" name="nobin_nolocks" value="no" id="checkbox_dump_disable-add-locks" />';
	print '<label for="checkbox_dump_disable-add-locks">'.$langs->trans("NoLockBeforeInsert").'</label>';
	print '<br>';

	print '<input type="checkbox" name="nobin_delayed" value="yes" id="checkbox_dump_delayed" />';
	print '<label for="checkbox_dump_delayed">'.$langs->trans("DelayedInsert").'</label>';
	print '<br>';

	print '<input type="checkbox" name="nobin_sql_ignore" value="yes" id="checkbox_dump_ignore" />';
	print '<label for="checkbox_dump_ignore">'.$langs->trans("IgnoreDuplicateRecords").'</label>';
	print '<br>';

	print '<input type="checkbox" name="nobin_charset_utf8" value="yes" id="checkbox_charset_utf8" checked disabled />';
	print '<label for="checkbox_charset_utf8">'.$langs->trans("UTF8").'</label>';
	print '<br>';

	print '</fieldset>';
	print '</fieldset>';
}

if (in_array($type, array('pgsql'))) {
	print "<!--  Fieldset pg_dump -->\n";
	print '<fieldset id="postgresql_options">';


	print '<fieldset class="formelementrow"><legend>'.$langs->trans("FullPathToPostgreSQLdumpCommand").'</legend>';
	if (!getDolGlobalString('SYSTEMTOOLS_POSTGRESQLDUMP')) {
		$fullpathofpgdump = $db->getPathOfDump();
	} else {
		$fullpathofpgdump = $conf->global->SYSTEMTOOLS_POSTGRESQLDUMP;
	}
	print '<br>';
	print '<input type="text" name="postgresqldump" style="width: 80%" value="'.$fullpathofpgdump.'" />';
	print '</fieldset>';

	print '<br>';
	print '<fieldset>';
	print '<legend>'.$langs->trans("ExportOptions").'</legend>';
	print '<label for="select_sql_compat">'.$langs->trans("ExportCompatibility").'</label>';
	print '<select name="sql_compat" id="select_sql_compat" class="flat">';
	print '<option value="POSTGRESQL" selected>POSTGRESQL</option>';
	print '<option value="ANSI">ANSI</option>';
	print '</select>';
	print '<br>';
	print '<!-- <input type="checkbox" name="drop_database" value="yes" id="checkbox_drop_database" />';
	print '<label for="checkbox_drop_database">'.$langs->trans("AddDropDatabase").'</label>';
	print '-->';
	print '</fieldset>';
	print '<br>';
	print '<fieldset>';
	print '<legend>';
	print '<input type="checkbox" name="sql_structure" value="structure" id="checkbox_sql_structure" checked />';
	print '<label for="checkbox_sql_structure">'.$langs->trans('ExportStructure').'</label>';
	print '</legend>';
	print '</fieldset>';
	print '<br>';
	print '<fieldset>';
	print '<legend>';
	print '<input type="checkbox" name="sql_data" value="data" id="checkbox_sql_data" checked />';
	print '<label for="checkbox_sql_data">'.$langs->trans("Datas").'</label>';
	print '</legend>';
	print '<input type="checkbox" name="showcolumns" value="yes" id="checkbox_dump_showcolumns" checked />';
	print '<label for="checkbox_dump_showcolumns">'.$langs->trans("NameColumn").'</label>';
	print '<br>';
	print '</fieldset>';
	print '</fieldset>';
}
print '</div>';

print '</td>';
print '</tr>';
print '</table>';




print '<!--<fieldset>';
print '<legend>'.$langs->trans("Destination").'</legend> -->';
print '<br>';
print '<label for="filename_template" class="line-height-large opacitymedium">'.$langs->trans("FileNameToGenerate").'</label>';
print '<br>';
$prefix = 'dump';
$ext = '.sql';
if (in_array($type, array('mysql', 'mysqli'))) {
	$prefix = 'mysqldump';
	$ext = 'sql';
}
//if ($label == 'PostgreSQL') {
//	$prefix='pg_dump';
//	$ext='dump';
//}
if (in_array($type, array('pgsql'))) {
	$prefix = 'pg_dump';
	$ext = 'sql';
}
$file = $prefix.'_'.$dolibarr_main_db_name.'_'.dol_sanitizeFileName(DOL_VERSION).'_'.dol_print_date(dol_now('gmt'), "dayhourlogsmall", 'tzuser').'.'.$ext;
print '<input type="text" name="filename_template" style="width: 90%" id="filename_template" value="'.$file.'" />';
print '<br>';
print '<br>';

// Define compressions array
$compression = array();
if (in_array($type, array('mysql', 'mysqli'))) {
	$compression['gz'] = array(
		'function' => 'gzopen',
		'id' => 'radio_compression_gzip',
		'label' => $langs->trans("Gzip")
	);
	// Not open source format. Must implement dol_compress function
	// $compression['zip']= array(
	//     'function' => 'dol_compress',
	//     'id' => 'radio_compression_zip',
	//     'label' => $langs->trans("FormatZip")
	// );
	$compression['bz'] = array(
	'function' => 'bzopen',
	'id' => 'radio_compression_bzip',
	'label' => $langs->trans("Bzip2")
	);
	$compression['zstd'] = array(
		'function' => 'zstd_compress',
		'id' => 'radio_compression_zstd',
		'label' => $langs->trans("Zstd")
	);
	$compression['none'] = array(
		'function' => '',
		'id' => 'radio_compression_none',
		'label' => $langs->trans("None")
	);
} else {
	$compression['none'] = array(
		'function' => '',
		'id' => 'radio_compression_none',
		'label' => $langs->trans("None")
	);
	$compression['gz'] = array(
		'function' => 'gzopen',
		'id' => 'radio_compression_gzip',
		'label' => $langs->trans("Gzip")
	);
}

// Show compression choices
print '<div class="formelementrow">';
print "\n";

print $langs->trans("Compression").': &nbsp; ';

$i = 0;
foreach ($compression as $key => $val) {
	if (!$val['function'] || function_exists($val['function'])) {
		// Enabled export format
		$checked = '';
		if ($key == 'gz') {
			$checked = ' checked';
		}
		print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'"'.$checked.'>';
		print ' <label for="'.$val['id'].'">'.$val['label'].'</label>';
	} else {
		// Disabled export format
		print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'" disabled>';
		print ' <label for="'.$val['id'].'">'.$val['label'].'</label>';
		print ' <span class="opacitymedium">('.$langs->trans("NotAvailable").')</span>';
	}
	print ' &nbsp; &nbsp; ';
	$i++;
}

print '</div>';
print "\n";

print "<!--</fieldset>--> <!-- End destination -->\n";

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button reposition" value="'.$langs->trans("GenerateBackup").'" id="buttonGo">';
print '<br>';
print '<br>';

if (!empty($_SESSION["commandbackuplastdone"])) {
	print '<br><b>'.$langs->trans("RunCommandSummary").':</b><br>'."\n";
	print '<textarea rows="'.ROWS_2.'" class="centpercent">'.$_SESSION["commandbackuplastdone"].'</textarea><br>'."\n";
	print '<br>';

	//print $paramclear;

	// Now show result
	print '<b>'.$langs->trans("BackupResult").':</b> ';
	print $_SESSION["commandbackupresult"];

	$_SESSION["commandbackuplastdone"] = '';
	$_SESSION["commandbackuptorun"] = '';
	$_SESSION["commandbackupresult"] = '';
}
if (!empty($_SESSION["commandbackuptorun"])) {
	print '<br><span class="warning">'.$langs->trans("YouMustRunCommandFromCommandLineAfterLoginToUser", $dolibarr_main_db_user, $dolibarr_main_db_user).':</span><br>'."\n";
	print '<textarea id="commandbackuptoruntext" rows="'.ROWS_2.'" class="centpercent">'.$_SESSION["commandbackuptorun"].'</textarea><br>'."\n";
	print ajax_autoselect("commandbackuptoruntext", 0);
	print '<br>';

	//print $paramclear;

	$_SESSION["commandbackuplastdone"] = '';
	$_SESSION["commandbackuptorun"] = '';
	$_SESSION["commandbackupresult"] = '';
}

print "</div> <!-- end div center button -->\n";

//print '</td></tr>';
//print '</table>';

print "</div> 	<!-- end div fichehalfleft -->\n";


print '<div id="backupdatabaseright" class="fichehalfright">';

$filearray = dol_dir_list($conf->admin->dir_output.'/backup', 'files', 0, '', '', $sortfield, (strtolower($sortorder) == 'asc' ? SORT_ASC : SORT_DESC), 1);
$result = $formfile->list_of_documents($filearray, null, 'systemtools', '', 1, 'backup/', 1, 0, $langs->trans("NoBackupFileAvailable"), 0, $langs->trans("PreviousDumpFiles"), '', 0, -1, '', '', 'ASC', 1, 0, -1, 'style="height:250px; overflow: auto;"');
print '<br>';

print '</div>';
print '</form>';
print '</fieldset>';


$title = $langs->trans("BackupZipWizard");

print "<br>\n";
print "<!-- Dump of a server -->\n";

print '<form method="post" action="export_files.php" name="dump">';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="export_type" value="server" />';
print '<input type="hidden" name="page_y" value="" />';

print '<fieldset><legend class="legendforfieldsetstep" style="font-size: 3em">2</legend>';

print '<span class="opacitymedium">';
print $langs->trans("BackupDesc2", DOL_DATA_ROOT).'<br>';
print $langs->trans("BackupDescX").'<br><br>';
print '</span>';

print '<div id="backupfilesleft" class="fichehalfleft">';

print load_fiche_titre($title);

print '<label for="zipfilename_template" class="line-height-large paddingbottom opacitymedium">'.$langs->trans("FileNameToGenerate").'</label><br>';
$prefix = 'documents';
$ext = 'zip';
$file = $prefix.'_'.$dolibarr_main_db_name.'_'.dol_sanitizeFileName(DOL_VERSION).'_'.dol_print_date(dol_now('gmt'), "dayhourlogsmall", 'tzuser');
print '<input type="text" name="zipfilename_template" style="width: 90%" id="zipfilename_template" value="'.$file.'" /> <br>';
print '<br>';


// Show compression choices
// Example: With gz choice, you can compress in 5mn, a file of 2GB directory (after compression) with 10 Mb memory.
print '<div class="formelementrow">';
print "\n";

print $langs->trans("Compression").': &nbsp; ';
$filecompression = $compression;
unset($filecompression['none']);
$filecompression['zip'] = array('function' => 'dol_compress_dir', 'id' => 'radio_compression_zip', 'label' => $langs->trans("FormatZip"));

$i = 0;
foreach ($filecompression as $key => $val) {
	if (!$val['function'] || function_exists($val['function'])) {	// Enabled export format
		$checked = '';
		if ($key == 'gz') {
			$checked = ' checked';
		}
		print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'2"'.$checked.'>';
		print ' <label for="'.$val['id'].'2">'.$val['label'].'</label>';
	} else { // Disabled export format
		print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'2" disabled>';
		print ' <label for="'.$val['id'].'2">'.$val['label'].'</label>';
		print ' <span class="opacitymedium">('.$langs->trans("NotAvailable").')</span>';
	}
	print ' &nbsp; &nbsp; ';
	$i++;
}

print '</div>';
print "\n";

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button reposition" value="'.$langs->trans("GenerateBackup").'" id="buttonGo" /><br>';
print '<br>';
print '</div>';

print '</div>';

print '<div id="backupfileright" class="fichehalfright">';

$filearray = dol_dir_list($conf->admin->dir_output.'/documents', 'files', 0, '', '', $sortfield, (strtolower($sortorder) == 'asc' ? SORT_ASC : SORT_DESC), 1);
$result = $formfile->list_of_documents($filearray, null, 'systemtools', '', 1, 'documents/', 1, 0, $langs->trans("NoBackupFileAvailable"), 0, $langs->trans("PreviousArchiveFiles"), '', 0, -1, '', '', 'ASC', 1, 0, -1, 'style="height:250px; overflow: auto;"');
print '<br>';

print '</div>';

print '</fieldset>';
print '</form>';

print '<br>';

// End of page
llxFooter();
$db->close();
