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
 * Class FilterLZW
 */
class FilterLZW
{
    protected $_sTable = array();
    protected $_data = null;
    protected $_dataLength = 0;
    protected $_tIdx;
    protected $_bitsToGet = 9;
    protected $_bytePointer;
    protected $_bitPointer;
    protected $_nextData = 0;
    protected $_nextBits = 0;
    protected $_andTable = array(511, 1023, 2047, 4095);

    /**
     * Decodes LZW compressed data.
     *
     * @param string $data The compressed data.
     * @throws Exception
     * @return string
     */
    public function decode($data)
    {
        if ($data[0] == 0x00 && $data[1] == 0x01) {
            throw new Exception('LZW flavour not supported.');
        }

        $this->_initsTable();

        $this->_data = $data;
        $this->_dataLength = strlen($data);

        // Initialize pointers
        $this->_bytePointer = 0;
        $this->_bitPointer = 0;

        $this->_nextData = 0;
        $this->_nextBits = 0;

        $oldCode = 0;

        $unCompData = '';

        while (($code = $this->_getNextCode()) != 257) {
            if ($code == 256) {
                $this->_initsTable();
                $code = $this->_getNextCode();

                if ($code == 257) {
                    break;
                }

                if (!isset($this->_sTable[$code])) {
                    throw new Exception('Error while decompression LZW compressed data.');
                }

                $unCompData .= $this->_sTable[$code];
                $oldCode = $code;

            } else {

                if ($code < $this->_tIdx) {
                    $string = $this->_sTable[$code];
                    $unCompData .= $string;

                    $this->_addStringToTable($this->_sTable[$oldCode], $string[0]);
                    $oldCode = $code;
                } else {
                    $string = $this->_sTable[$oldCode];
                    $string = $string . $string[0];
                    $unCompData .= $string;

                    $this->_addStringToTable($string);
                    $oldCode = $code;
                }
            }
        }

        return $unCompData;
    }


    /**
     * Initialize the string table.
     */
    protected function _initsTable()
    {
        $this->_sTable = array();

        for ($i = 0; $i < 256; $i++)
            $this->_sTable[$i] = chr($i);

        $this->_tIdx = 258;
        $this->_bitsToGet = 9;
    }

    /**
     * Add a new string to the string table.
     */
    protected function _addStringToTable($oldString, $newString = '')
    {
        $string = $oldString . $newString;

        // Add this new String to the table
        $this->_sTable[$this->_tIdx++] = $string;

        if ($this->_tIdx == 511) {
            $this->_bitsToGet = 10;
        } else if ($this->_tIdx == 1023) {
            $this->_bitsToGet = 11;
        } else if ($this->_tIdx == 2047) {
            $this->_bitsToGet = 12;
        }
    }

    /**
     * Returns the next 9, 10, 11 or 12 bits
     *
     * @return int
     */
    protected function _getNextCode()
    {
        if ($this->_bytePointer == $this->_dataLength) {
            return 257;
        }

        $this->_nextData = ($this->_nextData << 8) | (ord($this->_data[$this->_bytePointer++]) & 0xff);
        $this->_nextBits += 8;

        if ($this->_nextBits < $this->_bitsToGet) {
            $this->_nextData = ($this->_nextData << 8) | (ord($this->_data[$this->_bytePointer++]) & 0xff);
            $this->_nextBits += 8;
        }

        $code = ($this->_nextData >> ($this->_nextBits - $this->_bitsToGet)) & $this->_andTable[$this->_bitsToGet-9];
        $this->_nextBits -= $this->_bitsToGet;

        return $code;
    }

    /**
     * NOT IMPLEMENTED
     *
     * @param string $in
     * @return string
     * @throws LogicException
     */
    public function encode($in)
    {
        throw new LogicException("LZW encoding not implemented.");
    }
}