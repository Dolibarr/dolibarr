<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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

$conf = "../conf/conf.php";

if (is_readable($conf))
{
  include ($conf);
}

include("./inc.php");
pHeader();



if (!file_exists("../conf/conf.php"))
{
  print '<div class="error">';
  print "Le fichier <b>conf.php</b> n'existe pas reportez-vous à la ".$docurl." pour créer ce fichier<br>";

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
<tr>
<td valign="top">
<?php print "Répertoire d'installation"; ?>
</td><td valign="top"><input type="text" size="60" value="
<?php

if(strlen($dolibarr_main_url_root) == 0)
{
$dolibarr_main_document_root = substr($_SERVER["SCRIPT_FILENAME"],0,strlen($_SERVER["SCRIPT_FILENAME"])-18);
}


 print $dolibarr_main_document_root 
?>
" name="main_dir">
</td><td>
Sans le slash "/" à la fin<br>
exemple : /var/www/dolibarr/htdocs

</td>
</tr>

<tr class="bg1">
<td valign="top">
URL Racine</td><td valign="top"><input type="text" size="60" name="main_url" value="
<?php 
if(strlen($dolibarr_main_url_root) == 0)
{
  $dolibarr_main_url_root = substr($_SERVER["SCRIPT_URI"],0,strlen($_SERVER["SCRIPT_URI"])-9);
}

print $dolibarr_main_url_root ;

?>">
</td><td>
exemples : 
<br>
<ul>
<li>http://dolibarr.lafrere.net</li>
<li>http://www.lafrere.net/dolibarr</li>
</ul>
</tr>
<tr>
<td colspan="3" align="center"><h2>Base de données<h2></td>
</tr>
<?php
if (!isset($dolibarr_main_db_host))
{
$dolibarr_main_db_host = "localhost";
}
?>
<tr class="bg1">
<td valign="top">Serveur</td><td valign="top"><input type="text" name="db_host" value="<?php print $dolibarr_main_db_host ?>"></td>
<td><div class="comment">Nom du serveur de base de données, généralement 'localhost' quand le serveur est installé sur la même machine que le serveur web</div></td>
</tr>

<tr class="bg2">
<td>Nom de la base de données</td><td valign="top"><input type="text" name="db_name" value="<?php print $dolibarr_main_db_name ?>"></td>
<td><div class="comment">Nom de votre base de données</div></td>
</tr>

<tr class="bg1">
<td valign="top">Login</td>
<td>
<input type="text" name="db_user" value="<?php print $dolibarr_main_db_user ?>">
</td><td><div class="comment">Laisser vide si vous vous connectez en anonymous</div>
</td>
</tr>

<tr class="bg2">
<td valign="top">Mot de passe</td>
<td>
<input type="text" name="db_pass" value="<?php print $dolibarr_main_db_pass ?>">
</td><td><div class="comment">Laisser vide si vous vous connectez en anonymous</div>
</td>
</tr>

<tr class="bg1">
<td valign="top">Créer l'utilisateur</td>
<td>
<input type="checkbox" name="db_create_user">
</td><td><div class="comment">Cocher cette option si l'utilisateur doit-être créé</div>
</td>
</tr>



<tr><td colspan="3" align="center"><h2>Base de données - Accés super utilisateur</h2></td></tr>

<tr class="bg1">
<td valign="top">Login</td>
<td>
<input type="text" name="db_user_root">
</td><td><div class="comment">Login de l'utilisateur ayant les droits de création de la base de données, inutile si vous êtes chez un hébergeur, votre base de données est déjà créée. Laisser vide si vous vous connectez en anonymous</div>
</td>
</tr>

<tr class="bg2">
<td valign="top">Mot de passe</td>
<td>
<input type="text" name="db_pass_root">
</td><td><div class="comment">Laisser vide si vous vous connectez en anonymous</div>
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
