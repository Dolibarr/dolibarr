<?php
/* Copyright (C) 2015      Juanjo Menent	    <jmenent@2byte.es>
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
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/modules/supplier_payment/mod_supplier_payment_bronan.php
 * \ingroup    supplier_payment
 * \brief      File containing class for numbering module Bronan
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/supplier_payment/modules_supplier_payment.php';

/**
 *	Class to manage customer payment numbering rules Cicada
 */
class mod_supplier_payment_bronan extends ModeleNumRefSupplierPayments
{
	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';		// 'development', 'experimental', 'dolibarr'

	public $prefix='SPAY';

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see name
	 */
	public $nom='Bronan';

	/**
	 * @var string model name
	 */
	public $name='Bronan';


    /**
     *  Return description of numbering module
     *
     *  @return     string      Text with description
     */
    public function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
    }


	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		return $this->prefix."0501-0001";
	}


	/**
     *  Checks if the numbers already in force in the data base do not
     *  cause conflicts that would prevent this numbering from working.
	 *
	 *  @return     boolean     false if conflict, true if ok
	 */
	public function canBeActivated()
	{
		global $conf,$langs,$db;

		$payyymm=''; $max='';

		$posindice=9;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiementfourn";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $payyymm = substr($row[0], 0, 6); $max=$row[0]; }
		}
		if ($payyymm && ! preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $payyymm))
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe		$objsoc     Object thirdparty
	 *  @param  Object		$object		Object we need next value for
	 *  @return string      			Value if KO, <0 if KO
	 */
	public function getNextValue($objsoc, $object)
	{
		global $db,$conf;

		// D'abord on recupere la valeur max
		$posindice=10;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiementfourn";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}
		else
		{
			dol_syslog(__METHOD__, LOG_DEBUG);
			return -1;
		}

		//$date=time();
		$date=$object->datepaye;
		$yymm = strftime("%y%m", $date);

    	if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    	else $num = sprintf("%04s", $max+1);

		dol_syslog(__METHOD__." return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return next free value
	 *
	 *  @param	Societe		$objsoc     Object third party
	 * 	@param	string		$objforref	Object for number to search
	 *  @return string      			Next free value
	 */
	public function payment_get_num($objsoc, $objforref)
	{
        // phpcs:enable
		return $this->getNextValue($objsoc, $objforref);
	}
}
