<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
 *
 */

/*!	\file htdocs/boxes.php
		\brief  Fichier de la classe boxes
		\author Rodolphe Qiedeville
		\author	Laurent Destailleur
		\version $Revision$
*/



/*! \class infoBox
		\brief      Classe permettant la gestion des boxes sur une page
        \remarks    Cette classe est utilisé par les fichiers includes/boxes/box_xxx.php
                    qui sont les modules de boites
*/

class infoBox 
{

  /*!
   *    \brief      Constructeur de la classe
   *    \param      $head       tableau des entetes de colonnes
   *    \param      $contents   tableau des lignes
   */
  function infoBox($head, $contents)
  {
    $var = true;
    $bcx[0] = 'class="box_pair"';
    $bcx[1] = 'class="box_impair"';
    $nbcol=sizeof($contents[0]);

    print '<table width="100%" cellpadding="2" cellspacing="0" class="noborder">';

    print '<tr class="box_titre"><td';
    if ($nbcol > 0) { print ' colspan="'.$nbcol.'"'; }
    print '>'.$head[0]['text']."</td></tr>";

    for ($i=0, $n=sizeof($contents); $i<$n; $i++)
      {
	$var=!$var;
	print '<tr valign="top" '.$bcx[$var].'>';

	for ($j=0, $m=sizeof($contents[$i]); $j<$m; $j++)
	  {
	    print "<td";
	    if (strlen($contents[$i][$j]['align']) > 0)
	      {
		print ' align="'. $contents[$i][$j]['align'].'"';
	      }
	    if (strlen($contents[$i][$j]['width']) > 0)
	      {
		print ' width="'. $contents[$i][$j]['width'].'"';
	      }
	    print'>';

	    if (strlen($contents[$i][$j]['url']) > 0)
	      {
		print '<a href="'.$contents[$i][$j]['url'].'">';
		print $contents[$i][$j]['text'] . "</a></td>";
	      }
	    else
	      {
		print $contents[$i][$j]['text'] . "</td>";
	      }
	  }
	print '</tr>';
      }

    print "</table>";
  }
}
?>
