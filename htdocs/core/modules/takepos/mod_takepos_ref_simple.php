<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2020      Open-DSI	            <support@open-dsi.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/core/modules/takepos/mod_takepos_ref_simple.php
 *  \ingroup    takepos
 *  \brief      File with Simple ref numbering module for takepos
 */
dol_include_once('/core/modules/takepos/modules_takepos.php');

/**
 *	Class to manage ref numbering of takepos cards with rule Simple.
 */
class mod_takepos_ref_simple extends ModeleNumRefTakepos
{
	/**
	 * Dolibarr version of the loaded document 'development', 'experimental', 'dolibarr'
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * Prefix
	 * @var string
	 */
	public $prefix = 'TC';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * Name
	 * @var string
	 */
	public $nom = 'Simple';

	/**
	 *  Return description of numbering module
	 *
	 * @return     string      Text with description
	 */
	public function info()
	{
		global $langs;

		return $langs->trans('SimpleNumRefModelDesc', $this->prefix.'0-');
	}

	/**
	 *  Return an example of numbering module values
	 *
	 * @return     string      Example
	 */
	public function getExample()
	{
		return $this->prefix.'0-0501-0001';
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 * @return     boolean     false si conflit, true si ok
	 */
	public function canBeActivated()
	{
		global $conf, $langs, $db;

		$pryymm = '';
		$max = '';

		$pos_source = 0;

		// First, we get the max value
		$posindice = strlen($this->prefix.$pos_source.'-____-') + 1;

		$sql  = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$pryymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}

		if (!$pryymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $pryymm)) {
			return true;
		} else {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}
	}

	/**
	 *  Return next value
	 *
	 * @param   Societe     $objsoc     Object third party
	 * @param   Facture		$invoice	Object invoice
	 * @param   string		$mode       'next' for next value or 'last' for last value
	 * @return  string      Next value
	 */
	public function getNextValue($objsoc = null, $invoice = null, $mode = 'next')
	{
		global $db;

		$pos_source = is_object($invoice) && $invoice->pos_source > 0 ? $invoice->pos_source : 0;

		// First, we get the max value
		$posindice = strlen($this->prefix.$pos_source.'-____-') + 1;
		$sql  = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max"; // This is standard SQL
		$sql .= " FROM ".MAIN_DB_PREFIX."facture";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix.$pos_source)."-____-%'";
		$sql .= " AND entity IN (".getEntity('invoicenumber', 1, $invoice).")";

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max = 0;
		} else {
			dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);
			return -1;
		}

		if ($mode == 'last')
		{
			if ($max >= (pow(10, 4) - 1)) $num = $max; // If counter > 9999, we do not format on 4 chars, we take number as it is
			else $num = sprintf("%04s", $max);

			$ref = '';
			$sql  = "SELECT ref as ref";
			$sql .= " FROM ".MAIN_DB_PREFIX."facture";
			$sql .= " WHERE ref LIKE '".$db->escape($this->prefix.$pos_source)."-____-".$num."'";
			$sql .= " AND entity IN (".getEntity('invoicenumber', 1, $invoice).")";
			$sql .= " ORDER BY ref DESC";

			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if ($obj) $ref = $obj->ref;
			} else dol_print_error($db);

			return $ref;
		} elseif ($mode == 'next')
		{
			$date = $invoice->date; // This is invoice date (not creation date)
			$yymm = strftime("%y%m", $date);

			if ($max >= (pow(10, 4) - 1)) $num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
			else $num = sprintf("%04s", $max + 1);

			dol_syslog(get_class($this)."::getNextValue return ".$this->prefix.$pos_source.'-'.$yymm.'-'.$num);
			return $this->prefix.$pos_source.'-'.$yymm.'-'.$num;
		} else dol_print_error('', 'Bad parameter for getNextValue');
	}

	/**
	 *  Return next free value
	 *
	 * @param       Societe     $objsoc         Object third party
	 * @param       Object      $objforref      Object for number to search
	 * @return      string      Next free value
	 */
	public function getNumRef($objsoc, $objforref)
	{
		return $this->getNextValue($objsoc, $objforref);
	}
}
