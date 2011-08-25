<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file      htdocs/install/etape5.php
 *	 	 \ingroup	install
 *       \brief     Last page of upgrade or install process
 *       \version   $Id: etape5.php,v 1.108 2011/08/04 12:07:30 eldy Exp $
 */

include_once("./inc.php");
if (file_exists($conffile)) include_once($conffile);
require_once($dolibarr_main_document_root . "/lib/databases/".$dolibarr_main_db_type.".lib.php");
require_once($dolibarr_main_document_root . "/lib/admin.lib.php");


$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

// Define targetversion used to update MAIN_VERSION_LAST_INSTALL for first install
// or MAIN_VERSION_LAST_UPGRADE for upgrade.
$targetversion=DOL_VERSION;		// It it's last upgrade
if (isset($_POST["action"]) && preg_match('/upgrade/i',$_POST["action"]))	// If it's an old upgrade
{
	$tmp=explode('_',$_POST["action"],2);
	if ($tmp[0]=='upgrade' && ! empty($tmp[1])) $targetversion=$tmp[1];
}

$langs->load("admin");
$langs->load("install");

$action=GETPOST('action');

$success=0;

// Init "forced values" to nothing. "forced values" are used after an doliwamp install wizard.
if (! isset($force_install_type))              $force_install_type='';
if (! isset($force_install_dbserver))          $force_install_dbserver='';
if (! isset($force_install_port))              $force_install_port='';
if (! isset($force_install_database))          $force_install_database='';
if (! isset($force_install_createdatabase))    $force_install_createdatabase='';
if (! isset($force_install_databaselogin))     $force_install_databaselogin='';
if (! isset($force_install_databasepass))      $force_install_databasepass='';
if (! isset($force_install_databaserootlogin)) $force_install_databaserootlogin='';
if (! isset($force_install_databaserootpass))  $force_install_databaserootpass='';
if (! isset($force_install_lockinstall))       $force_install_lockinstall='';
$useforcedwizard=false;
if (file_exists("./install.forced.php")) { $useforcedwizard=true; include_once("./install.forced.php"); }
else if (file_exists("/etc/dolibarr/install.forced.php")) { $useforcedwizard=include_once("/etc/dolibarr/install.forced.php"); }

dolibarr_install_syslog("--- etape5: Entering etape5.php page", LOG_INFO);


/*
 *	Actions
 */

// If install, check pass and pass_verif used to create admin account
if ($action == "set")
{
	if ($_POST["pass"] <> $_POST["pass_verif"])
	{
		Header("Location: etape4.php?error=1&selectlang=$setuplang".(isset($_POST["login"])?'&login='.$_POST["login"]:''));
		exit;
	}

	if (dol_strlen(trim($_POST["pass"])) == 0)
	{
		Header("Location: etape4.php?error=2&selectlang=$setuplang".(isset($_POST["login"])?'&login='.$_POST["login"]:''));
		exit;
	}

	if (dol_strlen(trim($_POST["login"])) == 0)
	{
		Header("Location: etape4.php?error=3&selectlang=$setuplang".(isset($_POST["login"])?'&login='.$_POST["login"]:''));
		exit;
	}
}


/*
 *	View
 */

pHeader($langs->trans("SetupEnd"),"etape5");
print '<br>';

// Test if we can run a first install process
if (! GETPOST("versionfrom") && ! GETPOST("versionto") && ! is_writable($conffile))
{
    print $langs->trans("ConfFileIsNotWritable",$conffiletoshow);
    pFooter(1,$setuplang,'jscheckparam');
    exit;
}

