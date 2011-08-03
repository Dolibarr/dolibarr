<?php
/* Copyright (C) 2010 Laurent Destailleur         <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/lib/google.class.php
 *	\brief			A set of functions for using Google APIs
 *	\version		$Id: google.class.php,v 1.3 2011/07/31 23:25:23 eldy Exp $
 */
class GoogleAPI
{
	var $db;
	var $error;

	var $key;

	/**
	 * Constructor
	 *
	 * @param 	db			Database handler
	 * @param	string		Google key
	 * @return GoogleAPI
	 */
	function GoogleAPI($DB,$key)
	{
		$this->db=$DB;
		$this->key=$key;
	}


	/**
	 *  \brief      Return geo coordinates of an address
	 *  \param      address		Address
	 * 							Example: 68 Grande rue Charles de Gaulle,+94130,+Nogent sur Marne,+France
	 *							Example: 188, rue de Fontenay,+94300,+Vincennes,+France
	 *	\return		string		Coordinates
	 */
	function getGeoCoordinatesOfAddress($address)
	{
		global $conf;


		$i=0;

		// Desired address
		$urladdress = "http://maps.google.com/maps/geo?q=".urlencode($address)."&output=xml&key=".$this->key;

		// Retrieve the URL contents
		$page = file_get_contents($urladdress);

		$code = strstr($page, '<coordinates>');
		$code = strstr($code, '>');
		$val=strpos($code, "<");
		$code = substr($code, 1, $val-1);
		//print $code;
		//print "<br>";
		$latitude = substr($code, 0, strpos($code, ","));
		$longitude = substr($code, strpos($code, ",")+1, dol_strlen(strpos($code, ","))-3);

		// Output the coordinates
		//echo "Longitude: $longitude ',' Latitude: $latitude";

		$i++;
	}
}
