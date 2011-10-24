<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/includes/modules/expedition/methode_expedition.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier contenant la classe mere de generation de bon de livraison en PDF
 *				et la classe mere de numerotation des bons de livraisons
 */
require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');


/**
 *	\class      ModeleShippingMethod
 *	\brief      Parent class for shipping method classes
 */
class ModeleShippingMethod
{

	function ModeleShippingMethod($db=0)
	{
		$this->db = $db;
		$this->name = "NOT DEFINED";
		$this->description = "ERROR IN MODULE DESCRIPTION";
	}


	/**
	 *      Return list of active generation modules
	 * 		@param		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='???';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');

		return $liste;
	}
}

?>
