<?php
//
//  FPDI - Version 1.4.2
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

if (!defined ('PDF_TYPE_NULL'))
    define ('PDF_TYPE_NULL', 0);
if (!defined ('PDF_TYPE_NUMERIC'))
    define ('PDF_TYPE_NUMERIC', 1);
if (!defined ('PDF_TYPE_TOKEN'))
    define ('PDF_TYPE_TOKEN', 2);
if (!defined ('PDF_TYPE_HEX'))
    define ('PDF_TYPE_HEX', 3);
if (!defined ('PDF_TYPE_STRING'))
    define ('PDF_TYPE_STRING', 4);
if (!defined ('PDF_TYPE_DICTIONARY'))
    define ('PDF_TYPE_DICTIONARY', 5);
if (!defined ('PDF_TYPE_ARRAY'))
    define ('PDF_TYPE_ARRAY', 6);
if (!defined ('PDF_TYPE_OBJDEC'))
    define ('PDF_TYPE_OBJDEC', 7);
if (!defined ('PDF_TYPE_OBJREF'))
    define ('PDF_TYPE_OBJREF', 8);
if (!defined ('PDF_TYPE_OBJECT'))
    define ('PDF_TYPE_OBJECT', 9);
if (!defined ('PDF_TYPE_STREAM'))
    define ('PDF_TYPE_STREAM', 10);
if (!defined ('PDF_TYPE_BOOLEAN'))
    define ('PDF_TYPE_BOOLEAN', 11);
if (!defined ('PDF_TYPE_REAL'))
    define ('PDF_TYPE_REAL', 12);
    
require_once('pdf_context.php');

