<?php
/*
 * Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Pierre-Henry Favre  <phf@atm-consulting.fr>
 * Copyright (C) 2016-2020  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2013-2017  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2017       Elarifr. Ari Elbaz  <github@accedinfo.com>
 * Copyright (C) 2017-2019  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2017       André Schild        <a.schild@aarboard.ch>
 * Copyright (C) 2020       Guillaume Alexandre <guillaume@tag-info.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file		htdocs/accountancy/class/accountancyimport.class.php
 * \ingroup		Accountancy (Double entries)
 * \brief 		Class with methods for accountancy import
 */



/**
 * Manage the different format accountancy import
 */
class AccountancyImport
{
	/**
	 * @var DoliDB	Database handler
	 */
	public $db;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 *  Clean amount
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function cleanAmount(&$arrayrecord, $listfields, $record_key)
	{
		$value_trim = trim($arrayrecord[$record_key]['val']);
		return (float) $value_trim;
	}

	/**
	 *  Clean value with trim
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function cleanValue(&$arrayrecord, $listfields, $record_key)
	{
		return trim($arrayrecord[$record_key]['val']);
	}

	/**
	 *  Compute amount
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function computeAmount(&$arrayrecord, $listfields, $record_key)
	{
		// get fields indexes
		if (isset($listfields['b.debit']) && isset($listfields['b.credit'])) {
			$debit_index = $listfields['b.debit'];
			$credit_index = $listfields['b.credit'];

			$debit  = (float) $arrayrecord[$debit_index]['val'];
			$credit = (float) $arrayrecord[$credit_index]['val'];
			if (!empty($debit)) {
				$amount = $debit;
			} else {
				$amount = $credit;
			}

			return "'" . $this->db->escape(abs($amount)) . "'";
		}

		return "''";
	}


	/**
	 *  Compute direction
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function computeDirection(&$arrayrecord, $listfields, $record_key)
	{
		if (isset($listfields['b.debit'])) {
			$debit_index = $listfields['b.debit'];

			$debit = (float) $arrayrecord[$debit_index]['val'];
			if (!empty($debit)) {
				$sens = 'D';
			} else {
				$sens = 'C';
			}

			return "'" . $this->db->escape($sens) . "'";
		}

		return "''";
	}
}
