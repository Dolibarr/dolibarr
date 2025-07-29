<?php
/* Copyright (C) 2023 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/commonhookactions.class.php
 *	\ingroup    core
 *	\brief      File of parent class of all other hook actions classes
 */


/**
 *	Parent class of all other hook actions classes
 */
abstract class CommonHookActions
{
	/**
	 * @var ?string	String of results.
	 */
	public $resprints;

	/**
	 * @var array<mixed|mixed[]> 	Array of results.
	 */
	public $results = array();

	/**
	 * @var string
	 */
	public $error;

	/**
	 * @var string[]
	 */
	public $errors = array();

	/**
	 * Check context of hook
	 * @param array<string,mixed> $parameters Hook parameters.
	 * @param string[]|string $allContexts Context to check
	 * @return bool
	 */
	protected function isContext($parameters, $allContexts)
	{
		if (is_array($allContexts)) {
			foreach ($allContexts as $context) {
				if ($this->isContext($parameters, $context)) {
					return true;
				}
			}
			return false;
		}
		if ($parameters['currentcontext'] == $allContexts) {
			return true;
		}
		$contexts = explode(':', $parameters['context']);
		return in_array($allContexts, $contexts);
	}
}
