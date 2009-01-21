<?php
//
//  FPDI - Version 1.2.1
//
//    Copyright 2004-2008 Setasign - Jan Slabon
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
 * This class is used as a bridge between TCPDF and FPDI
 * and will create the possibility to use both FPDF and TCPDF
 * via one FPDI version.
 * 
 * We'll simply remap TCPDF to FPDF again.
 * 
 * It'll be loaded and extended by FPDF_TPL.
 */
class FPDF extends TCPDF {
    
    /**
     * Missing in TCPDF
     *
     * @var string
     */
    var $padding;
    
    function __get($name) {
        switch ($name) {
            case 'PDFVersion':
                return $this->PDFVersion;
            case 'k':
                return $this->k;
            case 'lastUsedPageBox':
                return $this->lastUsedPageBox;
            default:
                // Error handling
                $this->Error('Cannot access protected property '.get_class($this).':$'.$name.' / Undefined property: '.get_class($this).'::$'.$name);
        }
    }

    function __set($name, $value) {
        switch ($name) {
            case 'PDFVersion':
                $this->PDFVersion = $value;
                break;
            default:
                // Error handling
                $this->Error('Cannot access protected property '.get_class($this).':$'.$name.' / Undefined property: '.get_class($this).'::$'.$name);
        }
    }

    /**
     * Encryption of imported data by FPDI
     *
     * @param array $value
     */
    function pdf_write_value(&$value) {
        switch ($value[0]) {
    		case PDF_TYPE_STRING :
				if ($this->encrypted) {
				    $value[1] = $this->_unescape($value[1]);
                    $value[1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[1]);
                 	$value[1] = $this->_escape($value[1]);
                } 
    			break;
    			
			case PDF_TYPE_STREAM :
			    if ($this->encrypted) {
			        $value[2][1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[2][1]);
                }
                break;
                
            case PDF_TYPE_HEX :
            	if ($this->encrypted) {
                	$value[1] = $this->hex2str($value[1]);
                	$value[1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[1]);
                    
                	// remake hexstring of encrypted string
    				$value[1] = $this->str2hex($value[1]);
                }
                break;
    	}
    }
    
    /**
     * Unescapes a PDF string
     *
     * @param string $s
     * @return string
     */
    function _unescape($s) {
        return strtr($s, array(
            '\\\\' => "\\",
            '\)' => ')',
            '\(' => '(',
            '\\f' => chr(0x0C),
            '\\b' => chr(0x08),
            '\\t' => chr(0x09),
            '\\r' => chr(0x0D),
            '\\n' => chr(0x0A),
        ));
    }
    
    /**
     * Hexadecimal to string
     *
     * @param string $hex
     * @return string
     */
    function hex2str($hex) {
    	return pack("H*", str_replace(array("\r", "\n", " "), "", $hex));
    }
    
    /**
     * String to hexadecimal
     *
     * @param string $str
     * @return string
     */
    function str2hex($str) {
        return current(unpack("H*", $str));
    }
}