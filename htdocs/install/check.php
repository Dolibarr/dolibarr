<?php
/* Copyright (C) 2004-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/install/check.php
 *	\ingroup    install
 *	\brief      Test if file conf can be modified and if does not exists, test if install process can create it
 */
include_once 'inc.php';

global $langs;

$err = 0;
$allowinstall = 0;
$allowupgrade = false;
$checksok = 1;

$setuplang = GETPOST("selectlang", 'aZ09', 3) ?GETPOST("selectlang", 'aZ09', 3) : $langs->getDefaultLang();
$langs->setDefaultLang($setuplang);

$langs->load("install");

// Now we load forced/pre-set values from install.forced.php file.
$useforcedwizard = false;
$forcedfile = "./install.forced.php";
if ($conffile == "/etc/dolibarr/conf.php") $forcedfile = "/etc/dolibarr/install.forced.php";
if (@file_exists($forcedfile)) {
	$useforcedwizard = true;
	include_once $forcedfile;
}

dolibarr_install_syslog("- check: Dolibarr install/upgrade process started");


/*
 *	View
 */

pHeader('', ''); // No next step for navigation buttons. Next step is defined by click on links.


//print "<br>\n";
//print $langs->trans("InstallEasy")."<br><br>\n";

print '<h3><img class="valigntextbottom" src="../theme/common/octicons/build/svg/gear.svg" width="20" alt="Database"> '.$langs->trans("MiscellaneousChecks").":</h3>\n";

// Check browser
$useragent = $_SERVER['HTTP_USER_AGENT'];
if (!empty($useragent))
{
    $tmp = getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
    $browserversion = $tmp['browserversion'];
    $browsername = $tmp['browsername'];
    if ($browsername == 'ie' && $browserversion < 7) print '<img src="../theme/eldy/img/warning.png" alt="Error"> '.$langs->trans("WarningBrowserTooOld")."<br>\n";
}


// Check PHP version
$arrayphpminversionerror = array(5, 5, 0);
$arrayphpminversionwarning = array(5, 5, 0);
if (versioncompare(versionphparray(), $arrayphpminversionerror) < 0)        // Minimum to use (error if lower)
{
	print '<img src="../theme/eldy/img/error.png" alt="Error"> '.$langs->trans("ErrorPHPVersionTooLow", versiontostring($arrayphpminversionerror));
	$checksok = 0; // 0=error, 1=warning
}
elseif (versioncompare(versionphparray(), $arrayphpminversionwarning) < 0)    // Minimum supported (warning if lower)
{
    print '<img src="../theme/eldy/img/warning.png" alt="Error"> '.$langs->trans("ErrorPHPVersionTooLow", versiontostring($arrayphpminversionwarning));
    $checksok = 0; // 0=error, 1=warning
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPVersion")." ".versiontostring(versionphparray());
}
if (empty($force_install_nophpinfo)) print ' (<a href="phpinfo.php" target="_blank">'.$langs->trans("MoreInformation").'</a>)';
print "<br>\n";


// Check PHP support for $_POST
if (!isset($_GET["testget"]) && !isset($_POST["testpost"]))
{
	print '<img src="../theme/eldy/img/warning.png" alt="Warning"> '.$langs->trans("PHPSupportPOSTGETKo");
	print ' (<a href="'.$_SERVER["PHP_SELF"].'?testget=ok">'.$langs->trans("Recheck").'</a>)';
	print "<br>\n";
	$checksok = 0;
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportPOSTGETOk")."<br>\n";
}


// Check if sessions enabled
if (!function_exists("session_id"))
{
	print '<img src="../theme/eldy/img/error.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportSessions")."<br>\n";
	$checksok = 0;
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportSessions")."<br>\n";
}


// Check if GD supported (we need GD for image conversion)
if (!function_exists("imagecreate"))
{
	$langs->load("errors");
	print '<img src="../theme/eldy/img/warning.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportGD")."<br>\n";
	// $checksok = 0;		// If ko, just warning. So check must still be 1 (otherwise no way to install)
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportGD")."<br>\n";
}


// Check if Curl supported
if (!function_exists("curl_init"))
{
    $langs->load("errors");
    print '<img src="../theme/eldy/img/warning.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportCurl")."<br>\n";
    // $checksok = 0;		// If ko, just warning. So check must still be 1 (otherwise no way to install)
}
else
{
    print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportCurl")."<br>\n";
}

// Check if PHP calendar extension is available
if (!function_exists("easter_date"))
{
    print '<img src="../theme/eldy/img/warning.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportCalendar")."<br>\n";
}
else
{
    print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportCalendar")."<br>\n";
}


