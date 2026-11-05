<?php
//============================================================+
// File name   : tcpdi_parser.php
// Version     : 1.1
// Begin       : 2013-09-25
// Last Update : 2016-05-03
// Author      : Paul Nicholls - https://github.com/pauln
// License     : GNU-LGPL v3 (https://www.gnu.org/copyleft/lesser.html)
//
// Based on    : tcpdf_parser.php
// Version     : 1.0.003
// Begin       : 2011-05-23
// Last Update : 2013-03-17
// Author      : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (https://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2011-2013 Nicola Asuni - Tecnick.com LTD
//
// This file is for use with the TCPDF software library.
//
// tcpdi_parser is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// tcpdi_parser is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the License
// along with tcpdi_parser. If not, see
// <http://www.tecnick.com/pagefiles/tcpdf/LICENSE.TXT>.
//
// See LICENSE file for more information.
// -------------------------------------------------------------------
//
// Description : This is a PHP class for parsing PDF documents.
//
//============================================================+

/**
 * @file
 * This is a PHP class for parsing PDF documents.<br>
 * @author Paul Nicholls
 * @author Nicola Asuni
 * @version 1.1
 */

// include class for decoding filters
if (defined('TCPDF_PATH')) require_once(constant('TCPDF_PATH').'/include/tcpdf_filters.php');
else require_once(dirname(__FILE__).'/../tecnickcom/tcpdf/include/tcpdf_filters.php');

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

/**
 * @class tcpdi_parser
 * This is a PHP class for parsing PDF documents.<br>
 * Based on TCPDF_PARSER, part of the TCPDF project by Nicola Asuni.
 * @brief This is a PHP class for parsing PDF documents..
 * @version 1.1
 * @author Paul Nicholls - github.com/pauln
 * @author Nicola Asuni - info@tecnick.com
 */
class tcpdi_parser {
    /**
     * Unique parser ID
     * @public
     */
    public $uniqueid = '';

    /**
     * Raw content of the PDF document.
     * @private
     */
    private $pdfdata = '';

    /**
     * XREF data.
     * @protected
     */
    protected $xref = array();

    /**
     * Object streams.
     * @protected
     */
    protected $objstreams = array();

    /**
     * Objects in objstreams.
     * @protected
     */
    protected $objstreamobjs = array();

    /**
     * List of seen XREF data locations.
     * @protected
     */
    protected $xref_seen_offsets = array();

    /**
     * Array of PDF objects.
     * @protected
     */
    protected $objects = array();

    /**
     * Array of object offsets.
     * @private
     */
    private $objoffsets = array();

    /**
     * Class object for decoding filters.
     * @private
     */
    private $FilterDecoders;

    /**
     * Pages
     *
     * @private array
     */
    private $pages;

    /**
     * Page count
     * @private integer
     */
    private $page_count;

    /**
     * actual page number
     * @private integer
     */
    private $pageno;

    /**
     * PDF version of the loaded document
     * @private string
     */
    private $pdfVersion;

    /**
     * Available BoxTypes
     *
     * @public array
     */
    public $availableBoxes = array('/MediaBox', '/CropBox', '/BleedBox', '/TrimBox', '/ArtBox');

// -----------------------------------------------------------------------------

    /**
     * Parse a PDF document an return an array of objects.
     * @param $data (string) PDF data to parse.
     * @public
     * @since 1.0.000 (2011-05-24)
     */
    public function __construct($data, $uniqueid) {
        if (empty($data)) {
            $this->Error('Empty PDF data.');
        }
        $this->uniqueid = $uniqueid;
        $this->pdfdata = $data;
        // initialize class for decoding filters
        $this->FilterDecoders = new TCPDF_FILTERS();
        // @CHANGE DOL Locate every object before anything else (the object streams are only extracted once the xref is
        // known): reading the xref, or an object stream, may need an indirect /Length object declared later in the file.
        $objstreams = $this->findObjectOffsets();
        // get xref and trailer data
        $this->xref = $this->getXrefData();
        // @CHANGE DOL An encrypted document can not be imported: say so instead of failing later on an obscure
        // "decodeFilterFlateDecode: invalid code", or importing garbage when the streams are not compressed.
        if (isset($this->xref['trailer'][1]['/Encrypt'])) {
            $this->Error('This PDF document is encrypted and cannot be imported.');
        }
        foreach ($objstreams as $objstream) {
            $this->extractObjectStream($objstream);
        }
        // parse all document objects
        $this->objects = array();
        /*foreach ($this->xref['xref'] as $obj => $offset) {
            if (!isset($this->objects[$obj]) AND ($offset > 0)) {
                // decode only objects with positive offset
                //$this->objects[$obj] = $this->getIndirectObject($obj, $offset, true);
            }
        }*/
        $this->getPDFVersion();
        $this->readPages();
    }

    /**
     * Clean up when done, to free memory etc
     */
    public function cleanUp() {
        unset($this->pdfdata);
        $this->pdfdata = '';
        unset($this->objstreams);
        $this->objstreams = array();
        unset($this->objects);
        $this->objects = array();
        unset($this->objstreamobjs);
        $this->objstreamobjs = array();
        unset($this->xref);
        $this->xref = array();
        unset($this->objoffsets);
        $this->objoffsets = array();
        unset($this->pages);
        $this->pages = array();
    }

    /**
     * Return an array of parsed PDF document objects.
     * @return (array) Array of parsed PDF document objects.
     * @public
     * @since 1.0.000 (2011-06-26)
     */
    public function getParsedData() {
        return array($this->xref, $this->objects, $this->pages);
    }

    /**
     * Get PDF-Version
     *
     * And reset the PDF Version used in FPDI if needed
     * @public
     */
    public function getPDFVersion() {
        preg_match('/\d\.\d/', substr($this->pdfdata, 0, 16), $m);
        if (isset($m[0]))
            $this->pdfVersion = $m[0];
        return $this->pdfVersion;
    }

    /**
     * Read all /Page(es)
     *
     */
    function readPages() {
        // @CHANGE DOL Always leave a usable (possibly empty) page list
        $this->pages = array();
        $this->page_count = 0;
        if (!isset($this->xref['trailer'][1]['/Root'])) {
            return;
        }
        $params = $this->getObjectVal($this->xref['trailer'][1]['/Root']);
        $objref = null;
        if (isset($params[1][1]) && is_array($params[1][1])) {
            foreach ($params[1][1] as $k=>$v) {
                if ($k == '/Pages') {
                    $objref = $v;
                    break;
                }
            }
        }
        if ($objref == null || $objref[0] !== PDF_TYPE_OBJREF) {
            // Offset not found.
            return;
        }

        $dict = $this->getObjectVal($objref);
        if ($dict[0] == PDF_TYPE_OBJECT && isset($dict[1][0]) && $dict[1][0] == PDF_TYPE_DICTIONARY) {
            // Dict wrapped in an object
            $dict = $dict[1];
        }

        if ($dict[0] !== PDF_TYPE_DICTIONARY) {
            return;
        }

        $seen = array();
        $kids = $this->getKids($dict[1]);
        if ($kids !== null) {
            foreach ($kids as $ref) {
                $page = $this->getObjectVal($ref);
                $this->readPage($page, $seen);
            }
        }

        $this->page_count = count($this->pages);
    }

