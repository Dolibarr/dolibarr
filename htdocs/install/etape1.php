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
$etape = 1;
print "<h2>Installation de Dolibarr - Etape $etape/$etapes</h2>";

$conf = "../conf/conf.php";

if ($HTTP_POST_VARS["action"] == "set")
{
  umask(0);
  print '<h2>Enregistrement des valeurs</h2>';

  print '<table cellspacing="0" width="100%" cellpadding="4" border="0">';
  $error=0;
  $fp = fopen("$conf", "w");
  if($fp)
    {

      if (substr($HTTP_POST_VARS["main_dir"], strlen($HTTP_POST_VARS["main_dir"]) -1) == "/")
	{
	  $HTTP_POST_VARS["main_dir"] = substr($HTTP_POST_VARS["main_dir"], 0, strlen($HTTP_POST_VARS["main_dir"])-1);
	}

      if (substr($HTTP_POST_VARS["main_url"], strlen($HTTP_POST_VARS["main_url"]) -1) == "/")
	{
	  $HTTP_POST_VARS["main_url"] = substr($HTTP_POST_VARS["main_url"], 0, strlen($HTTP_POST_VARS["main_url"])-1);
	}

      clearstatcache();

      fwrite($fp, '<?PHP');
      fputs($fp,"\n");

      fputs($fp, '$dolibarr_main_document_root="'.$HTTP_POST_VARS["main_dir"].'";');
      fputs($fp,"\n");

      fputs($fp, '$dolibarr_main_url_root="'.$HTTP_POST_VARS["main_url"].'";');
      fputs($fp,"\n");

      fputs($fp, '$dolibarr_main_db_host="'.$HTTP_POST_VARS["db_host"].'";');
      fputs($fp,"\n");

      fputs($fp, '$dolibarr_main_db_name="'.$HTTP_POST_VARS["db_name"].'";');
      fputs($fp,"\n");

      fputs($fp, '$dolibarr_main_db_user="'.$HTTP_POST_VARS["db_user"].'";');
      fputs($fp,"\n");

      fputs($fp, '$dolibarr_main_db_pass="'.$HTTP_POST_VARS["db_pass"].'";');
      fputs($fp,"\n");

      fputs($fp, '?>');
      fclose($fp);

      if (file_exists("$conf"))
	{
	  include ("$conf");


	  print "<tr><td>Configuration enregistrée</td><td>OK</td>";

	  print '<tr><td colspan="2">Test des répertoires</td></tr>';
	  
	  if (! is_dir($HTTP_POST_VARS["main_dir"]))
	    {
	      print "<tr><td>Le dossier ".$HTTP_POST_VARS["main_dir"]." n'existe pas !</td><td>Erreur</td></tr>";
	      $error++;
	    }
	  else
	    {
	      
	      print "<tr><td>Le dossier ".$HTTP_POST_VARS["main_dir"]." existe</td><td>OK</td></tr>";
	      /*
	       * Répertoire des documents
	       */
	      if (! is_dir($HTTP_POST_VARS["main_dir"]."/document"))
		{
		  @mkdir($HTTP_POST_VARS["main_dir"]."/document", 0755);
		}
	      
	      
	      if (! is_dir($HTTP_POST_VARS["main_dir"]."/document"))
		{
		  print "<tr><td>Le dossier ".$HTTP_POST_VARS["main_dir"]."/document n'existe pas !<p>";
		  print "- Vous devez créer le dossier : <b>".$HTTP_POST_VARS["main_dir"]."/document</b> et permettre au serveur web d'écrire dans celui-ci";
		  print '</td><td bgcolor="red">Erreur</td></tr>';
		  $error++;
		}
	      else
		{
		  $dir[0] = $HTTP_POST_VARS["main_dir"]."/document/facture";
		  $dir[1] = $HTTP_POST_VARS["main_dir"]."/document/propale";
		  $dir[2] = $HTTP_POST_VARS["main_dir"]."/document/societe";
		  $dir[3] = $HTTP_POST_VARS["main_dir"]."/document/ficheinter";
		  $dir[4] = $HTTP_POST_VARS["main_dir"]."/document/produit";
		  $dir[5] = $HTTP_POST_VARS["main_dir"]."/document/images";
		  $dir[6] = $HTTP_POST_VARS["main_dir"]."/document/rapport";
		  
		  for ($i = 0 ; $i < sizeof($dir) ; $i++)
		    {
		      if (is_dir($dir[$i]))
			{
			  print "<tr><td>Le dossier ".$dir[$i]." existe</td><td>OK</td></tr>";
			}
		      else
			{
			  if (! @mkdir($dir[$i], 0755))
			    {
			      print "<tr><td>Impossible de créer : ".$dir[$i]."</td><td bgcolor=\"red\">Erreur</td></tr>";
			      $error++;
			    }
			  else
			    {
			      print "<tr><td>Création de : ".$dir[$i]." réussie</td><td>OK</td></tr>";
			    }
			}
		    }
		}
	    }	  	 	  
	}
    }
  else
    {
      print "Erreur le système à besoin d'écrire dans le fichier $conf veuillez mettre les droits correct pour cela.";
    }
  

  /*
   * Base de données
   *
   */
  require ($dolibarr_main_document_root . "/lib/mysql.lib.php");
  require ($dolibarr_main_document_root . "/conf/conf.class.php");

  if (isset($HTTP_POST_VARS["db_create_user"]) && $HTTP_POST_VARS["db_create_user"] == "on")
    {
      $conf = new Conf();
      $conf->db->host = $dolibarr_main_db_host;
      $conf->db->name = "mysql";
      $conf->db->user = isset($HTTP_POST_VARS["db_user_root"])?$HTTP_POST_VARS["db_user_root"]:"";
      $conf->db->pass = isset($HTTP_POST_VARS["db_user_pass"])?$HTTP_POST_VARS["db_user_pass"]:"";
      $db = new DoliDb();
	  
      $sql = "INSERT INTO user ";
      $sql .= "(Host,User,password)";
      $sql .= " VALUES ('$dolibarr_main_db_host','$dolibarr_main_db_user',password('$dolibarr_main_db_pass'))";

      $db->query($sql);

      $sql = "INSERT INTO db ";
      $sql .= "(Host,Db,User,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Index_Priv,Alter_priv)";
      $sql .= " VALUES ('$dolibarr_main_db_host','$dolibarr_main_db_name','$dolibarr_main_db_user'";
      $sql .= ",'Y','Y','Y','Y','Y','Y','Y','Y')";

      if ($db->query($sql))
	{

	  $db->query("flush privileges");

	  print "<tr><td>Création de l'utilisateur : $dolibarr_main_db_user</td><td>OK</td></tr>";
	}
      else
	{
		if ($db->errno() == 1062) {
	  		print "<tr><td>Création de l'utilisateur : $dolibarr_main_db_user</td><td>Deja existant</td></tr>";
	  	} else {
	  		print "<tr><td>Création de l'utilisateur : $dolibarr_main_db_user</td><td>ERREUR</td></tr>";
	  	}
	}

      $db->close();

    }

  
  $conf = new Conf();
  $conf->db->host = $dolibarr_main_db_host;
  $conf->db->name = $dolibarr_main_db_name;
  $conf->db->user = $dolibarr_main_db_user;
  $conf->db->pass = $dolibarr_main_db_pass;
  $db = new DoliDb();
  $ok = 1;

  if ($ok)
    {
      if ($db->connected == 1)
	{
	  print "<tr><td>Connexion au serveur : $dolibarr_main_db_host</td><td>OK</td></tr>";
	}
      else
	{
	  print "<tr><td>Connexion au serveur : $dolibarr_main_db_host</td><td>ERREUR</td></tr>";
	  $ok = 0;	      
	}
    }
  
  if ($ok)
    {
      if($db->database_selected == 1)
	{
	  //
	  // Connexion base existante
	  // 
	  print "<tr><td>Connexion réussie à la base : $dolibarr_main_db_name</td><td>OK</td></tr>";
	  
	  $ok = 1 ;
	}
      else
	{
	  //
	  // Création de la base
	  //

	  print "<tr><td>Echec de connexion à la base : $dolibarr_main_db_name</td><td>Warning</td></tr>";
	  print '<tr><td colspan="2">Création de la base : '.$dolibarr_main_db_name.'</td></tr>';
	  	  
	  $db->close();
	  $conf = new Conf();
	  $conf->db->host = $dolibarr_main_db_host;
	  $conf->db->name = "mysql";
      $conf->db->user = isset($HTTP_POST_VARS["db_user_root"])?$HTTP_POST_VARS["db_user_root"]:"";
      $conf->db->pass = isset($HTTP_POST_VARS["db_user_pass"])?$HTTP_POST_VARS["db_user_pass"]:"";
	  $db = new DoliDb();
	  
	  if ($ok)
	    {
	      if ($db->connected == 1)
		{
		  print "<tr><td>Connexion au serveur : $dolibarr_main_db_host avec l'utilisateur : ".$HTTP_POST_VARS["db_user_root"]."</td><td>OK</td></tr>";
		}
	      else
		{
		  print "<tr><td>Connexion au serveur : $dolibarr_main_db_host avec l'utilisateur : ".$HTTP_POST_VARS["db_user_root"]."</td><td>ERREUR</td></tr>";
		  $ok = 0;
		}
	    }
	  
	  if ($ok)
	    {  
	      if($db->database_selected == 1)
		{
		}
	      else
		{
		  print "<tr><td>Vérification des droits de création</td><td>ERREUR</td></tr>";
		  print '<tr><td colspna="2">-- Droits insuffissant</td></tr>';
		  $ok = 0;
		}
	    }
	  
	  if ($ok)
	    {
	      if ($db->create_db ($dolibarr_main_db_name))
		{			      			      
		  print "<tr><td>Création de la base : $dolibarr_main_db_name</td><td>OK</td></tr>";
		}
	      else
		{
		  print "<tr><td>Création de la base : $dolibarr_main_db_name</td><td>ERREUR</td></tr>";
		  $ok = 0;
		}
	    }
	  
	}
    }    
  

}


?>
</table>
</div>
</div>

<?PHP
if ($ok)
{
print '
<div class="barrebottom">
<form action="etape2.php" method="POST">
<input type="hidden" name="action" value="set">
<input type="submit" value="Etape suivante ->">
</form>
</div>
';
}
?>

</body>
</html>

