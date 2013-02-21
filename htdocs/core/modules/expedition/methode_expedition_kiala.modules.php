<?PHP
/* 
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

/*
 *  \file       htdocs/core/modules/expedition/methode_expedition_kiala.modules.php
 *  \ingroup    expedition
 */

include_once "methode_expedition.modules.php";


Class methode_expedition_kiala extends ModeleShippingMethod

{
    /**
     * Constructor
     *
     * @param   DoliDB      $db     Database handler
     */
    function methode_expedition_kiala($db=0)
    { 
        $this->db = $db;
        $this->id = 6;              // Do not change this value
        $this->code = "COLKIALA";   // Do not change this value
        $this->name = "KIALA";
        $this->description = "KIALA";
    }

    /**
     * Return URL of provider
     *
     * @param   string  $tracking_number    Tracking number
     * @return  string                      URL for tracking
     */
    function provider_url_status($tracking_number)
    {
        return sprintf("http://www.kiala.fr/tnt/delivery/%s",$tracking_number);
    }
}

?>
