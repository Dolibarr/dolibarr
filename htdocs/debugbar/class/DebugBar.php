<?php
/* Copyright (C) 2023	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 */

/**
 *	\file       htdocs/debugbar/class/DebugBar.php
 *	\brief      Class for debugbar
 *	\ingroup    debugbar
 */

dol_include_once('/debugbar/class/autoloader.php');

use DebugBar\DebugBar;

dol_include_once('/debugbar/class/DataCollector/DolRequestDataCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolConfigCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolTimeDataCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolMemoryCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolPhpCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolExceptionsCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolQueryCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolibarrCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolLogsCollector.php');
dol_include_once('/debugbar/class/DataCollector/DolHooksCollector.php');

/**
 * DolibarrDebugBar class
 *
 * @see http://phpdebugbar.com/docs/base-collectors.html#base-collectors
 */

class DolibarrDebugBar extends DebugBar
{
	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		//$this->addCollector(new PhpInfoCollector());
		//$this->addCollector(new DolMessagesCollector());
		$this->addCollector(new DolRequestDataCollector());
		//$this->addCollector(new DolConfigCollector());      // Disabled for security purpose
		$this->addCollector(new DolTimeDataCollector());
		$this->addCollector(new PhpCollector());
		$this->addCollector(new DolMemoryCollector());
		//$this->addCollector(new DolExceptionsCollector());
		$this->addCollector(new DolQueryCollector());
		$this->addCollector(new DolibarrCollector());
		$this->addCollector(new DolHooksCollector());
		if (isModEnabled('syslog')) {
			$this->addCollector(new DolLogsCollector());
		}
	}

	/**
	 * Returns a JavascriptRenderer for this instance
	 *
	 * @param string $baseUrl Base url
	 * @param string $basePath Base path
	 * @return \DebugBar\JavascriptRenderer      String content
	 */
	public function getJavascriptRenderer($baseUrl = null, $basePath = null)
	{
		if ($baseUrl === null) {
			$baseUrl = DOL_URL_ROOT.'/includes/maximebf/debugbar/src/DebugBar/Resources';
		}
		$renderer = parent::getJavascriptRenderer($baseUrl, $basePath);
		$renderer->disableVendor('jquery');			// We already have jquery loaded globally by the main.inc.php
		$renderer->disableVendor('fontawesome');	// We already have fontawesome loaded globally by the main.inc.php
		$renderer->disableVendor('highlightjs');	// We don't need this
		$renderer->setEnableJqueryNoConflict(false);	// We don't need no conflict

		return $renderer;
	}

	/**
	 * Returns a JavascriptRenderer for this instance
	 *
	 * @return \DebugBar\JavascriptRenderer      String content
	 * @deprecated Use getJavascriptRenderer
	 */
	public function getRenderer()
	{
		return $this->getJavascriptRenderer();
	}
}
