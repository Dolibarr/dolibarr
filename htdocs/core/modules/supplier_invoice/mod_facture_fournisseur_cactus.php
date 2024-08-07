<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2018 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2016      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 *    	\file       htdocs/core/modules/supplier_invoice/mod_facture_fournisseur_cactus.php
 *		\ingroup    supplier invoice
 *		\brief      File containing class for the numbering module Cactus
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';


/**
 *  Cactus Class of numbering models of suppliers invoices references
 */
class mod_facture_fournisseur_cactus extends ModeleNumRefSuppliersInvoices
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Cactus';

	/**
	 * @var string model name
	 */
	public $name = 'Cactus';

	public $prefixinvoice = 'SI';

	public $prefixcreditnote = 'SA';

	public $prefixdeposit = 'SD';


	/**
	 *  Return description of numbering model
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("CactusNumRefModelDesc1", $this->prefixinvoice, $this->prefixcreditnote, $this->prefixdeposit);
	}


	/**
	 *  Returns a numbering example
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		return $this->prefixinvoice."1301-0001";
	}


	/**
	 * 	Tests if the numbers already in the database do not cause conflicts that would prevent this numbering.
	 *
	 *	@param	CommonObject	$object		Object we need next value for
	 *  @return boolean     				false if KO (there is a conflict), true if OK
	 */
	public function canBeActivated($object)
	{
		global $conf, $langs, $db;

		$langs->load("bills");

		// Check invoice num
		$siyymm = '';
		$max = '';

		$posindice = strlen($this->prefixinvoice) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefixinvoice)."____-%'";
		$sql .= " AND entity = ".$conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$siyymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if ($siyymm && !preg_match('/'.$this->prefixinvoice.'[0-9][0-9][0-9][0-9]/i', $siyymm)) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		// Check credit note num
		$siyymm = '';

		$posindice = strlen($this->prefixcreditnote) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max"; // This is standard SQL
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefixcreditnote)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$siyymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if ($siyymm && !preg_match('/'.$this->prefixcreditnote.'[0-9][0-9][0-9][0-9]/i', $siyymm)) {
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		// Check deposit num
		$siyymm = '';

		$posindice = strlen($this->prefixdeposit) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max"; // This is standard SQL
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefixdeposit)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$siyymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if ($siyymm && !preg_match('/'.$this->prefixdeposit.'[0-9][0-9][0-9][0-9]/i', $siyymm)) {
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * Return next value
	 *
	 * @param	Societe				$objsoc		Object third party
	 * @param  	FactureFournisseur	$object		Object invoice
	 * @param   string				$mode		'next' for next value or 'last' for last value
	 * @return 	string|int<-1,0>				Value if OK, -1 if KO
	 */
	public function getNextValue($objsoc, $object, $mode = 'next')
	{
		global $db, $conf;

		$prefix = $this->prefixinvoice;
		if ($object->type == 2) {
			$prefix = $this->prefixcreditnote;
		} elseif ($object->type == 3) {
			$prefix = $this->prefixdeposit;
		}

		// First, we get the max value
		$posindice = strlen($prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max"; // This is standard SQL
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn";
		$sql .= " WHERE ref LIKE '".$db->escape($prefix)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);
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
			$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn";
			$sql .= " WHERE ref LIKE '".$db->escape($prefix)."____-".$num."'";
			$sql .= " AND entity = ".$conf->entity;

			dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);
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
			$date = $object->date; // This is invoice date (not creation date)
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
			return -1;
		}
	}


	/**
	 * Return next free value
	 *
	 * @param	Societe				$objsoc     	Object third party
	 * @param	FactureFournisseur	$objforref		Object for number to search
	 * @param   string				$mode      		'next' for next value or 'last' for last value
	 * @return  string      						Next free value
	 * @deprecated see getNextValue
	 */
	public function getNumRef($objsoc, $objforref, $mode = 'next')
	{
		return $this->getNextValue($objsoc, $objforref, $mode);
	}
}
