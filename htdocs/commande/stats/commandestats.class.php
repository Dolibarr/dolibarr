<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/commande/stats/commandestats.class.php
        \ingroup    commandes
        \brief      Fichier de la classe de gestion des stats des commandes
        \version    $Revision$
*/

include_once DOL_DOCUMENT_ROOT . "/stats.class.php";


/**
        \class      CommandeStats
        \brief      Classe permettant la gestion des stats des commandes
*/

class CommandeStats extends Stats
{
  var $db ;

  function CommandeStats($DB, $socidp)
    {
      $this->db = $DB;
      $this->socidp = $socidp;
    }

  /**
   *    \brief      Renvoie le nombre de commande par mois pour une année donnée
   *
   */
    function getNbByMonth($year)
    {
        $sql = "SELECT date_format(date_commande,'%m') as dm, count(*) nb FROM ".MAIN_DB_PREFIX."commande";
        $sql .= " WHERE date_format(date_commande,'%Y') = $year AND fk_statut > 0";
        if ($this->socidp)
        {
            $sql .= " AND fk_soc = ".$this->socidp;
        }
        $sql .= " GROUP BY dm";
        $sql .= " ORDER BY dm DESC";

        return $this->_getNbByMonth($year, $sql);
    }

  /**
   * Renvoie le nombre de commande par année
   *
   */
  function getNbByYear()
  {
    $sql = "SELECT date_format(date_commande,'%Y') as dm, count(*), sum(total_ht)  FROM ".MAIN_DB_PREFIX."commande WHERE fk_statut > 0";
    if ($this->socidp)
      {
	$sql .= " AND fk_soc = ".$this->socidp;
      }
    $sql .= " GROUP BY dm DESC";

    return $this->_getNbByYear($sql);
  }
  
  /**
   * Renvoie le nombre de commande par mois pour une année donnée
   *
   */
  function getAmountByMonth($year)
  {
    $sql = "SELECT date_format(date_commande,'%m') as dm, sum(total_ht)  FROM ".MAIN_DB_PREFIX."commande";
    $sql .= " WHERE date_format(date_commande,'%Y') = $year AND fk_statut > 0";
    if ($this->socidp)
      {
	$sql .= " AND fk_soc = ".$this->socidp;
      }
    $sql .= " GROUP BY dm DESC";

    return $this->_getAmountByMonth($year, $sql);
  }
  
  /**
   * Renvoie le nombre de commande par mois pour une année donnée
   *
   */
  function getAverageByMonth($year)
  {
    $sql = "SELECT date_format(date_commande,'%m') as dm, avg(total_ht)  FROM ".MAIN_DB_PREFIX."commande";
    $sql .= " WHERE date_format(date_commande,'%Y') = $year AND fk_statut > 0";
    if ($this->socidp)
      {
	$sql .= " AND fk_soc = ".$this->socidp;
      }
    $sql .= " GROUP BY dm DESC";

    return $this->_getAverageByMonth($year, $sql);
  }
}

?>
