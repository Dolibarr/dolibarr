<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien DiCintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file      htdocs/install/etape5.php
 *	 	 \ingroup	install
 *       \brief     Page de fin d'installation ou de migration
 *       \version   $Id$
 */

include_once("./inc.php");


$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

// TODO Send value from migrate choice
$targetversion=DOL_VERSION;
if (isset($_REQUEST["targetversion"])) $targetversion=$_REQUEST["targetversion"];


$langs->load("admin");
$langs->load("install");

$success=0;

// Init "forced values" to nothing. "forced values" are used after an doliwamp install wizard.
if (! isset($force_install_type))              $force_install_type='';
if (! isset($force_install_port))              $force_install_port='';
if (! isset($force_install_database))          $force_install_database='';
if (! isset($force_install_createdatabase))    $force_install_createdatabase='';
if (! isset($force_install_databaselogin))     $force_install_databaselogin='';
if (! isset($force_install_databasepass))      $force_install_databasepass='';
if (! isset($force_install_databaserootlogin)) $force_install_databaserootlogin='';
if (! isset($force_install_databaserootpass))  $force_install_databaserootpass='';
if (! isset($force_install_lockinstall))       $force_install_lockinstall='';
if (file_exists("./install.forced.php")) include_once("./install.forced.php");

dolibarr_install_syslog("etape5: Entering etape5.php page", LOG_INFO);


/*
 *	Actions
 */

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


/*
 *	View
 */

pHeader($langs->trans("SetupEnd"),"etape5");

if ($_POST["action"] == "set" || $_POST["action"] == "upgrade")
{
	print '<table cellspacing="0" cellpadding="2" width="100%">';
	$error=0;

	// decode database pass if needed
	if (! empty($dolibarr_main_db_encrypted_pass))
	{
		require_once(DOL_DOCUMENT_ROOT ."/lib/security.lib.php");
		$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
	}

	$conf->db->type = $dolibarr_main_db_type;
	$conf->db->host = $dolibarr_main_db_host;
	$conf->db->port = $dolibarr_main_db_port;
	$conf->db->name = $dolibarr_main_db_name;
	$conf->db->user = $dolibarr_main_db_user;
	$conf->db->pass = $dolibarr_main_db_pass;

	$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);
	$ok = 0;

	// If first install
	if ($_POST["action"] == "set")
	{
		// Active module user
		$modName='modUser';
		$file = $modName . ".class.php";
		dolibarr_install_syslog('install/etape5.php Load module user '.DOL_DOCUMENT_ROOT ."/includes/modules/".$file, LOG_INFO);
		include_once(DOL_DOCUMENT_ROOT ."/includes/modules/".$file);
		$objMod = new $modName($db);
		$objMod->init();

		if ($db->connected == 1)
		{
			$conf->setValues($db);

			// Create user
			include_once(DOL_DOCUMENT_ROOT ."/user.class.php");

			$createuser=new User($db);
			$createuser->id=0;

			$newuser = new User($db);
			$newuser->nom='SuperAdmin';
			$newuser->prenom='';
			$newuser->login=$_POST["login"];
			$newuser->pass=$_POST["pass"];
			$newuser->admin=1;
			$newuser->entity=0;

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
					dolibarr_install_syslog('install/etape5.php AdminLoginAlreadyExists', LOG_WARNING);
					print '<br><div class="warning">'.$langs->trans("AdminLoginAlreadyExists",$_POST["login"])."</div><br>";
					$success = 1;
				}
				else
				{
					dolibarr_install_syslog('install/etape5.php FailedToCreateAdminLogin '.$newuser->error, LOG_ERR);
					print '<br>'.$langs->trans("FailedToCreateAdminLogin").' '.$newuser->error.'<br><br>';
				}
			}

			if ($success)
			{
				$db->begin();

				dolibarr_install_syslog('install/etape5.php set MAIN_VERSION_LAST_INSTALL const to '.$targetversion, LOG_DEBUG);
				$db->query("DELETE FROM llx_const WHERE name='MAIN_VERSION_LAST_INSTALL'");
				$db->query("INSERT INTO llx_const(name,value,type,visible,note,entity) values('MAIN_VERSION_LAST_INSTALL','".$targetversion."','chaine',0,'Dolibarr version when install',0)");
				$conf->global->MAIN_VERSION_LAST_INSTALL=$targetversion;

				dolibarr_install_syslog('install/etape5.php Remove MAIN_NOT_INSTALLED const', LOG_DEBUG);
				$db->query("DELETE FROM llx_const WHERE name='MAIN_NOT_INSTALLED'");

				$db->commit();
			}
		}
		else
		{
			print $langs->trans("Error")."<br>";
		}
	}
	// If upgrade
	elseif ($_POST["action"] == "upgrade")
	{
		dolibarr_install_syslog('install/etape5.php set MAIN_VERSION_LAST_UPGRADE const to value '.$targetversion, LOG_DEBUG);
		$db->query("DELETE FROM llx_const WHERE name='MAIN_VERSION_LAST_UPGRADE'");
		$db->query("INSERT INTO llx_const(name,value,type,visible,note,entity) values('MAIN_VERSION_LAST_UPGRADE','".$targetversion."','chaine',0,'Dolibarr version for last upgrade',0)");
		$conf->global->MAIN_VERSION_LAST_UPGRADE=$targetversion;
	}
	else
	{
		dol_print_error('','install/etape5.php Unknown choice of action');
	}

	// May fail if parameter already defined
	$resql=$db->query("INSERT INTO llx_const(name,value,type,visible,note,entity) values('MAIN_LANG_DEFAULT','".$setuplang."','chaine',0,'Default language',1)");

	print '</table>';

	$db->close();
}

