<?php
/*
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file           htdocs/core/class/multicurrency.class.php
 *  \brief          A set of functions for using multicurrency
 */

/**
 * Class to manage multicurrency
 */
class multicurrency
{

     var $db;


    /**
     * Constructor
     *
     * @param   DoliDB      $db         database
     */
    function __construct($db)
    {
        $this->db=$db;
    }


    /**
     *  Return rate for converion
     *
     *  @param  string      $from      currency code input
     *  @param  string      $to        currency code output
     *  @return double      $converter rate
     */
    function converter($from, $to)
    {
        global $conf,$db;

        $now = dol_now();
        if ($from==$to)
        {
            return 1;
        } else {
            $sql = 'SELECT rate, valid FROM '.MAIN_DB_PREFIX.'c_currencies_rate';
            $sql.= ' WHERE cur_from="'.$db->escape($from).'" AND cur_to="'.$db->escape($to).'"';
            $resql = $db->query($sql);
            $row = $db->fetch_object($resql);
            if ($row)
            {
                if (($now-strtotime($row->valid))<86400) // 24h refresh
                {
                    $converter = $row->rate;
                } else {
                    $converter = $this->update_rate($from, $to);
                }
            } else {
                $converter = $this->create_rate($from, $to);
            }
        }
        return $converter;
    }

    /**
     *  Update rate for converion
     *
     *  @param	string		$from		currency code input
     *  @param	string		$to			currency code output
     *  @return	double					error or rate
     */
    function update_rate($from, $to)
    {
        global $conf, $db;
        // source for update
        // http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml
        // yahoo api : http://finance.yahoo.com/d/quotes.csv?e=.csv&f=sl1d1t1&s=USDINR=X

        $url = 'http://finance.yahoo.com/d/quotes.csv?e=.csv&f=sl1d1t1&s='.$from.$to.'=X';
        $fp = @fopen($url, "r");
        if ($fp == FALSE)
        { 
            // Cannot get data from Yahoo! Finance
            return -1;
        }

        $array = @fgetcsv($fp, 4096, ', ');
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'c_currencies_rate';
        $sql.= ' SET rate='.$array[1];
        $sql.= ', source="Yahoo Finance"';
        $sql.= ' WHERE cur_from="'.$db->escape($from).'" && cur_to="'.$db->escape($to).'"';
        $resql = $db->query($sql);
        return $array[1];
    }

    /**
     *  Create rate for converion
     *
     *  @param	string		$from		currency code input
     *  @param	string		$to			currency code output
     *  @return	double					error or rate
     */
    function create_rate($from, $to)
    {
        global $conf, $db;

        $url = 'http://finance.yahoo.com/d/quotes.csv?e=.csv&f=sl1d1t1&s='.$from.$to.'=X';
        $fp = @fopen($url, "r");
        if ($fp == FALSE)
        {
            // Cannot get data from Yahoo! Finance
            return -1;
        }

        $array = @fgetcsv($fp, 4096, ', ');
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'c_currencies_rate';
        $sql.= ' (cur_from, cur_to, rate, source)';
        $sql.= ' VALUES ("'.$db->escape($from).'","'.$db->escape($to).'","'.$array[1].'","Yahoo Finance")';
        $resql = $db->query($sql);
        return $array[1];
    }
}
