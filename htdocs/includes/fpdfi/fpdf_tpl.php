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

require_once('fpdi_bridge.php');

/**
 * Class FPDF_TPL
 */
class FPDF_TPL extends fpdi_bridge
{
    /**
     * Array of template data
     *
     * @var array
     */
    protected $_tpls = array();

    /**
     * Current Template-Id
     *
     * @var int
     */
    public $tpl = 0;

    /**
     * "In Template"-Flag
     *
     * @var boolean
     */
    protected $_inTpl = false;

    /**
     * Name prefix of templates used in Resources dictionary
     *
     * @var string A String defining the Prefix used as Template-Object-Names. Have to begin with an /
     */
    public $tplPrefix = "/TPL";

    /**
     * Resources used by templates and pages
     *
     * @var array
     */
    protected  $_res = array();

    /**
     * Last used template data
     *
     * @var array
     */
    public $lastUsedTemplateData = array();

    /**
     * Start a template.
     *
     * This method starts a template. You can give own coordinates to build an own sized
     * template. Pay attention, that the margins are adapted to the new template size.
     * If you want to write outside the template, for example to build a clipped template,
     * you have to set the margins and "cursor"-position manual after beginTemplate()-call.
     *
     * If no parameter is given, the template uses the current page-size.
     * The method returns an id of the current template. This id is used later for using this template.
     * Warning: A created template is saved in the resulting PDF at all events. Also if you don't use it after creation!
     *
     * @param int $x The x-coordinate given in user-unit
     * @param int $y The y-coordinate given in user-unit
     * @param int $w The width given in user-unit
     * @param int $h The height given in user-unit
     * @return int The id of new created template
     * @throws LogicException
     */
    public function beginTemplate($x = null, $y = null, $w = null, $h = null)
    {
        if (is_subclass_of($this, 'TCPDF')) {
            throw new LogicException('This method is only usable with FPDF. Use TCPDF methods startTemplate() instead.');
        }

        if ($this->page <= 0) {
            throw new LogicException("You have to add at least a page first!");
        }

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
        $tpl =& $this->_tpls[$this->tpl];
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
            'o_FontFamily' => $this->FontFamily,
            'o_FontStyle' => $this->FontStyle,
            'o_FontSizePt' => $this->FontSizePt,
            'o_FontSize' => $this->FontSize,
            'buffer' => '',
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h
        );

        $this->SetAutoPageBreak(false);

        // Define own high and width to calculate correct positions
        $this->h = $h;
        $this->w = $w;

        $this->_inTpl = true;
        $this->SetXY($x + $this->lMargin, $y + $this->tMargin);
        $this->SetRightMargin($this->w - $w + $this->rMargin);

        if ($this->CurrentFont) {
            $fontKey = $this->FontFamily . $this->FontStyle;
            if ($fontKey) {
                $this->_res['tpl'][$this->tpl]['fonts'][$fontKey] =& $this->fonts[$fontKey];
                $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
            }
        }

