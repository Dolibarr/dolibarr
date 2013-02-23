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
 *  \file       htdocs/core/modules/expedition/methode_expedition_ups.modules.php
 *  \ingroup    expedition
 */

include_once "methode_expedition.modules.php";


class methode_expedition_ups extends ModeleShippingMethod
{
    /**
     * Constructor
     *
     * @param   DoliDB      $db     Database handler
     */
    function methode_expedition_ups($db=0)
    { 
        $this->db = $db;
        $this->id = 5;          // Do not change this value
        $this->code = "UPS";    // Do not change this value
        $this->name = "UPS";
        $this->description = "United Parcel Service";
    }

    /**
     * Return URL of provider
     *
     * @param   string  $tracking_number    Tracking number
     * @return  string                      URL for tracking
     */
    function provider_url_status($tracking_number)
    {
        return sprintf("http://wwwapps.ups.com/etracking/tracking.cgi?InquiryNumber2=&InquiryNumber3=&tracknums_displayed=3&loc=fr_FR&TypeOfInquiryNumber=T&HTMLVersion=4.0&InquiryNumber22=&InquiryNumber32=&track=Track&Suivi.x=64&Suivi.y=7&Suivi=Valider&InquiryNumber1=%s",$tracking_number);
    }
}

?>
