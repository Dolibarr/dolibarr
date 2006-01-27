<?php

/*
 * Copyleft 2002 Johann Hanne
 *
 * This is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA  02111-1307 USA
 */

/*
 * This is the Spreadsheet::WriteExcel Perl package ported to PHP
 * Spreadsheet::WriteExcel was written by John McNamara, jmcnamara@cpan.org
 */

class writeexcel_format {

    var $_xf_index;
    var $_font_index;
    var $_font;
    var $_size;
    var $_bold;
    var $_italic;
    var $_color;
    var $_underline;
    var $_font_strikeout;
    var $_font_outline;
    var $_font_shadow;
    var $_font_script;
    var $_font_family;
    var $_font_charset;
    var $_num_format;
    var $_hidden;
    var $_locked;
    var $_text_h_align;
    var $_text_wrap;
    var $_text_v_align;
    var $_text_justlast;
    var $_rotation;
    var $_fg_color;
    var $_bg_color;
    var $_pattern;
    var $_bottom;
    var $_top;
    var $_left;
    var $_right;
    var $_bottom_color;
    var $_top_color;
    var $_left_color;
    var $_right_color;

    /*
     * Constructor
     */
    function writeexcel_format() {
        $_=func_get_args();

        $this->_xf_index       = (sizeof($_)>0) ? array_shift($_) : 0;

        $this->_font_index     = 0;
        $this->_font           = 'Arial';
        $this->_size           = 10;
        $this->_bold           = 0x0190;
        $this->_italic         = 0;
        $this->_color          = 0x7FFF;
        $this->_underline      = 0;
        $this->_font_strikeout = 0;
        $this->_font_outline   = 0;
        $this->_font_shadow    = 0;
        $this->_font_script    = 0;
        $this->_font_family    = 0;
        $this->_font_charset   = 0;

        $this->_num_format     = 0;

        $this->_hidden         = 0;
        $this->_locked         = 1;

        $this->_text_h_align   = 0;
        $this->_text_wrap      = 0;
        $this->_text_v_align   = 2;
        $this->_text_justlast  = 0;
        $this->_rotation       = 0;

        $this->_fg_color       = 0x40;
        $this->_bg_color       = 0x41;

        $this->_pattern        = 0;

        $this->_bottom         = 0;
        $this->_top            = 0;
        $this->_left           = 0;
        $this->_right          = 0;

        $this->_bottom_color   = 0x40;
        $this->_top_color      = 0x40;
        $this->_left_color     = 0x40;
        $this->_right_color    = 0x40;

        // Set properties passed to writeexcel_workbook::addformat()
        if (sizeof($_)>0) {
            call_user_func_array(array(&$this, 'set_properties'), $_);
        }
    }

    /*
     * Copy the attributes of another writeexcel_format object.
     */
    function copy($other) {
        $xf = $this->_xf_index;   // Backup XF index
        foreach ($other as $key->$value) {
                $this->{$key} = $value;
        }
        $this->_xf_index = $xf;   // Restore XF index
    }

