<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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
        \file       htdocs/user/group/perms.php
        \brief      Onglet user et permissions de la fiche utilisateur
        \version    $Revision$
*/


require("./pre.inc.php");

$langs->load("users");


$form = new Form($db);

$module=isset($_GET["module"])?$_GET["module"]:$_POST["module"];


/**
 * Actions
 */
if ($_GET["action"] == 'addrights' && $user->admin)
{
    $editgroup = new Usergroup($db,$_GET["id"]);
    $editgroup->addrights($_GET["rights"]);
}

if ($_GET["action"] == 'delrights' && $user->admin)
{
    $editgroup = new Usergroup($db,$_GET["id"]);
    $editgroup->delrights($_GET["rights"]);
}


llxHeader('',$langs->trans("Permissions"));


/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["id"])
{
    $fgroup = new Usergroup($db, $_GET["id"]);
    $fgroup->fetch($_GET["id"]);
    $fgroup->getrights($_GET["id"]);

    /*
     * Affichage onglets
     */

    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/user/group/fiche.php?id='.$fgroup->id;
    $head[$h][1] = $langs->trans("GroupCard");
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/user/group/perms.php?id='.$fgroup->id;
    $head[$h][1] = $langs->trans("GroupRights");
    $hselected=$h;
    $h++;


    dolibarr_fiche_head($head, $hselected, $langs->trans("Group").": ".$fgroup->nom);

    // Lecture des droits groupes
    $permsgroup = array();

    $sql = "SELECT r.id, r.libelle, r.module ";
    $sql .= " FROM ".MAIN_DB_PREFIX."rights_def as r";
    $sql .= ", ".MAIN_DB_PREFIX."usergroup_rights as ugr";
    $sql .= " WHERE ugr.fk_id = r.id AND ugr.fk_usergroup = ".$fgroup->id;

    $result=$db->query($sql);



    if ($result)
    {
        $num = $db->num_rows($result);
        $i = 0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);

            array_push($permsgroup,$obj->id);

            $i++;
        }
        $db->free($result);
    }
    else
    {
        dolibarr_print_error($db);
    }


    // Charge les modules soumis a permissions
    $dir = DOL_DOCUMENT_ROOT . "/includes/modules/";
    $handle=opendir($dir);
    $modules = array();
    while (($file = readdir($handle))!==false)
    {
        if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, strlen($file) - 10) == '.class.php')
        {
            $modName = substr($file, 0, strlen($file) - 10);

            if ($modName)
            {
                include_once("../../includes/modules/$file");
                $objMod = new $modName($db);
                if ($objMod->rights_class) {
                    $modules[$objMod->rights_class]=$modName;
                    //print "modules[".$objMod->rights_class."]=$modName;";
                }
            }
        }
    }

    /*
     * Ecran ajout/suppression permission
     */


    print '<table width="100%" class="noborder">';
    print '<tr class="liste_titre">';
    if ($user->admin) print '<td width="24">&nbsp</td>';
    print '<td align="center" width="24">&nbsp;</td>';
    print '<td>'.$langs->trans("Permissions").'</td>';
    print '<td>'.$langs->trans("Module").'</td>';
    print '</tr>';

    $sql = "SELECT r.id, r.libelle, r.module FROM ".MAIN_DB_PREFIX."rights_def as r ORDER BY r.module, r.id ASC";

    $result=$db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);
        $i = 0;
        $var = True;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            if ($oldmod <> $obj->module)
            {
                $oldmod = $obj->module;
                $var = !$var;
                print '<tr '. $bc[$var].'>';

                // Recupère objet module
                $modName=$modules[$obj->module];
                $file = $modName.".class.php";
                include_once("../../includes/modules/$file");
                $objMod = new $modName($db);

                $picto=($objMod->picto?$objMod->picto:'generic');
            }
            else
            {
                print '<tr '. $bc[$var].'>';
            }

            if (in_array($obj->id, $permsgroup))
            {
                // Own permission by group
                if ($user->admin)
                {
                    print '<td align="center"><a href="perms.php?id='.$fgroup->id.'&amp;action=delrights&amp;rights='.$obj->id.'">'.img_edit_remove($langs->trans("Remove")).'</a></td>';
                }
                print '<td align="left" align="center">';
                print img_tick();
                print '</td>';
            }
            else
            {
                // Do not own permission
                if ($user->admin)
                {
                    print '<td align="center"><a href="perms.php?id='.$fgroup->id.'&amp;action=addrights&amp;rights='.$obj->id.'">'.img_edit_add($langs->trans("Add")).'</a></td>';
                }
                print '<td>&nbsp</td>';
            }

            print '<td>'.$obj->libelle . '</td>';

            print '<td>'.img_object('',$picto).' '.$objMod->getName();
            print '</td>';

            print '</tr>';

            $i++;
        }
    }
    print '</table>';
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
