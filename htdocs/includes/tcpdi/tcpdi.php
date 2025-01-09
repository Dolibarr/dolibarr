<?php
//
//  TCPDI - Version 1.0
//  Based on FPDI - Version 1.4.4
//
//    Copyright 2004-2013 Setasign - Jan Slabon
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      https://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//

// Dummy shim to allow unmodified use of fpdf_tpl
class FPDF extends TCPDF {}

require_once('fpdf_tpl.php');

require_once('tcpdi_parser.php');


class TCPDI extends FPDF_TPL {
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
        $this->setPDFVersion(max($this->getPDFVersion(), $this->current_parser->getPDFVersion()));

        return $this->parsers[$filename]->getPageCount();
    }

    /**
     * Set a source-file PDF data
     *
     * @param string $pdfdata The PDF file content
     * @return int number of available pages
     */
    function setSourceData($pdfdata) {
        $filename = uniqid('tcpdi-');
        $this->current_filename = $filename;

        if (!isset($this->parsers[$filename]))
            $this->parsers[$filename] = new tcpdi_parser($pdfdata, $filename);
        $this->current_parser =& $this->parsers[$filename];
        $this->setPDFVersion(max($this->getPDFVersion(), $this->current_parser->getPDFVersion()));

        return $this->parsers[$filename]->getPageCount();
    }

    /**
     * Returns a PDF parser object
     *
     * @param string $filename
     * @return fpdi_pdf_parser
     */
    function _getPdfParser($filename) {
        $data = file_get_contents($filename);
    	return new tcpdi_parser($data, $filename);
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
	function setPDFVersion($version = '1.7') {
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

            $this->setPageFormat($size, $orientation);
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
                        $nObj = $this->current_parser->getObjectVal($this->_obj_stack[$filename][$n][1]);

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

                // We're done with this parser.  Clean it up to free a bit of RAM.
                $this->current_parser->cleanUp();
                unset($this->parsers[$filename]);
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
            } elseif (!empty($tpl['x']) || !empty($tpl['y'])) {
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

            $this->_out('/Group <</Type/Group/S/Transparency>>');

            $nN = $this->n; // TCPDF: rem new "n"
            $this->n = $cN; // TCPDF: reset to current "n"

        	$p = $this->_getrawstream($p);
        	$this->_out('/Length ' . strlen($p) . ' >>');
        	$this->_out("stream\n" . $p . "\nendstream");

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
            $this->offsets[$obj_id] = $this->bufferlen;
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
        switch ($value[0]) {
            case PDF_TYPE_STRING:
                if ($this->encrypted) {
                    $value[1] = $this->_unescape($value[1]);
                    $value[1] = $this->_encrypt_data($this->_current_obj_id, $value[1]);
                    $value[1] = TCPDF_STATIC::_escape($value[1]);
                }
                break;

            case PDF_TYPE_STREAM:
                if ($this->encrypted) {
                    $value[2][1] = $this->_encrypt_data($this->_current_obj_id, $value[2][1]);
                    $value[1][1]['/Length'] = array(
                        PDF_TYPE_NUMERIC,
                        strlen($value[2][1])
                    );
                }
                break;

            case PDF_TYPE_HEX:
                if ($this->encrypted) {
                    $value[1] = $this->hex2str($value[1]);
                    $value[1] = $this->_encrypt_data($this->_current_obj_id, $value[1]);

                    // remake hexstring of encrypted string
                    $value[1] = $this->str2hex($value[1]);
                }
                break;
        }

        switch ($value[0]) {

            case PDF_TYPE_TOKEN:
                $this->_straightOut('/'.$value[1] . ' ');
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

                foreach ($value[1] as $k => $v) {
                    $this->_straightOut($k . ' ');
                    $this->pdf_write_value($v);
                }

                $this->_straightOut('>>');
                break;

            case PDF_TYPE_OBJREF:

                // An indirect object reference
                // Fill the object stack if needed
                $cpfn =& $this->current_parser->uniqueid;

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
        if ($this->state == 2) {
			if ($this->inxobj) {
				// we are inside an XObject template
				$this->xobjects[$this->xobjid]['outdata'] .= $s;
			} elseif ((!$this->InFooter) AND isset($this->footerlen[$this->page]) AND ($this->footerlen[$this->page] > 0)) {
				// puts data before page footer
				$pagebuff = $this->getPageBuffer($this->page);
				$page = substr($pagebuff, 0, -$this->footerlen[$this->page]);
				$footer = substr($pagebuff, -$this->footerlen[$this->page]);
				$this->setPageBuffer($this->page, $page.$s.$footer);
				// update footer position
				$this->footerpos[$this->page] += strlen($s);
			} else {
				// set page data
				$this->setPageBuffer($this->page, $s, true);
			}
		} elseif ($this->state > 0) {
			// set general data
			$this->setBuffer($s);
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
        if ($this->state > 2 && is_array($this->parsers) && count($this->parsers) > 0) {
          	$this->cleanUp();
            return true;
        }
        return false;
    }

    /**
     * Removes cylced references and closes the file handles of the parser objects
     */
    function cleanUp() {
    	foreach ($this->parsers as $k => $_){
        	$this->parsers[$k]->cleanUp();
        	$this->parsers[$k] = null;
        	unset($this->parsers[$k]);
        }
    }

    // Functions from here on are taken from FPDI's fpdi2tcpdf_bridge.php to remove dependence on it
    function _putstream($s, $n=0) {
        $this->_out($this->_getstream($s, $n));
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
