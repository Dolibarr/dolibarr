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
 * \file    htdocs/modulebuilder/class/PermissionsBlock.class.php
 * \ingroup modulebuilder
 * \brief   Text engine for the BEGIN/END MODULEBUILDER PERMISSIONS section of a module descriptor.
 */

/**
 * Text engine for the BEGIN/END MODULEBUILDER PERMISSIONS section of a module descriptor.
 *
 * Owns everything that touches the raw text of that section: locating it, deciding whether it is
 * safe to rewrite, rendering a new one from a rights array, and writing it back in a single pass.
 */
final class PermissionsBlock
{
	const BEGIN_MARKER = '/* BEGIN MODULEBUILDER PERMISSIONS */';
	const END_MARKER = '/* END MODULEBUILDER PERMISSIONS */';

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
	 * @return self
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

		$start = $posBegin + strlen(self::BEGIN_MARKER);
		$innerBlock = substr($content, $start, $posEnd - $start);

		return new self($file, $innerBlock);
	}

	/**
	 * Raw content between the markers, markers excluded.
	 *
	 * @return string
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
			 * @return 	int
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
}
