<?php
//
//  FPDI - Version 1.5.2
//
//    Copyright 2004-2014 Setasign - Jan Slabon
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//

/**
 * Class pdf_parser
 */
class pdf_parser
{
    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_NULL = 0;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_NUMERIC = 1;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_TOKEN = 2;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_HEX = 3;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_STRING = 4;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_DICTIONARY = 5;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_ARRAY = 6;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_OBJDEC = 7;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_OBJREF = 8;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_OBJECT = 9;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_STREAM = 10;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_BOOLEAN = 11;

    /**
     * Type constant
     *
     * @var integer
     */
    const TYPE_REAL = 12;

    /**
     * Define the amount of byte in which the initial keyword of a PDF document should be searched.
     *
     * @var int
     */
    static public $searchForStartxrefLength = 5500;

    /**
     * Filename
     *
     * @var string
     */
    public $filename;

    /**
     * File resource
     *
     * @var resource
     */
    protected $_f;

    /**
     * PDF Context
     *
     * @var pdf_context
     */
    protected $_c;

    /**
     * xref-Data
     *
     * @var array
     */
    protected $_xref;

    /**
     * Data of the Root object
     *
     * @var array
     */
    protected $_root;

    /**
     * PDF version of the loaded document
     *
     * @var string
     */
    protected $_pdfVersion;

    /**
     * For reading encrypted documents and xref/object streams are in use
     *
     * @var boolean
     */
    protected $_readPlain = true;

    /**
     * The current read object
     *
     * @var array
     */
    protected $_currentObj;

    /**
     * Constructor
     *
     * @param string $filename Source filename
     * @throws InvalidArgumentException
     */
    public function __construct($filename)
    {
        $this->filename = $filename;

        $this->_f = @fopen($this->filename, 'rb');

        if (!$this->_f) {
            throw new InvalidArgumentException(sprintf('Cannot open %s !', $filename));
        }

        $this->getPdfVersion();

        require_once('pdf_context.php');
        $this->_c = new pdf_context($this->_f);

        // Read xref-Data
        $this->_xref = array();
        $this->_readXref($this->_xref, $this->_findXref());

        // Check for Encryption
        $this->getEncryption();

        // Read root
        $this->_readRoot();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->closeFile();
    }

    /**
     * Close the opened file
     */
    public function closeFile()
    {
        if (isset($this->_f) && is_resource($this->_f)) {
            fclose($this->_f);
            unset($this->_f);
        }
    }

    /**
     * Check Trailer for Encryption
     *
     * @throws Exception
     */
    public function getEncryption()
    {
        if (isset($this->_xref['trailer'][1]['/Encrypt'])) {
            throw new Exception('File is encrypted!');
        }
    }

    /**
     * Get PDF-Version
     *
     * @return string
     */
    public function getPdfVersion()
    {
        if ($this->_pdfVersion === null) {
            fseek($this->_f, 0);
            preg_match('/\d\.\d/', fread($this->_f, 16), $m);
            if (isset($m[0]))
                $this->_pdfVersion = $m[0];
        }

        return $this->_pdfVersion;
    }

    /**
     * Read the /Root dictionary
     */
    protected function _readRoot()
    {
        if ($this->_xref['trailer'][1]['/Root'][0] != self::TYPE_OBJREF) {
            throw new Exception('Wrong Type of Root-Element! Must be an indirect reference');
        }

        $this->_root = $this->resolveObject($this->_xref['trailer'][1]['/Root']);
    }

    /**
     * Find the xref table
     *
     * @return integer
     * @throws Exception
     */
    protected function _findXref()
    {
        $toRead = self::$searchForStartxrefLength;

        $stat = fseek($this->_f, -$toRead, SEEK_END);
        if ($stat === -1) {
            fseek($this->_f, 0);
        }

        $data = fread($this->_f, $toRead);

        $keywordPos = strpos(strrev($data), strrev('startxref'));
        if (false === $keywordPos) {
            $keywordPos = strpos(strrev($data), strrev('startref'));
        }

        if (false === $keywordPos) {
            throw new Exception('Unable to find "startxref" keyword.');
        }

        $pos = strlen($data) - $keywordPos;
        $data = substr($data, $pos);

        if (!preg_match('/\s*(\d+).*$/s', $data, $matches)) {
            throw new Exception('Unable to find pointer to xref table.');
        }

        return (int) $matches[1];
    }

