<?php
/* Copyright (C) 2010 Regis Houssin  <regis.houssin@inodbox.com>
 * Copyright (C) 2010 Florian Henry  <florian.henry<àopen-concept.pro>
 * Copyright (C) 2014 Marcos García  <marcosgdf@gmail.com>
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
 *		\file       htdocs/core/modules/project/task/modules_task.php
 *      \ingroup    project
 *      \brief      File that contain parent class for task models
 *                  and parent class for task numbering models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';


/**
 *	Parent class for task models
 */
abstract class ModelePDFTask extends CommonDocGenerator
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
		$type = 'project_task';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Function to build a document on disk using the generic odt module.
	 *
	 *	@param	Project		$object					Object source to build document
	 *	@param	Translate	$outputlangs			Lang output object
	 * 	@param	string		$srctemplatepath		Full path of source filename for generator using a template file
	 *	@return	int<-1,1>							1 if OK, <=0 if KO
	 */
	abstract public function write_file($object, $outputlangs, $srctemplatepath = '');
	// phpcs:enable
}



/**
 * Parent class of task reference numbering models
 */
abstract class ModeleNumRefTask extends CommonNumRefGenerator
{
	/**
	 *  Return next value
	 *
	 *  @param	null|Societe|string	$objsoc	Object third party
	 *  @param	null|Task|string	$object	Object Task
	 *  @return	string|int<-1,0>			Value if OK, <=0 if KO
	 */
	abstract public function getNextValue($objsoc, $object);

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	abstract public function getExample();
}
