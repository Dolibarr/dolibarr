<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
include("./inc.php");
$success=0;

$conf = "../conf/conf.php";
if (file_exists($conf))
{
  include($conf);
}
require ($dolibarr_main_document_root . "/lib/mysql.lib.php");
require ($dolibarr_main_document_root . "/conf/conf.class.php");

if ($HTTP_POST_VARS["action"] == "set")
{
  if ($HTTP_POST_VARS["pass"] <> $HTTP_POST_VARS["pass_verif"])
    {
      Header("Location: etape4.php?error=1");
    }

  if (strlen(trim($HTTP_POST_VARS["pass"])) == 0)
    {
      Header("Location: etape4.php?error=2");
    }

  if (strlen(trim($HTTP_POST_VARS["login"])) == 0)
    {
      Header("Location: etape4.php?error=3");
    }


  pHeader("Fin de l'installation","etape5");

  print '<table cellspacing="0" cellpadding="4" border="1" width="100%">';
  $error=0;

  $conf = new Conf();
  $conf->db->host = $dolibarr_main_db_host;
  $conf->db->name = $dolibarr_main_db_name;
  $conf->db->user = $dolibarr_main_db_user;
  $conf->db->pass = $dolibarr_main_db_pass;
  $db = new DoliDb();
  $ok = 0;
  if ($db->connected == 1)
    {
      $sql = "INSERT INTO llx_user(datec,login,pass,admin,name,code) VALUES (now()";
      $sql .= ",'".$HTTP_POST_VARS["login"]."'";
      $sql .= ",'".$HTTP_POST_VARS["pass"]."'"; 
      $sql .= ",1,'Administrateur','ADM')";
    }
  
  if ($db->query($sql) || $db->errno() == 1062)
    {
      $db->query("DELETE FROM llx_const WHERE name='MAIN_NOT_INSTALLED'");
      print "Création du compte administrateur réussie<br>";
      $success = 1;
    }
  else
    {
      print "Echec de la création du compte administrateur<br>";
    }
  print '</table>';

  $db->close();
}

?>
<br>

<?PHP
print "Votre système est maintenant configuré, il ne vous reste plus qu'a sélectionner les modules que vous souhaitez utiliser. Pour cela cliquer sur l'url ci-dessous : <br>";
print '<a href="'.$dolibarr_main_url_root .'/admin/modules.php">Configurer les modules</a>';

pFooter(1);
?>
