<?php
/* Copyright (C) 2015 Laurent Destailleur    <eldy@users.sourceforge.net>
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

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Parent class for trips and expenses templates
 */
class ModeleExpenseReport extends CommonDocGenerator
{
	var $error='';


	/**
	 *  Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  string	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='expensereport';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
	}

}

/**
 * expensereport_pdf_create
 *
 *  @param	    DoliDB		$db  			Database handler
 *  @param	    Object		$object			Object order
 *  @param	    string		$modele			Force le modele a utiliser ('' to not force)
 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
 *  @param      int			$hidedetails    Hide details of lines
 *  @param      int			$hidedesc       Hide description
 *  @param      int			$hideref        Hide ref
 *  @return     int         				0 if KO, 1 if OK
 *  @return number
 */
function expensereport_pdf_create(DoliDB $db, ExpenseReport $object, $message, $modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
{
	return $object->generateDocument($modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
}
