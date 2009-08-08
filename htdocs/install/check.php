<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Océbo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
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
 *	\file       htdocs/install/check.php
 *	\ingroup    install
 *	\brief      Test si le fichier conf est modifiable et si il n'existe pas, test la possibilité de le créer
 *	\version    $Id$
 */
include_once("./inc.php");

$err = 0;
$allowinstall = 0;
$allowupgrade = 0;
$checksok = 1;

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:$langs->getDefaultLang());
$langs->setDefaultLang($setuplang);

$langs->load("install");

// Init "forced values" to nothing. "forced values" are used after an doliwamp install wizard.
if (! isset($force_install_dolibarrlogin))     $force_install_dolibarrlogin='';
if (file_exists("./install.forced.php")) include_once("./install.forced.php");

dolibarr_install_syslog("check: Dolibarr install/upgrade process started");


/*
 *	View
 */

pHeader('',"");   // Etape suivante = license

print '<center>';
print '<img src="../theme/dolibarr_logo.png" alt="Dolibarr logo"><br>';
print DOL_VERSION.'<br><br>';
print '</center>';

//print "<br>\n";
//print $langs->trans("InstallEasy")."<br><br>\n";

print '<b>'.$langs->trans("MiscellanousChecks")."</b>:<br>\n";


// Check PHP version
if (versioncompare(versionphparray(),array(4,1)) < 0)
{
	print '<img src="../theme/eldy/img/error.png" alt="Error"> '.$langs->trans("ErrorPHPVersionTooLow",'4.1');
	$checksok=0;
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPVersion")." ".versiontostring(versionphparray());
}
if (empty($force_install_nophpinfo)) print ' (<a href="phpinfo.php" target="_blank">'.$langs->trans("MoreInformation").'</a>)';
print "<br>\n";


// Check PHP support for $_POST
if (! isset($_GET["testget"]) && ! isset($_POST["testpost"]))
{
	print '<img src="../theme/eldy/img/warning.png" alt="Warning"> '.$langs->trans("PHPSupportPOSTGETKo");
	print ' (<a href="'.$_SERVER["PHP_SELF"].'?testget=ok">'.$langs->trans("Recheck").'</a>)';
	print "<br>\n";
	$checksok=0;
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportPOSTGETOk")."<br>\n";
}


// Check if sessions enabled
if (! function_exists("session_id"))
{
	print '<img src="../theme/eldy/img/error.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportSessions")."<br>\n";
	$checksok=0;
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportSessions")."<br>\n";
}


// Check if GD supported
if (! function_exists("imagecreate"))
{
	$langs->load("errors");
	print '<img src="../theme/eldy/img/warning.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportGD")."<br>\n";
	// $checksok=0;		// If image ko, just warning. So check must still be 1 (otherwise no way to install)
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportGD")."<br>\n";
}


// Check if UTF8 supported
if (! function_exists("utf8_encode"))
{
	$langs->load("errors");
	print '<img src="../theme/eldy/img/warning.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportUTF8")."<br>\n";
	// $checksok=0;		// If image ko, just warning. So check must still be 1 (otherwise no way to install)
}
else
{
	print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportUTF8")."<br>\n";
}


// Check memory
$memrequiredorig='24M';
$memrequired=24*1024*1024;
$memmaxorig=@ini_get("memory_limit");
$memmax=@ini_get("memory_limit");
if ($memmaxorig != '')
{
	eregi('([0-9]+)([a-zA-Z]*)',$memmax,$reg);
	if ($reg[2])
	{
		if (strtoupper($reg[2]) == 'M') $memmax=$reg[1]*1024*1024;
		if (strtoupper($reg[2]) == 'K') $memmax=$reg[1]*1024;
	}
	if ($memmax >= $memrequired)
	{
		print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPMemoryOK",$memmaxorig,$memrequiredorig)."<br>\n";
	}
	else
	{
		print '<img src="../theme/eldy/img/warning.png" alt="Warning"> '.$langs->trans("PHPMemoryTooLow",$memmaxorig,$memrequiredorig)."<br>\n";
	}
}


