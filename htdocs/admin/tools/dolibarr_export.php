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
		\file 		htdocs/admin/tools/dolibarr_export.php
		\ingroup	core
		\brief      Page export de la base
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
include_once $dolibarr_main_document_root."/lib/databases/".$conf->db->type.".lib.php";

$langs->load("admin");

if (! $user->admin)
  accessforbidden();


$html=new Form($db);
$formfile = new FormFile($db);


/*
* Affichage page
*/

llxHeader('','','EN:Backups|FR:Sauvegardes|ES:Copias_de_seguridad');

print_fiche_titre($langs->trans("Backup"),'','setup');
print '<br>';

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
<form method="post" action="export.php" name="dump">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />

<input type="hidden" name="export_type" value="server" />

<script type="text/javascript" language="javascript">
//<![CDATA[
function hide_them_all() {
    document.getElementById("mysql_options").style.display = 'none';
//    document.getElementById("csv_options").style.display = 'none';
//    document.getElementById("latex_options").style.display = 'none';
//    document.getElementById("pdf_options").style.display = 'none';
//    document.getElementById("none_options").style.display = 'none';
}

function show_checked_option() {
    hide_them_all();

    if (document.getElementById('radio_dump_mysql')) {
        document.getElementById('mysql_options').style.display = 'block';
    }
//    if (document.getElementById('radio_dump_latex').checked) {
//        document.getElementById('latex_options').style.display = 'block';
//    }
//    if (document.getElementById('radio_dump_pdf').checked) {
//        document.getElementById('pdf_options').style.display = 'block';
//    }
//    if (document.getElementById('radio_dump_xml').checked) {
//        document.getElementById('none_options').style.display = 'block';
//    }
//    if (document.getElementById('radio_dump_csv')) {
//        document.getElementById('csv_options').style.display = 'block';
//    }

}

//]]>
</script>

<fieldset id="fieldsetexport">


<!-- LDR -->
<table><tr><td valign="top">

<?php
print $langs->trans("DatabaseName").' : <b>'.$dolibarr_main_db_name.'</b><br>';
print '<br>';
?>

<div id="div_container_exportoptions">
<fieldset id="exportoptions">
<legend><?php echo $langs->trans("ExportMethod"); ?></legend>

    <div class="formelementrow">
        <input type="radio" name="what" value="mysql" id="radio_dump_mysql"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('mysql_options').style.display = 'block';
                }; return true"
             />
            <label for="radio_dump_mysql">MySQLDump</label>
    </div>

<!--
    <div class="formelementrow">
        <input type="radio" name="what" value="latex" id="radio_dump_latex"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('latex_options').style.display = 'block';
                }; return true"
             />
        <label for="radio_dump_latex">LaTeX</label>

    </div>

    <div class="formelementrow">
        <input type="radio" name="what" value="pdf" id="radio_dump_pdf"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('pdf_options').style.display = 'block';
                }; return true"
             />
        <label for="radio_dump_pdf">PDF</label>
    </div>

    <div class="formelementrow">
        <input type="radio" name="what" value="csv" id="radio_dump_csv"
            onclick="if
                (this.checked) {
                    hide_them_all();
                    document.getElementById('csv_options').style.display = 'block';
                 }; return true"
              />
        <label for="radio_dump_csv">CSV</label>
    </div>

    <div class="formelementrow">
        <input type="radio" name="what" value="xml" id="radio_dump_xml"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('none_options').style.display = 'block';
                }; return true"
             />
        <label for="radio_dump_xml">XML</label>

    </div>
-->

</fieldset>
</div>

</td><td valign="top">


<div id="div_container_sub_exportoptions">


