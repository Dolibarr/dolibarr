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
pHeader("Création des objets de la base","etape4");

$etape = 2;

$conf = "../conf/conf.php";
if (file_exists($conf))
{
  include($conf);
}

if($dolibarr_main_db_type == "mysql")
{
			require ($dolibarr_main_document_root . "/lib/mysql.lib.php");		
			$choix=1;
}
else
{
      require ($dolibarr_main_document_root . "/lib/pgsql.lib.php";
      require ($dolibarr_main_document_root . "/lib/grant.postgres.php");
			$choix=2;
}
			
require ($dolibarr_main_document_root . "/conf/conf.class.php");

if ($_POST["action"] == "set")
{
  print '<h2>Base de donnée</h2>';

  print '<table cellspacing="0" cellpadding="4" border="0" width="100%">';
  $error=0;

  print '<tr><td colspan="2">Test de connexion à la base de données</td></tr>';

  $conf = new Conf();// on pourrait s'en passer
  $conf->db->host = $dolibarr_main_db_host;
  $conf->db->name = $dolibarr_main_db_name;
  $conf->db->user = $dolibarr_main_db_user;
  $conf->db->pass = $dolibarr_main_db_pass;
  $db = new DoliDb();
  $ok = 0;
  if ($db->connected == 1)
    {
      print "<tr><td>Connexion au serveur : $dolibarr_main_db_host</td><td>OK</td></tr>";
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
     if ($choix == 1)
		 {
			      $dir = "../../mysql/tables/";						
		 }
			else
			{
						$dir = "../../pgsql/tables/";						
			}
	  
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
		  //print "<td>OK requete ==== $buffer</td></tr>";
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
		      print "<td>ERREUR ".$db->errno()." ".$db->error()."</td></tr>";
		      $error++;
		    }
		}
	    }
	  
	}
	
      //droit sur les tables
			if ($db->query($grant_query))
			{
		  	print "<tr><td>Grant User '$nom' </td><td>OK</td></tr>";
			}
      closedir($handle);
      
      if ($error == 0)
	{
	  print '<tr><td>Création des tables et clés primaires</td><td>OK</td></tr>';
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
			if ($choix==1)
			{
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
								if ($db->errno() == 1062)
						{
							// print "<tr><td>Insertion ligne : $buffer</td><td>
						}
								else
						{
								$ok = 0;
							print "Erreur SQL ".$db->errno()." sur requete '$buffer': ".$db->error()."<br>";
						}
							}
					}
						}
					fclose($fp);
				}
			}//choix==1
			else
			{
				$dir = "../../pgsql/data/";
				$file = "data.sql";
				$fp = fopen($dir.$file,"r");
				$buffer='';
      	if ($fp)
				{
					while (!feof ($fp))
	        {
	         $buffer .= fgets($fp, 4096);		
					}			 					 
							if ($db->query($buffer))
		    			{
		      			$ok = 1;
		    			}
		  				else
		    			{
		      				if ($db->errno() == 1062)
									{
			  		// print "<tr><td>Insertion ligne : $buffer</td><td>Déja existante</td></tr>";
									}	
		      				else
									{
		      					$ok = 0;
			  						print "Erreur SQL ".$db->errno()." sur requete '$buffer': ".$db->error()."<br>";
									}
		    			}						
					//}//while
				fclose($fp);
			}
		}//else
			      
      
      
      print "<tr><td>Chargement des données de base</td>";
      if ($ok)
	{	  
	  print "<td>OK</td></tr>";
	}
      else
	{
	  print "<td>ERREUR</td></tr>";
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
          			
	$chem1 = "/facture";
	$sql[0] = "UPDATE llx_const SET value = '".$dolibarr_main_data_root."/facture',
						 type = 'chaine',
						 visible = 0
 						 where name  ='FAC_OUTPUTDIR';" ;
				
	$sql[1] = "UPDATE llx_const SET value = '".$dolibarr_main_data_url."/document/facture',
						type = 'chaine',
						visible = 0
						where name  = 'FAC_OUTPUT_URL';" ;
				
	$sql[2] = "UPDATE llx_const SET value = '".$dolibarr_main_data_root."/propale',
						type = 'chaine',
						visible = 0
						where name  = 'PROPALE_OUTPUTDIR';" ;
				
	$sql[3] = "UPDATE llx_const SET value = '".$dolibarr_main_url_root."/document/propale',
						type = 'chaine',
						visible = 0
						where name  = 'PROPALE_OUTPUT_URL';" ;
				
	$sql[4] = "UPDATE llx_const SET value = '".$dolibarr_main_data_root."/ficheinter',
						 type = 'chaine',
						 visible = 0
						 where name  = 'FICHEINTER_OUTPUTDIR';" ;
				
	$sql[5] = "UPDATE llx_const SET value='".$dolibarr_main_url_root."/document/ficheinter',
						 type = 'chaine',
						 visible = 0
						 where name  = 'FICHEINTER_OUTPUT_URL';" ;
				
	$sql[6] = "UPDATE llx_const SET value='".$dolibarr_main_data_root."/societe',
	           type = 'chaine',
						 visible = 0
						 where name  = 'SOCIETE_OUTPUTDIR';" ;
				
	$sql[7] = "UPDATE llx_const SET value='".$dolibarr_main_url_root."/document/societe',
						 type = 'chaine',
						 visible = 0
						 where name  = 'SOCIETE_OUTPUT_URL';" ;
			
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
