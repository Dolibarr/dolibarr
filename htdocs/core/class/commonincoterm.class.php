<?php
/* Copyright (C) 2012 Regis Houssin  <regis.houssin@inodbox.com>
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
 *       \file       htdocs/core/class/commonincoterm.class.php
 *       \ingroup    core
 *       \brief      File of the superclass of object classes that support incoterm (customer and supplier)
 */


/**
 *      Superclass for incoterm classes
 */
trait CommonIncoterm
{
	/**
	 * @var int		ID incoterm.
	 * @see setIncoterms()
	 */
	public $fk_incoterms;

	/**
	 * @var string	Label of incoterm. Used for tooltip.
	 * @see SetIncoterms()
	 */
	public $label_incoterms;

	/**
	 * @var string	Location of incoterm.
	 * @see display_incoterms()
	 */
	public $location_incoterms;


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return incoterms informations
	 *    TODO Use a cache for label get
	 *
	 *    @return	string	incoterms info
	 */
	public function display_incoterms()
	{
		// phpcs:enable
		$out = '';

		$this->label_incoterms = '';
		if (!empty($this->fk_incoterms)) {
			$sql = 'SELECT code FROM '.MAIN_DB_PREFIX.'c_incoterms WHERE rowid = '.(int) $this->fk_incoterms;
			$result = $this->db->query($sql);
			if ($result) {
				$res = $this->db->fetch_object($result);
				$out .= $res->code;
			}
		}

		$out .= (($out && $this->location_incoterms) ? ' - ' : '').$this->location_incoterms;

		return $out;
	}

	/**
	 *    Return incoterms informations for pdf display
	 *
	 *    @return	string		incoterms info
	 */
	public function getIncotermsForPDF()
	{
		$sql = 'SELECT code FROM '.MAIN_DB_PREFIX.'c_incoterms WHERE rowid = '.(int) $this->fk_incoterms;
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				$res = $this->db->fetch_object($resql);
				return 'Incoterm : '.$res->code.' - '.$this->location_incoterms;
			} else {
				return '';
			}
		} else {
			$this->errors[] = $this->db->lasterror();
			return false;
		}
	}

	/**
	 *    Define incoterms values of current object
	 *
	 *    @param	int		$id_incoterm     Id of incoterm to set or '' to remove
	 * 	  @param 	string  $location		 location of incoterm
	 *    @return	int     				<0 if KO, >0 if OK
	 */
	public function setIncoterms($id_incoterm, $location)
	{
		if ($this->id && $this->table_element) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET fk_incoterms = ".($id_incoterm > 0 ? $id_incoterm : "null");
			$sql .= ", location_incoterms = ".($id_incoterm > 0 ? "'".$this->db->escape($location)."'" : "null");
			$sql .= " WHERE rowid = ".$this->id;
			dol_syslog(get_class($this).'::setIncoterms', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->fk_incoterms = $id_incoterm;
				$this->location_incoterms = $location;

				$sql = 'SELECT libelle as label_incotermsFROM '.MAIN_DB_PREFIX.'c_incoterms WHERE rowid = '.(int) $this->fk_incoterms;
				$res = $this->db->query($sql);
				if ($res) {
					$obj = $this->db->fetch_object($res);
					$this->label_incoterms = $obj->label_incoterms;
				}
				return 1;
			} else {
				$this->errors[] = $this->db->lasterror();
				return -1;
			}
		} else {
			return -1;
		}
	}
}
