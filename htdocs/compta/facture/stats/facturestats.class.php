<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/compta/facture/stats/facturestats.class.php
        \ingroup    factures
        \brief      Fichier de la classe de gestion des stats des factures
        \version    $Revision$
*/

include_once DOL_DOCUMENT_ROOT . "/stats.class.php";


/**
        \class      FactureStats
        \brief      Classe permettant la gestion des stats des factures
*/

class FactureStats extends Stats
{
  var $db ;

  function FactureStats($DB, $socid=0)
    {
      $this->db = $DB;
      $this->socid = $socid;
    }


  /**
   * Renvoie le nombre de facture par mois pour une année donnée
   *
   */
  function getNbByMonth($year)
  {
    $sql = "SELECT date_format(datef,'%m') as dm, count(*)  FROM ".MAIN_DB_PREFIX."facture";
    $sql .= " WHERE date_format(datef,'%Y') = $year AND fk_statut > 0";
    if ($this->socid)
      {
	$sql .= " AND fk_soc = ".$this->socid;
      }
    $sql .= " GROUP BY dm DESC";
    
    return $this->_getNbByMonth($year, $sql);
  }


  /**
   * Renvoie le nombre de facture par année
   *
   */
  function getNbByYear()
  {
    $sql = "SELECT date_format(datef,'%Y') as dm, count(*) FROM ".MAIN_DB_PREFIX."facture GROUP BY dm DESC WHERE fk_statut > 0";

    return $this->_getNbByYear($sql);
  }
  
  /**
   * Renvoie le nombre de facture par mois pour une année donnée
   *
   */
  function getAmountByMonth($year)
  {
    $sql = "SELECT date_format(datef,'%m') as dm, sum(total)  FROM ".MAIN_DB_PREFIX."facture";
    $sql .= " WHERE date_format(datef,'%Y') = $year AND fk_statut > 0";
    if ($this->socid)
      {
	$sql .= " AND fk_soc = ".$this->socid;
      }
    $sql .= " GROUP BY dm DESC";

    return $this->_getAmountByMonth($year, $sql);
  }
  /**
   * 
   *
   */
  function getAverageByMonth($year)
  {
    $sql = "SELECT date_format(datef,'%m') as dm, avg(total) FROM ".MAIN_DB_PREFIX."facture";
    $sql .= " WHERE date_format(datef,'%Y') = $year AND fk_statut > 0";
    if ($this->socid)
      {
	$sql .= " AND fk_soc = ".$this->socid;
      }
    $sql .= " GROUP BY dm DESC";

    return $this->_getAverageByMonth($year, $sql);
  }
}

?>
