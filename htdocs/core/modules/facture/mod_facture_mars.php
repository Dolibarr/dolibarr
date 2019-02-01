<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/core/modules/facture/mod_facture_mars.php
 *	\ingroup    facture
 *	\brief      File containing class for numbering module Mars
 */
require_once DOL_DOCUMENT_ROOT .'/core/modules/facture/modules_facture.php';

/**
 * 	Classe du modele de numerotation de reference de facture Mars
 */
class mod_facture_mars extends ModeleNumRefFactures
{
	/**
     * Dolibarr version of the loaded document
     * @public string
     */
	public $version = 'dolibarr';		// 'development', 'experimental', 'dolibarr'

	public $prefixinvoice='FA';

	public $prefixreplacement='FR';

	public $prefixdeposit='AC';

	public $prefixcreditnote='AV';

	/**
	 * @var string Error code (or message)
	 */
	public $error='';


	/**
	 * Constructor
	 */
	function __construct()
	{
		if (! empty($conf->global->INVOICE_NUMBERING_MARS_FORCE_PREFIX))
		{
			$this->prefixinvoice = $conf->global->INVOICE_NUMBERING_MARS_FORCE_PREFIX;
		}
	}

	/**
	 *  Renvoi la description du modele de numerotation
	 *
	 *  @return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans('MarsNumRefModelDesc1',$this->prefixinvoice,$this->prefixreplacement,$this->prefixdeposit,$this->prefixcreditnote);
	}

	/**
	 *  Renvoi un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
	function getExample()
	{
		return $this->prefixinvoice."0501-0001";
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $langs,$conf,$db;

		$langs->load("bills");

		// Check invoice num
		$fayymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(facnumber FROM ".$posindice.") AS SIGNED) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber LIKE '".$db->escape($this->prefixinvoice)."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $fayymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if ($fayymm && ! preg_match('/'.$this->prefixinvoice.'[0-9][0-9][0-9][0-9]/i',$fayymm))
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel',$max);
			return false;
		}

		// Check credit note num
		$fayymm='';

		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(facnumber FROM ".$posindice.")) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber LIKE '".$db->escape($this->prefixcreditnote)."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $fayymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if ($fayymm && ! preg_match('/'.$this->prefixcreditnote.'[0-9][0-9][0-9][0-9]/i',$fayymm))
		{
			$this->error=$langs->trans('ErrorNumRefModel',$max);
			return false;
		}

		return true;
	}

	/**
	 * Return next value not used or last value used
	 *
	 * @param	Societe		$objsoc		Object third party
	 * @param   Facture		$invoice	Object invoice
     * @param   string		$mode       'next' for next value or 'last' for last value
	 * @return  string       			Value
	 */
	function getNextValue($objsoc, $invoice, $mode='next')
	{
		global $db;

		$prefix=$this->prefixinvoice;

		if ($invoice->type == 1) $prefix=$this->prefixreplacement;
		else if ($invoice->type == 2) $prefix=$this->prefixcreditnote;
		else if ($invoice->type == 3) $prefix=$this->prefixdeposit;
		else $prefix=$this->prefixinvoice;

		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(facnumber FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber LIKE '".$prefix."____-%'";
		$sql.= " AND entity IN (".getEntity('invoicenumber', 1, $invoice).")";

		$resql=$db->query($sql);
		dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}
		else
		{
			return -1;
		}

		if ($mode == 'last')
		{
    		if ($max >= (pow(10, 4) - 1)) $num=$max;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    		else $num = sprintf("%04s",$max);

            $ref='';
            $sql = "SELECT facnumber as ref";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture";
            $sql.= " WHERE facnumber LIKE '".$prefix."____-".$num."'";
            $sql.= " AND entity IN (".getEntity('invoicenumber', 1, $invoice).")";
            $sql.= " ORDER BY ref DESC";

            dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);
            $resql=$db->query($sql);
            if ($resql)
            {
                $obj = $db->fetch_object($resql);
                if ($obj) $ref = $obj->ref;
            }
            else dol_print_error($db);

            return $ref;
		}
		else if ($mode == 'next')
		{
			$date=$invoice->date;	// This is invoice date (not creation date)
    		$yymm = strftime("%y%m",$date);

    		if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    		else $num = sprintf("%04s",$max+1);

    		dol_syslog(get_class($this)."::getNextValue return ".$prefix.$yymm."-".$num);
    		return $prefix.$yymm."-".$num;
		}
		else dol_print_error('','Bad parameter for getNextValue');
	}

	/**
	 * Return next free value
	 *
     * @param	Societe		$objsoc     	Object third party
     * @param	string		$objforref		Object for number to search
     * @param   string		$mode       	'next' for next value or 'last' for last value
     * @return  string      				Next free value
	 */
	function getNumRef($objsoc,$objforref,$mode='next')
	{
		return $this->getNextValue($objsoc,$objforref,$mode);
	}
}

