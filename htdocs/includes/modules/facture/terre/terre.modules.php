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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 \file       htdocs/includes/modules/facture/terre/terre.modules.php
 \ingroup    facture
 \brief      Fichier contenant la classe du mod�le de num�rotation de r�f�rence de facture Terre
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");

/**	    \class      mod_facture_terre
 \brief      Classe du mod�le de num�rotation de r�f�rence de facture Terre
 */

class mod_facture_terre extends ModeleNumRefFactures
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefixinvoice='FA';
	var $prefixcreditnote='AV';
	var $error='';

	/**     \brief      Renvoi la description du modele de num�rotation
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
	 *                  de conflits qui empechera cette num�rotation de fonctionner.
	 *      \return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $langs,$conf;

		$langs->load("bills");

		// Check invoice num
		$fayymm='';

		$sql = "SELECT MAX(facnumber)";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber like '".$this->prefixinvoice."%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) $fayymm = substr($row[0],0,6);
		}
		if ($fayymm && ! eregi($this->prefixinvoice.'[0-9][0-9][0-9][0-9]',$fayymm))
		{
			$this->error=$langs->trans('TerreNumRefModelError');
			return false;
		}

		// Check credit note num
		$fayymm='';

		$sql = "SELECT MAX(facnumber)";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber like '".$this->prefixcreditnote."%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) $fayymm = substr($row[0],0,6);
		}
		if ($fayymm && ! eregi($this->prefixcreditnote.'[0-9][0-9][0-9][0-9]',$fayymm))
		{
			$this->error=$langs->trans('TerreNumRefModelError');
			return false;
		}

		return true;
	}

	/**     \brief      Renvoi prochaine valeur attribuee
	 *      \param      objsoc		Objet societe
	 *      \param      facture		Objet facture
	 *      \return     string      Valeur
	 */
	function getNextValue($objsoc,$facture)
	{
		global $db,$conf;

		if ($facture->type == 2) $prefix=$this->prefixcreditnote;
		else $prefix=$this->prefixinvoice;

		// D'abord on recupere la valeur max (reponse immediate car champ ind�x�)
		$posindice=8;

		$sql = "SELECT MAX(0+SUBSTRING(facnumber,".$posindice.")) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber like '".$prefix."%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		dol_syslog("mod_facture_terre::getNextValue sql=".$sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = $obj->max;
			else $max=0;
		}
		else
		{
			dol_syslog("mod_facture_terre::getNextValue sql=".$sql, LOG_ERR);
			return -1;
		}

		//$date=time();
		$date=$facture->date;
		$yymm = strftime("%y%m",$date);
		$num = sprintf("%04s",$max+1);

		dol_syslog("mod_facture_terre::getNextValue return ".$prefix.$yymm."-".$num);
		return $prefix.$yymm."-".$num;
	}

	/**		\brief      Return next free value
	 *     	\param      objsoc      Object third party
	 * 		\param		objforref	Object for number to search
	 *   	\return     string      Next free value
	 */
	function getNumRef($objsoc,$objforref)
	{
		return $this->getNextValue($objsoc,$objforref);
	}

}

?>
