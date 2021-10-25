<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-16 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos\PrintBuffers;

use LogicException;
use Mike42\Escpos\Printer;

/**
 * This class manages newlines and character encoding for the target printer, and
 * can be interchanged for an image-bassed buffer (ImagePrintBuffer) if you can't
 * get it operating properly on your machine.
 */
class EscposPrintBuffer implements PrintBuffer
{
    /**
     *  True to cache output as .z, false to leave un-compressed (useful for debugging)
     */
    const COMPRESS_CACHE = true;

    /**
     * The input encoding of the buffer.
     */
    const INPUT_ENCODING = "UTF-8";

    /**
     * Un-recognised characters will be replaced with this.
     */
    const REPLACEMENT_CHAR = "?";

    /**
     * @var array $available
     *  This array Maps ESC/POS character tables to names iconv encodings
     */
    private $available = null;

    /**
     * @var array $encode
     *  Maps of UTF-8 to code-pages
     */
    private $encode = null;

    /**
     * @var Printer|null $printer
     *  Printer for output
     */
    private $printer;

    /**
     * Empty print buffer.
     */
    public function __construct()
    {
        $this -> printer = null;
    }

    public function flush()
    {
        if ($this -> printer == null) {
            throw new LogicException("Not attached to a printer.");
        }
        // TODO Not yet implemented for this buffer: This indicates that the printer needs the current line to be ended.
    }

    public function getPrinter()
    {
        return $this -> printer;
    }

    public function setPrinter(Printer $printer = null)
    {
        $this -> printer = $printer;
        if ($printer != null) {
            $this -> loadAvailableCharacters();
        }
    }

    public function writeText($text)
    {
        if ($this -> printer == null) {
            throw new LogicException("Not attached to a printer.");
        }
        if ($text == null) {
            return;
        }
        if (!mb_detect_encoding($text, self::INPUT_ENCODING, true)) {
            // Assume that the user has already put non-UTF8 into the target encoding.
            return $this -> writeTextRaw($text);
        }
        $i = 0;
        $j = 0;
        $len = mb_strlen($text, self::INPUT_ENCODING);
        while ($i < $len) {
            $matching = true;
            if (($encoding = $this -> identifyText(mb_substr($text, $i, 1, self::INPUT_ENCODING))) === false) {
                // Un-encodeable text
                $encoding = $this -> getPrinter() -> getCharacterTable();
            }
            $i++;
            $j = 1;
            do {
                $char = mb_substr($text, $i, 1, self::INPUT_ENCODING);
                $matching = !isset($this -> available[$char]) || isset($this -> available[$char][$encoding]);
                if ($matching) {
                    $i++;
                    $j++;
                }
            } while ($matching && $i < $len);
            $this -> writeTextUsingEncoding(mb_substr($text, $i - $j, $j, self::INPUT_ENCODING), $encoding);
        }
    }

    public function writeTextRaw($text)
    {
        if ($this -> printer == null) {
            throw new LogicException("Not attached to a printer.");
        }
        if (strlen($text) == 0) {
            return;
        }
        // Pass only printable characters
        $j = 0;
        $l = strlen($text);
        $outp = str_repeat(self::REPLACEMENT_CHAR, $l);
        for ($i = 0; $i < $l; $i++) {
            $c = substr($text, $i, 1);
            if ($c == "\r") {
                /* Skip past Windows line endings (raw usage). */
                continue;
            } else if (self::asciiCheck($c, true)) {
                $outp[$j] = $c;
            }
            $j++;
        }
        $this -> write(substr($outp, 0, $j));
    }

    /**
     * Return an encoding which we can start to use for outputting this text.
     * Later parts of the text need not be included in the returned code page.
     *
     * @param string $text Input text to check.
     * @return boolean|integer Code page number, or FALSE if the text is not
     *  printable on any supported encoding.
     */
    private function identifyText($text)
    {
        // TODO Replace this with an algorithm to choose the encoding which will
        //      encode the farthest into the string, to minimise code page changes.
        $char = mb_substr($text, 0, 1, self::INPUT_ENCODING);
        if (!isset($this -> available[$char])) {
            /* Character not available anywhere */
            return false;
        }
        foreach ($this -> available[$char] as $encodingNo => $true) {
            /* Return first code-page where it is available */
            return $encodingNo;
        }
        return false;
    }
    