    /**
     * Read the xref table
     *
     * @param array $result Array of xref table entries
     * @param integer $offset of xref table
     * @return boolean
     * @throws Exception
     */
    protected function _readXref(&$result, $offset)
    {
        $tempPos = $offset - min(20, $offset);
        fseek($this->_f, $tempPos); // set some bytes backwards to fetch corrupted docs

        $data = fread($this->_f, 100);

        $xrefPos = strrpos($data, 'xref');

        if ($xrefPos === false) {
            $this->_c->reset($offset);
            $xrefStreamObjDec = $this->_readValue($this->_c);

            if (is_array($xrefStreamObjDec) && isset($xrefStreamObjDec[0]) && $xrefStreamObjDec[0] == self::TYPE_OBJDEC) {
                throw new Exception(
                    sprintf(
                        'This document (%s) probably uses a compression technique which is not supported by the ' .
                        'free parser shipped with FPDI. (See https://www.setasign.com/fpdi-pdf-parser for more details)',
                        $this->filename
                    )
                );
            } else {
                throw new Exception('Unable to find xref table.');
            }
        }

        if (!isset($result['xrefLocation'])) {
            $result['xrefLocation'] = $tempPos + $xrefPos;
            $result['maxObject'] = 0;
        }

        $cycles = -1;
        $bytesPerCycle = 100;

        fseek($this->_f, $tempPos = $tempPos + $xrefPos + 4); // set the handle directly after the "xref"-keyword
        $data = fread($this->_f, $bytesPerCycle);

        while (($trailerPos = strpos($data, 'trailer', max($bytesPerCycle * $cycles++, 0))) === false && !feof($this->_f)) {
            $data .= fread($this->_f, $bytesPerCycle);
        }

        if ($trailerPos === false) {
            throw new Exception('Trailer keyword not found after xref table');
        }

        $data = ltrim(substr($data, 0, $trailerPos));

        // get Line-Ending
        preg_match_all("/(\r\n|\n|\r)/", substr($data, 0, 100), $m); // check the first 100 bytes for line breaks

        $differentLineEndings = count(array_unique($m[0]));
        if ($differentLineEndings > 1) {
            $lines = preg_split("/(\r\n|\n|\r)/", $data, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $lines = explode($m[0][0], $data);
        }

        $data = $differentLineEndings = $m = null;
        unset($data, $differentLineEndings, $m);

        $linesCount = count($lines);

        $start = 1;

        for ($i = 0; $i < $linesCount; $i++) {
            $line = trim($lines[$i]);
            if ($line) {
                $pieces = explode(' ', $line);
                $c = count($pieces);
                switch($c) {
                    case 2:
                        $start = (int)$pieces[0];
                        $end   = $start + (int)$pieces[1];
                        if ($end > $result['maxObject'])
                            $result['maxObject'] = $end;
                        break;
                    case 3:
                        if (!isset($result['xref'][$start]))
                            $result['xref'][$start] = array();

                        if (!array_key_exists($gen = (int) $pieces[1], $result['xref'][$start])) {
                            $result['xref'][$start][$gen] = $pieces[2] == 'n' ? (int) $pieces[0] : null;
                        }
                        $start++;
                        break;
                    default:
                        throw new Exception('Unexpected data in xref table');
                }
            }
        }

        $lines = $pieces = $line = $start = $end = $gen = null;
        unset($lines, $pieces, $line, $start, $end, $gen);

        $this->_c->reset($tempPos + $trailerPos + 7);
        $trailer = $this->_readValue($this->_c);

        if (!isset($result['trailer'])) {
            $result['trailer'] = $trailer;
        }

        if (isset($trailer[1]['/Prev'])) {
            $this->_readXref($result, $trailer[1]['/Prev'][1]);
        }

        $trailer = null;
        unset($trailer);

        return true;
    }

    /**
     * Reads a PDF value
     *
     * @param pdf_context $c
     * @param string $token A token
     * @return mixed
     */
    protected function _readValue(&$c, $token = null)
    {
        if (is_null($token)) {
            $token = $this->_readToken($c);
        }

        if ($token === false) {
            return false;
        }

        switch ($token) {
            case '<':
                // This is a hex string.
                // Read the value, then the terminator

                $pos = $c->offset;

                while(1) {

                    $match = strpos ($c->buffer, '>', $pos);

                    // If you can't find it, try
                    // reading more data from the stream

                    if ($match === false) {
                        if (!$c->increaseLength()) {
                            return false;
                        } else {
                            continue;
                        }
                    }

                    $result = substr ($c->buffer, $c->offset, $match - $c->offset);
                    $c->offset = $match + 1;

                    return array (self::TYPE_HEX, $result);
                }
                break;

            case '<<':
                // This is a dictionary.

                $result = array();

                // Recurse into this function until we reach
                // the end of the dictionary.
                while (($key = $this->_readToken($c)) !== '>>') {
                    if ($key === false) {
                        return false;
                    }

                    if (($value =   $this->_readValue($c)) === false) {
                        return false;
                    }

                    // Catch missing value
                    if ($value[0] == self::TYPE_TOKEN && $value[1] == '>>') {
                        $result[$key] = array(self::TYPE_NULL);
                        break;
                    }

                    $result[$key] = $value;
                }

                return array (self::TYPE_DICTIONARY, $result);

            case '[':
                // This is an array.

                $result = array();

                // Recurse into this function until we reach
                // the end of the array.
                while (($token = $this->_readToken($c)) !== ']') {
                    if ($token === false) {
                        return false;
                    }

                    if (($value = $this->_readValue($c, $token)) === false) {
                        return false;
                    }

                    $result[] = $value;
                }

                return array (self::TYPE_ARRAY, $result);

            case '(':
                // This is a string
                $pos = $c->offset;

                $openBrackets = 1;
                do {
                    for (; $openBrackets != 0 && $pos < $c->length; $pos++) {
                        switch (ord($c->buffer[$pos])) {
                            case 0x28: // '('
                                $openBrackets++;
                                break;
                            case 0x29: // ')'
                                $openBrackets--;
                                break;
                            case 0x5C: // backslash
                                $pos++;
                        }
                    }
                } while($openBrackets != 0 && $c->increaseLength());

                $result = substr($c->buffer, $c->offset, $pos - $c->offset - 1);
                $c->offset = $pos;

                return array (self::TYPE_STRING, $result);

            case 'stream':
                $tempPos = $c->getPos() - strlen($c->buffer);
                $tempOffset = $c->offset;

                $c->reset($startPos = $tempPos + $tempOffset);

                $e = 0; // ensure line breaks in front of the stream
                if ($c->buffer[0] == chr(10) || $c->buffer[0] == chr(13))
                    $e++;
                if ($c->buffer[1] == chr(10) && $c->buffer[0] != chr(10))
                    $e++;

                if ($this->_currentObj[1][1]['/Length'][0] == self::TYPE_OBJREF) {
                    $tmpLength = $this->resolveObject($this->_currentObj[1][1]['/Length']);
                    $length = $tmpLength[1][1];
                } else {
                    $length = $this->_currentObj[1][1]['/Length'][1];
                }

                if ($length > 0) {
                    $c->reset($startPos + $e, $length);
                    $v = $c->buffer;
                } else {
                    $v = '';
                }

                $c->reset($startPos + $e + $length);
                $endstream = $this->_readToken($c);

                if ($endstream != 'endstream') {
                    $c->reset($startPos + $e + $length + 9); // 9 = strlen("endstream")
                    // We don't throw an error here because the next
                    // round trip will start at a new offset
                }

                return array(self::TYPE_STREAM, $v);

            default	:
                if (is_numeric($token)) {
                    // A numeric token. Make sure that
                    // it is not part of something else.
                    if (($tok2 = $this->_readToken($c)) !== false) {
                        if (is_numeric($tok2)) {

                            // Two numeric tokens in a row.
                            // In this case, we're probably in
                            // front of either an object reference
                            // or an object specification.
                            // Determine the case and return the data
                            if (($tok3 = $this->_readToken($c)) !== false) {
                                switch ($tok3) {
                                    case 'obj':
                                        return array(self::TYPE_OBJDEC, (int)$token, (int)$tok2);
                                    case 'R':
                                        return array(self::TYPE_OBJREF, (int)$token, (int)$tok2);
                                }
                                // If we get to this point, that numeric value up
                                // there was just a numeric value. Push the extra
                                // tokens back into the stack and return the value.
                                array_push($c->stack, $tok3);
                            }
                        }

                        array_push($c->stack, $tok2);
                    }

                    if ($token === (string)((int)$token))
                        return array(self::TYPE_NUMERIC, (int)$token);
                    else
                        return array(self::TYPE_REAL, (float)$token);
                } else if ($token == 'true' || $token == 'false') {
                    return array(self::TYPE_BOOLEAN, $token == 'true');
                } else if ($token == 'null') {
                   return array(self::TYPE_NULL);
                } else {
                    // Just a token. Return it.
                    return array(self::TYPE_TOKEN, $token);
                }
         }
    }

    /**
     * Resolve an object
     *
     * @param array $objSpec The object-data
     * @return array|boolean
     * @throws Exception
     */
    public function resolveObject($objSpec)
    {
        $c = $this->_c;

        // Exit if we get invalid data
        if (!is_array($objSpec)) {
            return false;
        }

        if ($objSpec[0] == self::TYPE_OBJREF) {

            // This is a reference, resolve it
            if (isset($this->_xref['xref'][$objSpec[1]][$objSpec[2]])) {

                // Save current file position
                // This is needed if you want to resolve
                // references while you're reading another object
                // (e.g.: if you need to determine the length
                // of a stream)

                $oldPos = $c->getPos();

                // Reposition the file pointer and
                // load the object header.

                $c->reset($this->_xref['xref'][$objSpec[1]][$objSpec[2]]);

                $header = $this->_readValue($c);

                if ($header[0] != self::TYPE_OBJDEC || $header[1] != $objSpec[1] || $header[2] != $objSpec[2]) {
                    $toSearchFor = $objSpec[1] . ' ' . $objSpec[2] . ' obj';
                    if (preg_match('/' . $toSearchFor . '/', $c->buffer)) {
                        $c->offset = strpos($c->buffer, $toSearchFor) + strlen($toSearchFor);
                        // reset stack
                        $c->stack = array();
                    } else {
                        throw new Exception(
                            sprintf("Unable to find object (%s, %s) at expected location.", $objSpec[1], $objSpec[2])
                        );
                    }
                }

                // If we're being asked to store all the information
                // about the object, we add the object ID and generation
                // number for later use
                $result = array (
                    self::TYPE_OBJECT,
                    'obj' => $objSpec[1],
                    'gen' => $objSpec[2]
                );

                $this->_currentObj =& $result;

                // Now simply read the object data until
                // we encounter an end-of-object marker
                while (true) {
                    $value = $this->_readValue($c);
                    if ($value === false || count($result) > 4) {
                        // in this case the parser couldn't find an "endobj" so we break here
                        break;
                    }

                    if ($value[0] == self::TYPE_TOKEN && $value[1] === 'endobj') {
                        break;
                    }

                    $result[] = $value;
                }

                $c->reset($oldPos);

                if (isset($result[2][0]) && $result[2][0] == self::TYPE_STREAM) {
                    $result[0] = self::TYPE_STREAM;
                }

            } else {
                throw new Exception(
                    sprintf("Unable to find object (%s, %s) at expected location.", $objSpec[1], $objSpec[2])
                );
            }

            return $result;
        } else {
            return $objSpec;
        }
    }

    /**
     * Reads a token from the context
     *
     * @param pdf_context $c
     * @return mixed
     */
    protected function _readToken($c)
    {
        // If there is a token available
        // on the stack, pop it out and
        // return it.

        if (count($c->stack)) {
            return array_pop($c->stack);
        }

        // Strip away any whitespace

        do {
            if (!$c->ensureContent()) {
                return false;
            }
            $c->offset += strspn($c->buffer, "\x20\x0A\x0C\x0D\x09\x00", $c->offset);
        } while ($c->offset >= $c->length - 1);

        // Get the first character in the stream

        $char = $c->buffer[$c->offset++];

        switch ($char) {

            case '[':
            case ']':
            case '(':
            case ')':

                // This is either an array or literal string
                // delimiter, Return it

                return $char;

            case '<':
            case '>':

                // This could either be a hex string or
                // dictionary delimiter. Determine the
                // appropriate case and return the token

                if ($c->buffer[$c->offset] == $char) {
                    if (!$c->ensureContent()) {
                        return false;
                    }
                    $c->offset++;
                    return $char . $char;
                } else {
                    return $char;
                }

            case '%':

                // This is a comment - jump over it!

                $pos = $c->offset;
                while(1) {
                    $match = preg_match("/(\r\n|\r|\n)/", $c->buffer, $m, PREG_OFFSET_CAPTURE, $pos);
                    if ($match === 0) {
                        if (!$c->increaseLength()) {
                            return false;
                        } else {
                            continue;
                        }
                    }

                    $c->offset = $m[0][1] + strlen($m[0][0]);

                    return $this->_readToken($c);
                }

            default:

                // This is "another" type of token (probably
                // a dictionary entry or a numeric value)
                // Find the end and return it.

                if (!$c->ensureContent()) {
                    return false;
                }

                while(1) {

                    // Determine the length of the token

                    $pos = strcspn($c->buffer, "\x20%[]<>()/\x0A\x0C\x0D\x09\x00", $c->offset);

                    if ($c->offset + $pos <= $c->length - 1) {
                        break;
                    } else {
                        // If the script reaches this point,
                        // the token may span beyond the end
                        // of the current buffer. Therefore,
                        // we increase the size of the buffer
                        // and try again--just to be safe.

                        $c->increaseLength();
                    }
                }

                $result = substr($c->buffer, $c->offset - 1, $pos + 1);

                $c->offset += $pos;

                return $result;
        }
    }

    /**
     * Un-filter a stream object
     *
     * @param array $obj
     * @return string
     * @throws Exception
     */
    protected function _unFilterStream($obj)
    {
        $filters = array();

        if (isset($obj[1][1]['/Filter'])) {
            $filter = $obj[1][1]['/Filter'];

            if ($filter[0] == pdf_parser::TYPE_OBJREF) {
                $tmpFilter = $this->resolveObject($filter);
                $filter = $tmpFilter[1];
            }

            if ($filter[0] == pdf_parser::TYPE_TOKEN) {
                $filters[] = $filter;
            } else if ($filter[0] == pdf_parser::TYPE_ARRAY) {
                $filters = $filter[1];
            }
        }

        $stream = $obj[2][1];

        foreach ($filters AS $filter) {
            switch ($filter[1]) {
                case '/FlateDecode':
                case '/Fl':
                    if (function_exists('gzuncompress')) {
                        $oStream = $stream;
                        $stream = (strlen($stream) > 0) ? @gzuncompress($stream) : '';
                    } else {
                        throw new Exception(
                            sprintf('To handle %s filter, please compile php with zlib support.', $filter[1])
                        );
                    }

                    if ($stream === false) {
                        $tries = 0;
                        while ($tries < 8 && ($stream === false || strlen($stream) < strlen($oStream))) {
                            $oStream = substr($oStream, 1);
                            $stream = @gzinflate($oStream);
                            $tries++;
                        }

                        if ($stream === false) {
                            throw new Exception('Error while decompressing stream.');
                        }
                    }
                    break;
                case '/LZWDecode':
                    require_once('filters/FilterLZW.php');
                    $decoder = new FilterLZW();
                    $stream = $decoder->decode($stream);
                    break;
                case '/ASCII85Decode':
                    require_once('filters/FilterASCII85.php');
                    $decoder = new FilterASCII85();
                    $stream = $decoder->decode($stream);
                    break;
                case '/ASCIIHexDecode':
                    require_once('filters/FilterASCIIHexDecode.php');
                    $decoder = new FilterASCIIHexDecode();
                    $stream = $decoder->decode($stream);
                    break;
                case null:
                    break;
                default:
                    throw new Exception(sprintf('Unsupported Filter: %s', $filter[1]));
            }
        }

        return $stream;
    }
}