<?php
/*
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

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandlerInterface.php';

/**
 * Parent class for log handlers
 */
class LogHandler
{
	protected $ident=0;


	/**
	 * Content of the info tooltip.
	 *
	 * @return string
	 */
	public function getInfo()
	{
		return '';
	}

	/**
	 * Return warning if something is wrong with logger
	 *
	 * @return string
	 */
	public function getWarning()
	{
		return '';
	}

	/**
	 * Version of the module ('x.y.z' or 'dolibarr' or 'experimental' or 'development')
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return 'development';
	}

	/**
	 * Is the module active ?
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return false;
	}

	/**
	 * Configuration variables of the module
	 *
	 * @return array
	 */
	public function configure()
	{
		return array();
	}

	/**
	 * Function that checks if the configuration is valid.
	 * It will be called after setting the configuration.
	 * The function returns an array with error messages
	 *
	 * @return array
	 */
	public function checkConfiguration()
	{
		return array();
	}

	/**
	 * Set current ident.
	 *
     * @param	int		$ident		1=Increase ident of 1, -1=Decrease ident of 1
	 * @return 	void
	 */
	public function setIdent($ident)
	{
		$this->ident+=$ident;
	}
}
