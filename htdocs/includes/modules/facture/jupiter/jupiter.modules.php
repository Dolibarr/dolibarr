<?PHP
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */

function facture_get_num($objsoc=0)
{ 
  global $db;
  $date = strftime("%Y%m", time());

  $sql = "SELECT count(*) FROM llx_facture";
  $sql .= " WHERE facnumber like '".$date."%'";
  if ( $db->query($sql) ) 
    {
      $row = $db->fetch_row(0);
      
      $num = $row[0];
    }
  $num++;
  //  return  "FA" . $date . substr("000".$num, strlen("000".$num)-4,4);
  return  "F" . $date . $num;
}

?>
