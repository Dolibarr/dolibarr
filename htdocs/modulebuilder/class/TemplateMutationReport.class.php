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
 * \file    htdocs/modulebuilder/class/TemplateMutationReport.class.php
 * \ingroup modulebuilder
 * \brief   Report describing include-statement mutations applied to one generated file.
 */

/**
 * Report describing include-statement mutations applied to one generated file.
 *
 * The report carries the replacements (from/to per line) so the caller can apply them:
 * the transformation itself is computed by a pure helper that performs no file I/O.
 */
final class TemplateMutationReport
{
	/** @var string Absolute path of the described file */
	public $file;

	/** @var array<int,array{line:int,from:string,to:string}> Applied replacements */
	public $replacements = array();

	/** @var array<int,array{line:int,reason:string}> Includes deliberately left untouched */
	public $skipped = array();

	/** @var string[] Non-fatal problems (syntax check failure, unresolved path...) */
	public $warnings = array();

	/**
	 * Constructor
	 *
	 * @param string $file Absolute path of the file the report describes
	 */
	public function __construct(string $file)
	{
		$this->file = $file;
	}

	/**
	 * Record a rewritten include statement.
	 *
	 * @param int    $line Line number (1-based)
	 * @param string $from Original statement
	 * @param string $to   Rewritten statement
	 * @return void
	 */
	public function addReplacement(int $line, string $from, string $to): void
	{
		$this->replacements[] = array('line' => $line, 'from' => $from, 'to' => $to);
	}

	/**
	 * Record an include statement deliberately left untouched.
	 *
	 * @param int    $line   Line number (1-based)
	 * @param string $reason Why the include was skipped
	 * @return void
	 */
	public function addSkipped(int $line, string $reason): void
	{
		$this->skipped[] = array('line' => $line, 'reason' => $reason);
	}

	/**
	 * Record a non-fatal warning.
	 *
	 * @param string $message Warning message
	 * @return void
	 */
	public function addWarning(string $message): void
	{
		$this->warnings[] = $message;
	}

	/**
	 * Tell whether at least one replacement was recorded.
	 *
	 * @return bool True if the file must be rewritten
	 */
	public function hasChanges(): bool
	{
		return !empty($this->replacements);
	}
}
