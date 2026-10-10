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
 * \file    htdocs/modulebuilder/class/PredicateRule.class.php
 * \ingroup modulebuilder
 * \brief   Leaf of the access rule tree: one whitelisted condition on the user.
 */

require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/AccessRuleExpression.class.php';

/**
 * Leaf of the access rule tree: one whitelisted condition on the user.
 */
final class PredicateRule implements AccessRuleExpression
{
	const TYPE = 'predicate';

	const IS_ADMIN = 'isAdmin';
	const IS_INTERNAL_USER = 'isInternalUser';
	const CAN_READ_OBJECT = 'canReadObject';
	const CAN_WRITE_OBJECT = 'canWriteObject';
	const CAN_DELETE_OBJECT = 'canDeleteObject';

	/**
	 * PHP rendered for each predicate. The mymodule and myobject tokens are replaced by the generator
	 * with the names of the generated module and object.
	 */
	const PREDICATES = array(
		self::IS_ADMIN => '!empty($user->admin)',
		self::IS_INTERNAL_USER => 'empty($user->socid)',
		self::CAN_READ_OBJECT => "\$user->hasRight('mymodule', 'myobject', 'read')",
		self::CAN_WRITE_OBJECT => "\$user->hasRight('mymodule', 'myobject', 'write')",
		self::CAN_DELETE_OBJECT => "\$user->hasRight('mymodule', 'myobject', 'delete')",
	);

	/** @var string One of the keys of PREDICATES */
	private $name;

	/**
	 * @param	string	$name	Name of the predicate, one of the keys of PREDICATES
	 * @throws	\InvalidArgumentException	When the predicate is not whitelisted
	 */
	public function __construct(string $name)
	{
		if (!array_key_exists($name, self::PREDICATES)) {
			throw new \InvalidArgumentException('Unknown access rule predicate: '.$name);
		}
		$this->name = $name;
	}

	/**
	 * @return string	Name of the predicate
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function toPhp(): string
	{
		return self::PREDICATES[$this->name];
	}

	/**
	 * @return array{type:string,name:string}
	 */
	public function toArray(): array
	{
		return array('type' => self::TYPE, 'name' => $this->name);
	}
}
