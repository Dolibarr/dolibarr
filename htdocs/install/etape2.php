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
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso8859-1">
<link rel="stylesheet" type="text/css" href="./default.css">
<title>Dolibarr Install</title>
</head>
<body>
<div class="main">
 <div class="main-inside">
<?PHP
include("./inc.php");
$etape = 2;
print "<h2>Installation de Dolibarr - Etape $etape/$etapes</h2>";

$conf = "../conf/conf.php";
if (file_exists($conf))
{
  include($conf);
}
require ($dolibarr_main_document_root . "/lib/mysql.lib.php");
require ($dolibarr_main_document_root . "/conf/conf.class.php");

if ($HTTP_POST_VARS["action"] == "set")
{
  umask(0);
  print '<h2>Base de donnée</h2>';

  print '<table cellspacing="0" cellpadding="4" border="0" width="100%">';
  $error=0;

  print '<tr><td colspan="2">Test de connexion à la base de données</td></tr>';

  $conf = new Conf();
  $conf->db->host = $dolibarr_main_db_host;
  $conf->db->name = $dolibarr_main_db_name;
  $conf->db->user = $dolibarr_main_db_user;
  $conf->db->pass = $dolibarr_main_db_pass;
  $db = new DoliDb();
  $ok = 0;
  if ($db->connected == 1)
    {
      print "<tr><td>Connexion réussie au serveur : $dolibarr_main_db_host</td><td>OK</td></tr>";

      if($db->database_selected == 1)
	{
	  print "<tr><td>Connexion réussie à la base : $dolibarr_main_db_name</td><td>OK</td></tr>";

	  $ok = 1 ;
	  
	  //$result = $db->list_tables($dolibarr_main_db_name);
	  //if ($result)
	  //{
	  //    while ($row = $db->fetch_row())
	  //	{
	  //	  print "Table : $row[0]<br>\n";
 	  //	}
	  //}

	  // Création des tables
	  $dir = "../../mysql/tables/";
	  
	  $handle=opendir($dir);
	  
	  while (($file = readdir($handle))!==false)
	    {
	      if (substr($file, strlen($file) - 4) == '.sql' && 
		  substr($file,0,4) == 'llx_')
		{
		  $name = substr($file, 0, strlen($file) - 4);
		  print "<tr><td>Création de la table $name</td>";
		  $buffer = '';
		  $fp = fopen($dir.$file,"r");
		  if ($fp)
		    {
		      while (!feof ($fp))
			{
			  $buffer .= fgets($fp, 4096);
			}
		      fclose($fp);
		    }
		  
		  if ($db->query($buffer))
		    {
		      print "<td>OK</td></tr>";
		    }
		  else
		    {
			  if ($db->errno() == 1050) {
		      print "<td>Déjà existante</td></tr>";
			  }
			  else {
		      print "<td>ERREUR ".$db->errno()."</td></tr>";
		      $error++;
		      }
		    }
		}
	      
	    }
	  closedir($handle);
	  
	  //
	  // Données
	  //
	  $dir = "../../mysql/data/";
	  $file = "data.sql";

	  $fp = fopen($dir.$file,"r");
	  if ($fp)
	    {
	      while (!feof ($fp))
		{
		  $buffer = fgets($fp, 4096);

		  if (strlen(trim(ereg_replace("--","",$buffer))))
		    {
		      if ($db->query($buffer))
			{
			  $ok = 1;
			}
		      else
			{
			  $ok = 0;
			  if ($db->errno() == 1062) {
			  	// print "<tr><td>Insertion ligne : $buffer</td><td>Déja existante</td></tr>";
			  }
			  else {
			  	print "Erreur SQL ".$db->errno()." sur requete '$buffer'<br>";
			  }
			}
		    }
		}
	      fclose($fp);
	    }
	  
	  print "<tr><td>Chargement des données de base</td>";
	  if ($ok)
	    {	  
	      print "<td>OK</td></tr>";
	    }
	  else
	    {
	      $ok = 1 ;
	    }

	}
      else
	{
	  print "<tr><td>Erreur lors de la création de : $dolibarr_main_db_name</td><td>ERREUR</td></tr>";
	}

    }
  print '</table>';

  $db->close();
}
?>
</div>
</div>
<div class="barrebottom">
<form action="etape3.php" method="POST">
<input type="hidden" name="action" value="set">
<input type="submit" value="Etape suivante ->">
</form>
</div>
</body>
</html>
