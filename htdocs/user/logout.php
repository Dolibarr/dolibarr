<?
require_once "Auth/Auth.php";
$a = new Auth("DB");
$a->setShowLogin (false);
$a->start();
if ($a->getAuth()) 
  $a->logout();
header("Location: /"); 
?>
