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
 * \file    htdocs/modulebuilder/class/RightsSyncCommand.class.php
 * \ingroup modulebuilder
 * \brief   Immutable request describing one permissions sync to perform on a module descriptor.
 */

/**
 * Immutable request describing one permissions sync to perform on a module descriptor.
 *
 * ModuleBuilder triggers project onto two axes, not one: a scope (the three CRUD rights of an
 * object, or a single right) and an action. The five real triggers are the five named factories
 * below; there is no object-scoped update.
 *
 * The command deliberately carries the current rights array along with the intent. That keeps
 * RightsSyncService free of $db, dol_include_once() and globals, so it stays unit-testable
 * against a throwaway fixture file.
 */
final class RightsSyncCommand
{
	const ACTION_ADD = 'add';
	const ACTION_UPDATE = 'update';
	const ACTION_DELETE = 'delete';

	const SCOPE_OBJECT = 'object';
	const SCOPE_RIGHT = 'right';

	/** @var string Module name, any casing */
	public $module;

	/** @var string Path to the mod<Module>.class.php descriptor to patch */
	public $descriptorFile;

	/** @var array<int,array<int,string>> Rights array read from the instantiated descriptor */
	public $permissions;

	/** @var string Object the command applies to, compared case-insensitively. Empty for right deletion. */
	public $objectName;

	/** @var string One of SCOPE_OBJECT, SCOPE_RIGHT */
	public $scope;

	/** @var string One of ACTION_ADD, ACTION_UPDATE, ACTION_DELETE */
	public $actionType;

	/** @var int|null Index in $permissions. Required for right-scoped update and delete. */
	public $rightKey;

	/** @var string|null Permission label. Required for right-scoped add and update. */
	public $rightLabel;

	/** @var string|null Permission crud code. Required for right-scoped add and update. */
	public $rightCrud;

	/**
	 * @param string                       $module         Module name
	 * @param string                       $descriptorFile Path to the descriptor to patch
	 * @param array<int,array<int,string>> $permissions    Current rights array
	 * @param string                       $objectName     Target object, may be empty for right deletion
	 * @param string                       $scope          One of SCOPE_*
	 * @param string                       $actionType     One of ACTION_*
	 * @param int|null                     $rightKey       Index in $permissions
	 * @param string|null                  $rightLabel     Permission label
	 * @param string|null                  $rightCrud      Permission crud code
	 * @throws \InvalidArgumentException When the command is incomplete for its scope and action
	 */
	private function __construct(
		string $module,
		string $descriptorFile,
		array $permissions,
		string $objectName,
		string $scope,
		string $actionType,
		?int $rightKey = null,
		?string $rightLabel = null,
		?string $rightCrud = null
	) {
		if ($module === '') {
			throw new \InvalidArgumentException('RightsSyncCommand requires a module name');
		}
		if ($descriptorFile === '') {
			throw new \InvalidArgumentException('RightsSyncCommand requires a descriptor file path');
		}
		if (!in_array($scope, array(self::SCOPE_OBJECT, self::SCOPE_RIGHT), true)) {
			throw new \InvalidArgumentException('RightsSyncCommand got an unknown scope: '.$scope);
		}
		if (!in_array($actionType, array(self::ACTION_ADD, self::ACTION_UPDATE, self::ACTION_DELETE), true)) {
			throw new \InvalidArgumentException('RightsSyncCommand got an unknown action: '.$actionType);
		}
		if ($scope === self::SCOPE_OBJECT) {
			if ($objectName === '') {
				throw new \InvalidArgumentException('An object-scoped RightsSyncCommand requires an object name');
			}
			if ($actionType === self::ACTION_UPDATE) {
				throw new \InvalidArgumentException('There is no object-scoped update in ModuleBuilder');
			}
		}
		if ($scope === self::SCOPE_RIGHT && $actionType !== self::ACTION_ADD && $rightKey === null) {
			throw new \InvalidArgumentException('A right-scoped '.$actionType.' requires a right key');
		}
		if ($scope === self::SCOPE_RIGHT && $actionType !== self::ACTION_DELETE && ($rightLabel === null || $rightCrud === null)) {
			throw new \InvalidArgumentException('A right-scoped '.$actionType.' requires a label and a crud code');
		}

		$this->module = $module;
		$this->descriptorFile = $descriptorFile;
		$this->permissions = $permissions;
		$this->objectName = $objectName;
		$this->scope = $scope;
		$this->actionType = $actionType;
		$this->rightKey = $rightKey;
		$this->rightLabel = $rightLabel;
		$this->rightCrud = $rightCrud;
	}

