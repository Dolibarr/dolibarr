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
 * This file is used as a bridge between TCPDF or FPDF
 * It will dynamically create the class extending the available
 * class FPDF or TCPDF.
 *
 * This way it is possible to use FPDI for both FPDF and TCPDF with one FPDI version.
 */

if (!class_exists('TCPDF', false)) {
    /**
     * Class fpdi_bridge
     */
    class fpdi_bridge extends FPDF
    {
        // empty body
    }

} else {

    /**
     * Class fpdi_bridge
     */
    class fpdi_bridge extends TCPDF
    {
        /**
         * Array of Tpl-Data
         *
         * @var array
         */
        protected $_tpls = array();

        /**
         * Name-prefix of Templates used in Resources-Dictionary
         *
         * @var string A String defining the Prefix used as Template-Object-Names. Have to begin with an /
         */
        public $tplPrefix = "/TPL";

        /**
         * Current Object Id.
         *
         * @var integer
         */
        protected $_currentObjId;

        /**
         * Return XObjects Dictionary.
         *
         * Overwritten to add additional XObjects to the resources dictionary of TCPDF
         *
         * @return string
         */
        protected function _getxobjectdict()
        {
            $out = parent::_getxobjectdict();
            foreach ($this->_tpls as $tplIdx => $tpl) {
                $out .= sprintf('%s%d %d 0 R', $this->tplPrefix, $tplIdx, $tpl['n']);
            }

            return $out;
        }

        /**
         * Writes a PDF value to the resulting document.
         *
         * Prepares the value for encryption of imported data by FPDI
         *
         * @param array $value
         */
        protected function _prepareValue(&$value)
        {
            switch ($value[0]) {
                case pdf_parser::TYPE_STRING:
                    if ($this->encrypted) {
                        $value[1] = $this->_unescape($value[1]);
                        $value[1] = $this->_encrypt_data($this->_currentObjId, $value[1]);
                        $value[1] = TCPDF_STATIC::_escape($value[1]);
                    }
                    break;

                case pdf_parser::TYPE_STREAM:
                    if ($this->encrypted) {
                        $value[2][1] = $this->_encrypt_data($this->_currentObjId, $value[2][1]);
                        $value[1][1]['/Length'] = array(
                            pdf_parser::TYPE_NUMERIC,
                            strlen($value[2][1])
                        );
                    }
                    break;

                case pdf_parser::TYPE_HEX:
                    if ($this->encrypted) {
                        $value[1] = $this->hex2str($value[1]);
                        $value[1] = $this->_encrypt_data($this->_currentObjId, $value[1]);

                        // remake hexstring of encrypted string
                        $value[1] = $this->str2hex($value[1]);
                    }
                    break;
            }
        }

        /**
         * Un-escapes a PDF string
         *
         * @param string $s
         * @return string
         */
        protected function _unescape($s)
        {
            $out = '';
            for ($count = 0, $n = strlen($s); $count < $n; $count++) {
                if ($s[$count] != '\\' || $count == $n-1) {
                    $out .= $s[$count];
                } else {
                    switch ($s[++$count]) {
                        case ')':
                        case '(':
                        case '\\':
                            $out .= $s[$count];
                            break;
                        case 'f':
                            $out .= chr(0x0C);
                            break;
                        case 'b':
                            $out .= chr(0x08);
                            break;
                        case 't':
                            $out .= chr(0x09);
                            break;
                        case 'r':
                            $out .= chr(0x0D);
                            break;
                        case 'n':
                            $out .= chr(0x0A);
                            break;
                        case "\r":
                            if ($count != $n-1 && $s[$count+1] == "\n")
                                $count++;
                            break;
                        case "\n":
                            break;
                        default:
                            // Octal-Values
                            if (ord($s[$count]) >= ord('0') &&
                                ord($s[$count]) <= ord('9')) {
                                $oct = ''. $s[$count];

                                if (ord($s[$count+1]) >= ord('0') &&
                                    ord($s[$count+1]) <= ord('9')) {
                                    $oct .= $s[++$count];

                                    if (ord($s[$count+1]) >= ord('0') &&
                                        ord($s[$count+1]) <= ord('9')) {
                                        $oct .= $s[++$count];
                                    }
                                }

                                $out .= chr(octdec($oct));
                            } else {
                                $out .= $s[$count];
                            }
                    }
                }
            }
            return $out;
        }

        /**
         * Hexadecimal to string
         *
         * @param string $data
         * @return string
         */
        public function hex2str($data)
        {
            $data = preg_replace('/[^0-9A-Fa-f]/', '', rtrim($data, '>'));
            if ((strlen($data) % 2) == 1) {
                $data .= '0';
            }

            return pack('H*', $data);
        }

        /**
         * String to hexadecimal
         *
         * @param string $str
         * @return string
         */
        public function str2hex($str)
        {
            return current(unpack('H*', $str));
        }
    }
}