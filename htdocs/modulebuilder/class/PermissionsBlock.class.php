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
	 * Identifiers a rewritable block may mention: the two descriptor properties it assigns and
	 * the single function the generated id expression calls.
	 *
	 * @var string[]
	 */
	private const ALLOWED_IDENTIFIERS = array('rights', 'numero', 'sprintf');

	/**
	 * Punctuation a rewritable block may contain.
	 *
	 * @var string[]
	 */
	private const ALLOWED_PUNCTUATION = array('[', ']', '(', ')', '=', ';', ',', '.', '+', '-', '*');

	/** @var string Path to the descriptor file */
	private $file;

	/** @var string Full descriptor content as read from disk */
	private $content;

	/** @var string Content between the two markers, markers excluded */
	private $innerBlock;

	/**
	 * @param string $file       Path to the descriptor file
	 * @param string $content    Full descriptor content
	 * @param string $innerBlock Content between the markers
	 */
	private function __construct(string $file, string $content, string $innerBlock)
	{
		$this->file = $file;
		$this->content = $content;
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

		return new self($file, $content, $innerBlock);
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
}
