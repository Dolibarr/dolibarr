<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file htdocs/admin/fichinter.php
		\ingroup    fichinter
		\brief      Page d'administration/configuration du module FicheInter
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

llxHeader();

if (!$user->admin)
  accessforbidden();


// positionne la variable pour le test d'affichage de l'icone

$ficheinter_addon_var_pdf = FICHEINTER_ADDON_PDF;


if ($action == 'setpdf')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'FICHEINTER_ADDON_PDF' ;";
	$db->query($sql);$sql ='';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('FICHEINTER_ADDON_PDF','".$value."',0) ; ";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $ficheinter_addon_var_pdf = $value;
    }
}

$dir = "../includes/modules/fichinter/";

/*
 *
 */

print_titre("Configuration du module Fiches d'interventions");

print "<br>";

print_titre("Modèles de fiche d'intervention pdf");

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Info</td>';
print '<td align="center">Activé</td>';
print '<td>&nbsp;</td>';
print "</TR>\n";

clearstatcache();

$handle=opendir($dir);

while (($file = readdir($handle))!==false)
{
  if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,4) == 'pdf_')
    {
      $name = substr($file, 4, strlen($file) -16);
      $classname = substr($file, 0, strlen($file) -12);

      print '<tr class="pair"><td>';
      echo "$name";
      print "</td><td>\n";
      require_once($dir.$file);
      $obj = new $classname();
      
      print $obj->description;

      print '</td><td align="center">';

      if ($ficheinter_addon_var_pdf == "$name")
	{
	  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	}
      else
	{
	  print "&nbsp;";
	}

      print "</td><td>\n";

      print '<a href="fichinter.php?action=setpdf&value='.$name.'">activer</a>';

      print '</td></tr>';
    }
}
closedir($handle);

print '</table>';



$db->close();
llxFooter();
?>
