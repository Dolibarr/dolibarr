<?php
/* Copyright (C) 2003 Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier			 <benoit.mortier@opensides.be>
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
 */

/*!	\file htdocs/admin/external_rss.php
		\ingroup    external_rss
		\brief      Page d'administration/configuration du module ExternalRss
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

llxHeader();

if (!$user->admin)
  accessforbidden();


$def = array();

// positionne la variable pour le nombre de rss externes
$db->query("select count(*) nb from ".MAIN_DB_PREFIX."const WHERE name like 'EXTERNAL_RSS_URLRSS_%'");
$obj = $db->fetch_object(0);
$nbexternalrss = $obj->nb;

if ($_POST["action"] == 'add')
{

    $external_rss_urlrss = "external_rss_urlrss_" . $_POST["norss"];

    if(isset($_POST[$external_rss_urlrss])) {
      $external_rss_title = "external_rss_title_" . $_POST["norss"];
      //$external_rss_url = "external_rss_url_" . $_POST["norss"];
			
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name ='EXTERNAL_RSS_TITLE_" . $_POST["norss"] . "'; ";
      $db->query($sql); $sql='';
			$sql1 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('EXTERNAL_RSS_TITLE_" . $_POST["norss"] . "','".$_POST[$external_rss_title]."',0) ;";
						
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name ='EXTERNAL_RSS_URLRSS_" . $_POST["norss"] . "'; ";
			$db->query($sql);$sql='';
		$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('EXTERNAL_RSS_URLRSS_" . $_POST["norss"] . "','".$_POST[$external_rss_urlrss]."',0) ;";
			      
      if ($db->query($sql1) && $db->query($sql2))
	{
        header("Location: external_rss.php");
	}
      else
        dolibarr_print_error($db); 
    }
}

if ($_POST["delete"])
{
    if(isset($_POST["norss"])) {
      $sql  = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXTERNAL_RSS_URL_" . $_POST["norss"]."'";
      $sql1 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXTERNAL_RSS_TITLE_" . $_POST["norss"]."'";
      $sql2 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXTERNAL_RSS_URLRSS_" . $_POST["norss"]."'";
      
      $result = $db->query($sql);
      $result = $db->query($sql1);
      $result = $db->query($sql2);
      if ($result) {
        header("Location: external_rss.php");
      } else {
        dolibarr_print_error($db); 
      }
    }
}

if ($_POST["modify"])
{
    $external_rss_urlrss = "external_rss_urlrss_" . $_POST["norss"];
    if(isset($_POST[$external_rss_urlrss])) {
      $external_rss_title = "external_rss_title_" . $_POST["norss"];
      //$external_rss_url = "external_rss_url_" . $i;
			
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXTERNAL_RSS_TITLE_" . $_POST["norss"]."';";
			$db->query($sql);$sql='';
			$sql1 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
			('" . "EXTERNAL_RSS_TITLE_" . $_POST["norss"] . "',". $_POST[$external_rss_title]."',0) ;";
			
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXTERNAL_RSS_URLRSS_" . $_POST["norss"]."';";
			$db->query($sql);$sql='';
			$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES 
			('" . "EXTERNAL_RSS_URLRSS_" . $_POST["norss"] . "','". $_POST[$external_rss_urlrss]."',0)";
      
      if ($db->query($sql1) && $db->query($sql2))
	{
        header("Location: external_rss.php");
	}
      else
        dolibarr_print_error($db); 
    }
}


/*
 * Affichage du formulaire de saisie
 */
  
print_fiche_titre("Configuration des imports de flux RSS", $mesg);

?>

Attention, pour la récupération des données au format RSS, les urls en https ne marchent pas pour l'instant.
Exemples:<ul>
  <li>WikiApril / http://wiki.april.org / http://wiki.april.org/RecentChanges?format=rss (et tous les sites phpwiki)</li>
  <li>LinuxFR / http://linuxfr.org / http://www.linuxfr.org/backend.rss</li>
  <li>WikiInterneRycks / ../wiki / ../wiki/RecentChanges?format=rss (ne marche pas, je suis en https et en plus y a un apache_auth)</li>
  <li>LoLix / http://back.fr.lolix.org/ / http://back.fr.lolix.org/jobs.rss.php3</li>
  <li>Parinux / http://parinux.org/ / http://parinux.org/backend.rss</li>
  <li>Docs d'AbulÉdu / http://docs.abuledu.org / http://docs.abuledu.org/backend.php3 (et tous les sites spip)</li>
 </ul>
 
<form name="externalrssconfig" action="external_rss.php" method="post">

<table class="border" cellpadding="2" cellspacing="0">
<tr class="liste_titre">
  <td colspan="2">Syndication d'un nouveau flux RSS</td>
</tr>
<tr>
  <td>Titre</td>
  <td><input type="text" name="external_rss_title_<?php echo $nbexternalrss ?>" value="<?php echo @constant("EXTERNAL_RSS_TITLE_" . $nbexternalrss) ?>" size="45"></td>
</tr>
<!--
<tr>
  <td>URL du site</td>
  <td><input type="text" name="external_rss_url_<?php echo $nbexternalrss ?>" value="<?php echo @constant('EXTERNAL_RSS_URL_' . $nbexternalrss) ?>" size="45"></td>
</tr>
-->
<tr>
  <td>URL du RSS</td>
  <td><input type="text" name="external_rss_urlrss_<?php echo $nbexternalrss ?>" value="<?php echo @constant("EXTERNAL_RSS_URLRSS_" . $nbexternalrss) ?>" size="45"></td>
</tr>
<tr><td colspan="2">
<input type="submit"  value="<?php echo $langs->trans("Add") ?>">
<input type="hidden" name="action" value="add">
<input type="hidden" name="norss" value="<?php echo $nbexternalrss ?>">
</td>
</table>

</form>

<br>

<table class="border" cellpadding="3" cellspacing="0">

<?php

for($i = 0; $i < $nbexternalrss; $i++) {
  print "<tr class=\"liste_titre\">
<form name=\"externalrssconfig\" action=\"external_rss.php\" method=\"post\">
  <td colspan=\"2\">Syndication du flux numéro " . ($i+1) . "</td>
</tr>
<tr>
  <td>Titre</td>
  <td><input type=\"text\" name=\"external_rss_title_" . $i . "\" value=\"" . @constant("EXTERNAL_RSS_TITLE_" . $i) . "\" size=\"45\"></td>
</tr>
<!--
<tr>
  <td>URL du site</td>
  <td><input type=\"text\" name=\"external_rss_url_" . $i . "\" value=\"". @constant("EXTERNAL_RSS_URL_" . $i) . "\" size=\"45\"></td>
</tr>
-->
<tr>
  <td>URL du RSS</td>
  <td><input type=\"text\" name=\"external_rss_urlrss_" . $i . "\" value=\"" . @constant("EXTERNAL_RSS_URLRSS_" . $i) . "\" size=\"45\"></td>
</tr>
<tr>
<td colspan=\"2\">
<input type=\"submit\" name=\"modify\" value=\"".$langs->trans("Modify")."\">
<input type=\"submit\" name=\"delete\" value=\"".$langs->trans("Delete")."\">
<input type=\"hidden\" name=\"norss\"  value=\"$i\">
</td>
</form>
</tr>
";
}
?>

</table>

<?php 


$db->close();

llxFooter();

?>
