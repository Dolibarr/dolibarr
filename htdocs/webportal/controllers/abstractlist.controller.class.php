<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
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

require_once __DIR__ . '/../class/controller.class.php';

/**
 * \file        htdocs/webportal/controllers/abstractlist.controller.class.php
 * \ingroup     webportal
 * \brief       This file is an abstract controller with shared logic to display a list
 */

/**
 * Class for AbstractListController
 */
abstract class AbstractListController extends Controller
{
	/**
	 * @var FormListWebPortal Form for list
	 */
	public $formList;

	/**
	 * Set array fields
	 *
	 * @return	void
	 */
	public function listSetArrayFields()
	{
	}

	/**
	 * Set search values
	 *
	 * @param	bool		$clear		Clear search values
	 * @return	void
	 */
	public function listSetSearchValues($clear = false)
	{
	}

	/**
	 * Called before print value for list
	 *
	 * @param	string					$field_key		Field key
	 * @param	array<string,mixed>		$field_spec		Field specification
	 * @param	stdClass				$record			Contain data of object from database
	 * @return	string									HTML input
	 */
	public function listPrintValueBefore($field_key, $field_spec, &$record)
	{
		return '';
	}

	/**
	 * Called after print value for list
	 *
	 * @param	string					$field_key		Field key
	 * @param	array<string,mixed>		$field_spec		Field specification
	 * @param	stdClass				$record			Contain data of object from database
	 * @param	string					$out			Current HTML input
	 * @return	string									HTML input
	 */
	public function listPrintValueAfter($field_key, $field_spec, &$record, $out)
	{
		return $out;
	}
}
