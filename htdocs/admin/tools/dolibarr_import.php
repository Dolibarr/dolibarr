<?php
/* Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\brief      Page import de la base
 *		\version    $Id$
 */

require("./pre.inc.php");

$langs->load("admin");
$langs->load("other");

if (! $user->admin)
  accessforbidden();


/*
 * View
 */

llxHeader('','','EN:Restores|FR:Restaurations|ES:Restauraciones');

print_fiche_titre($langs->trans("Restore"),'','setup');
print '<br>';

print $langs->trans("RestoreDesc",DOL_DOCUMENT_ROOT).'<br><br>';
print $langs->trans("RestoreDesc2",DOL_DOCUMENT_ROOT).'<br><br>';
print $langs->trans("RestoreDesc3",DOL_DOCUMENT_ROOT).'<br><br>';

?>

<!-- Run on page load -->
<script type="text/javascript" language="javascript">
//<![CDATA[
function hide_them_all() {
    document.getElementById("mysql_options").style.display = 'none';
//    document.getElementById("csv_options").style.display = 'none';
//    document.getElementById("latex_options").style.display = 'none';
//    document.getElementById("pdf_options").style.display = 'none';
//    document.getElementById("none_options").style.display = 'none';

<?php
if (! empty($_GET["radio_dump"])) print "document.getElementById('mysql_options').style.display = 'block';";
?>
}

//]]>
</script>


<fieldset id="fieldsetexport">
<table><tr><td valign="top">
<?php
print $langs->trans("DatabaseName").' : <b>'.$dolibarr_main_db_name.'</b><br>';
print '<br>';
?>

<div id="div_container_exportoptions">
<fieldset id="exportoptions">
	<legend><?php echo $langs->trans("ImportMethod"); ?></legend>
    <div class="formelementrow">
        <input type="radio" name="what" value="mysql" id="radio_dump_mysql"
        	<?php echo ($_GET["radio_dump"]=='mysql_options'?' checked':''); ?>
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('mysql_options').style.display = 'block';
                }; return true"
             />
            <label for="radio_dump_mysql">MySQL</label>
    </div>
</fieldset>
</div>


</td><td valign="top">


<div id="div_container_sub_exportoptions">
	<fieldset id="mysql_options">
    <legend>Import MySql</legend>
	<div class="formelementrow">
	<?php
	// Parameteres execution
	$command='mysql';
	if (eregi(" ",$command)) $command=$command=escapeshellarg($command);	// Use quotes on command

	$param=$dolibarr_main_db_name;
	$param.=" -h ".$dolibarr_main_db_host;
	if (! empty($dolibarr_main_db_port)) $param.=" -P ".$dolibarr_main_db_port;
	$param.=" -u ".$dolibarr_main_db_user;
	$paramcrypted=$param;
	$paramclear=$param;
	if (! empty($dolibarr_main_db_pass))
	{
		$paramcrypted.=" -p".eregi_replace('.','*',$dolibarr_main_db_pass);
		$paramclear.=" -p".$dolibarr_main_db_pass;
	}

	echo $langs->trans("ImportMySqlDesc");
	print '<br>';
	print '<textarea rows="1" cols="120">'.$langs->trans("ImportMySqlCommand",$command,$_GET["showpass"]?$paramclear:$paramcrypted).'</textarea><br>';

	if (empty($_GET["showpass"])) print '<br><a href="'.$_SERVER["PHP_SELF"].'?showpass=1&amp;radio_dump=mysql_options">'.$langs->trans("UnHidePassword").'</a>';
	//else print '<br><a href="'.$_SERVER["PHP_SELF"].'?showpass=0&amp;radio_dump=mysql_options">'.$langs->trans("HidePassword").'</a>';
	?>
	</div>

	<script type="text/javascript" language="javascript">
//<![CDATA[
	hide_them_all();
//]]>
	</script>
	</fieldset>
</div>


</td></tr></table>
</fieldset>


<script type="text/javascript" language="javascript">
//<![CDATA[


// set current db, table and sql query in the querywindow
if (window.parent.refreshLeft) {
    window.parent.reload_querywindow("","","");
}


if (window.parent.frames[1]) {
    // reset content frame name, as querywindow needs to set a unique name
    // before submitting form data, and navigation frame needs the original name
    if (window.parent.frames[1].name != 'frame_content') {
        window.parent.frames[1].name = 'frame_content';
    }
    if (window.parent.frames[1].id != 'frame_content') {
        window.parent.frames[1].id = 'frame_content';
    }
    //window.parent.frames[1].setAttribute('name', 'frame_content');
    //window.parent.frames[1].setAttribute('id', 'frame_content');
}
//]]>
</script>

<?php

llxFooter('$Date$ - $Revision$');
?>