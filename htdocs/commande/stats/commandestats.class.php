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

class CommandeStats 
{
  var $db ;

  function CommandeStats($DB, $socidp)
    {
      $this->db = $DB;
      $this->socidp = $socidp;
    }

  function getNbCommandeByMonthWithPrevYear($year)
  {
    $data1 = $this->getNbCommandeByMonth($year - 1);
    $data2 = $this->getNbCommandeByMonth($year);

    $data = array();

    for ($i = 0 ; $i < 12 ; $i++)
      {
	$data[$i] = array($data1[$i][0], 
			  $data1[$i][1],
			  $data2[$i][1]);
      }
    return $data;
  }
  /**
   * Renvoie le nombre de commande par mois pour une année donnée
   *
   */
  function getNbCommandeByMonth($year)
  {
    $result = array();
    $sql = "SELECT date_format(date_commande,'%m') as dm, count(*)  FROM ".MAIN_DB_PREFIX."commande";
    $sql .= " WHERE date_format(date_commande,'%Y') = $year AND fk_statut > 0";
    if ($this->socidp)
      {
	$sql .= " AND fk_soc = ".$this->socidp;
      }
    //pas un group by mais un ORDER// $sql .= " GROUP BY dm DESC";
		$sql .= " ORDER BY dm DESC";
    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($i);
	    $j = $row[0] * 1;
	    $result[$j] = $row[1];
	    $i++;
	  }
	$this->db->free();
      }
    
    for ($i = 1 ; $i < 13 ; $i++)
      {
	$res[$i] = $result[$i] + 0;
      }

    $data = array();
    
    for ($i = 1 ; $i < 13 ; $i++)
      {
	$data[$i-1] = array(strftime("%b",mktime(12,12,12,$i,1,$year)), $res[$i]);
      }
    
    return $data;
  }

  /**
   * Renvoie le nombre de commande par année
   *
   */
  function getNbByYear()
  {
    $result = array();
    $sql = "SELECT date_format(date_commande,'%Y') as dm, count(*), sum(total_ht)  FROM ".MAIN_DB_PREFIX."commande WHERE fk_statut > 0";
    if ($this->socidp)
      {
	$sql .= " AND fk_soc = ".$this->socidp;
      }
    $sql .= " GROUP BY dm DESC";
    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($i);
	    $result[$row[0]] = array($row[1], $row[2]);

	    $i++;
	  }
	$this->db->free();
      }
    return $result;
  }
  /**
   * Renvoie le nombre de commande par mois pour une année donnée
   *
   */
  function getCommandeAmountByMonth($year)
  {
    $result = array();
    $sql = "SELECT date_format(date_commande,'%m') as dm, sum(total_ht)  FROM ".MAIN_DB_PREFIX."commande";
    $sql .= " WHERE date_format(date_commande,'%Y') = $year AND fk_statut > 0";
    if ($this->socidp)
      {
	$sql .= " AND fk_soc = ".$this->socidp;
      }
    $sql .= " GROUP BY dm DESC";

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($i);
	    $j = $row[0] * 1;
	    $result[$j] = $row[1];
	    $i++;
	  }
	$this->db->free();
      }
    
    for ($i = 1 ; $i < 13 ; $i++)
      {
	$res[$i] = $result[$i] + 0;
      }

    return $res;
  }
  /**
   * Renvoie le nombre de commande par mois pour une année donnée
   *
   */
  function getCommandeAverageByMonth($year)
  {
    $result = array();
    $sql = "SELECT date_format(date_commande,'%m') as dm, avg(total_ht)  FROM ".MAIN_DB_PREFIX."commande";
    $sql .= " WHERE date_format(date_commande,'%Y') = $year AND fk_statut > 0";
    if ($this->socidp)
      {
	$sql .= " AND fk_soc = ".$this->socidp;
      }
    $sql .= " GROUP BY dm DESC";

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($i);
	    $j = $row[0] * 1;
	    $result[$j] = $row[1];
	    $i++;
	  }
	$this->db->free();
      }
    
    for ($i = 1 ; $i < 13 ; $i++)
      {
	$res[$i] = $result[$i] + 0;
      }

    return $res;
  }
}

?>
