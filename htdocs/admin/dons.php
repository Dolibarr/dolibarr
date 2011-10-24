<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/admin/dons.php
 *		\ingroup    dons
 *		\brief      Page d'administration/configuration du module Dons
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/dons/class/don.class.php");

$langs->load("admin");
$langs->load("donations");

if (!$user->admin) accessforbidden();

$typeconst=array('yesno','texte','chaine');


/*
 * Action
 */

if ($_GET["action"] == 'specimen')
{
    $modele=$_GET["module"];

    $don = new Don($db);
    $don->initAsSpecimen();

    // Charge le modele
    $dir = DOL_DOCUMENT_ROOT . "/includes/modules/dons/";
    $file = $modele.".modules.php";
    if (file_exists($dir.$file))
    {
        $classname = $modele;
        require_once($dir.$file);

        $obj = new $classname($db);

        if ($obj->write_file($don,$langs) > 0)
        {
            header("Location: ".DOL_URL_ROOT."/document.php?modulepart=donation&file=SPECIMEN.html");
            return;
        }
        else
        {
            $mesg='<div class="error">'.$obj->error.'</div>';
            dol_syslog($obj->error, LOG_ERR);
        }
    }
    else
    {
        $mesg='<div class="error">'.$langs->trans("ErrorModuleNotFound").'</div>';
        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

if ($_GET["action"] == 'setdoc')
{
    $db->begin();

    if (dolibarr_set_const($db, "DON_ADDON_MODEL",$_GET["value"],'chaine',0,'',$conf->entity))
    {
        $conf->global->DON_ADDON_MODEL = $_GET["value"];
    }

    // On active le modele
    $type='donation';
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql_del.= " WHERE nom = '".$db->escape($_GET["value"])."' AND type = '".$type."'";
    $result1=$db->query($sql_del);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($_GET["value"])."', '".$type."', ".$conf->entity.", ";
    $sql.= ($_GET["label"]?"'".$db->escape($_GET["label"])."'":'null').", ";
    $sql.= (! empty($_GET["scandir"])?"'".$db->escape($_GET["scandir"])."'":"null");
    $sql.= ")";
    $result2=$db->query($sql);
    if ($result1 && $result2)
    {
        $db->commit();
    }
    else
    {
        $db->rollback();
    }
}

if ($_GET["action"] == 'set')
{
    $type='donation';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($_GET["value"])."','".$type."',".$conf->entity.", ";
    $sql.= ($_GET["label"]?"'".$db->escape($_GET["label"])."'":'null').", ";
    $sql.= (! empty($_GET["scandir"])?"'".$db->escape($_GET["scandir"])."'":"null");
    $sql.= ")";
    $resql=$db->query($sql);
}

if ($_GET["action"] == 'del')
{
    $type='donation';
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    $resql=$db->query($sql);
}


/*
 * View
 */

$dir = "../includes/modules/dons/";
$html=new Form($db);

llxHeader('',$langs->trans("DonationsSetup"),'DonConfiguration');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("DonationsSetup"),$linkback,'setup');


// Document templates
print '<br>';
print_titre($langs->trans("DonationsModels"));

// Defini tableau def de modele
$type='donation';
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
$resql=$db->query($sql);
if ($resql)
{
    $i = 0;
    $num_rows=$db->num_rows($resql);
    while ($i < $num_rows)
    {
        $array = $db->fetch_array($resql);
        array_push($def, $array[0]);
        $i++;
    }
}
else
{
    dol_print_error($db);
}

print '<table class="noborder" width=\"100%\">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="80">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=True;
if (is_resource($handle))
{
    while (($file = readdir($handle))!==false)
    {
        if (preg_match('/\.modules\.php$/i',$file))
        {
            $var = !$var;
            $name = substr($file, 0, dol_strlen($file) -12);
            $classname = substr($file, 0, dol_strlen($file) -12);

            require_once($dir.'/'.$file);
            $module=new $classname($db);

            // Show modules according to features level
            if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

            if ($module->isEnabled())
            {
                print '<tr '.$bc[$var].'><td width=\"100\">';
                echo $module->name;
                print '</td>';
                print '<td>';
                print $module->description;
                print '</td>';

                // Active
                if (in_array($name, $def))
                {
                    print "<td align=\"center\">\n";
                    if ($conf->global->DON_ADDON_MODEL == $name)
                    {
                        print img_picto($langs->trans("Enabled"),'on');
                    }
                    else
                    {
                        print '&nbsp;';
                        print '</td><td align="center">';
                        print '<a href="dons.php?action=setdoc&value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Enabled"),'on').'</a>';
                    }
                    print '</td>';
                }
                else
                {
                    print "<td align=\"center\">\n";
                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
                    print "</td>";
                }

                // Defaut
                print "<td align=\"center\">";
                if ($conf->global->DON_ADDON_MODEL == "$name")
                {
                    print img_picto($langs->trans("Default"),'on');
                }
                else
                {
                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
                }
                print '</td>';

                // Info
                $htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
                $htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
                if ($module->type == 'pdf')
                {
                    $htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
                }
                $htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
                $htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
                $htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
                $text='<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'" target="specimen">'.img_object($langs->trans("Preview"),'generic').'</a>';
                print '<td align="center">';
                print $html->textwithpicto(' &nbsp; '.$text,$htmltooltip,-1,0);
                print '</td>';

                print "</tr>\n";
            }
        }
    }
    closedir($handle);
}

print '</table>';


print "<br>";


$db->close();

llxFooter();
?>
