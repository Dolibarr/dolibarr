<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
 * Copyright (C) 2026		MDW				<mdeweerd@users.noreply.github.com>
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
 * \file    htdocs/modulebuilder/class/PermissionsBlock.class.php
 * \ingroup modulebuilder
 * \brief   Text engine for the BEGIN/END MODULEBUILDER PERMISSIONS section of a module descriptor.
 */
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

/**
 * Text engine for the BEGIN/END MODULEBUILDER PERMISSIONS section of a module descriptor.
 *
 * Owns everything that touches the raw text of that section: locating it, deciding whether it is
 * safe to rewrite, rendering a new one from a rights array, and writing it back in a single pass.
 * sync() applies one ModuleBuilder action (add/delete an object, add/update/delete a right) to it.
 */
final class PermissionsBlock
{
	const BEGIN_MARKER = '/* BEGIN MODULEBUILDER PERMISSIONS */';
	const END_MARKER = '/* END MODULEBUILDER PERMISSIONS */';

	/** Actions accepted by sync() */
	const SYNC_ADD_OBJECT = 'addobject';
	const SYNC_DELETE_OBJECT = 'deleteobject';
	const SYNC_ADD_RIGHT = 'addright';
	const SYNC_UPDATE_RIGHT = 'updateright';
	const SYNC_DELETE_RIGHT = 'deleteright';

	/** @var array<string,string> Label template of each standard crud code */
	private const CRUD_LABELS = array(
		'read' => 'Read %s object of %s',
		'write' => 'Create/Update %s object of %s',
		'delete' => 'Delete %s object of %s',
	);

	/**
	 * Variables a rewritable block may mention. Anything else means the block builds its rights
	 * from data the renderer cannot reproduce.
	 *
	 * @var string[]
	 */
	private const ALLOWED_VARIABLES = array('$this', '$r', '$o');

	/**
	 * Identifiers a rewritable block may mention: the two descriptor properties it assigns, the
	 * single function the generated id expression calls, and the three literals the tokenizer
	 * reports as T_STRING rather than as a dedicated token.
	 *
	 * @var string[]
	 */
	private const ALLOWED_IDENTIFIERS = array('rights', 'numero', 'sprintf', 'null', 'true', 'false');

	/**
	 * Punctuation a rewritable block may contain.
	 *
	 * @var string[]
	 */
	private const ALLOWED_PUNCTUATION = array('[', ']', '(', ')', '=', ';', ',', '.', '+', '-', '*');

	/** @var array<string,int> Canonical numbering offset of the three standard crud codes */
	private const CRUD_OFFSETS = array('read' => 0, 'write' => 1, 'delete' => 2);

	/** @var int Numbering stride between two objects */
	private const OBJECT_ID_STRIDE = 10;

	/** @var int Rights array index holding the permission id */
	private const INDEX_ID = 0;

	/** @var int Rights array index holding the permission label */
	private const INDEX_LABEL = 1;

	/** @var int Rights array index holding the object name */
	private const INDEX_OBJECT = 4;

	/** @var int Rights array index holding the crud code */
	private const INDEX_CRUD = 5;

	/** @var int[] The only rights array indexes the renderer emits */
	private const SUPPORTED_INDEXES = array(0, 1, 4, 5);

	/** @var string Path to the descriptor file */
	private $file;

	/** @var string Content between the two markers, markers excluded */
	private $innerBlock;

	/**
	 * @param string $file       Path to the descriptor file
	 * @param string $innerBlock Content between the markers
	 */
	private function __construct(string $file, string $innerBlock)
	{
		$this->file = $file;
		$this->innerBlock = $innerBlock;
	}

	/**
	 * Read a descriptor and locate its permissions block.
	 *
	 * @param string $file Path to the mod<Module>.class.php descriptor
	 * @return self New PermissionsBlock instance
	 * @throws \RuntimeException When the file is unreadable or the markers are missing or inverted
	 */
	public static function fromFile(string $file): self
	{
		if (strpos($file, '..') !== false) {
			throw new \RuntimeException('Descriptor path must not contain a parent directory reference: '.$file);
		}
		if (!dol_is_file($file)) {
			throw new \RuntimeException('Descriptor file not found: '.$file);
		}
		$content = file_get_contents($file);
		if ($content === false) {
			throw new \RuntimeException('Descriptor file is unreadable: '.$file);
		}

		$posBegin = strpos($content, self::BEGIN_MARKER);
		$posEnd = strpos($content, self::END_MARKER);
		if ($posBegin === false || $posEnd === false || $posEnd < $posBegin) {
			throw new \RuntimeException('Cannot find the start and/or end comments of the permissions section in '.$file);
		}

		$start = $posBegin + dol_strlen(self::BEGIN_MARKER);
		$innerBlock = dol_substr($content, $start, $posEnd - $start);

		return new self($file, $innerBlock);
	}

