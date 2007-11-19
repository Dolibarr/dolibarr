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
 *
 */

/**
        \file       htdocs/comm/propal/stats/propalestats.class.php
        \ingroup    propales
        \brief      Fichier de la classe de gestion des stats des propales
        \version    $Revision$
*/

include_once DOL_DOCUMENT_ROOT . "/stats.class.php";


/**
        \class      PropaleStats
        \brief      Classe permettant la gestion des stats des propales
*/

class PropaleStats extends Stats
{
  var $db ;

  function PropaleStats($DB)
    {
      $this->db = $DB;
    }


  /**
   * Renvoie le nombre de proposition par mois pour une année donnée
   *
   */
  function getNbByMonth($year)
  {
    global $user;
    
    $sql = "SELECT date_format(p.datep,'%m') as dm, count(*)";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE date_format(p.datep,'%Y') = $year AND p.fk_statut > 0";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
    if($user->societe_id)
    {
      $sql .= " AND p.fk_soc = ".$user->societe_id;
    }
    $sql .= " GROUP BY dm DESC";
    
    return $this->_getNbByMonth($year, $sql);
  }

  /**
   * Renvoie le nombre de propale par année
   *
   */
  function getNbByYear()
  {
  	global $user;
  	
    $sql = "SELECT date_format(p.datep,'%Y') as dm, count(*)";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE p.fk_statut > 0";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
    if($user->societe_id)
    {
      $sql .= " AND p.fk_soc = ".$user->societe_id;
    }
    $sql .= " GROUP BY dm DESC";

    return $this->_getNbByYear($sql);
  }
  /**
   * Renvoie le nombre de propale par mois pour une année donnée
   *
   */
  function getAmountByMonth($year)
  {
  	global $user;
  	
    $sql = "SELECT date_format(p.datep,'%m') as dm, sum(p.total_ht)";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE date_format(p.datep,'%Y') = $year AND p.fk_statut > 0";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
    if($user->societe_id)
    {
      $sql .= " AND p.fk_soc = ".$user->societe_id;
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
  	global $user;
  	
    $sql = "SELECT date_format(p.datep,'%m') as dm, avg(p.total_ht)";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE date_format(p.datep,'%Y') = $year AND p.fk_statut > 0";
    if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
    if($user->societe_id)
    {
      $sql .= " AND p.fk_soc = ".$user->societe_id;
    }
    $sql .= " GROUP BY dm DESC";

    return $this->_getAverageByMonth($year, $sql);
  }
}

?>
