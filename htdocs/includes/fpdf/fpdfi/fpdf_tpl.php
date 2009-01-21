<?php
//
//  FPDF_TPL - Version 1.1.2
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

// DOLCHANGE
require_once(FPDF_PATH."fpdf.php");
//require_once('E:/Mes Developpements/dolibarr/htdocs/includes/fpdf/fpdf/fpdf.php');
//require_once('C:/temp/16/fpdf.php');

class FPDF_TPL extends FPDF {
    /**
     * Array of Tpl-Data
     * @var array
     */
    var $tpls = array();

    /**
     * Current Template-ID
     * @var int
     */
    var $tpl = 0;

    /**
     * "In Template"-Flag
     * @var boolean
     */
    var $_intpl = false;

    /**
     * Nameprefix of Templates used in Resources-Dictonary
     * @var string A String defining the Prefix used as Template-Object-Names. Have to beginn with an /
     */
    var $tplprefix = "/TPL";

    /**
     * Resources used By Templates and Pages
     * @var array
     */
    var $_res = array();

    /**
     * Start a Template
     *
     * This method starts a template. You can give own coordinates to build an own sized
     * Template. Pay attention, that the margins are adapted to the new templatesize.
     * If you want to write outside the template, for example to build a clipped Template,
     * you have to set the Margins and "Cursor"-Position manual after beginTemplate-Call.
     *
     * If no parameter is given, the template uses the current page-size.
     * The Method returns an ID of the current Template. This ID is used later for using this template.
     * Warning: A created Template is used in PDF at all events. Still if you don't use it after creation!
     *
     * @param int $x The x-coordinate given in user-unit
     * @param int $y The y-coordinate given in user-unit
     * @param int $w The width given in user-unit
     * @param int $h The height given in user-unit
     * @return int The ID of new created Template
     */
    function beginTemplate($x=null, $y=null, $w=null, $h=null) {
        if ($this->page <= 0)
            $this->error("You have to add a page to fpdf first!");

        if ($x == null)
            $x = 0;
        if ($y == null)
            $y = 0;
        if ($w == null)
            $w = $this->w;
        if ($h == null)
            $h = $this->h;

        // Save settings
        $this->tpl++;
        $tpl =& $this->tpls[$this->tpl];
        $tpl = array(
            'o_x' => $this->x,
            'o_y' => $this->y,
            'o_AutoPageBreak' => $this->AutoPageBreak,
            'o_bMargin' => $this->bMargin,
            'o_tMargin' => $this->tMargin,
            'o_lMargin' => $this->lMargin,
            'o_rMargin' => $this->rMargin,
            'o_h' => $this->h,
            'o_w' => $this->w,
            'buffer' => '',
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h
        );

        $this->SetAutoPageBreak(false);

        // Define own high and width to calculate possitions correct
        $this->h = $h;
        $this->w = $w;

        $this->_intpl = true;
        $this->SetXY($x+$this->lMargin, $y+$this->tMargin);
        $this->SetRightMargin($this->w-$w+$this->rMargin);

        return $this->tpl;
    }

    /**
     * End Template
     *
     * This method ends a template and reset initiated variables on beginTemplate.
     *
     * @return mixed If a template is opened, the ID is returned. If not a false is returned.
     */
    function endTemplate() {
        if ($this->_intpl) {
            $this->_intpl = false;
            $tpl =& $this->tpls[$this->tpl];
            $this->SetXY($tpl['o_x'], $tpl['o_y']);
            $this->tMargin = $tpl['o_tMargin'];
            $this->lMargin = $tpl['o_lMargin'];
            $this->rMargin = $tpl['o_rMargin'];
            $this->h = $tpl['o_h'];
            $this->w = $tpl['o_w'];
            $this->SetAutoPageBreak($tpl['o_AutoPageBreak'], $tpl['o_bMargin']);

            return $this->tpl;
        } else {
            return false;
        }
    }

    /**
     * Use a Template in current Page or other Template
     *
     * You can use a template in a page or in another template.
     * You can give the used template a new size like you use the Image()-method.
     * All parameters are optional. The width or height is calculated automaticaly
     * if one is given. If no parameter is given the origin size as defined in
     * beginTemplate() is used.
     * The calculated or used width and height are returned as an array.
     *
     * @param int $tplidx A valid template-Id
     * @param int $_x The x-position
     * @param int $_y The y-position
     * @param int $_w The new width of the template
     * @param int $_h The new height of the template
     * @retrun array The height and width of the template
     */
    function useTemplate($tplidx, $_x=null, $_y=null, $_w=0, $_h=0) {
        if ($this->page <= 0)
            $this->error("You have to add a page to fpdf first!");

        if (!isset($this->tpls[$tplidx]))
            $this->error("Template does not exist!");

        if ($this->_intpl) {
            $this->_res['tpl'][$this->tpl]['tpls'][$tplidx] =& $this->tpls[$tplidx];
        }

        $tpl =& $this->tpls[$tplidx];
        $x = $tpl['x'];
        $y = $tpl['y'];
        $w = $tpl['w'];
        $h = $tpl['h'];

        if ($_x == null)
            $_x = $x;
        if ($_y == null)
            $_y = $y;
        $wh = $this->getTemplateSize($tplidx, $_w, $_h);
        $_w = $wh['w'];
        $_h = $wh['h'];

		$this->_out(sprintf("q %.4F 0 0 %.4F %.2F %.2F cm", ($_w/$w), ($_h/$h), $_x*$this->k, ($this->h-($_y+$_h))*$this->k)); // Translate
        $this->_out($this->tplprefix.$tplidx." Do Q");

        return array("w" => $_w, "h" => $_h);
    }