// Check if UTF8 supported
if (!function_exists("utf8_encode"))
{
	$langs->load("errors");
	print '<img src="../theme/eldy/img/warning.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportUTF8")."<br>\n";
	// $checksok = 0;		// If ko, just warning. So check must still be 1 (otherwise no way to install)
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportUTF8")."<br>\n";
}


// Check if intl methods are supported
if (empty($_SERVER["SERVER_ADMIN"]) || $_SERVER["SERVER_ADMIN"] != 'doliwamp@localhost')
{
	if (!function_exists("locale_get_primary_language") || !function_exists("locale_get_region"))
	{
	    $langs->load("errors");
	    print '<img src="../theme/eldy/img/warning.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportIntl")."<br>\n";
	    // $checksok = 0;		// If ko, just warning. So check must still be 1 (otherwise no way to install)
	}
	else
	{
	    print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportIntl")."<br>\n";
	}
}

if (!class_exists('ZipArchive'))
{
	$langs->load("errors");
	print '<img src="../theme/eldy/img/warning.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupport", "ZIP")."<br>\n";
	// $checksok = 0;		// If ko, just warning. So check must still be 1 (otherwise no way to install)
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupport", "ZIP")."<br>\n";
}

// Check memory
$memrequiredorig = '64M';
$memrequired = 64 * 1024 * 1024;
$memmaxorig = @ini_get("memory_limit");
$memmax = @ini_get("memory_limit");
if ($memmaxorig != '')
{
	preg_match('/([0-9]+)([a-zA-Z]*)/i', $memmax, $reg);
	if ($reg[2])
	{
		if (strtoupper($reg[2]) == 'G') $memmax = $reg[1] * 1024 * 1024 * 1024;
		if (strtoupper($reg[2]) == 'M') $memmax = $reg[1] * 1024 * 1024;
		if (strtoupper($reg[2]) == 'K') $memmax = $reg[1] * 1024;
	}
	if ($memmax >= $memrequired || $memmax == -1)
	{
		print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPMemoryOK", $memmaxorig, $memrequiredorig)."<br>\n";
	}
	else
	{
		print '<img src="../theme/eldy/img/warning.png" alt="Warning"> '.$langs->trans("PHPMemoryTooLow", $memmaxorig, $memrequiredorig)."<br>\n";
	}
}


// If config file present and filled
clearstatcache();
if (is_readable($conffile) && filesize($conffile) > 8)
{
	dolibarr_install_syslog("check: conf file '".$conffile."' already defined");
	$confexists = 1;
	include_once $conffile;

	$databaseok = 1;
	if ($databaseok)
	{
		// Already installed for all parts (config and database). We can propose upgrade.
		$allowupgrade = true;
	}
	else
	{
		$allowupgrade = false;
	}
}
else
{
	// If not, we create it
	dolibarr_install_syslog("check: we try to create conf file '".$conffile."'");
	$confexists = 0;

	// First we try by copying example
	if (@copy($conffile.".example", $conffile))
	{
		// Success
		dolibarr_install_syslog("check: successfully copied file ".$conffile.".example into ".$conffile);
	}
	else
	{
		// If failed, we try to create an empty file
		dolibarr_install_syslog("check: failed to copy file ".$conffile.".example into ".$conffile.". We try to create it.", LOG_WARNING);

		$fp = @fopen($conffile, "w");
		if ($fp)
		{
			@fwrite($fp, '<?php');
			@fputs($fp, "\n");
			fclose($fp);
		}
		else dolibarr_install_syslog("check: failed to create a new file ".$conffile." into current dir ".getcwd().". Please check permissions.", LOG_ERR);
	}

	// First install: no upgrade necessary/required
	$allowupgrade = false;
}