if ($action == "set" || preg_match('/upgrade/i',$action))
{
	print '<table cellspacing="0" cellpadding="2" width="100%">';
	$error=0;

	// If password is encoded, we decode it
	if (preg_match('/crypted:/i',$dolibarr_main_db_pass) || ! empty($dolibarr_main_db_encrypted_pass))
	{
		require_once($dolibarr_main_document_root."/lib/security.lib.php");
		if (preg_match('/crypted:/i',$dolibarr_main_db_pass))
		{
			$dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
			$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
			$dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass;	// We need to set this as it is used to know the password was initially crypted
		}
		else $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
	}

	$conf->db->type = $dolibarr_main_db_type;
	$conf->db->host = $dolibarr_main_db_host;
	$conf->db->port = $dolibarr_main_db_port;
	$conf->db->name = $dolibarr_main_db_name;
	$conf->db->user = $dolibarr_main_db_user;
	$conf->db->pass = $dolibarr_main_db_pass;
	$conf->db->dolibarr_main_db_encryption = isset($dolibarr_main_db_encryption)?$dolibarr_main_db_encryption:'';
	$conf->db->dolibarr_main_db_cryptkey = isset($dolibarr_main_db_cryptkey)?$dolibarr_main_db_cryptkey:'';

	$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

	$ok = 0;

	// If first install
	if ($action == "set")
	{
		// Active module user
		$modName='modUser';
		$file = $modName . ".class.php";
		dolibarr_install_syslog('install/etape5.php Load module user '.DOL_DOCUMENT_ROOT ."/includes/modules/".$file, LOG_INFO);
		include_once(DOL_DOCUMENT_ROOT ."/includes/modules/".$file);
		$objMod = new $modName($db);
		$result=$objMod->init();
        if (! $result) print 'ERROR in activating module file='.$file;

		if ($db->connected == 1)
		{
			$conf->setValues($db);

			// Create user
			include_once(DOL_DOCUMENT_ROOT ."/user/class/user.class.php");

			$createuser=new User($db);
			$createuser->id=0;

			$newuser = new User($db);
			$newuser->nom='SuperAdmin';
			$newuser->prenom='';
			$newuser->login=$_POST["login"];
			$newuser->pass=$_POST["pass"];
			$newuser->admin=1;
			$newuser->entity=0;

			$conf->global->USER_MAIL_REQUIRED=0;     // Force global option to be sure to create a new user with no email
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
					print '<br><div class="error">'.$langs->trans("FailedToCreateAdminLogin").' '.$newuser->error.'</div><br><br>';
				}
			}

			if ($success)
			{
				$db->begin();

				dolibarr_install_syslog('install/etape5.php set MAIN_VERSION_LAST_INSTALL const to '.$targetversion, LOG_DEBUG);
				$resql=$db->query("DELETE FROM llx_const WHERE ".$db->decrypt('name')."='MAIN_VERSION_LAST_INSTALL'");
				if (! $resql) dol_print_error($db,'Error in setup program');
				$resql=$db->query("INSERT INTO llx_const(name,value,type,visible,note,entity) values(".$db->encrypt('MAIN_VERSION_LAST_INSTALL',1).",".$db->encrypt($targetversion,1).",'chaine',0,'Dolibarr version when install',0)");
				if (! $resql) dol_print_error($db,'Error in setup program');
				$conf->global->MAIN_VERSION_LAST_INSTALL=$targetversion;

				if ($useforcedwizard)
				{
					dolibarr_install_syslog('install/etape5.php set MAIN_REMOVE_INSTALL_WARNING const to 1', LOG_DEBUG);
					$resql=$db->query("DELETE FROM llx_const WHERE ".$db->decrypt('name')."='MAIN_REMOVE_INSTALL_WARNING'");
					if (! $resql) dol_print_error($db,'Error in setup program');
					$resql=$db->query("INSERT INTO llx_const(name,value,type,visible,note,entity) values(".$db->encrypt('MAIN_REMOVE_INSTALL_WARNING',1).",".$db->encrypt(1,1).",'chaine',1,'Disable install warnings',0)");
					if (! $resql) dol_print_error($db,'Error in setup program');
					$conf->global->MAIN_REMOVE_INSTALL_WARNING=1;
				}

				// If we ask to force some modules to be enabled
				if (! empty($force_install_module))
				{
					if (! defined('DOL_DOCUMENT_ROOT') && ! empty($dolibarr_main_document_root)) define('DOL_DOCUMENT_ROOT',$dolibarr_main_document_root);
					if (! defined('DOL_DOCUMENT_ROOT_ALT') && ! empty($dolibarr_main_document_root_alt)) define('DOL_DOCUMENT_ROOT_ALT',$dolibarr_main_document_root_alt);

					$tmparray=explode(',',$force_install_module);
					foreach ($tmparray as $modtoactivate)
					{
						$modtoactivatenew=preg_replace('/\.class\.php$/i','',$modtoactivate);
                        $file=$modtoactivatenew.'.class.php';
						dolibarr_install_syslog('install/etape5.php Activate module file='.$file);
                        $res=dol_include_once("/includes/modules/".$file);

                        $res=Activate($modtoactivatenew,1);
                        if (! $result) print 'ERROR in activating module file='.$file;
					}
				}

				dolibarr_install_syslog('install/etape5.php Remove MAIN_NOT_INSTALLED const', LOG_DEBUG);
				$resql=$db->query("DELETE FROM llx_const WHERE ".$db->decrypt('name')."='MAIN_NOT_INSTALLED'");
				if (! $resql) dol_print_error($db,'Error in setup program');

				$db->commit();
			}
		}
		else
		{
			print $langs->trans("ErrorFailedToConnect")."<br>";
		}
	}
	// If upgrade
	elseif (preg_match('/upgrade/i',$action))
	{
		if ($db->connected == 1)
		{
			$conf->setValues($db);

			// Define if we need to update the MAIN_VERSION_LAST_UPGRADE value in database
			$tagdatabase=false;
			if (empty($conf->global->MAIN_VERSION_LAST_UPGRADE)) $tagdatabase=true;	// We don't know what it was before, so now we consider we are version choosed.
			else
			{
				$mainversionlastupgradearray=preg_split('/[.-]/',$conf->global->MAIN_VERSION_LAST_UPGRADE);
				$targetversionarray=preg_split('/[.-]/',$targetversion);
				if (versioncompare($targetversionarray,$mainversionlastupgradearray) > 0) $tagdatabase=true;
			}

			if ($tagdatabase)
			{
				dolibarr_install_syslog('install/etape5.php set MAIN_VERSION_LAST_UPGRADE const to value '.$targetversion, LOG_DEBUG);
				$resql=$db->query("DELETE FROM llx_const WHERE ".$db->decrypt('name')."='MAIN_VERSION_LAST_UPGRADE'");
				if (! $resql) dol_print_error($db,'Error in setup program');
				$resql=$db->query("INSERT INTO llx_const(name,value,type,visible,note,entity) values(".$db->encrypt('MAIN_VERSION_LAST_UPGRADE',1).",".$db->encrypt($targetversion,1).",'chaine',0,'Dolibarr version for last upgrade',0)");
				if (! $resql) dol_print_error($db,'Error in setup program');
				$conf->global->MAIN_VERSION_LAST_UPGRADE=$targetversion;
			}
			else
			{
				dolibarr_install_syslog('install/etape5.php We run an upgrade to version '.$targetversion.' but database was already upgraded to '.$conf->global->MAIN_VERSION_LAST_UPGRADE.'. We keep MAIN_VERSION_LAST_UPGRADE as it is.', LOG_DEBUG);
			}
		}
		else
		{
			print $langs->trans("ErrorFailedToConnect")."<br>";
		}
	}
	else
	{
		dol_print_error('','install/etape5.php Unknown choice of action');
	}

	// May fail if parameter already defined
	$resql=$db->query("INSERT INTO llx_const(name,value,type,visible,note,entity) values(".$db->encrypt('MAIN_LANG_DEFAULT',1).",".$db->encrypt($setuplang,1).",'chaine',0,'Default language',1)");
	//if (! $resql) dol_print_error($db,'Error in setup program');

	print '</table>';

	$db->close();
}

