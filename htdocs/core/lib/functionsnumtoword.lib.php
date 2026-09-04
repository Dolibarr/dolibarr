<?php
/* Copyright (C) 2015       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Víctor Ortiz Pérez      <victor@accett.com.mx>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file			htdocs/core/lib/functionsnumtoword.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all frequently used functions.
 */

/**
 * Function to return a number into a text.
 * May use module NUMBERWORDS if found.
 *
 * @param	float       $num			Number to convert (must be a numeric value, like reported by price2num())
 * @param	Translate   $langs			Language
 * @param	string      $currency		''=number to translate | 'XX'=currency code to use into text
 * @param	boolean     $centimes		false=no cents/centimes | true=there is cents/centimes
 * @return 	string|false			    Text of the number
 */
function dol_convertToWord($num, $langs, $currency = '', $centimes = false)
{
	//$num = str_replace(array(',', ' '), '', trim($num));	This should be useless since $num MUST be a php numeric value
	if (!$num) {
		return false;
	}

	// Dedicated converter for French. The generic algorithm below is built on the English
	// number structure and cannot express French spelling rules (et, hyphens, agreement of
	// cent/vingt, elision of "un" before cent/mille). See dolConvertToWordFrench().
	$langcode = $langs->getDefaultLang(0);
	if (preg_match('/^fr/i', (string) $langcode)) {
		return dolConvertToWordFrench($num, $langs, $currency, $centimes);
	}

	$numbackup = $num;

	if (isModEnabled('numberwords')) {
		$concatWords = $langs->getLabelFromNumber((string) $num, $currency);
		return $concatWords;
	} else {
		$TNum = explode('.', (string) $num);

		$num = abs((int) $TNum[0]);
		$words = array();
		$list1 = array(
			'',
			$langs->transnoentitiesnoconv('one'),
			$langs->transnoentitiesnoconv('two'),
			$langs->transnoentitiesnoconv('three'),
			$langs->transnoentitiesnoconv('four'),
			$langs->transnoentitiesnoconv('five'),
			$langs->transnoentitiesnoconv('six'),
			$langs->transnoentitiesnoconv('seven'),
			$langs->transnoentitiesnoconv('eight'),
			$langs->transnoentitiesnoconv('nine'),
			$langs->transnoentitiesnoconv('ten'),
			$langs->transnoentitiesnoconv('eleven'),
			$langs->transnoentitiesnoconv('twelve'),
			$langs->transnoentitiesnoconv('thirteen'),
			$langs->transnoentitiesnoconv('fourteen'),
			$langs->transnoentitiesnoconv('fifteen'),
			$langs->transnoentitiesnoconv('sixteen'),
			$langs->transnoentitiesnoconv('seventeen'),
			$langs->transnoentitiesnoconv('eighteen'),
			$langs->transnoentitiesnoconv('nineteen')
		);
		$list2 = array(
			'',
			$langs->transnoentitiesnoconv('ten'),
			$langs->transnoentitiesnoconv('twenty'),
			$langs->transnoentitiesnoconv('thirty'),
			$langs->transnoentitiesnoconv('forty'),
			$langs->transnoentitiesnoconv('fifty'),
			$langs->transnoentitiesnoconv('sixty'),
			$langs->transnoentitiesnoconv('seventy'),
			$langs->transnoentitiesnoconv('eighty'),
			$langs->transnoentitiesnoconv('ninety'),
			$langs->transnoentitiesnoconv('hundred')
		);
		$list3 = array(
			'',
			$langs->transnoentitiesnoconv('thousand'),
			$langs->transnoentitiesnoconv('million'),
			$langs->transnoentitiesnoconv('billion'),
			$langs->transnoentitiesnoconv('trillion'),
			$langs->transnoentitiesnoconv('quadrillion')
		);

		$num_length = strlen((string) $num);
		$levels = (int) (($num_length + 2) / 3);
		$max_length = $levels * 3;
		$num = substr('00'.$num, -$max_length);
		$num_levels = str_split($num, 3);
		$nboflevels = count($num_levels);
		for ($i = 0; $i < $nboflevels; $i++) {
			$levels--;
			$hundreds = (int) ((int) $num_levels[$i] / 100);
			$hundreds = ($hundreds ? ' '.$list1[$hundreds].' '.$langs->transnoentities('hundred').($hundreds == 1 ? '' : 's').' ' : '');
			$tens = (int) ((int) $num_levels[$i] % 100);
			$singles = '';
			if ($tens < 20) {
				$tens = ($tens ? ' '.$list1[$tens].' ' : '');
			} else {
				$tsd = (int) ($tens / 10);  // tens digit (2-9)
				$usd = (int) ($tens % 10);  // units digit (0-9)

				// French-style systems: 70-79 = sixty + (10-19), 90-99 = eighty + (10-19)
				// Detection: if the translation of "seventy"/"ninety" contains the word for "ten"
				$tenWord = trim($list1[10]);  // e.g. "dix"
				if ($usd > 0 && ($tsd == 7 || $tsd == 9) && $tenWord !== '' && strpos(trim($list2[$tsd]), $tenWord) !== false) {
					// Use the base ten word (60 for 70s, 80 for 90s) + teen word (11-19)
					$baseWord = trim($list2[$tsd - 1]);
					// Remove trailing 's' (e.g. quatre-vingts → quatre-vingt when used in composition)
					if (substr($baseWord, -1) === 's') {
						$baseWord = substr($baseWord, 0, -1);
					}
					$tens = ' '.$baseWord.' ';
					$singles = ' '.$list1[10 + $usd].' ';
				} else {
					$tens = ' '.$list2[$tsd].' ';
					$singles = ' '.$list1[$usd].' ';
				}
			}
			$words[] = $hundreds.$tens.$singles.(($levels && (int) ($num_levels[$i])) ? ' '.$list3[$levels].' ' : '');
		} //end for loop
		$commas = count($words);
		if ($commas > 1) {
			$commas -= 1;
		}
		$concatWords = implode(' ', $words);
		// Delete multi whitespaces
		$concatWords = trim(preg_replace('/[ ]+/', ' ', $concatWords));

		if (!empty($currency)) {
			$concatWords .= ' '.$currency;
		}

		// If we need to write cents, spell the 2-digit cents value (e.g. 0.07 => 7 cents, not 70)
		$tmptab = explode('.', number_format((float) abs($numbackup), 2, '.', ''));
		$cents = isset($tmptab[1]) ? (int) $tmptab[1] : 0;

		if ($cents > 0) {
			if (!empty($currency)) {
				$concatWords .= ' '.$langs->transnoentities('and');
			}

			$concatWords .= ' '.dol_convertToWord((float) $cents, $langs, '', false);
			if (!empty($currency)) {
				$concatWords .= ' '.$langs->transnoentities('centimes');
			}
		}
		return $concatWords;
	}
}


