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

define('FPDI_VERSION', '1.4.2');

// Check for TCPDF and remap TCPDF to FPDF
if (class_exists('TCPDF', false)) {
    require_once('fpdi2tcpdf_bridge.php');
}

require_once('fpdf_tpl.php');
require_once('fpdi_pdf_parser.php');


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
     * Cache for imported pages/template ids
     * @var array
     */
    var $_importedPages = array();
    
    /**
     * Set a source-file
     *
     * @param string $filename a valid filename
     * @return int number of available pages
     */
    function setSourceFile($filename) {
        $this->current_filename = $filename;
        
        if (!isset($this->parsers[$filename]))
            $this->parsers[$filename] = $this->_getPdfParser($filename);
        $this->current_parser =& $this->parsers[$filename];
        
        return $this->parsers[$filename]->getPageCount();
    }
    
    /**
     * Returns a PDF parser object
     *
     * @param string $filename
     * @return fpdi_pdf_parser
     */
    function _getPdfParser($filename) {
    	return new fpdi_pdf_parser($filename, $this);
    }
    
    /**
     * Get the current PDF version
     *
     * @return string
     */
    function getPDFVersion() {
		return $this->PDFVersion;
	}
    
	/**
     * Set the PDF version
     *
     * @return string
     */
	function setPDFVersion($version = '1.3') {
		$this->PDFVersion = $version;
	}
	
    /**
     * Import a page
     *
     * @param int $pageno pagenumber
     * @return int Index of imported page - to use with fpdf_tpl::useTemplate()
     */
    function importPage($pageno, $boxName = '/CropBox') {
        if ($this->_intpl) {
            return $this->error('Please import the desired pages before creating a new template.');
        }
        
        $fn = $this->current_filename;
        
        // check if page already imported
        $pageKey = $fn . '-' . ((int)$pageno) . $boxName;
        if (isset($this->_importedPages[$pageKey]))
            return $this->_importedPages[$pageKey];
        
        $parser =& $this->parsers[$fn];
        $parser->setPageno($pageno);

        if (!in_array($boxName, $parser->availableBoxes))
            return $this->Error(sprintf('Unknown box: %s', $boxName));
            
        $pageboxes = $parser->getPageBoxes($pageno, $this->k);
        
        /**
         * MediaBox
         * CropBox: Default -> MediaBox
         * BleedBox: Default -> CropBox
         * TrimBox: Default -> CropBox
         * ArtBox: Default -> CropBox
         */
        if (!isset($pageboxes[$boxName]) && ($boxName == '/BleedBox' || $boxName == '/TrimBox' || $boxName == '/ArtBox'))
            $boxName = '/CropBox';
        if (!isset($pageboxes[$boxName]) && $boxName == '/CropBox')
            $boxName = '/MediaBox';
        
        if (!isset($pageboxes[$boxName]))
            return false;
            
        $this->lastUsedPageBox = $boxName;
        
        $box = $pageboxes[$boxName];
        
        $this->tpl++;
        $this->tpls[$this->tpl] = array();
        $tpl =& $this->tpls[$this->tpl];
        $tpl['parser'] =& $parser;
        $tpl['resources'] = $parser->getPageResources();
        $tpl['buffer'] = $parser->getContent();
        $tpl['box'] = $box;
        
        // To build an array that can be used by PDF_TPL::useTemplate()
        $this->tpls[$this->tpl] = array_merge($this->tpls[$this->tpl], $box);
        
        // An imported page will start at 0,0 everytime. Translation will be set in _putformxobjects()
        $tpl['x'] = 0;
        $tpl['y'] = 0;
        
        // handle rotated pages
        $rotation = $parser->getPageRotation($pageno);
        $tpl['_rotationAngle'] = 0;
        if (isset($rotation[1]) && ($angle = $rotation[1] % 360) != 0) {
        	$steps = $angle / 90;
                
            $_w = $tpl['w'];
            $_h = $tpl['h'];
            $tpl['w'] = $steps % 2 == 0 ? $_w : $_h;
            $tpl['h'] = $steps % 2 == 0 ? $_h : $_w;
            
            if ($angle < 0)
            	$angle += 360;
            
        	$tpl['_rotationAngle'] = $angle * -1;
        }
        
        $this->_importedPages[$pageKey] = $this->tpl;
        
        return $this->tpl;
    }
    
    /**
     * Returns the last used page box
     *
     * @return string
     */
    function getLastUsedPageBox() {
        return $this->lastUsedPageBox;
    }
    
    
    function useTemplate($tplidx, $_x = null, $_y = null, $_w = 0, $_h = 0, $adjustPageSize = false) {
        if ($adjustPageSize == true && is_null($_x) && is_null($_y)) {
            $size = $this->getTemplateSize($tplidx, $_w, $_h);
            $orientation = $size['w'] > $size['h'] ? 'L' : 'P';
            $size = array($size['w'], $size['h']);
            
            if (is_subclass_of($this, 'TCPDF')) {
            	$this->setPageFormat($size, $orientation);
            } else {
            	$size = $this->_getpagesize($size);
            	
            	if($orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1])
				{
					// New size or orientation
					if($orientation=='P')
					{
						$this->w = $size[0];
						$this->h = $size[1];
					}
					else
					{
						$this->w = $size[1];
						$this->h = $size[0];
					}
					$this->wPt = $this->w*$this->k;
					$this->hPt = $this->h*$this->k;
					$this->PageBreakTrigger = $this->h-$this->bMargin;
					$this->CurOrientation = $orientation;
					$this->CurPageSize = $size;
					$this->PageSizes[$this->page] = array($this->wPt, $this->hPt);
				}
            } 
        }
        
        $this->_out('q 0 J 1 w 0 j 0 G 0 g'); // reset standard values
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
                if (isset($this->_obj_stack[$filename]) && is_array($this->_obj_stack[$filename])) {
                    while(($n = key($this->_obj_stack[$filename])) !== null) {
                        $nObj = $this->current_parser->pdf_resolve_object($this->current_parser->c, $this->_obj_stack[$filename][$n][1]);
						
                        $this->_newobj($this->_obj_stack[$filename][$n][0]);
                        
                        if ($nObj[0] == PDF_TYPE_STREAM) {
							$this->pdf_write_value($nObj);
                        } else {
                            $this->pdf_write_value($nObj[1]);
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
     * Private Method that writes the form xobjects
     */
    function _putformxobjects() {
        $filter=($this->compress) ? '/Filter /FlateDecode ' : '';
	    reset($this->tpls);
        foreach($this->tpls AS $tplidx => $tpl) {
            $p=($this->compress) ? gzcompress($tpl['buffer']) : $tpl['buffer'];
    		$this->_newobj();
    		$cN = $this->n; // TCPDF/Protection: rem current "n"
    		
    		$this->tpls[$tplidx]['n'] = $this->n;
    		$this->_out('<<' . $filter . '/Type /XObject');
            $this->_out('/Subtype /Form');
            $this->_out('/FormType 1');
            
            $this->_out(sprintf('/BBox [%.2F %.2F %.2F %.2F]', 
                (isset($tpl['box']['llx']) ? $tpl['box']['llx'] : $tpl['x']) * $this->k,
                (isset($tpl['box']['lly']) ? $tpl['box']['lly'] : -$tpl['y']) * $this->k,
                (isset($tpl['box']['urx']) ? $tpl['box']['urx'] : $tpl['w'] + $tpl['x']) * $this->k,
                (isset($tpl['box']['ury']) ? $tpl['box']['ury'] : $tpl['h'] - $tpl['y']) * $this->k
            ));
            
            $c = 1;
            $s = 0;
            $tx = 0;
            $ty = 0;
            
            if (isset($tpl['box'])) {
                $tx = -$tpl['box']['llx'];
                $ty = -$tpl['box']['lly']; 
                
                if ($tpl['_rotationAngle'] <> 0) {
                    $angle = $tpl['_rotationAngle'] * M_PI/180;
                    $c=cos($angle);
                    $s=sin($angle);
                    
                    switch($tpl['_rotationAngle']) {
                        case -90:
                           $tx = -$tpl['box']['lly'];
                           $ty = $tpl['box']['urx'];
                           break;
                        case -180:
                            $tx = $tpl['box']['urx'];
                            $ty = $tpl['box']['ury'];
                            break;
                        case -270:
                        	$tx = $tpl['box']['ury'];
                            $ty = -$tpl['box']['llx'];
                            break;
                    }
                }
            } else if ($tpl['x'] != 0 || $tpl['y'] != 0) {
                $tx = -$tpl['x'] * 2;
                $ty = $tpl['y'] * 2;
            }
            
            $tx *= $this->k;
            $ty *= $this->k;
            
            if ($c != 1 || $s != 0 || $tx != 0 || $ty != 0) {
                $this->_out(sprintf('/Matrix [%.5F %.5F %.5F %.5F %.5F %.5F]',
                    $c, $s, -$s, $c, $tx, $ty
                ));
            }
            
            $this->_out('/Resources ');

            if (isset($tpl['resources'])) {
                $this->current_parser =& $tpl['parser'];
                $this->pdf_write_value($tpl['resources']); // "n" will be changed
            } else {
                $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
            	if (isset($this->_res['tpl'][$tplidx]['fonts']) && count($this->_res['tpl'][$tplidx]['fonts'])) {
                	$this->_out('/Font <<');
                    foreach($this->_res['tpl'][$tplidx]['fonts'] as $font)
                		$this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
                	$this->_out('>>');
                }
            	if(isset($this->_res['tpl'][$tplidx]['images']) && count($this->_res['tpl'][$tplidx]['images']) || 
            	   isset($this->_res['tpl'][$tplidx]['tpls']) && count($this->_res['tpl'][$tplidx]['tpls']))
            	{
                    $this->_out('/XObject <<');
                    if (isset($this->_res['tpl'][$tplidx]['images']) && count($this->_res['tpl'][$tplidx]['images'])) {
                        foreach($this->_res['tpl'][$tplidx]['images'] as $image)
                  			$this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
                    }
                    if (isset($this->_res['tpl'][$tplidx]['tpls']) && count($this->_res['tpl'][$tplidx]['tpls'])) {
                        foreach($this->_res['tpl'][$tplidx]['tpls'] as $i => $tpl)
                            $this->_out($this->tplprefix . $i . ' ' . $tpl['n'] . ' 0 R');
                    }
                    $this->_out('>>');
            	}
            	$this->_out('>>');
            }

            $nN = $this->n; // TCPDF: rem new "n"
            $this->n = $cN; // TCPDF: reset to current "n"
            if (is_subclass_of($this, 'TCPDF')) {
            	$p = $this->_getrawstream($p);
            	$this->_out('/Length ' . strlen($p) . ' >>');
            	$this->_out("stream\n" . $p . "\nendstream");
            } else {
	            $this->_out('/Length ' . strlen($p) . ' >>');
	    		$this->_putstream($p);
            }
    		$this->_out('endobj');
    		$this->n = $nN; // TCPDF: reset to new "n"
        }
        
        $this->_putimportedobjects();
    }

    /**
     * Rewritten to handle existing own defined objects
     */
    function _newobj($obj_id = false, $onlynewobj = false) {
        if (!$obj_id) {
            $obj_id = ++$this->n;
        }

        //Begin a new object
        if (!$onlynewobj) {
            $this->offsets[$obj_id] = is_subclass_of($this, 'TCPDF') ? $this->bufferlen : strlen($this->buffer);
            $this->_out($obj_id . ' 0 obj');
            $this->_current_obj_id = $obj_id; // for later use with encryption
        }
        
        return $obj_id;
    }

    /**
     * Writes a value
     * Needed to rebuild the source document
     *
     * @param mixed $value A PDF-Value. Structure of values see cases in this method
     */
    function pdf_write_value(&$value)
    {
        if (is_subclass_of($this, 'TCPDF')) {
            parent::pdf_write_value($value);
        }
        
        switch ($value[0]) {

    		case PDF_TYPE_TOKEN:
                $this->_straightOut($value[1] . ' ');
    			break;
		    case PDF_TYPE_NUMERIC:
    		case PDF_TYPE_REAL:
                if (is_float($value[1]) && $value[1] != 0) {
    			    $this->_straightOut(rtrim(rtrim(sprintf('%F', $value[1]), '0'), '.') . ' ');
    			} else {
        			$this->_straightOut($value[1] . ' ');
    			}
    			break;
    			
    		case PDF_TYPE_ARRAY:

    			// An array. Output the proper
    			// structure and move on.

    			$this->_straightOut('[');
                for ($i = 0; $i < count($value[1]); $i++) {
    				$this->pdf_write_value($value[1][$i]);
    			}

    			$this->_out(']');
    			break;

    		case PDF_TYPE_DICTIONARY:

    			// A dictionary.
    			$this->_straightOut('<<');

    			reset ($value[1]);

    			while (list($k, $v) = each($value[1])) {
    				$this->_straightOut($k . ' ');
    				$this->pdf_write_value($v);
    			}

    			$this->_straightOut('>>');
    			break;

    		case PDF_TYPE_OBJREF:

    			// An indirect object reference
    			// Fill the object stack if needed
    			$cpfn =& $this->current_parser->filename;
    			
    			if (!isset($this->_don_obj_stack[$cpfn][$value[1]])) {
    			    $this->_newobj(false, true);
    			    $this->_obj_stack[$cpfn][$value[1]] = array($this->n, $value);
                    $this->_don_obj_stack[$cpfn][$value[1]] = array($this->n, $value); // Value is maybee obsolete!!!
                }
                $objid = $this->_don_obj_stack[$cpfn][$value[1]][0];

    			$this->_out($objid . ' 0 R');
    			break;

    		case PDF_TYPE_STRING:

    			// A string.
                $this->_straightOut('(' . $value[1] . ')');

    			break;

    		case PDF_TYPE_STREAM:

    			// A stream. First, output the
    			// stream dictionary, then the
    			// stream data itself.
                $this->pdf_write_value($value[1]);
    			$this->_out('stream');
    			$this->_out($value[2][1]);
    			$this->_out('endstream');
    			break;
    			
            case PDF_TYPE_HEX:
                $this->_straightOut('<' . $value[1] . '>');
                break;

            case PDF_TYPE_BOOLEAN:
    		    $this->_straightOut($value[1] ? 'true ' : 'false ');
    		    break;
            
    		case PDF_TYPE_NULL:
                // The null object.

    			$this->_straightOut('null ');
    			break;
    	}
    }
    
    
    /**
     * Modified so not each call will add a newline to the output.
     */
    function _straightOut($s) {
        if (!is_subclass_of($this, 'TCPDF')) {
            if($this->state==2)
        		$this->pages[$this->page] .= $s;
        	else
        		$this->buffer .= $s;
        } else {
            if ($this->state == 2) {
				if (isset($this->footerlen[$this->page]) AND ($this->footerlen[$this->page] > 0)) {
					// puts data before page footer
					$page = substr($this->getPageBuffer($this->page), 0, -$this->footerlen[$this->page]);
					$footer = substr($this->getPageBuffer($this->page), -$this->footerlen[$this->page]);
					$this->setPageBuffer($this->page, $page . ' ' . $s . "\n" . $footer);
				} else {
					$this->setPageBuffer($this->page, $s, true);
				}
			} else {
				$this->setBuffer($s);
			}
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