<fieldset id="mysql_options">
    <legend><?php echo $langs->trans("MySqlExportParameters"); ?></legend>

    <div class="formelementrow">
        <?php echo $langs->trans("FullPathToMysqldumpCommand");
            if (empty($conf->global->SYSTEMTOOLS_MYSQLDUMP))
            {
				$resql=$db->query('SHOW VARIABLES LIKE \'basedir\'');
				if ($resql)
				{
					$liste=$db->fetch_array($resql);
					$basedir=$liste['Value'];
					$fullpathofmysqldump=$basedir.'bin/mysqldump';
				}
				else
				{
					$fullpathofmysqldump='/pathtomysqldump/mysqldump';
				}
            }
            else
            {
	            $fullpathofmysqldump=$conf->global->SYSTEMTOOLS_MYSQLDUMP;
            }
        ?><br />
        <input type="text" name="mysqldump" size="80"
            value="<?php echo $fullpathofmysqldump; ?>" />
    </div>

    <div class="formelementrow">
        <input type="checkbox" name="use_transaction" value="yes"
            id="checkbox_use_transaction"
             />
        <label for="checkbox_use_transaction">
            <?php echo $langs->trans("UseTransactionnalMode"); ?></label>

    </div>

    <div class="formelementrow">
        <input type="checkbox" name="disable_fk" value="yes" id="checkbox_disable_fk" checked="true" />
        <label for="checkbox_disable_fk">
            <?php echo $langs->trans("CommandsToDisableForeignKeysForImport"); ?></label>
    </div>
    <label for="select_sql_compat">
        <?php echo $langs->trans("ExportCompatibility"); ?></label>

    <select name="sql_compat" id="select_sql_compat">
        <option value="NONE" selected="selected">NONE</option>
<option value="ANSI">ANSI</option>
<option value="DB2">DB2</option>
<option value="MAXDB">MAXDB</option>
<option value="MYSQL323">MYSQL323</option>
<option value="MYSQL40">MYSQL40</option>
<option value="MSSQL">MSSQL</option>
<option value="ORACLE">ORACLE</option>
<option value="POSTGRESQL">POSTGRESQL</option>
    </select>
    <fieldset>
        <legend><?php echo $langs->trans("ExportOptions"); ?></legend>
        <input type="checkbox" name="drop_database" value="yes"
            id="checkbox_drop_database"
             />
        <label for="checkbox_drop_database"><?php echo $langs->trans("AddDropDatabase"); ?></label>
    </fieldset>
    <fieldset>
        <legend>
            <input type="checkbox" name="sql_structure" value="structure"
                id="checkbox_sql_structure"
                 checked="checked"                onclick="
                    if (!this.checked &amp;&amp; !document.getElementById('checkbox_sql_data').checked)
                        return false;
                    else return true;" />
            <label for="checkbox_sql_structure">
                Structure</label>
        </legend>

        <input type="checkbox" name="drop" value="1" id="checkbox_dump_drop"
             />
        <label for="checkbox_dump_drop"><?php echo $langs->trans("AddDropTable"); ?></label><br />

    </fieldset>
    <fieldset>
        <legend>

            <input type="checkbox" name="sql_data" value="data"
                id="checkbox_sql_data"  checked="checked"                onclick="
                    if (!this.checked &amp;&amp; (!document.getElementById('checkbox_sql_structure') || !document.getElementById('checkbox_sql_structure').checked))
                        return false;
                    else return true;" />
            <label for="checkbox_sql_data">
                <?php echo $langs->trans("Datas"); ?></label>
        </legend>
        <input type="checkbox" name="showcolumns" value="yes"
            id="checkbox_dump_showcolumns"
             />
        <label for="checkbox_dump_showcolumns">
            <?php echo $langs->trans("NameColumn"); ?></label><br />

        <input type="checkbox" name="extended_ins" value="yes"
            id="checkbox_dump_extended_ins"
             />
        <label for="checkbox_dump_extended_ins">
            <?php echo $langs->trans("ExtendedInsert"); ?></label><br />

        <input type="checkbox" name="delayed" value="yes"
            id="checkbox_dump_delayed"
             />

        <label for="checkbox_dump_delayed">
            <?php echo $langs->trans("DelayedInsert"); ?></label><br />

        <input type="checkbox" name="sql_ignore" value="yes"
            id="checkbox_dump_ignore"
             />
        <label for="checkbox_dump_ignore">
            Ignorer les erreurs de doublons (INSERT IGNORE)</label><br />

        <input type="checkbox" name="hexforbinary" value="yes"
            id="checkbox_hexforbinary"
             checked="checked" />
        <label for="checkbox_hexforbinary">
            <?php echo $langs->trans("EncodeBinariesInHexa"); ?></label><br />

    </fieldset>
