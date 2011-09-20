<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/**
 *      \file       htdocs/boutique/promotion/class/promotion.class.php
 *      \brief      File of class to manage discounts on online shop
 */

/**
 *      \class      Promotion
 *      \brief      Class to manage discounts on online shop
 */
class Promotion
{
	var $db;

	var $id;
	var $parent_id;
	var $oscid;
	var $ref;
	var $titre;
	var $description;
	var $price;
	var $status;

	/**
	 * 	Constructor
	 *
	 * 	@param		DoliDB	$DB		Database handler
	 */
	function Promotion($DB)
	{
		$this->db = $DB;
		$this->id = $id;
	}

	/**
	 *	Create promotion
	 *
	 *	@param	User	$user		Object user
	 *	@param	int		$pid		Pid
	 *	@param	int		$percent	Percent
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function create($user, $pid, $percent)
	{
		global $conf;

		$sql = "SELECT products_price ";
		$sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products as p";
		$sql .= " WHERE p.products_id = ".$pid;

		$result = $this->db->query($sql);
		if ( $result )
		{
			$result = $this->db->fetch_array($result);
			$this->price_init = $result["products_price"];
		}

		$newprice = $percent * $this->price_init;

		$date_exp = "2003-05-01";

		$sql = "INSERT INTO ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."specials ";
		$sql .= " (products_id, specials_new_products_price, specials_date_added, specials_last_modified, expires_date, date_status_change, status) ";
		$sql .= " VALUES ($pid, $newprice, '".$this->db->idate(mktime())."', NULL, '".$this->db->idate(mktime()+3600*24*365)."', NULL, 1)";

		if ($this->db->query($sql) )
		{
			$id = $this->db->last_insert_id(OSC_DB_NAME.".specials");

			return $id;
		}
		else
		{
			print $this->db->error() . ' in ' . $sql;
		}
	}

	/**
	 * 	Update
	 *
	 *	@param	int		$id			id
	 *	@param	User	$user		Object user
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function update($id, $user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."album ";
		$sql .= " SET title = '" . trim($this->titre) ."'";
		$sql .= ",description = '" . trim($this->description) ."'";

		$sql .= " WHERE rowid = " . $id;

		if ( $this->db->query($sql) ) {
			return 1;
		} else {
			print $this->db->error() . ' in ' . $sql;
		}
	}

	/**
	 * 	Set active
	 *
	 *	@param	int		$id			id
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function set_active($id)
	{
		global $conf;

		$sql = "UPDATE ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."specials";
		$sql .= " SET status = 1";

		$sql .= " WHERE products_id = " . $id;

		if ( $this->db->query($sql) ) {
			return 1;
		} else {
			print $this->db->error() . ' in ' . $sql;
		}
	}

	/**
	 * 	Set inactive
	 *
	 *	@param	int		$id			id
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function set_inactive($id)
	{
		global $conf;

		$sql = "UPDATE ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."specials";
		$sql .= " SET status = 0";

		$sql .= " WHERE products_id = " . $id;

		if ( $this->db->query($sql) ) {
			return 1;
		} else {
			print $this->db->error() . ' in ' . $sql;
		}
	}

	/**
	 * 	Fetch datas
	 *
	 *	@param	int		$id			id
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		global $conf;

		$sql = "SELECT c.categories_id, cd.categories_name, c.parent_id";
		$sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."categories as c,".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."categories_description as cd";
		$sql .= " WHERE c.categories_id = cd.categories_id AND cd.language_id = ".$conf->global->OSC_LANGUAGE_ID;
		$sql .= " AND c.categories_id = ".$id;
		$result = $this->db->query($sql);

		if ( $result ) {
			$result = $this->db->fetch_array($result);

			$this->id          = $result["categories_id"];
			$this->parent_id   = $result["parent_id"];
			$this->name        = $result["categories_name"];
			$this->titre       = $result["title"];
			$this->description = $result["description"];
			$this->oscid       = $result["osc_id"];
		}
		$this->db->free($result);

		return $result;
	}


	/**
	 * 	Delete object
	 *
	 *	@param	User	$user		Object user
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function delete($user)
	{
		global $conf;

		$sql = "DELETE FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products WHERE products_id = $idosc ";

		$sql = "DELETE FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products_to_categories WHERE products_id = $idosc";

		$sql = "DELETE FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products_description WHERE products_id = $idosc";

	}


}
?>
