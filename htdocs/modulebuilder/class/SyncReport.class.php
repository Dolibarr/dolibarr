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
 * \file    htdocs/modulebuilder/class/SyncReport.class.php
 * \ingroup modulebuilder
 * \brief   Immutable outcome of a permissions sync run.
 */

/**
 * Immutable outcome of a permissions sync run.
 *
 * Conflicts and warnings are two distinct severities and must not be merged: a non-empty
 * $conflicts means NO write was performed, while $warnings means the write was performed and
 * something was normalized on the way.
 */
final class SyncReport
{
	/** @var int Number of lines written into the permissions block, 0 when no write occurred */
	public $updatedLines;

	/** @var int Number of sync operations deliberately skipped, e.g. rights already declared */
	public $skipped;

	/** @var string[] Blocking problems, in plain English — when not empty, no write was performed */
	public $conflicts;

	/** @var string[] Non-blocking problems, in plain English — the write was performed anyway */
	public $warnings;

	/**
	 * @param int      $updatedLines Number of lines written into the permissions block
	 * @param int      $skipped      Number of operations deliberately skipped
	 * @param string[] $conflicts    Blocking problems, in plain English
	 * @param string[] $warnings     Non-blocking problems, in plain English
	 */
	public function __construct(int $updatedLines = 0, int $skipped = 0, array $conflicts = array(), array $warnings = array())
	{
		$this->updatedLines = $updatedLines;
		$this->skipped = $skipped;
		$this->conflicts = $conflicts;
		$this->warnings = $warnings;
	}

	/**
	 * Whether a blocking problem prevented the write.
	 *
	 * @return bool True when no write was performed
	 */
	public function hasConflicts(): bool
	{
		return !empty($this->conflicts);
	}

	/**
	 * Whether something was normalized while still writing.
	 *
	 * @return bool True when the write happened but something was adjusted
	 */
	public function hasWarnings(): bool
	{
		return !empty($this->warnings);
	}

	/**
	 * Whether the sync produced no permission line.
	 *
	 * This is not the same as "the file was left untouched": removing the last right of a module
	 * writes an empty block, which changes the file while producing zero lines.
	 *
	 * @return bool True when no permission line was written
	 */
	public function isNoop(): bool
	{
		return $this->updatedLines === 0;
	}

	/**
	 * Map the report onto the historical reWriteAllPermissions() return code.
	 *
	 * @return int<-1,1> 1 if OK, -1 if KO
	 */
	public function toLegacyReturnCode(): int
	{
		return $this->hasConflicts() ? -1 : 1;
	}
}
