<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Éric Seigne <erics@rycks.com>
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
require("./pre.inc.php");

llxHeader();

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}

$def = array();

// positionne la variable pour le test d'affichage de l'icone
if ($action == 'save')
{
  if(trim($phpwebcalendar_pass) == trim($phpwebcalendar_pass2)) {
    $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PHPWEBCALENDAR_URL', value='".$phpwebcalendar_url."', visible=0";

    $sql1 = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PHPWEBCALENDAR_HOST', value='".$phpwebcalendar_host."', visible=0";
    $sql2 = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PHPWEBCALENDAR_DBNAME', value='".$phpwebcalendar_dbname."', visible=0";
    $sql3 = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PHPWEBCALENDAR_USER', value='".$phpwebcalendar_user."', visible=0";
    $sql4 = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PHPWEBCALENDAR_PASS', value='".$phpwebcalendar_pass."', visible=0";

    if ($db->query($sql) && $db->query($sql1) && $db->query($sql2) && $db->query($sql3) && $db->query($sql4))
      {
	// la constante qui a été lue en avant du nouveau set
	// on passe donc par une variable pour avoir un affichage cohérent
	print "<p>ok bien enregistré</p>\n";
        print "<p>dans quelques jours je rajoute un test de connexion à la base de données de webcal pour être certain que tout est OK</p>\n";
	define("PHPWEBCALENDAR_URL",  $phpwebcalendar_url);
      }
    else
      print "erreur d'enregistement !";
  }
  else
    {
      print "<p>erreur, votre mot de passe n'est pas vérifié, merci de retourner à la page de saisie pour corriger votre erreur</p>\n";
    }
}
else
{
  /*
   * Affichage du formulaire de saisie
   */
  
  print_titre("Configuration du lien vers le calendrier");
  
  print "\n<form name=\"phpwebcalendarconfig\" action=\"" . $_SERVER['SCRIPT_NAME'] . "\" method=\"post\">
<table border=\"1\" cellpadding=\"3\" cellspacing=\"0\">
<tr>
  <td>Adresse URL d'accès au calendrier</td>
  <td><input type=\"text\" name=\"phpwebcalendar_url\" value=\"". PHPWEBCALENDAR_URL . "\" size=\"45\"></td>
</tr>
<tr>
  <td>Serveur où la base du calendrier est hébergée</td>
  <td><input type=\"text\" name=\"phpwebcalendar_host\" value=\"". PHPWEBCALENDAR_HOST . "\" size=\"45\"></td>
</tr>
<tr>
  <td>Nom de la base de données</td>
  <td><input type=\"text\" name=\"phpwebcalendar_dbname\" value=\"". PHPWEBCALENDAR_DBNAME . "\" size=\"45\"></td>
</tr>
<tr>
  <td>Identifiant d'accès à la base</td>
  <td><input type=\"text\" name=\"phpwebcalendar_user\" value=\"". PHPWEBCALENDAR_USER . "\" size=\"45\"></td>
</tr>
<tr>
  <td>Mot de passe d'accès à la base</td>
  <td><input type=\"password\" name=\"phpwebcalendar_pass\" value=\"" . PHPWEBCALENDAR_PASS . "\" size=\"45\"></td>
</tr>
<tr>
  <td>Mot de passe (vérification)</td>
  <td><input type=\"password\" name=\"phpwebcalendar_pass2\" value=\"" . PHPWEBCALENDAR_PASS ."\" size=\"45\"></td>
</tr>
<tr>
<td colspan=\"2\"><input type=\"submit\" name=\"envoyer\" value=\"Enregistrer\"></td>
</tr>\n";

  clearstatcache();
  
  print "</table>
<input type=\"hidden\" name=\"action\" value=\"save\"></td>
</form>\n";
}

/*
 *
 *
 */


$db->close();

llxFooter();
?>
