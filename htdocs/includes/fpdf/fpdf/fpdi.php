<?php
//
//  FPDI - Version 1.1
//
//    Copyright 2004,2005 Setasign - Jan Slabon
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

define ('PDF_TYPE_NULL', 0);
define ('PDF_TYPE_NUMERIC', 1);
define ('PDF_TYPE_TOKEN', 2);
define ('PDF_TYPE_HEX', 3);
define ('PDF_TYPE_STRING', 4);
define ('PDF_TYPE_DICTIONARY', 5);
define ('PDF_TYPE_ARRAY', 6);
define ('PDF_TYPE_OBJDEC', 7);
define ('PDF_TYPE_OBJREF', 8);
define ('PDF_TYPE_OBJECT', 9);
define ('PDF_TYPE_STREAM', 10);

ini_set('auto_detect_line_endings',1); // Strongly required!

require_once("fpdf_tpl.php");
require_once("fpdi_pdf_parser.php");


class fpdi extends fpdf_tpl {
    /**
     * Actual filename
     * @var string
     */
    var $current_filename;

    /**
     * Parser-Objects
     * @var array
     */
    var $parsers;
    
    /**
     * Current parser
     * @var object
     */
    var $current_parser;
    
    /**
     * FPDF/FPDI - PDF-Version
     * @var double
     */
    var $PDFVersion = 1.3;

    /**
     * Highest version of imported PDF
     * @var double
     */
    var $importVersion = 1.3;

    /**
     * object stack
     * @var array
     */
    var $obj_stack;
    
    /**
     * done object stack
     * @var array
     */
    var $don_obj_stack;

    /**
     * Current Object Id.
     * @var integer
     */
    var $current_obj_id;
    
    /**
     * Constructor
     * See FPDF-Manual
     */
    function fpdi($orientation='P',$unit='mm',$format='A4') {
        parent::fpdf_tpl($orientation,$unit,$format);
    }
    
    /**
     * Set a source-file
     *
     * @param string $filename a valid filename
     * @return int number of available pages
     */
    function setSourceFile($filename) {
        $this->current_filename = $filename;
        $fn =& $this->current_filename;

        $this->parsers[$fn] = new fpdi_pdf_parser($fn,$this);
        $this->current_parser =& $this->parsers[$fn];
        
        return $this->parsers[$fn]->getPageCount();
    }
    
    /**
     * Import a page
     *
     * @param int $pageno pagenumber
     * @return int Index of imported page - to use with fpdf_tpl::useTemplate()
     */
    function ImportPage($pageno) {
        $fn =& $this->current_filename;
        
        $this->parsers[$fn]->setPageno($pageno);

        $this->tpl++;
        $this->tpls[$this->tpl] = array();
        $this->tpls[$this->tpl]['parser'] =& $this->parsers[$fn];
        $this->tpls[$this->tpl]['resources'] = $this->parsers[$fn]->getPageResources();
        $this->tpls[$this->tpl]['buffer'] = $this->parsers[$fn]->getContent();
        // $mediabox holds the dimensions of the source page
        $mediabox = $this->parsers[$fn]->getPageMediaBox($pageno);
        
        // To build array that can used by pdf_tpl::useTemplate()
        $this->tpls[$this->tpl] = array_merge($this->tpls[$this->tpl],$mediabox);

        return $this->tpl;
    }
    
    /**
     * Private method, that rebuilds all needed objects of source files
     */
    function _putOobjects() {
        if (is_array($this->parsers) && count($this->parsers) > 0) {
            foreach($this->parsers AS $filename => $p) {
                $this->current_parser =& $this->parsers[$filename];
                if (is_array($this->obj_stack[$filename])) {
                    while($n = key($this->obj_stack[$filename])) {
                        $nObj = $this->current_parser->pdf_resolve_object($this->current_parser->c,$this->obj_stack[$filename][$n][1]);
						
                        $this->_newobj($this->obj_stack[$filename][$n][0]);
                        
                        if ($nObj[0] == PDF_TYPE_STREAM) {
							$this->pdf_write_value ($nObj);
                        } else {
                            $this->pdf_write_value ($nObj[1]);
                        }
                        
                        $this->_out('endobj');
                        $this->obj_stack[$filename][$n] = null; // free memory
                        unset($this->obj_stack[$filename][$n]);
                        reset($this->obj_stack[$filename]);
                    }
                }
            }
        }
    }
    