print "<br>";


// Create lock file

// If first install
if ($_POST["action"] == "set")
{
	if (empty($conf->global->MAIN_VERSION_LAST_UPGRADE) || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION))
	{
		// Install is finished
		print $langs->trans("SystemIsInstalled")."<br>";
		if (empty($force_install_lockinstall))
		{
			print '<div class="warning">'.$langs->trans("WarningRemoveInstallDir")."</div>";
		}
		else
		{
			// Install is finished, we create the lock file
			$fp = fopen("../../install.lock", "w");
			fwrite($fp, "This is a lock file to prevent use of install pages");
			fclose($fp);
		}

		print "<br>";

		print $langs->trans("YouNeedToPersonalizeSetup")."<br><br>";

		print '<center><a href="'.$dolibarr_main_url_root .'/admin/index.php?mainmenu=home&leftmenu=setup'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
		print $langs->trans("GoToSetupArea");
		print '</a></center>';
	}
	else
	{
		// If here MAIN_VERSION_LAST_UPGRADE is not empty
		print $langs->trans("VersionLastUpgrade").': <b><font class="ok">'.$conf->global->MAIN_VERSION_LAST_UPGRADE.'</font></b><br>';
		print $langs->trans("VersionProgram").': <b><font class="ok">'.DOL_VERSION.'</font></b>';

		print "<br>";

		print '<center><a href="'.$dolibarr_main_url_root .'/install/index.php">';
		print $langs->trans("GoToUpgradePage");
		print '</a></center>';
	}
}
// If upgrade
elseif ($_POST["action"] == "upgrade")
{
	if (empty($conf->global->MAIN_VERSION_LAST_UPGRADE) || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION))
	{
		// Upgrade is finished
		print $langs->trans("SystemIsUpgraded")."<br>";
		if (empty($force_install_lockinstall))
		{
			print '<div class="warning">'.$langs->trans("WarningRemoveInstallDir")."</div>";
		}
		else
		{
			// Upgrade is finished, we create the lock file
			$fp = fopen("../../install.lock", "w");
			fwrite($fp, "This is a lock file to prevent use of install pages");
			fclose($fp);
		}

		print "<br>";

		print '<center><a href="'.$dolibarr_main_url_root .'/index.php?mainmenu=home'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
		print $langs->trans("GoToDolibarr");
		print '</a></center>';
	}
	else
	{
		// If here MAIN_VERSION_LAST_UPGRADE is not empty
		print $langs->trans("VersionLastUpgrade").': <b><font class="ok">'.$conf->global->MAIN_VERSION_LAST_UPGRADE.'</font></b><br>';
		print $langs->trans("VersionProgram").': <b><font class="ok">'.DOL_VERSION.'</font></b>';

		print "<br>";

		print '<center><a href="'.$dolibarr_main_url_root .'/install/index.php">';
		print $langs->trans("GoToUpgradePage");
		print '</a></center>';
	}
}
else
{
	dol_print_error('','install/etape5.php Unknown choice of action');
}



// Clear cache files
clearstatcache();


dolibarr_install_syslog("install/etape5.php Dolibarr setup finished", LOG_INFO);

pFooter(1,$setuplang);
?>