	/**
	 * Declare the three CRUD rights of a newly generated object.
	 *
	 * @param string                       $module         Module name
	 * @param string                       $descriptorFile Path to the descriptor to patch
	 * @param array<int,array<int,string>> $permissions    Current rights array
	 * @param string                       $objectName     Object being generated
	 * @return self
	 */
	public static function forObjectCreation(string $module, string $descriptorFile, array $permissions, string $objectName): self
	{
		return new self($module, $descriptorFile, $permissions, $objectName, self::SCOPE_OBJECT, self::ACTION_ADD);
	}

	/**
	 * Drop every right attached to an object being deleted.
	 *
	 * @param string                       $module         Module name
	 * @param string                       $descriptorFile Path to the descriptor to patch
	 * @param array<int,array<int,string>> $permissions    Current rights array
	 * @param string                       $objectName     Object being deleted
	 * @return self
	 */
	public static function forObjectDeletion(string $module, string $descriptorFile, array $permissions, string $objectName): self
	{
		return new self($module, $descriptorFile, $permissions, $objectName, self::SCOPE_OBJECT, self::ACTION_DELETE);
	}

	/**
	 * Append one right to the descriptor.
	 *
	 * @param string                       $module         Module name
	 * @param string                       $descriptorFile Path to the descriptor to patch
	 * @param array<int,array<int,string>> $permissions    Current rights array
	 * @param string                       $objectName     Object the right belongs to
	 * @param string                       $label          Permission label
	 * @param string                       $crud           Permission crud code
	 * @return self
	 */
	public static function forRightAddition(string $module, string $descriptorFile, array $permissions, string $objectName, string $label, string $crud): self
	{
		return new self($module, $descriptorFile, $permissions, $objectName, self::SCOPE_RIGHT, self::ACTION_ADD, null, $label, $crud);
	}

	/**
	 * Replace one right, addressed by its index in the rights array.
	 *
	 * @param string                       $module         Module name
	 * @param string                       $descriptorFile Path to the descriptor to patch
	 * @param array<int,array<int,string>> $permissions    Current rights array
	 * @param int                          $rightKey       Index of the right to replace
	 * @param string                       $objectName     Object the right belongs to
	 * @param string                       $label          New permission label
	 * @param string                       $crud           New permission crud code
	 * @return self
	 */
	public static function forRightUpdate(string $module, string $descriptorFile, array $permissions, int $rightKey, string $objectName, string $label, string $crud): self
	{
		return new self($module, $descriptorFile, $permissions, $objectName, self::SCOPE_RIGHT, self::ACTION_UPDATE, $rightKey, $label, $crud);
	}

	/**
	 * Remove one right, addressed by its index in the rights array.
	 *
	 * @param string                       $module         Module name
	 * @param string                       $descriptorFile Path to the descriptor to patch
	 * @param array<int,array<int,string>> $permissions    Current rights array
	 * @param int                          $rightKey       Index of the right to remove
	 * @return self
	 */
	public static function forRightDeletion(string $module, string $descriptorFile, array $permissions, int $rightKey): self
	{
		return new self($module, $descriptorFile, $permissions, '', self::SCOPE_RIGHT, self::ACTION_DELETE, $rightKey);
	}
}