	/**
	 * Raw content between the markers, markers excluded.
	 *
	 * @return string Permissions block content without markers
	 */
	public function getInnerBlock(): string
	{
		return $this->innerBlock;
	}

	/**
	 * List the reasons why rewriting this block would destroy something.
	 *
	 * Comments are ignored, which is what makes a brand new module — whose block holds the fully
	 * commented-out template — rewritable. Everything else must belong to the whitelist: a block
	 * that computes its rights (loop, condition, translation call) cannot be reproduced by
	 * render() and must never be flattened into static lines.
	 *
	 * @return string[] Plain English reasons, empty when the block is safe to rewrite
	 */
	public function detectTextConflicts(): array
	{
		$conflicts = array();
		$tokens = token_get_all('<?php '.$this->innerBlock);

		foreach ($tokens as $token) {
			if (is_string($token)) {
				if (!in_array($token, self::ALLOWED_PUNCTUATION, true)) {
					$conflicts[] = 'unexpected "'.$token.'" in the permissions block';
				}
				continue;
			}

			list($id, $text, $line) = $token;

			if (in_array($id, array(T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML), true)) {
				continue;
			}
			if (in_array($id, array(T_LNUMBER, T_DNUMBER, T_CONSTANT_ENCAPSED_STRING, T_OBJECT_OPERATOR, T_INC), true)) {
				continue;
			}
			if ($id === T_VARIABLE) {
				if (!in_array($text, self::ALLOWED_VARIABLES, true)) {
					$conflicts[] = 'line '.$line.': unexpected variable '.$text.' — the permissions block builds its rights dynamically and cannot be rewritten safely';
				}
				continue;
			}
			if ($id === T_STRING) {
				if (!in_array($text, self::ALLOWED_IDENTIFIERS, true)) {
					$conflicts[] = 'line '.$line.': unexpected identifier "'.$text.'" in the permissions block';
				}
				continue;
			}

			$conflicts[] = 'line '.$line.': unexpected "'.trim($text).'" in the permissions block — the section cannot be rewritten safely';
		}

		return array_values(array_unique($conflicts));
	}

	/**
	 * List the rights that cannot be rendered at all.
	 *
	 * @param array<int,array<int,string>> $permissions Rights array to inspect
	 * @return string[] Plain English reasons, empty when every right is renderable
	 */
	public function detectRightsShapeConflicts(array $permissions): array
	{
		$conflicts = array();
		foreach ($permissions as $i => $right) {
			if (!is_array($right)) {
				$conflicts[] = 'right #'.$i.' is not an array';
				continue;
			}
			if (!isset($right[self::INDEX_OBJECT]) || (string) $right[self::INDEX_OBJECT] === '') {
				$conflicts[] = 'right #'.$i.' declares no object name at index '.self::INDEX_OBJECT;
			}
			if (!isset($right[self::INDEX_CRUD]) || (string) $right[self::INDEX_CRUD] === '') {
				$conflicts[] = 'right #'.$i.' declares no crud code at index '.self::INDEX_CRUD;
			}
		}

		return $conflicts;
	}

	/**
	 * List the rights carrying indexes the renderer will drop.
	 *
	 * Indexes 2 and 3 are obsolete in modern Dolibarr. Dropping them is the correct migration, so
	 * this is reported as a warning and does not prevent the write.
	 *
	 * @param array<int,array<int,string>> $permissions Rights array to inspect
	 * @return string[] Plain English warnings, empty when every right has a supported shape
	 */
	public function detectRightsShapeWarnings(array $permissions): array
	{
		$warnings = array();
		foreach ($permissions as $i => $right) {
			if (!is_array($right)) {
				continue;
			}
			$unsupported = array_diff(array_keys($right), self::SUPPORTED_INDEXES);
			if (!empty($unsupported)) {
				$warnings[] = 'right #'.$i.' carries unsupported index '.implode(', ', $unsupported).', dropped on rewrite';
			}
		}

		return $warnings;
	}

