<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso8859-1">
<link rel="stylesheet" type="text/css" href="./default.css">
<title>Dolibarr Install</title>
</head>
<body>
<div class="main">
 <div class="main-inside">
<h2>Installation de Dolibarr - Etape 2/5</h2>

<?PHP
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
	}
      else
	{
	  print "<tr><td>Echec de connexion à la base : $dolibarr_main_db_name</td><td>ERREUR</td></tr>";

	  $ok = 0;

	  print "<tr><td>Création de la base : $dolibarr_main_db_name</td><td>-</td></tr>";

	  if ($db->create_db ($dolibarr_main_db_name))
	    {
	      print "<tr><td>Création de la base réussie : $dolibarr_main_db_name</td><td>OK</td></tr>";
	      $db->select_db ($dolibarr_main_db_name);

	      // Création des tables
	      $dir = "../../mysql/tables/";

	      $handle=opendir($dir);

	      while (($file = readdir($handle))!==false)
		{
		  if (substr($file, strlen($file) - 4) == '.sql' && substr($file,0,4) == 'llx_')
		    {
		      $name = substr($file, 0, strlen($file) - 4);
		      $classname = substr($file, 0, strlen($file) -12);
		      
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
			  print "<td>ERREUR</td></tr>";
			  $error++;
			}
		    }
		}
	      closedir($handle);	     
	    }
	  else
	    {
	      print "<tr><td>Erreur lors de la création de : $dolibarr_main_db_name</td><td>ERREUR</td></tr>";
	    }

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
