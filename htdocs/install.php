<?PHP

if ($action == "set")
{
  print "Enregistrement des valeurs";

  $fp = fopen("conf/conf.php", "w");
  if($fp)
    {
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
    }
  else
    {
      print "Erreur le système à besoin d'écrire dans le fichier conf/conf.php veuillez mettre les droits correct pour cela.";
    }

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
<center>
<h2>Installation de dolibarr</h2>
</center>
<form action="install.php" method="POST">
<input type="hidden" name="action" value="set">
<table border="1" cellpadding="4" cellspacing="0">
<tr>
<td>Répertoire d'install</td><td><input type="text" size="60" value="<?PHP print $dolibarr_main_document_root ?>" name="main_dir"></td>
</tr>
<tr>
<td valign="top">
URL Racine</td><td><input type="text" size="60" name="main_url" value="<?PHP print $dolibarr_main_url_root ?>">
<br>
exemples : 
<br>
<i>http://dolibarr.lafrere.net/</i>
ou
<i>http://www.lafrere.net/dolibarr/</i>
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

<a href="/">Go !</a>

</body>
</html>