	/**
	 * Render a rights array as the new content of the permissions block.
	 *
	 * Only indexes 0, 1, 4 and 5 are emitted: the historical code rewrote those four but imploded
	 * the whole row, so a right carrying the obsolete index 2 or 3 had its raw value written into
	 * the descriptor, which then no longer parsed.
	 *
	 * @param array<int,array<int,string>> $permissions Rights array to render
	 * @return string Block content, markers excluded, one trailing newline
	 */
	public function render(array $permissions): string
	{
		$grouped = array();
		foreach ($permissions as $right) {
			if (!is_array($right) || !isset($right[self::INDEX_OBJECT], $right[self::INDEX_CRUD])) {
				continue;
			}
			$grouped[(string) $right[self::INDEX_OBJECT]][] = $right;
		}

		$lines = array();
		$objectIndex = 0;
		foreach ($grouped as $group) {
			foreach ($this->assignOffsets($group) as $entry) {
				$right = $entry['right'];
				$id = "\$this->numero . sprintf('%02d', (".$objectIndex." * ".self::OBJECT_ID_STRIDE.") + ".$entry['offset']." + 1)";

				$lines[] = "\t\t\$this->rights[\$r][".self::INDEX_ID."] = ".$id.";";
				$lines[] = "\t\t\$this->rights[\$r][".self::INDEX_LABEL."] = '".$this->escapeForPhpSingleQuotedString((string) ($right[self::INDEX_LABEL] ?? ''))."';";
				$lines[] = "\t\t\$this->rights[\$r][".self::INDEX_OBJECT."] = '".$this->escapeForPhpSingleQuotedString((string) $right[self::INDEX_OBJECT])."';";
				$lines[] = "\t\t\$this->rights[\$r][".self::INDEX_CRUD."] = '".$this->escapeForPhpSingleQuotedString((string) $right[self::INDEX_CRUD])."';";
				$lines[] = "\t\t\$r++;";
			}
			$objectIndex++;
		}

		return empty($lines) ? '' : implode("\n", $lines)."\n";
	}

	/**
	 * Give each right of one object its numbering offset.
	 *
	 * The three standard crud codes keep their canonical offset. Any other code — or a duplicate
	 * standard one — takes the next free offset from 3 upwards, so two rights of the same object
	 * can never collide on the same permission id.
	 *
	 * @param array<int,array<int,string>> $group Rights of a single object
	 * @return array<int,array{offset:int,right:array<int,string>}> Entries sorted by offset
	 */
	private function assignOffsets(array $group): array
	{
		$assigned = array();
		$usedOffsets = array();
		$next = count(self::CRUD_OFFSETS);

		foreach ($group as $right) {
			$crud = (string) $right[self::INDEX_CRUD];
			if (isset(self::CRUD_OFFSETS[$crud]) && !isset($usedOffsets[self::CRUD_OFFSETS[$crud]])) {
				$offset = self::CRUD_OFFSETS[$crud];
			} else {
				while (isset($usedOffsets[$next])) {
					$next++;
				}
				$offset = $next;
			}
			$usedOffsets[$offset] = true;
			$assigned[] = array('offset' => $offset, 'right' => $right);
		}

		usort(
			$assigned,
			/**
			 * @param 	array{offset:int,right:array<int,string>} $a First entry to compare
			 * @param 	array{offset:int,right:array<int,string>} $b Second entry to compare
			 * @return 	int Comparison result (-1, 0, or 1)
			 */
			static function (array $a, array $b): int {
				return $a['offset'] <=> $b['offset'];
			}
		);

		return $assigned;
	}

	/**
	 * Escape a value for inclusion in a single-quoted PHP string literal.
	 *
	 * GETPOST(..., 'alpha') keeps the single quote, the semicolon, the dollar sign and
	 * parentheses, so a label reaches this class able to close the literal it is written into.
	 *
	 * @param string $value Raw value
	 * @return string Value safe to place between single quotes in generated PHP
	 */
	private function escapeForPhpSingleQuotedString(string $value): string
	{
		return str_replace(array('\\', "'"), array('\\\\', "\\'"), $value);
	}

