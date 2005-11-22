<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class TelephonieClient extends Societe {
  var $db;
  var $id;

  /**
   * Créateur
   *
   */
  function TelephonieClient($DB, $id=0)
  {
    $this->db = $DB;
    $this->id = $id;

    return 0;
  }
  /**
   *
   *
   *
   */
  function count_comment()
  {
    $num_comments = 0;

    $sql = "SELECT count(rowid)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_commentaire";
    $sql .= " WHERE fk_soc = ".$this->id;
    $resql = $this->db->query($sql);
    
    if ($resql)
      {
	$row = $this->db->fetch_row($resql);
	$num_comments = $row[0];
	$this->db->free($resql);
      }

    return $num_comments;
  }
}
?>