    /*
     * Generate an Excel BIFF XF record.
     */
    function get_xf() {

        $_=func_get_args();

        // $record    Record identifier
        // $length    Number of bytes to follow

        // $ifnt      Index to FONT record
        // $ifmt      Index to FORMAT record
        // $style     Style and other options
        // $align     Alignment
        // $icv       fg and bg pattern colors
        // $fill      Fill and border line style
        // $border1   Border line style and color
        // $border2   Border color

        // Set the type of the XF record and some of the attributes.
        if ($_[0] == "style") {
            $style = 0xFFF5;
        } else {
            $style   = $this->_locked;
            $style  |= $this->_hidden << 1;
        }

        // Flags to indicate if attributes have been set.
        $atr_num     = ($this->_num_format != 0) ? 1 : 0;
        $atr_fnt     = ($this->_font_index != 0) ? 1 : 0;
        $atr_alc     =  $this->_text_wrap ? 1 : 0;
        $atr_bdr     = ($this->_bottom   ||
                        $this->_top      ||
                        $this->_left     ||
                        $this->_right) ? 1 : 0;
        $atr_pat     = ($this->_fg_color != 0x41 ||
                        $this->_bg_color != 0x41 ||
                        $this->_pattern  != 0x00) ? 1 : 0;
        $atr_prot    = 0;

        // Reset the default colors for the non-font properties
        if ($this->_fg_color     == 0x7FFF) $this->_fg_color     = 0x40;
        if ($this->_bg_color     == 0x7FFF) $this->_bg_color     = 0x41;
        if ($this->_bottom_color == 0x7FFF) $this->_bottom_color = 0x41;
        if ($this->_top_color    == 0x7FFF) $this->_top_color    = 0x41;
        if ($this->_left_color   == 0x7FFF) $this->_left_color   = 0x41;
        if ($this->_right_color  == 0x7FFF) $this->_right_color  = 0x41;

        // Zero the default border colour if the border has not been set.
        if ($this->_bottom == 0) {
            $this->_bottom_color = 0;
        }
        if ($this->_top    == 0) {
            $this->_top_color    = 0;
        }
        if ($this->_right  == 0) {
            $this->_right_color  = 0;
        }
        if ($this->_left   == 0) {
            $this->_left_color   = 0;
        }

        // The following 2 logical statements take care of special cases in 
        // relation to cell colors and patterns:
        // 1. For a solid fill (_pattern == 1) Excel reverses the role of
        //    foreground and background colors
        // 2. If the user specifies a foreground or background color
        //    without a pattern they probably wanted a solid fill, so we
        //    fill in the defaults.
        if ($this->_pattern <= 0x01 && 
            $this->_bg_color != 0x41 && 
            $this->_fg_color == 0x40 )
        {
            $this->_fg_color = $this->_bg_color;
            $this->_bg_color = 0x40;
            $this->_pattern  = 1;
        }

        if ($this->_pattern <= 0x01 &&
            $this->_bg_color == 0x41 &&
            $this->_fg_color != 0x40 )
        {
            $this->_bg_color = 0x40;
            $this->_pattern  = 1;
        }

        $record         = 0x00E0;
        $length         = 0x0010;

        $ifnt           = $this->_font_index;
        $ifmt           = $this->_num_format;

        $align          = $this->_text_h_align;
        $align         |= $this->_text_wrap     << 3;
        $align         |= $this->_text_v_align  << 4;
        $align         |= $this->_text_justlast << 7;
        $align         |= $this->_rotation      << 8;
        $align         |= $atr_num              << 10;
        $align         |= $atr_fnt              << 11;
        $align         |= $atr_alc              << 12;
        $align         |= $atr_bdr              << 13;
        $align         |= $atr_pat              << 14;
        $align         |= $atr_prot             << 15;

        $icv            = $this->_fg_color;
        $icv           |= $this->_bg_color      << 7;

        $fill           = $this->_pattern;
        $fill          |= $this->_bottom        << 6;
        $fill          |= $this->_bottom_color  << 9;

        $border1        = $this->_top;
        $border1       |= $this->_left          << 3;
        $border1       |= $this->_right         << 6;
        $border1       |= $this->_top_color     << 9;

        $border2        = $this->_left_color;
        $border2       |= $this->_right_color   << 7;

        $header      = pack("vv",       $record, $length);
        $data        = pack("vvvvvvvv", $ifnt, $ifmt, $style, $align,
                                        $icv, $fill,
                                        $border1, $border2);

        return($header . $data);
    }

    /*
     * Generate an Excel BIFF FONT record.
     */
    function get_font() {

        // $record     Record identifier
        // $length     Record length

        // $dyHeight   Height of font (1/20 of a point)
        // $grbit      Font attributes
        // $icv        Index to color palette
        // $bls        Bold style
        // $sss        Superscript/subscript
        // $uls        Underline
        // $bFamily    Font family
        // $bCharSet   Character set
        // $reserved   Reserved
        // $cch        Length of font name
        // $rgch       Font name

        $dyHeight   = $this->_size * 20;
        $icv        = $this->_color;
        $bls        = $this->_bold;
        $sss        = $this->_font_script;
        $uls        = $this->_underline;
        $bFamily    = $this->_font_family;
        $bCharSet   = $this->_font_charset;
        $rgch       = $this->_font;

        $cch        = strlen($rgch);
        $record     = 0x31;
        $length     = 0x0F + $cch;
        $reserved   = 0x00;

        $grbit      = 0x00;

        if ($this->_italic) {
            $grbit     |= 0x02;
        }

        if ($this->_font_strikeout) {
            $grbit     |= 0x08;
        }

        if ($this->_font_outline) {
            $grbit     |= 0x10;
        }

        if ($this->_font_shadow) {
            $grbit     |= 0x20;
        }

        $header  = pack("vv",         $record, $length);
        $data    = pack("vvvvvCCCCC", $dyHeight, $grbit, $icv, $bls,
                                      $sss, $uls, $bFamily,
                                      $bCharSet, $reserved, $cch);

        return($header . $data . $this->_font);
    }

