<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier			 <benoit.mortier@opensides.be>
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

/*!
	    \file       htdocs/admin/webcalendar.php
        \ingroup    webcal
        \brief      Page de configuration du module webcalendar
		\version    $Revision$
*/

require("./pre.inc.php");

if (!$user->admin)
    accessforbidden();


llxHeader();

print_titre("Configuration du lien vers le calendrier partagé");
print '<br>';

$def = array();

$phpwebcalendar_url=trim($_POST["phpwebcalendar_url"]);
$phpwebcalendar_host=trim($_POST["phpwebcalendar_host"]);
$phpwebcalendar_dbname=trim($_POST["phpwebcalendar_dbname"]);
$phpwebcalendar_user=trim($_POST["phpwebcalendar_user"]);
$phpwebcalendar_pass=trim($_POST["phpwebcalendar_pass"]);
$phpwebcalendar_pass2=trim($_POST["phpwebcalendar_pass2"]);
$actionsave=$_POST["save"];
$actiontest=$_POST["test"];

// Positionne la variable pour le test d'affichage de l'icone
if ($actionsave)
{
    if ($phpwebcalendar_pass == $phpwebcalendar_pass2)
    {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'PHPWEBCALENDAR_URL';";
			$db->query($sql);
			
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES	('PHPWEBCALENDAR_URL','".$phpwebcalendar_url."',0);"; 
			$result=$db->query($sql);
			
			$sql1 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'PHPWEBCALENDAR_HOST';";
			$db->query($sql1);
			
			$sql1 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('PHPWEBCALENDAR_HOST','".$phpwebcalendar_host."',0);"; 
			$result1=$db->query($sql1);
									
			$sql2 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'PHPWEBCALENDAR_DBNAME';";
			$db->query($sql2);
			
			$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('PHPWEBCALENDAR_DBNAME','".$phpwebcalendar_dbname."',0);";
			$result2=$db->query($sql2);
			
			$sql3 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'PHPWEBCALENDAR_USER' ;";
			$db->query($sql3);
			
			$sql3 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('PHPWEBCALENDAR_USER','".$phpwebcalendar_user."',0);";
			$result3=$db->query($sql3);
			
			$sql4 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'PHPWEBCALENDAR_PASS';";
			$db->query($sql4);
			
			$sql4 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('PHPWEBCALENDAR_PASS','".$phpwebcalendar_pass."',0);";
			$result4=$db->query($sql4);

            if ($result && $result1 && $result2 && $result3 && $result4)
            {
                $ok = "<br><font class=\"ok\">Les identifiants Webcalendar ont été sauvegardés avec succès.</font>";
            }
    }
    else
    {
        $ok="<br><font class=\"error\">Le mot de passe n'est pas identique, veuillez le saisir à nouveau</font><br>\n";
    }
}

if (! $phpwebcalendar_url)      { $phpwebcalendar_url=PHPWEBCALENDAR_URL; }
if (! $phpwebcalendar_host)     { $phpwebcalendar_host=PHPWEBCALENDAR_HOST; }
if (! $phpwebcalendar_dbname)   { $phpwebcalendar_dbname=PHPWEBCALENDAR_DBNAME; }
if (! $phpwebcalendar_user)     { $phpwebcalendar_user=PHPWEBCALENDAR_USER; }
if (! $phpwebcalendar_pass)     { $phpwebcalendar_pass=PHPWEBCALENDAR_PASS; }
if (! $phpwebcalendar_pass2)     { $phpwebcalendar_pass2=PHPWEBCALENDAR_PASS; }


/**
 * Affichage du formulaire de saisie
 */
print '<form name="phpwebcalendarconfig" action="webcalendar.php" method="post">';
print "<table class=\"noborder\">
<tr class=\"liste_titre\">
<td>".$langs->trans("Parameter")."</td>
<td>".$langs->trans("Value")."</td>
</tr>
<tr class=\"impair\">
<td>Adresse URL d'accès au calendrier</td>
<td><input type=\"text\" name=\"phpwebcalendar_url\" value=\"". $phpwebcalendar_url . "\" size=\"45\"></td>
</tr>
<tr class=\"pair\">
<td>Serveur où la base du calendrier est hébergée</td>
<td><input type=\"text\" name=\"phpwebcalendar_host\" value=\"". $phpwebcalendar_host . "\" size=\"45\"></td>
</tr>
<tr class=\"impair\">
<td>Nom de la base de données</td>
<td><input type=\"text\" name=\"phpwebcalendar_dbname\" value=\"". $phpwebcalendar_dbname . "\" size=\"45\"></td>
</tr>
<tr class=\"pair\">
<td>Identifiant d'accès à la base</td>
<td><input type=\"text\" name=\"phpwebcalendar_user\" value=\"". $phpwebcalendar_user . "\" size=\"45\"></td>
</tr>
<tr class=\"impair\">
<td>".$langs->trans("Password")."</td>
<td><input type=\"password\" name=\"phpwebcalendar_pass\" value=\"" . $phpwebcalendar_pass . "\" size=\"45\"></td>
</tr>
<tr class=\"pair\">
<td>".$langs->trans("PasswordRetype")."</td>
<td><input type=\"password\" name=\"phpwebcalendar_pass2\" value=\"" . $phpwebcalendar_pass2 ."\" size=\"45\"></td>
</tr>
<tr class=\"impair\">
<td colspan=\"2\" align=\"center\">
<input type=\"submit\" name=\"test\" value=\"".$langs->trans("TestConnection")."\">
<input type=\"submit\" name=\"save\" value=\"".$langs->trans("Save")."\">
</td>
</tr>\n";

clearstatcache();

print "
</table>
</form>\n";


if ($ok) print "$ok<br>";


// Test de la connection a la database webcalendar
if ($actiontest)
{
    $webcal = new DoliDb('',$phpwebcalendar_host,$phpwebcalendar_user,$phpwebcalendar_pass,$phpwebcalendar_dbname);

    if ($webcal->connected == 1 && $webcal->database_selected == 1)
    {
        print "<br><font class=\"ok\">La connection au serveur '$phpwebcalendar_host' sur la base '$phpwebcalendar_dbname' a réussi.</font><br>";
        $webcal->close();
    }
    elseif ($webcal->connected == 1)
    {
        print "<br><font class=\"error\">La connection au serveur '$phpwebcalendar_host' a réussi mais la base '$phpwebcalendar_dbname' n'a pu être accédée.</font><br>";
        $webcal->close();
    }
    else
    {
        print "<br><font class=\"error\">La connection au serveur '$phpwebcalendar_host' à échoué.</font><br>";
    }
}


llxFooter();
?>
