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
pHeader("Fichier de configuration","etape4");

$etape = 2;

$conf = "../conf/conf.php";
if (file_exists($conf))
{
  include($conf);
}
require ($dolibarr_main_document_root . "/lib/mysql.lib.php");
require ($dolibarr_main_document_root . "/conf/conf.class.php");

if ($HTTP_POST_VARS["action"] == "set")
{
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
      $ok = 1 ;
    }
  else
    {
      print "<tr><td>Erreur lors de la création de : $dolibarr_main_db_name</td><td>ERREUR</td></tr>";
    }
  /***************************************************************************************
   *
   *
   */
  if ($ok)
    {
      if($db->database_selected == 1)
	{
	  dolibarr_syslog("Connexion réussie à la base : $dolibarr_main_db_name");
	}
      else
	{
	  $ok = 0 ;
	}
    }
  /***************************************************************************************
   *
   *
   */
  if ($ok)
    {
      $ok = 0;
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
      $table_exists = 0;
      while (($file = readdir($handle))!==false)
	{
	  if (substr($file, strlen($file) - 4) == '.sql' && substr($file,0,4) == 'llx_')
	    {
	      $name = substr($file, 0, strlen($file) - 4);
	      //print "<tr><td>Création de la table $name</td>";
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
		  if ($db->errno() == 1050)
		    {
		      //print "<td>Déjà existante</td></tr>";
		      $table_exists = 1;
		    }
		  else
		    {
		      print "<tr><td>Création de la table $name</td>";
		      print "<td>ERREUR ".$db->errno()."</td></tr>";
		      $error++;
		    }
		}
	    }
	  
	}
      closedir($handle);
      
      if ($error == 0)
	{
	  print '<tr><td colspan="2">Création des tables réussie</td></tr>';
	  $ok = 1;
	}
    }
  /***************************************************************************************
   *
   *
   *
   *
   ***************************************************************************************/
  if ($ok == 1)
    {
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
		      if ($db->errno() == 1062)
			{
			  // print "<tr><td>Insertion ligne : $buffer</td><td>Déja existante</td></tr>";
			}
		      else
			{
			  print "Erreur SQL ".$db->errno()." sur requete '$buffer': ".$db->error()."<br>";
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


  /***************************************************************************************
   *
   *
   *
   *
   ***************************************************************************************/
  if ($ok == 1)
    {
      /*
       *
       *
       */
      
      $sql[0] = "REPLACE INTO llx_const SET name = 'FAC_OUTPUTDIR', value='".$dolibarr_main_document_root."/document/facture', visible=0, type='chaine'";
      
      $sql[1] = "REPLACE INTO llx_const SET name = 'FAC_OUTPUT_URL', value='".$dolibarr_main_url_root."/document/facture', visible=0, type='chaine'";
      
      $sql[2] = "REPLACE INTO llx_const SET name = 'PROPALE_OUTPUTDIR', value='".$dolibarr_main_document_root."/document/propale', visible=0, type='chaine'";
      
      $sql[3] = "REPLACE INTO llx_const SET name = 'PROPALE_OUTPUT_URL', value='".$dolibarr_main_url_root."/document/propale', visible=0, type='chaine'";
      
      $sql[4] = "REPLACE INTO llx_const SET name = 'FICHEINTER_OUTPUTDIR', value='".$dolibarr_main_document_root."/document/ficheinter', visible=0, type='chaine'";
      
      $sql[5] = "REPLACE INTO llx_const SET name = 'FICHEINTER_OUTPUT_URL', value='".$dolibarr_main_url_root."/document/ficheinter', visible=0, type='chaine'";
      
      $sql[6] = "REPLACE INTO llx_const SET name = 'SOCIETE_OUTPUTDIR', value='".$dolibarr_main_document_root."/document/societe', visible=0, type='chaine'";
      
      $sql[7] = "REPLACE INTO llx_const SET name = 'SOCIETE_OUTPUT_URL', value='".$dolibarr_main_url_root."/document/societe', visible=0, type='chaine'";
      $result = 0;
      
      for ($i=0; $i < sizeof($sql);$i++)
	{
	  if ($db->query($sql[$i]))
	    {
	      $result++;
	    }
	}
      
      if ($result == sizeof($sql))
	{
	  if ($error == 0)
	    {	  
	      $db->query("DELETE FROM llx_const WHERE name='MAIN_NOT_INSTALLED'");		  
	    }
	}
    }
  /***************************************************************************************
   *
   *
   *
   *
   ***************************************************************************************/

  print '</table>';

  $db->close();
}
pFooter(!$ok);
?>
