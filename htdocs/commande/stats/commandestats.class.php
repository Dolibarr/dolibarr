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

  function CommandeStats($DB, $socid)
    {
      $this->db = $DB;
      $this->socid = $socid;
    }

  /**
   *    \brief      Renvoie le nombre de commande par mois pour une année donnée
   *
   */
    function getNbByMonth($year)
    {
    	  global $conf;
    	  global $user;
    	  
        $sql = "SELECT date_format(c.date_commande,'%m') as dm, count(*) nb";
        $sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
        if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql .= " WHERE date_format(c.date_commande,'%Y') = $year AND c.fk_statut > 0";
        if (!$user->rights->societe->client->voir && !$this->socid) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($this->socid)
        {
            $sql .= " AND c.fk_soc = ".$this->socid;
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
  	global $conf;
    global $user;
  	
    $sql = "SELECT date_format(c.date_commande,'%Y') as dm, count(*), sum(c.total_ht)";
    $sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
    if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE c.fk_statut > 0";
    if (!$user->rights->societe->client->voir && !$this->socid) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($this->socid)
    {
	    $sql .= " AND c.fk_soc = ".$this->socid;
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
  	global $conf;
    global $user;
  	
    $sql = "SELECT date_format(c.date_commande,'%m') as dm, sum(c.total_ht)";
    $sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
    if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE date_format(c.date_commande,'%Y') = $year AND c.fk_statut > 0";
    if (!$user->rights->societe->client->voir && !$this->socid) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($this->socid)
    {
	    $sql .= " AND c.fk_soc = ".$this->socid;
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
  	global $conf;
    global $user;
  	
    $sql = "SELECT date_format(c.date_commande,'%m') as dm, avg(c.total_ht)";
    $sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
    if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE date_format(c.date_commande,'%Y') = $year AND c.fk_statut > 0";
    if (!$user->rights->societe->client->voir && !$this->socid) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($this->socid)
    {
	    $sql .= " AND c.fk_soc = ".$this->socid;
    }
    $sql .= " GROUP BY dm DESC";

    return $this->_getAverageByMonth($year, $sql);
  }
}

?>
