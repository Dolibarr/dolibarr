<?php
/* Copyright (C) 2006-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin		<regis.houssin@inodbox.com>
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
 *		\file 		htdocs/admin/tools/dolibarr_import.php
 *		\ingroup	core
 * 		\brief      Page to import database
 */

require '../../main.inc.php';

// Load translation files required by the page
$langs->loadLangs(array("other","admin"));

if (! $user->admin)
	accessforbidden();

$radio_dump=GETPOST('radio_dump');
$showpass=GETPOST('showpass');


/*
 * View
 */

$label=$db::LABEL;
$type=$db->type;


$help_url='EN:Restores|FR:Restaurations|ES:Restauraciones';
llxHeader('','',$help_url);

?>
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#mysql_options").<?php echo $radio_dump=='mysql_options'?'show()':'hide()'; ?>;
	jQuery("#postgresql_options").<?php echo $radio_dump=='postgresql_options'?'show()':'hide()'; ?>;

	jQuery("#radio_dump_mysql").click(function() {
		jQuery("#mysql_options").show();
	});
	jQuery("#radio_dump_postgresql").click(function() {
		jQuery("#postgresql_options").show();
	});
	<?php
	    if ($label == 'MySQL')      print 'jQuery("#radio_dump_mysql").click();';
	    if ($label == 'PostgreSQL') print 'jQuery("#radio_dump_postgresql").click();';
	?>
});
</script>
<?php

print load_fiche_titre($langs->trans("Restore"),'','title_setup');

print '<div class="center">';
print $langs->trans("RestoreDesc",DOL_DATA_ROOT);
print '</div>';
print '<br>';

?>
<fieldset>
<legend style="font-size: 3em">1</legend>
<?php
print $langs->trans("RestoreDesc2",DOL_DATA_ROOT).'<br><br>';
?>
</fieldset>

<br>

<fieldset>
<legend style="font-size: 3em">2</legend>
<?php
print $langs->trans("RestoreDesc3",$dolibarr_main_db_name).'<br><br>';
?>

<?php print $langs->trans("DatabaseName").' : <b>'.$dolibarr_main_db_name.'</b>'; ?><br><br>

<table class="centpercent"><tr><td class="tdtop">

<?php if ($conf->use_javascript_ajax) { ?>
<div id="div_container_exportoptions">
<fieldset id="exportoptions">
	<legend><?php echo $langs->trans("ImportMethod"); ?></legend>
    <?php
    if (in_array($type, array('mysql', 'mysqli')))
    {
    ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="mysql" id="radio_dump_mysql"<?php echo ($radio_dump=='mysql_options'?' checked':''); ?> />
        <label for="radio_dump_mysql">MySQL (mysql)</label>
    </div>
    <?php
    }
    else if (in_array($type, array('pgsql')))
    {
    ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="mysql" id="radio_dump_postgresql"<?php echo ($radio_dump=='postgresql_options'?' checked':''); ?> />
        <label for="radio_dump_postgresql">PostgreSQL Restore (pg_restore or psql)</label>
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
<?php } ?>

</td><td class="tdtop">


<div id="div_container_sub_exportoptions" >
<?php
if (in_array($type, array('mysql', 'mysqli')))
{
?>
	<fieldset id="mysql_options">
    <legend><?php echo $langs->trans('RestoreMySQL') ?></legend>
	<div class="formelementrow centpercent">
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
	print '<textarea rows="1" id="restorecommand" class="centpercent">'.$langs->trans("ImportMySqlCommand",$command,($showpass?$paramclear:$paramcrypted)).'</textarea><br>';
	print ajax_autoselect('restorecommand');

	if (empty($_GET["showpass"]) && $dolibarr_main_db_pass) print '<br><a href="'.$_SERVER["PHP_SELF"].'?showpass=1&amp;radio_dump=mysql_options">'.$langs->trans("UnHidePassword").'</a>';
	//else print '<br><a href="'.$_SERVER["PHP_SELF"].'?showpass=0&amp;radio_dump=mysql_options">'.$langs->trans("HidePassword").'</a>';
	?>
	</div>
    </fieldset>
<?php
}
else if (in_array($type, array('pgsql')))
{
?>
    <fieldset id="postgresql_options">
    <legend>Restore PostgreSQL</legend>
    <div class="formelementrow">
    <?php
    // Parameteres execution
    $command=$db->getPathOfRestore();
    if (preg_match("/\s/",$command)) $command=$command=escapeshellarg($command);    // Use quotes on command

    $param=" -d ".$dolibarr_main_db_name;
    $param.=" -h ".$dolibarr_main_db_host;
    if (! empty($dolibarr_main_db_port)) $param.=" -p ".$dolibarr_main_db_port;
    $param.=" -U ".$dolibarr_main_db_user;
    $paramcrypted=$param;
    $paramclear=$param;
    /*if (! empty($dolibarr_main_db_pass))
    {
        $paramcrypted.=" -p".preg_replace('/./i','*',$dolibarr_main_db_pass);
        $paramclear.=" -p".$dolibarr_main_db_pass;
    }*/
    $paramcrypted.=" -W";
    $paramclear.=" -W";
    // With psql:
    $paramcrypted.=" -f";
    $paramclear.=" -f";

    echo $langs->trans("ImportPostgreSqlDesc");
    print '<br>';
    print '<textarea rows="1" id="restorecommand" class="centpercent">'.$langs->trans("ImportPostgreSqlCommand",$command,($showpass?$paramclear:$paramcrypted)).'</textarea><br>';
    print ajax_autoselect('restorecommand');
    //if (empty($_GET["showpass"]) && $dolibarr_main_db_pass) print '<br><a href="'.$_SERVER["PHP_SELF"].'?showpass=1&amp;radio_dump=postgresql_options">'.$langs->trans("UnHidePassword").'</a>';
    //else print '<br><a href="'.$_SERVER["PHP_SELF"].'?showpass=0&amp;radio_dump=mysql_options">'.$langs->trans("HidePassword").'</a>';
    ?>
    </div>
    </fieldset>
<?php
}
?>

</div>


</td></tr></table>
</fieldset>

<?php
// End of page
llxFooter();
$db->close();
