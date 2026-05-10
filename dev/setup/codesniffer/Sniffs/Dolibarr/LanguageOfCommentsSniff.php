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
 * LanguageOfCommentsSniff
 */
class LanguageOfCommentsSniff implements Sniff
{
	// List of words that betray a comment in French
	public $frenchWords = [
		' additionner ',
		' arrondir ',
		' avec ',
		' calculer ',
		' chaine ',
		' chaîne ',
		' chercher ',
		' chiffre ',
		' chiffres ',
		// ' commande ',
		' commandes ',
		' compteur ',
		' compteurs ',
		' contrats ',
		' depuis ',
		' diviser ',
		' donnée ',
		' entier ',
		// ' facture ', // avoid french name of dolibarr object
		' factures ',
		' ligne ',
		' lignes ',
		' modèle ',
		' niveau ',
		' niveaux ',
		' nombre ',
		' parametrage ',
		' paramétrage ',
		' pourcentage ',
		' produit ',
		' produits ',
		' quand ',
		' rechercher ',
		' sinon ',
		' stocker ',
		' soustraire ',
		' sujet ',
		' sujets ',
		' suppression ',
		' utilisateur ',
		' utilisateurs ',
		' valeur ',
		' valeurs ',
	];

	/**
	 * Which tokens to listen ?
	 * T_COMMENT = comments // or #
	 * T_DOC_COMMENT_STRING = text in block comments
	 * @return int[]
	 */
	public function register()
	{
		return [
			T_COMMENT,
			T_DOC_COMMENT_STRING,
		];
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
		$content = $tokens[$stackPtr]['content'];

		// Basic cleanup (lowercase for comparison)
		$contentLower = strtolower($content);

		foreach ($this->frenchWords as $word) {
			if (strpos($contentLower, $word) !== false) {
				$error = "The comment appears to be in French (word detected: '%s'). Please write in English.";
				$data  = [trim($word)];
				$phpcsFile->addWarning($error, $stackPtr, 'FrenchDetected', $data);
				return; // We stop at the first occurrence.
			}
		}
	}
}
