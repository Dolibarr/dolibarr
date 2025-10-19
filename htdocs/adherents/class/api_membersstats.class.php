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
		$this->memberstats = new AdherentStats($this->db);
	}

	/**
	 * List subscriptions
	 *
	 * Get a list of subscriptions
	 *
	 * @param int    $max  		Max numbers of members
	 * @return array 			Array of statistics for lest modified members
	 * @phan-return array<int,array{id:int,ref:string,firstname:string,lastname:string,company:string,fk_soc:?int,datec:int|'',datem:int|'',status:int,date_end_subscription:int|'',photo:null|string,email:string,gender:string,morphy:string,typeid:int,need_subscription:0|1|null,subscription:'0'|'1'|null,label:string}>
	 * @phpstan-return array<int,array{id:int,ref:string,firstname:string,lastname:string,company:string,fk_soc:?int,datec:int|'',datem:int|'',status:int,date_end_subscription:int|'',photo:null|string,email:string,gender:string,morphy:string,typeid:int,need_subscription:0|1|null,subscription:'0'|'1'|null,label:string}>
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		No Subscription found
	 * @throws	RestException	503		Error when retrieving Subscription list
	 */
	public function getLastModifiedMembers($max)
	{
		global $conf;

		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		}

		return $this->memberstats->getLastModifiedMembers($max);
	}
}