    /**
     * Rewritten for handling own defined PDF-Versions
     * only needed by FPDF 1.52
     */
    function _begindoc() {
    	//Start document
    	$this->state=1;
    }
    
    /**
     * Sets the PDF Version to the highest of imported documents
     */
    function setVersion() {
        if ($this->importVersion > $this->PDFVersion)
            $this->PDFVersion = $this->importVersion;

        if (!method_exists($this, '_putheader')) {
            $this->buffer = '%PDF-'.$this->PDFVersion."\n".$this->buffer;
		}
    }
    
    /**
     * rewritten for handling higher PDF Versions
     */
    function _enddoc() {
        $this->setVersion();
        parent::_enddoc();
    }

    
    /**
     * Put resources
     */
    function _putresources() {
        $this->_putfonts();
    	$this->_putimages();
        $this->_puttemplates();
        $this->_putOobjects();
        
        //Resource dictionary
    	$this->offsets[2]=strlen($this->buffer);
    	$this->_out('2 0 obj');
    	$this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
    	$this->_out('/Font <<');
        foreach($this->fonts as $font)
        	$this->_out($this->fontprefix.$font['i'].' '.$font['n'].' 0 R');
    	$this->_out('>>');
    	if(count($this->images) || count($this->tpls))
    	{
    		$this->_out('/XObject <<');
            if (count($this->images)) {
                foreach($this->images as $image)
        			$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
            }
            if (count($this->tpls)) {
                foreach($this->tpls as $tplidx => $tpl)
        			$this->_out($this->tplprefix.$tplidx.' '.$tpl['n'].' 0 R');
            }
            $this->_out('>>');
    	}
    	$this->_out('>>');
    	$this->_out('endobj');
    }

    /**
     * Private Method that writes /XObjects - "Templates"
     */
    function _puttemplates() {
        $filter=($this->compress) ? '/Filter /FlateDecode ' : '';
	    reset($this->tpls);
        foreach($this->tpls AS $tplidx => $tpl) {

            $p=($this->compress) ? gzcompress($tpl['buffer']) : $tpl['buffer'];
    		$this->_newobj();
    		$this->tpls[$tplidx]['n'] = $this->n;
    		$this->_out('<<'.$filter.'/Type /XObject');
            $this->_out('/Subtype /Form');
            $this->_out('/FormType 1');
            $this->_out(sprintf('/BBox [%.2f %.2f %.2f %.2f]',$tpl['x']*$this->k, ($tpl['h']-$tpl['y'])*$this->k, $tpl['w']*$this->k, ($tpl['h']-$tpl['y']-$tpl['h'])*$this->k));
            $this->_out('/Resources ');

            if ($tpl['resources']) {
                $this->current_parser =& $tpl['parser'];
                $this->pdf_write_value($tpl['resources']);
            } else {
                $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
            	if (count($this->res['tpl'][$tplidx]['fonts'])) {
                	$this->_out('/Font <<');
                    foreach($this->res['tpl'][$tplidx]['fonts'] as $font)
                		$this->_out($this->fontprefix.$font['i'].' '.$font['n'].' 0 R');
                	$this->_out('>>');
                }
            	if(count($this->res['tpl'][$tplidx]['images']) || count($this->res['tpl'][$tplidx]['tpls']))
            	{
                    $this->_out('/XObject <<');
                    if (count($this->res['tpl'][$tplidx]['images'])) {
                        foreach($this->res['tpl'][$tplidx]['images'] as $image)
                  			$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
                    }
                    if (count($this->res['tpl'][$tplidx]['tpls'])) {
                        foreach($this->res['tpl'][$tplidx]['tpls'] as $i => $tpl)
                            $this->_out($this->tplprefix.$i.' '.$tpl['n'].' 0 R');
                    }
                    $this->_out('>>');
            	}
            	$this->_out('>>');
            }

        	$this->_out('/Length '.strlen($p).' >>');
    		$this->_putstream($p);
    		$this->_out('endobj');
        }
    }

