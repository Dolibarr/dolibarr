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
 *
 * $Id$
 * $Source$
 *
 */

function get_ca_propal ($db, $year, $socidp)
{

  $sql = "SELECT sum(f.price - f.remise) as sum FROM llx_propal as f WHERE fk_statut in (1,2,4) AND date_format(f.datep, '%Y') = $year ";
  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }
  
  $result = $db->query($sql);

  if ($result)
    {
      return  $db->result (0, 0);
    }
  else
    {
      return 0;
    } 

}

function get_ca ($db, $year, $socidp)
{
  
  $sql = "SELECT sum(f.amount) as sum FROM llx_facture as f WHERE f.paye = 1 AND date_format(f.datef , '%Y') = $year ";
  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }
  
  $result = $db->query($sql);

  if ($result)
    {
      return  $db->result ( 0, 0);
    }
  else
    {
      return 0;
    }   
}
