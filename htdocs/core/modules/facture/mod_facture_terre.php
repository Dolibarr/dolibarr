<?php
/* Copyright (C) 2005-2008  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/core/modules/facture/mod_facture_terre.php
 *  \ingroup    invoice
 *  \brief      File containing class for numbering module Terre
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';

/**
 *  \class      mod_facture_terre
 *  \brief      Class of numbering module Terre for invoices
 */
class mod_facture_terre extends ModeleNumRefFactures
{
	/**
	 * Dolibarr version of the loaded document 'development', 'experimental', 'dolibarr'
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * Prefix for invoices
	 * @var string
	 */
	public $prefixinvoice = 'FA';

	/**
	 * Prefix for replacement invoices
	 * @var string
	 */
	public $prefixreplacement = 'FA';

	/**
	 * Prefix for credit note
	 * @var string
	 */
	public $prefixcreditnote = 'AV';

	/**
	 * Prefix for deposit
	 * @var string
	 */
	public $prefixdeposit = 'AC';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $conf, $mysoc;

		if (((float) getDolGlobalString('MAIN_VERSION_LAST_INSTALL')) >= 16.0 && $mysoc->country_code != 'FR') {
			$this->prefixinvoice = 'IN'; // We use correct standard code "IN = Invoice"
			$this->prefixreplacement = 'IR';
			$this->prefixdeposit = 'ID';
			$this->prefixcreditnote = 'IC';
		}

		if (getDolGlobalString('INVOICE_NUMBERING_TERRE_FORCE_PREFIX')) {
			$this->prefixinvoice = getDolGlobalString('INVOICE_NUMBERING_TERRE_FORCE_PREFIX');
		}
	}

	/**
	 *  Returns the description of the numbering model
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans('TerreNumRefModelDesc1', $this->prefixinvoice, $this->prefixcreditnote, $this->prefixdeposit);
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		return $this->prefixinvoice."0501-0001";
	}

	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *  @param  CommonObject	$object		Object we need next value for
	 *  @return boolean     				false if conflict, true if ok
	 */
	public function canBeActivated($object)
	{
		global $langs, $conf, $db;

		$langs->load("bills");

		// Check invoice num
		$fayymm = '';
		$max = '';

		$posindice = strlen($this->prefixinvoice) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max"; // This is standard SQL
		$sql .= " FROM ".MAIN_DB_PREFIX."facture";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefixinvoice)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$fayymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if ($fayymm && !preg_match('/'.$this->prefixinvoice.'[0-9][0-9][0-9][0-9]/i', $fayymm)) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		// Check credit note num
		$fayymm = '';

		$posindice = strlen($this->prefixcreditnote) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max"; // This is standard SQL
		$sql .= " FROM ".MAIN_DB_PREFIX."facture";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefixcreditnote)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$fayymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if ($fayymm && !preg_match('/'.$this->prefixcreditnote.'[0-9][0-9][0-9][0-9]/i', $fayymm)) {
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		// Check deposit num
		$fayymm = '';

		$posindice = strlen($this->prefixdeposit) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max"; // This is standard SQL
		$sql .= " FROM ".MAIN_DB_PREFIX."facture";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefixdeposit)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$fayymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if ($fayymm && !preg_match('/'.$this->prefixdeposit.'[0-9][0-9][0-9][0-9]/i', $fayymm)) {
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * Return next value not used or last value used.
	 * Note to increase perf of this numbering engine, you can create a calculated column and modify request to use this field instead for select:
	 * ALTER TABLE llx_facture ADD COLUMN calculated_numrefonly INTEGER AS (CASE SUBSTRING(ref FROM 1 FOR 2) WHEN 'FA' THEN CAST(SUBSTRING(ref FROM 10) AS SIGNED) ELSE 0 END) PERSISTENT;
	 * ALTER TABLE llx_facture ADD INDEX calculated_numrefonly_idx (calculated_numrefonly);
	 *
	 * @param   Societe		$objsoc		Object third party
	 * @param   Facture		$invoice	Object invoice
	 * @param   string		$mode       'next' for next value or 'last' for last value
	 * @return  string|int<-1,0>       	Next ref value or last ref if $mode is 'last', -1 or 0 if KO
	 */
	public function getNextValue($objsoc, $invoice, $mode = 'next')
	{
		global $db;

		dol_syslog(get_class($this)."::getNextValue mode=".$mode, LOG_DEBUG);

		$prefix = $this->prefixinvoice;
		if ($invoice->type == 2) {
			$prefix = $this->prefixcreditnote;
		} elseif ($invoice->type == 3) {
			$prefix = $this->prefixdeposit;
		}

		// First we get the max value
		$posindice = strlen($prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max"; // This is standard SQL
		$sql .= " FROM ".MAIN_DB_PREFIX."facture";
		$sql .= " WHERE ref LIKE '".$db->escape($prefix)."____-%'";
		$sql .= " AND entity IN (".getEntity('invoicenumber', 1, $invoice).")";

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$max = intval($obj->max);
			} else {
				$max = 0;
			}
		} else {
			return -1;
		}

		if ($mode == 'last') {
			if ($max >= (pow(10, 4) - 1)) {
				$num = $max; // If counter > 9999, we do not format on 4 chars, we take number as it is
			} else {
				$num = sprintf("%04d", $max);
			}

			$ref = '';
			$sql = "SELECT ref as ref";
			$sql .= " FROM ".MAIN_DB_PREFIX."facture";
			$sql .= " WHERE ref LIKE '".$db->escape($prefix)."____-".$num."'";
			$sql .= " AND entity IN (".getEntity('invoicenumber', 1, $invoice).")";
			$sql .= " ORDER BY ref DESC";

			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$ref = $obj->ref;
				}
			} else {
				dol_print_error($db);
			}

			return $ref;
		} elseif ($mode == 'next') {
			$date = $invoice->date; // This is invoice date (not creation date)
			$yymm = dol_print_date($date, "%y%m");

			if ($max >= (pow(10, 4) - 1)) {
				$num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
			} else {
				$num = sprintf("%04d", $max + 1);
			}

			dol_syslog(get_class($this)."::getNextValue return ".$prefix.$yymm."-".$num);
			return $prefix.$yymm."-".$num;
		} else {
			dol_print_error(null, 'Bad parameter for getNextValue');
		}

		return 0;
	}

	/**
	 *  Return next free value
	 *
	 *  @param  Societe     $objsoc         Object third party
	 *  @param  Facture      $objforref      Object for number to search
	 *  @param   string     $mode           'next' for next value or 'last' for last value
	 *  @return  string|int<-1,0>           Next free value, -1 or 0 if error
	 *  @deprecated see getNextValue
	 */
	public function getNumRef($objsoc, $objforref, $mode = 'next')
	{
		return $this->getNextValue($objsoc, $objforref, $mode);
	}
}