print "<br>";


// Create lock file

// If first install
if ($action == "set")
{
	if (empty($conf->global->MAIN_VERSION_LAST_UPGRADE) || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION))
	{
		// Install is finished
		print $langs->trans("SystemIsInstalled")."<br>";

		$createlock=0;

		if (! empty($force_install_lockinstall))
		{
			// Install is finished, we create the lock file
			$lockfile=DOL_DATA_ROOT.'/install.lock';
		    $fp = @fopen($lockfile, "w");
			if ($fp)
			{
                if ($force_install_lockinstall == 1) $force_install_lockinstall=444;    // For backward compatibility
                fwrite($fp, "This is a lock file to prevent use of install pages (set with permission ".$force_install_lockinstall.")");
				fclose($fp);
				@chmod($lockfile, octdec($force_install_lockinstall));
				$createlock=1;
			}
		}
		if (empty($createlock))
		{
			print '<div class="warning">'.$langs->trans("WarningRemoveInstallDir")."</div>";
		}

		print "<br>";

		print $langs->trans("YouNeedToPersonalizeSetup")."<br><br>";

		print '<center><a href="../admin/company.php?mainmenu=home&leftmenu=setup'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
		print $langs->trans("GoToSetupArea");
		print '</a></center>';
	}
	else
	{
		// If here MAIN_VERSION_LAST_UPGRADE is not empty
		print $langs->trans("VersionLastUpgrade").': <b><font class="ok">'.$conf->global->MAIN_VERSION_LAST_UPGRADE.'</font></b><br>';
		print $langs->trans("VersionProgram").': <b><font class="ok">'.DOL_VERSION.'</font></b><br>';
		print $langs->trans("MigrationNotFinished").'<br>';
		print "<br>";

		print '<center><a href="'.$dolibarr_main_url_root .'/install/index.php">';
		print $langs->trans("GoToUpgradePage");
		print '</a></center>';
	}
}
// If upgrade
elseif (preg_match('/upgrade/i',$action))
{
	if (empty($conf->global->MAIN_VERSION_LAST_UPGRADE) || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION))
	{
		// Upgrade is finished
		print $langs->trans("SystemIsUpgraded")."<br>";

		$createlock=0;

		if (! empty($force_install_lockinstall))
		{
			// Upgrade is finished, we create the lock file
			$lockfile=DOL_DATA_ROOT.'/install.lock';
		    $fp = @fopen($lockfile, "w");
			if ($fp)
			{
                if ($force_install_lockinstall == 1) $force_install_lockinstall=444;    // For backward compatibility
				fwrite($fp, "This is a lock file to prevent use of install pages (set with permission ".$force_install_lockinstall.")");
				fclose($fp);
                @chmod($lockfile, octdec($force_install_lockinstall));
				$createlock=1;
			}
		}
		if (empty($createlock))
		{
			print '<br><div class="warning">'.$langs->trans("WarningRemoveInstallDir")."</div>";
		}

		print "<br>";

		print '<center><a href="../index.php?mainmenu=home'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
		print $langs->trans("GoToDolibarr");
		print '</a></center>';
	}
	else
	{
		// If here MAIN_VERSION_LAST_UPGRADE is not empty
		print $langs->trans("VersionLastUpgrade").': <b><font class="ok">'.$conf->global->MAIN_VERSION_LAST_UPGRADE.'</font></b><br>';
		print $langs->trans("VersionProgram").': <b><font class="ok">'.DOL_VERSION.'</font></b>';

		print "<br>";

		print '<center><a href="../install/index.php">';
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


dolibarr_install_syslog("--- install/etape5.php Dolibarr setup finished", LOG_INFO);

pFooter(1,$setuplang);
?>