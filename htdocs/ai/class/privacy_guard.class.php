<?php
/* Copyright (C) 2026		Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026		Nick Fragoulis
 * Copyright (C) 2026		MDW						<mdeweerd@users.noreply.github.com>
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
	 * @var string Per-request salt appended to every token (see createToken).
	 */
	private $salt = '';

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
		$this->startSession();

		// Emails, before the references: a local part with digits (jean2024@...) would
		// otherwise be taken as a reference and the domain sent unmasked.
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

		// References / IDs (e.g. FA24-001, CUS-999)
		// Must contain letters and numbers and separators. A house number with one
		// letter (221B, 5a) is left to the address patterns below.
		$text = preg_replace_callback(
			'/\b(?!\d{1,5}[A-Za-z]\b)(?=[A-Z0-9]*[0-9])(?=[A-Z0-9]*[A-Z])[A-Z0-9-_]{4,}\b/i',
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
		$text = $this->maskSwift($text);

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
				'name' => 'Greek International Numbers',
				// +30 / 0030 followed by exactly 10 national digits with the valid
				// prefixes (2x landline, 69 mobile) — Greek numbers are 10 digits,
				// so this is checked strictly; other countries fall through to the
				// length-flexible generic pattern below.
				'regex' => '/(?<![\w+])(?:\+30|0030)[\s.\-]?(?:2\d|69)(?:[\s.\-()]?\d){8}(?!\d)/',
				'token' => 'PHONE'
			],
			[
				'name' => 'Generic International Numbers',
				// Counts DIGITS (9-14 after the prefix) with optional single separators
				// between them; the old char-counting class broke on spaced formats.
				// No \b before '+': space->'+' is not a word boundary, so that \b
				// could never match and the pattern was dead for '+30 ...' numbers.
				'regex' => '/(?<![\w+])(?:\+|00)\d{1,3}(?:[\s.\-()]?\d){8,13}(?!\d)/',
				'token' => 'PHONE'
			],
			[
				'name' => 'Greek National Numbers',
				// This pattern is specific to Greece.
				// Landlines: 10 digits starting with '2' (e.g., 210 123 4567).
				// Mobiles: 10 digits starting with '69' (e.g., 698 123 4567).
				// It matches numbers with optional separators like spaces, hyphens, or dots.
				// Exactly 10 digits (landline 2x..., mobile 69...), separators optional
				// BETWEEN digits. The old pattern counted characters: '210 2461234'
				// (11 chars) overflowed {9} and slipped through unmasked, while
				// '2026-09-07' (9 chars after the 2) was masked as a phone. The
				// lookarounds forbid digit-adjacency, so it never fires inside longer
				// digit runs (EAN barcodes, references).
				'regex' => '/(?<!\d)(?:2\d|69)(?:[\s.\-()]?\d){8}(?!\d)/',
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
		// An address is recognised by its shape around a street word (Street, Rue,
		// Via, Straße, Οδός...) or a street suffix (Musterweg), never by a list of
		// words that are not streets: such a list never ends ("Customer 4821",
		// "Order 1007"...). Without a street word, a name and a number are an
		// address only when a postcode and a town follow (ΦΟΡΜΙΩΝΟΣ 101, 115 28 Athens).
		// No i flag on the shapes that start with a word: with it, \p{Lu} also
		// takes lowercase letters and a match grows leftwards over the sentence.
		$address_keywords = 'Street|Road|Avenue|Lane|Boulevard|Drive|Court|Way|Plaza|Square|Rue|Allée|Impasse|Chemin|Place|Quai|Via|Viale|Piazza|Corso|Avenida|Paseo|Camino|Strasse|Straße|Platz|Gasse|Weg|Οδός|Οδ\.|Λεωφόρος|Λεωφ\.|Πλατεία';
		$address_abbreviations = 'St|Rd|Ave|Ln|Blvd|Dr|Ct|Sq|Str';
		// Written in lowercase after the number, as French, Spanish and Italian
		// addresses do ("12 rue de Rivoli"); English street words are capitalised.
		$address_keywords_lower = 'rue|avenue|boulevard|chemin|place|allée|impasse|quai|avenida|paseo|camino|via|viale|piazza|corso';
		$address_suffixes = 'strasse|straße|weg|platz|gasse|allee|ring|damm|ufer|laan|straat|gracht|vej|gatan|gade';
		$word = '\p{Lu}[\p{L}\p{N}\.\'\-]*';
		$particle = '\p{Ll}{1,3}\b';	// de, la, du, del, di, von, der
		// A house number (12, 12B), never part of a larger number: the 3 of 3,374.00.
		// A comma may follow it, as in "Musterweg 12, 10115 Berlin".
		$number = '\d{1,5}[A-Za-z]?(?!\d|[.,]\d)\b';
		// A postcode then a town, not a currency: "4821, 12500 EUR" is no address.
		$town = '(?=\s*,?\s*(?:\d{3}\s?\d{2}|\d{4,5})(?!\d)(?!\s*(?:[A-Z]{3}\b|[€$£¥₹%]))\s+\p{Lu})';

		$addressPatterns = [
			[
				'name' => 'Number First (e.g., 12 rue de Rivoli, 123 Main St, 221B Baker Street)',
				'regex' => '/\b\d{1,5}[A-Za-z]?,?\s+(?:' . $word . '\s+){0,3}(?:' . $address_keywords . '|' . $address_abbreviations . '|' . $address_keywords_lower . ')\b\.?(?:\s+(?:' . $word . '|' . $particle . ')){0,4}/u',
				'token' => 'ADDR'
			],
			[
				'name' => 'Name First, Keyword After (e.g., Main Street 42, Main St. 42)',
				'regex' => '/\b' . $word . '(?:\s+' . $word . '){0,2}\s+(?:' . $address_keywords . '|' . $address_abbreviations . ')\.?\s+' . $number . '/u',
				'token' => 'ADDR'
			],
			[
				'name' => 'Street Suffix (e.g., Musterweg 12, Hauptstraße 5)',
				'regex' => '/\b\p{Lu}\p{L}*(?:' . $address_suffixes . ')\.?\s+' . $number . '/u',
				'token' => 'ADDR'
			],
			[
				'name' => 'Keyword First (e.g., Rue Victor Hugo 5, Οδός Ερμού 12)',
				// At least one name between the street word and the number: "Square 3" is no address.
				'regex' => '/\b(?:' . $address_keywords . ')(?:\s+(?:' . $word . '|' . $particle . ')){1,4}\s+' . $number . '/u',
				'token' => 'ADDR'
			],
			[
				'name' => 'No Keyword, Postcode And Town After (e.g., ΦΟΡΜΙΩΝΟΣ 101, 115 28 Athens)',
				'regex' => '/\b' . $word . '(?:\s+' . $word . '){0,2}\s+' . $number . $town . '/u',
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
				'token' => 'ZIP',
				'amounts_look_alike' => true
			],
			[
				'name' => 'Greek Postal Codes',
				// Matches 5-digit Greek codes (e.g., 115 28). Note: This is a generic
				// 5-digit pattern and may have false positives, but is standard for Greece.
				'regex' => '/\b\d{3}\s?\d{2}\b/',
				'token' => 'ZIP',
				'amounts_look_alike' => true
			],
			[
				'name' => 'US ZIP Codes',
				// Matches 5-digit US ZIP codes and ZIP+4 format.
				'regex' => '/\b\d{5}(?:-\d{4})?\b/',
				'token' => 'ZIP',
				'amounts_look_alike' => true
			],
		];

		foreach ($zipCodePatterns as $pattern) {
			if (!empty($pattern['amounts_look_alike'])) {
				// A five-digit postcode looks like an amount: masked only outside of one.
				$text = $this->maskOutsideAmounts($text, $pattern['regex'], $pattern['token']);
				continue;
			}
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
	 * Mask the matches of a pattern, except those that are an amount: part of a
	 * larger number (12,500.00), next to a currency (12500 INR, EUR 12500, 12500 €)
	 * or a percent sign, or right after a word saying it is an amount
	 * (Total: 12500, Amount 12500).
	 *
	 * @param	string	$text	Text to mask
	 * @param	string	$regex	Pattern of the values to mask
	 * @param	string	$token	Token type
	 * @return	string			Masked text
	 */
	private function maskOutsideAmounts($text, $regex, $token)
	{
		$m = array();
		if (!preg_match_all($regex, $text, $m, PREG_OFFSET_CAPTURE)) {
			return $text;
		}
		$labels = 'amount|total|subtotal|sum|price|qty|quantity|balance|due|paid|net|gross|montant|prix|somme|betrag|summe|preis|importe|precio|ποσό|σύνολο|τιμή|αξία';
		$replace = array();
		foreach ($m[0] as $found) {
			$value = (string) $found[0];
			$start = (int) $found[1];
			$end = $start + strlen($value);
			$before = substr($text, max(0, $start - 30), $start - max(0, $start - 30));
			$after = substr($text, $end, 12);
			if (preg_match('/\d[.,]$/', $before) || preg_match('/^[.,]\d/', $after)) {
				continue;	// part of a larger number
			}
			if (preg_match('/(?:[€$£¥₹]|\b[A-Z]{3})\s*$/u', $before) || preg_match('/^\s*(?:[€$£¥₹%]|[A-Z]{3}\b)/u', $after)) {
				continue;	// next to a currency or a percent sign
			}
			if (preg_match('/(?:'.$labels.')\s*[:=]?\s*$/iu', $before)) {
				continue;	// said to be an amount
			}
			$replace[] = array($start, strlen($value), $this->createToken($value, $token));
		}
		// From the end, so the offsets of the earlier matches stay valid.
		foreach (array_reverse($replace) as $r) {
			$text = substr($text, 0, $r[0]).$r[2].substr($text, $r[0] + $r[1]);
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
	 * Mask SWIFT / BIC codes.
	 *
	 * Any word of 8 or 11 capital letters has the shape of a BIC, so shape alone
	 * masked ordinary words typed in capitals ("SOLUTION", "CUSTOMER",
	 * "SUPPLIER") and the model then searched for the placeholder. A code is
	 * masked when it is introduced as one ("BIC", "SWIFT", a "bic" field of a
	 * record, within the 30 characters before it), or when its location or
	 * branch part carries a digit, which words do not.
	 *
	 * @param string $text Text to mask.
	 * @return string Masked text.
	 */
	private function maskSwift($text)
	{
		$m = array();
		if (!preg_match_all('/\b[A-Z]{6}([A-Z0-9]{2})([A-Z0-9]{3})?\b/', $text, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
			return $text;
		}
		// Right to left, so the offsets of the earlier codes stay valid.
		foreach (array_reverse($m) as $hit) {
			list($code, $pos) = $hit[0];
			$tail = $hit[1][0].(isset($hit[2]) ? $hit[2][0] : '');
			$start = max(0, $pos - 30);
			$before = substr($text, $start, $pos - $start);
			if (!preg_match('/(bic|swift)/i', $before) && !preg_match('/[0-9]/', $tail)) {
				continue;
			}
			$text = substr($text, 0, $pos).$this->createToken($code, 'SWIFT').substr($text, $pos + strlen($code));
		}

		return $text;
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
	 * Start the masking session for this guard instance, once.
	 *
	 * @return void
	 */
	private function startSession()
	{
		// One masking session per guard instance: mask() and maskNames() may
		// be called several times for one request (query, context line), and
		// every token they issue must survive in the same map until unmask.
		if ($this->salt !== '') {
			return;
		}
		$this->map = [];
		$this->index = 0;
		$this->salt = dol_substr(dol_hash(uniqid((string) mt_rand(), true), 'md5'), 0, 4);
	}

	/**
	 * Create a unique token and store the original value in the map.
	 *
	 * @param string $value The original sensitive value.
	 * @param string $type  The type of data (e.g., 'EMAIL').
	 * @return string The generated token (e.g., [[EMAIL_1a2b]]).
	 */
	private function createToken($value, $type)
	{
		// Reuse the token already issued for this value in this request, so
		// the same entity reads as the same placeholder throughout the prompt.
		$existing = array_search($value, $this->map, true);
		if ($existing !== false) {
			return (string) $existing;
		}

		$this->index++;
		// Format: [[EMAIL_1a2b3c]]. The per-request salt keeps placeholders
		// unpredictable (a provider cannot correlate "entity 1" across
		// requests) and prevents collisions with literal [[TYPE_n]] text.
		$token = "[[{$type}_{$this->index}{$this->salt}]]";
		$this->map[$token] = $value;
		return $token;
	}

	/**
	 * Mask the names of the objects carried in this payload.
	 *
	 * Thirdparty and product names are arbitrary strings: no pattern can
	 * recognize them, so they are masked from a dictionary built out of the
	 * values actually present in the text. Longest first, so "ACME Ltd" is
	 * replaced before "ACME".
	 *
	 * @param string        $text  Text to mask.
	 * @param array<string> $names Candidate names to look for.
	 * @return string Masked text.
	 */
	public function maskNames($text, $names)
	{
		$this->startSession();

		$clean = array();
		foreach ($names as $name) {
			$name = trim((string) $name);
			// Very short names would shred unrelated words.
			if (dol_strlen($name) >= 4) {
				$clean[] = $name;
			}
		}
		if (empty($clean)) {
			return $text;
		}
		$clean = array_unique($clean);
		usort($clean, /** @return int */ function (string $a, string $b) {
			return dol_strlen($b) - dol_strlen($a);
		});
		foreach ($clean as $name) {
			// Whole words only, case-insensitive in any script ("Acme" must not
			// eat "Acmetal"), and never inside a placeholder already issued: a
			// company called "Name" would otherwise break [[NAME_1ab12]].
			$pattern = '/(?<![\p{L}\p{N}])'.preg_quote($name, '/').'(?![\p{L}\p{N}])/iu';
			$parts = preg_split('/(\[\[[A-Z]+_[^\]]*\]\])/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
			if (!is_array($parts)) {
				continue;
			}
			$found = false;
			foreach ($parts as $pi => $part) {
				if ($pi % 2 === 1 || !preg_match($pattern, $part)) {
					continue;
				}
				$found = true;
				$parts[$pi] = (string) preg_replace($pattern, $this->createToken($name, 'NAME'), $part);
			}
			if ($found) {
				$text = implode('', $parts);
			}
		}

		return $text;
	}
}