if (!class_exists('pdf_parser', false)) {
    
    class pdf_parser {
    	
    	/**
         * Filename
         * @var string
         */
        var $filename;
        
        /**
         * File resource
         * @var resource
         */
        var $f;
        
        /**
         * PDF Context
         * @var object pdf_context-Instance
         */
        var $c;
        
        /**
         * xref-Data
         * @var array
         */
        var $xref;
    
        /**
         * root-Object
         * @var array
         */
        var $root;
    	
        /**
         * PDF version of the loaded document
         * @var string
         */
        var $pdfVersion;
        
        /**
	     * For reading encrypted documents and xref/objectstreams are in use
	     *
	     * @var boolean
	     */
	    var $readPlain = true;
	    
        /**
         * Constructor
         *
         * @param string $filename  Source-Filename
         */
    	function pdf_parser($filename) {
            $this->filename = $filename;
            
            $this->f = @fopen($this->filename, 'rb');
    
            if (!$this->f)
                $this->error(sprintf('Cannot open %s !', $filename));
    
            $this->getPDFVersion();
    
            $this->c = new pdf_context($this->f);
            
            // Read xref-Data
            $this->xref = array();
            $this->pdf_read_xref($this->xref, $this->pdf_find_xref());
            
            // Check for Encryption
            $this->getEncryption();
    
            // Read root
            $this->pdf_read_root();
        }
        
        /**
         * Close the opened file
         */
        function closeFile() {
        	if (isset($this->f) && is_resource($this->f)) {
        	    fclose($this->f);	
        		unset($this->f);
        	}	
        }
        
        /**
         * Print Error and die
         *
         * @param string $msg  Error-Message
         */
        function error($msg) {
        	die('<b>PDF-Parser Error:</b> ' . $msg);	
        }
        
        /**
         * Check Trailer for Encryption
         */
        function getEncryption() {
            if (isset($this->xref['trailer'][1]['/Encrypt'])) {
            	$this->error('File is encrypted!');
            }
        }
        
    	/**
         * Find/Return /Root
         *
         * @return array
         */
        function pdf_find_root() {
            if ($this->xref['trailer'][1]['/Root'][0] != PDF_TYPE_OBJREF) {
                $this->error('Wrong Type of Root-Element! Must be an indirect reference');
            }
            
            return $this->xref['trailer'][1]['/Root'];
        }
    
        /**
         * Read the /Root
         */
        function pdf_read_root() {
            // read root
            $this->root = $this->pdf_resolve_object($this->c, $this->pdf_find_root());
        }
        
        /**
         * Get PDF-Version
         *
         * And reset the PDF Version used in FPDI if needed
         */
        function getPDFVersion() {
            fseek($this->f, 0);
            preg_match('/\d\.\d/',fread($this->f, 16), $m);
            if (isset($m[0]))
                $this->pdfVersion = $m[0];
            return $this->pdfVersion;
        }
        
        /**
         * Find the xref-Table
         */
        function pdf_find_xref() {
           	$toRead = 1500;
                    
            $stat = fseek ($this->f, -$toRead, SEEK_END);
            if ($stat === -1) {
                fseek ($this->f, 0);
            }
           	$data = fread($this->f, $toRead);
            
            $pos = strlen($data) - strpos(strrev($data), strrev('startxref')); 
            $data = substr($data, $pos);
            
            if (!preg_match('/\s*(\d+).*$/s', $data, $matches)) {
                $this->error('Unable to find pointer to xref table');
        	}
    
        	return (int) $matches[1];
        }
    
        /**
         * Read xref-table
         *
         * @param array $result Array of xref-table
         * @param integer $offset of xref-table
         */
        function pdf_read_xref(&$result, $offset) {
            $o_pos = $offset-min(20, $offset);
        	fseek($this->f, $o_pos); // set some bytes backwards to fetch errorious docs
                
            $data = fread($this->f, 100);
            
            $xrefPos = strrpos($data, 'xref');
    
            if ($xrefPos === false) {
                fseek($this->f, $offset);
                $c = new pdf_context($this->f);
                $xrefStreamObjDec = $this->pdf_read_value($c);
                
                if (is_array($xrefStreamObjDec) && isset($xrefStreamObjDec[0]) && $xrefStreamObjDec[0] == PDF_TYPE_OBJDEC) {
                    $this->error(sprintf('This document (%s) probably uses a compression technique which is not supported by the free parser shipped with FPDI.', $this->filename));
                } else {            
                    $this->error('Unable to find xref table.');
                }
            }
            
            if (!isset($result['xref_location'])) {
                $result['xref_location'] = $o_pos + $xrefPos;
                $result['max_object'] = 0;
        	}
    
        	$cylces = -1;
            $bytesPerCycle = 100;
            
        	fseek($this->f, $o_pos = $o_pos + $xrefPos + 4); // set the handle directly after the "xref"-keyword
            $data = fread($this->f, $bytesPerCycle);
            
            while (($trailerPos = strpos($data, 'trailer', max($bytesPerCycle * $cylces++, 0))) === false && !feof($this->f)) {
                $data .= fread($this->f, $bytesPerCycle);
            }
            
            if ($trailerPos === false) {
                $this->error('Trailer keyword not found after xref table');
            }
            
            $data = substr($data, 0, $trailerPos);
            
            // get Line-Ending
            preg_match_all("/(\r\n|\n|\r)/", substr($data, 0, 100), $m); // check the first 100 bytes for linebreaks
    
            $differentLineEndings = count(array_unique($m[0]));
            if ($differentLineEndings > 1) {
                $lines = preg_split("/(\r\n|\n|\r)/", $data, -1, PREG_SPLIT_NO_EMPTY);
            } else {
                $lines = explode($m[0][1], $data);
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
                            if ($end > $result['max_object'])
                                $result['max_object'] = $end;
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
                            $this->error('Unexpected data in xref table');
                    }
                }
            }
            
            $lines = $pieces = $line = $start = $end = $gen = null;
            unset($lines, $pieces, $line, $start, $end, $gen);
            
            fseek($this->f, $o_pos + $trailerPos + 7);
            
            $c = new pdf_context($this->f);
    	    $trailer = $this->pdf_read_value($c);
    	    
    	    $c = null;
    	    unset($c);
    	    
    	    if (!isset($result['trailer'])) {
                $result['trailer'] = $trailer;          
    	    }
    	    
    	    if (isset($trailer[1]['/Prev'])) {
    	        $this->pdf_read_xref($result, $trailer[1]['/Prev'][1]);
    	    } 
    	    
    	    $trailer = null;
    	    unset($trailer);
            
            return true;
        }
        
        /**
         * Reads an Value
         *
         * @param object $c pdf_context
         * @param string $token a Token
         * @return mixed
         */
        function pdf_read_value(&$c, $token = null) {
        	if (is_null($token)) {
        	    $token = $this->pdf_read_token($c);
        	}
        	
            if ($token === false) {
        	    return false;
        	}
    
        	switch ($token) {
                case	'<':
        			// This is a hex string.
        			// Read the value, then the terminator
    
                    $pos = $c->offset;
    
        			while(1) {
    
                        $match = strpos ($c->buffer, '>', $pos);
    				
        				// If you can't find it, try
        				// reading more data from the stream
    
        				if ($match === false) {
        					if (!$c->increase_length()) {
        						return false;
        					} else {
                            	continue;
                        	}
        				}
    
        				$result = substr ($c->buffer, $c->offset, $match - $c->offset);
        				$c->offset = $match + 1;
        				
        				return array (PDF_TYPE_HEX, $result);
                    }
                    
                    break;
        		case	'<<':
        			// This is a dictionary.
    
        			$result = array();
    
        			// Recurse into this function until we reach
        			// the end of the dictionary.
        			while (($key = $this->pdf_read_token($c)) !== '>>') {
        				if ($key === false) {
        					return false;
        				}
        				
        				if (($value =   $this->pdf_read_value($c)) === false) {
        					return false;
        				}
        				
        				// Catch missing value
        				if ($value[0] == PDF_TYPE_TOKEN && $value[1] == '>>') {
        				    $result[$key] = array(PDF_TYPE_NULL);
        				    break;
        				}
        				
        				$result[$key] = $value;
        			}
    				
        			return array (PDF_TYPE_DICTIONARY, $result);
    
        		case	'[':
        			// This is an array.
    
        			$result = array();
    
        			// Recurse into this function until we reach
        			// the end of the array.
        			while (($token = $this->pdf_read_token($c)) !== ']') {
                        if ($token === false) {
        					return false;
        				}
    					
        				if (($value = $this->pdf_read_value($c, $token)) === false) {
                            return false;
        				}
    					
        				$result[] = $value;
        			}
        			
                    return array (PDF_TYPE_ARRAY, $result);
    
        		case	'('		:
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
        			} while($openBrackets != 0 && $c->increase_length());
        			
        			$result = substr($c->buffer, $c->offset, $pos - $c->offset - 1);
        			$c->offset = $pos;
        			
        			return array (PDF_TYPE_STRING, $result);
    
                case 'stream':
                	$o_pos = ftell($c->file)-strlen($c->buffer);
    		        $o_offset = $c->offset;
    		        
    		        $c->reset($startpos = $o_pos + $o_offset);
    		        
    		        $e = 0; // ensure line breaks in front of the stream
    		        if ($c->buffer[0] == chr(10) || $c->buffer[0] == chr(13))
    		        	$e++;
    		        if ($c->buffer[1] == chr(10) && $c->buffer[0] != chr(10))
    		        	$e++;
    		        
    		        if ($this->actual_obj[1][1]['/Length'][0] == PDF_TYPE_OBJREF) {
    		        	$tmp_c = new pdf_context($this->f);
    		        	$tmp_length = $this->pdf_resolve_object($tmp_c, $this->actual_obj[1][1]['/Length']);
    		        	$length = $tmp_length[1][1];
    		        } else {
    		        	$length = $this->actual_obj[1][1]['/Length'][1];	
    		        }
    		        	
    		        if ($length > 0) {
        		        $c->reset($startpos + $e,$length);
        		        $v = $c->buffer;
    		        } else {
    		            $v = '';   
    		        }
    		        $c->reset($startpos + $e + $length + 9); // 9 = strlen("endstream")
    		        
    		        return array(PDF_TYPE_STREAM, $v);
    		        
    	        default	:
                	if (is_numeric ($token)) {
                        // A numeric token. Make sure that
        				// it is not part of something else.
        				if (($tok2 = $this->pdf_read_token ($c)) !== false) {
                            if (is_numeric ($tok2)) {
    
        						// Two numeric tokens in a row.
        						// In this case, we're probably in
        						// front of either an object reference
        						// or an object specification.
        						// Determine the case and return the data
        						if (($tok3 = $this->pdf_read_token ($c)) !== false) {
                                    switch ($tok3) {
        								case 'obj':
                                            return array (PDF_TYPE_OBJDEC, (int) $token, (int) $tok2);
        								case 'R':
        									return array (PDF_TYPE_OBJREF, (int) $token, (int) $tok2);
        							}
        							// If we get to this point, that numeric value up
        							// there was just a numeric value. Push the extra
        							// tokens back into the stack and return the value.
        							array_push ($c->stack, $tok3);
        						}
        					}
    
        					array_push ($c->stack, $tok2);
        				}
    
        				if ($token === (string)((int)$token))
            				return array (PDF_TYPE_NUMERIC, (int)$token);
        				else 
        					return array (PDF_TYPE_REAL, (float)$token);
        			} else if ($token == 'true' || $token == 'false') {
                        return array (PDF_TYPE_BOOLEAN, $token == 'true');
        			} else if ($token == 'null') {
        			   return array (PDF_TYPE_NULL);
        			} else {
                        // Just a token. Return it.
        				return array (PDF_TYPE_TOKEN, $token);
        			}
             }
        }
        
        /**
         * Resolve an object
         *
         * @param object $c pdf_context
         * @param array $obj_spec The object-data
         * @param boolean $encapsulate Must set to true, cause the parsing and fpdi use this method only without this para
         */
        function pdf_resolve_object(&$c, $obj_spec, $encapsulate = true) {
            // Exit if we get invalid data
        	if (!is_array($obj_spec)) {
                $ret = false;
        	    return $ret;
        	}
    
        	if ($obj_spec[0] == PDF_TYPE_OBJREF) {
    
        		// This is a reference, resolve it
        		if (isset($this->xref['xref'][$obj_spec[1]][$obj_spec[2]])) {
    
        			// Save current file position
        			// This is needed if you want to resolve
        			// references while you're reading another object
        			// (e.g.: if you need to determine the length
        			// of a stream)
    
        			$old_pos = ftell($c->file);
    
        			// Reposition the file pointer and
        			// load the object header.
    				
        			$c->reset($this->xref['xref'][$obj_spec[1]][$obj_spec[2]]);
    
        			$header = $this->pdf_read_value($c);
    
        			if ($header[0] != PDF_TYPE_OBJDEC || $header[1] != $obj_spec[1] || $header[2] != $obj_spec[2]) {
        				$toSearchFor = $obj_spec[1] . ' ' . $obj_spec[2] . ' obj';
        				if (preg_match('/' . $toSearchFor . '/', $c->buffer)) {
        					$c->offset = strpos($c->buffer, $toSearchFor) + strlen($toSearchFor);
        					// reset stack
        					$c->stack = array();
        				} else {
	        				$this->error("Unable to find object ({$obj_spec[1]}, {$obj_spec[2]}) at expected location");
        				}
        			}
    
        			// If we're being asked to store all the information
        			// about the object, we add the object ID and generation
        			// number for later use
    				$result = array();
    				$this->actual_obj =& $result;
        			if ($encapsulate) {
        				$result = array (
        					PDF_TYPE_OBJECT,
        					'obj' => $obj_spec[1],
        					'gen' => $obj_spec[2]
        				);
        			} 
    
        			// Now simply read the object data until
        			// we encounter an end-of-object marker
        			while(1) {
                        $value = $this->pdf_read_value($c);
    					if ($value === false || count($result) > 4) {
    						// in this case the parser coudn't find an endobj so we break here
    						break;
        				}
    
        				if ($value[0] == PDF_TYPE_TOKEN && $value[1] === 'endobj') {
        					break;
        				}
    
                        $result[] = $value;
        			}
    
        			$c->reset($old_pos);
    
                    if (isset($result[2][0]) && $result[2][0] == PDF_TYPE_STREAM) {
                        $result[0] = PDF_TYPE_STREAM;
                    }
    
        			return $result;
        		}
        	} else {
        		return $obj_spec;
        	}
        }
    
        
        
        /**
         * Reads a token from the file
         *
         * @param object $c pdf_context
         * @return mixed
         */
        function pdf_read_token(&$c)
        {
        	// If there is a token available
        	// on the stack, pop it out and
        	// return it.
    
        	if (count($c->stack)) {
        		return array_pop($c->stack);
        	}
    
        	// Strip away any whitespace
    
        	do {
        		if (!$c->ensure_content()) {
        			return false;
        		}
        		$c->offset += strspn($c->buffer, " \n\r\t", $c->offset);
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
        				if (!$c->ensure_content()) {
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
        					if (!$c->increase_length()) {
        						return false;
        					} else {
                            	continue;
                        	}
        				}
    
        				$c->offset = $m[0][1]+strlen($m[0][0]);
        				
        				return $this->pdf_read_token($c);
                    }
                    
    			default:
    
        			// This is "another" type of token (probably
        			// a dictionary entry or a numeric value)
        			// Find the end and return it.
    
        			if (!$c->ensure_content()) {
        				return false;
        			}
    
        			while(1) {
    
        				// Determine the length of the token
    
        				$pos = strcspn($c->buffer, " %[]<>()\r\n\t/", $c->offset);
        				
        				if ($c->offset + $pos <= $c->length - 1) {
        					break;
        				} else {
        					// If the script reaches this point,
        					// the token may span beyond the end
        					// of the current buffer. Therefore,
        					// we increase the size of the buffer
        					// and try again--just to be safe.
    
        					$c->increase_length();
        				}
        			}
    
        			$result = substr($c->buffer, $c->offset - 1, $pos + 1);
    
        			$c->offset += $pos;
        			return $result;
        	}
        }
    }
}
