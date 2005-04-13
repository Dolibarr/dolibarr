<?php
/* Copyright (C) 2005 Matthieu Valleton <mv@seeschloss.org>
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

require "./pre.inc.php";

if (!$user->rights->categorie->supprimer) accessforbidden();


llxHeader("","",$langs->trans("Categories"));

print_titre($langs->trans("CategoriesArea"));

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';


$cat = new Categorie ($db, $_REQUEST['id']);

if (!isset ($_REQUEST['valid']))
{
  print "<p>Veuillez valider la suppression de la catégorie ".$cat->label." : <a href='".$_SERVER['PHP_SELF']."?id=".$cat->id."&amp;valid=true'>supprimer la catégorie ".$cat->label."</a></p>\n";
}
else
{
  if ($cat->remove () < 0)
    {
      print "<p>Impossible de supprimer la catégorie ".$cat->label.".</p>";
    }
  else
    {
      print "<p>La catégorie ".$cat->label." a été supprimée.</p>";
    }
}

print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
