<?php
/* Copyright (C) 2016      Jean-François Ferry  <hello@librethic.io>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * A class containing a diff implementation
 *
 * Created by Stephen Morley - http://stephenmorley.org/ - and released under the
 * terms of the CC0 1.0 Universal legal code:
 *
 * http://creativecommons.org/publicdomain/zero/1.0/legalcode
 */


/**
 * A class containing functions for computing diffs and formatting the output.
 * We can compare 2 strings or 2 files (as one string or line by line)
 */
class Diff
{
	// define the constants
	const UNMODIFIED = 0;
	const DELETED = 1;
	const INSERTED = 2;

	/**
	 * Returns the diff for two strings. The return value is an array, each of
	 * whose values is an array containing two values: a line (or character, if
	 * $compareCharacters is true), and one of the constants DIFF::UNMODIFIED (the
	 * line or character is in both strings), DIFF::DELETED (the line or character
	 * is only in the first string), and DIFF::INSERTED (the line or character is
	 * only in the second string). The parameters are:
	 *
	 * @param	string	$string1            First string
	 * @param	string	$string2            Second string
	 * @param	boolean	$compareCharacters  true to compare characters, and false to compare lines; this optional parameter defaults to false
	 * @return	array<array{0:string,1:int<0,2>}>		Array of diff
	 */
	public static function compare($string1, $string2, $compareCharacters = false)
	{
		// initialise the sequences and comparison start and end positions
		$start = 0;
		if ($compareCharacters) {
			$sequence1 = $string1;
			$sequence2 = $string2;
			$end1 = strlen($string1) - 1;
			$end2 = strlen($string2) - 1;
		} else {
			$sequence1 = preg_split('/\R/', $string1);
			$sequence2 = preg_split('/\R/', $string2);
			$end1 = count($sequence1) - 1;
			$end2 = count($sequence2) - 1;
		}

		// skip any common prefix
		while ($start <= $end1 && $start <= $end2
			&& $sequence1[$start] == $sequence2[$start]) {
			$start++;
		}

		// skip any common suffix
		while ($end1 >= $start && $end2 >= $start
			&& $sequence1[$end1] == $sequence2[$end2]) {
			$end1--;
			$end2--;
		}

		// compute the table of longest common subsequence lengths
		$table = self::computeTable($sequence1, $sequence2, $start, $end1, $end2);

		// generate the partial diff
		$partialDiff = self::generatePartialDiff($table, $sequence1, $sequence2, $start);

		// generate the full diff
		$diff = array();
		for ($index = 0; $index < $start; $index++) {
			$diff[] = array($sequence1[$index], self::UNMODIFIED);
		}
		while (count($partialDiff) > 0) {
			$diff[] = array_pop($partialDiff);
		}

		$end2 = ($compareCharacters ? strlen($sequence1) : count($sequence1));
		for ($index = $end1 + 1; $index < $end2; $index++) {
			$diff[] = array($sequence1[$index], self::UNMODIFIED);
		}

		// return the diff
		return $diff;
	}

	/**
	 * Returns the diff for two files. The parameters are:
	 *
	 * @param	string	$file1              Path to the first file
	 * @param	string	$file2              Path to the second file
	 * @param	boolean	$compareCharacters  true to compare characters, and false to compare lines; this optional parameter defaults to false
	 * @return	array<array{0:string,1:int<0,2>}>						Array of diff
	 */
	public static function compareFiles(
		$file1,
		$file2,
		$compareCharacters = false
	) {

		// return the diff of the files
		return self::compare(
			file_get_contents($file1),
			file_get_contents($file2),
			$compareCharacters
		);
	}

