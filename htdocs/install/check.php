<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
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

include_once("./inc.php");

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:$langcode);
$langs->defaultlang=$setuplang;
$langs->load("install");


pHeader($langs->trans("DolibarrWelcome"),"");   // Etape suivante = license

print $langs->trans("InstallEasy")."<br>";

// Si fichier présent et lisible
if (is_readable($conffile))
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
    $fp = @fopen("$conffile", "w");
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
if (!file_exists($conffile))
{
  print "<br /><br />";
  print "Le fichier de configuration <b>conf.php</b> n'existe pas !";
  print "<br />";
  print "Vous devez créer un fichier <b>htdocs/conf/conf.php</b> et donner les droits d'écriture dans celui-ci au serveur web durant le processus d'installation.";
  print "<br /><br />";

  print 'Corrigez le problème et <a href="index.php">rechargez la page</a>.';

  $err++;
}
else
{
    print "<br />\n";
    // Si ficiher présent mais ne peut etre modifié
    if (!is_writable($conffile))
    {
        if ($confexists) {
            print $langs->trans("ConfFileExists");
        }
        else {
            print $langs->trans("ConfFileCouldBeCreated");
        }
        print "<br />";
        print $langs->trans("ConfFileIsNotWritable");
        print "<br />";
    
        $allowinstall=0;
    }
    // Si fichier présent et peut etre modifié
    else
    {
        if ($confexists) {
            print $langs->trans("ConfFileExists");
        }
        else {
            print $langs->trans("ConfFileCouldBeCreated");
        }
        print "<br />";
        print $langs->trans("ConfFileIsWritable");
        print "<br />";
    
        $allowinstall=1;
    }
    print "<br />\n";
    print "<br />\n";

    // Si pas d'erreur, on affiche le bouton pour passer à l'étape suivante

    
    print $langs->trans("ChooseYourSetupMode");

    print '<table width="100%" cellspacing="0" cellpadding="4" border="1">';
    
    print '<tr><td nowrap><b>'.$langs->trans("FreshInstall").'</b></td><td>';
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
    print '</tr>';

    print '<tr><td nowrap><b>'.$langs->trans("Upgrade").'</b></td><td>';
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
    print '</tr>';
    
    print '</table>';
    print "\n";

}

print '</div>';
print '</div>';
print '</form>';


print '</body>';
print '</html>';

?>
