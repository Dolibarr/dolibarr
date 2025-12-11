<?php
/* Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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

namespace codesniffer\Sniffs\Dolibarr;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * CheckIsModEnabledArgumentSniff
 */
class CheckIsModEnabledArgumentSniff implements Sniff
{
	// Nom de la fonction cible
	protected $targetFunction = 'ismodenabled';

	protected $validModules = [
		'asset',
		'notification',
	];

	/**
	 * register
	 *
	 * @return void
	 */
	public function register()
	{
		// We are listening function calls (T_STRING)
		return [T_STRING];
	}

	/**
	 * process
	 *
	 * @param  File $phpcsFile file to process
	 * @param  mixed $stackPtr pointer
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		if (strtolower($tokens[$stackPtr]['content']) !== strtolower($this->targetFunction)) {
			return;
		}

		// Check that it is a function call (followed by '(')
		$openParen = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
		if ($tokens[$openParen]['code'] !== T_OPEN_PARENTHESIS) {
			return;
		}

		// We are looking for the first 'useful' token after the parenthesis
		$firstArgTokenPtr = $phpcsFile->findNext(T_WHITESPACE, $openParen + 1, null, true);

		// If the function is called without arguments (isModEnabled()), we stop
		if ($tokens[$firstArgTokenPtr]['code'] === T_CLOSE_PARENTHESIS) {
			return;
		}

		// check value of argument
		$argContent = $tokens[$firstArgTokenPtr]['content'];
		$argCode    = $tokens[$firstArgTokenPtr]['code'];

		if (!in_array($argContent, $this->validModules)) {
			$phpcsFile->addError(
				'The function "%s" has invalid argument (%s).',
				$firstArgTokenPtr,
				'InvalidNumericArgument',
				[$this->targetFunction, $argContent]
			);
		}
	}
}
