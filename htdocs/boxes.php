<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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

class infoBox 
{

  function infoBox($head, $contents)
  {
    $var = true;
    $bcx[0] = 'class="box_pair"';
    $bcx[1] = 'class="box_impair"';

    print '<table cellpadding="2" cellspacing="0" border="0">';

    print '<tr class="box_titre"><td>'.$head[0]['text']."</td></tr>";

    for ($i=0, $n=sizeof($contents); $i<$n; $i++)
      {
	$var=!$var;
	if (strlen($contents[$i]['url']) > 0)
	  {
	    print '<tr '.$bcx[$var].'><td><a href="'.$contents[$i]['url'].'">';
	    print $contents[$i]['text'] . "</a></td></tr>";
	  }
	else
	  {
	    print "<tr $bcx[$var]><td>".$contents[$i]['text'] . "</td></tr>";
	  }
	    
      }

    print "</table>";

  }


}


?>