    /**
     * Rewritten to handle existing own defined objects
     */
    function _newobj($obj_id=false,$onlynewobj=false) {
        if (!$obj_id) {
            $obj_id = ++$this->n;
        }

    	//Begin a new object
        if (!$onlynewobj) {
            $this->offsets[$obj_id]=strlen($this->buffer);
            $this->_out($obj_id.' 0 obj');
            $this->current_obj_id = $obj_id; // for later use with encryption
        }
        
    }

    /**
     * Writes a value
     * Needed to rebuild the source document
     *
     * @param mixed $value A PDF-Value. Structure of values see cases in this method
     */
    function pdf_write_value(&$value)
    {

        switch ($value[0]) {

    		case PDF_TYPE_NUMERIC :
    		case PDF_TYPE_TOKEN :
                // A numeric value or a token.
    			// Simply output them
                $this->_out($value[1]." ");
    			break;

    		case PDF_TYPE_ARRAY :

    			// An array. Output the proper
    			// structure and move on.

    			$this->_out("[",false);
                for ($i = 0; $i < count($value[1]); $i++) {
    				$this->pdf_write_value($value[1][$i]);
    			}

    			$this->_out("]");
    			break;

    		case PDF_TYPE_DICTIONARY :

    			// A dictionary.
    			$this->_out("<<",false);

    			reset ($value[1]);

    			while (list($k, $v) = each($value[1])) {
    				$this->_out($k . " ",false);
    				$this->pdf_write_value($v);
    			}

    			$this->_out(">>");
    			break;

    		case PDF_TYPE_OBJREF :

    			// An indirect object reference
    			// Fill the object stack if needed
    			if (!isset($this->don_obj_stack[$this->current_parser->filename][$value[1]])) {
                    $this->_newobj(false,true);
                    $this->obj_stack[$this->current_parser->filename][$value[1]] = array($this->n,$value);
                    $this->don_obj_stack[$this->current_parser->filename][$value[1]] = array($this->n,$value);
                }
                $objid = $this->don_obj_stack[$this->current_parser->filename][$value[1]][0];

    			$this->_out("{$objid} 0 R"); //{$value[2]}
    			break;

    		case PDF_TYPE_STRING :

    			// A string.
                $this->_out('(' . $value[1] . ')');

    			break;

    		case PDF_TYPE_STREAM :

    			// A stream. First, output the
    			// stream dictionary, then the
    			// stream data itself.
                $this->pdf_write_value($value[1]);
    			$this->_out("stream");
    			$this->_out($value[2][1]);
    			$this->_out("endstream");
    			break;
            case PDF_TYPE_HEX :
            
                $this->_out("<" . $value[1] . ">");
                break;

    		case PDF_TYPE_NULL :
                // The null object.

    			$this->_out("null");
    			break;
    	}
    }
    
    
    /**
     * Private Method
     */
    function _out($s,$ln=true) {
	   //Add a line to the document
	   if ($this->state==2) {
           if (!$this->intpl)
	           $this->pages[$this->page].=$s.($ln == true ? "\n" : '');
           else
               $this->tpls[$this->tpl]['buffer'] .= $s.($ln == true ? "\n" : '');
       } else {
		   $this->buffer.=$s.($ln == true ? "\n" : '');
       }
    }

    /**
     * close all files opened by parsers
     */
    function closeParsers() {
      	foreach ($this->parsers as $parser){
        	$parser->closeFile();
        }
    }

}

?>