// File is missing and cannot be created
if (!file_exists($conffile))
{
	print '<img src="../theme/eldy/img/error.png" alt="Error"> '.$langs->trans("ConfFileDoesNotExistsAndCouldNotBeCreated", $conffiletoshow);
	print "<br><br>";
	print $langs->trans("YouMustCreateWithPermission", $conffiletoshow);
	print "<br><br>";

	print $langs->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'].'?testget=ok');
	$err++;
}
else
{
	if (dol_is_dir($conffile))
	{
		print '<img src="../theme/eldy/img/error.png" alt="Warning"> '.$langs->trans("ConfFileMustBeAFileNotADir", $conffiletoshow);

		$allowinstall = 0;
	}
	// File exists but cannot be modified
	elseif (!is_writable($conffile))
	{
		if ($confexists)
		{
			print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("ConfFileExists", $conffiletoshow);
		}
		else
		{
			print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("ConfFileCouldBeCreated", $conffiletoshow);
		}
		print "<br>";
		print '<img src="../theme/eldy/img/tick.png" alt="Warning"> '.$langs->trans("ConfFileIsNotWritable", $conffiletoshow);
		print "<br>\n";

		$allowinstall = 0;
	}
	// File exists and can be modified
	else
	{
		if ($confexists)
		{
			print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("ConfFileExists", $conffiletoshow);
		}
		else
		{
			print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("ConfFileCouldBeCreated", $conffiletoshow);
		}
		print "<br>";
		print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("ConfFileIsWritable", $conffiletoshow);
		print "<br>\n";

		$allowinstall = 1;
	}
	print "<br>\n";

	// Requirements met/all ok: display the next step button
	if ($checksok)
	{
		$ok = 0;

		// Try to create db connection
		if (file_exists($conffile))
		{
			include_once $conffile;
			if (!empty($dolibarr_main_db_type) && !empty($dolibarr_main_document_root))
			{
				if (!file_exists($dolibarr_main_document_root."/core/lib/admin.lib.php"))
				{
                    print '<span class="error">A '.$conffiletoshow.' file exists with a dolibarr_main_document_root to '.$dolibarr_main_document_root.' that seems wrong. Try to fix or remove the '.$conffiletoshow.' file.</span><br>'."\n";
				    dol_syslog("A '".$conffiletoshow."' file exists with a dolibarr_main_document_root to ".$dolibarr_main_document_root." that seems wrong. Try to fix or remove the '".$conffiletoshow."' file.", LOG_WARNING);
				}
				else
				{
                    require_once $dolibarr_main_document_root.'/core/lib/admin.lib.php';

                    // If password is encoded, we decode it
                    if (preg_match('/crypted:/i', $dolibarr_main_db_pass) || !empty($dolibarr_main_db_encrypted_pass))
                    {
                        require_once $dolibarr_main_document_root.'/core/lib/security.lib.php';
                        if (preg_match('/crypted:/i', $dolibarr_main_db_pass))
                        {
                            $dolibarr_main_db_encrypted_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass); // We need to set this as it is used to know the password was initially crypted
                            $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
                        }
                        else $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
                    }

    				// $conf already created in inc.php
    				$conf->db->type = $dolibarr_main_db_type;
    				$conf->db->host = $dolibarr_main_db_host;
    				$conf->db->port = $dolibarr_main_db_port;
    				$conf->db->name = $dolibarr_main_db_name;
    				$conf->db->user = $dolibarr_main_db_user;
    				$conf->db->pass = $dolibarr_main_db_pass;
                    $db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, $conf->db->port);
    				if ($db->connected && $db->database_selected)
    				{
    					$ok = true;
    				}
                }
			}
		}

		// If database access is available, we set more variables
		if ($ok)
		{
			if (empty($dolibarr_main_db_encryption)) $dolibarr_main_db_encryption = 0;
			$conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
			if (empty($dolibarr_main_db_cryptkey)) $dolibarr_main_db_cryptkey = '';
			$conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

			$conf->setValues($db);
			// Reset forced setup after the setValues
			if (defined('SYSLOG_FILE')) $conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
			$conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

			// Current version is $conf->global->MAIN_VERSION_LAST_UPGRADE
			// Version to install is DOL_VERSION
			$dolibarrlastupgradeversionarray = preg_split('/[\.-]/', isset($conf->global->MAIN_VERSION_LAST_UPGRADE) ? $conf->global->MAIN_VERSION_LAST_UPGRADE : (isset($conf->global->MAIN_VERSION_LAST_INSTALL) ? $conf->global->MAIN_VERSION_LAST_INSTALL : ''));
			$dolibarrversiontoinstallarray = versiondolibarrarray();
		}

		// Show title
		if (!empty($conf->global->MAIN_VERSION_LAST_UPGRADE) || !empty($conf->global->MAIN_VERSION_LAST_INSTALL))
		{
            print $langs->trans("VersionLastUpgrade").': <b><span class="ok">'.(empty($conf->global->MAIN_VERSION_LAST_UPGRADE) ? $conf->global->MAIN_VERSION_LAST_INSTALL : $conf->global->MAIN_VERSION_LAST_UPGRADE).'</span></b> - ';
            print $langs->trans("VersionProgram").': <b><span class="ok">'.DOL_VERSION.'</span></b>';
			//print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired"));
			print '<br>';
			print '<br>';
		}
		else print "<br>\n";

		//print $langs->trans("InstallEasy")." ";
		print '<h3><span class="soustitre">'.$langs->trans("ChooseYourSetupMode").'</span></h3>';

		$foundrecommandedchoice = 0;

        $available_choices = array();
        $notavailable_choices = array();

        if (empty($dolibarr_main_db_host))	// This means install process was not run
        {
        	$foundrecommandedchoice = 1; // To show only once
        }

        // Show line of first install choice
        $choice  = '<tr class="trlineforchoice'.($foundrecommandedchoice ? ' choiceselected' : '').'">'."\n";
        $choice .= '<td class="nowrap center"><b>'.$langs->trans("FreshInstall").'</b>';
		$choice .= '</td>';
        $choice .= '<td class="listofchoicesdesc">';
		$choice .= $langs->trans("FreshInstallDesc");
		if (empty($dolibarr_main_db_host))	// This means install process was not run
		{
			$choice .= '<br>';
			//print $langs->trans("InstallChoiceRecommanded",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_UPGRADE);
			$choice .= '<div class="center"><div class="ok suggestedchoice">'.$langs->trans("InstallChoiceSuggested").'</div></div>';
			// <img src="../theme/eldy/img/tick.png" alt="Ok"> ';
		}

        $choice .= '</td>';
        $choice .= '<td class="center">';
		if ($allowinstall)
		{
            $choice .= '<a class="button" href="fileconf.php?selectlang='.$setuplang.'">'.$langs->trans("Start").'</a>';
		}
		else
		{
			$choice .= ($foundrecommandedchoice ? '<span class="warning">' : '').$langs->trans("InstallNotAllowed").($foundrecommandedchoice ? '</span>' : '');
		}
        $choice .= '</td>'."\n";
        $choice .= '</tr>'."\n";

        $positionkey = ($foundrecommandedchoice ? 999 : 0);
        if ($allowinstall) {
        	$available_choices[$positionkey] = $choice;
        } else {
        	$notavailable_choices[$positionkey] = $choice;
        }

		// Show upgrade lines
		$allowupgrade = true;
		if (empty($dolibarr_main_db_host))	// This means install process was not run
		{
			$allowupgrade = false;
		}
		if (defined("MAIN_NOT_INSTALLED")) $allowupgrade = false;
		if (GETPOST('allowupgrade')) $allowupgrade = true;
		$migrationscript = array(array('from'=>'3.0.0', 'to'=>'3.1.0'),
								array('from'=>'3.1.0', 'to'=>'3.2.0'),
								array('from'=>'3.2.0', 'to'=>'3.3.0'),
								array('from'=>'3.3.0', 'to'=>'3.4.0'),
								array('from'=>'3.4.0', 'to'=>'3.5.0'),
								array('from'=>'3.5.0', 'to'=>'3.6.0'),
								array('from'=>'3.6.0', 'to'=>'3.7.0'),
								array('from'=>'3.7.0', 'to'=>'3.8.0'),
								array('from'=>'3.8.0', 'to'=>'3.9.0'),
								array('from'=>'3.9.0', 'to'=>'4.0.0'),
								array('from'=>'4.0.0', 'to'=>'5.0.0'),
								array('from'=>'5.0.0', 'to'=>'6.0.0'),
								array('from'=>'6.0.0', 'to'=>'7.0.0'),
								array('from'=>'7.0.0', 'to'=>'8.0.0'),
								array('from'=>'8.0.0', 'to'=>'9.0.0'),
								array('from'=>'9.0.0', 'to'=>'10.0.0'),
								array('from'=>'10.0.0', 'to'=>'11.0.0'),
								array('from'=>'11.0.0', 'to'=>'12.0.0')
		);

		$count = 0;
		foreach ($migrationscript as $migarray)
		{
            $choice = '';

			$count++;
            $recommended_choice = false;
            $version = DOL_VERSION;
			$versionfrom = $migarray['from'];
            $versionto = $migarray['to'];
            $versionarray = preg_split('/[\.-]/', $version);
            $dolibarrversionfromarray = preg_split('/[\.-]/', $versionfrom);
            $dolibarrversiontoarray = preg_split('/[\.-]/', $versionto);
            // Define string newversionxxx that are used for text to show
            $newversionfrom = preg_replace('/(\.[0-9]+)$/i', '.*', $versionfrom);
            $newversionto = preg_replace('/(\.[0-9]+)$/i', '.*', $versionto);
            $newversionfrombis = '';
            if (versioncompare($dolibarrversiontoarray, $versionarray) < -2)	// From x.y.z -> x.y.z+1
            {
            	$newversionfrombis = ' '.$langs->trans("or").' '.$versionto;
            }

            if ($ok)
            {
                if (count($dolibarrlastupgradeversionarray) >= 2)	// If database access is available and last upgrade version is known
                {
                    // Now we check if this is the first qualified choice
                    if ($allowupgrade && empty($foundrecommandedchoice) &&
                        (versioncompare($dolibarrversiontoarray, $dolibarrlastupgradeversionarray) > 0 || versioncompare($dolibarrversiontoarray, $versionarray) < -2)
                    )
                    {
                        $foundrecommandedchoice = 1; // To show only once
                        $recommended_choice = true;
                    }
                }
                else {
                    // We cannot recommend a choice.
                    // A version of install may be known, but we need last upgrade.
                }
            }

            $choice .= "\n".'<!-- choice '.$count.' -->'."\n";
            $choice .= '<tr'.($recommended_choice ? ' class="choiceselected"' : '').'>';
            $choice .= '<td class="nowrap center"><b>'.$langs->trans("Upgrade").'<br>'.$newversionfrom.$newversionfrombis.' -> '.$newversionto.'</b></td>';
            $choice .= '<td class="listofchoicesdesc">';
            $choice .= $langs->trans("UpgradeDesc");

            if ($recommended_choice)
            {
                $choice .= '<br>';
                //print $langs->trans("InstallChoiceRecommanded",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_UPGRADE);
                $choice .= '<div class="center">';
                $choice .= '<div class="ok suggestedchoice">'.$langs->trans("InstallChoiceSuggested").'</div>';
                if ($count < count($migarray))	// There are other choices after
                {
                    print $langs->trans("MigrateIsDoneStepByStep", DOL_VERSION);
                }
                $choice .= '</div>';
            }

            $choice .= '</td>';
            $choice .= '<td class="center">';
			if ($allowupgrade)
			{
				$disabled = false;
				if ($foundrecommandedchoice == 2)
				{
					$disabled = true;
				}
				if ($foundrecommandedchoice == 1)
				{
					$foundrecommandedchoice = 2;
				}
				if ($disabled)
                {
                    $choice .= '<span class="opacitymedium">'.$langs->trans("NotYetAvailable").'</span>';
                }
				else
                {
                    $choice .= '<a class="button runupgrade" href="upgrade.php?action=upgrade'.($count < count($migrationscript) ? '_'.$versionto : '').'&amp;selectlang='.$setuplang.'&amp;versionfrom='.$versionfrom.'&amp;versionto='.$versionto.'">'.$langs->trans("Start").'</a>';
                }
			}
			else
			{
                $choice .= $langs->trans("NotAvailable");
			}
            $choice .= '</td>';
            $choice .= '</tr>'."\n";

            if ($allowupgrade) {
                $available_choices[$count] = $choice;
            } else {
                $notavailable_choices[$count] = $choice;
            }
		}

		// If there is no choice at all, we show all of them.
		if (empty($available_choices))
		{
			$available_choices = $notavailable_choices;
			$notavailable_choices = array();
		}

        // Array of install choices
		krsort($available_choices, SORT_NATURAL);
        print"\n";
        print '<table width="100%" class="listofchoices">';
        foreach ($available_choices as $choice) {
            print $choice;
        }

        print '</table>'."\n";

        if (count($notavailable_choices)) {
            print '<br><div id="AShowChoices" style="opacity: 0.5">';
            print '> '.$langs->trans('ShowNotAvailableOptions').'...';
            print '</div>';

            print '<div id="navail_choices" style="display:none">';
            print "<br>\n";
            print '<table width="100%" class="listofchoices">';
            foreach ($notavailable_choices as $choice) {
                print $choice;
            }

            print '</table>'."\n";
            print '</div>';
        }
    }
}

print '<script type="text/javascript">

$("div#AShowChoices").click(function() {

    $("div#navail_choices").toggle();

    if ($("div#navail_choices").css("display") == "none") {
        $(this).text("> '.$langs->trans('ShowNotAvailableOptions').'...");
    } else {
        $(this).text("'.$langs->trans('HideNotAvailableOptions').'...");
    }

});

/*
$(".runupgrade").click(function() {
	return confirm("'.dol_escape_js($langs->transnoentitiesnoconv("WarningUpgrade"), 0, 1).'");
});
*/

</script>';

dolibarr_install_syslog("- check: end");
pFooter(1); // Never display next button
