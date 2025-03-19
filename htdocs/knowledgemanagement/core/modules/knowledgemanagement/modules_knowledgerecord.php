<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2012      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 *  \file			htdocs/knowledgemanagement/core/modules/knowledgemanagement/modules_knowledgerecord.php
 *  \ingroup		knowledgemanagement
 *  \brief			File that contains parent class for knowledgerecords document models and parent class for knowledgerecords numbering models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php'; // required for use by classes that inherit


/**
 *	Parent class for documents models
 */
abstract class ModelePDFKnowledgeRecord extends CommonDocGenerator
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
		global $conf;

		$type = 'knowledgerecord';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build document
	 *
	 *  @param		KnowledgeRecord	$object				Object to generate
	 *  @param		Translate		$outputlangs		Lang output object
	 *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int<0,1>		$hidedetails		Do not show line details
	 *  @param		int<0,1>		$hidedesc			Do not show desc
	 *  @param		int<0,1>		$hideref			Do not show ref
	 *  @return		int<0,1>							1=OK, 0=KO
	 */
	abstract public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0);
	// phpcs:enable
}



/**
 *  Parent class to manage numbering of KnowledgeRecord
 *
 *  @property 'development'|'experimental'|'release' $version Version of "module"
 *  @property string $name Name of "module"
 */
abstract class ModeleNumRefKnowledgeRecord
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 *	Return if a module can be used or not
	 *
	 *	@return		boolean     true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 *	Returns the default description of the numbering template
	 *
	 *  @param	Translate	$langs Object langs
	 *	@return	string	Descriptive text
	 */
	public function info($langs)
	{
		$langs->load("knowledgemanagement");
		return $langs->trans("NoDescription");
	}

	/**
	 *	Returns an example of numbering
	 *
	 *	@return     string      Example
	 */
	public function getExample()
	{
		global $langs;
		$langs->load("knowledgemanagement");
		return $langs->trans("NoExample");
	}

	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *	@param	Object		$object		Object we need next value for
	 *	@return boolean     			false if conflict, true if ok
	 */
	public function canBeActivated($object)
	{
		return true;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param  KnowledgeRecord	$object		Object we need next value for
	 *  @return string|int<-1,0>			Next value if OK, <=0 if KO
	 */
	public function getNextValue($object)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Returns version of numbering module
	 *
	 *	@return     string      Valeur
	 */
	public function getVersion()
	{
		return DOL_VERSION;
	}
}
