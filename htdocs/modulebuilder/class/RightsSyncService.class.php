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
 * \file    htdocs/modulebuilder/class/RightsSyncService.class.php
 * \ingroup modulebuilder
 * \brief   Keeps the permissions section of a module descriptor in sync with ModuleBuilder actions.
 */

require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/RightsSyncCommand.class.php';
require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/SyncReport.class.php';
require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/PermissionsBlock.class.php';

/**
 * Keeps the permissions section of a module descriptor in sync with ModuleBuilder actions.
 */
interface RightsSyncService
{
	/**
	 * Apply one sync command to a module descriptor.
	 *
	 * @param RightsSyncCommand $cmd Command to apply
	 * @return SyncReport Outcome — a report carrying conflicts means nothing was written
	 */
	public function sync(RightsSyncCommand $cmd): SyncReport;
}

/**
 * Syncs rights straight into the mod<Module>.class.php descriptor file.
 *
 * Error-first: every reason not to write is collected before the rights array is touched, and a
 * single conflict aborts the whole run without writing. A half-written permissions section is
 * worse than a stale one.
 */
final class DescriptorRightsSyncService implements RightsSyncService
{
	/** @var array<string,string> Label template of each standard crud code */
	private const CRUD_LABELS = array(
		'read' => 'Read %s object of %s',
		'write' => 'Create/Update %s object of %s',
		'delete' => 'Delete %s object of %s',
	);

	/** @var int Rights array index holding the permission label */
	private const INDEX_LABEL = 1;

	/** @var int Rights array index holding the object name */
	private const INDEX_OBJECT = 4;

	/** @var int Rights array index holding the crud code */
	private const INDEX_CRUD = 5;

	/**
	 * Apply one sync command to a module descriptor.
	 *
	 * @param RightsSyncCommand $cmd Command to apply
	 * @return SyncReport Outcome — a report carrying conflicts means nothing was written
	 */
	public function sync(RightsSyncCommand $cmd): SyncReport
	{
		try {
			$block = PermissionsBlock::fromFile($cmd->descriptorFile);
		} catch (\RuntimeException $e) {
			dol_syslog('DescriptorRightsSyncService::sync '.$e->getMessage(), LOG_WARNING);
			return new SyncReport(0, 0, array($e->getMessage()));
		}

		$conflicts = array_merge(
			$block->detectTextConflicts(),
			$block->detectRightsShapeConflicts($cmd->permissions)
		);
		if (!empty($conflicts)) {
			dol_syslog(
				'DescriptorRightsSyncService::sync refused to rewrite '.$cmd->descriptorFile.': '
				.implode('; ', array_slice($conflicts, 0, 3)),
				LOG_WARNING
			);
			return new SyncReport(0, 0, $conflicts);
		}

		$warnings = $block->detectRightsShapeWarnings($cmd->permissions);

		try {
			$permissions = $this->applyCommand($cmd);
		} catch (\InvalidArgumentException $e) {
			dol_syslog('DescriptorRightsSyncService::sync '.$e->getMessage(), LOG_WARNING);
			return new SyncReport(0, 0, array($e->getMessage()), $warnings);
		}
		if ($permissions === null) {
			return new SyncReport(0, 1, array(), $warnings);
		}

		// The shape check above ran on the incoming rights; the command may itself produce an
		// unusable one, e.g. a right attached to no object, which hasRight() could never match.
		$producedConflicts = $block->detectRightsShapeConflicts($permissions);
		if (!empty($producedConflicts)) {
			dol_syslog(
				'DescriptorRightsSyncService::sync refused to write an unusable right into '.$cmd->descriptorFile.': '
				.implode('; ', array_slice($producedConflicts, 0, 3)),
				LOG_WARNING
			);
			return new SyncReport(0, 0, $producedConflicts, $warnings);
		}

		$newBlock = $block->render($permissions);
		if ($block->write($newBlock) < 0) {
			return new SyncReport(0, 0, array('Failed to write the permissions section of '.$cmd->descriptorFile), $warnings);
		}

		return new SyncReport(substr_count($newBlock, "\n"), 0, array(), $warnings);
	}