	/**
	 * Replace the whole permissions block, markers included, in a single write.
	 *
	 * dolReplaceInFile() already writes to a .tmp file and dol_move()s it into place, so the write
	 * itself is atomic. What matters here is that there is exactly ONE of them: the historical code
	 * deleted the block and then inserted the new one, leaving the descriptor without any
	 * permissions block whenever the second write failed.
	 *
	 * Regex mode is mandatory, not a style choice: in plain mode dolReplaceInFile() runs the whole
	 * file through make_substitutions(), which expands __(Key)__ and __[class:method:id]__ patterns
	 * anywhere in the descriptor and even instantiates classes to do so.
	 *
	 * @param string $newInnerBlock New block content, markers excluded
	 * @return int 1 if OK, <0 if KO
	 */
	public function write(string $newInnerBlock): int
	{
		$pattern = '/'.preg_quote(self::BEGIN_MARKER, '/').'.*?'.preg_quote(self::END_MARKER, '/').'/s';
		$replacement = self::BEGIN_MARKER."\n".$newInnerBlock."\t\t".self::END_MARKER;

		// preg_replace() reads $1 and \1 in the replacement as backreferences, and a permission
		// label can legitimately contain either.
		$replacement = str_replace(array('\\', '$'), array('\\\\', '\\$'), $replacement);

		$result = dolReplaceInFile($this->file, array($pattern => $replacement), '', '0', 0, 1);
		if ($result <= 0) {
			dol_syslog('PermissionsBlock::write failed on '.$this->file.' with code '.$result, LOG_ERR);
			return $result < 0 ? $result : -1;
		}

		$this->innerBlock = $newInnerBlock;

		return 1;
	}

	/**
	 * Apply one ModuleBuilder action to the rights of a module descriptor and rewrite its permissions block.
	 *
	 * Error-first: every reason not to write is collected before the rights array is touched, and a
	 * single conflict aborts the whole run without writing. A half-written permissions section is
	 * worse than a stale one.
	 *
	 * @param string                       $file        Path to the mod<Module>.class.php descriptor to patch
	 * @param string                       $action      One of the SYNC_* constants
	 * @param array<int,array<int,string>> $permissions Rights array read from the instantiated descriptor
	 * @param string                       $module      Module name, used in the labels of the rights of a new object
	 * @param string                       $objectName  Object the action applies to, compared case-insensitively. Empty for SYNC_DELETE_RIGHT.
	 * @param int|null                     $key         Index in $permissions of the right to update or delete
	 * @param string                       $label       Label of the right to add or update
	 * @param string                       $crud        Crud code of the right to add or update
	 * @return array{conflicts:string[],warnings:string[],skipped:bool} Conflicts (nothing was written when not empty), warnings (written anyway), skipped (object already owns rights, nothing written)
	 */
	public static function sync(string $file, string $action, array $permissions, string $module, string $objectName = '', ?int $key = null, string $label = '', string $crud = ''): array
	{
		try {
			$block = self::fromFile($file);
		} catch (\RuntimeException $e) {
			return self::refuseSync($file, array($e->getMessage()), array());
		}

		$conflicts = array_merge($block->detectTextConflicts(), $block->detectRightsShapeConflicts($permissions));
		if (!empty($conflicts)) {
			return self::refuseSync($file, $conflicts, array());
		}

		$warnings = $block->detectRightsShapeWarnings($permissions);

		try {
			$newPermissions = self::applySyncAction($action, array_values($permissions), $module, $objectName, $key, $label, $crud);
		} catch (\InvalidArgumentException $e) {
			return self::refuseSync($file, array($e->getMessage()), $warnings);
		}
		if ($newPermissions === null) {
			return array('conflicts' => array(), 'warnings' => $warnings, 'skipped' => true);
		}

		// The action may itself produce an unusable right, e.g. a right attached to no object, which hasRight() could never match.
		$conflicts = $block->detectRightsShapeConflicts($newPermissions);
		if (!empty($conflicts)) {
			return self::refuseSync($file, $conflicts, $warnings);
		}

		if ($block->write($block->render($newPermissions)) < 0) {
			return self::refuseSync($file, array('Failed to write the permissions section of '.$file), $warnings);
		}

		return array('conflicts' => array(), 'warnings' => $warnings, 'skipped' => false);
	}