    /**
     * Get the list of references of the /Kids entry of a /Pages dictionary.
     * @CHANGE DOL New method: /Kids may be an indirect reference to the array (any value of a dictionary may be).
     *
     * @param array $dict Content of the dictionary
     * @return array|null List of references, null if there is no usable /Kids entry
     */
    private function getKids($dict) {
        if (!isset($dict['/Kids'])) {
            return null;
        }
        $kids = $dict['/Kids'];
        if ($kids[0] == PDF_TYPE_OBJREF) {
            $kids = $this->getObjectVal($kids);
            $kids = isset($kids[1]) ? $kids[1] : null;
        }
        if (is_array($kids) && $kids[0] == PDF_TYPE_ARRAY && is_array($kids[1])) {
            return $kids[1];
        }
        return null;
    }

    /**
     * Read a single /Page element, recursing through /Kids if necessary
     *
     */
    private function readPage($page, &$seen = array()) {
        if (!isset($page[1][1]) || !is_array($page[1][1])) {
            // @CHANGE DOL Not a dictionary (unresolved reference): this is not a page
            return;
        }
        $kids = $this->getKids($page[1][1]);
        if ($kids !== null) {
            // Nested pages!
            foreach ($kids as $subref) {
                // @CHANGE DOL Do not follow a page tree that loops back on itself
                $id = (isset($subref[1]) ? $subref[1] : '').'_'.(isset($subref[2]) ? $subref[2] : '');
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $subpage = $this->getObjectVal($subref);
                $this->readPage($subpage, $seen);
            }
        } else {
            $this->pages[] = $page;
        }
    }

    /**
     * Get pagecount from sourcefile
     *
     * @return int
     */
    function getPageCount() {
        return $this->page_count;
    }

