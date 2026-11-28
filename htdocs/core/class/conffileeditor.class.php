<?php
/* Copyright (C) 2026 ATM Consulting <contact@atm-consulting.fr>
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
 *	\file       htdocs/core/class/conffileeditor.class.php
 *	\ingroup    core
 *	\brief      Class to read and edit an existing conf.php file in place.
 */

/**
 *	Read the $dolibarr_xxx assignments of an existing conf.php and change them in place.
 *
 *	The content is tokenized, never executed. Only top-level assignments are seen: an assignment inside a block
 *	(if, function...) or after a condition without braces is ignored. Every change keeps the rest of the content
 *	byte for byte.
 */
class ConfFileEditor
{
	/**
	 *	Return the top-level $dolibarr_xxx assignments of a conf.php content.
	 *
	 *	When a variable is assigned several times, the last assignment is returned, since it is the one PHP applies.
	 *
	 *	@param	string	$content	Raw content of the conf.php file.
	 *	@return	array<string,array{raw:string,literal:?string,start:int,valueStart:int,valueEnd:int,end:int}>
	 *			Keyed by variable name (without '$'): raw right-hand side, its value when it is a plain literal
	 *			(null for an expression), byte offsets of the statement start, of the value and of the end (after ';').
	 */
	public function parse(string $content): array
	{
		$assignments = [];
		$tokens = token_get_all($content);
		$count = count($tokens);
		$offset = 0;
		$braceDepth = 0;
		$previous = null;

		for ($i = 0; $i < $count; $i++) {
			$token = $tokens[$i];
			$text = is_array($token) ? $token[1] : $token;
			$id = is_array($token) ? $token[0] : null;

			if ($id === T_VARIABLE && $braceDepth === 0 && $this->isStatementStart($previous) && preg_match('/^\$(dolibarr_\w+)$/', $text, $reg)) {
				$assignment = $this->readAssignment($content, $tokens, $i, $offset);
				if ($assignment !== null) {
					$assignments[$reg[1]] = $assignment['data'];
					$offset = $assignment['data']['end'];
					$i = $assignment['lastIndex'];
					$previous = ';';
					continue;
				}
			}

			if ($text === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
				$braceDepth++;
			} elseif ($text === '}') {
				$braceDepth--;
			}
			if (!in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				$previous = $id === T_OPEN_TAG ? T_OPEN_TAG : $text;
			}
			$offset += strlen($text);
		}

		return $assignments;
	}

	/**
	 *	Set the value of a variable: replace the right-hand side of its assignment, or add the assignment at the end.
	 *
	 *	@param	string	$content	Raw content of the conf.php file.
	 *	@param	string	$key		Variable name without '$' (e.g. dolibarr_main_instance_unique_id).
	 *	@param	string	$value		New value, written as a single-quoted PHP string.
	 *	@return	string				New content.
	 */
	public function setValue(string $content, string $key, string $value): string
	{
		$literal = '\''.str_replace(['\\', '\''], ['\\\\', '\\\''], $value).'\'';
		$assignments = $this->parse($content);

		if (isset($assignments[$key])) {
			$found = $assignments[$key];
			return substr($content, 0, $found['valueStart']).$literal.substr($content, $found['valueEnd']);
		}

		$line = '$'.$key.'='.$literal.';'."\n";
		$closeTag = $this->findTopLevelCloseTag($content);
		if ($closeTag !== null) {
			return substr($content, 0, $closeTag).$line.substr($content, $closeTag);
		}
		if ($content !== '' && substr($content, -1) !== "\n") {
			$content .= "\n";
		}
		return $content.$line;
	}

	/**
	 *	Comment out the assignment of a variable, with a note line above it.
	 *
	 *	@param	string	$content	Raw content of the conf.php file.
	 *	@param	string	$key		Variable name without '$'.
	 *	@param	string	$note		Text of the note line (without the leading '//').
	 *	@return	string				New content, unchanged when the variable is not assigned at top level.
	 */
	public function commentOut(string $content, string $key, string $note): string
	{
		$assignments = $this->parse($content);
		if (!isset($assignments[$key])) {
			return $content;
		}
		$start = $assignments[$key]['start'];
		$end = $assignments[$key]['end'];

		$after = substr($content, $end);
		$restOfLine = strstr($after, "\n", true);
		$restOfLine = trim($restOfLine === false ? $after : $restOfLine);
		$separatorAfter = ($restOfLine !== '' && strpos($restOfLine, '//') !== 0 && strpos($restOfLine, '#') !== 0) ? "\n" : '';

		$lineStart = strrpos(substr($content, 0, $start), "\n");
		$lineStart = $lineStart === false ? 0 : $lineStart + 1;
		$before = substr($content, $lineStart, $start - $lineStart);
		$separatorBefore = trim($before) === '' ? '' : "\n";
		$indent = trim($before) === '' ? $before : '';

		$statement = substr($content, $start, $end - $start);
		$commented = $indent.'// '.$note."\n".$indent.'//'.str_replace("\n", "\n".$indent.'//', $statement);

		$head = $separatorBefore === '' ? substr($content, 0, $lineStart) : substr($content, 0, $start).$separatorBefore;
		return $head.$commented.$separatorAfter.$after;
	}

