<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *  \file       htdocs/core/modules/member/modules_member.class.php
 *  \ingroup    members
 *  \brief      File with parent class for generating members to PDF
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';


/**
 *	Parent class to manage intervention document templates
 */
abstract class ModelePDFMember extends CommonDocGenerator
{
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param  DoliDB  	$db                 Database handler
	 *  @param  int<0,max>	$maxfilenamelength  Max length of value to show
	 *  @return string[]|int<-1,0>				List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		$type = 'member';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);
		return $list;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build a document
	 *
	 *	@param	Adherent	$object				Object source to build document
	 *	@param	Translate	$outputlangs		Lang output object
	 * 	@param	string		$srctemplatepath	Full path of source filename for generator using a template file
	 *	@param	string		$mode				Tell if doc module is called for 'member', ...
	 *  @param  int<0,1>	$nooutput           1=Generate only file on disk and do not return it on response
	 *  @param	string		$filename			Name of output file (without extension)
	 *	@return	int<-1,1>        				1 if OK, <=0 if KO
	 */
	abstract public function write_file($object, $outputlangs, $srctemplatepath = '', $mode = 'member', $nooutput = 0, $filename = 'tmp_cards');
	// phpcs:enable
}



/**
 *  Class mere des modeles de numerotation des references de members
 */
abstract class ModeleNumRefMembers extends CommonNumRefGenerator
{
	/**
	 *  Return description of module parameters
	 *
	 *  @param	Translate	$langs      Output language
	 *  @param	?Societe	$soc		Third party object
	 *  @return	string					HTML translated description
	 */
	public function getToolTip($langs, $soc)
	{
		$langs->loadLangs(array("admin", "companies"));

		$strikestart = '';
		$strikeend = '';
		if (getDolGlobalString('MAIN_MEMBER_CODE_ALWAYS_REQUIRED') && !empty($this->code_null)) {
			$strikestart = '<strike>';
			$strikeend = '</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
		}

		$s = '';
		$s .= $langs->trans("Name").': <b>'.$this->getName($langs).'</b><br>';
		$s .= $langs->trans("Version").': <b>'.$this->getVersion().'</b><br>';
		$s .= $langs->trans("MemberCodeDesc").'<br>';
		$s .= $langs->trans("ValidityControledByModule").': <b>'.$this->getName($langs).'</b><br>';
		$s .= '<br>';
		$s .= '<u>'.$langs->trans("ThisIsModuleRules").':</u><br>';

		$s .= $langs->trans("Required").': '.$strikestart;
		$s .= yn(!$this->code_null, 1, 2).$strikeend;
		$s .= '<br>';
		$s .= $langs->trans("CanBeModifiedIfOk").': ';
		$s .= yn($this->code_modifiable, 1, 2);
		$s .= '<br>';
		$s .= $langs->trans("CanBeModifiedIfKo").': '.yn($this->code_modifiable_invalide, 1, 2).'<br>';
		$s .= $langs->trans("AutomaticCode").': '.yn($this->code_auto, 1, 2).'<br>';
		$s .= '<br>';
		$nextval = $this->getNextValue($soc, null);
		if (empty($nextval)) {
			$nextval = $langs->trans("Undefined");
		}
		$s .= $langs->trans("NextValue").' ('.$langs->trans("Member").'): <b>'.$nextval.'</b><br>';

		return $s;
	}

	/**
	 *  Return next value
	 *
	 *  @param  Societe		$objsoc		Object third party
	 *  @param  ?Adherent	$object		Object we need next value for
	 *  @return	string|int<-1,0>		next value
	 */
	public function getNextValue($objsoc, $object)
	{
		return '';
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	abstract public function getExample();
}