	/**
	 * Returns the table of longest common subsequence lengths for the specified sequences. The parameters are:
	 *
	 * @param	string	$sequence1 	the first sequence
	 * @param	string	$sequence2 	the second sequence
	 * @param	int		$start     	the starting index
	 * @param	int		$end1      	the ending index for the first sequence
	 * @param	int		$end2      	the ending index for the second sequence
	 * @return	array<array<int>>	array of diff
	 */
	private static function computeTable($sequence1, $sequence2, $start, $end1, $end2)
	{
		// determine the lengths to be compared
		$length1 = $end1 - $start + 1;
		$length2 = $end2 - $start + 1;

		// initialise the table
		$table = array(array_fill(0, $length2 + 1, 0));

		// loop over the rows
		for ($index1 = 1; $index1 <= $length1; $index1++) {
			// create the new row
			$table[$index1] = array(0);

			// loop over the columns
			for ($index2 = 1; $index2 <= $length2; $index2++) {
				// store the longest common subsequence length
				if ($sequence1[$index1 + $start - 1] == $sequence2[$index2 + $start - 1]
				) {
					$table[$index1][$index2] = $table[$index1 - 1][$index2 - 1] + 1;
				} else {
					$table[$index1][$index2] = max($table[$index1 - 1][$index2], $table[$index1][$index2 - 1]);
				}
			}
		}

		// return the table
		return $table;
	}

	/**
	 * Returns the partial diff for the specified sequences, in reverse order.
	 * The parameters are:
	 *
	 * @param	array<array{0:string,1:int<0,2>}>	$table     	the table returned by the computeTable function
	 * @param	string	$sequence1 	the first sequence
	 * @param	string	$sequence2 	the second sequence
	 * @param	int		$start     	the starting index
	 * @return	array<array{0:string,1:int<0,2>}>	array of diff
	 */
	private static function generatePartialDiff($table, $sequence1, $sequence2, $start)
	{
		//  initialise the diff
		$diff = array();

		// initialise the indices
		$index1 = count($table) - 1;
		$index2 = count($table[0]) - 1;

		// loop until there are no items remaining in either sequence
		while ($index1 > 0 || $index2 > 0) {
			// check what has happened to the items at these indices
			if ($index1 > 0 && $index2 > 0
				&& $sequence1[$index1 + $start - 1] == $sequence2[$index2 + $start - 1]
			) {
				// update the diff and the indices
				$diff[] = array($sequence1[$index1 + $start - 1], self::UNMODIFIED);
				$index1--;
				$index2--;
			} elseif ($index2 > 0
				&& $table[$index1][$index2] == $table[$index1][$index2 - 1]
			) {
				// update the diff and the indices
				$diff[] = array($sequence2[$index2 + $start - 1], self::INSERTED);
				$index2--;
			} else {
				// update the diff and the indices
				$diff[] = array($sequence1[$index1 + $start - 1], self::DELETED);
				$index1--;
			}
		}

		// return the diff
		return $diff;
	}

	/**
	 * Returns a diff as a string, where unmodified lines are prefixed by '  ',
	 * deletions are prefixed by '- ', and insertions are prefixed by '+ '. The
	 * parameters are:
	 *
	 * @param	array<array{0:string,1:int<0,2>}>	$diff      	the diff array
	 * @param	string	$separator 	the separator between lines; this optional parameter defaults to "\n"
	 * @return	string				String
	 */
	public static function toString($diff, $separator = "\n")
	{
		// initialise the string
		$string = '';

		// loop over the lines in the diff
		foreach ($diff as $line) {
			// extend the string with the line
			switch ($line[1]) {
				case self::UNMODIFIED:
					$string .= '  '.$line[0];
					break;
				case self::DELETED:
					$string .= '- '.$line[0];
					break;
				case self::INSERTED:
					$string .= '+ '.$line[0];
					break;
			}

			// extend the string with the separator
			$string .= $separator;
		}

		// return the string
		return $string;
	}

