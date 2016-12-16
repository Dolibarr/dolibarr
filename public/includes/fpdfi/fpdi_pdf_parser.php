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

require_once('pdf_parser.php');

/**
 * Class fpdi_pdf_parser
 */
class fpdi_pdf_parser extends pdf_parser
{
    /**
     * Pages
     *
     * Index begins at 0
     *
     * @var array
     */
    protected $_pages;
    
    /**
     * Page count
     *
     * @var integer
     */
    protected $_pageCount;
    
    /**
     * Current page number
     *
     * @var integer
     */
    public $pageNo;
    
    /**
     * PDF version of imported document
     *
     * @var string
     */
    public $_pdfVersion;
    
    /**
     * Available BoxTypes
     *
     * @var array
     */
    public $availableBoxes = array('/MediaBox', '/CropBox', '/BleedBox', '/TrimBox', '/ArtBox');
        
    /**
     * The constructor.
     *
     * @param string $filename The source filename
     */
    public function __construct($filename)
    {
        parent::__construct($filename);

        // resolve Pages-Dictonary
        $pages = $this->resolveObject($this->_root[1][1]['/Pages']);

        // Read pages
        $this->_readPages($pages, $this->_pages);
        
        // count pages;
        $this->_pageCount = count($this->_pages);
    }
    
    /**
     * Get page count from source file.
     *
     * @return int
     */
    public function getPageCount()
    {
        return $this->_pageCount;
    }

    /**
     * Set the page number.
     *
     * @param int $pageNo Page number to use
     * @throws InvalidArgumentException
     */
    public function setPageNo($pageNo)
    {
        $pageNo = ((int) $pageNo) - 1;

        if ($pageNo < 0 || $pageNo >= $this->getPageCount()) {
            throw new InvalidArgumentException('Invalid page number!');
        }

        $this->pageNo = $pageNo;
    }
    
    /**
     * Get page-resources from current page
     *
     * @return array|boolean
     */
    public function getPageResources()
    {
        return $this->_getPageResources($this->_pages[$this->pageNo]);
    }
    
    /**
     * Get page-resources from a /Page dictionary.
     *
     * @param array $obj Array of pdf-data
     * @return array|boolean
     */
    protected function _getPageResources($obj)
    {
    	$obj = $this->resolveObject($obj);

        // If the current object has a resources
    	// dictionary associated with it, we use
    	// it. Otherwise, we move back to its
    	// parent object.
        if (isset($obj[1][1]['/Resources'])) {
    		$res = $this->resolveObject($obj[1][1]['/Resources']);
    		if ($res[0] == pdf_parser::TYPE_OBJECT)
                return $res[1];
            return $res;
    	}

        if (!isset($obj[1][1]['/Parent'])) {
            return false;
        }

        $res = $this->_getPageResources($obj[1][1]['/Parent']);
        if ($res[0] == pdf_parser::TYPE_OBJECT)
            return $res[1];
        return $res;
    }

    /**
     * Get content of current page.
     *
     * If /Contents is an array, the streams are concatenated
     *
     * @return string
     */
    public function getContent()
    {
        $buffer = '';
        
        if (isset($this->_pages[$this->pageNo][1][1]['/Contents'])) {
            $contents = $this->_getPageContent($this->_pages[$this->pageNo][1][1]['/Contents']);
            foreach ($contents AS $tmpContent) {
                $buffer .= $this->_unFilterStream($tmpContent) . ' ';
            }
        }
        
        return $buffer;
    }

    /**
     * Resolve all content objects.
     *
     * @param array $contentRef
     * @return array
     */
    protected function _getPageContent($contentRef)
    {
        $contents = array();
        
        if ($contentRef[0] == pdf_parser::TYPE_OBJREF) {
            $content = $this->resolveObject($contentRef);
            if ($content[1][0] == pdf_parser::TYPE_ARRAY) {
                $contents = $this->_getPageContent($content[1]);
            } else {
                $contents[] = $content;
            }
        } else if ($contentRef[0] == pdf_parser::TYPE_ARRAY) {
            foreach ($contentRef[1] AS $tmp_content_ref) {
                $contents = array_merge($contents, $this->_getPageContent($tmp_content_ref));
            }
        }

        return $contents;
    }

