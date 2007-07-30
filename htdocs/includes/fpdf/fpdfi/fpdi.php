<?php
//
//  FPDI - Version 1.2
//
//    Copyright 2004-2007 Setasign - Jan Slabon
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

define('FPDI_VERSION','1.2');

ini_set('auto_detect_line_endings',1); // Strongly required!

require_once("fpdf_tpl.php");
require_once("fpdi_pdf_parser.php");


class FPDI extends FPDF_TPL {
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
     * Highest version of imported PDF
     * @var double
     */
    var $importVersion = 1.3;

    /**
     * object stack
     * @var array
     */
    var $_obj_stack;
    
    /**
     * done object stack
     * @var array
     */
    var $_don_obj_stack;

    /**
     * Current Object Id.
     * @var integer
     */
    var $_current_obj_id;
    
    /**
     * The name of the last imported page box
     * @var string
     */
    var $lastUsedPageBox;
    
    /**
     * Constructor
     * See FPDF-Manual
     */
    function FPDI($orientation='P',$unit='mm',$format='A4') {
        parent::FPDF_TPL($orientation,$unit,$format);
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

        if (!isset($this->parsers[$fn]))
            $this->parsers[$fn] =& new fpdi_pdf_parser($fn,$this);
        $this->current_parser =& $this->parsers[$fn];
        
        return $this->parsers[$fn]->getPageCount();
    }
    
    /**
     * Import a page
     *
     * @param int $pageno pagenumber
     * @return int Index of imported page - to use with fpdf_tpl::useTemplate()
     */
    function importPage($pageno, $boxName='/CropBox') {
        if ($this->_intpl) {
            return $this->error("Please import the desired pages before creating a new template.");
        }
        
        $fn =& $this->current_filename;
        
        $parser =& $this->parsers[$fn];
        $parser->setPageno($pageno);

        $this->tpl++;
        $this->tpls[$this->tpl] = array();
        $tpl =& $this->tpls[$this->tpl];
        $tpl['parser'] =& $parser;
        $tpl['resources'] = $parser->getPageResources();
        $tpl['buffer'] = $parser->getContent();
        
        if (!in_array($boxName, $parser->availableBoxes))
            return $this->Error(sprintf("Unknown box: %s", $boxName));
        $pageboxes = $parser->getPageBoxes($pageno);
        
        /**
         * MediaBox
         * CropBox: Default -> MediaBox
         * BleedBox: Default -> CropBox
         * TrimBox: Default -> CropBox
         * ArtBox: Default -> CropBox
         */
        if (!isset($pageboxes[$boxName]) && ($boxName == "/BleedBox" || $boxName == "/TrimBox" || $boxName == "/ArtBox"))
            $boxName = "/CropBox";
        if (!isset($pageboxes[$boxName]) && $boxName == "/CropBox")
            $boxName = "/MediaBox";
        
        if (!isset($pageboxes[$boxName]))
            return false;
        $this->lastUsedPageBox = $boxName;
        
        $box = $pageboxes[$boxName];
        $tpl['box'] = $box;
        
        // To build an array that can be used by PDF_TPL::useTemplate()
        $this->tpls[$this->tpl] = array_merge($this->tpls[$this->tpl],$box);
        // An imported page will start at 0,0 everytime. Translation will be set in _putformxobjects()
        $tpl['x'] = 0;
        $tpl['y'] = 0;
        
        $page =& $parser->pages[$parser->pageno];
        
        // fix for rotated pages
        $rotation = $parser->getPageRotation($pageno);
        if (isset($rotation[1]) && ($angle = $rotation[1] % 360) != 0) {
            $steps = $angle / 90;
                
            $_w = $tpl['w'];
            $_h = $tpl['h'];
            $tpl['w'] = $steps % 2 == 0 ? $_w : $_h;
            $tpl['h'] = $steps % 2 == 0 ? $_h : $_w;
            
            if ($steps % 2 != 0) {
                $x = $y = ($steps == 1 || $steps == -3) ? $tpl['h'] : $tpl['w'];
            } else {
                $x = $tpl['w'];
                $y = $tpl['h'];
            }
            
            $cx=($x/2+$tpl['box']['x'])*$this->k;
            $cy=($y/2+$tpl['box']['y'])*$this->k;
            
            $angle*=-1; 
            
            $angle*=M_PI/180;
            $c=cos($angle);
            $s=sin($angle);
            
            $tpl['buffer'] = sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm %s Q',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy, $tpl['buffer']);
        }
        
        return $this->tpl;
    }
    
    function getLastUsedPageBox() {
        return $this->lastUsedPageBox;
    }
    
    function useTemplate($tplidx, $_x=null, $_y=null, $_w=0, $_h=0) {
        $this->_out('q 0 J 1 w 0 j 0 G'); // reset standard values
        $s = parent::useTemplate($tplidx, $_x, $_y, $_w, $_h);
        $this->_out('Q');
        return $s;
    }
    
    /**
     * Private method, that rebuilds all needed objects of source files
     */
    function _putimportedobjects() {
        if (is_array($this->parsers) && count($this->parsers) > 0) {
            foreach($this->parsers AS $filename => $p) {
                $this->current_parser =& $this->parsers[$filename];
                if (is_array($this->_obj_stack[$filename])) {
                    while($n = key($this->_obj_stack[$filename])) {
                        $nObj = $this->current_parser->pdf_resolve_object($this->current_parser->c,$this->_obj_stack[$filename][$n][1]);
						
                        $this->_newobj($this->_obj_stack[$filename][$n][0]);
                        
                        if ($nObj[0] == PDF_TYPE_STREAM) {
							$this->pdf_write_value ($nObj);
                        } else {
                            $this->pdf_write_value ($nObj[1]);
                        }
                        
                        $this->_out('endobj');
                        $this->_obj_stack[$filename][$n] = null; // free memory
                        unset($this->_obj_stack[$filename][$n]);
                        reset($this->_obj_stack[$filename]);
                    }
                }
            }
        }
    }
    
