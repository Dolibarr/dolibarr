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
$etape = 5;
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
  print '<h2>Création du compte admin</h2>';
  print '<form action="etape5.php" method="POST">';
  print '<table cellspacing="0" cellpadding="4" border="1" width="100%">';

  $error=0;

  $conf = new Conf();
  $conf->db->host = $dolibarr_main_db_host;
  $conf->db->name = $dolibarr_main_db_name;
  $conf->db->user = $dolibarr_main_db_user;
  $conf->db->pass = $dolibarr_main_db_pass;
  $db = new DoliDb();
  $ok = 0;
  if ($db->ok == 1)
    {

      print '<tr><td>Compte administrateur :</td><td>';
      print '<input name="login"></td></tr>';
      print '<tr><td>Mot de passe :</td><td>';
      print '<input type="password" name="login"></td></tr>';
      print '<tr><td>Vérification du mot de passe :</td><td>';
      print '<input type="password" name="login"></td></tr>';
      print '</table>';
      $db->close();
    }
}

?>
</div>
</div>
<div class="barrebottom">
<input type="hidden" name="action" value="set">
<input type="submit" value="Etape suivante ->">
</form>
</div>
</body>
</html>
