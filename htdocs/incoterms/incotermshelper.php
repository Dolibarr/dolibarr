<?php
/* Copyright (C) 2020		Tobias Sekan	<tobias.sekan@startmail.com>
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

/**
 *    \file       htdocs/incoterms/incotermhelper.php
 *    \ingroup    Incoterms
 *    \brief      File of class to manage Incoterms (International Commercial Terms)
 */

require_once DOL_DOCUMENT_ROOT.'/incoterms/class/incoterm.class.php';

// TODO - First
//
// * Move to this place -> "htdocs\core\class\commonobject.class.php @getIncotermsForPDF"
// * Move to this place -> "htdocs\core\class\commonobject.class.php @setIncoterms"

// TODO - Near future
// * Add selectable matrix (for easier select the correct Incoterm)

// TODO - Backlog
// * Recreate as Dolibarr module

class IncotermsHelper
{
	private $db;

	/**
	 * Incoterm list
	 *
	 * @var Incoterm[]
	 */
	private $incotermList;

	public function __construct(DoliDb $db)
	{
		$this->db			= $db;
		$this->incotermList = array();

		$this->fetchIncotermListFromDatabase();
	}

	/*
	* ----------------
	* Public functions
	* ----------------
	*/

	/**
	 * Return a list with all Incoterms (inside the database)
	 *
	 * @return Incoterm[]	A list with Incoterms
	 */
	public function getIncotermList()
	{
		return $this->incotermList;
	}

	/**
	 * Return the Incoterm for the given id
	 *
	 * @param	string		$id		The id of the Incoterm
	 * @return 	Incoterm
	 */
	public function getIncotermById($id)
	{
		foreach ($this->incotermList as $incoterm)
		{
			if ($incoterm->id != $id)
			{
				continue;
			}

			return $incoterm;
		}
	}

	/**
	 * Return the Incoterm for the given international incoterm code
	 *
	 * @param	string		$code		The international code of the Incoterm
	 * @return 	Incoterm
	 */
	public function getIncotermByCode($code)
	{
		foreach ($this->incotermList as $incoterm)
		{
			if($incoterm->id != $code)
			{
				continue;
			}

			return $incoterm;
		}
	}

	/**
	 * Return a formatted text for a Incoterm, based on teh values inside the given object
	 *
	 * @param CommonObject $object		A object that should contain a internal id of a incoterm
	 * @return string					A formatted Incoterm text
	 */
	public function getText(CommonObject $object)
	{
		$incoterm = $this->getIncotermById($object->fk_incoterms);

		if (!empty($incoterm))
		{
			$out = $incoterm->code;
			$out .= ($incoterm->code && $object->location_incoterms) ? ' - ' : '';
		}

		$out .= $object->location_incoterms;

		return $out;
	}

	/**
	 * Return the description of the Incoterm of the given object
	 *
	 * @param	CommonObject	$object		A object that should contain a internal id of the Incoterm
	 * @return	string						A description of a Incoterm
	 */
	public function getDescription(CommonObject $object)
	{
		$incoterm = $this->getIncotermById($object->fk_incoterms);

		return empty($incoterm) ? "" : $incoterm->description;
	}

	/*
	* -----------------
	* Private functions
	* -----------------
	*/

	/**
	 * Fetch the list of Incoterms from the database
	 *
	 * @return void
	 */
	private function fetchIncotermListFromDatabase()
	{
		$sql = "SELECT rowid, code, libelle as description";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_incoterms";
		$sql .= " WHERE active > 0";
		$sql .= " ORDER BY code ASC";

		$resql = $this->db->query($sql);
		if (!$resql)
		{
			dol_print_error($this->db, "Error on get incoterm list from database");
			return;
		}

		$i		= 0;
		$num	= $this->db->num_rows($resql);

		while ($i < $num)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				$this->incotermList[] = new Incoterm($obj->rowid, $obj->code, $obj->description);
			}

			$i++;
		}
	}
}
