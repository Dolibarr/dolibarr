<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier			  <benoit.mortier@opensides.be>
 * Copyright (C) 2004 Eric Seigne <eric.seigne@ryxeo.com>
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

/*!	\file htdocs/admin/expedition.php
		\ingroup    expedition
		\brief      Page d'administration/configuration du module Expedition
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


if ($action == 'nbprod' && $user->admin)
{
  $sql ="delete from ".MAIN_DB_PREFIX."const where name = 'EXPEDITION_NEW_FORM_NB_PRODUCT';";
	$db->query($sql);
	$sql ='';
	$sql = "insert into ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('EXPEDITION_NEW_FORM_NB_PRODUCT','".$value."',0);";
	
  //$sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'EXPEDITION_NEW_FORM_NB_PRODUCT', value='".$value."', visible=0";

  if ($db->query($sql))
    {
      Header("Location: expedition.php");
    }
}

llxHeader();


if ($_GET["action"] == 'set')
{
  $file = DOL_DOCUMENT_ROOT . '/includes/modules/expedition/methode_expedition_'.$_GET["value"].'.modules.php';

  $classname = 'methode_expedition_'.$_GET["value"];
  require_once($file);
  
  $obj = new $classname();
  $sql = "delete from ".MAIN_DB_PREFIX."expedition_methode where rowid = ".$obj->id.";";
  $db->query($sql);
  $sql='';
  $sql = "insert into ".MAIN_DB_PREFIX."expedition_methode (rowid,code,libelle,description,status) VALUES (".$obj->id.",'".$obj->code."','".$obj->name."','".addslashes($obj->description)."',".$_GET["statut"].");";
  
  //$sql = "REPLACE INTO ".MAIN_DB_PREFIX."expedition_methode (rowid,code,libelle, description, statut)";
  //$sql .= " VALUES (".$obj->id.",'".$obj->code."','".$obj->name."','".addslashes($obj->description)."',".$_GET["statut"].")";
  
  if ($db->query($sql))
    {
      
    }
}

// positionne la variable pour le test d'affichage de l'icone

$expedition_addon_var_pdf = EXPEDITION_ADDON_PDF;

if ($_GET["action"] == 'setpdf')
{
	$sql = "delete from ".MAIN_DB_PREFIX."const where name = 'EXPEDITION_ADDON_PDF';";
	$db->query($sql);
	$sql='';
	$sql = "insert into ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('EXPEDITION_ADDON_PDF','".$_GET["value"]."',0)";
	
  //$sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'EXPEDITION_ADDON_PDF', value='".$_GET["value"]."', visible=0";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $expedition_addon_var_pdf = $value;
    }
  /*
   * On la set active
   */
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."propal_model_pdf (nom) VALUES ('".$value."')";

  if ($db->query($sql))
    {

    }
}

$expedition_default = EXPEDITION_DEFAULT;

if ($_GET["action"] == 'setdef')
{
  $sql = "delete from ".MAIN_DB_PREFIX."const where name = 'EXPEDITION_ADDON';";
	$db->query($sql);
	$sql='';
	$sql = "insert into ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('EXPEDITION_ADDON','".$value."',0)";
	
  //$sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'EXPEDITION_ADDON', value='".$value."', visible=0";
  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $expedition_default = $_GET["value"];
    }
}

/*
 *
 *
 *
 */

$def = array();

$sql = "SELECT nom FROM ".MAIN_DB_PREFIX."propal_model_pdf";
if ($db->query($sql))
{
  $i = 0;
  while ($i < $db->num_rows())
    {
      $array = $db->fetch_array($i);
      array_push($def, $array[0]);
      $i++;
    }
}
else
{
  print $db->error();
}

$dir = DOL_DOCUMENT_ROOT."/includes/modules/expedition/";

/*
 * Méthode de livraison
 */

print_titre("Configuration du module Expedition/Livraisons");

print "<br>";

print_titre("Méthode de livraison");

print '<table class="noborder" cellpadding="3" cellspacing="0">';
print '<tr class="liste_titre">';
print '<td>Nom</td><td>Info</td>';
print '<td align="center" colspan="2">Actif</td>';
print '<td align="center" colspan="2">Défaut</td>';
print "</tr>\n";

if(is_dir($dir)) {
  $handle=opendir($dir);

  while (($file = readdir($handle))!==false)
    {
      if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,19) == 'methode_expedition_')
	{
	  $name = substr($file, 19, strlen($file) - 31);
	  $classname = substr($file, 0, strlen($file) - 12);
	  
	  require_once($dir.$file);
	  
	  $obj = new $classname();
	  
	  print '<tr class="pair"><td>';
	  echo $obj->name;
	  print "</td><td>\n";
	  
	  print $obj->description;
	  
	  print '</td><td align="center">';
	  
	  if (in_array($name, $def))
	    {
	      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	      print "</td><td>\n";
	      print '<a href="expedition.php?action=set&amp;statut=0&amp;value='.$name.'">désactiver</a>';
	    }
	  else
	    {
	      print "&nbsp;";
	      print "</td><td>\n";
	      print '<a href="expedition.php?action=set&amp;statut=1&amp;value='.$name.'">activer</a>';
	    }
	  
	  print '</td><td align="center">';
	  
	  if ($expedition_default == "$name")
	    {
	      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	    }
	  else
	    {
	      print "&nbsp;";
	    }
	  
	  print "</td><td>\n";
	  
	  print '<a href="expedition.php?action=setdef&amp;value='.$name.'">activer</a>';
	  
	  print '</td></tr>';
	}
    }
  closedir($handle);
}
else
{
  print "<tr><td><b>ERROR</b>: $dir is not a directory !</td></tr>\n";
}
print '</table>';

print '<br>';

/*
 *
 */

print_titre("Modèles bordereau de livraison");

print '<table class="noborder" cellpadding="3" cellspacing="0">';
print '<tr class="liste_titre">';
print '<td>Nom</td><td>Info</td>';
print '<td align="center" colspan="2">Actif</td>';
print '<td align="center" colspan="2">Défaut</td>';
print "</tr>\n";

clearstatcache();

if(is_dir($dir)) {
  $handle=opendir($dir);

  while (($file = readdir($handle))!==false)
    {
      if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,15) == 'pdf_expedition_')
	{
	  $name = substr($file, 15, strlen($file) - 27);
	  $classname = substr($file, 0, strlen($file) - 12);
	  
	  print '<tr class="pair"><td>';
	  echo "$name";
	  print "</td><td>\n";
	  require_once($dir.$file);
	  $obj = new $classname();
	  
	  print $obj->description;
	  
	  print '</td><td align="center">';
	  
	  if (in_array($name, $def))
	    {
	      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	      print "</td><td>\n";
	      print '<a href="expedition.php?action=del&amp;value='.$name.'">désactiver</a>';
	    }
	  else
	    {
	      print "&nbsp;";
	      print "</td><td>\n";
	      print '<a href="expedition.php?action=set&amp;value='.$name.'">activer</a>';
	    }
	  
	  print '</td><td align="center">';
	  
	  if ($expedition_addon_var_pdf == "$name")
	    {
	      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	    }
	  else
	    {
	      print "&nbsp;";
	    }
	  
	  print "</td><td>\n";
	  
	  print '<a href="expedition.php?action=setpdf&amp;value='.$name.'">activer</a>';
	  
	  print '</td></tr>';
	}
    }
  closedir($handle);
}
else
{
  print "<tr><td><b>ERROR</b>: $dir is not a directory !</td></tr>\n";
}
print '</table>';

/*
 *
 *
 */

$db->close();

llxFooter();
?>
