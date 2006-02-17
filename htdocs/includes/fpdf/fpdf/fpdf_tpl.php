<?php
//
//  fpdf_tpl - Version 1.0.2
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

require_once("fpdf.php");

class fpdf_tpl extends fpdf {
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
    var $intpl = false;
    
    /**
     * Nameprefix of Templates used in Resources-Dictonary
     * @var string A String defining the Prefix used as Template-Object-Names. Have to beginn with an /
     */
    var $tplprefix = "/TPL";

    /**
     * Nameprefix of Fonts used in Resources-Dictonary
     * (not realy needed, but for future versions with import-function needed)
     * @var string
     */
    var $fontprefix = "/F";

    /**
     * Resources used By Templates and Pages
     * @var array
     */
    var $res = array();
    
    /**
     * Constructor
     * See FPDF-Documentation
     * @param string $orientation
     * @param string $unit
     * @param mixed $format
     */
    function fpdf_tpl($orientation='P',$unit='mm',$format='A4') {
        parent::fpdf($orientation,$unit,$format);
    }
    
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
    function beginTemplate($x=null,$y=null,$w=null,$h=null) {
        if ($this->page <= 0)
            $this->error("You have to add a page to fpdf first!");

        // Save settings
        $this->tpl++;
        $this->tpls[$this->tpl]['o_x'] = $this->x;
        $this->tpls[$this->tpl]['o_y'] = $this->y;
        $this->tpls[$this->tpl]['o_AutoPageBreak'] = $this->AutoPageBreak;
        $this->tpls[$this->tpl]['o_bMargin'] = $this->bMargin;
        $this->tpls[$this->tpl]['o_tMargin'] = $this->tMargin;
        $this->tpls[$this->tpl]['o_lMargin'] = $this->lMargin;
        $this->tpls[$this->tpl]['o_rMargin'] = $this->rMargin;
        $this->tpls[$this->tpl]['o_h'] = $this->h;
        $this->tpls[$this->tpl]['o_w'] = $this->w;

        $this->SetAutoPageBreak(false);
        
        if ($x == null)
            $x = 0;
        if ($y == null)
            $y = 0;
        if ($w == null)
            $w = $this->w;
        if ($h == null)
            $h = $this->h;

        // Define own high and width to calculate possitions correct
        $this->h = $h;
        $this->w = $w;

        $this->tpls[$this->tpl]['buffer'] = "";
        $this->tpls[$this->tpl]['x'] = $x;
        $this->tpls[$this->tpl]['y'] = $y;
        $this->tpls[$this->tpl]['w'] = $w;
        $this->tpls[$this->tpl]['h'] = $h;

        $this->intpl = true;
        $this->SetXY($x+$this->lMargin,$y+$this->tMargin);
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
        if ($this->intpl) {
            $this->intpl = false;
            $this->SetAutoPageBreak($this->tpls[$this->tpl]['o_AutoPageBreak'],$this->tpls[$this->tpl]['o_bMargin']);
            $this->SetXY($this->tpls[$this->tpl]['o_x'],$this->tpls[$this->tpl]['o_y']);
            $this->tMargin = $this->tpls[$this->tpl]['o_tMargin'];
            $this->lMargin = $this->tpls[$this->tpl]['o_lMargin'];
            $this->rMargin = $this->tpls[$this->tpl]['o_rMargin'];
            $this->h = $this->tpls[$this->tpl]['o_h'];
            $this->w = $this->tpls[$this->tpl]['o_w'];
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

        if (!$this->tpls[$tplidx])
            $this->error("Template does not exist!");
            
        if ($this->intpl) {
            $this->res['tpl'][$this->tpl]['tpls'][$tplidx] =& $this->tpls[$tplidx];
        }
        extract($this->tpls[$tplidx]);

        if ($_x == null)
            $_x = $x;
        if ($_y == null)
            $_y = $y;
        $wh = $this->getTemplateSize($tplidx,$_w,$_h);
        $_w = $wh['w'];
        $_h = $wh['h'];

        $this->_out(sprintf("q %.4f 0 0 %.4f %.2f %.2f cm", ($_w/$w), ($_h/$h), $_x*$this->k, ($this->h-($_y+$_h))*$this->k)); // Translate
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

        extract($this->tpls[$tplidx]);
        if ($_w == 0 and $_h == 0) {
            $_w = $w;
            $_h = $h;
        }

    	if($_w==0)
    		$_w=$_h*$w/$h;
    	if($_h==0)
    		$_h=$_w*$h/$w;
    		
        return array("w" => $_w, "h" => $_h);
    }
    
    /**
     * See FPDF-Documentation ;-)
     */
    function SetFont($family,$style='',$size=0) {
        //Select a font; size given in points
    	global $fpdf_charwidths;

    	$family=strtolower($family);
    	if($family=='')
    		$family=$this->FontFamily;
    	if($family=='arial')
    		$family='helvetica';
    	elseif($family=='symbol' or $family=='zapfdingbats')
    		$style='';
    	$style=strtoupper($style);
    	if(is_int(strpos($style,'U')))
    	{
    		$this->underline=true;
    		$style=str_replace('U','',$style);
    	}
    	else
    		$this->underline=false;
    	if($style=='IB')
    		$style='BI';
    	if($size==0)
    		$size=$this->FontSizePt;
    	//Test if font is already selected
    	if($this->FontFamily==$family and $this->FontStyle==$style and $this->FontSizePt==$size and !$this->intpl)
            return;
    	//Test if used for the first time
    	$fontkey=$family.$style;
    	if(!isset($this->fonts[$fontkey]))
    	{
    		//Check if one of the standard fonts
    		if(isset($this->CoreFonts[$fontkey]))
    		{
    			if(!isset($fpdf_charwidths[$fontkey]))
    			{
    				//Load metric file
    				$file=$family;
    				if($family=='times' or $family=='helvetica')
    					$file.=strtolower($style);
    				$file.='.php';
    				if(defined('FPDF_FONTPATH'))
    					$file=FPDF_FONTPATH.$file;
    				include($file);
    				if(!isset($fpdf_charwidths[$fontkey]))
    					$this->Error('Could not include font metric file');
    			}
                $i = $this->findNextAvailFont();
                $this->fonts[$fontkey]=array('i'=>$i,'type'=>'core','name'=>$this->CoreFonts[$fontkey],'up'=>-100,'ut'=>50,'cw'=>$fpdf_charwidths[$fontkey]);
    		}
    		else
    			$this->Error('Undefined font: '.$family.' '.$style);
    	}
    	//Select it
    	$this->FontFamily=$family;
    	$this->FontStyle=$style;
    	$this->FontSizePt=$size;
    	$this->FontSize=$size/$this->k;
    	$this->CurrentFont=&$this->fonts[$fontkey];
    	if($this->page>0)
    		$this->_out(sprintf('BT '.$this->fontprefix.'%d %.2f Tf ET',$this->CurrentFont['i'],$this->FontSizePt));


        if ($this->intpl) {
            $this->res['tpl'][$this->tpl]['fonts'][$fontkey] =& $this->fonts[$fontkey];
        } else {
            $this->res['page'][$this->page]['fonts'][$fontkey] =& $this->fonts[$fontkey];
        }
    }
    
    /**
     * Find the next available Font-No.
     *
     * @return int
     */
    function findNextAvailFont() {
        return count($this->fonts)+1;
    }

    /**
     * See FPDF-Documentation ;-)
     */
    function Image($file,$x,$y,$w=0,$h=0,$type='',$link='') {
        parent::Image($file,$x,$y,$w,$h,$type,$link);
        if ($this->intpl) {
            $this->res['tpl'][$this->tpl]['images'][$file] =& $this->images[$file];
        } else {
            $this->res['page'][$this->page]['images'][$file] =& $this->images[$file];
        }
    }
    
    /**
     * See FPDF-Documentation ;-)
     *
     * AddPage is not available when you're "in" a template.
     */
    function AddPage($orientation='') {
        if ($this->intpl)
            $this->Error('Adding pages in templates isn\'t possible!');
        parent::AddPage($orientation);
    }

    /**
     * Preserve adding Links in Templates ...won't work
     */
    function Link($x,$y,$w,$h,$link) {
        if ($this->intpl)
            $this->Error('Using links in templates aren\'t possible!');
        parent::Link($x,$y,$w,$h,$link);
    }
    
    function AddLink() {
        if ($this->intpl)
            $this->Error('Adding links in templates aren\'t possible!');
        return parent::AddLink();
    }
    
    function SetLink($link,$y=0,$page=-1) {
        if ($this->intpl)
            $this->Error('Setting links in templates aren\'t possible!');
        parent::SetLink($link,$y,$page);
    }
    
    /**
     * Private Method that writes the Resources-Objects
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
            $this->_out(sprintf('/BBox [%.2f %.2f %.2f %.2f]',$tpl['x']*$this->k, ($tpl['h']-$tpl['y'])*$this->k, $tpl['w']*$this->k, ($tpl['h']-$tpl['y']-$tpl['h'])*$this->k));       // ($this->h-$tpl['y'])*$this->k
            $this->_out('/Resources ');

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
        	
        	$this->_out('/Length '.strlen($p).' >>');
    		$this->_putstream($p);
    		$this->_out('endobj');
        }
    }
    
    /**
     * Private Method
     */
    function _putresources() {
    	$this->_putfonts();
    	$this->_putimages();
        $this->_puttemplates();
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
     * Private Method
     */
    function _out($s) {
	   //Add a line to the document
	   if ($this->state==2) {
           if (!$this->intpl)
	           $this->pages[$this->page].=$s."\n";
           else
               $this->tpls[$this->tpl]['buffer'] .= $s."\n";
       } else {
		   $this->buffer.=$s."\n";
       }
    }
}

?>