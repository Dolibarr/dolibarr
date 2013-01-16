<?php
/* Copyright (C) 2003-2008 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/modules/expedition/methode_expedition_trans.modules.php
 *	\ingroup    expedition
 */
include_once 'methode_expedition.modules.php';


/**
 * Class to manage shipment Trans
 */
class methode_expedition_trans extends ModeleShippingMethod
{

    /**
     * Constructor
     *
     * @param	DoliDB		$db		Database handler
     */
	function __construct($db=0)
	{
		global $langs;

		$this->db = $db;
		$this->id = 2; // Ne pas changer cette valeur
		$this->code = "TRANS";
		$this->name = "Transporteur";
		$this->description = $langs->trans("GenericTransport");
	}

	/**
	 * Return URL of provider
	 *
	 * @param	string	$tracking_number	Tracking number
	 * @return	string						URL for tracking
	 */
	function provider_url_status($tracking_number)
	{
		return '';
	}
}

?>