    /*
     * Returns a unique hash key for a font.
     * Used by writeexcel_workbook::_store_all_fonts()
     */
    function get_font_key() {

        # The following elements are arranged to increase the probability of
        # generating a unique key. Elements that hold a large range of numbers
        # eg. _color are placed between two binary elements such as _italic
        #
        $key  = $this->_font.$this->_size.
                $this->_font_script.$this->_underline.
                $this->_font_strikeout.$this->_bold.$this->_font_outline.
                $this->_font_family.$this->_font_charset.
                $this->_font_shadow.$this->_color.$this->_italic;

        $key = preg_replace('/ /', '_', $key); # Convert the key to a single word

        return $key;
    }

    /*
     * Returns the used by Worksheet->_XF()
     */
    function get_xf_index() {
        return $this->_xf_index;
    }

    /*
     * Used in conjunction with the set_xxx_color methods to convert a color
     * string into a number. Color range is 0..63 but we will restrict it
     * to 8..63 to comply with Gnumeric. Colors 0..7 are repeated in 8..15.
     */
    function _get_color($color=false) {

        $colors = array(
                        'aqua'    => 0x0F,
                        'cyan'    => 0x0F,
                        'black'   => 0x08,
                        'blue'    => 0x0C,
                        'brown'   => 0x10,
                        'magenta' => 0x0E,
                        'fuchsia' => 0x0E,
                        'gray'    => 0x17,
                        'grey'    => 0x17,
                        'green'   => 0x11,
                        'lime'    => 0x0B,
                        'navy'    => 0x12,
                        'orange'  => 0x35,
                        'purple'  => 0x14,
                        'red'     => 0x0A,
                        'silver'  => 0x16,
                        'white'   => 0x09,
                        'yellow'  => 0x0D
                       );

        // Return the default color, 0x7FFF, if undef,
        if ($color===false) {
            return 0x7FFF;
        }

        // or the color string converted to an integer,
        if (isset($colors[strtolower($color)])) {
            return $colors[strtolower($color)];
        }

        // or the default color if string is unrecognised,
        if (preg_match('/\D/', $color)) {
            return 0x7FFF;
        }

        // or an index < 8 mapped into the correct range,
        if ($color<8) {
            return $color + 8;
        }

        // or the default color if arg is outside range,
        if ($color>63) {
            return 0x7FFF;
        }

        // or an integer in the valid range
        return $color;
    }

    /*
     * Set cell alignment.
     */
    function set_align($location) {

        // Ignore numbers
        if (preg_match('/\d/', $location)) {
            return;
        }

        $location = strtolower($location);

        switch ($location) {

        case 'left':
            $this->set_text_h_align(1);
            break;

        case 'centre':
        case 'center':
            $this->set_text_h_align(2);
            break;

        case 'right':
            $this->set_text_h_align(3);
            break;

        case 'fill':
            $this->set_text_h_align(4);
            break;

        case 'justify':
            $this->set_text_h_align(5);
            break;

        case 'merge':
            $this->set_text_h_align(6);
            break;

        case 'equal_space':
            $this->set_text_h_align(7);
            break;

        case 'top':
            $this->set_text_v_align(0);
            break;

        case 'vcentre':
        case 'vcenter':
            $this->set_text_v_align(1);
            break;
            break;

        case 'bottom':
            $this->set_text_v_align(2);
            break;

        case 'vjustify':
            $this->set_text_v_align(3);
            break;

        case 'vequal_space':
            $this->set_text_v_align(4);
            break;
        }
    }

    /*
     * Set vertical cell alignment. This is required by the set_properties()
     * method to differentiate between the vertical and horizontal properties.
     */
    function set_valign($location) {
        $this->set_align($location);
    }

    /*
     * This is an alias for the unintuitive set_align('merge')
     */
    function set_merge() {
        $this->set_text_h_align(6);
    }

