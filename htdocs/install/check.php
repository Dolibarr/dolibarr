<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org> 
 * Copyright (C) 2004-2006 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/install/check.php
        \ingroup    install
        \brief      Test si le fichier conf est modifiable et si il n'existe pas, test la possibilité de le créer
        \version    $Revision$
*/

$err = 0;
$allowinstall = 0;
$allowupgrade = 0;
$checksok = 1;

include_once("./inc.php");

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:$langs->getDefaultLang());
$langs->setDefaultLang($setuplang);

$langs->load("install");


dolibarr_install_syslog("Dolibarr install/upgrade process started");


pHeader($langs->trans("DolibarrWelcome"),"");   // Etape suivante = license

print '<center><img src="../theme/dolibarr_logo_2.png" alt="Dolibarr logo"></center><br>';
print "<br>\n";


print $langs->trans("InstallEasy")."<br><br>\n";


print '<b>'.$langs->trans("MiscellanousChecks")."</b>:<br>\n";

// Check PHP version
if (versioncompare(versionphp(),array(4,1)) < 0)
{
    print '<img src="../theme/eldy/img/error.png" alt="Error"> '.$langs->trans("ErrorPHPVersionTooLow",'4.1')."<br>\n";
    $checksok=0;
}
else
{
    print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPVersion")." ".versiontostring(versionphp())."<br>\n";
}

// Si session non actives
if (! function_exists("session_id"))
{
    print '<img src="../theme/eldy/img/error.png" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportSessions")."<br>\n";
    $checksok=0;
}
else
{
    print '<img src="../theme/eldy/img/tick.png" alt="Ok"> '.$langs->trans("PHPSupportSessions")."<br>\n";
}

// Si fichier présent et lisible et renseigné
clearstatcache();
if (is_readable($conffile) && filesize($conffile) > 8)
{
    $confexists=1;
    include_once($conffile);
    
    // Deja installé, on peut upgrader
    // \todo Test if database ok
    $allowupgrade=1;
}
else
{
    // Si non on le crée        
    $confexists=0;
    $fp = @fopen($conffile, "w");
    if($fp)
    {
        @fwrite($fp, '<?php');
        @fputs($fp,"\n");
        @fputs($fp,"?>");
        fclose($fp);
    }
    
    // First install, on ne peut pas upgrader
    $allowupgrade=0;
}

// Si fichier absent et n'a pu etre créé
if (! file_exists($conffile))
{
    print '<img src="../theme/eldy/img/error.png" alt="Error"> '.$langs->trans("ConfFileDoesNotExists",'conf.php');
    print "<br />";
    print $langs->trans("YouMustCreateWithPermission",'htdocs/conf/conf.php');
    print "<br /><br />";
    
    print $langs->trans("CorrectProblemAndReloadPage");
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
	
	    print '<tr><td nowrap="nowrap"><b>'.$langs->trans("Upgrade").'</b></td><td>';
	    print $langs->trans("UpgradeDesc").'</td>';
	    print '<td align="center">';
	    if ($allowupgrade)
	    {
	        print '<a href="upgrade.php?action=upgrade&amp;selectlang='.$setuplang.'">'.$langs->trans("Start").'</a>';
	    }
	    else
	    {
	        print $langs->trans("NotAvailable");   
	    }
	    print '</td>';
	    print '</tr>'."\n";
	    
	    print '</table>';
	    print "\n";
	}

}


pFooter(1);	// 1 car ne doit jamais afficher bouton Suivant

?>
