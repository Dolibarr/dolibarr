<?PHP

if ($HTTP_POST_VARS["action"] == "set")
{
  print '<h2>Enregistrement des valeurs</h2>';

  print '<table cellspacing="0" cellpadding="4" border="1">';
  $error=0;
  $fp = fopen("conf/conf.php", "w");
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

      if (file_exists("conf/conf.php"))
	{
	  include ("conf/conf.php");
	}

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

	  if (! is_dir($HTTP_POST_VARS["main_dir"]."/document"))
	    {
	      print "<tr><td>Le dossier ".$HTTP_POST_VARS["main_dir"]."/document n'existe pas !<p>";
	      print "- Vous devez créer le dossier : <b>".$HTTP_POST_VARS["main_dir"]."/document</b> et permettre au serveur web d'écrire dans celui-ci";
	      print "</td><td>Erreur</td></tr>";
	    }
	  else
	    {
	      $dir[0] = $HTTP_POST_VARS["main_dir"]."/document/facture";
	      $dir[1] = $HTTP_POST_VARS["main_dir"]."/document/propale";
	      $dir[2] = $HTTP_POST_VARS["main_dir"]."/document/societe";
	      $dir[3] = $HTTP_POST_VARS["main_dir"]."/document/ficheinter";
	      $dir[4] = $HTTP_POST_VARS["main_dir"]."/document/produit";

	      for ($i = 0 ; $i < sizeof($dir) ; $i++)
		{

		  if (is_dir($dir[$i]))
		    {
		      print "<tr><td>Le dossier ".$dir[$i]." existe</td><td>OK</td></tr>";
		    }
		  else
		    {
		      if (! @mkdir($dir[$i]))
			{
			  print "<tr><td>Impossible de créer : ".$dir[$i]."</td><td>Erreur</td></tr>";
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

      print '<tr><td colspan="2">test de connexion à la base de données</td></tr>';
      require ($dolibarr_main_document_root . "/lib/mysql.lib.php3");
      require ($dolibarr_main_document_root . "/conf/conf.class.php");
      $conf = new Conf();
      $conf->db->host = $dolibarr_main_db_host;
      $conf->db->name = $dolibarr_main_db_name;
      $conf->db->user = $dolibarr_main_db_user;
      $conf->db->pass = $dolibarr_main_db_pass;
      $db = new Db();

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
	      print "<tr><td>requete sql $i</td><td>OK</td></tr>";
	      $result++;
	    }
	  else
	    {
	      print "<tr><td>requete sql $i</td><td>Erreur</td></tr>";
	    }
	}

      if ($result == sizeof($sql))
	{
	  print "<tr><td>connexion réussie à la base de données</td><td>OK</td></tr>";
	  print '</table>';

	  if ($error == 0)
	    {
	      print "Votre système est maintenant configuré, il ne vous reste plus qu'a sélectionner les modules que vous souhaitez utiliser. Pour cela cliquer sur l'url ci-dessous : <br>";
	      print '<a href="'.$dolibarr_main_url_root .'/admin/modules.php">Configurer les modules</a>';
	    }
	}
      else
	{
	  print '</table>';
	  print $db->error();
	}
      $db->close();
    }
  else
    {
      print "Erreur le système à besoin d'écrire dans le fichier conf/conf.php veuillez mettre les droits correct pour cela.";
    }
  print "<hr>";
}

if (file_exists("conf/conf.php"))
{
  include ("conf/conf.php");
}
else
{
  print "conf/conf.php does not exists<br>";
}

?>
<html>
<body bgcolor="#c0c0c0">
<head>
<title>Dolibarr Install</title>
</head>
<h2>Installation de dolibarr</h2>
<form action="install.php" method="POST">
<input type="hidden" name="action" value="set">
<table border="1" cellpadding="4" cellspacing="0">
<tr>
<td valign="top">
<?PHP print "Répertoire d'installation"; ?>
</td><td><input type="text" size="60" value="<?PHP print $dolibarr_main_document_root ?>" name="main_dir">
<br>
Sans le slash "/" à la fin<br>
exemple : /var/www/dolibarr/htdocs
</td>
</tr>
<tr>
<td valign="top">
URL Racine</td><td><input type="text" size="60" name="main_url" value="<?PHP print $dolibarr_main_url_root ?>">
<br>
exemples : 
<br>
<ul>
<li>http://dolibarr.lafrere.net</li>
<li>http://www.lafrere.net/dolibarr</li>
</ul>
</tr>
<tr>

<td colspan="2" align="center">Base de données</td>
</tr>
<tr>
<td>host</td><td><input type="text" name="db_host" value="<?PHP print $dolibarr_main_db_host ?>"></td>
</tr>
<tr>
<td>nom</td><td><input type="text" name="db_name" value="<?PHP print $dolibarr_main_db_name ?>"></td>
</tr>
<tr>
<td valign="top">user</td>
<td>
<input type="text" name="db_user" value="<?PHP print $dolibarr_main_db_user ?>">
<br>
Laisser vide si vous vous connectez en anonymous
</td>
</tr>
<tr>
<td valign="top">pass</td>
<td>
<input type="text" name="db_pass" value="<?PHP print $dolibarr_main_db_pass ?>">
<br>
Laisser vide si vous vous connectez en anonymous
</td>
</tr>
<tr>
<td colspan="2" align="center"><input type="submit"></td>
</tr>
</table>
</form>

</body>
</html>
