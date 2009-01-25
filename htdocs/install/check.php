<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Océbo <marc@ocebo.com>
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

pHeader($langs->trans("DolibarrWelcome"),"");   // Etape suivante = license

print '<center><img src="../theme/dolibarr_logo_2.png" alt="Dolibarr logo"></center><br>';
print "<br>\n";


print $langs->trans("InstallEasy")."<br><br>\n";

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
$memrequiredorig='16M';
$memrequired=16*1024*1024;
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
	print "<br />\n";

	// Si prerequis ok, on affiche le bouton pour passer à l'étape suivante
	if ($checksok)
	{
		print $langs->trans("ChooseYourSetupMode");

		print '<table width="100%" cellspacing="1" cellpadding="4" border="1">';

		print '<tr><td nowrap="nowrap"><b>'.$langs->trans("FreshInstall").'</b></td><td>';
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

		$migrationscript=array(array('from'=>'2.0.0', 'to'=>'2.1.0'),
								array('from'=>'2.1.0', 'to'=>'2.2.0'),
								array('from'=>'2.2.0', 'to'=>'2.4.0'),
								array('from'=>'2.4.0', 'to'=>'2.5.0'),
								array('from'=>'2.5.0', 'to'=>'2.6.0')
								);
		# Upgrade lines
		foreach ($migrationscript as $migarray)
		{
			$versionfrom=$migarray['from'];
			$versionto=$migarray['to'];
		    $newversionfrom=eregi_replace('\.[0-9]+$','.*',$versionfrom);
		    $newversionto=eregi_replace('\.[0-9]+$','.*',$versionto);
			print '<tr><td nowrap="nowrap"><b>'.$langs->trans("Upgrade").' '.$newversionfrom.' -> '.$newversionto.'</b></td><td>';
			print $langs->trans("UpgradeDesc").'</td>';
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