	/**
	 * Produce the rights array the descriptor should now declare.
	 *
	 * @param RightsSyncCommand $cmd Command to apply
	 * @return array<int,array<int,string>>|null New rights array, or null when the command is a no-op
	 * @throws \InvalidArgumentException When the command targets a right that cannot be addressed
	 */
	private function applyCommand(RightsSyncCommand $cmd): ?array
	{
		$permissions = array_values($cmd->permissions);

		if ($cmd->scope === RightsSyncCommand::SCOPE_OBJECT) {
			if ($cmd->actionType === RightsSyncCommand::ACTION_ADD) {
				return $this->addObjectRights($permissions, $cmd->module, $cmd->objectName);
			}
			return $this->removeObjectRights($permissions, $cmd->objectName);
		}

		if ($cmd->actionType === RightsSyncCommand::ACTION_ADD) {
			return $this->addRight($permissions, $cmd->objectName, (string) $cmd->rightLabel, (string) $cmd->rightCrud);
		}

		$key = (int) $cmd->rightKey;
		if (!array_key_exists($key, $permissions)) {
			throw new \InvalidArgumentException('No permission found at index '.$key.' of the descriptor rights array');
		}

		if ($cmd->actionType === RightsSyncCommand::ACTION_UPDATE) {
			// Addressed by key, never by value: two rights may carry the very same label.
			$permissions[$key] = array(
				self::INDEX_LABEL => (string) $cmd->rightLabel,
				self::INDEX_OBJECT => strtolower($cmd->objectName),
				self::INDEX_CRUD => (string) $cmd->rightCrud,
			);
			return $permissions;
		}

		unset($permissions[$key]);
		return array_values($permissions);
	}

	/**
	 * Append the three CRUD rights of a freshly generated object.
	 *
	 * @param array<int,array<int,string>> $permissions Current rights
	 * @param string                       $module      Module name
	 * @param string                       $objectName  Object being generated
	 * @return array<int,array<int,string>>|null New rights, or null when the object already owns rights
	 */
	private function addObjectRights(array $permissions, string $module, string $objectName): ?array
	{
		$target = strtolower($objectName);
		foreach ($permissions as $right) {
			if (isset($right[self::INDEX_OBJECT]) && strtolower((string) $right[self::INDEX_OBJECT]) === $target) {
				return null;
			}
		}

		foreach (self::CRUD_LABELS as $crud => $template) {
			$permissions[] = array(
				self::INDEX_LABEL => sprintf($template, $objectName, ucfirst($module)),
				self::INDEX_OBJECT => $target,
				self::INDEX_CRUD => $crud,
			);
		}

		return $permissions;
	}

	/**
	 * Drop every right attached to an object.
	 *
	 * Filtering, not array_splice() inside a foreach over the same array.
	 *
	 * @param array<int,array<int,string>> $permissions Current rights
	 * @param string                       $objectName  Object being deleted
	 * @return array<int,array<int,string>> Remaining rights
	 */
	private function removeObjectRights(array $permissions, string $objectName): array
	{
		$target = strtolower($objectName);

		return array_values(array_filter(
			$permissions,
			/**
			 * @param 	array<int,string> $right Right to keep or drop
			 * @return 	bool
			 */
			static function ($right) use ($target) {
				return !isset($right[self::INDEX_OBJECT]) || strtolower((string) $right[self::INDEX_OBJECT]) !== $target;
			}
		));
	}

	/**
	 * Append one right, refusing a crud code the object already declares.
	 *
	 * @param array<int,array<int,string>> $permissions Current rights
	 * @param string                       $objectName  Object the right belongs to
	 * @param string                       $label       Permission label
	 * @param string                       $crud        Permission crud code
	 * @return array<int,array<int,string>> New rights
	 * @throws \InvalidArgumentException When the object already declares that crud code
	 */
	private function addRight(array $permissions, string $objectName, string $label, string $crud): array
	{
		$target = strtolower($objectName);
		foreach ($permissions as $right) {
			if (isset($right[self::INDEX_OBJECT], $right[self::INDEX_CRUD])
				&& strtolower((string) $right[self::INDEX_OBJECT]) === $target
				&& (string) $right[self::INDEX_CRUD] === $crud) {
				throw new \InvalidArgumentException('Permission "'.$crud.'" is already declared for object "'.$objectName.'"');
			}
		}

		$permissions[] = array(
			self::INDEX_LABEL => $label,
			self::INDEX_OBJECT => $target,
			self::INDEX_CRUD => $crud,
		);

		return $permissions;
	}
}