	/**
	 * Returns a diff as an HTML string, where unmodified lines are contained
	 * within 'span' elements, deletions are contained within 'del' elements, and
	 * insertions are contained within 'ins' elements. The parameters are:
	 *
	 * @param	array<array{0:string,1:int<0,2>}>	$diff      	the diff array
	 * @param	string	$separator 	the separator between lines; this optional parameter defaults to '<br>'
	 * @return	string				HTML string
	 */
	public static function toHTML($diff, $separator = '<br>')
	{
		// initialise the HTML
		$html = '';

		// loop over the lines in the diff
		$element = 'unknown';
		foreach ($diff as $line) {
			// extend the HTML with the line
			switch ($line[1]) {
				case self::UNMODIFIED:
					$element = 'span';
					break;
				case self::DELETED:
					$element = 'del';
					break;
				case self::INSERTED:
					$element = 'ins';
					break;
			}
			$html .= '<'.$element.'>'.dol_escape_htmltag($line[0]).'</'.$element.'>';

			// extend the HTML with the separator
			$html .= $separator;
		}

		// return the HTML
		return $html;
	}

	/**
	 * Returns a diff as an HTML table. The parameters are:
	 *
	 * @param	array<array{0:string,1:int<0,2>}>	$diff        	the diff array
	 * @param	string	$indentation 	indentation to add to every line of the generated HTML; this optional parameter defaults to ''
	 * @param	string	$separator   	the separator between lines; this optional parameter defaults to '<br>'
	 * @return	string					HTML string
	 */
	public static function toTable($diff, $indentation = '', $separator = '<br>')
	{
		// initialise the HTML
		$html = $indentation."<table class=\"diff\">\n";

		$rightCell = $leftCell = '';

		// loop over the lines in the diff
		$index = 0;
		$nbdiff = count($diff);
		while ($index < $nbdiff) {
			// determine the line type
			switch ($diff[$index][1]) {
				// display the content on the left and right
				case self::UNMODIFIED:
					$leftCell = self::getCellContent(
						$diff,
						$indentation,
						$separator,
						$index,
						self::UNMODIFIED
					);
					$rightCell = $leftCell;
					break;

					// display the deleted on the left and inserted content on the right
				case self::DELETED:
					$leftCell = self::getCellContent(
						$diff,
						$indentation,
						$separator,
						$index,
						self::DELETED
					);
					$rightCell = self::getCellContent(
						$diff,
						$indentation,
						$separator,
						$index,
						self::INSERTED
					);
					break;

					// display the inserted content on the right
				case self::INSERTED:
					$leftCell = '';
					$rightCell = self::getCellContent(
						$diff,
						$indentation,
						$separator,
						$index,
						self::INSERTED
					);
					break;
			}

			// extend the HTML with the new row
			$html .=
				$indentation
				. "  <tr>\n"
				. $indentation
				. '    <td class="diff'
				. ($leftCell == $rightCell
				? 'Unmodified'
				: ($leftCell == '' ? 'Blank' : 'Deleted'))
				. '">'
				. $leftCell
				. "</td>\n"
				. $indentation
				. '    <td class="diff'
				. ($leftCell == $rightCell
				? 'Unmodified'
				: ($rightCell == '' ? 'Blank' : 'Inserted'))
				. '">'
				. $rightCell
				. "</td>\n"
				. $indentation
				. "  </tr>\n";
		}

		// return the HTML
		return $html.$indentation."</table>\n";
	}

	/**
	 * Returns the content of the cell, for use in the toTable function. The
	 * parameters are:
	 *
	 * @param	array<array{0:string,1:int<0,2>}>	$diff        	the diff array
	 * @param	string	$indentation 	indentation to add to every line of the generated HTML
	 * @param	string	$separator   	the separator between lines
	 * @param	int 	$index       	the current index, passed by reference
	 * @param	string	$type        	the type of line
	 * @return	string					HTML string
	 */
	private static function getCellContent($diff, $indentation, $separator, &$index, $type)
	{
		// initialise the HTML
		$html = '';

		// loop over the matching lines, adding them to the HTML
		while ($index < count($diff) && $diff[$index][1] == $type) {
			$html .=
			'<span>'
			. htmlspecialchars($diff[$index][0])
				. '</span>'
				. $separator;
			$index++;
		}

		// return the HTML
		return $html;
	}
}
