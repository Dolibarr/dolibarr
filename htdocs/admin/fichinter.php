<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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

/**
    	\file       htdocs/admin/fichinter.php
		\ingroup    fichinter
		\brief      Page d'administration/configuration du module FicheInter
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("interventions");

llxHeader();

if (!$user->admin)
  accessforbidden();


$ficheinter_addon_var_pdf = FICHEINTER_ADDON_PDF;


if ($_GET["action"] == 'setpdf')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'FICHEINTER_ADDON_PDF' ;";
  $db->query($sql);$sql ='';
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('FICHEINTER_ADDON_PDF','".$_GET["value"]."',0) ; ";
  
  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $ficheinter_addon_var_pdf = $_GET["value"];
    }
}

$dir = "../includes/modules/fichinter/";


print_titre($langs->trans("InterventionsSetup"));

print "<br>";

print_titre($langs->trans("TemplatePDFInterventions"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center">'.$langs->trans("Activated").'</td>';
print '<td>&nbsp;</td>';
print "</tr>\n";

clearstatcache();

$var=true;

$handle=opendir($dir);
while (($file = readdir($handle))!==false)
{
  if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,4) == 'pdf_')
    {
      $name = substr($file, 4, strlen($file) -16);
      $classname = substr($file, 0, strlen($file) -12);

      $var=!$var;

      print '<tr '.$bc[$var].'><td>';
      echo "$name";
      print "</td><td>\n";
      require_once($dir.$file);
      $obj = new $classname();
      
      print $obj->description;

      print '</td><td align="center">';

      if ($ficheinter_addon_var_pdf == "$name")
	{
	  print img_tick();
	}
      else
	{
	  print "&nbsp;";
	}

      print "</td><td>\n";

      print '<a href="fichinter.php?action=setpdf&value='.$name.'">'.$langs->trans("Activate").'</a>';

      print '</td></tr>';
    }
}
closedir($handle);

print '</table>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
