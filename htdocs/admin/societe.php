<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
/* Copyright (C) 2004 Éric Seigne <eric.seigne@ryxeo.com>
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

/*!	\file htdocs/admin/propale.php
		\ingroup    propale
		\brief      Page d'administration/configuration du module Propale
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

$codeclient_addon_var = CODECLIENT_ADDON;

if ($_GET["action"] == 'setcodeclient')
{
  if (dolibarr_set_const($db, "CODECLIENT_ADDON",$_GET["value"]))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $codeclient_addon_var = $_GET["value"];
      Header("Location: societe.php");
    }
}


$codecompta_addon_var = CODECOMPTA_ADDON;

if ($_GET["action"] == 'setcodecompta')
{
  if (dolibarr_set_const($db, "CODECOMPTA_ADDON",$_GET["value"]))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $codecompta_addon_var = $_GET["value"];
      Header("Location: societe.php");
    }
}



llxHeader();

/*
 *
 *
 *
 */

print_titre("Configuration du module Sociétés");

print "<br>";

print_titre("Module de vérification des codes client");

print "<table class=\"noborder\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td>Nom</td>\n";
print "  <td>Info</td>\n";
print "  <td align=\"center\">Activé</td>\n";
print "  <td>&nbsp;</td>\n";
print "</tr>\n";

clearstatcache();

$dir = "../includes/modules/societe/";
$handle = opendir($dir);
if ($handle)
{
  while (($file = readdir($handle))!==false)
    {
      if (substr($file, 0, 15) == 'mod_codeclient_' && substr($file, -3) == 'php')
	{
	  $file = substr($file, 0, strlen($file)-4);

	  require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$file.".php");

	  $modCodeClient = new $file;
	  if($pair == "pair")
	    $pair = "impair";
	  else
	    $pair = "pair";
	  print "<tr class=\"$pair\">\n  <td width=\"140\">".$modCodeClient->nom."</td>\n  <td>";
	  print $modCodeClient->info();
	  print "</td>\n";
	  
	  if ($codeclient_addon_var == "$file")
	    {
	      print "  <td align=\"center\">\n";
	      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	      print "</td>\n  <td>&nbsp;</td>\n";
	    }
	  else
	    {

	      print '<td>&nbsp;</td>';
	      print '<td align="center"><a href="societe.php?action=setcodeclient&amp;value='.$file.'">activer</a></td>';
	    }
	  
	  print '</tr>';
	}
    }
  closedir($handle);
}
print '</table>';

print "<br>";

print_titre("Module de gestion des compta");

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print '<tr class="liste_titre">';
print '<td>Nom</td>';
print '<td>Info</td>';
print '<td align="center">Activé</td>';
print '<td>&nbsp;</td>';
print "</tr>\n";

clearstatcache();

$dir = "../includes/modules/societe/";
$handle = opendir($dir);
if ($handle)
{
  while (($file = readdir($handle))!==false)
    {
      if (substr($file, 0, 15) == 'mod_codecompta_' && substr($file, -3) == 'php')
	{
	  $file = substr($file, 0, strlen($file)-4);

	  require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$file.".php");

	  $modCodeCompta = new $file;

	  print '<tr class="pair"><td width="140">'.$modCodeCompta->nom."</td><td>\n";
	  print $modCodeCompta->info();
	  print '</td>';
	  
	  if ($codecompta_addon_var == "$file")
	    {
	      print '<td align="center">';
	      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	      print '</td><td>&nbsp;</td>';
	    }
	  else
	    {
	      print '<td>&nbsp;</td>';
	      print '<td align="center"><a href="societe.php?action=setcodecompta&amp;value='.$file.'">activer</a></td>';

	    }
	  
	  print "</tr>\n";
	}
    }
  closedir($handle);
}
print "</table>\n";



$db->close();

llxFooter();
?>
