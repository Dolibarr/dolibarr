<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/lib/bookmark.lib.php
 *		\brief      List of functions for bookmark module
 *		\version    $Id: bookmark.lib.php,v 1.6 2011/07/31 23:25:41 eldy Exp $
 *
 *		Ensemble de fonctions de base de dolibarr sous forme d'include
 */

function showArrayOfBookmark($fac)
{
	$sql = "SELECT s.rowid as socid, s.nom, b.rowid as bid";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."bookmark as b";
	$sql .= " WHERE b.fk_soc = s.rowid AND b.fk_user = ".$user->id;
	$sql .= " ORDER BY lower(s.nom) ASC";

	$resql = $db->query($sql);

	if ( $resql )
	{
	  $num = $db->num_rows($resql);
	  $i = 0;
	  if ($num)
	    {
	      print '<table class="noborder" width="100%">';
	      print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Bookmarks")."</td></tr>\n";
	      $var = True;
	      while ($i < $num)
		{
		  $obj = $db->fetch_object($resql);
		  $var = !$var;
		  print "<tr $bc[$var]>";
		  print '<td><a href="fiche.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td>';
		  print '<td align="right"><a href="index.php?action=del_bookmark&amp;bid='.$obj->bid.'">'.img_delete().'</a></td>';
		  print '</tr>';
		  $i++;
		}
	      print '</table>';
	    }
	  $db->free($resql);
	}
	else
	{
	  dol_print_error($db);
	}
}

?>