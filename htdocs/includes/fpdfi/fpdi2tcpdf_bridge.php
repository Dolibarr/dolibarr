<?php
//
//  FPDI - Version 1.4.1
//
//    Copyright 2004-2011 Setasign - Jan Slabon
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
    
	function _putstream($s) {
		$this->_out($this->_getstream($s));
	}
	
	function _getxobjectdict() {
        $out = parent::_getxobjectdict();
        if (count($this->tpls)) {
            foreach($this->tpls as $tplidx => $tpl) {
                $out .= sprintf('%s%d %d 0 R', $this->tplprefix, $tplidx, $tpl['n']);
            }
        }
        
        return $out;
    }
	
    /**
     * Encryption of imported data by FPDI
     *
     * @param array $value
     */
    function pdf_write_value(&$value) {
        switch ($value[0]) {
    		case PDF_TYPE_STRING:
				if ($this->encrypted) {
				    $value[1] = $this->_unescape($value[1]);
                    $value[1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[1]);
                 	$value[1] = $this->_escape($value[1]);
                } 
    			break;
    			
			case PDF_TYPE_STREAM:
			    if ($this->encrypted) {
			        $value[2][1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[2][1]);
                }
                break;
                
            case PDF_TYPE_HEX:
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
     * @param string $hex
     * @return string
     */
    function hex2str($hex) {
    	return pack('H*', str_replace(array("\r", "\n", ' '), '', $hex));
    }
    
    /**
     * String to hexadecimal
     *
     * @param string $str
     * @return string
     */
    function str2hex($str) {
        return current(unpack('H*', $str));
    }
}