    /*
     * Bold has a range 0x64..0x3E8.
     * 0x190 is normal. 0x2BC is bold.
     */
    function set_bold($weight=1) {

        if ($weight == 1) {
            // Bold text
            $weight = 0x2BC;
        }

        if ($weight == 0) {
            // Normal text
            $weight = 0x190;
        }

        if ($weight < 0x064) {
            // Lower bound
            $weight = 0x190;
        }

        if ($weight > 0x3E8) {
            // Upper bound
            $weight = 0x190;
        }

        $this->_bold = $weight;
    }

    /*
     * Set all cell borders (bottom, top, left, right) to the same style
     */
    function set_border($style) {
        $this->set_bottom($style);
        $this->set_top($style);
        $this->set_left($style);
        $this->set_right($style);
    }

    /*
     * Set all cell borders (bottom, top, left, right) to the same color
     */
    function set_border_color($color) {
        $this->set_bottom_color($color);
        $this->set_top_color($color);
        $this->set_left_color($color);
        $this->set_right_color($color);
    }

    /*
     * Convert hashes of properties to method calls.
     */
    function set_properties() {

        $_=func_get_args();

        $properties=array();
        foreach($_ as $props) {
            if (is_array($props)) {
                $properties=array_merge($properties, $props);
            } else {
                $properties[]=$props;
            }
        }

        foreach ($properties as $key=>$value) {

            // Strip leading "-" from Tk style properties eg. -color => 'red'.
            $key = preg_replace('/^-/', '', $key);

            /* Make sure method names are alphanumeric characters only, in
               case tainted data is passed to the eval(). */
            if (preg_match('/\W/', $key)) {
                trigger_error("Unknown property: $key.",
                              E_USER_ERROR);
            }

            /* Evaling all $values as a strings gets around the problem of
               some numerical format strings being evaluated as numbers, for
               example "00000" for a zip code. */
            if (is_int($key)) {
                eval("\$this->set_$value();");
            } else {
                eval("\$this->set_$key('$value');");
            }

        }
    }

    function set_font($font) {
        $this->_font=$font;
    }

    function set_size($size) {
        $this->_size=$size;
    }

    function set_italic($italic=1) {
        $this->_italic=$italic;
    }

    function set_color($color) {
        $this->_color=$this->_get_color($color);
    }

    function set_underline($underline=1) {
        $this->_underline=$underline;
    }

    function set_font_strikeout($font_strikeout=1) {
        $this->_font_strikeout=$font_strikeout;
    }

    function set_font_outline($font_outline=1) {
        $this->_font_outline=$font_outline;
    }

    function set_font_shadow($font_shadow=1) {
        $this->_font_shadow=$font_shadow;
    }

    function set_font_script($font_script=1) {
        $this->_font_script=$font_script;
    }

    /* Undocumented */
    function set_font_family($font_family=1) {
        $this->_font_family=$font_family;
    }

    /* Undocumented */
    function set_font_charset($font_charset=1) {
        $this->_font_charset=$font_charset;
    }

    function set_num_format($num_format=1) {
        $this->_num_format=$num_format;
    }

    function set_hidden($hidden=1) {
        $this->_hidden=$hidden;
    }

    function set_locked($locked=1) {
        $this->_locked=$locked;
    }

    function set_text_h_align($align) {
        $this->_text_h_align=$align;
    }

    function set_text_wrap($wrap=1) {
        $this->_text_wrap=$wrap;
    }

    function set_text_v_align($align) {
        $this->_text_v_align=$align;
    }

    function set_text_justlast($text_justlast=1) {
        $this->_text_justlast=$text_justlast;
    }

    function set_rotation($rotation=1) {
        $this->_rotation=$rotation;
    }

    function set_fg_color($color) {
        $this->_fg_color=$this->_get_color($color);
    }

    function set_bg_color($color) {
        $this->_bg_color=$this->_get_color($color);
    }

    function set_pattern($pattern=1) {
        $this->_pattern=$pattern;
    }

    function set_bottom($bottom=1) {
        $this->_bottom=$bottom;
    }

    function set_top($top=1) {
        $this->_top=$top;
    }

    function set_left($left=1) {
        $this->_left=$left;
    }

    function set_right($right=1) {
         $this->_right=$right;
    }

    function set_bottom_color($color) {
        $this->_bottom_color=$this->_get_color($color);
    }

    function set_top_color($color) {
        $this->_top_color=$this->_get_color($color);
    }

    function set_left_color($color) {
        $this->_left_color=$this->_get_color($color);
    }

    function set_right_color($color) {
        $this->_right_color=$this->_get_color($color);
    }

}

?>
