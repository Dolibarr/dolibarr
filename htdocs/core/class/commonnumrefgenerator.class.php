<?php
/* Copyright (C) 2023	Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/core/class/commonnumrefgenerator.class.php
 *		\ingroup    core
 *		\brief      File of parent class for num ref generators
 */


/**
 *	Parent class for number ref generators
 */
abstract class CommonNumRefGenerator
{
	/**
	 * @var string Model name
	 */
	public $name = '';

	/**
	 * @var string Version
	 */
	public $version = '';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[]    Array of error strings
	 */
	public $errors = array();

	/**
	 * @var DoliDB Database handler.
	 */
	protected $db;


	/**     Return model name
	 * 		TODO Replace with getName()
	 *
	 *		@param	Translate	$langs		Object langs
	 *      @return string      			Model name
	 */
	public function getNom($langs)
	{
		return empty($this->name) ? get_class($this) : $this->name;
	}

	/**     Return model name
	 *
	 *		@param	Translate	$langs		Object langs
	 *      @return string      			Model name
	 */
	public function getName($langs)
	{
		return empty($this->name) ? get_class($this) : $this->name;
	}

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
	 *	@param		Translate	$langs		Language
	 *	@return     string      			Descriptive text
	 */
	public function info($langs)
	{
		return $langs->trans("NoDescription");
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
	 *	Returns version of numbering module
	 *
	 *	@return     string      Valeur
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') {
			return $langs->trans("VersionDevelopment");
		}
		if ($this->version == 'experimental') {
			return $langs->trans("VersionExperimental");
		}
		if ($this->version == 'dolibarr') {
			return DOL_VERSION;
		}
		if ($this->version) {
			return $this->version;
		}
		return $langs->trans("NotAvailable");
	}
}
