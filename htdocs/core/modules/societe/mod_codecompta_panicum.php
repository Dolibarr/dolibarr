<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/modules/societe/mod_codecompta_panicum.php
 *      \ingroup    societe
 *      \brief      File of class to manage accountancy code of thirdparties with Panicum rules
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';


/**
 *		Class to manage accountancy code of thirdparties with Panicum rules
 */
class mod_codecompta_panicum extends ModeleAccountancyCode
{
	var $nom='Panicum';
	var $name='Panicum';
	var $version='dolibarr';        // 'development', 'experimental', 'dolibarr'


	/**
	 * 	Constructor
	 */
	function __construct()
	{
	}


	/**
	 * Return description of module
	 *
	 * @param	Translate	$langs	Object langs
	 * @return 	string      		Description of module
	 */
	function info($langs)
	{
		return $langs->trans("ModuleCompanyCode".$this->name);
	}

	/**
	 *  Return an example of result returned by getNextValue
	 *
	 *  @param	Translate	$langs		Object langs
	 *  @param	Societe		$objsoc		Object thirdparty
	 *  @param	int			$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 *  @return	string					Example
	 */
	function getExample($langs,$objsoc=0,$type=-1)
	{
		return '';
	}

	/**
	 *  Set accountancy account code for a third party into this->code
	 *
	 *  @param	DoliDB	$db              Database handler
	 *  @param  Societe	$societe         Third party object
	 *  @param  int		$type			'customer' or 'supplier'
	 *  @return	int						>=0 if OK, <0 if KO
	 */
	function get_code($db, $societe, $type='')
	{
		$this->code='';

		if (is_object($societe)) {
			if ($type == 'supplier') $this->code = (! empty($societe->code_compta_fournisseur)?$societe->code_compta_fournisseur:'');
			else $this->code = (! empty($societe->code_compta)?$societe->code_compta:'');
		}

		return 0; // return ok
	}
}

