<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
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
 *	\file       htdocs/core/class/commonobjectline.class.php
 *  \ingroup    core
 *  \brief      File of the superclass of classes of lines of business objects (invoice, contract, proposal, orders, etc. ...)
 */


/**
 *  Parent class for class inheritance lines of business objects
 *  This class is useless for the moment so no inherit are done on it
 */
abstract class CommonObjectLine extends CommonObject
{
	/**
	 * Id of the line
	 * @var int
	 */
	public $id;

	/**
	 * Id of the line
	 * @var int
	 * @deprecated Try to use id property as possible (even if field into database is still rowid)
	 * @see $id
	 */
	public $rowid;

	/**
	 * Product/service unit code ('km', 'm', 'p', ...)
	 * @var string
	 */
	public $fk_unit;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Returns the label, shot_label or code found in units dictionary from ->fk_unit.
	 *  A langs->trans() must be called on result to get translated value.
	 *
	 * 	@param	string $type 	Label type ('long', 'short' or 'code'). This can be a translation key.
	 *	@return	string|int 		<0 if KO, label if OK (Example: 'long', 'short' or 'unitCODE')
	 */
	public function getLabelOfUnit($type = 'long')
	{
		global $langs;

		if (!$this->fk_unit) {
			return '';
		}

		$langs->load('products');

		$label_type = 'label';

		$label_type = 'label';
		if ($type == 'short') $label_type = 'short_label';
		elseif ($type == 'code') $label_type = 'code';

		$sql = 'select '.$label_type.', code from '.MAIN_DB_PREFIX.'c_units where rowid='.$this->fk_unit;
		$resql = $this->db->query($sql);
		if ($resql && $this->db->num_rows($resql) > 0) {
			$res = $this->db->fetch_array($resql);
			if ($label_type == 'code') $label = 'unit'.$res['code'];
			else $label = $res[$label_type];
			$this->db->free($resql);
			return $label;
		} else {
			$this->error = $this->db->error().' sql='.$sql;
			dol_syslog(get_class($this)."::getLabelOfUnit Error ".$this->error, LOG_ERR);
			return -1;
		}
	}
	// Currently we need function at end of file CommonObject for all object lines. Should find a way to avoid duplicate code.

	// For the moment we use the extends on CommonObject until PHP min is 5.4 so use Traits.
}
