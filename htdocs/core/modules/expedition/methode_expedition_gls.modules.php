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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
/*
 *  \file       htdocs/core/modules/expedition/methode_expedition_gls.modules.php
 *  \ingroup    expedition
 */

include_once "methode_expedition.modules.php";

/**
 * Class to manage shipment GLS
 */
class methode_expedition_gls extends ModeleShippingMethod
{
    /**
     * Constructor
     *
     * @param   DoliDB      $db     Database handler
     */
    function __construct($db=0)
    { 
        $this->db = $db;
        $this->id = 7;          // Do not change this value
        $this->code = "GLS";    // Do not change this value
        $this->name = "GLS";
        $this->description = "General Logistics Systems";
    }

    /**
     * Return URL of provider
     *
     * @param   string  $tracking_number    Tracking number
     * @return  string                      URL for tracking
     */
    function provider_url_status($tracking_number)
    {
        return sprintf("http://www.gls-group.eu/276-I-PORTAL-WEB/content/GLS/FR01/FR/5004.htm?txtAction=71000&txtRefNo=%s",$tracking_number);
    }
}

?>
