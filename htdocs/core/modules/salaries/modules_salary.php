<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2024      MDW                  <mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';

/**
 * Base class for Salary PDF document models
 */
abstract class ModelePDFSalary extends CommonDocGenerator
{
	/**
	 * Return list of available salary PDF models
	 *
	 * @param DoliDB $db                 Database handler
	 * @param int    $maxfilenamelength  Max filename length
	 * @return array<string,string>
	 */
	public static function listModels($db, $maxfilenamelength = 0)
	{
		$type = 'salary';

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		return getListOfModels($db, $type, $maxfilenamelength);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Legacy method required by Dolibarr PDF system
	 * Delegates to camelCase method
	 *
	 * @param DoliDB $db Database handler
	 * @return array<string,string> List of available PDF models
	 */
	public static function liste_modeles($db)
	{
		return self::listModels($db); // delegates to camelCase
	}

	/**
	 * Build the salary PDF document
	 *
	 * @param object    $object             Salary object
	 * @param Translate $outputlangs         Language object
	 * @param string    $srctemplatepath     Template path
	 * @param int       $hidedetails         Hide details
	 * @param int       $hidedesc            Hide description
	 * @param int       $hideref             Hide reference
	 * @return int
	 */
	abstract public function writeFile($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0);
}