</fieldset>

<!--
<fieldset id="latex_options">
    <legend>Parametres export LaTeX</legend>

    <div class="formelementrow">
        <input type="checkbox" name="latex_caption" value="yes"
            id="checkbox_latex_show_caption"
             checked="checked" />

        <label for="checkbox_latex_show_caption">
            Inclure les sous-titres</label>
    </div>

    <fieldset>
        <legend>
            <input type="checkbox" name="latex_structure" value="structure"
                id="checkbox_latex_structure"
                 checked="checked"                onclick="
                    if (!this.checked &amp;&amp; !document.getElementById('checkbox_latex_data').checked)
                        return false;
                    else return true;" />
            <label for="checkbox_latex_structure">
                Structure</label>

        </legend>

        <table>
        <tr><td><label for="latex_structure_caption">
                    Sous-titre de la table</label></td>
            <td><input type="text" name="latex_structure_caption" size="30"
                    value="Structure de la table __TABLE__"
                    id="latex_structure_caption" />
            </td>
        </tr>
        <tr><td><label for="latex_structure_continued_caption">

                    Sous-titre de la table (suite)</label></td>
            <td><input type="text" name="latex_structure_continued_caption"
                    value="Structure de la table __TABLE__ (suite)"
                    size="30" id="latex_structure_continued_caption" />
            </td>
        </tr>
        <tr><td><label for="latex_structure_label">
                    Cl� de l'�tiquette</label></td>
            <td><input type="text" name="latex_structure_label" size="30"
                    value="tab:__TABLE__-structure"
                    id="latex_structure_label" />
            </td>

        </tr>
        </table>

        </fieldset>
        <fieldset>
        <legend>
            <input type="checkbox" name="latex_data" value="data"
                id="checkbox_latex_data"
                 checked="checked"                onclick="
                    if (!this.checked &amp;&amp; (!document.getElementById('checkbox_latex_structure') || !document.getElementById('checkbox_latex_structure').checked))
                        return false;
                    else return true;" />
            <label for="checkbox_latex_data">
                Donn�es</label>

        </legend>
        <input type="checkbox" name="latex_showcolumns" value="yes"
            id="ch_latex_showcolumns"
             checked="checked" />
        <label for="ch_latex_showcolumns">
            Nom des colonnes</label><br />
        <table>
        <tr><td><label for="latex_data_caption">
                    Sous-titre de la table</label></td>
            <td><input type="text" name="latex_data_caption" size="30"
                    value="Contenu de la table __TABLE__"
                    id="latex_data_caption" />

            </td>
        </tr>
        <tr><td><label for="latex_data_continued_caption">
                    Sous-titre de la table (suite)</label></td>
            <td><input type="text" name="latex_data_continued_caption" size="30"
                    value="Contenu de la table __TABLE__ (suite)"
                    id="latex_data_continued_caption" />
            </td>
        </tr>
        <tr><td><label for="latex_data_label">

                    Cl� de l'�tiquette</label></td>
            <td><input type="text" name="latex_data_label" size="30"
                    value="tab:__TABLE__-data"
                    id="latex_data_label" />
            </td>
        </tr>
        <tr><td><label for="latex_replace_null">
                    Remplacer NULL par</label></td>
            <td><input type="text" name="latex_replace_null" size="20"
                    value="\textit{NULL}"
                    id="latex_replace_null" />
            </td>

        </tr>
        </table>
    </fieldset>
</fieldset>
-->

