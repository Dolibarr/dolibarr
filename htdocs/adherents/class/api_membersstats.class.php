<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2025       Frédéric France     <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherentstats.class.php';

/**
 * API class for members stats
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Membersstats extends DolibarrApi
{
	/**
	 * @var string[]   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = [];

	/**
	 * @var AdherentStats
	 */
	public $memberstats;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->memberstats = new AdherentStats($this->db, DolibarrApiAccess::$user->socid, DolibarrApiAccess::$user->id);
	}

	/**
	 * Return the number of members by month for a given year
	 *
	 * Get an array of number of members by month for a given year
	 *
	 * @param	int		$year       Year
	 * @param	int		$format		0=Label of abscissa is a translated text
	 *                              1=Label of abscissa is month number
	 *                              2=Label of abscissa is first letter of month
	 * @return array 			Array of statistics for last modified members
	 * @phan-return array<int<0,11>,array{0:int<1,12>,1:int}>	Array of nb each month
	 * @phpstan-return array<int<0,11>,array{0:int<1,12>,1:int}>	Array of nb each month
	 *
	 * @throws	RestException	403		Access denied
	 */
	public function getNbByMonth($year, $format = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		}

		return $this->memberstats->getNbByMonth($year, $format);
	}

	/**
	 * Last Modified Members
	 *
	 * Get an array of statistics for last modified members
	 *
	 * @param int    $max  		Max numbers of members
	 * @return array 			Array of statistics for last modified members
	 * @phan-return array<int,array{id:int,ref:string,firstname:string,lastname:string,company:string,fk_soc:?int,datec:int|'',datem:int|'',status:int,date_end_subscription:int|'',photo:null|string,email:string,gender:string,morphy:string,typeid:int,need_subscription:0|1|null,subscription:'0'|'1'|null,label:string}>
	 * @phpstan-return array<int,array{id:int,ref:string,firstname:string,lastname:string,company:string,fk_soc:?int,datec:int|'',datem:int|'',status:int,date_end_subscription:int|'',photo:null|string,email:string,gender:string,morphy:string,typeid:int,need_subscription:0|1|null,subscription:'0'|'1'|null,label:string}>
	 *
	 * @throws	RestException	403		Access denied
	 */
	public function getLastModifiedMembers($max)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		}

		return $this->memberstats->getLastModifiedMembers($max);
	}
}
