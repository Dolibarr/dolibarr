<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
include("./inc.php");
pHeader("Création du compte administrateur","etape5");
$conf = "../conf/conf.php";
if (file_exists($conf))
{
  include($conf);
}
require ($dolibarr_main_document_root . "/lib/mysql.lib.php");
require ($dolibarr_main_document_root . "/conf/conf.class.php");

print '<table cellspacing="0" cellpadding="4" border="1" width="100%">';

$error=0;

$conf = new Conf();
$conf->db->host = $dolibarr_main_db_host;
$conf->db->name = $dolibarr_main_db_name;
$conf->db->user = $dolibarr_main_db_user;
$conf->db->pass = $dolibarr_main_db_pass;
$db = new DoliDb();
$ok = 0;
if ($db->ok == 1)
{
  
  print '<tr><td>Compte administrateur :</td><td>';
  print '<input name="login"></td></tr>';
  print '<tr><td>Mot de passe :</td><td>';
  print '<input type="password" name="pass"></td></tr>';
  print '<tr><td>Vérification du mot de passe :</td><td>';
  print '<input type="password" name="pass_verif"></td></tr>';
  print '</table>';

  if ($_GET["error"] == 1)
    {
      print '<div class="error">Les mots de passe ne concordent pas, veuillez recommencer !</div>';
    }

  if ($_GET["error"] == 2)
    {
      print '<div class="error">Veuillez saisir un mot de passe, les mots de passe vides ne sont pas acceptés !</div>';
    }

  if ($_GET["error"] == 3)
    {
      print '<div class="error">Veuillez saisir un login !</div>';
    }
  $db->close();
}

pFooter($err);
?>
