<?php
/* Copyright (C) 2011 Regis Houssin  <regis.houssin@capnetworks.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/modules/expedition/methode_expedition_lettremax.modules.php
 *	\ingroup    expedition
 */

include_once 'methode_expedition.modules.php';


/**
 * Class to manage shipment lettremax
 */
class methode_expedition_lettremax extends ModeleShippingMethod
{
    /**
     * Constructor
     *
     * @param	DoliDB		$db		Database handler
     */
	function __construct($db=0)
	{
		$this->db = $db;
		$this->id = 4; // Do not change this value
		$this->code = "LETTREMAX";  // Do not change this value
		$this->name = "Lettre max";
		$this->description = "Courrier suivi et lettre max";
	}

	/**
	 * Return URL of provider
	 *
	 * @param	string	$tracking_number	Tracking number
	 * @return	string						URL for tracking
	 */
	function provider_url_status($tracking_number)
	{
		return sprintf("http://www.csuivi.courrier.laposte.fr/default.asp?EZ_ACTION=rechercheRapide&numObjet=%s",$tracking_number);
	}
}

?>
