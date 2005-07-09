<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne <eric.seigne@ryxeo.com>
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
        \file       htdocs/admin/expedition.php
        \ingroup    expedition
        \brief      Page d'administration/configuration du module Expedition
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("sendings");

if (!$user->admin) accessforbidden();

// positionne la variable pour le test d'affichage de l'icone
$expedition_addon_var_pdf = EXPEDITION_ADDON_PDF;
$expedition_default = EXPEDITION_ADDON;

/*
 * Actions
 */
if ($_GET["action"] == 'set')
{
    $file = DOL_DOCUMENT_ROOT . '/expedition/mods/methode_expedition_'.$_GET["value"].'.modules.php';

    $classname = 'methode_expedition_'.$_GET["value"];
    require_once($file);

    $obj = new $classname();

    // Mise a jour statut
    $sql = "UPDATE ".MAIN_DB_PREFIX."expedition_methode set status='".$_GET["statut"]."'";
    $sql.= " WHERE rowid = ".$obj->id;
    print "$sql";
    exit;

    Header("Location: expedition.php");

}

if ($_GET["action"] == 'setpdf')
{
    $db->begin();
    
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXPEDITION_ADDON_PDF';";
    $resql=$db->query($sql);
    if ($resql)
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('EXPEDITION_ADDON_PDF','".$_GET["value"]."',0)";
        $resql=$db->query($sql);
        if ($resql)
        {
            // la constante qui a été lue en avant du nouveau set
            // on passe donc par une variable pour avoir un affichage cohérent
            $expedition_addon_var_pdf = $value;

            $db->commit();
        
            Header("Location: ".$_SERVER["PHP_SELF"]);
            exit;
        }
        else 
        {
            $db->rollback();
            dolibarr_print_error($db);
        }
    }
    else 
    {
        $db->rollback();
        dolibarr_print_error($db);
    }
}

if ($_GET["action"] == 'setdef')
{
    $db->begin();

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXPEDITION_ADDON';";
    $resql=$db->query($sql);
    if ($resql)
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('EXPEDITION_ADDON','".$_GET["value"]."',0)";
        $resql=$db->query($sql);
        if ($resql)
        {
            // la constante qui a été lue en avant du nouveau set
            // on passe donc par une variable pour avoir un affichage cohérent
            $expedition_default = $_GET["value"];
            $db->commit();

            Header("Location: ".$_SERVER["PHP_SELF"]);
            exit;
        }
        else 
        {
            $db->rollback();
            dolibarr_print_error($db);
        }
    }
    else 
    {
        $db->rollback();
        dolibarr_print_error($db);
    }
}


/*
 *
 */

llxHeader();


$dir = DOL_DOCUMENT_ROOT."/expedition/mods/";


// Méthode de livraison

print_titre($langs->trans("SendingsSetup"));

print "<br>";

print_titre($langs->trans("SendingMethod"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td><td>'.$langs->trans("Description").'</td>';
print '<td align="center" colspan="2">'.$langs->trans("Active").'</td>';
print '<td align="center">'.$langs->trans("Default").'</td>';
print "</tr>\n";

if(is_dir($dir)) {
    $handle=opendir($dir);
    $var=true;
    
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,19) == 'methode_expedition_')
        {
            $name = substr($file, 19, strlen($file) - 31);
            $classname = substr($file, 0, strlen($file) - 12);

            require_once($dir.$file);

            $obj = new $classname();

            $var=!$var;
            print "<tr $bc[$var]><td>";
            echo $obj->name;
            print "</td><td>\n";

            print $obj->description;

            print '</td><td align="center">';


            print "&nbsp;";
            print "</td><td>\n";
            print '<a href="expedition.php?action=set&amp;statut=1&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';


            print '</td>';
            
            // Default
            print '<td align="center">';
            if ($expedition_default == "$name")
            {
                print img_tick();
            }
            else
            {
                print '<a href="expedition.php?action=setdef&amp;value='.$name.'">'.$langs->trans("Default").'</a>';
            }
            print '</td>';

            print '</tr>';
        }
    }
    closedir($handle);
}
else
{
    print "<tr><td><b>ERROR</b>: $dir is not a directory !</td></tr>\n";
}
print '</table>';

print '<br>';

// PDF

print_titre($langs->trans("SendingsReceiptModel"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td><td>'.$langs->trans("Description").'</td>';
print '<td align="center" colspan="2">'.$langs->trans("Active").'</td>';
print "</tr>\n";

clearstatcache();

$dir = DOL_DOCUMENT_ROOT."/expedition/mods/pdf/";

if(is_dir($dir))
{
    $handle=opendir($dir);
    $var=true;

    while (($file = readdir($handle))!==false)
    {
        if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,15) == 'pdf_expedition_')
        {
            $name = substr($file, 15, strlen($file) - 27);
            $classname = substr($file, 0, strlen($file) - 12);

            $var=!$var;
            print "<tr $bc[$var]><td>";
            print $name;
            print "</td><td>\n";
            require_once($dir.$file);
            $obj = new $classname();

            print $obj->description;


            print '</td><td align="center">';

            if ($expedition_addon_var_pdf == "$name")
            {
                print img_tick();
            }
            else
            {
                print "&nbsp;";
            }

            print "</td><td>\n";

            print '<a href="expedition.php?action=setpdf&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';

            print '</td></tr>';
        }
    }
    closedir($handle);
}
else
{
    print "<tr><td><b>ERROR</b>: $dir is not a directory !</td></tr>\n";
}
print '</table>';

/*
*
*
*/

$db->close();

llxFooter();
?>
