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
 *    	\file       htdocs/core/modules/supplier_order/mod_facture_fournisseur_cactus.php
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
	var $prefix='SI';


    /**
     * 	Return description of numbering model
     *
     *  @return     string      Text with description
     */
    function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc",$this->prefix);
    }


    /**
     * 	Returns a numbering example
     *
     *  @return     string      Example
     */
    function getExample()
    {
        return $this->prefix."1301-0001";
    }


    /**
     * 	Tests if the numbers already in force in the database do not cause conflicts that would prevent this numbering.
     *
     *  @return     boolean     false if conflict, true if ok
     */
    function canBeActivated()
    {
    	global $conf,$langs;

        $siyymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn";
		$sql.= " WHERE ref LIKE '".$this->prefix."____-%'";
        $sql.= " AND entity = ".$conf->entity;
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) { $siyymm = substr($row[0],0,6); $max=$row[0]; }
        }
        if (! $siyymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i',$siyymm))
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
     * 	Return next value
	 *
	 *  @param	Societe		$objsoc     Object third party
	 *  @param  Object		$object		Object
	 *  @return string      			Value if OK, 0 if KO
     */
    function getNextValue($objsoc=0,$object='')
    {
        global $db,$conf;

        // D'abord on recupere la valeur max
        $posindice=8;
        $sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn";
		$sql.= " WHERE ref like '".$this->prefix."____-%'";
        $sql.= " AND entity = ".$conf->entity;

        $resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) $max = intval($obj->max);
            else $max=0;
        }

		//$date=time();
        $date=$object->datec;   // Not always defined
        if (empty($date)) $date=$object->date;  // Creation date is invoice date for suppliers invoices
        $yymm = strftime("%y%m",$date);
        $num = sprintf("%04s",$max+1);

        return $this->prefix.$yymm."-".$num;
    }


    /**
     * 	Renvoie la reference de facture suivante non utilisee
     *
	 *  @param	Societe		$objsoc     Object third party
	 *  @param  Object	    $object		Object
     *  @return string      			Texte descripif
     */
    function invoice_get_num($objsoc=0,$object='')
    {
        return $this->getNextValue($objsoc,$object);
    }
}

?>
