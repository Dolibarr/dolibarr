<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien DiCintio   <sdicintio@ressource-toi.org>
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
        \file       htdocs/install/etape5.php
        \brief      Page de fin d'installation ou de migration
        \version    $Revision$
*/

include_once("./inc.php");

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:$langcode);
$langs->defaultlang=$setuplang;
$langs->load("admin");
$langs->load("install");

$success=0;

if (file_exists($conffile))
{
    include($conffile);
    if (! isset($dolibarr_main_db_prefix) || ! $dolibarr_main_db_prefix) $dolibarr_main_db_prefix='llx_'; 
    define('MAIN_DB_PREFIX',$dolibarr_main_db_prefix);
}


if($dolibarr_main_db_type == "mysql")
    require_once($dolibarr_main_document_root . "/lib/mysql.lib.php");
else
    require_once($dolibarr_main_document_root . "/lib/pgsql.lib.php");

require_once($dolibarr_main_document_root . "/conf/conf.class.php");


if ($_POST["action"] == "set" || $_POST["action"] == "upgrade")
{
    // If install, check pass and pass_verif used to create admin account
    if ($_POST["action"] == "set")
    {
        if ($_POST["pass"] <> $_POST["pass_verif"])
        {
            Header("Location: etape4.php?error=1&selectlang=$setuplang");
            exit;
        }
    
        if (strlen(trim($_POST["pass"])) == 0)
        {
            Header("Location: etape4.php?error=2&selectlang=$setuplang");
            exit;
        }
    
        if (strlen(trim($_POST["login"])) == 0)
        {
            Header("Location: etape4.php?error=3&selectlang=$setuplang");
            exit;
        }
    }
    
    // If upgrade
    if ($_POST["action"] == "upgrade")
    {

    }

    pHeader($langs->trans("SetupEnd"),"etape5");

    print '<table cellspacing="0" cellpadding="2" width="100%">';
    $error=0;

    $conf = new Conf();
    $conf->db->type = $dolibarr_main_db_type;
    $conf->db->host = $dolibarr_main_db_host;
    $conf->db->name = $dolibarr_main_db_name;
    $conf->db->user = $dolibarr_main_db_user;
    $conf->db->pass = $dolibarr_main_db_pass;

    $db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name);
    $ok = 0;

    // Active module user
    $modName='modUser';
    $file = $modName . ".class.php";
    include_once("../includes/modules/$file");
    $objMod = new $modName($db);
    $objMod->init();
    
    // If first install
    if ($_POST["action"] == "set")
    {
        if ($db->connected == 1)
        {
            $sql = "INSERT INTO llx_user(datec,login,pass,admin,name,code) VALUES (now()";
            $sql .= ",'".$_POST["login"]."'";
            $sql .= ",'".$_POST["pass"]."'";
            $sql .= ",1,'Administrateur','ADM')";
        }
    
        $resql=$db->query($sql);
    
        if ($resql)
        {
            print $langs->trans("AdminLoginCreatedSuccessfuly")."<br>";
            $success = 1;
        }
        else
        {
            if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
            {
                print $langs->trans("AdminLoginAlreadyExists",$_POST["login"])."<br>";
                $success = 1;
            }
            else {
                print $langs->trans("FailedToCreateAdminLogin")."<br>";
            }
        }
    
        if ($success)
        {
            $db->query("DELETE FROM llx_const WHERE name='MAIN_NOT_INSTALLED'");
        
            // Si install non Français, on configure pour fonctionner en mode internationnal
            if ($langs->defaultlang != "fr_FR")
            {
                $db->query("UPDATE llx_const set value='eldy.php' WHERE name='MAIN_MENU_BARRETOP';");
                $db->query("UPDATE llx_const set value='eldy.php' WHERE name='MAIN_MENU_BARRELEFT';");
            }
        }
    }
            
    print '</table>';

    $db->close();
}

print "<br>";


// If first install
if ($_POST["action"] == "set")
{
    // Fin install
    print $langs->trans("SystemIsInstalled")."<br>";
    print '<div class="warning">'.$langs->trans("WarningRemoveInstallDir")."</div>";
    
    print "<br>";
    
    print $langs->trans("YouNeedToPersonalizeSetup")."<br><br>";
}

// If upgrade
if ($_POST["action"] == "upgrade")
{
    // Fin install
    print $langs->trans("SystemIsUpgraded")."<br>";
    print '<div class="warning">'.$langs->trans("WarningRemoveInstallDir")."</div>";
    
    print "<br>";
}


print '<a href="'.$dolibarr_main_url_root .'/admin/index.php?mainmenu=home&leftmenu=setup">';
print $langs->trans("GoToSetupArea");
print '</a>';


pFooter(1);
?>
