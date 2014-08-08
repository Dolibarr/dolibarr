<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Philippe Grand       <philippe.grand@atoo-net.com>
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
 *    	\file       htdocs/core/modules/supplier_invoice/mod_facture_fournisseur_cactus.php
 *		\ingroup    supplier invoice
 *		\brief      File containing the Cactus Class of numbering models of suppliers invoices references
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/supplier_invoice/modules_facturefournisseur.php';


/**
 *	Cactus Class of numbering models of suppliers invoices references
 */
class mod_facture_fournisseur_cactus extends ModeleNumRefSuppliersInvoices
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = 'Cactus';
	var $prefixinvoice='SI';


    /**
     * 	Return description of numbering model
     *
     *  @return     string      Text with description
     */
    function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc",$this->prefixinvoice);
    }


    /**
     * 	Returns a numbering example
     *
     *  @return     string      Example
     */
    function getExample()
    {
        return $this->prefixinvoice."1301-0001";
    }


    /**
     * 	Tests if the numbers already in force in the database do not cause conflicts that would prevent this numbering.
     *
     *  @return     boolean     false if conflict, true if ok
     */
    function canBeActivated()
    {
    	global $conf,$langs,$db;

        $siyymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn";
		$sql.= " WHERE ref LIKE '".$this->prefixinvoice."____-%'";
        $sql.= " AND entity = ".$conf->entity;
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) { $siyymm = substr($row[0],0,6); $max=$row[0]; }
        }
        if (! $siyymm || preg_match('/'.$this->prefixinvoice.'[0-9][0-9][0-9][0-9]/i',$siyymm))
        {
            return true;
        }
        else
        {
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel',$max);
            return false;
        }
    }

    /**
     * Return next value
	 *
	 * @param	Societe		$objsoc     Object third party
	 * @param  	Object		$object		Object
     * @param   string		$mode       'next' for next value or 'last' for last value
	 * @return 	string      			Value if OK, 0 if KO
     */
    function getNextValue($objsoc,$object,$mode='next')
    {
        global $db,$conf;

        if ($object->type == 2) $prefix=$this->prefixcreditnote;
        else $prefix=$this->prefixinvoice;

        // D'abord on recupere la valeur max
        $posindice=8;
        $sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn";
        $sql.= " WHERE ref LIKE '".$prefix."____-%'";
        $sql.= " AND entity = ".$conf->entity;

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
        	$sql = "SELECT ref as ref";
        	$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn";
        	$sql.= " WHERE ref LIKE '".$prefix."____-".$num."'";
        	$sql.= " AND entity = ".$conf->entity;

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
        	$date=$object->date;	// This is invoice date (not creation date)
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

