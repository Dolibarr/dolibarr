<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien DiCintio      <sdicintio@ressource-toi.org>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2015-2016  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *       \file      htdocs/install/step5.php
 *       \ingroup   install
 *       \brief     Last page of upgrade or install process
 */

include_once 'inc.php';
if (file_exists($conffile)) include_once $conffile;
require_once $dolibarr_main_document_root . '/core/lib/admin.lib.php';
require_once $dolibarr_main_document_root . '/core/lib/security.lib.php'; // for dol_hash

global $langs;

$setuplang=GETPOST("selectlang",'',3)?GETPOST("selectlang",'',3):'auto';
$langs->setDefaultLang($setuplang);
$versionfrom=GETPOST("versionfrom",'',3)?GETPOST("versionfrom",'',3):(empty($argv[1])?'':$argv[1]);
$versionto=GETPOST("versionto",'',3)?GETPOST("versionto",'',3):(empty($argv[2])?'':$argv[2]);
$action=GETPOST('action', 'alpha');

// Define targetversion used to update MAIN_VERSION_LAST_INSTALL for first install
// or MAIN_VERSION_LAST_UPGRADE for upgrade.
$targetversion=DOL_VERSION;		// It it's last upgrade
if (! empty($action) && preg_match('/upgrade/i', $action))	// If it's an old upgrade
{
    $tmp=explode('_', $action, 2);
    if ($tmp[0]=='upgrade' && ! empty($tmp[1])) $targetversion=$tmp[1];
}

$langs->load("admin");
$langs->load("install");

$login = GETPOST('login', 'alpha');
$pass = GETPOST('pass', 'alpha');
$pass_verif = GETPOST('pass_verif', 'alpha');

$success=0;

$useforcedwizard=false;
$forcedfile="./install.forced.php";
if ($conffile == "/etc/dolibarr/conf.php") $forcedfile="/etc/dolibarr/install.forced.php";
if (@file_exists($forcedfile)) {
	$useforcedwizard = true;
	include_once $forcedfile;
	// If forced install is enabled, let's replace post values. These are empty because form fields are disabled.
	if ($force_install_noedit == 2) {
		if (!empty($force_install_dolibarrlogin)) {
			$login = $force_install_dolibarrlogin;
		}
	}
}

dolibarr_install_syslog("--- step5: entering step5.php page");


/*
 *	Actions
 */

// If install, check pass and pass_verif used to create admin account
if ($action == "set") {
	if ($pass <> $pass_verif) {
		header("Location: step4.php?error=1&selectlang=$setuplang" . (isset($login) ? '&login=' . $login : ''));
		exit;
	}

	if (dol_strlen(trim($pass)) == 0) {
		header("Location: step4.php?error=2&selectlang=$setuplang" . (isset($login) ? '&login=' . $login : ''));
		exit;
	}

	if (dol_strlen(trim($login)) == 0) {
		header("Location: step4.php?error=3&selectlang=$setuplang" . (isset($login) ? '&login=' . $login : ''));
		exit;
	}
}


/*
 *	View
 */

pHeader($langs->trans("SetupEnd"),"step5");
print '<br>';

// Test if we can run a first install process
if (empty($versionfrom) && empty($versionto) && ! is_writable($conffile))
{
    print $langs->trans("ConfFileIsNotWritable",$conffiletoshow);
    pFooter(1,$setuplang,'jscheckparam');
    exit;
}

