<?php
/* Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       htdocs/docs/index.php
   \ingroup    document
   \brief      Page d'accueil module document
   \version    $Revision$
*/

require("./pre.inc.php");

/*
 * 	Affichage page configuration module societe
 *
 */

if ($_GET["id"] > 0)
{
  require_once(DOL_DOCUMENT_ROOT.'/docs/document.class.php');
  $doc = new Document($db);
  $doc->Generate($_GET["id"]);
}

llxHeader();


print_titre($langs->trans("Documents"));

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td>'.$langs->trans("Description").'</td>';
print "</tr>\n";

$sql = "SELECT dg.rowid,dg.name, dg.classfile, dg.class";
$sql.= " FROM ".MAIN_DB_PREFIX."document_generator as dg";
$sql.= " ORDER BY dg.name ASC;";

$resql = $db->query($sql);
if ($resql)
{
  $var=True;
  while ($obj = $db->fetch_object($resql) )
    {
      
      $var=!$var;
      
      print "<tr $bc[$var]>";
      print '<td><a href="generate.php?id='.$obj->rowid.'">'.stripslashes($obj->name).'</a></td>';
      print '<td>&nbsp;</td>';
      print "</tr>\n";
    }

    $db->free($resql);
}
else
{
  dolibarr_print_error($db);
}


print '</table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