    /**
     * Based on the printer's connector, compute (or load a cached copy of) maps
     * of UTF character to unicode characters for later use.
     */
    private function loadAvailableCharacters()
    {
        $profile = $this -> printer -> getPrinterCapabilityProfile();
        $supportedCodePages = $profile -> getCodePages();
        $profileName = $profile -> getId();
        $cacheFile = dirname(__FILE__) . "/cache/Characters-" . $profileName . ".ser" .
            (self::COMPRESS_CACHE ? ".z" : "");
        $cacheKey = $profile -> getCodePageCacheKey();
        /* Check for pre-generated file */
        if (file_exists($cacheFile)) {
            $cacheData = file_get_contents($cacheFile);
            if (self::COMPRESS_CACHE) {
                $cacheData = gzuncompress($cacheData);
            }
            if ($cacheData) {
                $dataArray = unserialize($cacheData);
                if (isset($dataArray["key"]) && isset($dataArray["available"]) &&
                        isset($dataArray["encode"]) && $dataArray["key"] == $cacheKey) {
                    $this -> available = $dataArray["available"];
                    $this -> encode = $dataArray["encode"];
                    return;
                }
            }
        }

        /* Generate conversion tables */
        $encode = [];
        $available = [];

        foreach ($supportedCodePages as $num => $codePage) {
            $encode[$num] = [];
            if (!$codePage -> isEncodable()) {
                continue;
            }
            $map = $codePage -> getData();
            for ($char = 128; $char <= 255; $char++) {
                $utf8 = mb_substr($map, $char - 128, 1, self::INPUT_ENCODING);
                if ($utf8 == " ") { // Skip placeholders
                    continue;
                }
                if (!isset($available[$utf8])) {
                    $available[$utf8] = [];
                }
                $available[$utf8][$num] = true;
                $encode[$num][$utf8] = chr($char);
            }
        }
        
        /* Use generated data */
        $dataArray = ["available" => $available, "encode" => $encode, "key" => $cacheKey];
        $this -> available = $dataArray["available"];
        $this -> encode = $dataArray["encode"];
        $cacheData = serialize($dataArray);
        if (self::COMPRESS_CACHE) {
            $cacheData = gzcompress($cacheData);
        }
        /* Attempt to cache, but don't worry if we can't */
        @file_put_contents($cacheFile, $cacheData);
    }

    /**
     * Encode a block of text using the specified map, and write it to the printer.
     *
     * @param string $text Text to print, UTF-8 format.
     * @param integer $encodingNo Encoding number to use- assumed to exist.
     */
    private function writeTextUsingEncoding($text, $encodingNo)
    {
        $encodeMap = $this -> encode[$encodingNo];
        $len = mb_strlen($text, self::INPUT_ENCODING);
        $rawText = str_repeat(self::REPLACEMENT_CHAR, $len);
        $j = 0;
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1, self::INPUT_ENCODING);
            if (isset($encodeMap[$char])) {
                $rawText[$j] = $encodeMap[$char];
            } elseif (self::asciiCheck($char)) {
                $rawText[$j] = $char;
            } elseif ($char === "\r") {
                /* Skip past Windows line endings (UTF-8 usage) */
                continue;
            }
            $j++;
        }
        if ($this -> printer -> getCharacterTable() != $encodingNo) {
            $this -> printer -> selectCharacterTable($encodingNo);
        }
        $this -> writeTextRaw(substr($rawText, 0, $j));
    }

    /**
     * Write data to the underlying printer.
     *
     * @param string $data
     */
    private function write($data)
    {
        $this -> printer -> getPrintConnector() -> write($data);
    }

    /**
     * Return true if a character is an ASCII printable character.
     *
     * @param string $char Character to check
     * @param boolean $extended True to allow 128-256 values also (excluded by default)
     * @return boolean True if the character is printable, false if it is not.
     */
    private static function asciiCheck($char, $extended = false)
    {
        if (strlen($char) != 1) {
            // Multi-byte string
            return false;
        }
        $num = ord($char);
        if ($num > 31 && $num < 127) { // Printable
            return true;
        }
        if ($num == 10) { // New-line (printer will take these)
            return true;
        }
        if ($extended && $num > 127) {
            return true;
        }
        return false;
    }
}
