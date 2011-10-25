<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/core/modules/facture/mod_facture_terre.php
 *	\ingroup    facture
 *	\brief      File containing class for numbering module Terre
 */
require_once(DOL_DOCUMENT_ROOT ."/core/modules/facture/modules_facture.php");

/**	    \class      mod_facture_terre
 *		\brief      Classe du modele de numerotation de reference de facture Terre
 */
class mod_facture_terre extends ModeleNumRefFactures
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefixinvoice='FA';
	var $prefixcreditnote='AV';
	var $error='';

	/**     \brief      Renvoi la description du modele de numerotation
	 *      \return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans('TerreNumRefModelDesc1',$this->prefixinvoice,$this->prefixcreditnote);
	}

	/**     \brief      Renvoi un exemple de numerotation
	 *      \return     string      Example
	 */
	function getExample()
	{
		return $this->prefixinvoice."0501-0001";
	}

	/**     \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *                  de conflits qui empechera cette numerotation de fonctionner.
	 *      \return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $langs,$conf;

		$langs->load("bills");

		// Check invoice num
		$fayymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(facnumber FROM ".$posindice.")) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber LIKE '".$this->prefixinvoice."____-%'";
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
		$sql.= " WHERE facnumber LIKE '".$this->prefixcreditnote."____-%'";
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

	/**     Return next value not used or last value used
	 *      @param     objsoc		Object third party
	 *      @param     facture		Object invoice
     *      @param     mode         'next' for next value or 'last' for last value
	 *      @return    string       Value
	 */
	function getNextValue($objsoc,$facture,$mode='next')
	{
		global $db,$conf;

		if ($facture->type == 2) $prefix=$this->prefixcreditnote;
		else $prefix=$this->prefixinvoice;

		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(facnumber FROM ".$posindice.")) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber LIKE '".$prefix."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		dol_syslog("mod_facture_terre::getNextValue sql=".$sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}
		else
		{
			dol_syslog("mod_facture_terre::getNextValue sql=".$sql, LOG_ERR);
			return -1;
		}

		if ($mode == 'last')
		{
            $num = sprintf("%04s",$max);

            $ref='';
            $sql = "SELECT facnumber as ref";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture";
            $sql.= " WHERE facnumber LIKE '".$prefix."____-".$num."'";
            $sql.= " AND entity = ".$conf->entity;

            dol_syslog("mod_facture_terre::getNextValue sql=".$sql);
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
    		$date=$facture->date;	// This is invoice date (not creation date)
    		$yymm = strftime("%y%m",$date);
    		$num = sprintf("%04s",$max+1);

    		dol_syslog("mod_facture_terre::getNextValue return ".$prefix.$yymm."-".$num);
    		return $prefix.$yymm."-".$num;
		}
		else dol_print_error('','Bad parameter for getNextValue');
	}

	/**		Return next free value
	 *     	@param      objsoc      Object third party
	 * 		@param		objforref	Object for number to search
     *      @param      mode        'next' for next value or 'last' for last value
	 *   	@return     string      Next free value
	 */
	function getNumRef($objsoc,$objforref,$mode='next')
	{
		return $this->getNextValue($objsoc,$objforref,$mode);
	}

}

?>
