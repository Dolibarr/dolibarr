<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien DiCintio   <sdicintio@ressource-toi.org>
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

/**     \file       htdocs/install/fileconf.php
        \brief      Demande les infos qui constituerons le contenu du fichier conf.php. Ce fichier sera remplie à l'étape suivante
        \version    $Revision$
*/

include_once("./inc.php");

$setuplang=$_POST["selectlang"];
$langs->defaultlang=$setuplang;
$langs->load("install");

pHeader("Fichier de configuration","etape1");

// Ici, le fichier conf.php existe et est forcément editable car le test a été fait précédemment.
include_once($conffile);

print '<table border="0" cellpadding="1" cellspacing="0">';

print '<tr>';
print '<td valign="top" class="label">';
print "Répertoire d'installation des pages web";
print "</td>";

if(! isset($dolibarr_main_url_root) || strlen($dolibarr_main_url_root) == 0)
{
  // Si le php fonctionne en CGI, alors SCRIPT_FILENAME vaut le path du php et
  // ce n'est pas ce qu'on veut. Dans ce cas, on propose $_SERVER["DOCUMENT_ROOT"]
  if (eregi('php$',$_SERVER["SCRIPT_FILENAME"]) || eregi('php\.exe$',$_SERVER["SCRIPT_FILENAME"])) {
    $dolibarr_main_document_root=$_SERVER["DOCUMENT_ROOT"];

    if (! eregi('\/dolibarr/htdocs$',$dolibarr_main_document_root))
      {
	$dolibarr_main_document_root.="/dolibarr/htdocs";
      }
  }
  else
    {
      $dolibarr_main_document_root = substr($_SERVER["SCRIPT_FILENAME"],0,strlen($_SERVER["SCRIPT_FILENAME"])- 21 );
      // Nettoyage du path proposé
      // Gere les chemins windows avec double "\"
      $dolibarr_main_document_root = str_replace('\\\\','/',$dolibarr_main_document_root);
      
      // Supprime le slash ou antislash final
      $dolibarr_main_document_root = ereg_replace('[\\\\\/]$','',$dolibarr_main_document_root);	
												  }
}

?>
<td  class="label" valign="top"><input type="text" size="60" value="<?php print $dolibarr_main_document_root; ?>" name="main_dir">
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
</td>
<?php 
if(! isset($dolibarr_main_data_root) || strlen($dolibarr_main_data_root) == 0)
{
    // Si le répertoire documents non défini, on en propose un par défaut
    $dolibarr_main_data_root=ereg_replace("/htdocs","",$dolibarr_main_document_root);
    $dolibarr_main_data_root.="/documents";
}
?>
<td class="label" valign="top"><input type="text" size="60" value="<?php print $dolibarr_main_data_root; ?>" name="main_data_dir">
</td><td class="comment">
Sans le slash "/" à la fin. Il est recommandé de mettre ce répertoire en dehors du répertoire des pages web.<br>
exemples :<br>
<li>/var/www/dolibarr/documents</li>
<li>C:/wwwroot/dolibarr/documents</li>
</td>
</tr>

<tr>
<td valign="top" class="label">
URL Racine</td><td valign="top" class="label"><input type="text" size="60" name="main_url" value="
<?php 
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
<?php
if (!isset($dolibarr_main_db_host))
{
$dolibarr_main_db_host = "localhost";
}
?>
<tr>
<!-- moi-->
<td valign="top" class="label">Choix de la base de données</td>

<td class="label"><select name='db_type'>
<option value='mysql'<?php echo (!$dolibarr_main_db_type||$dolibarr_main_db_type=='mysql')?" selected":"" ?>>MySql</option>
<option value='pgsql'<?php echo ($dolibarr_main_db_type=='pgsql')?" selected":"" ?>>PostgreSQL (expérimental, non opérationnel)</option>
</select>
&nbsp;
</td>

<td class="comment">Type de la base de donnée</td>

</tr>

<tr>
<td valign="top" class="label">Serveur</td>
<td valign="top" class="label"><input type="text" name="db_host" value="<?php print $dolibarr_main_db_host ?>">
<input type="hidden" name="base" value="<?php print $test_base?>">
</td>
<td class="comment">Nom ou adresse ip du serveur de base de données, généralement 'localhost' quand le serveur est installé sur la même machine que le serveur web</td>

</tr>

<tr>
<td class="label">Nom de la base de données</td>

<td class="label" valign="top"><input type="text" name="db_name" value="<?php echo $dolibarr_main_db_name ?>"></td>
<td class="comment">Nom de la base de données Dolibarr (sera créée si nécessaire)</td>
</tr>

<tr class="bg1">
<td class="label" valign="top">Login</td>

<td class="label"><input type="text" name="db_user" value="<?php print isset($dolibarr_main_db_user)?$dolibarr_main_db_user:'' ?>"></td>
<td class="comment">Login de l'administrateur de la base de données Dolibarr. Laisser vide si vous vous connectez en anonymous</td>
</tr>

<tr>
<td class="label" valign="top">Mot de passe</td>

<td class="label"><input type="text" name="db_pass" value="<?php print isset($dolibarr_main_db_pass)?$dolibarr_main_db_pass:'' ?>"></td>
<td class="comment">Mot de passe de l'administrateur de la base de données Dolibarr. Laisser vide si vous vous connectez en anonymous</td>
</tr>

<tr>
<td class="label" valign="top">Créer l'utilisateur</td>

<td class="label"><input type="checkbox" name="db_create_user"></td>
<td class="comment">Cocher cette option si l'utilisateur doit-être créé
</td>
</tr>

<tr>
<td colspan="3" align="center"><h2>Base de données - Accés super utilisateur</h2></td></tr>

<tr>
<td class="label" valign="top">Login</td>
<td class="label" valign="top"><input type="text" name="db_user_root" value="<?php if(isset($db_user_root)) print $db_user_root; ?>"></td>
<td class="label"><div class="comment">Login de l'utilisateur ayant les droits de création de la base de données, inutile si votre base est déjà créée (comme lorsque vous êtes chez un hébergeur). Laisser vide si vous vous connectez en anonymous</div>
</td>
</tr>

<tr>
<td class="label" valign="top">Mot de passe</td>
<td class="label" valign="top"><input type="text" name="db_pass_root" value="<?php if(isset($db_pass_root)) print $db_pass_root; ?>"></td>
<td class="label"><div class="comment">Laisser vide si l'utilisateur n'a pas de mot de passe</div>
</td>
</tr>

</table>

<?php

pFooter($err);

?>
