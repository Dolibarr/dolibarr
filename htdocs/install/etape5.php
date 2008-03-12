<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/install/etape5.php
        \brief      Page de fin d'installation ou de migration
        \version    $Id$
*/

include_once("./inc.php");

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("admin");
$langs->load("install");

$success=0;


dolibarr_install_syslog("etape5: Entering etape5.php page", LOG_INFO);


if ($_POST["action"] == "set" || $_POST["action"] == "upgrade")
{
    // If install, check pass and pass_verif used to create admin account
    if ($_POST["action"] == "set")
    {
        if ($_POST["pass"] <> $_POST["pass_verif"])
        {
            Header("Location: etape4.php?error=1&selectlang=$setuplang".(isset($_POST["login"])?'&login='.$_POST["login"]:''));
            exit;
        }
    
        if (strlen(trim($_POST["pass"])) == 0)
        {
            Header("Location: etape4.php?error=2&selectlang=$setuplang".(isset($_POST["login"])?'&login='.$_POST["login"]:''));
            exit;
        }
    
        if (strlen(trim($_POST["login"])) == 0)
        {
            Header("Location: etape4.php?error=3&selectlang=$setuplang".(isset($_POST["login"])?'&login='.$_POST["login"]:''));
            exit;
        }
    }
    

    pHeader($langs->trans("SetupEnd"),"etape5");

    print '<table cellspacing="0" cellpadding="2" width="100%">';
    $error=0;
    
    // on décode le mot de passe de la base si besoin
    require_once(DOL_DOCUMENT_ROOT ."/lib/functions.inc.php");
    if (isset($dolibarr_main_db_encrypted_pass) && $dolibarr_main_db_encrypted_pass) $dolibarr_main_db_pass = dolibarr_decode($dolibarr_main_db_encrypted_pass);

    $conf->db->type = $dolibarr_main_db_type;
    $conf->db->host = $dolibarr_main_db_host;
    $conf->db->port = $dolibarr_main_db_port;
    $conf->db->name = $dolibarr_main_db_name;
    $conf->db->user = $dolibarr_main_db_user;
    $conf->db->pass = $dolibarr_main_db_pass;
	
    $db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);
    $ok = 0;

    // Active module user
    $modName='modUser';
    $file = $modName . ".class.php";
    dolibarr_install_syslog('install/etape5.php Load module user '.DOL_DOCUMENT_ROOT ."/includes/modules/".$file, LOG_INFO);
	include_once(DOL_DOCUMENT_ROOT ."/includes/modules/".$file);
    $objMod = new $modName($db);
    $objMod->init();
    
    // If first install
    if ($_POST["action"] == "set")
    {
        if ($db->connected == 1)
        {
			$conf->setValues($db);

			// Create user
			include_once(DOL_DOCUMENT_ROOT ."/user.class.php");

			$createuser=new User($db);
			$createuser->id=0;
			
			$newuser = new User($db);
			$newuser->nom='Admin';
			$newuser->prenom='';
			$newuser->login=$_POST["login"];
			$newuser->pass=$_POST["pass"];
			$newuser->admin=1;

			$result=$newuser->create($createuser,1);
	        if ($result > 0)
	        {
	            print $langs->trans("AdminLoginCreatedSuccessfuly",$_POST["login"])."<br>";
	            $success = 1;
	        }
	        else
	        {
	            if ($newuser->error == 'ErrorLoginAlreadyExists')
	            {
					dolibarr_install_syslog('install/etape5.php ErrorLoginAlreadyExists', LOG_WARNING);
	                print '<br><div class="warning">'.$langs->trans("AdminLoginAlreadyExists",$_POST["login"])."</div><br>";
	                $success = 1;
	            }
	            else
	            {
					dolibarr_install_syslog('install/etape5.php FailedToCreateAdminLogin', LOG_ERR);
	                print '<br>'.$langs->trans("FailedToCreateAdminLogin").'<br><br>';
	            }
	        }
	    
	        if ($success)
	        {
				dolibarr_install_syslog('install/etape5.php Remove MAIN_NOT_INSTALLED const', LOG_ERR);
	            $db->query("DELETE FROM llx_const WHERE name='MAIN_NOT_INSTALLED'");
	        
	            // Si install non Français, on configure pour fonctionner en mode internationnal
	            if ($langs->defaultlang != "fr_FR")
	            {
	                $db->query("UPDATE llx_const set value='eldy_backoffice.php' WHERE name='MAIN_MENU_BARRETOP';");
	                $db->query("UPDATE llx_const set value='eldy_backoffice.php' WHERE name='MAIN_MENU_BARRELEFT';");
	
	                $db->query("UPDATE llx_const set value='eldy_frontoffice.php' WHERE name='MAIN_MENUFRONT_BARRETOP';");
	                $db->query("UPDATE llx_const set value='eldy_frontoffice.php' WHERE name='MAIN_MENUFRONT_BARRELEFT';");
	            }
	
	        }
    	}
    	else
    	{
            print $langs->trans("Error")."<br>";
    	}
    }

    // May fail if parameter already defined
    $resql=$db->query("INSERT INTO llx_const(name,value,type,visible,note) values('MAIN_LANG_DEFAULT','".$setuplang."','chaine',0,'Default language')");
	
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


print '<a href="'.$dolibarr_main_url_root .'/admin/index.php?mainmenu=home&leftmenu=setup'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
print $langs->trans("GoToSetupArea");
print '</a>';


// Clear cache files
clearstatcache();


dolibarr_install_syslog("Dolibarr install/setup finished", LOG_INFO);

pFooter(1,$setuplang);
?>
