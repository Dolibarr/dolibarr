<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004 Sebastien DiCintio   <sdicintio@ressource-toi.org>
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


if($dolibarr_main_db_type == "mysql")
	require ($dolibarr_main_document_root . "/lib/mysql.lib.php");		
else
	require ($dolibarr_main_document_root . "/lib/pgsql.lib.php");
			
require ($dolibarr_main_document_root . "/conf/conf.class.php");

if ($_POST["action"] == "set")
{
  if ($_POST["pass"] <> $_POST["pass_verif"])
    {
      Header("Location: etape4.php?error=1");
    }

  if (strlen(trim($_POST["pass"])) == 0)
    {
      Header("Location: etape4.php?error=2");
    }

  if (strlen(trim($_POST["login"])) == 0)
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
      $sql .= ",'".$_POST["login"]."'";
      $sql .= ",'".$_POST["pass"]."'"; 
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

<?php
print "Votre système est maintenant installé.<br>";
print "Il vous reste à le configurer selon vos besoins (Choix de l'apparence, des fonctionnalités, etc...). Pour cela, cliquez sur le lien ci-dessous:<br>";

print '<br><a href="'.$dolibarr_main_url_root .'/admin/index.php">Accès à l\'espace configuration</a>';

pFooter(1);
?>