/**
 * Convert a non-negative integer into French words (standard French: soixante-dix,
 * quatre-vingts, quatre-vingt-dix). Handles "et", hyphens and the agreement of
 * "cent" and "quatre-vingt(s)".
 *
 * @param	int		$n			Integer to convert (sign is ignored)
 * @param	bool	$reform		false = traditional spelling (hyphen only between tens and units),
 * 								true = 1990 rectified spelling (hyphens everywhere)
 * @return	string				Number written in French words
 */
function dolConvertIntToFrenchWords($n, $reform = false)
{
	$n = (int) $n;
	if ($n < 0) {
		$n = -$n;
	}
	if ($n === 0) {
		return 'zéro';
	}

	$sep = $reform ? '-' : ' ';
	$etjoin = $reform ? '-et-' : ' et ';

	$units = array(
		'zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
		'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize',
		'dix-sept', 'dix-huit', 'dix-neuf'
	);
	$tensmap = array(2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante');
	$scales = array('', 'mille', 'million', 'milliard', 'billion', 'billiard', 'trillion');

	// Spell a number from 1 to 99
	$below100 = function (int $m) use ($units, $tensmap, $etjoin): string {
		if ($m < 20) {
			return $units[$m] ?? '';
		}
		$t = intdiv($m, 10);
		$u = $m % 10;
		if ($t == 7 || $t == 9) {
			// 70-79 = soixante + (10..19), 90-99 = quatre-vingt + (10..19)
			$base = ($t == 7) ? 'soixante' : 'quatre-vingt';
			if ($t == 7 && $u == 1) {
				return $base.$etjoin.$units[10 + $u]; // soixante et onze (but quatre-vingt-onze takes no "et")
			}
			return $base.'-'.$units[10 + $u];
		}
		if ($t == 8) {
			// 80 = quatre-vingts, 81..89 = quatre-vingt-x (no "s", no "et")
			return ($u == 0) ? 'quatre-vingts' : 'quatre-vingt-'.$units[$u];
		}
		// 20..69
		$w = $tensmap[$t] ?? '';
		if ($u == 0) {
			return $w;
		}
		if ($u == 1) {
			return $w.$etjoin.$units[1]; // vingt et un .. soixante et un
		}
		return $w.'-'.$units[$u];
	};

	// Split into groups of 3 digits, lowest group first (index 0 = units, 1 = thousands, 2 = millions...)
	$groups = array();
	$tmp = $n;
	while ($tmp > 0) {
		$groups[] = $tmp % 1000;
		$tmp = intdiv($tmp, 1000);
	}
	$ng = count($groups);

	$pieces = array(); // each entry: array('text' => string, 'noun' => bool) ; noun = million/milliard scale
	for ($g = $ng - 1; $g >= 0; $g--) {
		$val = $groups[$g];
		if ($val == 0) {
			continue;
		}
		$h = intdiv($val, 100);
		$r = $val % 100;

		// "cent" agrees (becomes "cents") only when multiplied and not followed by another numeral:
		// plural when terminal (units group) or directly before a noun scale (million+),
		// but invariable before "mille".
		$centplural = ($h >= 2 && $r == 0 && ($g === 0 || $g >= 2));

		$text = '';
		if ($h >= 1) {
			$text = ($h == 1) ? 'cent' : $units[$h].$sep.'cent';
			if ($centplural) {
				$text .= 's';
			}
		}
		if ($r > 0) {
			$rtext = $below100($r);
			// "quatre-vingts" keeps its "s" only when terminal or before a noun scale, not before "mille".
			if ($r == 80 && $g === 1) {
				$rtext = 'quatre-vingt';
			}
			$text = ($text === '') ? $rtext : $text.$sep.$rtext;
		}

		if ($g == 1) {
			// thousands: "mille" is invariable and takes no leading "un"
			$text = ($val == 1) ? 'mille' : $text.$sep.'mille';
			$pieces[] = array('text' => $text, 'noun' => false);
		} elseif ($g >= 2) {
			$scaleword = isset($scales[$g]) ? $scales[$g] : '';
			if ($scaleword !== '') {
				if ($val >= 2) {
					$scaleword .= 's'; // millions, milliards...
				}
				$text .= ' '.$scaleword; // a noun scale is always separated by a space
			}
			$pieces[] = array('text' => $text, 'noun' => true);
		} else {
			$pieces[] = array('text' => $text, 'noun' => false);
		}
	}

	$result = '';
	foreach ($pieces as $i => $piece) {
		if ($i === 0) {
			$result = $piece['text'];
		} else {
			// after a noun scale (million+) keep a space, otherwise use the chosen separator
			$result .= ($pieces[$i - 1]['noun'] ? ' ' : $sep).$piece['text'];
		}
	}

	return $result;
}


/**
 * Convert a number into French words. Used by dol_convertToWord() for French languages.
 *
 * @param	float		$num		Number to convert
 * @param	Translate	$langs		Language object (used for the "and"/"centime"/"centimes" labels)
 * @param	string		$currency	'' = no currency word, otherwise a currency string appended after the integer part
 * @param	bool		$centimes	true = render the decimal part as "et X centime(s)"
 * @return	string					Number written in French words
 */
function dolConvertToWordFrench($num, $langs, $currency = '', $centimes = false)
{
	// Spelling convention, set with constant CONVERT_TO_WORD_FR (Home - Setup - Other setup):
	// - default/'PRE1990' = traditional spelling (hyphen only between tens and units)
	// - 'REFORME1990'      = 1990 rectified spelling (hyphens between all the numeral words)
	$reform = (getDolGlobalString('CONVERT_TO_WORD_FR') === 'REFORME1990');

	$numstr = number_format((float) $num, 2, '.', '');
	$negative = false;
	if (strpos($numstr, '-') === 0) {
		$negative = true;
		$numstr = substr($numstr, 1);
	}
	$tmptab = explode('.', $numstr);
	$intpart = (int) $tmptab[0];
	$cents = isset($tmptab[1]) ? (int) $tmptab[1] : 0;

	$result = ($intpart === 0) ? '' : dolConvertIntToFrenchWords($intpart, $reform);

	if (!empty($currency)) {
		if ($result === '') {
			$result = 'zéro';
		}
		$result .= ' '.$currency;
	}

	if ($cents > 0) {
		if ($centimes) {
			$label = $langs->transnoentitiesnoconv($cents === 1 ? 'centime' : 'centimes');
			$and = $langs->transnoentitiesnoconv('and');
			$centtext = dolConvertIntToFrenchWords($cents, $reform).' '.$label;
			if ($result === '') {
				$result = $centtext;
			} else {
				$result .= ' '.$and.' '.$centtext;
			}
		} else {
			// Not an amount: spell the decimal part after "virgule"
			$result = ($result === '' ? 'zéro' : $result).' virgule '.dolConvertIntToFrenchWords($cents, $reform);
		}
	}

	if ($result === '') {
		$result = 'zéro';
	}
	if ($negative) {
		$result = 'moins '.$result;
	}

	return $result;
}


/**
 * Function to return number or amount in text.
 *
 * @param	float 	    $numero			Number to convert
 * @param	Translate	$langs			Language
 * @param	string	    $numorcurrency	'number' or 'amount'
 * @return 	string|int  	       			Text of the number or -1 in case TOO LONG (more than 1000000000000.99)
 *
 * @deprecated Use dol_convertToWord instead
 */
function dolNumberToWord($numero, $langs, $numorcurrency = 'number')
{
	// If the number is negative convert to positive and return -1 if it is too long
	if ($numero < 0) {
		$numero *= -1;
	}
	if ($numero >= 1000000000001) {
		return -1;
	}

	// Get 2 decimals to cents, another functions round or truncate
	$strnumber = number_format($numero, 10);
	$len = strlen($strnumber);
	$parte_decimal = '00';  // For static analysis, strnumber should contain '.'
	for ($i = 0; $i < $len; $i++) {
		if ($strnumber[$i] == '.') {
			$parte_decimal = $strnumber[$i + 1].$strnumber[$i + 2];
			break;
		}
	}

	/* Dolibarr 3.6.2 doesn't have $langs->default, why ask $lang like a parameter in case it exists? */
	if (((is_object($langs) && $langs->getDefaultLang(0) == 'es_MX') || (!is_object($langs) && $langs == 'es_MX')) && $numorcurrency == 'currency') {
		if ($numero >= 1 && $numero < 2) {
			return ("UN PESO ".$parte_decimal." / 100 M.N.");
		} elseif ($numero >= 0 && $numero < 1) {
			return ("CERO PESOS ".$parte_decimal." / 100 M.N.");
		} elseif ($numero >= 1000000 && $numero < 1000001) {
			return ("UN MILL&OacuteN DE PESOS ".$parte_decimal." / 100 M.N.");
		} elseif ($numero >= 1000000000000 && $numero < 1000000000001) {
			return ("UN BILL&OacuteN DE PESOS ".$parte_decimal." / 100 M.N.");
		} else {
			$entexto = "";
			$number = $numero;
			if ($number >= 1000000000) {
				$CdMMillon = (int) ($numero / 100000000000);
				$numero -= $CdMMillon * 100000000000;
				$DdMMillon = (int) ($numero / 10000000000);
				$numero -= $DdMMillon * 10000000000;
				$UdMMillon = (int) ($numero / 1000000000);
				$numero -= $UdMMillon * 1000000000;
				$entexto .= hundreds2text($CdMMillon, $DdMMillon, $UdMMillon);
				$entexto .= " MIL ";
			} else {
				$CdMMillon = 0;
				$DdMMillon = 0;
				$UdMMillon = 0;
			}
			if ($number >= 1000000) {
				$CdMILLON = (int) ($numero / 100000000);
				$numero -= $CdMILLON * 100000000;
				$DdMILLON = (int) ($numero / 10000000);
				$numero -= $DdMILLON * 10000000;
				$udMILLON = (int) ($numero / 1000000);
				$numero -= $udMILLON * 1000000;
				$entexto .= hundreds2text($CdMILLON, $DdMILLON, $udMILLON);
				if (!$CdMMillon && !$DdMMillon && !$UdMMillon && !$CdMILLON && !$DdMILLON && $udMILLON == 1) {
					$entexto .= " MILL&OacuteN ";
				} else {
					$entexto .= " MILLONES ";
				}
			}

			if ($number >= 1000) {
				$cdm = (int) ($numero / 100000);
				$numero -= $cdm * 100000;
				$ddm = (int) ($numero / 10000);
				$numero -= $ddm * 10000;
				$udm = (int) ($numero / 1000);
				$numero -= $udm * 1000;
				$entexto .= hundreds2text($cdm, $ddm, $udm);
				if ($cdm || $ddm || $udm) {
					$entexto .= " MIL ";
				}
			} else {
				$ddm = 0;
				$cdm = 0;
				$udm = 0;
			}
			$c = (int) ($numero / 100);
			$numero -= $c * 100;
			$d = (int) ($numero / 10);
			$u = (int) $numero - $d * 10;
			$entexto .= hundreds2text($c, $d, $u);
			if (!$cdm && !$ddm && !$udm && !$c && !$d && !$u && $number > 1000000) {
				$entexto .= " DE";
			}
			$entexto .= " PESOS ".$parte_decimal." / 100 M.N.";
		}
		return $entexto;
	}
	return -1;
}

/**
 * hundreds2text
 *
 * @param integer $hundreds     Hundreds
 * @param integer $tens         Tens
 * @param integer $units        Units
 * @return string
 */
function hundreds2text($hundreds, $tens, $units)
{
	if ($hundreds == 1 && $tens == 0 && $units == 0) {
		return "CIEN";
	}
	$centenas = array("CIENTO", "DOSCIENTOS", "TRESCIENTOS", "CUATROCIENTOS", "QUINIENTOS", "SEISCIENTOS", "SETECIENTOS", "OCHOCIENTOS", "NOVECIENTOS");
	$decenas = array("", "", "TREINTA ", "CUARENTA ", "CINCUENTA ", "SESENTA ", "SETENTA ", "OCHENTA ", "NOVENTA ");
	$veintis = array("VEINTE", "VEINTIUN", "VEINTID&OacuteS", "VEINTITR&EacuteS", "VEINTICUATRO", "VEINTICINCO", "VEINTIS&EacuteIS", "VEINTISIETE", "VEINTIOCHO", "VEINTINUEVE");
	$diecis = array("DIEZ", "ONCE", "DOCE", "TRECE", "CATORCE", "QUINCE", "DIECIS&EacuteIS", "DIECISIETE", "DIECIOCHO", "DIECINUEVE");
	$unidades = array("UN", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE");
	$entexto = "";
	if ($hundreds != 0) {
		$entexto .= $centenas[$hundreds - 1];
	}
	if ($tens > 2) {
		if ($hundreds != 0) {
			$entexto .= " ";
		}
		$entexto .= $decenas[$tens - 1];
		if ($units != 0) {
			$entexto .= " Y ";
			$entexto .= $unidades[$units - 1];
		}
		return $entexto;
	} elseif ($tens == 2) {
		if ($hundreds != 0) {
			$entexto .= " ";
		}
		$entexto .= " ".$veintis[$units];
		return $entexto;
	} elseif ($tens == 1) {
		if ($hundreds != 0) {
			$entexto .= " ";
		}
		$entexto .= $diecis[$units];
		return $entexto;
	}
	if ($units != 0) {
		if ($hundreds != 0 || $tens != 0) {
			$entexto .= " ";
		}
		$entexto .= $unidades[$units - 1];
	}
	return $entexto;
}
