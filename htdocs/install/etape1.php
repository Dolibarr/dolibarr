<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso8859-1">
<link rel="stylesheet" type="text/css" href="./default.css">
<title>Dolibarr Install</title>
</head>
<body>
<div class="main">
 <div class="main-inside">
<h2>Installation de Dolibarr - Etape 1/5</h2>

<?PHP

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
  else
    {
      print "Erreur le système à besoin d'écrire dans le fichier $conf veuillez mettre les droits correct pour cela.";
    }
}

if (file_exists("$conf"))
{
  include ("$conf");
}
else
{
  print "$conf does not exists<br>";
}

?>
</table>
</div>
</div>

<div class="barrebottom">
<form action="etape2.php" method="POST">
<input type="hidden" name="action" value="set">
<input type="submit" value="Etape suivante ->">
</form>
</div>
</body>
</html>
