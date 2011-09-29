<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/expedition/class/expeditionstats.class.php
 *  \ingroup    expedition
 *  \brief      Fichier des classes expedition
 */

/**
 *	\class      ExpeditionStats
 *	\brief      Class to manage shipment statistics
 */
class ExpeditionStats
{
    var $db;

    /**
     * Constructor
     *
     * @param		DoliDB		$DB      Database handler
     */
    function ExpeditionStats($DB)
    {
        $this->db = $DB;
    }

    /**
     * Renvoie le nombre de expedition par annee
     *
     * @return	void
     */
    function getNbExpeditionByYear()
    {
        global $conf;

        $result = array();
        $sql = "SELECT count(*), date_format(date_expedition,'%Y') as dm";
        $sql.= " FROM ".MAIN_DB_PREFIX."expedition";
        $sql.= " WHERE fk_statut > 0";
        $sql.= " AND entity = ".$conf->entity;
        $sql.= " GROUP BY dm DESC";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);
                $result[$i] = $row;

                $i++;
            }
            $this->db->free($resql);
        }
        return $result;
    }

    /**
     * Renvoie le nombre de expedition par mois pour une annee donnee
     *
     * @param	int		$year		Year
     * @return	int
     */
    function getNbExpeditionByMonth($year)
    {
        global $conf;

        $result = array();
        $sql = "SELECT count(*), date_format(date_expedition,'%m') as dm";
        $sql.= " FROM ".MAIN_DB_PREFIX."expedition";
        $sql.= " WHERE date_format(date_expedition,'%Y') = '".$year."'";
        $sql.= " AND fk_statut > 0";
        $sql.= " AND entity = ".$conf->entity;
        $sql.= " GROUP BY dm DESC";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);
                $j = $row[0] * 1;
                $result[$j] = $row[1];
                $i++;
            }
            $this->db->free($resql);
        }
        for ($i = 1 ; $i < 13 ; $i++)
        {
            $res[$i] = $result[$i] + 0;
        }

        $data = array();

        for ($i = 1 ; $i < 13 ; $i++)
        {
            $data[$i-1] = array(dol_print_date(dol_mktime(12,0,0,$i,1,$year),"%b"), $res[$i]);
        }

        return $data;
    }


    /**
     *
     * @param 	int		$year	Year
     * @return	int
     */
    function getNbExpeditionByMonthWithPrevYear($year)
    {
        $data1 = $this->getNbExpeditionByMonth($year);
        $data2 = $this->getNbExpeditionByMonth($year - 1);

        $data = array();

        for ($i = 1 ; $i < 13 ; $i++)
        {
            $data[$i-1] = array(dol_print_date(dol_mktime(12,0,0,$i,1,$year),"%b"),
            $data1[$i][1],
            $data2[$i][1]);
        }
        return $data;
    }

}

?>
