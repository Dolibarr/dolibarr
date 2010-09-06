<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *		\file 		htdocs/admin/tools/dolibarr_import.php
 *		\ingroup	core
 * 		\brief      Page to import database
 *		\version    $Id$
 */

require("../../main.inc.php");

$langs->load("admin");
$langs->load("other");

if (! $user->admin)
  accessforbidden();


/*
 * View
 */

llxHeader('','','EN:Restores|FR:Restaurations|ES:Restauraciones');

?>
<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	jQuery("#mysql_options").<?php echo GETPOST('radio_dump')=='mysql_options'?'show()':'hide()'; ?>;
	jQuery("#postgresql_options").<?php echo GETPOST('radio_dump')=='postgresql_options'?'show()':'hide()'; ?>;

	jQuery("#radio_dump_mysql").click(function() {
		jQuery("#mysql_options").show();
	});
	jQuery("#radio_dump_postgresql").click(function() {
		jQuery("#postgresql_options").show();
	});
});
</script>
<?php

print_fiche_titre($langs->trans("Restore"),'','setup');

print $langs->trans("RestoreDesc",DOL_DATA_ROOT).'<br><br>';
print $langs->trans("RestoreDesc2",DOL_DATA_ROOT).'<br><br>';
print $langs->trans("RestoreDesc3",DOL_DATA_ROOT).'<br><br>';

?>

<fieldset id="fieldsetexport">
<?php print '<legend>'.$langs->trans("DatabaseName").' : <b>'.$dolibarr_main_db_name.'</b></legend>'; ?>
<table><tr><td valign="top">

<?php if ($conf->use_javascript_ajax) { ?>
<div id="div_container_exportoptions">
<fieldset id="exportoptions">
	<legend><?php echo $langs->trans("ImportMethod"); ?></legend>
    <div class="formelementrow">
        <input type="radio" name="what" value="mysql" id="radio_dump_mysql"
        	<?php echo ($_GET["radio_dump"]=='mysql_options'?' checked':''); ?>
            />
            <label for="radio_dump_mysql">MySQL</label>
    </div>
</fieldset>
</div>
<?php } ?>

</td><td valign="top">


<div id="div_container_sub_exportoptions">
<?php
if ($db->label == 'MySQL')
{
?>
	<fieldset id="mysql_options">
    <legend>Import MySql</legend>
	<div class="formelementrow">
	<?php
	// Parameteres execution
	$command=$db->getPathOfRestore();
	if (preg_match("/\s/",$command)) $command=$command=escapeshellarg($command);	// Use quotes on command

	$param=$dolibarr_main_db_name;
	$param.=" -h ".$dolibarr_main_db_host;
	if (! empty($dolibarr_main_db_port)) $param.=" -P ".$dolibarr_main_db_port;
	$param.=" -u ".$dolibarr_main_db_user;
	$paramcrypted=$param;
	$paramclear=$param;
	if (! empty($dolibarr_main_db_pass))
	{
		$paramcrypted.=" -p".preg_replace('/./i','*',$dolibarr_main_db_pass);
		$paramclear.=" -p".$dolibarr_main_db_pass;
	}

	echo $langs->trans("ImportMySqlDesc");
	print '<br>';
	print '<textarea rows="1" cols="120">'.$langs->trans("ImportMySqlCommand",$command,$_GET["showpass"]?$paramclear:$paramcrypted).'</textarea><br>';

	if (empty($_GET["showpass"]) && $dolibarr_main_db_pass) print '<br><a href="'.$_SERVER["PHP_SELF"].'?showpass=1&amp;radio_dump=mysql_options">'.$langs->trans("UnHidePassword").'</a>';
	//else print '<br><a href="'.$_SERVER["PHP_SELF"].'?showpass=0&amp;radio_dump=mysql_options">'.$langs->trans("HidePassword").'</a>';
	?>
	</div>
<?php
}
?>

	</fieldset>
</div>


</td></tr></table>
</fieldset>

<?php

$db->close();

llxFooter('$Date$ - $Revision$');
?>