    /**
     * Get Cross-Reference (xref) table and trailer data from PDF document data.
     * @param $offset (int) xref offset (if know).
     * @param $xref (array) previous xref array (if any).
     * @return Array containing xref and trailer data.
     * @protected
     * @since 1.0.000 (2011-05-24)
     */
    protected function getXrefData($offset=0, $xref=array()) {
        if ($offset == 0) {
            // find last startxref
            if (preg_match('/.*[\r\n]startxref[\s\r\n]+([0-9]+)[\s\r\n]+%%EOF/is', $this->pdfdata, $matches) == 0) {
                $this->Error('Unable to find startxref');
            }
            $startxref = $matches[1];
        } else {
            if (preg_match('/([0-9]+[\s][0-9]+[\s]obj)/i', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                // Cross-Reference Stream object
                $startxref = $offset;
            } elseif (preg_match('/[\r\n]startxref[\s\r\n]+([0-9]+)[\s\r\n]+%%EOF/i', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                // startxref found
                $startxref = $matches[1][0];
            } else {
                $this->Error('Unable to find startxref');
            }
        }
        unset($matches);

        // @CHANGE DOL Check that the offset is inside the file and really is the start of a xref section (table or
        // stream). With PHP 8, strpos() throws a ValueError on an offset beyond the end of the data.
        $startxref = (int) $startxref;
        $pdflen = strlen($this->pdfdata);
        if ($startxref < $pdflen) {
            // DOMPDF gets the startxref wrong, giving us the linebreak before the xref starts.
            $startxref += strspn($this->pdfdata, "\x00\x09\x0a\x0c\x0d\x20", $startxref);
        }
        $isxreftable = ($startxref < $pdflen && substr($this->pdfdata, $startxref, 4) === 'xref');
        $isxrefstream = (!$isxreftable && $startxref < $pdflen && preg_match('/\G[0-9]+[\s]+[0-9]+[\s]+obj/', $this->pdfdata, $matches, 0, $startxref) == 1);
        if (!$isxreftable && !$isxrefstream) {
            if ($offset != 0) {
                // A previous xref section that can not be found: keep what the newer sections gave.
                return $xref;
            }
            // The startxref of the file is wrong: fall back on the last xref table of the file, if any.
            if (preg_match_all('/[\r\n]xref[\s]*[\r\n]/', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE) >= 1) {
                $last = end($matches[0]);
                $startxref = $last[1] + 1;
                $isxreftable = true;
            } else {
                $this->Error('Unable to find xref');
            }
        }
        unset($matches);

        // check xref position
        if ($isxreftable) {
            // Cross-Reference
            $xref = $this->decodeXref($startxref, $xref);
        } else {
            // Cross-Reference Stream
            $xref = $this->decodeXrefStream($startxref, $xref);
        }
        if (empty($xref)) {
            $this->Error('Unable to find xref');
        }

        return $xref;
    }

    /**
     * Decode the Cross-Reference section
     * @param $startxref (int) Offset at which the xref section starts.
     * @param $xref (array) Previous xref array (if any).
     * @return Array containing xref and trailer data.
     * @protected
     * @since 1.0.000 (2011-06-20)
     */
    protected function decodeXref($startxref, $xref=array()) {
        $this->xref_seen_offsets[] = $startxref;
        if (!isset($xref['xref_location'])) {
            $xref['xref_location'] = $startxref;
            $xref['max_object'] = 0;
        }
        // extract xref data (object indexes and offsets)
        $xoffset = $startxref + 5;
        // initialize object number
        $obj_num = 0;
        $offset = $xoffset;
        while (preg_match('/^([0-9]+)[\s]([0-9]+)[\s]?([nf]?)/im', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE, $offset) > 0) {
            $offset = (strlen($matches[0][0]) + $matches[0][1]);
            if ($matches[3][0] == 'n') {
                // create unique object index: [object number]_[generation number]
                $gen_num = intval($matches[2][0]);
                $index = $obj_num.'_'.$gen_num;
                // check if object already exist
                if (!isset($xref['xref'][$obj_num][$gen_num])) {
                    // store object offset position
                    $xref['xref'][$obj_num][$gen_num] = intval($matches[1][0]);
                }
                ++$obj_num;
                $offset += 2;
            } elseif ($matches[3][0] == 'f') {
                ++$obj_num;
                $offset += 2;
            } else {
                // object number (index)
                $obj_num = intval($matches[1][0]);
            }
        }
        unset($matches);
        $xref['max_object'] = max($xref['max_object'], $obj_num);
        // get trailer data
        if (preg_match('/trailer[\s]*<<(.*)>>[\s\r\n]+(?:[%].*[\r\n]+)*startxref[\s\r\n]+/isU', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE, $xoffset) > 0) {
            $trailer_data = $matches[1][0];
            if (!isset($xref['trailer']) OR empty($xref['trailer'])) {
                // get only the last updated version
                $xref['trailer'] = array();
                $xref['trailer'][0] = PDF_TYPE_DICTIONARY;
                $xref['trailer'][1] = array();
                // parse trailer_data
                if (preg_match('/Size[\s]+([0-9]+)/i', $trailer_data, $matches) > 0) {
                    $xref['trailer'][1]['/Size'] = array(PDF_TYPE_NUMERIC, intval($matches[1]));
                }
                if (preg_match('/Root[\s]+([0-9]+)[\s]+([0-9]+)[\s]+R/i', $trailer_data, $matches) > 0) {
                    $xref['trailer'][1]['/Root'] = array(PDF_TYPE_OBJREF, intval($matches[1]), intval($matches[2]));
                }
                if (preg_match('/Encrypt[\s]+([0-9]+)[\s]+([0-9]+)[\s]+R/i', $trailer_data, $matches) > 0) {
                    $xref['trailer'][1]['/Encrypt'] = array(PDF_TYPE_OBJREF, intval($matches[1]), intval($matches[2]));
                } elseif (preg_match('/\/Encrypt[\s]*<</', $trailer_data) > 0) {
                    // @CHANGE DOL encryption dictionary written directly in the trailer
                    $xref['trailer'][1]['/Encrypt'] = array(PDF_TYPE_DICTIONARY, array());
                }
                if (preg_match('/Info[\s]+([0-9]+)[\s]+([0-9]+)[\s]+R/i', $trailer_data, $matches) > 0) {
                    $xref['trailer'][1]['/Info'] = array(PDF_TYPE_OBJREF, intval($matches[1]), intval($matches[2]));
                }
                if (preg_match('/ID[\s]*[\[][\s]*[<]([^>]*)[>][\s]*[<]([^>]*)[>]/i', $trailer_data, $matches) > 0) {
                    $xref['trailer'][1]['/ID'] = array(PDF_TYPE_ARRAY, array());
                    $xref['trailer'][1]['/ID'][1][0] = array(PDF_TYPE_HEX, $matches[1]);
                    $xref['trailer'][1]['/ID'][1][1] = array(PDF_TYPE_HEX, $matches[2]);
                }
            }
            if (preg_match('/Prev[\s]+([0-9]+)/i', $trailer_data, $matches) > 0) {
                // get previous xref
                $prevoffset = intval($matches[1]);
                if (!in_array($prevoffset, $this->xref_seen_offsets)) {
                    $this->xref_seen_offsets[] = $prevoffset;
                    $xref = $this->getXrefData($prevoffset, $xref);
                }
            }
            unset($matches);
        } else {
            $this->Error('Unable to find trailer');
        }
        return $xref;
    }

    /**
     * Decode the Cross-Reference Stream section
     * @param $startxref (int) Offset at which the xref section starts.
     * @param $xref (array) Previous xref array (if any).
     * @return Array containing xref and trailer data.
     * @protected
     * @since 1.0.003 (2013-03-16)
     */
    protected function decodeXrefStream($startxref, $xref=array()) {
        // try to read Cross-Reference Stream
        list($xrefobj, $unused) = $this->getRawObject($startxref);
        if ($xrefobj[0] !== PDF_TYPE_OBJECT) {
            $this->Error('Unable to find xref');
        }
        $xrefcrs = $this->getIndirectObject($xrefobj[1], $startxref, true);
        if (!isset($xrefcrs[0][0]) || $xrefcrs[0][0] !== PDF_TYPE_DICTIONARY) {
            $this->Error('Unable to find xref');
        }
        if (!isset($xref['xref_location'])) {
            $xref['xref_location'] = $startxref;
            $xref['max_object'] = 0;
        }
        if (!isset($xref['xref'])) {
            $xref['xref'] = array();
        }
        if (!isset($xref['trailer']) OR empty($xref['trailer'])) {
            // get only the last updated version
            $xref['trailer'] = array();
            $xref['trailer'][0] = PDF_TYPE_DICTIONARY;
            $xref['trailer'][1] = array();
            $filltrailer = true;
        } else {
            $filltrailer = false;
        }
        $valid_crs = false;
        $sarr = $xrefcrs[0][1];
        $keys = array_keys($sarr);
        $columns = 1; // Default as per PDF 32000-1:2008.
        $predictor = 1; // Default as per PDF 32000-1:2008.
        $index = array(); // @CHANGE DOL list of (first object number, number of entries), /Index may hold several subsections
        foreach ($keys as $k=>$key) {
            $v = $sarr[$key];
            if (($key == '/Type') AND ($v[0] == PDF_TYPE_TOKEN AND ($v[1] == 'XRef'))) {
                $valid_crs = true;
            } elseif (($key == '/Index') AND ($v[0] == PDF_TYPE_ARRAY AND count($v[1]) >= 2)) {
                for ($i = 0; ($i + 1) < count($v[1]); $i += 2) {
                    // first object number in the subsection, number of entries in the subsection
                    $index[] = array(intval($v[1][$i][1]), intval($v[1][($i + 1)][1]));
                }
            } elseif (($key == '/Prev') AND ($v[0] == PDF_TYPE_NUMERIC)) {
                // get previous xref offset
                $prevxref = intval($v[1]);
            } elseif (($key == '/W') AND ($v[0] == PDF_TYPE_ARRAY) AND (count($v[1]) >= 3)) {
                // number of bytes (in the decoded stream) of the corresponding field
                $wb = array();
                $wb[0] = intval($v[1][0][1]);
                $wb[1] = intval($v[1][1][1]);
                $wb[2] = intval($v[1][2][1]);
            } elseif (($key == '/DecodeParms') AND ($v[0] == PDF_TYPE_DICTIONARY)) {
                $decpar = $v[1];
                foreach ($decpar as $kdc => $vdc) {
                    if (($kdc == '/Columns') AND ($vdc[0] == PDF_TYPE_NUMERIC)) {
                        $columns = intval($vdc[1]);
                    } elseif (($kdc == '/Predictor') AND ($vdc[0] == PDF_TYPE_NUMERIC)) {
                        $predictor = intval($vdc[1]);
                    }
                }
            } elseif ($filltrailer) {
                switch($key) {
                    case '/Size':
                    case '/Root':
                    case '/Info':
                    case '/ID':
                    case '/Encrypt': // @CHANGE DOL
                        $xref['trailer'][1][$key] = $v;
                        break;
                    default:
                        break;
                }
            }
        }
        // decode data
        $obj_num = 0;
        if ($valid_crs AND isset($xrefcrs[1][3][0]) AND isset($wb)) {
            // convert the stream into an array of integers
            $sdata = unpack('C*', $xrefcrs[1][3][0]);
            // initialize decoded array
            $ddata = array();
            if ($predictor >= 10) {
                // PNG prediction: each row starts with a tag byte giving the PNG filter used by this row
                // number of bytes in a row
                $rowlen = ($columns + 1);
                // split the rows
                $sdata = array_chunk($sdata, $rowlen);
                // initialize first row with zeros
                $prev_row = array_fill (0, $rowlen, 0);
                // for each row apply PNG unpredictor
                foreach ($sdata as $k => $row) {
                    // initialize new row
                    $ddata[$k] = array();
                    // @CHANGE DOL the filter is the one of the row, not the /Predictor value of the stream
                    $rowpredictor = (10 + $row[0]);
                    // for each byte on the row
                    for ($i=1; $i<=$columns; ++$i) {
                        if (!isset($row[$i])) {
                            // No more data in this row - we're done here.
                            break;
                        }
                        // new index
                        $j = ($i - 1);
                        $row_up = isset($prev_row[$j]) ? $prev_row[$j] : 0;
                        if ($i == 1) {
                            $row_left = 0;
                            $row_upleft = 0;
                        } else {
                            // @CHANGE DOL the byte on the left is the decoded one
                            $row_left = $ddata[$k][($j - 1)];
                            $row_upleft = isset($prev_row[($j - 1)]) ? $prev_row[($j - 1)] : 0;
                        }
                        switch ($rowpredictor) {
                            case 10: { // PNG None
                                $ddata[$k][$j] = $row[$i];
                                break;
                            }
                            case 11: { // PNG Sub
                                $ddata[$k][$j] = (($row[$i] + $row_left) & 0xff);
                                break;
                            }
                            case 12: { // PNG Up
                                $ddata[$k][$j] = (($row[$i] + $row_up) & 0xff);
                                break;
                            }
                            case 13: { // PNG Average
                                $ddata[$k][$j] = (($row[$i] + (int) floor(($row_left + $row_up) / 2)) & 0xff);
                                break;
                            }
                            case 14: { // PNG Paeth
                                // initial estimate
                                $p = ($row_left + $row_up - $row_upleft);
                                // distances
                                $pa = abs($p - $row_left);
                                $pb = abs($p - $row_up);
                                $pc = abs($p - $row_upleft);
                                $pmin = min($pa, $pb, $pc);
                                // return minumum distance
                                switch ($pmin) {
                                    case $pa: {
                                        $ddata[$k][$j] = (($row[$i] + $row_left) & 0xff);
                                        break;
                                    }
                                    case $pb: {
                                        $ddata[$k][$j] = (($row[$i] + $row_up) & 0xff);
                                        break;
                                    }
                                    case $pc: {
                                        $ddata[$k][$j] = (($row[$i] + $row_upleft) & 0xff);
                                        break;
                                    }
                                }
                                break;
                            }
                            default: {
                                $this->Error("Unknown PNG predictor $rowpredictor");
                                break;
                            }
                        }
                    }
                    $prev_row = $ddata[$k];
                } // end for each row
            } else {
                // @CHANGE DOL No predictor (cairo, PDFlib, pdfTeX...): the stream is the plain list of the entries,
                // sum(W) bytes each, without any tag byte. It was read as if a PNG predictor was always used.
                $rowlen = array_sum($wb);
                if ($rowlen > 0) {
                    $ddata = array_chunk($sdata, $rowlen);
                }
            }
            // complete decoding
            unset($sdata);
            $sdata = array();
            // for every row
            foreach ($ddata as $k => $row) {
                // initialize new row
                $sdata[$k] = array(0, 0, 0);
                if ($wb[0] == 0) {
                    // default type field
                    $sdata[$k][0] = 1;
                }
                $i = 0; // count bytes on the row
                // for every column
                for ($c = 0; $c < 3; ++$c) {
                    // for every byte on the column
                    for ($b = 0; $b < $wb[$c]; ++$b) {
                        if (isset($row[$i])) {
                            $sdata[$k][$c] += ($row[$i] << (($wb[$c] - 1 - $b) * 8));
                        }
                        ++$i;
                    }
                }
            }
            unset($ddata);
            // fill xref
            if (empty($index)) {
                $index[] = array(0, count($sdata));
            }
            $k = 0;
            foreach ($index as $subsection) {
                $obj_num = $subsection[0];
                for ($n = 0; ($n < $subsection[1]) AND isset($sdata[$k]); ++$n, ++$k) {
                    $row = $sdata[$k];
                    // (0) free object, (2) object stored in an object stream: nothing to store for them
                    if ($row[0] == 1) { // (n) objects that are in use but are not compressed
                        // check if object already exist
                        if (!isset($xref['xref'][$obj_num][$row[2]])) {
                            // store object offset position
                            $xref['xref'][$obj_num][$row[2]] = $row[1];
                        }
                    }
                    // @CHANGE DOL every entry stands for one object number, whatever its type. The number was
                    // not incremented on the entries of compressed objects, shifting all the following ones.
                    ++$obj_num;
                }
                $xref['max_object'] = max($xref['max_object'], $obj_num);
            }
        } // end decoding data
        $xref['max_object'] = max($xref['max_object'], $obj_num);
        if (isset($prevxref)) {
            // get previous xref
            $xref = $this->getXrefData($prevxref, $xref);
        }
        return $xref;
    }

    /**
     * Get raw stream data
     * @param $offset (int) Stream offset.
     * @param $sdic (array) Stream's dictionary array.
     * @return array containing the stream and the offset to the next object
     * @protected
     */
    protected function getRawStream($offset, $sdic) {
        $pdflen = strlen($this->pdfdata);
        $offset += strspn($this->pdfdata, "\x00\x09\x0a\x0c\x0d\x20", $offset);
        $offset += 6; // "stream"
        // @CHANGE DOL The keyword is followed by ONE end-of-line marker (CRLF or LF, a lone CR is tolerated). Every CR and
        // LF was skipped, so a stream whose data starts with such a byte lost its first bytes and the parser lost its way.
        $spaces = strspn($this->pdfdata, "\x09\x20", $offset);
        if ($spaces > 0 && isset($this->pdfdata[$offset + $spaces]) && ($this->pdfdata[$offset + $spaces] === "\r" || $this->pdfdata[$offset + $spaces] === "\n")) {
            $offset += $spaces;
        }
        if (substr($this->pdfdata, $offset, 2) === "\r\n") {
            $offset += 2;
        } elseif (isset($this->pdfdata[$offset]) && ($this->pdfdata[$offset] === "\n" || $this->pdfdata[$offset] === "\r")) {
            $offset += 1;
        }

        // @CHANGE DOL Get the declared length. It may be an indirect object, that may not be found.
        $length = null;
        if (isset($sdic['/Length'])) {
            $lengthobj = $sdic['/Length'];
            if ($lengthobj[0] === PDF_TYPE_OBJREF) {
                $lengthobj = $this->getObjectVal($lengthobj);
                if ($lengthobj[0] === PDF_TYPE_OBJECT && isset($lengthobj[1]) && is_array($lengthobj[1])) {
                    $lengthobj = $lengthobj[1];
                }
            }
            if (isset($lengthobj[0]) && $lengthobj[0] === PDF_TYPE_NUMERIC && is_numeric($lengthobj[1])) {
                $length = (int) $lengthobj[1];
            }
        }
        // @CHANGE DOL Trust the declared length only if the stream really ends there, else look for the end of the stream.
        // The number of the object holding the length was used as the length when this object was not found.
        if ($length === null || $length < 0 || ($offset + $length) > $pdflen || preg_match('/\G[\s]*endstream/', $this->pdfdata, $unused, 0, $offset + $length) != 1) {
            $end = ($offset <= $pdflen) ? strpos($this->pdfdata, 'endstream', $offset) : false;
            if ($end === false) {
                $this->Error('Unable to find the end of a stream');
                $end = $pdflen;
            }
            $length = $end - $offset;
            // remove the end-of-line marker before the keyword, it is not part of the data
            if ($length > 0 && $this->pdfdata[$offset + $length - 1] === "\n") {
                $length--;
            }
            if ($length > 0 && $this->pdfdata[$offset + $length - 1] === "\r") {
                $length--;
            }
        }

        $obj = array();
        $obj[] = PDF_TYPE_STREAM;
        $obj[] = (string) substr($this->pdfdata, $offset, $length);

        return array($obj, $offset+$length);
    }

    /**
     * Get object type, raw value and offset to next object
     * @param $offset (int) Object offset.
     * @return array containing object type, raw value and offset to next object
     * @protected
     * @since 1.0.000 (2011-06-20)
     */
    protected function getRawObject($offset=0, $data=null) {
        if ($data === null) {
            $data =& $this->pdfdata;
        }
        $objtype = ''; // object type to be returned
        $objval = ''; // object value to be returned
        $datalen = strlen($data);
        // skip initial white space chars: \x00 null (NUL), \x09 horizontal tab (HT), \x0A line feed (LF), \x0C form feed (FF), \x0D carriage return (CR), \x20 space (SP)
        if ($offset < $datalen) {
            $offset += strspn($data, "\x00\x09\x0a\x0c\x0d\x20", $offset);
        }
        if ($offset >= $datalen) {
            // @CHANGE DOL End of the data: tell it to the callers so that they stop, they were looping forever
            return array(array('eof', ''), $datalen);
        }
        // get first char
        $char = $data[$offset];
        // get object type
        switch ($char) {
            case '%': { // \x25 PERCENT SIGN
                // skip comment and search for next token
                // @CHANGE DOL Return the object AND the offset, as everywhere else: only the object was returned, so
                // a comment inside an object ended in a TypeError in the caller.
                $offset += strcspn($data, "\r\n", $offset);
                return $this->getRawObject($offset, $data);
            }
            case '/': { // \x2F SOLIDUS
                // name object
                $objtype = PDF_TYPE_TOKEN;
                ++$offset;
                $length = strcspn($data, "\x00\x09\x0a\x0c\x0d\x20\x28\x29\x3c\x3e\x5b\x5d\x7b\x7d\x2f\x25", $offset);
                $objval = substr($data, $offset, $length);
                $offset += $length;
                break;
            }
            case '(':   // \x28 LEFT PARENTHESIS
            case ')': { // \x29 RIGHT PARENTHESIS
                // literal string object
                $objtype = PDF_TYPE_STRING;
                ++$offset;
                $strpos = $offset;
                if ($char == '(') {
                    $open_bracket = 1;
                    while ($open_bracket > 0) {
                        if (!isset($data[$strpos])) {
                            break;
                        }
                        $ch = $data[$strpos];
                        switch ($ch) {
                            case '\\': { // REVERSE SOLIDUS (5Ch) (Backslash)
                                // skip next character
                                ++$strpos;
                                break;
                            }
                            case '(': { // LEFT PARENHESIS (28h)
                                ++$open_bracket;
                                break;
                            }
                            case ')': { // RIGHT PARENTHESIS (29h)
                                --$open_bracket;
                                break;
                            }
                        }
                        ++$strpos;
                    }
                    $objval = substr($data, $offset, ($strpos - $offset - 1));
                    $offset = $strpos;
                }
                break;
            }
            case '[':   // \x5B LEFT SQUARE BRACKET
            case ']': { // \x5D RIGHT SQUARE BRACKET
                // array object
                $objtype = PDF_TYPE_ARRAY;
                ++$offset;
                if ($char == '[') {
                    // get array content
                    $objval = array();
                    do {
                        // get element
                        list($element, $offset) = $this->getRawObject($offset, $data);
                        $objval[] = $element;
                    } while (($element[0] !== ']') AND ($element[0] !== 'eof'));
                    // remove closing delimiter
                    array_pop($objval);
                } else {
                    $objtype = ']';
                }
                break;
            }
            case '<':   // \x3C LESS-THAN SIGN
            case '>': { // \x3E GREATER-THAN SIGN
                if (isset($data[($offset + 1)]) AND ($data[($offset + 1)] == $char)) {
                    // dictionary object
                    $objtype = PDF_TYPE_DICTIONARY;
                    if ($char == '<') {
                        list ($objval, $offset) = $this->getDictValue($offset, $data);
                    } else {
                        $objtype = '>>';
                        $offset += 2;
                    }
                } else {
                    // hexadecimal string object
                    $objtype = PDF_TYPE_HEX;
                    ++$offset;
                    if ($char == '<') {
                        // @CHANGE DOL Read up to the closing ">" whatever the string holds. Only hexadecimal digits and
                        // spaces were accepted: an empty string "<>" or a string written on several lines was not
                        // read at all, and the rest of the object was then parsed from the wrong place.
                        $end = strpos($data, '>', $offset);
                        if ($end === false) {
                            $end = $datalen;
                        }
                        $objval = (string) substr($data, $offset, ($end - $offset));
                        $offset = min(($end + 1), $datalen);
                    }
                }
                break;
            }
            default: {
                $frag = substr($data, $offset, 4);
                switch ($frag) {
                    case 'endo':
                        // indirect object
                        $objtype = 'endobj';
                        $offset += 6;
                        break;
                    case 'stre':
                        // Streams should always be indirect objects, and thus processed by getRawStream().
                        // If we get here, treat it as a null object as something has gone wrong.
                    case 'null':
                        // null object
                        $objtype = PDF_TYPE_NULL;
                        $offset += 4;
                        $objval = 'null';
                        break;
                    case 'true':
                        // boolean true object
                        $objtype = PDF_TYPE_BOOLEAN;
                        $offset += 4;
                        $objval = true;
                        break;
                    case 'fals':
                        // boolean false object
                        $objtype = PDF_TYPE_BOOLEAN;
                        $offset += 5;
                        $objval = false;
                        break;
                    case 'ends':
                        // end stream object
                        $objtype = 'endstream';
                        $offset += 9;
                        break;
                    default:
                        if (preg_match('/^([0-9]+)[\s]+([0-9]+)[\s]+([Robj]{1,3})/i', substr($data, $offset, 33), $matches) == 1) {
                            if ($matches[3] == 'R') {
                                // indirect object reference
                                $objtype = PDF_TYPE_OBJREF;
                                $offset += strlen($matches[0]);
                                $objval = array(intval($matches[1]), intval($matches[2]));
                            } elseif ($matches[3] == 'obj') {
                                // object start
                                $objtype = PDF_TYPE_OBJECT;
                                $objval = intval($matches[1]).'_'.intval($matches[2]);
                                $offset += strlen ($matches[0]);
                            }
                        }
                        if (($objtype === '') AND (($numlen = strspn($data, '+-.0123456789', $offset)) > 0)) {
                            // numeric object
                            $objval = substr($data, $offset, $numlen);
                            $objtype = (intval($objval) != $objval) ? PDF_TYPE_REAL : PDF_TYPE_NUMERIC;
                            $offset += $numlen;
                        }
                        unset($matches);
                        if ($objtype === '') {
                            // @CHANGE DOL Not something we know: step over it. The offset was not moved, so the
                            // callers were reading the same bytes again and again until the memory was exhausted.
                            $offset += max(1, strcspn($data, "\x00\x09\x0a\x0c\x0d\x20\x28\x29\x3c\x3e\x5b\x5d\x7b\x7d\x2f\x25", $offset));
                        }
                        break;
                }
                break;
            }
        }
        $obj = array();
        $obj[] = $objtype;
        if ($objtype === PDF_TYPE_OBJREF && is_array($objval)) {
            foreach ($objval as $val) {
                $obj[] = $val;
            }
        } else {
            $obj[] = $objval;
        }
        return array($obj, $offset);
    }

    /**
     * Get the content of a dictionary
     * @CHANGE DOL Rewritten. The text of the dictionary was first cut out by counting the "<<" and ">>", without looking
     * at what they belong to: a hexadecimal string closed just before the end of the dictionary ("/Panose <0105>>>", as
     * written by FPDI or xdvipdfmx) ended the dictionary one character too early, and the parsing of what was cut out
     * then never ended. The entries are now read one after the other up to the closing ">>".
     *
     * @param $offset (int) Offset of the "<<" opening the dictionary.
     * @param $data (string) Data to read.
     * @return array containing the entries of the dictionary and the offset to the next object
     */
    private function getDictValue($offset, &$data) {
        $objval = array();
        $offset += 2; // "<<"
        // getRawObject() always moves forward or says 'eof', so this loop ends
        while (true) {
            list($key, $offset) = $this->getRawObject($offset, $data);
            if (($key[0] === '>>') OR ($key[0] === 'eof')) {
                break;
            }
            if ($key[0] !== PDF_TYPE_TOKEN) {
                // a key is a name: ignore anything else
                continue;
            }
            list($element, $offset) = $this->getRawObject($offset, $data);
            if (($element[0] === '>>') OR ($element[0] === 'eof')) {
                // key without value
                $objval['/'.$key[1]] = array(PDF_TYPE_NULL, 'null');
                break;
            }
            $objval['/'.$key[1]] = $element;
        }

        return array($objval, $offset);
    }

    /**
     * Get content of indirect object.
     * @param $obj_ref (string) Object number and generation number separated by underscore character.
     * @param $offset (int) Object offset.
     * @param $decoding (boolean) If true decode streams.
     * @return array containing object data.
     * @protected
     * @since 1.0.000 (2011-05-24)
     */
    protected function getIndirectObject($obj_ref, $offset=0, $decoding=true) {
        $obj = is_string($obj_ref) ? explode('_', $obj_ref) : false;
        if (($obj === false) OR (count($obj) != 2)) {
            $this->Error('Invalid object reference: '.(is_scalar($obj_ref) ? $obj_ref : gettype($obj_ref)));
            return;
        }

        // @CHANGE DOL Accept any white space between the object number, the generation number and the keyword
        $headerlen = $this->matchObjectHeader($obj[0], $obj[1], $offset);
        if ($headerlen === false) {
            // an indirect reference to an undefined object shall be considered a reference to the null object
            return array('null', 'null', $offset);
        }
        // starting position of object content
        $offset += $headerlen;
        $pdflen = strlen($this->pdfdata);
        // get array of object content
        $objdata = array();
        $i = 0; // object main index
        do {
            // @CHANGE DOL A dictionary is the dictionary of a stream if it is followed by the keyword "stream", not if
            // it has a /Length entry: other dictionaries have one too (the encryption dictionary for example).
            if (($i > 0) AND (isset($objdata[($i - 1)][0])) AND ($objdata[($i - 1)][0] === PDF_TYPE_DICTIONARY) AND $this->isStreamKeyword($offset)) {
                list($element, $offset) = $this->getRawStream($offset, $objdata[($i - 1)][1]);
            } else {
                // get element
                list($element, $offset) = $this->getRawObject($offset);
            }
            // decode stream using stream's dictionary information
            if ($decoding AND ($element[0] === PDF_TYPE_STREAM) AND (isset($objdata[($i - 1)][0])) AND ($objdata[($i - 1)][0] === PDF_TYPE_DICTIONARY)) {
                $element[3] = $this->decodeStream($objdata[($i - 1)][1], $element[1]);
            }
            $objdata[$i] = $element;
            ++$i;
            // @CHANGE DOL Stop at the end of the data too
        } while (($element[0] !== 'endobj') AND ($element[0] !== 'eof') AND ($offset < $pdflen));
        // remove closing delimiter
        if (($element[0] === 'endobj') OR ($element[0] === 'eof')) {
            array_pop($objdata);
        }
        // return raw object content
        return $objdata;
    }

    /**
     * Tell if the keyword "stream" is the next thing to read.
     * @CHANGE DOL New method.
     *
     * @param $offset (int) Offset in the document
     * @return bool
     */
    private function isStreamKeyword($offset) {
        $pdflen = strlen($this->pdfdata);
        if ($offset >= $pdflen) {
            return false;
        }
        $offset += strspn($this->pdfdata, "\x00\x09\x0a\x0c\x0d\x20", $offset);
        return (($offset + 6) <= $pdflen && substr_compare($this->pdfdata, 'stream', $offset, 6) === 0);
    }

    /**
     * Check that an object starts at a given offset, and give the length of its header ("12 0 obj").
     * @CHANGE DOL New method. The header was compared to the string "$num $gen obj": the objects of a file that separates
     * them with something else than one space ("12         0 obj" from HP Exstream, or a line break) were never found.
     *
     * @param $num (int) Object number
     * @param $gen (int) Generation number
     * @param $offset (int) Offset in the document
     * @return int|false Length of the header, false if this object does not start at this offset
     */
    private function matchObjectHeader($num, $gen, $offset) {
        if (!is_numeric($offset) || $offset < 0 || $offset >= strlen($this->pdfdata)) {
            // with PHP 8, the string functions throw a ValueError on an offset out of the data
            return false;
        }
        if (preg_match('/\G'.intval($num).'[\s]+'.intval($gen).'[\s]+obj/', $this->pdfdata, $matches, 0, (int) $offset) == 1) {
            return strlen($matches[0]);
        }
        return false;
    }

    /**
     * Get the content of object, resolving indect object reference if necessary.
     * @param $obj (string) Object value.
     * @return array containing object data.
     * @public
     * @since 1.0.000 (2011-06-26)
     */
    public function getObjectVal($obj) {
        if ($obj[0] == PDF_TYPE_OBJREF) {
            if (strpos($obj[1], '_') !== false) {
                $key = explode('_', $obj[1]);
            } else {
                $key = array($obj[1], $obj[2]);
            }

            $ret = array(0=>PDF_TYPE_OBJECT, 'obj'=>$key[0], 'gen'=>$key[1]);

            // reference to indirect object
            $object = null;
            if (isset($this->objects[$key[0]][$key[1]])) {
                // this object has been already parsed
                $object = $this->objects[$key[0]][$key[1]];
            } elseif (($offset = $this->findObjectOffset($key)) !== false) {
                // parse new object
                $this->objects[$key[0]][$key[1]] = $this->getIndirectObject($key[0].'_'.$key[1], $offset, false);
                $object = $this->objects[$key[0]][$key[1]];
            } elseif (($key[1] == 0) && isset($this->objstreamobjs[$key[0]])) {
                // Object is in an object stream
                $streaminfo = $this->objstreamobjs[$key[0]];
                $objs = $streaminfo[0];
                if (!isset($this->objstreams[$objs[0]][$objs[1]])) {
                    // Fetch and decode object stream
                    $offset = $this->findObjectOffset($objs);;
                    $objstream = $this->getObjectVal(array(PDF_TYPE_OBJREF, $objs[0], $objs[1]));
                    $decoded = $this->decodeStream($objstream[1][1], $objstream[2][1]);
                    $this->objstreams[$objs[0]][$objs[1]] = $decoded[0]; // Store just the data, in case we need more from this objstream
                    // Free memory
                    unset($objstream);
                    unset($decoded);
                }
                $this->objects[$key[0]][$key[1]] = $this->getRawObject($streaminfo[1], $this->objstreams[$objs[0]][$objs[1]]);
                $object = $this->objects[$key[0]][$key[1]];
            }
            if (!is_null($object)) {
                $ret[1] = $object[0];
                if (isset($object[1][0]) && $object[1][0] == PDF_TYPE_STREAM) {
                    $ret[0] = PDF_TYPE_STREAM;
                    $ret[2] = $object[1];
                }
                return $ret;
            }
        }
        return $obj;
    }

    /**
     * Extract object stream to find out what it contains.
     *
     */
    function extractObjectStream($key) {
        $objref = array(PDF_TYPE_OBJREF, $key[0], $key[1]);
        $obj = $this->getObjectVal($objref);
        if ($obj[0] !== PDF_TYPE_STREAM || !isset($obj[1][1]['/First'][1])) {
            // Not a valid object stream dictionary - skip it.
            return;
        }
        $stream = $this->decodeStream($obj[1][1], $obj[2][1]);// Decode object stream, as we need the first bit
        $first = intval($obj[1][1]['/First'][1]);
        // @CHANGE DOL Get list of object / offset pairs. They may be separated by any number of white space chars, the
        // split on each single one gave empty values (then a TypeError) with a CRLF or two spaces.
        $ints = preg_split('/[\s]+/', trim(substr($stream[0], 0, $first)));
        for ($j=1; $j<count($ints); $j+=2) {
            if (is_numeric($ints[$j-1]) && is_numeric($ints[$j])) {
                $this->objstreamobjs[(int) $ints[$j-1]] = array($key, ((int) $ints[$j]) + $first);
            }
        }

        // Free memory - we may not need this at all.
        unset($obj);
        unset($stream);
    }

    /**
     * Find all object offsets.  Saves having to scour the file multiple times.
     * @CHANGE DOL The object streams found are returned instead of being extracted on the fly: they can only be read
     * once all the objects are located (their /Length may be an indirect object declared after them).
     * @return array List of the object streams (object number, generation number)
     * @private
     */
    private function findObjectOffsets() {
        $this->objoffsets = array();
        $objstreams = array();
        if (preg_match_all('/(*ANYCRLF)(?:^|endobj)[\s]*([0-9]+)[\s]+([0-9]+)[\s]+obj/im', $this->pdfdata, $matches, PREG_OFFSET_CAPTURE) >= 1) {
            $laststreamend = 0;
            foreach($matches[0] as $i => $match) {
                $offset = $matches[1][$i][1];
                if ($offset < $laststreamend) {
                    // Contained within another stream, skip it.
                    continue;
                }
                $num = intval($matches[1][$i][0]);
                $gen = intval($matches[2][$i][0]);
                // @CHANGE DOL the key is always "num gen obj", whatever separates them in the file
                $this->objoffsets[$num.' '.$gen.' obj'] = $offset;
                $dictoffset = $match[1] + strlen($match[0]);
                $dictfrag = substr($this->pdfdata, $dictoffset, 512);
                if (preg_match('~^\s*<<[^>]+/Length\s+(\d+)(?![\d]|\s+\d+\s+R)~', $dictfrag, $lengthmatch) == 1) {
                    // @CHANGE DOL the end of the stream is an offset in the file: the lengths were added together
                    $laststreamend = $dictoffset + intval($lengthmatch[1]);
                }
                // keep only what is before the data of the stream, or before the next object
                $dictparts = preg_split('/stream|endobj/', $dictfrag, 2);
                if (preg_match('~^\s*<<.*/Type\s*/ObjStm~s', $dictparts[0]) == 1) {
                    $objstreams[] = array($num, $gen);
                }
            }
        }
        unset($lengthmatch);
        unset($dictfrag);
        unset($matches);
        return $objstreams;
    }

    /**
     * Get offset of an object.  Checks xref first, then offsets found by scouring the file.
     * @param $key (array) Object key to find (obj, gen).
     * @return int Offset of the object in $this->pdfdata.
     * @private
     */
    private function findObjectOffset($key) {
        $objref = intval($key[0]).' '.intval($key[1]).' obj';
        if (isset($this->xref['xref'][$key[0]][$key[1]])) {
            $offset = $this->xref['xref'][$key[0]][$key[1]];
            if ($this->matchObjectHeader($key[0], $key[1], $offset) !== false) {
                // Offset is in xref table and matches actual position in file
                //echo "Offset in XREF is correct, returning<br>";
                return $offset;
            }
        }
        if (array_key_exists($objref, $this->objoffsets)) {
            //echo "Offset found in internal reftable<br>";
            return $this->objoffsets[$objref];
        }
        return false;
    }

    /**
     * Decode the specified stream.
     * @param $sdic (array) Stream's dictionary array.
     * @param $stream (string) Stream to decode.
     * @return array containing decoded stream data and remaining filters.
     * @protected
     * @since 1.0.000 (2011-06-22)
     */
    protected function decodeStream($sdic, $stream) {
        // get stream lenght and filters
        $slength = strlen($stream);
        if ($slength <= 0) {
            return array('', array());
        }
        // @CHANGE DOL The filters were only looked for inside a test that was true for a name only: a stream with its
        // filter in an array ("/Filter [/FlateDecode]") was not decoded.
        $filters = array();
        if (isset($sdic['/Filter'])) {
            $v = $sdic['/Filter'];
            if ($v[0] == PDF_TYPE_OBJREF) {
                $v = $this->getObjectVal($v);
                $v = (isset($v[1]) && is_array($v[1])) ? $v[1] : array(PDF_TYPE_NULL, 'null');
            }
            if ($v[0] == PDF_TYPE_TOKEN) {
                // single filter
                $filters[] = $v[1];
            } elseif ($v[0] == PDF_TYPE_ARRAY) {
                // array of filters
                foreach ($v[1] as $flt) {
                    if ($flt[0] == PDF_TYPE_TOKEN) {
                        $filters[] = $flt[1];
                    }
                }
            }
        }
        // decode the stream
        $remaining_filters = array();
        foreach ($filters as $filter) {
            if (in_array($filter, $this->FilterDecoders->getAvailableFilters())) {
                $stream = $this->decodeFilter($filter, $stream);
            } else {
                // add missing filter to array
                $remaining_filters[] = $filter;
            }
        }
        return array($stream, $remaining_filters);
    }

    /**
     * Decode data with a filter.
     * @CHANGE DOL New method. A FlateDecode stream that has no end marker (the writer did not flush it, or the declared
     * length is too short) is refused by gzuncompress() but opens in every PDF reader: decode what is there.
     *
     * @param $filter (string) Name of the filter
     * @param $stream (string) Data to decode
     * @return string Decoded data
     */
    private function decodeFilter($filter, $stream) {
        if ($filter == 'FlateDecode') {
            if ($stream === '') {
                return '';
            }
            $decoded = @gzuncompress($stream);
            if ($decoded === false) {
                if (function_exists('inflate_init')) {
                    $context = @inflate_init(ZLIB_ENCODING_DEFLATE);
                    if ($context !== false) {
                        $decoded = @inflate_add($context, $stream, ZLIB_SYNC_FLUSH);
                    }
                }
                if ($decoded === false) {
                    $this->Error('decodeFilterFlateDecode: invalid code');
                }
            }
            return $decoded;
        }
        return $this->FilterDecoders->decodeFilter($filter, $stream);
    }


    /**
     * Set pageno
     *
     * @param int $pageno Pagenumber to use
     */
    public function setPageno($pageno) {
        $pageno = ((int) $pageno) - 1;

        if ($pageno < 0 || $pageno >= $this->getPageCount()) {
            $this->error("Pagenumber is wrong! (Requested $pageno, max ".$this->getPageCount().")");
        }

        $this->pageno = $pageno;
    }

    /**
     * Get page-resources from current page
     *
     * @return array
     */
    public function getPageResources() {
        return $this->_getPageResources($this->pages[$this->pageno]);
    }

    /**
     * Get page-resources from /Page
     *
     * @param array $obj Array of pdf-data
     */
    private function _getPageResources ($obj) { // $obj = /Page
        $obj = $this->getObjectVal($obj);

        // If the current object has a resources
        // dictionary associated with it, we use
        // it. Otherwise, we move back to its
        // parent object.
        if (isset ($obj[1][1]['/Resources'])) {
            $res = $obj[1][1]['/Resources'];
            if ($res[0] == PDF_TYPE_OBJECT)
                return $res[1];
            return $res;
        } else {
            if (!isset ($obj[1][1]['/Parent'])) {
                return false;
            } else {
                $res = $this->_getPageResources($obj[1][1]['/Parent']);
                if (is_array($res) && $res[0] == PDF_TYPE_OBJECT) // @CHANGE DOL false when there is no resources
                    return $res[1];
                return $res;
            }
        }
    }

    /**
     * Get annotations from current page
     *
     * @return array
     */
    public function getPageAnnotations() {
        return $this->_getPageAnnotations($this->pages[$this->pageno]);
    }

    /**
     * Get annotations from /Page
     *
     * @param array $obj Array of pdf-data
     */
    private function _getPageAnnotations ($obj) { // $obj = /Page
        $obj = $this->getObjectVal($obj);

        // If the current object has an annotations
        // dictionary associated with it, we use
        // it. Otherwise, we move back to its
        // parent object.
        if (isset ($obj[1][1]['/Annots'])) {
            $annots = $obj[1][1]['/Annots'];
        } else {
            if (!isset ($obj[1][1]['/Parent'])) {
                return false;
            } else {
                $annots = $this->_getPageAnnotations($obj[1][1]['/Parent']);
            }
        }

        if (!is_array($annots)) {
            // @CHANGE DOL no annotation on this page
            return false;
        }
        if ($annots[0] == PDF_TYPE_OBJREF)
            return $this->getObjectVal($annots);
        return $annots;
    }


    /**
     * Get content of current page
     *
     * If more /Contents is an array, the streams are concated
     *
     * @return string
     */
    public function getContent() {
        $buffer = '';

        if (isset($this->pages[$this->pageno][1][1]['/Contents'])) {
            $contents = $this->_getPageContent($this->pages[$this->pageno][1][1]['/Contents']);
            foreach($contents AS $tmp_content) {
                $buffer .= $this->_rebuildContentStream($tmp_content) . ' ';
            }
        }

        return $buffer;
    }


    /**
     * Resolve all content-objects
     *
     * @param array $content_ref
     * @return array
     */
    private function _getPageContent($content_ref) {
        $contents = array();

        if ($content_ref[0] == PDF_TYPE_OBJREF) {
            $content = $this->getObjectVal($content_ref);
            if ($content[1][0] == PDF_TYPE_ARRAY) {
                $contents = $this->_getPageContent($content[1]);
            } else {
                $contents[] = $content;
            }
        } elseif ($content_ref[0] == PDF_TYPE_ARRAY) {
            foreach ($content_ref[1] AS $tmp_content_ref) {
                $contents = array_merge($contents,$this->_getPageContent($tmp_content_ref));
            }
        }

        return $contents;
    }


    /**
     * Rebuild content-streams
     *
     * @param array $obj
     * @return string
     */
    private function _rebuildContentStream($obj) {
        $filters = array();

        if (isset($obj[1][1]['/Filter'])) {
            $_filter = $obj[1][1]['/Filter'];

            if ($_filter[0] == PDF_TYPE_OBJREF) {
                $tmpFilter = $this->getObjectVal($_filter);
                $_filter = $tmpFilter[1];
            }

            if ($_filter[0] == PDF_TYPE_TOKEN) {
                $filters[] = $_filter;
            } elseif ($_filter[0] == PDF_TYPE_ARRAY) {
                $filters = $_filter[1];
            }
        }

        if (!isset($obj[2][1])) {
            // @CHANGE DOL not a stream (object not found)
            return '';
        }
        $stream = $obj[2][1];

        foreach ($filters AS $_filter) {
            $stream = $this->decodeFilter($_filter[1], $stream); // @CHANGE DOL
        }

        return $stream;
    }


    /**
     * Get a Box from a page
     * Arrayformat is same as used by fpdf_tpl
     *
     * @param array $page a /Page
     * @param string $box_index Type of Box @see $availableBoxes
     * @param float Scale factor from user space units to points
     * @return array
     */
    public function getPageBox($page, $box_index, $k) {
        $page = $this->getObjectVal($page);
        $box = null;
        if (isset($page[1][1][$box_index]))
            $box =& $page[1][1][$box_index];

        if (!is_null($box) && $box[0] == PDF_TYPE_OBJREF) {
            $tmp_box = $this->getObjectVal($box);
            $box = $tmp_box[1];
        }

        if (!is_null($box) && $box[0] == PDF_TYPE_ARRAY) {
            $b =& $box[1];
            return array('x' => $b[0][1] / $k,
                         'y' => $b[1][1] / $k,
                         'w' => abs($b[0][1] - $b[2][1]) / $k,
                         'h' => abs($b[1][1] - $b[3][1]) / $k,
                         'llx' => min($b[0][1], $b[2][1]) / $k,
                         'lly' => min($b[1][1], $b[3][1]) / $k,
                         'urx' => max($b[0][1], $b[2][1]) / $k,
                         'ury' => max($b[1][1], $b[3][1]) / $k,
                         );
        } elseif (!isset ($page[1][1]['/Parent'])) {
            return false;
        } else {
            return $this->getPageBox($this->getObjectVal($page[1][1]['/Parent']), $box_index, $k);
        }
    }

    /**
     * Get all page boxes by page no
     *
     * @param int The page number
     * @param float Scale factor from user space units to points
     * @return array
     */
    public function getPageBoxes($pageno, $k) {
        return $this->_getPageBoxes($this->pages[$pageno - 1], $k);
    }

    /**
     * Get all boxes from /Page
     *
     * @param array a /Page
     * @return array
     */
    private function _getPageBoxes($page, $k) {
        $boxes = array();

        foreach($this->availableBoxes AS $box) {
            if ($_box = $this->getPageBox($page, $box, $k)) {
                $boxes[$box] = $_box;
            }
        }

        return $boxes;
    }

    /**
     * Get the page rotation by pageno
     *
     * @param integer $pageno
     * @return array
     */
    public function getPageRotation($pageno) {
        return $this->_getPageRotation($this->pages[$pageno - 1]);
    }

    private function _getPageRotation($obj) { // $obj = /Page
        $obj = $this->getObjectVal($obj);
        if (isset ($obj[1][1]['/Rotate'])) {
            $res = $this->getObjectVal($obj[1][1]['/Rotate']);
    		if (isset($res[0]) && $res[0] == PDF_TYPE_OBJECT)
                return $res[1];
            return $res;
        } else {
            if (!isset ($obj[1][1]['/Parent'])) {
                return false;
            } else {
                $res = $this->_getPageRotation($obj[1][1]['/Parent']);
                if (isset($res[0]) && $res[0] == PDF_TYPE_OBJECT)
                    return $res[1];
                return $res;
            }
        }
    }

    /**
     * This method is automatically called in case of fatal error; it simply outputs the message and halts the execution.
     * @param $msg (string) The error message
     * @public
     * @since 1.0.000 (2011-05-23)
     */
    public function Error($msg) {
        // Respect K_TCPDF_THROW_EXCEPTION_ERROR like TCPDF does
        if (defined('K_TCPDF_THROW_EXCEPTION_ERROR') && K_TCPDF_THROW_EXCEPTION_ERROR) {
            throw new \Exception("TCPDI_PARSER ERROR [{$this->uniqueid}]: ".$msg);
        }
        // Default: exit program and print error
        die("<strong>TCPDI_PARSER ERROR [{$this->uniqueid}]: </strong>".$msg);
    }

} // END OF TCPDF_PARSER CLASS

//============================================================+
// END OF FILE
//============================================================+
