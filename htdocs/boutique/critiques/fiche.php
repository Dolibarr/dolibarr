<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * 	    \file       htdocs/boutique/critiques/fiche.php
 * 		\ingroup    boutique
 * 		\brief      Page fiche critique OS Commerce
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/boutique/osc_master.inc.php';

$id=$_GET["id"];



llxHeader();

if ($id)
{

  $critique = new Critique($dbosc);
  $result = $critique->fetch($id);

  if ( $result )
    {

      print '<div class="titre">Fiche Critique</div><br>';

      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
      print "<tr>";
      print '<td width="20%">Produit</td><td width="80%">'.$critique->product_name.'</td></tr>';

      print '<tr><td width="20%">Texte</td><td width="80%">'.nl2br($critique->text).'</td></tr>';
      print "</table>";



    }
  else
    {
      print "Fetch failed";
    }

}

/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print '<br><table width="100%" border="1" cellspacing="0" cellpadding="3">';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '</table><br>';



$dbosc->close();

llxFooter();

?>