<!--
<fieldset id="csv_options">
    <input type="hidden" name="csv_data" value="csv_data" />
    <legend>Parametres export CSV</legend>

    <table>

    <tr><td><label for="export_separator">
                Champs termin�s par</label></td>
        <td><input type="text" name="export_separator" size="2"
                id="export_separator"
                value=";" />
        </td>
    </tr>
    <tr><td><label for="enclosed">
                Champs entour�s par</label></td>
        <td><input type="text" name="enclosed" size="2"
                id="enclosed"
                value="&quot;" />

        </td>
    </tr>
    <tr><td><label for="escaped">
                Caract�re sp�cial</label></td>
        <td><input type="text" name="escaped" size="2"
                id="escaped"
                value="\" />
        </td>
    </tr>
    <tr><td><label for="add_character">

                Lignes termin�es par</label></td>
        <td><input type="text" name="add_character" size="2"
                id="add_character"
                value="\r\n" />
        </td>
    </tr>
    <tr><td><label for="csv_replace_null">
                Remplacer NULL par</label></td>
        <td><input type="text" name="csv_replace_null" size="20"
                id="csv_replace_null"
                value="NULL" />
        </td>

    </tr>
    </table>
    <input type="checkbox" name="showcsvnames" value="yes"
        id="checkbox_dump_showcsvnames"
          />
    <label for="checkbox_dump_showcsvnames">
        Afficher les noms de champ en premi�re ligne</label>
</fieldset>
-->

<!--
<fieldset id="pdf_options">
    <input type="hidden" name="pdf_data" value="pdf_data" />

    <legend>Parametres export PDF</legend>

    <div class="formelementrow">
        <label for="pdf_report_title">Titre du rapport</label>
        <input type="text" name="pdf_report_title" size="50"
            value=""
            id="pdf_report_title" />
    </div>
</fieldset>
-->

<!--
<fieldset id="none_options">
    <legend>Options XML</legend>
    Ce format ne comporte pas d'options    <input type="hidden" name="xml_data" value="xml_data" />
</fieldset>
-->

</div>


</td></tr></table>

<script type="text/javascript" language="javascript">
//<![CDATA[
    show_checked_option();
	hide_them_all();
//]]>
</script>

</fieldset>



<fieldset>

    <label for="filename_template">
        <?php echo $langs->trans("FileNameToGenerate"); ?></label> :
    <input type="text" name="filename_template" size="60" id="filename_template"
     value="<?php
$file='mysqldump_'.$dolibarr_main_db_name.'_'.strftime("%Y%m%d%H%M").'.sql';
echo $file;
?>" />

<br><br>

<?php

print '<div class="formelementrow">';
print "\n";

print $langs->trans("Compression").': &nbsp; ';

$compression=array(
	'none' => array('function' => '',         'id' => 'radio_compression_none', 'label' => $langs->trans("None")),
//	'zip'  => array('function' => 'zip_open', 'id' => 'radio_compression_zip',  'label' => $langs->trans("Zip")),		Not open source
	'gz'   => array('function' => 'gzopen',  'id' => 'radio_compression_gzip', 'label' => $langs->trans("Gzip")),
	'bz'   => array('function' => 'bzopen',  'id' => 'radio_compression_bzip', 'label' => $langs->trans("Bzip2"))
);

foreach($compression as $key => $val)
{
	if (! $val['function'] || function_exists($val['function']))
	{
		print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'"';
		print ' onclick="document.getElementById(\'checkbox_dump_asfile\').checked = true;" checked="checked" />';
		print ' <label for="'.$val['id'].'">'.$val['label'].'</label>';
	}
	else
	{
		print '<input type="radio" name="compression" value="'.$key.'" id="'.$val['id'].'" disabled="true">';
		print ' <label for="'.$val['id'].'">'.$val['label'].'</label>';
		print ' ('.$langs->trans("NotAvailable").')';
	}
	print ' &nbsp; &nbsp; ';
}

print '</div>';
print "\n";

?>

</fieldset>


<center>
    <input type="submit" class="button" value="<?php echo $langs->trans("GenerateBackup") ?>" id="buttonGo" /><br><br>
</center>


</form>


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


$result=$formfile->show_documents('systemtools','',DOL_DATA_ROOT.'/admin/temp',$_SERVER['PHP_SELF'],0,1,'','',0,0,48,0,'',$langs->trans("Files"));
//if ($result) print '<br><br>';



llxFooter('$Date$ - $Revision$');
?>