<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * 
 * $Id$
 * $Source$
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
 */

class Menu {
  var $liste;

  Function Menu() {
    $this->liste = array();

  }


  Function add($url, $titre) {

    $i = sizeof($this->liste);

    $this->liste[$i][0] = $url;
    $this->liste[$i][1] = $titre;

  }

  Function add_submenu($url, $titre) {

    $i = sizeof($this->liste) - 1;
    $j = sizeof($this->liste[$i]);

    $this->liste[$i][$j] = $url;
    $this->liste[$i][$j+1] = $titre;

  }


}