    /**
     * Get a boundary box from a page
     *
     * Array format is same as used by FPDF_TPL.
     *
     * @param array $page a /Page dictionary
     * @param string $boxIndex Type of box {see {@link $availableBoxes})
     * @param float Scale factor from user space units to points
     *
     * @return array|boolean
     */
    protected function _getPageBox($page, $boxIndex, $k)
    {
        $page = $this->resolveObject($page);
        $box = null;
        if (isset($page[1][1][$boxIndex])) {
            $box = $page[1][1][$boxIndex];
        }
        
        if (!is_null($box) && $box[0] == pdf_parser::TYPE_OBJREF) {
            $tmp_box = $this->resolveObject($box);
            $box = $tmp_box[1];
        }
            
        if (!is_null($box) && $box[0] == pdf_parser::TYPE_ARRAY) {
            $b = $box[1];
            return array(
                'x' => $b[0][1] / $k,
                'y' => $b[1][1] / $k,
                'w' => abs($b[0][1] - $b[2][1]) / $k,
                'h' => abs($b[1][1] - $b[3][1]) / $k,
                'llx' => min($b[0][1], $b[2][1]) / $k,
                'lly' => min($b[1][1], $b[3][1]) / $k,
                'urx' => max($b[0][1], $b[2][1]) / $k,
                'ury' => max($b[1][1], $b[3][1]) / $k,
            );
        } else if (!isset($page[1][1]['/Parent'])) {
            return false;
        } else {
            return $this->_getPageBox($this->resolveObject($page[1][1]['/Parent']), $boxIndex, $k);
        }
    }

    /**
     * Get all page boundary boxes by page number
     * 
     * @param int $pageNo The page number
     * @param float $k Scale factor from user space units to points
     * @return array
     * @throws InvalidArgumentException
     */
    public function getPageBoxes($pageNo, $k)
    {
        if (!isset($this->_pages[$pageNo - 1])) {
            throw new InvalidArgumentException('Page ' . $pageNo . ' does not exists.');
        }

        return $this->_getPageBoxes($this->_pages[$pageNo - 1], $k);
    }
    
    /**
     * Get all boxes from /Page dictionary
     *
     * @param array $page A /Page dictionary
     * @param float $k Scale factor from user space units to points
     * @return array
     */
    protected function _getPageBoxes($page, $k)
    {
        $boxes = array();

        foreach($this->availableBoxes AS $box) {
            if ($_box = $this->_getPageBox($page, $box, $k)) {
                $boxes[$box] = $_box;
            }
        }

        return $boxes;
    }

    /**
     * Get the page rotation by page number
     *
     * @param integer $pageNo
     * @throws InvalidArgumentException
     * @return array
     */
    public function getPageRotation($pageNo)
    {
        if (!isset($this->_pages[$pageNo - 1])) {
            throw new InvalidArgumentException('Page ' . $pageNo . ' does not exists.');
        }

        return $this->_getPageRotation($this->_pages[$pageNo - 1]);
    }

    /**
     * Get the rotation value of a page
     *
     * @param array $obj A /Page dictionary
     * @return array|bool
     */
    protected function _getPageRotation($obj)
    {
    	$obj = $this->resolveObject($obj);
    	if (isset($obj[1][1]['/Rotate'])) {
    		$res = $this->resolveObject($obj[1][1]['/Rotate']);
    		if ($res[0] == pdf_parser::TYPE_OBJECT)
                return $res[1];
            return $res;
    	}

        if (!isset($obj[1][1]['/Parent'])) {
            return false;
        }

        $res = $this->_getPageRotation($obj[1][1]['/Parent']);
        if ($res[0] == pdf_parser::TYPE_OBJECT)
            return $res[1];

        return $res;
    }

    /**
     * Read all pages
     *
     * @param array $pages /Pages dictionary
     * @param array $result The result array
     * @throws Exception
     */
    protected function _readPages(&$pages, &$result)
    {
        // Get the kids dictionary
    	$_kids = $this->resolveObject($pages[1][1]['/Kids']);

        if (!is_array($_kids)) {
            throw new Exception('Cannot find /Kids in current /Page-Dictionary');
        }

        if ($_kids[0] === self::TYPE_OBJECT) {
            $_kids =  $_kids[1];
        }

        $kids = $_kids[1];

        foreach ($kids as $v) {
    		$pg = $this->resolveObject($v);
            if ($pg[1][1]['/Type'][1] === '/Pages') {
                // If one of the kids is an embedded
    			// /Pages array, resolve it as well.
                $this->_readPages($pg, $result);
    		} else {
    			$result[] = $pg;
    		}
    	}
    }
}