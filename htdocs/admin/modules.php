<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo   <jlb@j1b.org>
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
 */
require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();

//
// TODO mettre cette section dans la base de données
//

if ($_GET["action"] == 'set' && $user->admin)
{
  Activate($_GET["value"]);

  Header("Location: modules.php");
}

if ($_GET["action"] == 'reset' && $user->admin)
{
  UnActivate($_GET["value"]);

  Header("Location: modules.php");
}


function Activate($value)
{
  global $db, $modules;

  $modName = $value;

  // Activation du module
  if ($modName)
    {
      $file = $modName . ".class.php";
      include_once("../includes/modules/$file");
      $objMod = new $modName($db);
      $objMod->init();
    }

  // Activation des modules dont le module dépend
  for ($i = 0; $i < sizeof($objMod->depends); $i++)
    {
      Activate($objMod->depends[$i]);
    }

}

function UnActivate($value)
{
  global $db, $modules;

  $modName = $value;

  // Desactivation du module
  if ($modName)
    {
      $file = $modName . ".class.php";
      include_once("../includes/modules/$file");
      $objMod = new $modName($db);
      $objMod->remove();
    }

  // Desactivation des modules qui dependent de lui
  for ($i = 0; $i < sizeof($objMod->requiredby); $i++)
    {
      UnActivate($objMod->requiredby[$i]);
    }

  Header("Location: modules.php");
}


$db->close();

llxHeader();

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}

print_titre("Modules");

print '<br>';
print 'Les modules Dolibarr définissent les fonctionnalités disponibles dans l\'application.<br>';
print 'Certains modules nécessitent des droits qu\'il vous faudra affecter aux utilisateurs pour qu\'ils puissent accéder à ces fonctionnalités.<br>';

print '<br>';
print '<table class="noborder" cellpadding="3" cellspacing="0">';
print '<tr class="liste_titre">';
print '<td>Nom</td>';
print '<td>Info</td>';
print '<td align="center">Actif</td>';
print '<td align="center">Action</td>';
print '<td>&nbsp;</td>';
print "</tr>\n";


$dir = DOL_DOCUMENT_ROOT . "/includes/modules/";

$handle=opendir($dir);
$modules = array();
$i = 0;
$j = 0;
while (($file = readdir($handle))!==false)
{
  if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, strlen($file) - 10) == '.class.php')
    {
      $modName = substr($file, 0, strlen($file) - 10);

      if ($modName)
	{
	  include_once("../includes/modules/$file");
	  $objMod = new $modName($db);

	  if ($objMod->numero > 0)
	    {
	      $j = $objMod->numero;
	      $modules[$objMod->numero] = $modName;
	    }
	  else
	    {
	      $j = 1000 + $i;
	    }
	  $modules[$j] = $modName;
	  $orders[$i] = $j;
	  $j++;
	  $i++;
	}
    }
}

sort($orders);
$var=True;

foreach ($orders as $key => $value)
{
  $var=!$var;
	
  $modName = $modules[$orders[$key]];

  if ($modName)
    {
      $objMod = new $modName($db);
    }
  
  $const_name = $objMod->const_name;
  $const_value = $objMod->const_config;
  
  print "<tr $bc[$var]><td>";
  echo $objMod->name;
  print "</td><td>\n";
  print $objMod->description;
  print '</td><td align="center">';
  
  if ($const_value == 1)
    {
      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
    }
  else
    {
      print "&nbsp;";
    }
  
  print '</td><td align="center">';
  
  
  if ($const_value == 1)
    {
      print '<a href="modules.php?action=reset&value='.$modName.'">Désactiver</a></td>';
      
      
      if ($objMod->config_page_url)
	{
	  if (is_array($objMod->config_page_url)) {
		print '<td>';
		$i=0;
		foreach ($objMod->config_page_url as $page) {
	  		if ($i++) { print '<a href="'.$page.'">'.ucfirst($page).'</a>&nbsp;'; }
	  		else { print '<a href="'.$page.'">Configurer</a>&nbsp;'; }
	  	}
	  	print '</td>';
	  } else {
	  	print '<td><a href="'.$objMod->config_page_url.'">Configurer</a></td>';
	  }
	}
      else
	{
	  print "<td>&nbsp;</td>";
	}
      
    }
  else
    {
      print '<a href="modules.php?action=set&value='.$modName.'">Activer</a></td><td>&nbsp;</td>';
    }
  
  print '</tr>';
  
}
print "</table>";

llxFooter();
?>
