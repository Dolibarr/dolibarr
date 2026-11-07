<?php
/* Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/PdfFiltersTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test for the stream filters of TCPDF
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/includes/tecnickcom/tcpdf/include/tcpdf_filters.php';

/**
 * Class for the PHPUnit tests of the stream filters of TCPDF
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @RunTestsInSeparateProcesses
 */
class PdfFiltersTest extends CommonClassTest
{
	/**
	 * Decoding of the LZW example of the PDF specification (ISO 32000-1, 7.4.4.2).
	 *
	 * The encoded stream holds the codes 256, 45, 258, 258, 65, 259, 66, 257: the code 258 is used
	 * once before it is added to the dictionary, which is the case the decoder has to handle by
	 * repeating the first character of the previous entry.
	 *
	 * @return	void
	 */
	public function testDecodeFilterLZWDecodeOfTheSpecificationExample()
	{
		$encoded = "\x80\x0b\x60\x50\x22\x0c\x0c\x85\x01";
		$expected = "\x2d\x2d\x2d\x2d\x2d\x41\x2d\x2d\x2d\x42";

		$this->assertSame($expected, TCPDF_FILTERS::decodeFilterLZWDecode($encoded));
	}

	/**
	 * A code that is not yet in the dictionary must become the previous code for the next one,
	 * otherwise every entry added after it is wrong and the end of the stream is decoded wrong.
	 *
	 * @return	void
	 */
	public function testDecodeFilterLZWDecodeAfterACodeNotYetInTheDictionary()
	{
		// 'ABABABABABABC' repeated: the encoder emits codes that are not yet in the dictionary
		$plain = str_repeat('AB', 24).'C'.str_repeat('AB', 24).'C';

		$this->assertSame($plain, TCPDF_FILTERS::decodeFilterLZWDecode($this->lzwEncode($plain)));
	}

	/**
	 * A stream large enough to show the cost of the decoding: the time must stay reasonable.
	 *
	 * @return	void
	 */
	public function testDecodeFilterLZWDecodeOfALargeStream()
	{
		$plain = str_repeat('The quick brown fox jumps over the lazy dog. ', 2000);	// about 88 KB

		$start = microtime(true);
		$decoded = TCPDF_FILTERS::decodeFilterLZWDecode($this->lzwEncode($plain));
		$duration = microtime(true) - $start;

		// The hash and the length are compared, not the strings: a failure here would print 88 KB otherwise
		$this->assertSame(strlen($plain), strlen($decoded));
		$this->assertSame(md5($plain), md5($decoded));
		$this->assertLessThan(20, $duration, 'Decoding 88 KB of LZW took '.round($duration, 1).'s');
	}

	/**
	 * Decoding of the ASCII85 example of the PDF specification, with a group of 5 characters whose
	 * value uses the whole 32 bits.
	 *
	 * @return	void
	 */
	public function testDecodeFilterASCII85Decode()
	{
		$this->assertSame('Hello World!', TCPDF_FILTERS::decodeFilterASCII85Decode('<~87cURD]i,"Ebo80~>'));
		// s8W-! is 0xFFFFFFFF: the four bytes of the tuple are over 127, which is the case chr() is given
		$this->assertSame("\xff\xff\xff\xff", TCPDF_FILTERS::decodeFilterASCII85Decode('<~s8W-!~>'));
	}

	/**
	 * Encode a string the way a PDF producer does, to decode it back in the tests above.
	 *
	 * @param	string	$plain	String to encode
	 * @return	string			LZW encoded string, with the clear-table and the end-of-data markers
	 */
	private function lzwEncode($plain)
	{
		$dictionary = array();
		for ($i = 0; $i < 256; $i++) {
			$dictionary[chr($i)] = $i;
		}
		$next = 258;
		$bitlen = 9;
		$bits = '';
		$emit = function ($code) use (&$bits, &$bitlen) {
			$bits .= str_pad(decbin($code), $bitlen, '0', STR_PAD_LEFT);
		};

		$emit(256);
		$word = '';
		$len = strlen($plain);
		for ($i = 0; $i < $len; $i++) {
			$char = $plain[$i];
			if (isset($dictionary[$word.$char])) {
				$word .= $char;
				continue;
			}
			$emit($dictionary[$word]);
			$dictionary[$word.$char] = $next++;
			// The decoder adds its entry one code later, so the length grows one code earlier here
			if ($next + 1 > (1 << $bitlen) && $bitlen < 12) {
				$bitlen++;
			}
			if ($next >= 4095) {
				$emit(256);
				$dictionary = array();
				for ($j = 0; $j < 256; $j++) {
					$dictionary[chr($j)] = $j;
				}
				$next = 258;
				$bitlen = 9;
			}
			$word = $char;
		}
		if ($word !== '') {
			$emit($dictionary[$word]);
		}
		$emit(257);

		$bits = str_pad($bits, (int) (ceil(strlen($bits) / 8) * 8), '0', STR_PAD_RIGHT);
		$encoded = '';
		foreach (str_split($bits, 8) as $byte) {
			$encoded .= chr((int) bindec($byte));
		}

		return $encoded;
	}
}
