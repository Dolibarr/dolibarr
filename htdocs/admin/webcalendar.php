<?PHP
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

$phpwebcalendar_url=$_POST["phpwebcalendar_url"];
$phpwebcalendar_host=$_POST["phpwebcalendar_host"];
$phpwebcalendar_dbname=$_POST["phpwebcalendar_dbname"];
$phpwebcalendar_user=$_POST["phpwebcalendar_user"];
$phpwebcalendar_pass=$_POST["phpwebcalendar_pass"];
$phpwebcalendar_pass2=$_POST["phpwebcalendar_pass2"];
$actionsave=$_POST["save"];
$actiontest=$_POST["test"];

// Positionne la variable pour le test d'affichage de l'icone
if ($actionsave)
{
    if (trim($phpwebcalendar_pass) == trim($phpwebcalendar_pass2))
    {
            $sql = "delete from ".MAIN_DB_PREFIX."const where name = 'PHPWEBCALENDAR_URL';";
						$db->query($sql);$sql='';
						$sql = "insert into ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
						('PHPWEBCALENDAR_URL','".$phpwebcalendar_url."',0);"; 
						//$sql  = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PHPWEBCALENDAR_URL',value='".$phpwebcalendar_url."', visible=0";
						
						$sql1 = "delete from ".MAIN_DB_PREFIX."const where name = 'PHPWEBCALENDAR_HOST';";
						$db->query($sql1);$sql1 = '';
						$sql1 = "insert into ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('PHPWEBCALENDAR_HOST','".$phpwebcalendar_host."',0);"; 
            //$sql1 = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PHPWEBCALENDAR_HOST',value='".$phpwebcalendar_host."', visible=0";
						
						$sql2 = "delete from ".MAIN_DB_PREFIX."const where name = 'PHPWEBCALENDAR_DBNAME';";
						$db->query($sql2);$sql2='';
						$sql2 = "insert into ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
						('PHPWEBCALENDAR_DBNAME','".$phpwebcalendar_dbname."',0);";
            //$sql2 = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PHPWEBCALENDAR_DBNAME', value='".$phpwebcalendar_dbname."', visible=0";
						
						$sql3 = "delete from ".MAIN_DB_PREFIX."const where name = 'PHPWEBCALENDAR_USER' ;";
						$db->query($sql3);$sql3='';
						$sql3 = "insert into ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
						('PHPWEBCALENDAR_USER','".$phpwebcalendar_user."',0);";
            //$sql3 = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PHPWEBCALENDAR_USER',	value='".$phpwebcalendar_user."', visible=0";
						
						$sql4 = "delete from ".MAIN_DB_PREFIX."const where name = 'PHPWEBCALENDAR_PASS';";
						$db->query($sql4);$sql4='';
						$sql4 = "insert into ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
						('PHPWEBCALENDAR_PASS','".$phpwebcalendar_pass."',0);";
            //$sql4 = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PHPWEBCALENDAR_PASS',	value='".$phpwebcalendar_pass."', visible=0";

            if ($db->query($sql) && $db->query($sql1) && $db->query($sql2) && $db->query($sql3) && $db->query($sql4))
            {
                // la constante qui a été lue en avant du nouveau set
                // on passe donc par une variable pour avoir un affichage cohérent
                define("PHPWEBCALENDAR_URL",   $phpwebcalendar_url);
                define("PHPWEBCALENDAR_HOST",  $phpwebcalendar_host);
                define("PHPWEBCALENDAR_DBNAME",$phpwebcalendar_dbname);
                define("PHPWEBCALENDAR_USER",  $phpwebcalendar_user);
                define("PHPWEBCALENDAR_PASS",  $phpwebcalendar_pass);
                $ok = "<p>Les identifiants webcalendar ont été sauvegardés.</p>";
            }
    }
    else
    {
        $ok="<p>Le mot de passe n'est pas identique, veuillez le saisir à nouveau</p><br>\n";
    }
}


/**
 * Affichage du formulaire de saisie
 */

print '<form name="phpwebcalendarconfig" action="webcalendar.php" method="post">';
print "<table class=\"noborder\" cellpadding=\"3\" cellspacing=\"1\">
<tr class=\"liste_titre\">
<td>".$langs->trans("Parameter")."</td>
<td>".$langs->trans("Value")."</td>
</tr>
<tr class=\"impair\">
<td>Adresse URL d'accès au calendrier</td>
<td><input type=\"text\" name=\"phpwebcalendar_url\" value=\"". PHPWEBCALENDAR_URL . "\" size=\"45\"></td>
</tr>
<tr class=\"pair\">
<td>Serveur où la base du calendrier est hébergée</td>
<td><input type=\"text\" name=\"phpwebcalendar_host\" value=\"". PHPWEBCALENDAR_HOST . "\" size=\"45\"></td>
</tr>
<tr class=\"impair\">
<td>Nom de la base de données</td>
<td><input type=\"text\" name=\"phpwebcalendar_dbname\" value=\"". PHPWEBCALENDAR_DBNAME . "\" size=\"45\"></td>
</tr>
<tr class=\"pair\">
<td>Identifiant d'accès à la base</td>
<td><input type=\"text\" name=\"phpwebcalendar_user\" value=\"". PHPWEBCALENDAR_USER . "\" size=\"45\"></td>
</tr>
<tr class=\"impair\">
<td>".$langs->trans("Password")."</td>
<td><input type=\"password\" name=\"phpwebcalendar_pass\" value=\"" . PHPWEBCALENDAR_PASS . "\" size=\"45\"></td>
</tr>
<tr class=\"pair\">
<td>".$langs->trans("PasswordRetype")."</td>
<td><input type=\"password\" name=\"phpwebcalendar_pass2\" value=\"" . PHPWEBCALENDAR_PASS ."\" size=\"45\"></td>
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


/**
* Test de la connection a la database webcalendar
*
*/
if ($ok) print "$ok<br>";

if ($actiontest)
{
    $conf = new Conf();

    $conf->db->host = $phpwebcalendar_host;
    $conf->db->name = $phpwebcalendar_dbname;
    $conf->db->user = $phpwebcalendar_user;
    $conf->db->pass = $phpwebcalendar_pass;

    $webcal = new DoliDb();

    if ($webcal->connected == 1)
    {
        print "<p class=\"ok\">La connection à la base de données '$phpwebcalendar_dbname' à réussi</p><br>";
        $webcal->close();
    }
    else
    print "<p class=\"error\">La connection à la base de données '$phpwebcalendar_dbname' à échoué.</p><br>";
}


llxFooter();
?>
