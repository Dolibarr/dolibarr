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

//
// Essaye de créer le fichier de conf
//

$conf = "../conf/conf.php";

if (file_exists($conf))
{
  include ($conf);
}
else
{
  $fp = @fopen("$conf", "w");
  if($fp)
    {
      @fwrite($fp, '<?PHP');
      @fputs($fp,"\n");
      @fputs($fp,"?>");
      fclose($fp);
    }
}
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso8859-1">
<link rel="stylesheet" type="text/css" href="./default.css">
<title>Dolibarr Install</title>
</head>
<body>

<?PHP

$docurl = '<a href="doc/dolibarr-install.html">documentation</a>';

if (!file_exists("../conf/conf.php"))
{
  print '<div class="error">';
  print "Le fichier <b>conf.php</b> n'existe pas, reportez-vous à la ".$docurl." pour créer ce fichier<br>";

  print '</div>';
}
else
{

  if (!is_writable("../conf/conf.php"))
    {
      print '<div class="error">';
      print "Le fichier <b>conf.php</b> n'est pas accessible en écriture, vérififiez les droits sur celui-ci, reportez-vous à la ".$docurl."<br>";
      
      print '</div>';
    }
}

?>

<div class="main">
<div class="main-inside">
<h2>Installation de Dolibarr</h2>
<form action="etape1.php" method="POST">
<input type="hidden" name="action" value="set">
<table border="0" cellpadding="4" cellspacing="0">
<tr class="bg2">
<td valign="top">
<?php print "Répertoire d'installation"; ?>
</td><td valign="top"><input type="text" size="60" value="
<?PHP
if(! isset($dolibarr_main_url_root) || strlen($dolibarr_main_url_root) == 0)
{
	$dolibarr_main_document_root = substr($_SERVER["SCRIPT_FILENAME"],0,strlen($_SERVER["SCRIPT_FILENAME"])-18);
	# Nettoyage du path proposé
	$dolibarr_main_document_root = str_replace('\\\\','/',$dolibarr_main_document_root);		# Gere les chemins windows avec double "\"
	$dolibarr_main_document_root = ereg_replace('[\\\\\/]$','',$dolibarr_main_document_root);	# Supprime le "\" ou "/" de fin
}
print "$dolibarr_main_document_root";
?>
" name="main_dir">
</td><td>
Sans le slash "/" à la fin<br>
exemples :<br>
<li>/var/www/dolibarr/htdocs</li>
<li>C:/wwwroot/dolibarr</li>
</td>
</tr>
<tr class="bg1">
<td valign="top">
URL Racine</td><td valign="top"><input type="text" size="60" name="main_url" value="
<?PHP 
if(! isset($dolibarr_main_url_root) || strlen($dolibarr_main_url_root) == 0)
{
	if (isset($_SERVER["SCRIPT_URI"])) {	# Si défini
		$dolibarr_main_url_root=$_SERVER["SCRIPT_URI"];
	}
	else {									# SCRIPT_URI n'est pas toujours défini (Exemple: Apache 2.0.44 pour Windows)
		$dolibarr_main_url_root="http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
	}
	$dolibarr_main_url_root = substr($dolibarr_main_url_root,0,strlen($dolibarr_main_url_root)-9);
	# Nettoyage de l'URL proposée
	$dolibarr_main_url_root = ereg_replace('\/$','',$dolibarr_main_url_root);	# Supprime le /
	$dolibarr_main_url_root = ereg_replace('\/index\.php$','',$dolibarr_main_url_root);	# Supprime le /index.php
	$dolibarr_main_url_root = ereg_replace('\/install$','',$dolibarr_main_url_root);	# Supprime le /install
}
print "$dolibarr_main_url_root";
?>">
</td><td>
exemples :<br>
<li>http://dolibarr.lafrere.net</li>
<li>http://www.lafrere.net/dolibarr</li>
</tr>


<tr><td colspan="3" align="center"><h2>Base de données - Accés super utilisateur</h2></td></tr>

<tr class="bg1">
<td valign="top">Serveur</td><td valign="top"><input type="text" name="db_host" value="<?PHP print isset($dolibarr_main_db_host)?$dolibarr_main_db_host:'localhost' ?>"></td>
<td><div class="comment">Nom ou adresse ip du serveur de base de données, généralement 'localhost' quand le serveur est installé sur la même machine que le serveur web</div></td>
</tr>

<tr class="bg2">
<td valign="top">Login administrateur de la base</td>
<td>
<input type="text" name="db_user_root">
</td><td><div class="comment">Login de l'utilisateur ayant les droits de création de bases de données.<br>Si vous avez déjà une base, vierge ou non, pour accueillir les tables dolibarr (exemple si vous êtes chez un hébergeur), ne rien remplir. Laisser vide également si vous vous connectez en anonymous</div>
</td>
</tr>

<tr class="bg1">
<td valign="top">Mot de passe</td>
<td>
<input type="text" name="db_pass_root">
</td><td><div class="comment">Laisser vide si vous vous connectez en anonymous</div>
</td>
</tr>


<tr><td colspan="3" align="center"><h2>Base de données Dolibarr<h2></td></tr>

<tr class="bg2">
<td>Nom de la base de données</td><td valign="top"><input type="text" name="db_name" value="<?PHP print isset($dolibarr_main_db_name)?$dolibarr_main_db_name:'dolibarr' ?>"></td>
<td><div class="comment">Nom de la base de données Dolibarr (sera créée si nécessaire)</div></td>
</tr>

<tr class="bg1">
<td valign="top">Login</td>
<td>
<input type="text" name="db_user" value="<?PHP print isset($dolibarr_main_db_user)?$dolibarr_main_db_user:'' ?>">
</td><td><div class="comment">Login de l'administrateur de la base de données Dolibarr. Laisser vide si vous vous connectez en anonymous</div>
</td>
</tr>

<tr class="bg2">
<td valign="top">Mot de passe</td>
<td>
<input type="text" name="db_pass" value="<?PHP print isset($dolibarr_main_db_pass)?$dolibarr_main_db_pass:'' ?>">
</td><td><div class="comment">Mot de passe de l'administrateur de la base de données Dolibarr. Laisser vide si vous vous connectez en anonymous</div>
</td>
</tr>

<tr class="bg1">
<td valign="top">Créer l'utilisateur</td>
<td>
<input type="checkbox" name="db_create_user">
</td><td><div class="comment">Cocher cette option si l'utilisateur doit-être créé</div>
</td>
</tr>



</table>
</div>
</div>

<div class="barrebottom">
<input type="submit" value="Etape suivante ->">
</form>
</div>
</body>
</html>