    /**
     * Get The calculated Size of a Template
     *
     * If one size is given, this method calculates the other one.
     *
     * @param int $tplidx A valid template-Id
     * @param int $_w The width of the template
     * @param int $_h The height of the template
     * @return array The height and width of the template
     */
    function getTemplateSize($tplidx, $_w=0, $_h=0) {
        if (!$this->tpls[$tplidx])
            return false;

        $tpl =& $this->tpls[$tplidx];
        $w = $tpl['w'];
        $h = $tpl['h'];

        if ($_w == 0 and $_h == 0) {
            $_w = $w;
            $_h = $h;
        }

    	if($_w==0)
    		$_w = $_h*$w/$h;
    	if($_h==0)
    		$_h = $_w*$h/$w;

        return array("w" => $_w, "h" => $_h);
    }

    /**
     * See FPDF-Documentation ;-)
     */
    function SetFont($family, $style='', $size=0) {
        /**
         * force the resetting of font changes in a template
         */
        if ($this->_intpl)
            $this->FontFamily = '';

        parent::SetFont($family, $style, $size);

        $fontkey = $this->FontFamily.$this->FontStyle;

        if ($this->_intpl) {
            $this->_res['tpl'][$this->tpl]['fonts'][$fontkey] =& $this->fonts[$fontkey];
        } else {
            $this->_res['page'][$this->page]['fonts'][$fontkey] =& $this->fonts[$fontkey];
        }
    }

    /**
     * See FPDF/TCPDF-Documentation ;-)
     */
    function Image($file, $x, $y, $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300) {
        if (!is_subclass_of($this, 'TCPDF') && func_num_args() > 7) {
            $this->Error('More than 7 arguments for the Image method are only available in TCPDF.');
        }

        parent::Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi);
        if ($this->_intpl) {
            $this->_res['tpl'][$this->tpl]['images'][$file] =& $this->images[$file];
        } else {
            $this->_res['page'][$this->page]['images'][$file] =& $this->images[$file];
        }
    }

    /**
     * See FPDF-Documentation ;-)
     *
     * AddPage is not available when you're "in" a template.
     */
    function AddPage($orientation='', $format='') {
        if ($this->_intpl)
            $this->Error('Adding pages in templates isn\'t possible!');
        parent::AddPage($orientation, $format);
    }

    /**
     * Preserve adding Links in Templates ...won't work
     */
    function Link($x, $y, $w, $h, $link) {
        if ($this->_intpl)
            $this->Error('Using links in templates aren\'t possible!');
        parent::Link($x, $y, $w, $h, $link);
    }

    function AddLink() {
        if ($this->_intpl)
            $this->Error('Adding links in templates aren\'t possible!');
        return parent::AddLink();
    }

    function SetLink($link, $y=0, $page=-1) {
        if ($this->_intpl)
            $this->Error('Setting links in templates aren\'t possible!');
        parent::SetLink($link, $y, $page);
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
            $this->_out(sprintf('/BBox [%.2F %.2F %.2F %.2F]',$tpl['x']*$this->k, ($tpl['h']-$tpl['y'])*$this->k, $tpl['w']*$this->k, ($tpl['h']-$tpl['y']-$tpl['h'])*$this->k));
            $this->_out('/Resources ');

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

        	$this->_out('/Length '.strlen($p).' >>');
    		$this->_putstream($p);
    		$this->_out('endobj');
        }
    }

    /**
     * Private Method
     */
    function _putresources() {
        if (!is_subclass_of($this, 'TCPDF')) {
            $this->_putfonts();
        	$this->_putimages();
        	$this->_putformxobjects();
            //Resource dictionary
        	$this->offsets[2]=strlen($this->buffer);
        	$this->_out('2 0 obj');
        	$this->_out('<<');
        	$this->_putresourcedict();
        	$this->_out('>>');
    	   $this->_out('endobj');
        } else {
        	$this->_putextgstates();
    		$this->_putocg();
    		$this->_putfonts();
    		$this->_putimages();
    	  	$this->_putshaders();
			$this->_putformxobjects();
            //Resource dictionary
    		$this->offsets[2]=strlen($this->buffer);
    		$this->_out('2 0 obj');
    		$this->_out('<<');
    		$this->_putresourcedict();
    		$this->_out('>>');
    		$this->_out('endobj');
    		$this->_putjavascript();
    		$this->_putbookmarks();
    		// encryption
    		if ($this->encrypted) {
    			$this->_newobj();
    			$this->enc_obj_id = $this->n;
    			$this->_out('<<');
    			$this->_putencryption();
    			$this->_out('>>');
    			$this->_out('endobj');
    		}
        }
    }

    function _putxobjectdict() {
        parent::_putxobjectdict();

        if (count($this->tpls)) {
            foreach($this->tpls as $tplidx => $tpl) {
                $this->_out($this->tplprefix.$tplidx.' '.$tpl['n'].' 0 R');
            }
        }
    }

    /**
     * Private Method
     */
    function _out($s) {
        if ($this->state==2 && $this->_intpl) {
            $this->tpls[$this->tpl]['buffer'] .= $s."\n";
        } else {
            parent::_out($s);
        }
    }
}