// Si fichier présent et lisible et renseigné
clearstatcache();
if (is_readable($conffile) && filesize($conffile) > 8)
{
	dolibarr_install_syslog("check: conf file '$conffile' already exists");
	$confexists=1;
	include_once($conffile);

	// Deja installé, on peut upgrader
	// \todo Test if database ok
	$allowupgrade=1;
}
else
{
	// Si non on le crée
	dolibarr_install_syslog("check: we try to creat conf file '$conffile'");
	$confexists=0;

	# First we try by copying example
	if (@copy($conffile.".example", $conffile))
	{
		# Success
		dolibarr_install_syslog("check: copied file ".$conffile.".example into ".$conffile." done successfully.");
	}
	else
	{
		# If failed, we try to create an empty file
		dolibarr_install_syslog("check: failed to copy file ".$conffile.".example into ".$conffile.". We try to create it.");

		$fp = @fopen($conffile, "w");
		if ($fp)
		{
			@fwrite($fp, '<?php');
			@fputs($fp,"\n");
			@fputs($fp,"?>");
			fclose($fp);
		}
	}

	// First install, on ne peut pas upgrader
	$allowupgrade=0;
}



// Si fichier absent et n'a pu etre créé
if (! file_exists($conffile))
{
	//print '<img src="../theme/eldy/img/error.png" alt="Error"> '.$langs->trans("ConfFileDoesNotExistsAndCouldNotBeCreated",$conffile);
	print '<img src="../theme/eldy/img/error.png" alt="Error"> '.$langs->trans("ConfFileDoesNotExistsAndCouldNotBeCreated",'conf.php');
	print "<br /><br />";
	print $langs->trans("YouMustCreateWithPermission",'htdocs/conf/conf.php');
	print "<br /><br />";

	print $langs->trans("CorrectProblemAndReloadPage",$_SERVER['PHP_SELF'].'?testget=ok');
	$err++;
}
else
{
	// Si fichier présent mais ne peut etre modifié
	if (!is_writable($conffile))
	{
		if ($confexists)
		{
			print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("ConfFileExists",'conf.php');
		}
		else
		{
			print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("ConfFileCouldBeCreated",'conf.php');
		}
		print "<br />";
		print '<img src="../theme/eldy/img/tick.png" alt="Warning"> '.$langs->trans("ConfFileIsNotWritable",'htdocs/conf/conf.php');
		print "<br />\n";

		$allowinstall=0;
	}
	// Si fichier présent et peut etre modifié
	else
	{
		if ($confexists)
		{
			print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("ConfFileExists",'conf.php');
		}
		else
		{
			print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("ConfFileCouldBeCreated",'conf.php');
		}
		print "<br />";
		print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("ConfFileIsWritable",'conf.php');
		print "<br />\n";

		$allowinstall=1;
	}
	print "<br />\n";

	// Si prerequis ok, on affiche le bouton pour passer à l'étape suivante
	if ($checksok)
	{
		$ok=0;

		// Try to create db connexion
		if (file_exists($conffile))
		{
			include_once($conffile);
			if (! empty($dolibarr_main_db_type) && ! empty($dolibarr_main_document_root))
			{
				require_once($dolibarr_main_document_root."/lib/databases/".$dolibarr_main_db_type.".lib.php");
				require_once($dolibarr_main_document_root."/lib/admin.lib.php");
				// $conf is already instancied inside inc.php
				$conf->db->type = $dolibarr_main_db_type;
				$conf->db->host = $dolibarr_main_db_host;
				$conf->db->port = $dolibarr_main_db_port;
				$conf->db->name = $dolibarr_main_db_name;
				$conf->db->user = $dolibarr_main_db_user;
				$conf->db->pass = $dolibarr_main_db_pass;
				$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);
				if ($db->connected == 1 && $db->database_selected == 1)
				{
					$ok=1;
				}
			}
		}

		# If a database access is available, we set more variable
		if ($ok)
		{
			if (empty($dolibarr_main_db_encryption)) $dolibarr_main_db_encryption=0;
			$conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
			if (empty($dolibarr_main_db_cryptkey)) $dolibarr_main_db_cryptkey='';
			$conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

			$conf->setValues($db);
			// Current version is $conf->global->MAIN_VERSION_LAST_UPGRADE
			// Version to install is DOL_VERSION
			$dolibarrlastupgradeversionarray=split('[\.-]',$conf->global->MAIN_VERSION_LAST_UPGRADE);
			$dolibarrversiontoinstallarray=versiondolibarrarray();
		}

		# Show title
		if (! empty($conf->global->MAIN_VERSION_LAST_UPGRADE))
		{
			print $langs->trans("VersionLastUpgrade").': <b><font class="ok">'.$conf->global->MAIN_VERSION_LAST_UPGRADE.'</font></b><br>';
			print $langs->trans("VersionProgram").': <b><font class="ok">'.DOL_VERSION.'</font></b>';
			//print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired"));
			print '<br>';
			print '<br>';
		}
		else print "<br />\n";

		print $langs->trans("InstallEasy")." ";
		print $langs->trans("ChooseYourSetupMode");


		// Array of install choices
		print '<table width="100%" border="1" cellpadding="2">';

		# Sho first install line
		print '<tr><td nowrap="nowrap" align="center"><b>'.$langs->trans("FreshInstall").'</b></td>';
		print '<td>';
		print $langs->trans("FreshInstallDesc").'</td>';
		print '<td align="center">';
		if ($allowinstall)
		{
			print '<a href="licence.php?selectlang='.$setuplang.'">'.$langs->trans("Start").'</a>';
		}
		else
		{
			print $langs->trans("InstallNotAllowed");
		}
		print '</td>';
		print '</tr>'."\n";


		# Show upgrade lines
		$allowupgrade=true;
		if (defined("MAIN_NOT_INSTALLED")) $allowupgrade=false;
		$migrationscript=array( //array('from'=>'2.0.0', 'to'=>'2.1.0'),
								//array('from'=>'2.1.0', 'to'=>'2.2.0'),
								array('from'=>'2.2.0', 'to'=>'2.4.0'),
								array('from'=>'2.4.0', 'to'=>'2.5.0'),
								array('from'=>'2.5.0', 'to'=>'2.6.0'),
								array('from'=>'2.6.0', 'to'=>'2.7.0')
								);

		$foundrecommandedchoice=0;
		foreach ($migrationscript as $migarray)
		{
			$versionfrom=$migarray['from'];
			$versionto=$migarray['to'];
		    $newversionfrom=eregi_replace('\.[0-9]+$','.*',$versionfrom);
		    $newversionto=eregi_replace('\.[0-9]+$','.*',$versionto);
			print '<tr><td nowrap="nowrap" align="center"><b>'.$langs->trans("Upgrade").'<br>'.$newversionfrom.' -> '.$newversionto.'</b></td>';
			print '<td>';
			print $langs->trans("UpgradeDesc");
			if ($ok)
			{
				if (sizeof($dolibarrlastupgradeversionarray) >= 2)	// If a database access is available and a version x.y already available
				{
					$dolibarrversionfromarray=split('[\.-]',$versionfrom);
					$dolibarrversiontoarray=split('[\.-]',$versionto);
					// If last upgrade was an alpha or beta, we increase target version to 1 to select this one.
					if (isset($conf->global->MAIN_VERSION_LAST_UPGRADE) && eregi('beta|alpha',$conf->global->MAIN_VERSION_LAST_UPGRADE)) $dolibarrversiontoarray[2]=(isset($dolibarrversiontoarray[2]) ? ($dolibarrversiontoarray[2]+1) : 1);
					// Now we check if this is the first qualified choice
					if ($allowupgrade && empty($foundrecommandedchoice) && versioncompare($dolibarrversiontoarray,$dolibarrlastupgradeversionarray) > 0)
					{
						print '<br>';
						//print $langs->trans("InstallChoiceRecommanded",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_UPGRADE);
						print '<center><div class="ok">'.$langs->trans("InstallChoiceSuggested").'</div></center>';
						// <img src="../theme/eldy/img/tick.png" alt="Ok"> ';
						$foundrecommandedchoice=1;	// To show only once
					}
				}
				else {
					// We can not recommand a choice.
				}
			}
			print '</td>';
			print '<td align="center">';
			if ($allowupgrade)
			{
				print '<a href="upgrade.php?action=upgrade&amp;selectlang='.$setuplang.'&amp;versionfrom='.$versionfrom.'&amp;versionto='.$versionto.'">'.$langs->trans("Start").'</a>';
			}
			else
			{
				print $langs->trans("NotAvailable");
			}
			print '</td>';
			print '</tr>'."\n";
		}

		print '</table>';
		print "\n";
	}

}



pFooter(1);	// 1 car ne doit jamais afficher bouton Suivant

?>
