<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004 Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004 Sebastien DiCintio   <sdicintio@ressource-toi.org>
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
include("./inc.php");
pHeader("Fichier de configuration","etape1");

$conf = "../conf/conf.php";


if (is_readable($conf))
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

$docurl = '<a href="doc/dolibarr-install.html">documentation</a>';

if (!file_exists($conf))
{
  print '<div class="error">';
  print "Le fichier <b>conf.php</b> n'existe pas reportez-vous à la ".$docurl." pour créer ce fichier<br>";
  print '</div>';
  $err++;
}
else
{

  if (!is_writable("../conf/conf.php"))
    {
      print '<div class="error">';
      print "Le fichier <b>conf.php</b> n'est pas accessible en écriture, vérifiez les droits sur celui-ci, reportez-vous à la ".$docurl."<br>";
      
      print '</div>';
      $err++;
    }
}

if ($err == 0)
{
?>


<table border="0" cellpadding="4" cellspacing="0">
<tr>
<td valign="top" class="label">
<?php print "Répertoire d'installation"; ?>
</td><td  class="label" valign="top"><input type="text" size="60" value="
<?PHP

if(! isset($dolibarr_main_url_root) || strlen($dolibarr_main_url_root) == 0)
{
	// Si le php fonctionne en CGI, alors SCRIPT_FILENAME vaut le path du php et
	// ce n'est pas ce qu'on veut. Dans ce cas, on propose $_SERVER["DOCUMENT_ROOT"]
	if (eregi('php$',$_SERVER["SCRIPT_FILENAME"]) || eregi('php\.exe$',$_SERVER["SCRIPT_FILENAME"])) {
		$dolibarr_main_document_root=$_SERVER["DOCUMENT_ROOT"];
		//print $dolibarr_main_document_root;
		if (! eregi('\/dolibarr/htdocs$',$dolibarr_main_document_root)) {
			$dolibarr_main_document_root.="dolibarr/htdocs";
		}
	}
	else {
		$dolibarr_main_document_root = substr($_SERVER["SCRIPT_FILENAME"],0,strlen($_SERVER["SCRIPT_FILENAME"])- 21 );
		# Nettoyage du path proposé
		$dolibarr_main_document_root = str_replace('\\\\','/',$dolibarr_main_document_root);	# Gere les chemins windows avec double "\"
		$dolibarr_main_document_root = ereg_replace('[\\\\\/]$','',$dolibarr_main_document_root);	# Supprime le "\" ou "/" de fin
	}
}
print $dolibarr_main_document_root;
?>
" name="main_dir">
</td><td class="comment">
Sans le slash "/" à la fin<br>
exemples :<br>
<li>/var/www/dolibarr/htdocs</li>
<li>C:/wwwroot/dolibarr/htdocs</li>
</td>
</tr>


<tr>
<td valign="top" class="label">
<?php print "Répertoire contenant les documents générés"; ?>
</td><td  class="label" valign="top"><input type="text" size="60" value="
<?PHP

//print ereg_replace("htdocs","documents",$dolibarr_main_document_root);
print $dolibarr_main_document_root."/documents";
?>
" name="main_data_dir">
</td><td class="comment">
Sans le slash "/" à la fin<br>
exemples :<br>
<li>/var/www/dolibarr/documents</li>
<li>C:/wwwroot/dolibarr/documents</li>
</td>
</tr>


<tr>
<td valign="top" class="label">
URL Racine</td><td valign="top" class="label"><input type="text" size="60" name="main_url" value="
<?PHP 
if(strlen($main_url) > 0)
  $dolibarr_main_url_root=$main_url;
if(! isset($dolibarr_main_url_root) || strlen($dolibarr_main_url_root) == 0)
{
	if (isset($_SERVER["SCRIPT_URI"])) {	# Si défini
		$dolibarr_main_url_root=$_SERVER["SCRIPT_URI"];
	}
	else {									# SCRIPT_URI n'est pas toujours défini (Exemple: Apache 2.0.44 pour Windows)
		$dolibarr_main_url_root="http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
	}
	$dolibarr_main_url_root = substr($dolibarr_main_url_root,0,strlen($dolibarr_main_url_root)-12);
	# Nettoyage de l'URL proposée
	$dolibarr_main_url_root = ereg_replace('\/$','',$dolibarr_main_url_root);	# Supprime le /
	$dolibarr_main_url_root = ereg_replace('\/index\.php$','',$dolibarr_main_url_root);	# Supprime le /index.php
	$dolibarr_main_url_root = ereg_replace('\/install$','',$dolibarr_main_url_root);	# Supprime le /install
}

print $dolibarr_main_url_root ;
?>">
</td><td class="comment">
exemples :<br> 
<li>http://dolibarr.lafrere.net</li>
<li>http://www.lafrere.net/dolibarr</li>
</tr>

<tr>
<td colspan="3" align="center"><h2>Base de données Dolibarr<h2></td>
</tr>
<?PHP
if (!isset($dolibarr_main_db_host))
{
$dolibarr_main_db_host = "localhost";
}
?>
<tr>
<!-- moi-->
<td valign="top" class="label">Choix de la base de données</td>

<td> <select name='db_type'>
<option value='mysql'>MySql</option>
<option value='pgsql'>PostgreSQL</option>
</select>
&nbsp;


<!--
<SCRIPT LANGUAGE="JavaScript" TYPE="text/javascript">

document.write('<input type = "button" name = "valide" value="confirmer" onclick="alert(this.form.elements[\'db_type\'].options[this.form.elements[\'db_type\'].selectedIndex].value);" >')

-->

<td valign="comment">Nom de la base de donnée qui est soit MySql par défaut ou alors PostgreSql.</td>

</tr>

<br>
<td valign="top" class="label">Serveur</td>
<td valign="top" class="label"><input type="text" name="db_host" value="<?PHP print $dolibarr_main_db_host ?>"></td>
<input type="hidden" name="base" value="<?PHP print $test_base?>">

<td class="comment">Nom ou adresse ip du serveur de base de données, généralement 'localhost' quand le serveur est installé sur la même machine que le serveur web</div></td>

</tr>

<tr>
<td class="label">Nom de la base de données</td>

<td class="label" valign="top"><input type="text" name="db_name" value="<?PHP print $dolibarr_main_db_name ?>"></td>
<td class="comment">Nom de la base de données Dolibarr (sera créée si nécessaire)</td>
</tr>

<tr class="bg1">
<td class="label" valign="top">Login</td>

<td class="label"><input type="text" name="db_user" value="<?PHP print isset($dolibarr_main_db_user)?$dolibarr_main_db_user:'' ?>"></td>
<td class="comment">Login de l'administrateur de la base de données Dolibarr. Laisser vide si vous vous connectez en anonymous</td>
</tr>

<tr>
<td class="label" valign="top">Mot de passe</td>

<td class="label"><input type="text" name="db_pass" value="<?PHP print isset($dolibarr_main_db_pass)?$dolibarr_main_db_pass:'' ?>"></td>
<td class="comment">Mot de passe de l'administrateur de la base de données Dolibarr. Laisser vide si vous vous connectez en anonymous</td>
</tr>

<tr>
<td class="label" valign="top">Créer l'utilisateur</td>

<td class="label"><input type="checkbox" name="db_create_user"></td>
<td class="comment">Cocher cette option si l'utilisateur doit-être créé</div>
</td>
</tr>

<tr>
<td colspan="3" align="center"><h2>Base de données - Accés super utilisateur</h2></td></tr>

<tr>
<td class="label" valign="top">Login</td>
<td class="label" valign="top"><input type="text" name="db_user_root" value="<?PHP if(isset($db_user_root)) print $db_user_root; ?>"></td>
<td class="label"><div class="comment">Login de l'utilisateur ayant les droits de création de la base de données, inutile si votre base est déjà créée (comme lorsque vous êtes chez un hébergeur). Laisser vide si vous vous connectez en anonymous</div>
</td>
</tr>

<tr>
<td class="label" valign="top">Mot de passe</td>
<td class="label" valign="top"><input type="text" name="db_pass_root" value="<?PHP if(isset($db_pass_root)) print $db_pass_root; ?>"></td>
<td class="label"><div class="comment">Laisser vide si l'utilisateur n'a pas de mot de passe</div>
</td>
</tr>

</table>
<?PHP
}
pFooter($err);
?>
