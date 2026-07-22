<?php
/* Copyright (C) 2026		Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026		Nick Fragoulis
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
 * \file    htdocs/ai/class/privacy_guard.class.php
 * \ingroup ai
 * \brief   Class to mask and unmask sensitive GDPR data in text.
 */

/**
 * Class to manage privacy data masking and unmasking
 */
class PrivacyGuard
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var array<string,string> Map of tokens to their original values
	 */
	private $map = [];

	/**
	 * @var int Counter for generating unique token indices
	 */
	private $index = 0;

	/**
	 * Mask sensitive GDPR data in the query.
	 * This method iterates through a series of patterns to find and replace
	 * sensitive data with tokens.
	 *
	 * @param string $text The input string containing potentially sensitive data.
	 * @return string The masked string with tokens replacing sensitive data.
	 */
	public function mask($text)
	{
		$this->map = [];
		$this->index = 0;

		// References / IDs (e.g. FA24-001, CUS-999)
		// Must contain letters and numbers and separators
		$text = preg_replace_callback(
			'/\b(?=[A-Z0-9]*[0-9])(?=[A-Z0-9]*[A-Z])[A-Z0-9-_]{4,}\b/i',
			/**
			 * @param array<int, string> $m
			 * @return string
			 */
			function (array $m) {
				return $this->createToken($m[0], 'REF');
			},
			$text
		);

		// Credit cards (13-19 digits, various separators)
		// Use the Luhn algorithm to validate credit cards before masking
		$text = preg_replace_callback(
			'/\b(?:\d[ -]*?){13,19}\b/',
			[$this, 'maskCreditCardCallback'],
			$text
		);

		// IBAN (International Bank Account Number)
		$text = preg_replace_callback(
			'/\b[A-Z]{2}[0-9]{2}[a-zA-Z0-9]{4,30}\b/',
			/**
			 * @param array<int, string> $m
			 * @return string
			 */
			function (array $m) {
				return $this->createToken($m[0], 'IBAN');
			},
			$text
		);

		// SWIFT / BIC Codes (8 or 11 characters)
		$text = preg_replace_callback(
			'/\b[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?\b/',
			/**
			 * @param array<int, string> $m
			 * @return string
			 */
			function (array $m) {
				return $this->createToken($m[0], 'SWIFT');
			},
			$text
		);

		// Generic bank account numbers (Context-aware)
		// This looks for numbers preceded by keywords to reduce false positives.
		$text = preg_replace_callback(
			'/(?i)(?:account\s+num(?:ber)?|bank\s+acct|acct\s*#)[:\s#]*\b(\d{8,17})\b/',
			/**
			 * @param array<int, string> $m
			 * @return string
			 */
			function (array $m) {
				return $this->createToken($m[0], 'BANKACCT');
			},
			$text
		);

		// Emails
		$text = preg_replace_callback(
			'/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
			/**
			 * @param array<int, string> $m
			 * @return string
			 */
			function (array $m) {
				return $this->createToken($m[0], 'EMAIL');
			},
			$text
		);

		// Vat numbers (EU: 2 letters + 2-12 chars)
		$taxPatterns = [
			[
				'name' => 'EU VAT Numbers',
				'regex' => '/\b(AT|BE|BG|CY|CZ|DE|DK|EE|EL|ES|FI|FR|GB|GR|HR|HU|IE|IT|LT|LU|LV|MT|NL|PL|PT|RO|SE|SI|SK)(?![a-z])[0-9A-Z]{2,12}\b/i',
				'token' => 'VAT'
			],
			[
				'name' => 'Canadian GST/HST Numbers',
				'regex' => '/\b\d{9}\s*RT\s*\d{4}\b/i',
				'token' => 'TAXID'
			],
			[
				'name' => 'Australian ABN (Australian Business Number)',
				'regex' => '/\b\d{2}\s*\d{3}\s*\d{3}\s*\d{3}\b/',
				'token' => 'TAXID'
			],
			[
				'name' => 'Norwegian MVA (VAT) Numbers',
				'regex' => '/\b\d{9}\s*MVA\b/i',
				'token' => 'TAXID'
			],
			[
				'name' => 'Swiss VAT Numbers (MWST/TVA/IVA)',
				'regex' => '/\bCHE-?\d{3}\.?\d{3}\.?\d{3}\s*(MWST|TVA|IVA)\b/i',
				'token' => 'TAXID'
			],
		];

		foreach ($taxPatterns as $pattern) {
			$text = preg_replace_callback(
				$pattern['regex'],
				/**
				 * @param array<int, string> $m
				 * @return string
				 */
				function (array $m) use ($pattern) {
					return $this->createToken($m[0], $pattern['token']);
				},
				$text
			);
		}

		// Phone numbers
		$phonePatterns = [
			[
				'name' => 'Generic International Numbers',
				'regex' => '/\b(?:\+|00)[0-9][0-9-.\s()]{8,}\b/',
				'token' => 'PHONE'
			],
			[
				'name' => 'Greek National Numbers',
				// This pattern is specific to Greece.
				// Landlines: 10 digits starting with '2' (e.g., 210 123 4567).
				// Mobiles: 10 digits starting with '69' (e.g., 698 123 4567).
				// It matches numbers with optional separators like spaces, hyphens, or dots.
				'regex' => '/\b(?:2[0-9-.\s()]{9}|69[0-9-.\s()]{8})\b/',
				'token' => 'PHONE'
			],
			[
				'name' => 'French National Numbers',
				// This pattern matches standard 10-digit French numbers.
				// It handles common formats like "01 23 45 67 89", "01.23.45.67.89", or "0123456789".
				// It covers all geographic prefixes (01-05) and mobiles (06, 07).
				'regex' => '/\b0[1-9](?:[\s.-]?\d){8}\b/',
				'token' => 'PHONE'
			],
		];

		foreach ($phonePatterns as $pattern) {
			$text = preg_replace_callback(
				$pattern['regex'],
				/**
				 * @param array<int, string> $m
				 * @return string
				 */
				function (array $m) use ($pattern) {
					return $this->createToken($m[0], $pattern['token']);
				},
				$text
			);
		}

		// Address patterns
		// Initialize translation and exclusions
		// We need the global $langs object to get dynamic month names.
		global $langs;

		// Hardcoded Fallbacks (English, French, Spanish, German, Greek)
		// We keep these hardcoded because an invoice might be in English even if the ERP is in Greek.
		$hardcoded_excludes = [
			// English
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December',
			'Jan',
			'Feb',
			'Mar',
			'Apr',
			'Jun',
			'Jul',
			'Aug',
			'Sep',
			'Oct',
			'Nov',
			'Dec',
			// Greek
			'Ιανουάριος',
			'Φεβρουάριος',
			'Μάρτιος',
			'Απρίλιος',
			'Μάιος',
			'Ιούνιος',
			'Ιούλιος',
			'Αύγουστος',
			'Σεπτέμβριος',
			'Οκτώβριος',
			'Νοέμβριος',
			'Δεκέμβριος',
			'Ιαν',
			'Φεβ',
			'Μαρ',
			'Απρ',
			'Μαι',
			'Ιουν',
			'Ιουλ',
			'Αυγ',
			'Σεπ',
			'Οκτ',
			'Νοε',
			'Δεκ',
			// French
			'Janvier',
			'Février',
			'Mars',
			'Avril',
			'Mai',
			'Juin',
			'Juillet',
			'Août',
			'Septembre',
			'Octobre',
			'Novembre',
			'Décembre',
			// German
			'Januar',
			'Februar',
			'März',
			'Juni',
			'Juli',
			'Oktober',
			'Dezember',
			// Spanish
			'Enero',
			'Febrero',
			'Marzo',
			'Abril',
			'Mayo',
			'Junio',
			'Julio',
			'Agosto',
			'Septiembre',
			'Octubre',
			'Noviembre',
			'Diciembre',
			// ERP Noise (Common False Positives)
			'Page',
			'Pag',
			'Vol',
			'Volume',
			'Inv',
			'Invoice',
			'Tel',
			'Fax',
			'Mob',
			'Email',
			'Vat',
			'Tax',
			'Sarl',
			'Gmbh',
			'Inc',
			'Ltd',
			'Total',
			'Subtotal'
		];

		// Dynamic Dolibarr Translations
		// If $langs is available, we add the months in the current user's language.
		$dynamic_excludes = [];
		if (is_object($langs)) {
			for ($i = 1; $i <= 12; $i++) {
				$key = sprintf("%02d", $i); // 01, 02...
				$dynamic_excludes[] = $langs->trans('Month' . $key);       // Full name
				$dynamic_excludes[] = $langs->trans('MonthShort' . $key);  // Short name
			}
		}

		// Merge and Deduplicate
		// We combine hardcoded list + dynamic list + noise words
		$all_excludes_array = array_unique(array_merge($hardcoded_excludes, $dynamic_excludes));

		// Remove empty entries just in case
		$all_excludes_array = array_filter($all_excludes_array);

		// Create the Regex string: "January|Feb|Μάρτιος|Page..."
		// We use preg_quote to ensure no special characters break the regex (though rare in months).
		$excluded_words_regex = implode('|', array_map(
			function (string $word): string {
				return preg_quote($word, '/');
			},
			$all_excludes_array
		));


		// Define address keywords
		$address_keywords = 'Street|St|Road|Rd|Avenue|Ave|Lane|Ln|Boulevard|Blvd|Rue|Via|Strasse|Platz|Drive|Dr|Court|Ct|Way|Plaza|Square|Sq|Οδός|Λεωφόρος|Διεύθυνση|Piazza|Avenida';


		// Define patterns
		$addressPatterns = [
			[
				'name' => 'Number First (e.g., 123 Main St)',
				// 123 Main St
				'regex' => '/\b\d{1,5}\s+(?:[\p{L}\p{N}\.\'\-]+\s+){1,6}(?:' . $address_keywords . ')\b/ui',
				'token' => 'ADDR'
			],
			[
				'name' => 'Keyword First (e.g., Rue de la Paix 12)',
				// Rue de la Paix 12
				'regex' => '/\b(?:' . $address_keywords . ')\s+(?:[\p{L}\p{N}\.\'\-]+\s+){1,6}\d{1,5}\b/ui',
				'token' => 'ADDR'
			],
			[
				'name' => 'Name First, Keyword Middle (e.g., Main St 12)',
				// Main St 12
				'regex' => '/\b(?:[\p{L}\p{N}\.\'\-]+\s+){1,4}(?:' . $address_keywords . ')\s+\d{1,5}\b/ui',
				'token' => 'ADDR'
			],
			[
				'name' => 'Name First, No Keyword (Strict)',
				// Matches: "ΦΟΡΜΙΩΝΟΣ 101" or "Musterway 12"
				// Ignores: "January 2024", "Page 1", "Invoice 2023"
				// Logic:
				// 1. Negative Lookahead (?!(?:...)\b): If next word is in exclusion list, STOP.
				// 2. \p{Lu}: Must start with Uppercase Letter (Unicode safe).
				'regex' => '/\b(?!(?:' . $excluded_words_regex . ')\b)\p{Lu}[\p{L}\p{N}\.\'\-]+\s+\d{1,5}\b/u',
				'token' => 'ADDR'
			],
		];

		foreach ($addressPatterns as $pattern) {
			// We use a callback to replace the found address with a token
			$text = preg_replace_callback(
				$pattern['regex'],
				/**
				 * @param array<int, string> $m
				 * @return string
				 */
				function (array $m) use ($pattern) {
					return $this->createToken($m[0], $pattern['token']);
				},
				$text
			);
		}

		// Zip codes
		$zipCodePatterns = [
			[
				'name' => 'UK Postal Codes',
				// Matches UK postcodes like SW1A 0AA, M1 1AA, B33 8TH.
				'regex' => '/\b[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}\b/i',
				'token' => 'ZIP'
			],
			[
				'name' => 'Canadian Postal Codes',
				// Matches Canadian codes like K1A 0B1 or V6A 1H1.
				'regex' => '/\b[A-CEGHJ-NPR-STV-Z]\d[A-CEGHJ-NPR-STV-Z][ -]?\d[A-CEGHJ-NPR-STV-Z]\d\b/i',
				'token' => 'ZIP'
			],
			[
				'name' => 'French Postal Codes',
				// Matches 5-digit French codes. It's more specific than a generic \d{5}
				// by checking for valid department numbers (01-95) and Corsica (2A, 2B).
				'regex' => '/\b(0[1-9]\d{3}|[1-8]\d{4}|9[0-5]\d{2}|2[AB]\d{3})\b/',
				'token' => 'ZIP'
			],
			[
				'name' => 'Greek Postal Codes',
				// Matches 5-digit Greek codes (e.g., 115 28). Note: This is a generic
				// 5-digit pattern and may have false positives, but is standard for Greece.
				'regex' => '/\b\d{3}\s?\d{2}\b/',
				'token' => 'ZIP'
			],
			[
				'name' => 'US ZIP Codes',
				// Matches 5-digit US ZIP codes and ZIP+4 format.
				'regex' => '/\b\d{5}(?:-\d{4})?\b/',
				'token' => 'ZIP'
			],
		];

		foreach ($zipCodePatterns as $pattern) {
			$text = preg_replace_callback(
				$pattern['regex'],
				/**
				 * @param array<int, string> $m
				 * @return string
				 */
				function (array $m) use ($pattern) {
					return $this->createToken($m[0], $pattern['token']);
				},
				$text
			);
		}

		return $text;
	}

	/**
	 * Restore real data from a masked string.
	 * This method is stateful and relies on the map generated by the preceding mask() call.
	 *
	 * @param string $jsonString The string containing tokens to be replaced.
	 * @return string The unmasked string with original data restored.
	 */
	public function unmask($jsonString)
	{
		if (empty($this->map)) {
			return $jsonString;
		}

		// Handle case where AI might return token inside quotes or escaped
		$search = array_keys($this->map);
		$replace = array_values($this->map);

		return str_replace($search, $replace, $jsonString);
	}

	/**
	 * Callback function for preg_replace_callback to mask credit cards.
	 * It first validates the number using the Luhn algorithm.
	 *
	 * @param array<int,string> $matches The regex matches array.
	 * @return string The token if valid, otherwise the original string.
	 */
	private function maskCreditCardCallback(array $matches)
	{
		$potentialCc = $matches[0];
		if ($this->passesLuhnCheck($potentialCc)) {
			return $this->createToken($potentialCc, 'CC');
		}

		// If it doesn't pass the Luhn check, return the original string unmodified.
		return $potentialCc;
	}

	/**
	 * Validates a number string using the Luhn algorithm.
	 *
	 * @param string $number The number string, can contain spaces or hyphens.
	 * @return bool True if the number is valid, false otherwise.
	 */
	private function passesLuhnCheck($number)
	{
		// Clean the string to contain only digits.
		$digits = preg_replace('/\D/', '', $number);

		// Check if the cleaned string is within a valid length range.
		if (strlen($digits) < 13 || strlen($digits) > 19) {
			return false;
		}

		// Perform the Luhn algorithm.
		$sum = 0;
		$isEvenDigit = false;

		// Iterate from right to left
		for ($i = strlen($digits) - 1; $i >= 0; $i--) {
			$digit = (int) $digits[$i];

			if ($isEvenDigit) {
				$digit *= 2;
				// If the result is two digits, sum them (or subtract 9)
				if ($digit > 9) {
					$digit -= 9;
				}
			}

			$sum += $digit;
			$isEvenDigit = !$isEvenDigit; // Flip the flag for the next digit
		}

		// The number is valid if the sum is a multiple of 10.
		return ($sum % 10) === 0;
	}

	/**
	 * Unmasks a string from an AI response, handling cases where the AI
	 * might have stripped the [[ and ]] delimiters from a token.
	 *
	 * @param string $text The string to unmask.
	 * @return string The fully unmasked string.
	 */
	public function unmaskAiResponse($text)
	{
		if (empty($this->map)) {
			return $text;
		}

		// Standard unmasking
		$text = $this->unmask($text);

		// Next, find and replace any tokens that were stripped by the AI.
		// We iterate through our map and check for the stripped version of each token.
		foreach ($this->map as $fullToken => $originalValue) {
			// The stripped token is the full token without the brackets.
			// e.g., '[[REF_1]]' becomes 'REF_1'
			$strippedToken = substr($fullToken, 2, -2);

			if (strpos($text, $strippedToken) !== false) {
				// We found a stripped token in the text, so we replace it.
				$text = str_replace($strippedToken, $originalValue, $text);
			}
		}

		return $text;
	}

	/**
	 * Create a unique token and store the original value in the map.
	 *
	 * @param string $value The original sensitive value.
	 * @param string $type The type of data (e.g., 'EMAIL').
	 * @return string The generated token (e.g., [[EMAIL_1]]).
	 */
	private function createToken($value, $type)
	{
		$this->index++;
		// Format: [[EMAIL_1]]
		$token = "[[{$type}_{$this->index}]]";
		$this->map[$token] = $value;
		return $token;
	}
}
