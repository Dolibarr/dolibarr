<?php
<<<<<<< HEAD
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
=======
/* Copyright (C) 2005-2008  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015  Regis Houssin           <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
 *	\file       htdocs/core/modules/facture/mod_facture_terre.php
 *	\ingroup    facture
 *	\brief      File containing class for numbering module Terre
 */
require_once DOL_DOCUMENT_ROOT .'/core/modules/facture/modules_facture.php';

/**	    \class      mod_facture_terre
 *		\brief      Classe du modele de numerotation de reference de facture Terre
 */
class mod_facture_terre extends ModeleNumRefFactures
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefixinvoice='FA';
	var $prefixcreditnote='AV';
	var $prefixdeposit='AC';
	var $error='';
=======
 *  \file       htdocs/core/modules/facture/mod_facture_terre.php
 *  \ingroup    facture
 *  \brief      File containing class for numbering module Terre
 */
require_once DOL_DOCUMENT_ROOT .'/core/modules/facture/modules_facture.php';

/**
 *  \class      mod_facture_terre
 *  \brief      Classe du modele de numerotation de reference de facture Terre
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
    public $prefixinvoice='FA';

    /**
     * Prefix for credit note
     * @var string
     */
    public $prefixcreditnote='AV';

    /**
     * Prefix for deposit
     * @var string
     */
    public $prefixdeposit='AC';

    /**
     * @var string Error code (or message)
     */
    public $error='';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	/**
	 * Constructor
	 */
<<<<<<< HEAD
	function __construct()
