<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * \file    htdocs/modulebuilder/class/AccessRuleEvaluator.class.php
 * \ingroup modulebuilder
 * \brief   Decides whether a user may access an object according to an access rule.
 */

/**
 * Decides whether a user may access an object according to an access rule.
 */
interface AccessRuleEvaluator
{
	/**
	 * @param	User			$user	User asking for the access
	 * @param	CommonObject	$obj	Object to access, it provides the module and element of the permissions
	 * @return	bool					True when the access is granted, false otherwise, including on failure
	 */
	public function evaluate(User $user, CommonObject $obj): bool;
}
