<?PHP
/* Copyright (C) 2003 Éric Seigne <erics@rycks.com>
 * Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

llxHeader();

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}

$def = array();

// positionne la variable pour le nombre de rss externes
$nbexternalrss = 1;

if ($action == 'save')
{
  for ($i = 0; $i < $nbexternalrss; $i++) {
    $external_rss_url = "external_rss_url_" . $i;
    if(isset($$external_rss_url)) {
      $external_rss_title = "external_rss_title_" . $i;
      $external_rss_urlrss = "external_rss_urlrss_" . $i;
      $sql = "REPLACE INTO llx_const SET name = '" . "EXTERNAL_RSS_URL_" . $i . "', value='".$$external_rss_url."', visible=0"; 
      $sql1 = "REPLACE INTO llx_const SET name = '" . "EXTERNAL_RSS_TITLE_" . $i . "', value='".$$external_rss_title."', visible=0";
      $sql2 = "REPLACE INTO llx_const SET name = '" . "EXTERNAL_RSS_URLRSS_" . $i . "', value='".$$external_rss_urlrss."', visible=0";
      
      if ($db->query($sql) && $db->query($sql1) && $db->query($sql2))
	{
	  // la constante qui a été lue en avant du nouveau set
	  // on passe donc par une variable pour avoir un affichage cohérent
	  print "<p>Enregistrement confirmé pour le site " . $$external_rss_title . "</p>\n";
	}
      else
	print "<p>Erreur d'enregistement pour le site " . $$external_rss_title . "</p>\n";
    }
  }
}


/*
 * Affichage du formulaire de saisie
 */
  
print_fiche_titre("Configuration du lien vers un site syndiqué", $mesg);
  
print "\n<p align=\"justify\">Attention, pour la récupération des données au format RSS, les urls en https ne marchent pas pour l'instant. </p>
<p>Exemples:
 <ul>
  <li>WikiApril / http://wiki.april.org / http://wiki.april.org/RecentChanges?format=rss (et tous les sites phpwiki)</li>
  <li>LinuxFR / http://linuxfr.org / http://www.linuxfr.org/backend.rss</li>
  <li>WikiInterneRycks / ../wiki / ../wiki/RecentChanges?format=rss (ne marche pas, je suis en https et en plus y a un apache_auth)</li>
  <li>LoLix / http://back.fr.lolix.org/ / http://back.fr.lolix.org/jobs.rss.php</li>
  <li>Parinux / http://parinux.org/ / http://parinux.org/backend.rss</li>
  <li>Docs d'AbulÉdu / http://docs.abuledu.org / http://docs.abuledu.org/backend.php (et tous les sites spip)</li>
 </ul>
</p>
<form name=\"externalrssconfig\" action=\"" . $_SERVER['SCRIPT_NAME'] . "\" method=\"post\">
<table border=\"1\" cellpadding=\"3\" cellspacing=\"0\">\n";

// Pour l'instant on fait un seul RSS externe, mais c'est sans soucis qu'on passe à plus !
// ptet définir une variable pour NBMAX_RSS_EXTERNE ... modifier en fonction le fichier
// ../pre.inc.php
for($i = 0; $i < $nbexternalrss; $i++) {
  print "<tr>
  <th colspan=\"2\">Syndication du site numéro " . ($i+1) . "</th>
</tr>
<tr>
  <td>Titre</td>
  <td><input type=\"text\" name=\"external_rss_title_" . $i . "\" value=\"" . @constant("EXTERNAL_RSS_TITLE_" . $i) . "\" size=\"45\"></td>
</tr>
<tr>
  <td>URL du site</td>
  <td><input type=\"text\" name=\"external_rss_url_" . $i . "\" value=\"". @constant("EXTERNAL_RSS_URL_" . $i) . "\" size=\"45\"></td>
</tr>
<tr>
  <td>URL du RSS</td>
  <td><input type=\"text\" name=\"external_rss_urlrss_" . $i . "\" value=\"" . @constant("EXTERNAL_RSS_URLRSS_" . $i) . "\" size=\"45\"></td>
</tr>\n";
}

clearstatcache();
  
print "<tr>
<td colspan=\"2\"><input type=\"submit\" name=\"envoyer\" value=\"Enregistrer\"></td>
</tr>
</table>
<input type=\"hidden\" name=\"action\" value=\"save\"></form>\n";

/*
 *
 *
 */

print_titre("Résultat du fetch");

// à modifier si on a plus d'un module RSS externe !
if (defined("MAIN_MODULE_EXTERNAL_RSS") && MAIN_MODULE_EXTERNAL_RSS)
{
  print '<a href="'.EXTERNAL_RSS_URL_0.'">'.EXTERNAL_RSS_TITLE_0.'</a><br><br>';
  require_once("../includes/magpierss/rss_fetch.inc");

  $rss = fetch_rss( EXTERNAL_RSS_URLRSS_0 );
  
  foreach ($rss->items as $item)
    {
      $href = $item['link'];
      $title = $item['title'];
      print '<a href="'.$href.'">'.$title.'</a><br>';
    }
}

$db->close();

llxFooter();
?>
