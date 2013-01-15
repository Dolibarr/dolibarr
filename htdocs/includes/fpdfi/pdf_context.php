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

if (!class_exists('pdf_context', false)) {
    
    class pdf_context {
    
        /**
         * Modi
         *
         * @var integer 0 = file | 1 = string
         */
        var $_mode = 0;
        
    	var $file;
    	var $buffer;
    	var $offset;
    	var $length;
    
    	var $stack;
    
    	// Constructor
    
    	function pdf_context(&$f) {
    		$this->file =& $f;
    		if (is_string($this->file))
    		    $this->_mode = 1;
    		$this->reset();
    	}
    
    	// Optionally move the file
    	// pointer to a new location
    	// and reset the buffered data
    
    	function reset($pos = null, $l = 100) {
    	    if ($this->_mode == 0) {
            	if (!is_null ($pos)) {
        			fseek ($this->file, $pos);
        		}
        
        		$this->buffer = $l > 0 ? fread($this->file, $l) : '';
        		$this->length = strlen($this->buffer);
        		if ($this->length < $l)
                    $this->increase_length($l - $this->length);
    	    } else {
    	        $this->buffer = $this->file;
    	        $this->length = strlen($this->buffer);
    	    }
    		$this->offset = 0;
    		$this->stack = array();
    	}
    
    	// Make sure that there is at least one
    	// character beyond the current offset in
    	// the buffer to prevent the tokenizer
    	// from attempting to access data that does
    	// not exist
    
    	function ensure_content() {
    		if ($this->offset >= $this->length - 1) {
    			return $this->increase_length();
    		} else {
    			return true;
    		}
    	}
    
    	// Forcefully read more data into the buffer
    
    	function increase_length($l = 100) {
			if ($this->_mode == 0 && feof($this->file)) {
				return false;
			} else if ($this->_mode == 0) {
			    $totalLength = $this->length + $l;
			    do {
			    	$toRead = $totalLength - $this->length;
			    	if ($toRead < 1)
			    		break;
			    
			    	$this->buffer .= fread($this->file, $toRead);
	            } while ((($this->length = strlen($this->buffer)) != $totalLength) && !feof($this->file));
				
				return true;
			} else {
		        return false;
			}
		}
    }
}