        return $this->tpl;
    }

    /**
     * End template.
     *
     * This method ends a template and reset initiated variables collected in {@link beginTemplate()}.
     *
     * @return int|boolean If a template is opened, the id is returned. If not a false is returned.
     */
    public function endTemplate()
    {
        if (is_subclass_of($this, 'TCPDF')) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'TCPDF::endTemplate'), $args);
        }

        if ($this->_inTpl) {
            $this->_inTpl = false;
            $tpl = $this->_tpls[$this->tpl];
            $this->SetXY($tpl['o_x'], $tpl['o_y']);
            $this->tMargin = $tpl['o_tMargin'];
            $this->lMargin = $tpl['o_lMargin'];
            $this->rMargin = $tpl['o_rMargin'];
            $this->h = $tpl['o_h'];
            $this->w = $tpl['o_w'];
            $this->SetAutoPageBreak($tpl['o_AutoPageBreak'], $tpl['o_bMargin']);

            $this->FontFamily = $tpl['o_FontFamily'];
            $this->FontStyle = $tpl['o_FontStyle'];
            $this->FontSizePt = $tpl['o_FontSizePt'];
            $this->FontSize = $tpl['o_FontSize'];

            $fontKey = $this->FontFamily . $this->FontStyle;
            if ($fontKey)
                $this->CurrentFont =& $this->fonts[$fontKey];

            return $this->tpl;
        } else {
            return false;
        }
    }

    /**
     * Use a template in current page or other template.
     *
     * You can use a template in a page or in another template.
     * You can give the used template a new size.
     * All parameters are optional. The width or height is calculated automatically
     * if one is given. If no parameter is given the origin size as defined in
     * {@link beginTemplate()} method is used.
     *
     * The calculated or used width and height are returned as an array.
     *
     * @param int $tplIdx A valid template-id
     * @param int $x The x-position
     * @param int $y The y-position
     * @param int $w The new width of the template
     * @param int $h The new height of the template
     * @return array The height and width of the template (array('w' => ..., 'h' => ...))
     * @throws LogicException|InvalidArgumentException
     */
    public function useTemplate($tplIdx, $x = null, $y = null, $w = 0, $h = 0)
    {
        if ($this->page <= 0) {
            throw new LogicException('You have to add at least a page first!');
        }

        if (!isset($this->_tpls[$tplIdx])) {
            throw new InvalidArgumentException('Template does not exist!');
        }

        if ($this->_inTpl) {
            $this->_res['tpl'][$this->tpl]['tpls'][$tplIdx] =& $this->_tpls[$tplIdx];
        }

        $tpl = $this->_tpls[$tplIdx];
        $_w = $tpl['w'];
        $_h = $tpl['h'];

        if ($x == null) {
            $x = 0;
        }

        if ($y == null) {
            $y = 0;
        }

        $x += $tpl['x'];
        $y += $tpl['y'];

        $wh = $this->getTemplateSize($tplIdx, $w, $h);
        $w = $wh['w'];
        $h = $wh['h'];

        $tplData = array(
            'x' => $this->x,
            'y' => $this->y,
            'w' => $w,
            'h' => $h,
            'scaleX' => ($w / $_w),
            'scaleY' => ($h / $_h),
            'tx' => $x,
            'ty' =>  ($this->h - $y - $h),
            'lty' => ($this->h - $y - $h) - ($this->h - $_h) * ($h / $_h)
        );

        $this->_out(sprintf('q %.4F 0 0 %.4F %.4F %.4F cm',
                $tplData['scaleX'], $tplData['scaleY'], $tplData['tx'] * $this->k, $tplData['ty'] * $this->k)
        ); // Translate
        $this->_out(sprintf('%s%d Do Q', $this->tplPrefix, $tplIdx));

        $this->lastUsedTemplateData = $tplData;

        return array('w' => $w, 'h' => $h);
    }

    /**
     * Get the calculated size of a template.
     *
     * If one size is given, this method calculates the other one.
     *
     * @param int $tplIdx A valid template-id
     * @param int $w The width of the template
     * @param int $h The height of the template
     * @return array The height and width of the template (array('w' => ..., 'h' => ...))
     */
    public function getTemplateSize($tplIdx, $w = 0, $h = 0)
    {
        if (!isset($this->_tpls[$tplIdx]))
            return false;

        $tpl = $this->_tpls[$tplIdx];
        $_w = $tpl['w'];
        $_h = $tpl['h'];

        if ($w == 0 && $h == 0) {
            $w = $_w;
            $h = $_h;
        }

        if ($w == 0)
            $w = $h * $_w / $_h;
        if($h == 0)
            $h = $w * $_h / $_w;

        return array("w" => $w, "h" => $h);
    }

    /**
     * Sets the font used to print character strings.
     *
     * See FPDF/TCPDF documentation.
     *
     * @see http://fpdf.org/en/doc/setfont.htm
     * @see http://www.tcpdf.org/doc/code/classTCPDF.html#afd56e360c43553830d543323e81bc045
     */
    public function SetFont($family, $style = '', $size = null, $fontfile = '', $subset = 'default', $out = true)
    {
        if (is_subclass_of($this, 'TCPDF')) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'TCPDF::SetFont'), $args);
        }

        parent::SetFont($family, $style, $size);

        $fontkey = $this->FontFamily . $this->FontStyle;

        if ($this->_inTpl) {
            $this->_res['tpl'][$this->tpl]['fonts'][$fontkey] =& $this->fonts[$fontkey];
        } else {
            $this->_res['page'][$this->page]['fonts'][$fontkey] =& $this->fonts[$fontkey];
        }
    }

    /**
     * Puts an image.
     *
     * See FPDF/TCPDF documentation.
     *
     * @see http://fpdf.org/en/doc/image.htm
     * @see http://www.tcpdf.org/doc/code/classTCPDF.html#a714c2bee7d6b39d4d6d304540c761352
     */
    public function Image(
        $file, $x = '', $y = '', $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false,
        $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false,
        $hidden = false, $fitonpage = false, $alt = false, $altimgs = array()
    )
    {
        if (is_subclass_of($this, 'TCPDF')) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'TCPDF::Image'), $args);
        }

        $ret = parent::Image($file, $x, $y, $w, $h, $type, $link);
        if ($this->_inTpl) {
            $this->_res['tpl'][$this->tpl]['images'][$file] =& $this->images[$file];
        } else {
            $this->_res['page'][$this->page]['images'][$file] =& $this->images[$file];
        }

        return $ret;
    }

    /**
     * Adds a new page to the document.
     *
     * See FPDF/TCPDF documentation.
     *
     * This method cannot be used if you'd started a template.
     *
     * @see http://fpdf.org/en/doc/addpage.htm
     * @see http://www.tcpdf.org/doc/code/classTCPDF.html#a5171e20b366b74523709d84c349c1ced
     */
    public function AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false)
    {
        if (is_subclass_of($this, 'TCPDF')) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'TCPDF::AddPage'), $args);
        }

        if ($this->_inTpl) {
            throw new LogicException('Adding pages in templates is not possible!');
        }

        parent::AddPage($orientation, $format);
    }

    /**
     * Puts a link on a rectangular area of the page.
     *
     * Overwritten because adding links in a template will not work.
     *
     * @see http://fpdf.org/en/doc/link.htm
     * @see http://www.tcpdf.org/doc/code/classTCPDF.html#ab87bf1826384fbfe30eb499d42f1d994
     */
    public function Link($x, $y, $w, $h, $link, $spaces = 0)
    {
        if (is_subclass_of($this, 'TCPDF')) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'TCPDF::Link'), $args);
        }

        if ($this->_inTpl) {
            throw new LogicException('Using links in templates is not posible!');
        }

        parent::Link($x, $y, $w, $h, $link);
    }

    /**
     * Creates a new internal link and returns its identifier.
     *
     * Overwritten because adding links in a template will not work.
     *
     * @see http://fpdf.org/en/doc/addlink.htm
     * @see http://www.tcpdf.org/doc/code/classTCPDF.html#a749522038ed7786c3e1701435dcb891e
     */
    public function AddLink()
    {
        if (is_subclass_of($this, 'TCPDF')) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'TCPDF::AddLink'), $args);
        }

        if ($this->_inTpl) {
            throw new LogicException('Adding links in templates is not possible!');
        }

        return parent::AddLink();
    }

    /**
     * Defines the page and position a link points to.
     *
     * Overwritten because adding links in a template will not work.
     *
     * @see http://fpdf.org/en/doc/setlink.htm
     * @see http://www.tcpdf.org/doc/code/classTCPDF.html#ace5be60e7857953ea5e2b89cb90df0ae
     */
    public function SetLink($link, $y = 0, $page = -1)
    {
        if (is_subclass_of($this, 'TCPDF')) {
            $args = func_get_args();
            return call_user_func_array(array($this, 'TCPDF::SetLink'), $args);
        }

        if ($this->_inTpl) {
            throw new LogicException('Setting links in templates is not possible!');
        }

        parent::SetLink($link, $y, $page);
    }

    /**
     * Writes the form XObjects to the PDF document.
     */
    protected function _putformxobjects()
    {
        $filter=($this->compress) ? '/Filter /FlateDecode ' : '';
        reset($this->_tpls);

        foreach($this->_tpls AS $tplIdx => $tpl) {
            $this->_newobj();
            $this->_tpls[$tplIdx]['n'] = $this->n;
            $this->_out('<<'.$filter.'/Type /XObject');
            $this->_out('/Subtype /Form');
            $this->_out('/FormType 1');
            $this->_out(sprintf('/BBox [%.2F %.2F %.2F %.2F]',
                // llx
                $tpl['x'] * $this->k,
                // lly
                -$tpl['y'] * $this->k,
                // urx
                ($tpl['w'] + $tpl['x']) * $this->k,
                // ury
                ($tpl['h'] - $tpl['y']) * $this->k
            ));

            if ($tpl['x'] != 0 || $tpl['y'] != 0) {
                $this->_out(sprintf('/Matrix [1 0 0 1 %.5F %.5F]',
                    -$tpl['x'] * $this->k * 2, $tpl['y'] * $this->k * 2
                ));
            }

            $this->_out('/Resources ');
            $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');

            if (isset($this->_res['tpl'][$tplIdx])) {
                $res = $this->_res['tpl'][$tplIdx];
                if (isset($res['fonts']) && count($res['fonts'])) {
                    $this->_out('/Font <<');

                    foreach($res['fonts'] as $font) {
                        $this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
                    }

                    $this->_out('>>');
                }

                if(isset($res['images']) || isset($res['tpls'])) {
                    $this->_out('/XObject <<');

                    if (isset($res['images'])) {
                        foreach($res['images'] as $image)
                            $this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
                    }

                    if (isset($res['tpls'])) {
                        foreach($res['tpls'] as $i => $_tpl)
                            $this->_out($this->tplPrefix . $i . ' ' . $_tpl['n'] . ' 0 R');
                    }

                    $this->_out('>>');
                }
            }

            $this->_out('>>');

            $buffer = ($this->compress) ? gzcompress($tpl['buffer']) : $tpl['buffer'];
            $this->_out('/Length ' . strlen($buffer) . ' >>');
            $this->_putstream($buffer);
            $this->_out('endobj');
        }
    }

    /**
     * Output images.
     *
     * Overwritten to add {@link _putformxobjects()} after _putimages().
     */
    public function _putimages()
    {
        parent::_putimages();
        $this->_putformxobjects();
    }

    /**
     * Writes the references of XObject resources to the document.
     *
     * Overwritten to add the the templates to the XObject resource dictionary.
     */
    public function _putxobjectdict()
    {
        parent::_putxobjectdict();

        foreach($this->_tpls as $tplIdx => $tpl) {
            $this->_out(sprintf('%s%d %d 0 R', $this->tplPrefix, $tplIdx, $tpl['n']));
        }
    }

    /**
     * Writes bytes to the resulting document.
     *
     * Overwritten to delegate the data to the template buffer.
     *
     * @param string $s
     */
    public function _out($s)
    {
        if ($this->state == 2 && $this->_inTpl) {
            $this->_tpls[$this->tpl]['buffer'] .= $s . "\n";
        } else {
            parent::_out($s);
        }
    }
}