=======
	public function __construct()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		if (! empty($conf->global->INVOICE_NUMBERING_TERRE_FORCE_PREFIX))
		{
			$this->prefixinvoice = $conf->global->INVOICE_NUMBERING_TERRE_FORCE_PREFIX;
		}
	}

	/**
<<<<<<< HEAD
	 *  Renvoi la description du modele de numerotation
	 *
	 *  @return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans('TerreNumRefModelDesc1',$this->prefixinvoice,$this->prefixcreditnote,$this->prefixdeposit);
=======
	 *  Returns the description of the numbering model
	 *
	 *  @return     string      Texte descripif
	 */
	public function info()
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans('TerreNumRefModelDesc1', $this->prefixinvoice, $this->prefixcreditnote, $this->prefixdeposit);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}

	/**
	 *  Renvoi un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
<<<<<<< HEAD
	function getExample()
=======
	public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->prefixinvoice."0501-0001";
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
<<<<<<< HEAD
	function canBeActivated()
=======
	public function canBeActivated()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs,$conf,$db;

		$langs->load("bills");

		// Check invoice num
		$fayymm=''; $max='';

		$posindice=8;
<<<<<<< HEAD
		$sql = "SELECT MAX(CAST(SUBSTRING(facnumber FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber LIKE '".$db->escape($this->prefixinvoice)."____-%'";
=======
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefixinvoice)."____-%'";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
<<<<<<< HEAD
			if ($row) { $fayymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if ($fayymm && ! preg_match('/'.$this->prefixinvoice.'[0-9][0-9][0-9][0-9]/i',$fayymm))
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel',$max);
=======
			if ($row) { $fayymm = substr($row[0], 0, 6); $max=$row[0]; }
		}
		if ($fayymm && ! preg_match('/'.$this->prefixinvoice.'[0-9][0-9][0-9][0-9]/i', $fayymm))
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel', $max);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			return false;
		}

		// Check credit note num
		$fayymm='';

		$posindice=8;
<<<<<<< HEAD
		$sql = "SELECT MAX(CAST(SUBSTRING(facnumber FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber LIKE '".$db->escape($this->prefixcreditnote)."____-%'";
=======
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefixcreditnote)."____-%'";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
<<<<<<< HEAD
			if ($row) { $fayymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if ($fayymm && ! preg_match('/'.$this->prefixcreditnote.'[0-9][0-9][0-9][0-9]/i',$fayymm))
		{
			$this->error=$langs->trans('ErrorNumRefModel',$max);
=======
			if ($row) { $fayymm = substr($row[0], 0, 6); $max=$row[0]; }
		}
		if ($fayymm && ! preg_match('/'.$this->prefixcreditnote.'[0-9][0-9][0-9][0-9]/i', $fayymm))
		{
			$this->error=$langs->trans('ErrorNumRefModel', $max);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			return false;
		}

		// Check deposit num
		$fayymm='';

		$posindice=8;
<<<<<<< HEAD
		$sql = "SELECT MAX(CAST(SUBSTRING(facnumber FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber LIKE '".$db->escape($this->prefixdeposit)."____-%'";
=======
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefixdeposit)."____-%'";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
<<<<<<< HEAD
			if ($row) { $fayymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if ($fayymm && ! preg_match('/'.$this->prefixdeposit.'[0-9][0-9][0-9][0-9]/i',$fayymm))
		{
			$this->error=$langs->trans('ErrorNumRefModel',$max);
=======
			if ($row) { $fayymm = substr($row[0], 0, 6); $max=$row[0]; }
		}
		if ($fayymm && ! preg_match('/'.$this->prefixdeposit.'[0-9][0-9][0-9][0-9]/i', $fayymm))
		{
			$this->error=$langs->trans('ErrorNumRefModel', $max);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			return false;
		}

		return true;
	}

	/**
	 * Return next value not used or last value used
	 *
<<<<<<< HEAD
	 * @param	Societe		$objsoc		Object third party
	 * @param   Facture		$facture	Object invoice
     * @param   string		$mode       'next' for next value or 'last' for last value
	 * @return  string       			Value
	 */
	function getNextValue($objsoc,$facture,$mode='next')
	{
		global $db;

		if ($facture->type == 2) $prefix=$this->prefixcreditnote;
		else if ($facture->type == 3) $prefix=$this->prefixdeposit;
		else $prefix=$this->prefixinvoice;

		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(facnumber FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber LIKE '".$prefix."____-%'";
		$sql.= " AND entity IN (".getEntity('invoicenumber').")";

		$resql=$db->query($sql);
		dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);
=======
	 * @param   Societe		$objsoc		Object third party
	 * @param   Facture		$invoice	Object invoice
     * @param   string		$mode       'next' for next value or 'last' for last value
	 * @return  string       			Value
	 */
	public function getNextValue($objsoc, $invoice, $mode = 'next')
	{
		global $db;

		dol_syslog(get_class($this)."::getNextValue mode=".$mode, LOG_DEBUG);

		if ($invoice->type == 2) $prefix=$this->prefixcreditnote;
		elseif ($invoice->type == 3) $prefix=$this->prefixdeposit;
		else $prefix=$this->prefixinvoice;
		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE ref LIKE '".$prefix."____-%'";
		$sql.= " AND entity IN (".getEntity('invoicenumber', 1, $invoice).")";

		$resql=$db->query($sql);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
    		else $num = sprintf("%04s",$max);

            $ref='';
            $sql = "SELECT facnumber as ref";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture";
            $sql.= " WHERE facnumber LIKE '".$prefix."____-".$num."'";
            $sql.= " AND entity IN (".getEntity('invoicenumber').")";

            dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);
=======
    		else $num = sprintf("%04s", $max);

            $ref='';
            $sql = "SELECT ref as ref";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture";
            $sql.= " WHERE ref LIKE '".$prefix."____-".$num."'";
            $sql.= " AND entity IN (".getEntity('invoicenumber', 1, $invoice).")";
            $sql.= " ORDER BY ref DESC";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $resql=$db->query($sql);
            if ($resql)
            {
                $obj = $db->fetch_object($resql);
                if ($obj) $ref = $obj->ref;
            }
            else dol_print_error($db);

            return $ref;
		}
<<<<<<< HEAD
		else if ($mode == 'next')
		{
    		$date=$facture->date;	// This is invoice date (not creation date)
    		$yymm = strftime("%y%m",$date);

    		if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    		else $num = sprintf("%04s",$max+1);
=======
		elseif ($mode == 'next')
		{
			$date=$invoice->date;	// This is invoice date (not creation date)
    		$yymm = strftime("%y%m", $date);

    		if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    		else $num = sprintf("%04s", $max+1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    		dol_syslog(get_class($this)."::getNextValue return ".$prefix.$yymm."-".$num);
    		return $prefix.$yymm."-".$num;
		}
<<<<<<< HEAD
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

=======
		else dol_print_error('', 'Bad parameter for getNextValue');
	}

    /**
     *  Return next free value
     *
     *  @param  Societe     $objsoc         Object third party
     *  @param  string      $objforref      Object for number to search
     *  @param   string     $mode           'next' for next value or 'last' for last value
     *  @return  string                     Next free value
     */
    public function getNumRef($objsoc, $objforref, $mode = 'next')
    {
        return $this->getNextValue($objsoc, $objforref, $mode);
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
