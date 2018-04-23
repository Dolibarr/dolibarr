<?php
/* Copyright (C) 2014 Marcos García         <marcosgdf@gmail.com>
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
 */

/**
 * Class that all the triggers must extend
 */
abstract class DolibarrTriggers
{

	/**
	 * Database handler
	 * @var DoliDB
	 */
	protected $db;

	/**
	 * Name of the trigger
	 * @var mixed|string
	 */
	public $name = '';

	/**
	 * Description of the trigger
	 * @var string
	 */
	public $description = '';

	/**
	 * Version of the trigger
	 * @var string
	 */
	public $version = self::VERSION_DEVELOPMENT;

	/**
	 * Image of the trigger
	 * @var string
	 */
	public $picto = 'technic';

	/**
	 * Category of the trigger
	 * @var string
	 */
	public $family = '';

	/**
	 * Error reported by the trigger
	 * @var string
	 * @deprecated Use $this->errors
	 * @see errors
	 */
	public $error = '';

	/**
	 * Errors reported by the trigger
	 * @var array
	 */
	public $errors = array();

	const VERSION_DEVELOPMENT = 'development';
	const VERSION_EXPERIMENTAL = 'experimental';
	const VERSION_DOLIBARR = 'dolibarr';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db) {

		$this->db = $db;

		if (empty($this->name)) 
		{
			$this->name = preg_replace('/^Interface/i', '', get_class($this));
		}
	}

	/**
	 * Returns the name of the trigger file
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the description of trigger file
	 *
	 * @return string
	 */
	public function getDesc()
	{
		return $this->description;
	}

	/**
	 * Returns the version of the trigger file
	 *
	 * @return string Version of trigger file
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == self::VERSION_DEVELOPMENT) {
			return $langs->trans("VersionDevelopment");
		} elseif ($this->version == self::VERSION_EXPERIMENTAL) {
			return $langs->trans("VersionExperimental");
		} elseif ($this->version == self::VERSION_DOLIBARR) {
			return DOL_VERSION;
		} elseif ($this->version) {
			return $this->version;
		} else {
			return $langs->trans("Unknown");
		}
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	abstract function runTrigger($action, $object, User $user, Translate $langs, Conf $conf);

}
