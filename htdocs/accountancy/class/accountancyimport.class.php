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
 * \brief 		Class accountancy import
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
		global $conf;

		$this->db = $db;
	}

	/**
	 *  Compute amount
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   string      $fieldname          Field name with alias
	 * @param   array       $listfields         Fields list to add
	 * @param   array       $listvalues         Values list to add
	 * @return  int         <0 if KO, >0 if OK
	 */
	public function computeAmount(&$arrayrecord, $fieldname, &$listfields, &$listvalues)
	{
		$fieldArr = explode('.', $fieldname);
		if (count($fieldArr) > 0) {
			$fieldname = $fieldArr[1];
		}

		$debit  = floatval(trim($arrayrecord[11]['val']));
		$credit = floatval(trim($arrayrecord[12]['val']));
		if (!empty($debit)) {
			$amount = $debit;
		} else {
			$amount = $credit;
		}

		$listfields[] = $fieldname;
		$listvalues[] = "'" . abs($amount) . "'";

		return 1;
	}


	/**
	 *  Compute sens
	 *
	 * @param   array       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   string      $fieldname          Field name with alias
	 * @param   array       $listfields         Fields list to add
	 * @param   array       $listvalues         Values list to add
	 * @return  int         <0 if KO, >0 if OK
	 */
	public function computeDirection(&$arrayrecord, $fieldname, &$listfields, &$listvalues)
	{
		$fieldArr = explode('.', $fieldname);
		if (count($fieldArr) > 0) {
			$fieldname = $fieldArr[1];
		}

		$debit = floatval(trim($arrayrecord[11]['val']));
		if (!empty($debit)) {
			$sens = 'D';
		} else {
			$sens = 'C';
		}

		$listfields[] = $fieldname;
		$listvalues[] = "'" . $sens . "'";

		return 1;
	}
}
