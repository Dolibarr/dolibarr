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
$etape = 6;
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

  print '<table cellspacing="0" cellpadding="4" border="1" width="100%">';
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
      $sql = "INSERT INTO llx_user(datec,login,pass,admin) VALUES (now()";
      $sql .= ",'".$HTTP_POST_VARS["login"]."'";
      $sql .= ",'".$HTTP_POST_VARS["pass"]."'"; 
      $sql .= ",1)";       
    }
  
  if ($db->query($sql))
    {
      $db->query("DELETE FROM llx_const WHERE name='MAIN_NOT_INSTALLED'");
      print "Création du compte administrateur réussie";
      $success = 1;
    }
  else
    {
      print "Echec de la création du compte administrateur";
    }
  print '</table>';

  $db->close();
}

?>
</div>
</div>
<?PHP
if ($success == 1)
{

}
?>
</body>
</html>
