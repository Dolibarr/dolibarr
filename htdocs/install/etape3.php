






REMPLACE PAR ETAPE 2 



TODO SUPPRIMER LE FICHIER















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
$etape = 3;
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
  print '<h2>Paramétrage des constantes</h2>';

  print '<table cellspacing="0" cellpadding="4" border="0" width="100%">';
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
      $sql[0] = "REPLACE INTO llx_const SET name = 'FAC_OUTPUTDIR', value='".$dolibarr_main_document_root."/document/facture', visible=0, type='chaine'";
  
      $sql[1] = "REPLACE INTO llx_const SET name = 'FAC_OUTPUT_URL', value='".$dolibarr_main_url_root."/document/facture', visible=0, type='chaine'";
      
      $sql[2] = "REPLACE INTO llx_const SET name = 'PROPALE_OUTPUTDIR', value='".$dolibarr_main_document_root."/document/propale', visible=0, type='chaine'";
      
      $sql[3] = "REPLACE INTO llx_const SET name = 'PROPALE_OUTPUT_URL', value='".$dolibarr_main_url_root."/document/propale', visible=0, type='chaine'";
      
      $sql[4] = "REPLACE INTO llx_const SET name = 'FICHEINTER_OUTPUTDIR', value='".$dolibarr_main_document_root."/document/ficheinter', visible=0, type='chaine'";
      
      $sql[5] = "REPLACE INTO llx_const SET name = 'FICHEINTER_OUTPUT_URL', value='".$dolibarr_main_url_root."/document/ficheinter', visible=0, type='chaine'";
      
      $sql[6] = "REPLACE INTO llx_const SET name = 'SOCIETE_OUTPUTDIR', value='".$dolibarr_main_document_root."/document/societe', visible=0, type='chaine'";
      
      $sql[7] = "REPLACE INTO llx_const SET name = 'SOCIETE_OUTPUT_URL', value='".$dolibarr_main_url_root."/document/societe', visible=0, type='chaine'";
      $result = 0;
      
    }
  
  for ($i=0; $i < sizeof($sql);$i++)
    {

      print "<tr><td>Définitions des constantes ".($i+1)."/8</td>";      

      if ($db->query($sql[$i]))
	{
	  print "<td>OK</td></tr>";
	  $result++;
	}
      else
	{
	  print "<td>Erreur</td></tr>";
	}
    }
  
  if ($result == sizeof($sql))
    {
      print '</table>';
	  
      if ($error == 0)
	{
	  
	  $db->query("DELETE FROM llx_const WHERE name='MAIN_NOT_INSTALLED'");
	  
	}
    }
  else
    {
      print '</table>';
      print $db->error();
    }
  $db->close();
}

?>
</div>
</div>
<div class="barrebottom">
<form action="etape4.php" method="POST">
<input type="hidden" name="action" value="set">
<input type="submit" value="Etape suivante ->">
</form>

</div>
</body>
</html>
