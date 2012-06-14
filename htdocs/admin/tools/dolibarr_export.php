<?php
/* Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file 		htdocs/admin/tools/dolibarr_export.php
 *		\ingroup	core
 *		\brief      Page to export database
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load("admin");

$action=GETPOST('action');

$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
$page = GETPOST("page");
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="date";
if ($page < 0) { $page = 0; }
$limit = $conf->liste_limit;
$offset = $limit * $page;

if (! $user->admin) accessforbidden();



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

$form=new Form($db);
$formfile = new FormFile($db);

$label=$db->label;

$help_url='EN:Backups|FR:Sauvegardes|ES:Copias_de_seguridad';
llxHeader('','',$help_url);

?>
<script type="text/javascript">
jQuery(document).ready(function() {

	function hideoptions () {
		jQuery("#mysql_options").hide();
		jQuery("#mysql_options_nobin").hide();
		jQuery("#postgresql_options").hide();
	}

	hideoptions();
	jQuery("#radio_dump_mysql").click(function() {
		hideoptions();
		jQuery("#mysql_options").show();
	});
	jQuery("#radio_dump_mysql_nobin").click(function() {
		hideoptions();
		jQuery("#mysql_options_nobin").show();
	});
	jQuery("#radio_dump_postgresql").click(function() {
		hideoptions();
		jQuery("#postgresql_options").show();
	});
	jQuery("#select_sql_compat").click(function() {
		if (jQuery("#select_sql_compat").val() == 'POSTGRESQL')
		{
			jQuery("#checkbox_dump_disable-add-locks").attr('checked',true);
		};
	});

	<?php
	    if ($label == 'MySQL')      print 'jQuery("#radio_dump_mysql").click();';
	    if ($label == 'PostgreSQL') print 'jQuery("#radio_dump_postgresql").click();';
	?>
});
</script>
<?php

print_fiche_titre($langs->trans("Backup"),'','setup');

print $langs->trans("BackupDesc",DOL_DATA_ROOT).'<br><br>';
print $langs->trans("BackupDesc2",DOL_DATA_ROOT).'<br>';
print $langs->trans("BackupDescX").'<br><br>';
print $langs->trans("BackupDesc3",DOL_DATA_ROOT).'<br>';
print $langs->trans("BackupDescY").'<br><br>';

if ($_GET["msg"])
{
	print '<div class="error">'.$_GET["msg"].'</div>';
	print '<br>';
	print "\n";
}


?>

<!-- Dump of a server -->
<form method="post" action="export.php" name="dump"><input type="hidden"
	name="token" value="<?php echo $_SESSION['newtoken']; ?>" /> <input
	type="hidden" name="export_type" value="server" />

<fieldset id="fieldsetexport">
<?php print '<legend>'.$langs->trans("DatabaseName").' : <b>'.$dolibarr_main_db_name.'</b></legend>'; ?>
<table>
	<tr>
		<td valign="top">

		<div id="div_container_exportoptions">
		<fieldset id="exportoptions"><legend><?php echo $langs->trans("ExportMethod"); ?></legend>
		<?php
		if ($label == 'MySQL')
		{
			?>
			<div class="formelementrow"><input type="radio" name="what" value="mysql" id="radio_dump_mysql" />
			<label for="radio_dump_mysql">MySQL	Dump (mysqldump)</label>
			</div>
			<br>
			<div class="formelementrow"><input type="radio" name="what" value="mysqlnobin" id="radio_dump_mysql_nobin" />
			<label for="radio_dump_mysql">MySQL	Dump (php) <?php print img_warning('Backup can\'t be guaranted with this method. Prefer previous one'); ?></label>
			</div>
			<?php
		}
		else if ($label == 'PostgreSQL')
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
		<td valign="top">


		<div id="div_container_sub_exportoptions">
		<?php
		if ($label == 'MySQL')
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
			<input type="text" name="mysqldump" size="80"
				value="<?php echo $fullpathofmysqldump; ?>" /></div>

			<br>
			<fieldset><legend><?php echo $langs->trans("ExportOptions"); ?></legend>
			<div class="formelementrow"><input type="checkbox"
				name="use_transaction" value="yes" id="checkbox_use_transaction" /> <label
				for="checkbox_use_transaction"> <?php echo $langs->trans("UseTransactionnalMode"); ?></label>

			</div>

			<div class="formelementrow"><input type="checkbox" name="disable_fk"
				value="yes" id="checkbox_disable_fk" checked="checked" /> <label
				for="checkbox_disable_fk"> <?php echo $langs->trans("CommandsToDisableForeignKeysForImport"); ?></label>
			</div>
			<label for="select_sql_compat"> <?php echo $langs->trans("ExportCompatibility"); ?></label>

			<select name="sql_compat" id="select_sql_compat" class="flat">
				<option value="NONE" selected="selected">NONE</option>
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
				value="structure" id="checkbox_sql_structure" checked="checked" /> <label
				for="checkbox_sql_structure"> Structure</label> </legend> <input
				type="checkbox" name="drop" value="1" id="checkbox_dump_drop" /> <label
				for="checkbox_dump_drop"><?php echo $langs->trans("AddDropTable"); ?></label><br>
			</fieldset>

			<br>
			<fieldset><legend> <input type="checkbox" name="sql_data" value="data"
				id="checkbox_sql_data" checked="checked" /> <label for="checkbox_sql_data">
				<?php echo $langs->trans("Datas"); ?></label> </legend> <input
				type="checkbox" name="showcolumns" value="yes"
				id="checkbox_dump_showcolumns" checked="checked" /> <label
				for="checkbox_dump_showcolumns"> <?php echo $langs->trans("NameColumn"); ?></label><br>

			<input type="checkbox" name="extended_ins" value="yes"
				id="checkbox_dump_extended_ins" checked="checked" /> <label
				for="checkbox_dump_extended_ins"> <?php echo $langs->trans("ExtendedInsert"); ?></label><br>

			<input type="checkbox" name="disable-add-locks" value="no"
				id="checkbox_dump_disable-add-locks" /> <label
				for="checkbox_dump_disable-add-locks"> <?php echo $langs->trans("NoLockBeforeInsert"); ?></label><br>

			<input type="checkbox" name="delayed" value="yes"
				id="checkbox_dump_delayed" /> <label for="checkbox_dump_delayed"> <?php echo $langs->trans("DelayedInsert"); ?></label><br>

			<input type="checkbox" name="sql_ignore" value="yes"
				id="checkbox_dump_ignore" /> <label for="checkbox_dump_ignore"> <?php echo $langs->trans("IgnoreDuplicateRecords"); ?></label><br>

			<input type="checkbox" name="hexforbinary" value="yes"
				id="checkbox_hexforbinary" checked="checked" /> <label
				for="checkbox_hexforbinary"> <?php echo $langs->trans("EncodeBinariesInHexa"); ?></label><br>

			<input type="checkbox" name="charset_utf8" value="yes"
				id="checkbox_charset_utf8" checked="checked" disabled="disabled" /> <label
				for="checkbox_charset_utf8"> <?php echo $langs->trans("UTF8"); ?></label><br>

			</fieldset>
			</fieldset>
		<?php
		}

		if ($label == 'PostgreSQL')
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
			<input type="text" name="postgresqldump" size="80"
				value="<?php echo $fullpathofpgdump; ?>" /></div>


			<br>
			<fieldset><legend><?php echo $langs->trans("ExportOptions"); ?></legend>
			<label for="select_sql_compat"> <?php echo $langs->trans("ExportCompatibility"); ?></label>
			<select name="sql_compat" id="select_sql_compat" class="flat">
				<option value="POSTGRESQL" selected="selected">POSTGRESQL</option>
				<option value="ANSI">ANSI</option>
			</select><br>
			<!-- <input type="checkbox" name="drop_database" value="yes"
				id="checkbox_drop_database" /> <label for="checkbox_drop_database"><?php echo $langs->trans("AddDropDatabase"); ?></label>
			-->
			</fieldset>

			<br>
			<fieldset><legend> <input type="checkbox" name="sql_structure"
				value="structure" id="checkbox_sql_structure" checked="checked" /> <label
				for="checkbox_sql_structure"> Structure</label> </legend></fieldset>

			<br>
			<fieldset><legend> <input type="checkbox" name="sql_data" value="data"
				id="checkbox_sql_data" checked="checked" /> <label for="checkbox_sql_data">
				<?php echo $langs->trans("Datas"); ?></label> </legend> <input
				type="checkbox" name="showcolumns" value="yes"
				id="checkbox_dump_showcolumns" checked="checked" /> <label
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

</fieldset>



<fieldset><label for="filename_template"> <?php echo $langs->trans("FileNameToGenerate"); ?></label>:
 <input type="text" name="filename_template" size="60"
	id="filename_template"
	value="<?php
$prefix='dump';
if ($label == 'MySQL')      $prefix='mysqldump';
if ($label == 'PostgreSQL') $prefix='pg_dump';
$file=$prefix.'_'.$dolibarr_main_db_name.'_'.dol_sanitizeFileName(DOL_VERSION).'_'.strftime("%Y%m%d%H%M").'.sql';
echo $file;
?>" /> <br>
<br>

<?php

// Define compressions array
$compression=array(
	'none' => array('function' => '',       'id' => 'radio_compression_none', 'label' => $langs->trans("None")),
	'gz'   => array('function' => 'gzopen', 'id' => 'radio_compression_gzip', 'label' => $langs->trans("Gzip")),
);
if ($label == 'MySQL')
{
//	$compression['zip']= array('function' => 'dol_compress', 'id' => 'radio_compression_zip',  'label' => $langs->trans("FormatZip"));		// Not open source format. Must implement dol_compress function
    $compression['bz'] = array('function' => 'bzopen',       'id' => 'radio_compression_bzip', 'label' => $langs->trans("Bzip2"));
}


// Show compression choices
print '<div class="formelementrow">';
print "\n";

print $langs->trans("Compression").': &nbsp; ';

foreach($compression as $key => $val)
{
	if (! $val['function'] || function_exists($val['function']))	// Enabled export format
	{
		print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'" checked="checked">';
		print ' <label for="'.$val['id'].'">'.$val['label'].'</label>';
	}
	else	// Disabled export format
	{
		print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'" disabled="disabled">';
		print ' <label for="'.$val['id'].'">'.$val['label'].'</label>';
		print ' ('.$langs->trans("NotAvailable").')';
	}
	print ' &nbsp; &nbsp; ';
}

print '</div>';
print "\n";

?></fieldset>


<div align="center"><input type="submit" class="button"
	value="<?php echo $langs->trans("GenerateBackup") ?>" id="buttonGo" /><br>
<br>
</div>


</form>

<?php

$filearray=dol_dir_list($conf->admin->dir_output.'/backup','files',0,'','',$sortfield,(strtolower($sortorder)=='asc'?SORT_ASC:SORT_DESC),1);
$result=$formfile->list_of_documents($filearray,null,'systemtools','',1,'backup/',1,0,$langs->trans("NoBackupFileAvailable"),0,$langs->trans("PreviousDumpFiles"));
print '<br>';


llxFooter();

$db->close();
?>