	/**
	 * Log and return the outcome of a sync that wrote nothing.
	 *
	 * @param string   $file      Path to the descriptor
	 * @param string[] $conflicts Reasons why nothing was written
	 * @param string[] $warnings  Non-blocking problems already detected
	 * @return array{conflicts:string[],warnings:string[],skipped:bool} Outcome of the sync
	 */
	private static function refuseSync(string $file, array $conflicts, array $warnings): array
	{
		dol_syslog('PermissionsBlock::sync refused to rewrite '.$file.': '.implode('; ', array_slice($conflicts, 0, 3)), LOG_WARNING);

		return array('conflicts' => $conflicts, 'warnings' => $warnings, 'skipped' => false);
	}

	/**
	 * Produce the rights array the descriptor should declare once the action is applied.
	 *
	 * @param string                       $action      One of the SYNC_* constants
	 * @param array<int,array<int,string>> $permissions Current rights, reindexed
	 * @param string                       $module      Module name
	 * @param string                       $objectName  Object the action applies to
	 * @param int|null                     $key         Index of the right to update or delete
	 * @param string                       $label       Label of the right to add or update
	 * @param string                       $crud        Crud code of the right to add or update
	 * @return array<int,array<int,string>>|null New rights, or null when the object to add already owns rights
	 * @throws \InvalidArgumentException When the action is unknown or targets a right that cannot be addressed
	 */
	private static function applySyncAction(string $action, array $permissions, string $module, string $objectName, ?int $key, string $label, string $crud): ?array
	{
		$target = dol_strtolower($objectName);

		if ($action === self::SYNC_ADD_OBJECT || $action === self::SYNC_DELETE_OBJECT) {
			if ($target === '') {
				throw new \InvalidArgumentException('An object action on the permissions requires an object name');
			}
			if ($action === self::SYNC_DELETE_OBJECT) {
				foreach ($permissions as $i => $right) {
					if (self::rightMatches($right, $target)) {
						unset($permissions[$i]);
					}
				}
				return array_values($permissions);
			}
			foreach ($permissions as $right) {
				if (self::rightMatches($right, $target)) {
					return null;
				}
			}
			foreach (self::CRUD_LABELS as $crudcode => $template) {
				$permissions[] = array(
					self::INDEX_LABEL => sprintf($template, $objectName, dol_ucfirst($module)),
					self::INDEX_OBJECT => $target,
					self::INDEX_CRUD => $crudcode,
				);
			}
			return $permissions;
		}

		if ($action === self::SYNC_ADD_RIGHT) {
			foreach ($permissions as $right) {
				if (self::rightMatches($right, $target, $crud)) {
					throw new \InvalidArgumentException('Permission "'.$crud.'" is already declared for object "'.$objectName.'"');
				}
			}
			$permissions[] = array(self::INDEX_LABEL => $label, self::INDEX_OBJECT => $target, self::INDEX_CRUD => $crud);
			return $permissions;
		}

		if ($action !== self::SYNC_UPDATE_RIGHT && $action !== self::SYNC_DELETE_RIGHT) {
			throw new \InvalidArgumentException('Unknown permissions sync action: '.$action);
		}
		if ($key === null || !array_key_exists($key, $permissions)) {
			throw new \InvalidArgumentException('No permission found at index '.$key.' of the descriptor rights array');
		}
		if ($action === self::SYNC_UPDATE_RIGHT) {
			// Addressed by key, never by value: two rights may carry the very same label.
			$permissions[$key] = array(self::INDEX_LABEL => $label, self::INDEX_OBJECT => $target, self::INDEX_CRUD => $crud);
			return $permissions;
		}

		unset($permissions[$key]);
		return array_values($permissions);
	}

	/**
	 * Tell whether a right belongs to an object and, optionally, carries a crud code.
	 *
	 * @param mixed       $right  Right of the rights array
	 * @param string      $target Lowercase object name
	 * @param string|null $crud   Crud code to match, null to match any
	 * @return bool True if the right matches
	 */
	private static function rightMatches($right, string $target, ?string $crud = null): bool
	{
		if (!is_array($right) || !isset($right[self::INDEX_OBJECT]) || dol_strtolower((string) $right[self::INDEX_OBJECT]) !== $target) {
			return false;
		}

		return $crud === null || (isset($right[self::INDEX_CRUD]) && (string) $right[self::INDEX_CRUD] === $crud);
	}
}