	/**
	 *	Tell whether a token can be followed by a new statement.
	 *
	 *	@param	int|string|null	$previous	Previous significant token (text, or T_OPEN_TAG), null at the start.
	 *	@return	bool
	 */
	private function isStatementStart($previous): bool
	{
		return $previous === null || $previous === T_OPEN_TAG || $previous === ';' || $previous === '{' || $previous === '}';
	}

	/**
	 *	Read the assignment starting at a $dolibarr_xxx variable token.
	 *
	 *	@param	string											$content	Raw content of the conf.php file.
	 *	@param	array<int,array{0:int,1:string,2:int}|string>	$tokens		Tokens of the content.
	 *	@param	int												$index		Index of the variable token.
	 *	@param	int												$offset		Byte offset of the variable token.
	 *	@return	?array{data:array{raw:string,literal:?string,start:int,valueStart:int,valueEnd:int,end:int},lastIndex:int}
	 *			Null when the variable is not followed by a plain '=' assignment ending with ';'.
	 */
	private function readAssignment(string $content, array $tokens, int $index, int $offset): ?array
	{
		$count = count($tokens);
		$position = $offset + strlen($tokens[$index][1]);
		$i = $index + 1;

		while ($i < $count && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
			$position += strlen($tokens[$i][1]);
			$i++;
		}
		if ($i >= $count || $tokens[$i] !== '=') {
			return null;
		}
		$position++;
		$valueStart = $position;

		$depth = 0;
		for ($i++; $i < $count; $i++) {
			$text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			if ($text === ';' && $depth === 0) {
				break;
			}
			if ($text === '(' || $text === '[' || $text === '{' || (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
				$depth++;
			} elseif ($text === ')' || $text === ']' || $text === '}') {
				$depth--;
			}
			$position += strlen($text);
		}
		if ($i >= $count) {
			return null;
		}

		$value = substr($content, $valueStart, $position - $valueStart);
		$raw = trim($value);
		$valueStart += strlen($value) - strlen(ltrim($value));

		return [
			'data' => [
				'raw' => $raw,
				'literal' => $this->decodeLiteral($raw),
				'start' => $offset,
				'valueStart' => $valueStart,
				'valueEnd' => $valueStart + strlen($raw),
				'end' => $position + 1,
			],
			'lastIndex' => $i,
		];
	}

	/**
	 *	Return the value of a plain literal (quoted string, number, true, false, null), or null for an expression.
	 *
	 *	@param	string	$raw	Right-hand side as written.
	 *	@return	?string
	 */
	private function decodeLiteral(string $raw): ?string
	{
		$significant = [];
		foreach (token_get_all('<?php '.$raw.';') as $token) {
			if (is_array($token) && in_array($token[0], [T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				continue;
			}
			$significant[] = $token;
		}
		$sign = '';
		if (count($significant) === 3 && $significant[0] === '-') {
			$sign = '-';
			array_shift($significant);
		}
		if (count($significant) !== 2 || $significant[1] !== ';' || !is_array($significant[0])) {
			return null;
		}
		list($id, $text) = $significant[0];
		if ($id === T_LNUMBER || $id === T_DNUMBER) {
			return $sign.$text;
		}
		if ($sign !== '') {
			return null;
		}
		if ($id === T_CONSTANT_ENCAPSED_STRING) {
			$inner = substr($text, 1, -1);
			return $text[0] === '\'' ? strtr($inner, ['\\\\' => '\\', '\\\'' => '\'']) : stripcslashes($inner);
		}
		if ($id === T_STRING) {
			$lower = strtolower($text);
			if ($lower === 'true') {
				return '1';
			}
			if ($lower === 'false' || $lower === 'null') {
				return '';
			}
		}
		return null;
	}

	/**
	 *	Return the byte offset of a closing tag '?>' found outside any block, or null.
	 *
	 *	@param	string	$content	Raw content of the conf.php file.
	 *	@return	?int
	 */
	private function findTopLevelCloseTag(string $content): ?int
	{
		$offset = 0;
		$depth = 0;
		foreach (token_get_all($content) as $token) {
			$text = is_array($token) ? $token[1] : $token;
			if ($text === '{') {
				$depth++;
			} elseif ($text === '}') {
				$depth--;
			} elseif (is_array($token) && $token[0] === T_CLOSE_TAG && $depth === 0) {
				return $offset;
			}
			$offset += strlen($text);
		}
		return null;
	}
}
