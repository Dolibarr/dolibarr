<?PHP

if ($action == "set")
{
  print "- Enregistrement des valeurs<br>";

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

      print "- Configuration enregistré<br>";
      print "- test de connexion à la base de données<br>";
      require ($dolibarr_main_document_root . "/lib/mysql.lib.php3");
      require ($dolibarr_main_document_root . "/conf/conf.class.php3");
      $conf = new Conf();
      $conf->db->host = $dolibarr_main_db_host;
      $conf->db->name = $dolibarr_main_db_name;
      $conf->db->user = $dolibarr_main_db_user;
      $conf->db->pass = $dolibarr_main_db_pass;
      $db = new Db();

      $sql = "REPLACE INTO llx_const SET name = 'FAC_OUTPUTDIR', value='".$dolibarr_main_document_root."/document', visible=0, type='chaine'";

      if ($db->query($sql))
	{
	  print "- connexion réussie à la base de données<br>";

	  print '<a href="'.$dolibarr_main_url_root .'/">Go !</a>';

	}
      else
	{
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
Sans le slash "/" à la fin
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
Laissez vide si vous vous connectez en anonymous
</td>
</tr>
<tr>
<td valign="top">pass</td>
<td>
<input type="text" name="db_pass" value="<?PHP print $dolibarr_main_db_pass ?>">
<br>
Laissez vide si vous vous connectez en anonymous
</td>
</tr>
<tr>
<td colspan="2" align="center"><input type="submit"></td>
</tr>
</table>
</form>

</body>
</html>