    /**
     * Sets the PDF Version to the highest of imported documents
     */
    function setVersion() {
        $this->PDFVersion = max($this->importVersion, $this->PDFVersion);
    }
    
    /**
     * Put resources
     */
    function _putresources() {
        $this->_putfonts();
    	$this->_putimages();
    	$this->_putformxobjects();
        $this->_putimportedobjects();
        //Resource dictionary
    	$this->offsets[2]=strlen($this->buffer);
    	$this->_out('2 0 obj');
    	$this->_out('<<');
    	$this->_putresourcedict();
    	$this->_out('>>');
    	$this->_out('endobj');
    }
    
    /**
     * Private Method that writes the form xobjects
     */
    function _putformxobjects() {
        $filter=($this->compress) ? '/Filter /FlateDecode ' : '';
	    reset($this->tpls);
        foreach($this->tpls AS $tplidx => $tpl) {
            $p=($this->compress) ? gzcompress($tpl['buffer']) : $tpl['buffer'];
    		$this->_newobj();
    		$this->tpls[$tplidx]['n'] = $this->n;
    		$this->_out('<<'.$filter.'/Type /XObject');
            $this->_out('/Subtype /Form');
            $this->_out('/FormType 1');
            
            $this->_out(sprintf('/BBox [%.2f %.2f %.2f %.2f]',
                ($tpl['x'] + (isset($tpl['box']['x'])?$tpl['box']['x']:0))*$this->k,
                ($tpl['h'] + (isset($tpl['box']['y'])?$tpl['box']['y']:0) - $tpl['y'])*$this->k,
                ($tpl['w'] + (isset($tpl['box']['x'])?$tpl['box']['x']:0))*$this->k,
                ($tpl['h'] + (isset($tpl['box']['y'])?$tpl['box']['y']:0) - $tpl['y']-$tpl['h'])*$this->k)
            );
            
            if (isset($tpl['box']))
                $this->_out(sprintf('/Matrix [1 0 0 1 %.5f %.5f]',-$tpl['box']['x']*$this->k, -$tpl['box']['y']*$this->k));
            
            $this->_out('/Resources ');

            if (isset($tpl['resources'])) {
                $this->current_parser =& $tpl['parser'];
                $this->pdf_write_value($tpl['resources']);
            } else {
                $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
            	if (isset($this->_res['tpl'][$tplidx]['fonts']) && count($this->_res['tpl'][$tplidx]['fonts'])) {
                	$this->_out('/Font <<');
                    foreach($this->_res['tpl'][$tplidx]['fonts'] as $font)
                		$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
                	$this->_out('>>');
                }
            	if(isset($this->_res['tpl'][$tplidx]['images']) && count($this->_res['tpl'][$tplidx]['images']) || 
            	   isset($this->_res['tpl'][$tplidx]['tpls']) && count($this->_res['tpl'][$tplidx]['tpls']))
            	{
                    $this->_out('/XObject <<');
                    if (isset($this->_res['tpl'][$tplidx]['images']) && count($this->_res['tpl'][$tplidx]['images'])) {
                        foreach($this->_res['tpl'][$tplidx]['images'] as $image)
                  			$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
                    }
                    if (isset($this->_res['tpl'][$tplidx]['tpls']) && count($this->_res['tpl'][$tplidx]['tpls'])) {
                        foreach($this->_res['tpl'][$tplidx]['tpls'] as $i => $tpl)
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
            $this->offsets[$obj_id] = strlen($this->buffer);
            $this->_out($obj_id.' 0 obj');
            $this->_current_obj_id = $obj_id; // for later use with encryption
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
                $this->_out($value[1]." ", false);
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
    			$cpfn =& $this->current_parser->filename;
    			if (!isset($this->_don_obj_stack[$cpfn][$value[1]])) {
                    $this->_newobj(false,true);
                    $this->_obj_stack[$cpfn][$value[1]] = array($this->n, $value);
                    $this->_don_obj_stack[$cpfn][$value[1]] = array($this->n, $value);
                }
                $objid = $this->_don_obj_stack[$cpfn][$value[1]][0];

    			$this->_out("{$objid} 0 R"); //{$value[2]}
    			break;

    		case PDF_TYPE_STRING :

    			// A string.
                $this->_out('('.$value[1].')');

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
            
                $this->_out("<".$value[1].">");
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
           if (!$this->_intpl)
	           $this->pages[$this->page] .= $s.($ln == true ? "\n" : '');
           else
               $this->tpls[$this->tpl]['buffer'] .= $s.($ln == true ? "\n" : '');
       } else {
		   $this->buffer.=$s.($ln == true ? "\n" : '');
       }
    }

    /**
     * rewritten to close opened parsers
     *
     */
    function _enddoc() {
        parent::_enddoc();
        $this->_closeParsers();
    }
    
    /**
     * close all files opened by parsers
     */
    function _closeParsers() {
        if ($this->state > 2 && count($this->parsers) > 0) {
          	foreach ($this->parsers as $k => $_){
            	$this->parsers[$k]->closeFile();
            	$this->parsers[$k] = null;
            	unset($this->parsers[$k]);
            }
            return true;
        }
        return false;
    }

}

// for PHP5
if (!class_exists('fpdi')) {
    class fpdi extends FPDI {}
}
?>