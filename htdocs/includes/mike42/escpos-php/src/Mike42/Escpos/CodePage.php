<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-18 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos;

use \InvalidArgumentException;

/**
 * Class to handle data about a particular CodePage, as loaded from the receipt print
 * database.
 *
 * Also computes map between UTF-8 and this encoding if necessary, using the intl library.
 */
class CodePage
{
    /**
     * Value to use when no character is set. This is a space in ASCII.
     */
    const MISSING_CHAR_CODE = 0x20;

    /**
     * @var array|null $data
     *  Data string, null if not known (can be computed with iconv)
     */
    protected $data;

    /**
     * @var string|null $iconv
     *  Iconv encoding name, null if not known
     */
    protected $iconv;

    /**
     * @var string $id
     *  Internal ID of the CodePage
     */
    protected $id;

    /**
     * @var string $name
     *  Name of the code page. Substituted with the ID if not set.
     */
    protected $name;

    /**
     * @var string|null $notes
     *  Notes on this code page, or null if not set.
     */
    protected $notes;

    /**
     *
     * @param string $id
     *            Unique internal identifier for the CodePage.
     * @param array $codePageData
     *            Associative array of CodePage data, as
     *            specified by the upstream receipt-print-hq/escpos-printer-db database.
     *            May contain 'name', 'data', 'iconv', and 'notes' fields.
     */
    public function __construct($id, array $codePageData)
    {
        $this->id = $id;
        $this->name = isset($codePageData['name']) ? $codePageData['name'] : $id;
        $this->data = isset($codePageData['data']) ? self::encodingArrayFromData($codePageData['data']) : null;
        $this->iconv = isset($codePageData['iconv']) ? $codePageData['iconv'] : null;
        $this->notes = isset($codePageData['notes']) ? $codePageData['notes'] : null;
    }

    /**
     * Get a 128-entry array of unicode code-points from this code page.
     *
     * @throws InvalidArgumentException Where the data is now known or computable.
     * @return array Data for this encoding.
     */
    public function getDataArray() : array
    {
        // Make string
        if ($this->data !== null) {
            // Return data if known
            return $this->data;
        }
        if ($this->iconv !== null) {
            // Calculate with iconv if we know the encoding name
            $this->data = self::generateEncodingArray($this->iconv);
            return $this->data;
        }
        // Can't encode..
        throw new InvalidArgumentException("Cannot encode this code page");
    }

    /**
     *
     * @return string|null Iconv encoding name, or null if not set.
     */
    public function getIconv()
    {
        return $this->iconv;
    }

    /**
     *
     * @return string Unique identifier of the code page.
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * @return string Name of the code page.
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * The notes may explain quirks about a code-page, such as a source if it's non-standard or un-encodeable.
     *
     * @return string|null Notes on the code page, or null if not set.
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     *
     * @return boolean True if we can encode with this code page (ie, we know what data it holds).
     *
     * Many printers contain vendor-specific code pages, which are named but have not been identified or
     * typed out. For our purposes, this is an "un-encodeable" code page.
     */
    public function isEncodable()
    {
        return $this->iconv !== null || $this->data !== null;
    }

    /**
     * Given an ICU encoding name, generate a 128-entry array, with the unicode code points
     * for the character at positions 128-255 in this code page.
     *
     * @param string $encodingName Name of the encoding
     * @return array 128-entry array of code points
     */
    protected static function generateEncodingArray(string $encodingName) : array
    {
        // Set up converter for encoding
        $missingChar = chr(self::MISSING_CHAR_CODE);
        // Throws a lot of warnings for ambiguous code pages, but fallbacks seem fine.
        $converter = @new \UConverter("UTF-8", $encodingName);
        $converter -> setSubstChars($missingChar);
        // Loop through 128 code points
        $intArray = array_fill(0, 128, self::MISSING_CHAR_CODE);
        for ($char = 128; $char <= 255; $char++) {
            // Try to identify the UTF-8 character at this position in the code page
            $encodingChar = chr($char);
            $utf8 = $converter ->convert($encodingChar, false);
            if ($utf8 === $missingChar) {
                // Cannot be mapped to unicode
                continue;
            }
            $reverse = $converter ->convert($utf8, true);
            if ($reverse !== $encodingChar) {
                // Avoid conversions which don't reverse well (eg. multi-byte code pages)
                continue;
            }
            // Replace space with the correct character if we found it
            $intArray[$char - 128] = \IntlChar::ord($utf8);
        }
        assert(count($intArray) == 128);
        return $intArray;
    }


    private static function encodingArrayFromData(array $data) : array
    {
        $text = implode("", $data); // Join lines
        $codePointIterator = \IntlBreakIterator::createCodePointInstance();
        $codePointIterator -> setText($text);
        $ret = array_fill(0, 128, self::MISSING_CHAR_CODE);
        for ($i = 0; ($codePointIterator -> next() > 0) && ($i < 128); $i++) {
            $codePoint = $codePointIterator -> getLastCodePoint();
            $ret[$i] = $codePoint;
        }
        assert(count($ret) == 128);
        return $ret;
    }
}