if ($action == "set" || empty($action) || preg_match('/upgrade/i',$action))
{
    $error=0;

    // If password is encoded, we decode it
    if (preg_match('/crypted:/i',$dolibarr_main_db_pass) || ! empty($dolibarr_main_db_encrypted_pass))
    {
        require_once $dolibarr_main_document_root.'/core/lib/security.lib.php';
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

    $db=getDoliDBInstance($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

    // Create the global $hookmanager object
    include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
    $hookmanager=new HookManager($db);

    $ok = 0;

    // If first install
    if ($action == "set")
    {
        // Active module user
        $modName='modUser';
        $file = $modName . ".class.php";
        dolibarr_install_syslog('step5: load module user ' . DOL_DOCUMENT_ROOT . "/core/modules/" . $file, LOG_INFO);
        include_once DOL_DOCUMENT_ROOT ."/core/modules/".$file;
        $objMod = new $modName($db);
        $result=$objMod->init();
        if (! $result) print 'ERROR in activating module file='.$file;

        if ($db->connected)
        {
            $conf->setValues($db);

            // Create user
            include_once DOL_DOCUMENT_ROOT .'/user/class/user.class.php';

            // Set default encryption to yes if there is no user yet into database
		    $sql = "SELECT u.rowid, u.pass, u.pass_crypted";
		    $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		    //$sql.= " WHERE u.pass IS NOT NULL AND LENGTH(u.pass) < 32"; // Not a MD5 value
		    $resql=$db->query($sql);
		    if ($resql)
		    {
		        $numrows=$db->num_rows($resql);
    			if ($numrows == 0) dolibarr_set_const($db, "DATABASE_PWD_ENCRYPTED", "1",'chaine',0,'',$conf->entity);
		    }            
            
		    // Create user used to create the admin user
            $createuser=new User($db);
            $createuser->id=0;
            $createuser->admin=1;
            
            // Set admin user
            $newuser = new User($db);
            $newuser->lastname='SuperAdmin';
            $newuser->firstname='';
            $newuser->login = $login;
            $newuser->pass = $pass;
            $newuser->admin=1;
            $newuser->entity=0;

            $conf->global->USER_MAIL_REQUIRED=0;     // Force global option to be sure to create a new user with no email
            $result=$newuser->create($createuser,1);
            if ($result > 0)
            {
                print $langs->trans("AdminLoginCreatedSuccessfuly", $login) . "<br>";
                $success = 1;
            }
            else
            {
                if ($newuser->error == 'ErrorLoginAlreadyExists')
                {
                    dolibarr_install_syslog('step5: AdminLoginAlreadyExists', LOG_WARNING);
                    print '<br><div class="warning">' . $langs->trans("AdminLoginAlreadyExists", $login) . "</div><br>";
                    $success = 1;
                }
                else
                {
                    dolibarr_install_syslog('step5: FailedToCreateAdminLogin ' . $newuser->error, LOG_ERR);
                    print '<br><div class="error">'.$langs->trans("FailedToCreateAdminLogin").' '.$newuser->error.'</div><br><br>';
                }
            }

            if ($success)
            {
                $db->begin();

                dolibarr_install_syslog('step5: set MAIN_VERSION_LAST_INSTALL const to ' . $targetversion, LOG_DEBUG);
                $resql=$db->query("DELETE FROM ".MAIN_DB_PREFIX."const WHERE ".$db->decrypt('name')."='MAIN_VERSION_LAST_INSTALL'");
                if (! $resql) dol_print_error($db,'Error in setup program');
                $resql=$db->query("INSERT INTO ".MAIN_DB_PREFIX."const(name,value,type,visible,note,entity) values(".$db->encrypt('MAIN_VERSION_LAST_INSTALL',1).",".$db->encrypt($targetversion,1).",'chaine',0,'Dolibarr version when install',0)");
                if (! $resql) dol_print_error($db,'Error in setup program');
                $conf->global->MAIN_VERSION_LAST_INSTALL=$targetversion;

                if ($useforcedwizard)
                {
                    dolibarr_install_syslog('step5: set MAIN_REMOVE_INSTALL_WARNING const to 1', LOG_DEBUG);
                    $resql=$db->query("DELETE FROM ".MAIN_DB_PREFIX."const WHERE ".$db->decrypt('name')."='MAIN_REMOVE_INSTALL_WARNING'");
                    if (! $resql) dol_print_error($db,'Error in setup program');
                    $resql=$db->query("INSERT INTO ".MAIN_DB_PREFIX."const(name,value,type,visible,note,entity) values(".$db->encrypt('MAIN_REMOVE_INSTALL_WARNING',1).",".$db->encrypt(1,1).",'chaine',1,'Disable install warnings',0)");
                    if (! $resql) dol_print_error($db,'Error in setup program');
                    $conf->global->MAIN_REMOVE_INSTALL_WARNING=1;
                }

                // If we ask to force some modules to be enabled
                if (! empty($force_install_module))
                {
                    if (! defined('DOL_DOCUMENT_ROOT') && ! empty($dolibarr_main_document_root)) define('DOL_DOCUMENT_ROOT',$dolibarr_main_document_root);

                    $tmparray=explode(',',$force_install_module);
                    foreach ($tmparray as $modtoactivate)
                    {
                        $modtoactivatenew=preg_replace('/\.class\.php$/i','',$modtoactivate);
                        print $langs->trans("ActivateModule",$modtoactivatenew).'<br>';

                        $file=$modtoactivatenew.'.class.php';
                        dolibarr_install_syslog('step5: activate module file=' . $file);
                        $res=dol_include_once("/core/modules/".$file);

                        $res=activateModule($modtoactivatenew,1);
                        if (! empty($res['errors'])) print 'ERROR in activating module file='.$file;
                    }
                }

                dolibarr_install_syslog('step5: remove MAIN_NOT_INSTALLED const');
                $resql=$db->query("DELETE FROM ".MAIN_DB_PREFIX."const WHERE ".$db->decrypt('name')."='MAIN_NOT_INSTALLED'");
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
    elseif (empty($action) || preg_match('/upgrade/i',$action))
    {
        if ($db->connected)
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
                dolibarr_install_syslog('step5: set MAIN_VERSION_LAST_UPGRADE const to value ' . $targetversion);
                $resql=$db->query("DELETE FROM ".MAIN_DB_PREFIX."const WHERE ".$db->decrypt('name')."='MAIN_VERSION_LAST_UPGRADE'");
                if (! $resql) dol_print_error($db,'Error in setup program');
                $resql=$db->query("INSERT INTO ".MAIN_DB_PREFIX."const(name,value,type,visible,note,entity) VALUES (".$db->encrypt('MAIN_VERSION_LAST_UPGRADE',1).",".$db->encrypt($targetversion,1).",'chaine',0,'Dolibarr version for last upgrade',0)");
                if (! $resql) dol_print_error($db,'Error in setup program');
                $conf->global->MAIN_VERSION_LAST_UPGRADE=$targetversion;
            }
            else
            {
                dolibarr_install_syslog('step5: we run an upgrade to version ' . $targetversion . ' but database was already upgraded to ' . $conf->global->MAIN_VERSION_LAST_UPGRADE . '. We keep MAIN_VERSION_LAST_UPGRADE as it is.');
            }
        }
        else
        {
            print $langs->trans("ErrorFailedToConnect")."<br>";
        }
    }
    else
    {
        dol_print_error('','step5.php: unknown choice of action');
    }

    // May fail if parameter already defined
    $resql=$db->query("INSERT INTO ".MAIN_DB_PREFIX."const(name,value,type,visible,note,entity) VALUES (".$db->encrypt('MAIN_LANG_DEFAULT',1).",".$db->encrypt($setuplang,1).",'chaine',0,'Default language',1)");
    //if (! $resql) dol_print_error($db,'Error in setup program');

    $db->close();
}



// Create lock file

// If first install
if ($action == "set" && $success) {
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

        print '<div class="center"><a href="../admin/index.php?mainmenu=home&leftmenu=setup' . (isset($login) ? '&username=' . urlencode($login) : '') . '">';
        print $langs->trans("GoToSetupArea");
        print '</a></div>';
    }
    else
    {
        // If here MAIN_VERSION_LAST_UPGRADE is not empty
        print $langs->trans("VersionLastUpgrade").': <b><font class="ok">'.$conf->global->MAIN_VERSION_LAST_UPGRADE.'</font></b><br>';
        print $langs->trans("VersionProgram").': <b><font class="ok">'.DOL_VERSION.'</font></b><br>';
        print $langs->trans("MigrationNotFinished").'<br>';
        print "<br>";

        print '<div class="center"><a href="'.$dolibarr_main_url_root .'/install/index.php">';
        print $langs->trans("GoToUpgradePage");
        print '</a></div>';
    }
}
// If upgrade
elseif (empty($action) || preg_match('/upgrade/i',$action))
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

        print '<div class="center"><a href="../index.php?mainmenu=home' . (isset($login) ? '&username=' . urlencode($login) : '') . '">';
        print $langs->trans("GoToDolibarr");
        print '</a></div>';
    }
    else
    {
        // If here MAIN_VERSION_LAST_UPGRADE is not empty
        print $langs->trans("VersionLastUpgrade").': <b><font class="ok">'.$conf->global->MAIN_VERSION_LAST_UPGRADE.'</font></b><br>';
        print $langs->trans("VersionProgram").': <b><font class="ok">'.DOL_VERSION.'</font></b>';

        print "<br>";

        print '<div class="center"><a href="../install/index.php">';
        print $langs->trans("GoToUpgradePage");
        print '</a></div>';
    }
}
else
{
    dol_print_error('','step5.php: unknown choice of action');
}



// Clear cache files
clearstatcache();


dolibarr_install_syslog("--- step5: Dolibarr setup finished");

pFooter(1,$setuplang);
