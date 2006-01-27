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

require_once "class.writeexcel_biffwriter.inc.php";

class writeexcel_worksheet extends writeexcel_biffwriter {

    var $_name;
    var $_index;
    var $_activesheet;
    var $_firstsheet;
    var $_url_format;
    var $_parser;
    var $_tempdir;

    var $_ext_sheets;
    var $_using_tmpfile;
    var $_tmpfilename;
    var $_filehandle;
    var $_fileclosed;
    var $_offset;
    var $_xls_rowmax;
    var $_xls_colmax;
    var $_xls_strmax;
    var $_dim_rowmin;
    var $_dim_rowmax;
    var $_dim_colmin;
    var $_dim_colmax;
    var $_colinfo;
    var $_selection;
    var $_panes;
    var $_active_pane;
    var $_frozen;
    var $_selected;

    var $_paper_size;
    var $_orientation;
    var $_header;
    var $_footer;
    var $_hcenter;
    var $_vcenter;
    var $_margin_head;
    var $_margin_foot;
    var $_margin_left;
    var $_margin_right;
    var $_margin_top;
    var $_margin_bottom;

    var $_title_rowmin;
    var $_title_rowmax;
    var $_title_colmin;
    var $_title_colmax;
    var $_print_rowmin;
    var $_print_rowmax;
    var $_print_colmin;
    var $_print_colmax;

    var $_print_gridlines;
    var $_screen_gridlines;
    var $_print_headers;

    var $_fit_page;
    var $_fit_width;
    var $_fit_height;

    var $_hbreaks;
    var $_vbreaks;

    var $_protect;
    var $_password;

    var $_col_sizes;
    var $_row_sizes;

    var $_col_formats;
    var $_row_formats;

    var $_zoom;
    var $_print_scale;

    var $_debug;

    /*
     * Constructor. Creates a new Worksheet object from a BIFFwriter object
     */
    function writeexcel_worksheet($name, $index, &$activesheet, &$firstsheet,
                                  &$url_format, &$parser, $tempdir) {

        $this->writeexcel_biffwriter();

        $rowmax                   = 65536; // 16384 in Excel 5
        $colmax                   = 256;
        $strmax                   = 255;

        $this->_name              = $name;
        $this->_index             = $index;
        $this->_activesheet       = &$activesheet;
        $this->_firstsheet        = &$firstsheet;
        $this->_url_format        = &$url_format;
        $this->_parser            = &$parser;
        $this->_tempdir           = $tempdir;

        $this->_ext_sheets        = array();
        $this->_using_tmpfile     = 1;
        $this->_tmpfilename       = false;
        $this->_filehandle        = false;
        $this->_fileclosed        = 0;
        $this->_offset            = 0;
        $this->_xls_rowmax        = $rowmax;
        $this->_xls_colmax        = $colmax;
        $this->_xls_strmax        = $strmax;
        $this->_dim_rowmin        = $rowmax +1;
        $this->_dim_rowmax        = 0;
        $this->_dim_colmin        = $colmax +1;
        $this->_dim_colmax        = 0;
        $this->_colinfo           = array();
        $this->_selection         = array(0, 0);
        $this->_panes             = array();
        $this->_active_pane       = 3;
        $this->_frozen            = 0;
        $this->_selected          = 0;

        $this->_paper_size        = 0x0;
        $this->_orientation       = 0x1;
        $this->_header            = '';
        $this->_footer            = '';
        $this->_hcenter           = 0;
        $this->_vcenter           = 0;
        $this->_margin_head       = 0.50;
        $this->_margin_foot       = 0.50;
        $this->_margin_left       = 0.75;
        $this->_margin_right      = 0.75;
        $this->_margin_top        = 1.00;
        $this->_margin_bottom     = 1.00;

        $this->_title_rowmin      = false;
        $this->_title_rowmax      = false;
        $this->_title_colmin      = false;
        $this->_title_colmax      = false;
        $this->_print_rowmin      = false;
        $this->_print_rowmax      = false;
        $this->_print_colmin      = false;
        $this->_print_colmax      = false;

        $this->_print_gridlines   = 1;
        $this->_screen_gridlines  = 1;
        $this->_print_headers     = 0;

        $this->_fit_page          = 0;
        $this->_fit_width         = 0;
        $this->_fit_height        = 0;

        $this->_hbreaks           = array();
        $this->_vbreaks           = array();

        $this->_protect           = 0;
        $this->_password          = false;

        $this->_col_sizes         = array();
        $this->_row_sizes         = array();

        $this->_col_formats       = array();
        $this->_row_formats       = array();

        $this->_zoom              = 100;
        $this->_print_scale       = 100;

        $this->_initialize();
    }

###############################################################################
#
# _initialize()
#
# Open a tmp file to store the majority of the Worksheet data. If this fails,
# for example due to write permissions, store the data in memory. This can be
# slow for large files.
#
function _initialize() {

    # Open tmp file for storing Worksheet data.
    $this->_tmpfilename=tempnam($this->_tempdir, "php_writeexcel");
    $fh=fopen($this->_tmpfilename, "w+b");

    if ($fh) {
        # Store filehandle
        $this->_filehandle = $fh;
    } else {
        # If tempfile() failed store data in memory
        $this->_using_tmpfile = 0;
        $this->_tmpfilename=false;

        if ($this->_index == 0) {
            $dir = $this->_tempdir;

//todo            warn "Unable to create temp files in $dir. Refer to set_tempdir()".
//                 " in the Spreadsheet::WriteExcel documentation.\n" ;
        }
    }
}

    /*
     * Add data to the beginning of the workbook (note the reverse order)
     * and to the end of the workbook.
     */
    function _close($sheetnames) {

        ///////////////////////////////
        // Prepend in reverse order!!
        //

        $this->_store_dimensions();        // Prepend the sheet dimensions
        $this->_store_password();          // Prepend the sheet password
        $this->_store_protect();           // Prepend the sheet protection
        $this->_store_setup();             // Prepend the page setup
        $this->_store_margin_bottom();     // Prepend the bottom margin
        $this->_store_margin_top();        // Prepend the top margin
        $this->_store_margin_right();      // Prepend the right margin
        $this->_store_margin_left();       // Prepend the left margin
        $this->_store_vcenter();           // Prepend the page vertical
                                           // centering
        $this->_store_hcenter();           // Prepend the page horizontal
                                           // centering
        $this->_store_footer();            // Prepend the page footer
        $this->_store_header();            // Prepend the page header
        $this->_store_vbreak();            // Prepend the vertical page breaks
        $this->_store_hbreak();            // Prepend the horizontal
                                           // page breaks
        $this->_store_wsbool();            // Prepend WSBOOL
        $this->_store_gridset();           // Prepend GRIDSET
        $this->_store_print_gridlines();   // Prepend PRINTGRIDLINES
        $this->_store_print_headers();     // Prepend PRINTHEADERS

        // Prepend EXTERNSHEET references
        $num_sheets = sizeof($sheetnames);
        for ($i = $num_sheets; $i > 0; $i--) {
            $sheetname = $sheetnames[$i-1];
            $this->_store_externsheet($sheetname);
        }

        $this->_store_externcount($num_sheets);   // Prepend the EXTERNCOUNT
                                                  // of external references.

        // Prepend the COLINFO records if they exist
        if (sizeof($this->_colinfo)>0){
            while (sizeof($this->_colinfo)>0) {
                $arrayref = array_pop ($this->_colinfo);
                $this->_store_colinfo($arrayref);
            }
            $this->_store_defcol();
        }

        $this->_store_bof(0x0010);    // Prepend the BOF record

        //
        // End of prepend. Read upwards from here.
        ////////////////////////////////////////////

        // Append
        $this->_store_window2();
        $this->_store_zoom();

        if (sizeof($this->_panes)>0) {
            $this->_store_panes($this->_panes);
        }

        $this->_store_selection($this->_selection);
        $this->_store_eof();
    }

    /*
     * Retrieve the worksheet name.
     */
    function get_name() {
        return $this->_name;
    }

###############################################################################
#
# get_data().
#
# Retrieves data from memory in one chunk, or from disk in $buffer
# sized chunks.
#
function get_data() {

    $buffer = 4096;

    # Return data stored in memory
    if ($this->_data!==false) {
        $tmp=$this->_data;
        $this->_data=false;

        // The next data comes from the temporary file, so prepare
        // it by putting the file pointer to the beginning
        if ($this->_using_tmpfile) {
            fseek($this->_filehandle, 0, SEEK_SET);
        }

        if ($this->_debug) {
            print "*** worksheet::get_data() called (1):";
            for ($c=0;$c<strlen($tmp);$c++) {
                if ($c%16==0) {
                    print "\n";
                }
                printf("%02X ", ord($tmp[$c]));
            }
            print "\n";
        }

        return $tmp;
    }

    # Return data stored on disk
    if ($this->_using_tmpfile) {
        if ($tmp=fread($this->_filehandle, $buffer)) {

            if ($this->_debug) {
                print "*** worksheet::get_data() called (2):";
                for ($c=0;$c<strlen($tmp);$c++) {
                    if ($c%16==0) {
                        print "\n";
                    }
                    printf("%02X ", ord($tmp[$c]));
                }
                print "\n";
            }

            return $tmp;
        }
    }

    # No more data to return
    return false;
}

    /* Remove the temporary file */
    function cleanup() {
      if ($this->_using_tmpfile) {
        fclose($this->_filehandle);
        unlink($this->_tmpfilename);
        $this->_tmpfilename=false;
        $this->_using_tmpfile=false;
      }
    }

    /*
     * Set this worksheet as a selected worksheet, i.e. the worksheet has
     * its tab highlighted.
     */
    function select() {
        $this->_selected = 1;
    }

    /*
     * Set this worksheet as the active worksheet, i.e. the worksheet
     * that is displayed when the workbook is opened. Also set it as
     * selected.
     */
    function activate() {
        $this->_selected = 1;
        $this->_activesheet = $this->_index;
    }

    /*
     * Set this worksheet as the first visible sheet. This is necessary
     * when there are a large number of worksheets and the activated
     * worksheet is not visible on the screen.
     */
    function set_first_sheet() {
        $this->_firstsheet = $this->_index;
    }

    /*
     * Set the worksheet protection flag to prevent accidental modification
     * and to hide formulas if the locked and hidden format properties have
     * been set.
     */
    function protect($password) {
        $this->_protect   = 1;
        $this->_password  = $this->_encode_password($password);
    }

###############################################################################
#
# set_column($firstcol, $lastcol, $width, $format, $hidden)
#
# Set the width of a single column or a range of column.
# See also: _store_colinfo
#
function set_column() {

    $_=func_get_args();

    $cell = $_[0];

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $cell)) {
        $_ = $this->_substitute_cellref($_);
    }

    array_push($this->_colinfo, $_);

    # Store the col sizes for use when calculating image vertices taking
    # hidden columns into account. Also store the column formats.
    #
    if (sizeof($_)<3) {
        # Ensure at least $firstcol, $lastcol and $width
        return;
    }

    $width  = $_[4] ? 0 : $_[2]; # Set width to zero if column is hidden
    $format = $_[3];

    list($firstcol, $lastcol) = $_;

    for ($col=$firstcol;$col<=$lastcol;$col++) {
        $this->_col_sizes[$col]   = $width;
        if ($format) {
            $this->_col_formats[$col] = $format;
        }
    }
}

###############################################################################
#
# set_selection()
#
# Set which cell or cells are selected in a worksheet: see also the
# function _store_selection
#
function set_selection() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    $this->_selection = $_;
}

###############################################################################
#
# freeze_panes()
#
# Set panes and mark them as frozen. See also _store_panes().
#
function freeze_panes() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    $this->_frozen = 1;
    $this->_panes  = $_;
}

###############################################################################
#
# thaw_panes()
#
# Set panes and mark them as unfrozen. See also _store_panes().
#
function thaw_panes() {

    $_=func_get_args();

    $this->_frozen = 0;
    $this->_panes  = $_;
}

    /*
     * Set the page orientation as portrait.
     */
    function set_portrait() {
        $this->_orientation = 1;
    }

    /*
     * Set the page orientation as landscape.
     */
    function set_landscape() {
        $this->_orientation = 0;
    }

    /*
     * Set the paper type. Ex. 1 = US Letter, 9 = A4
     */
    function set_paper($type) {
        $this->_paper_size = $type;
    }

    /*
     * Set the page header caption and optional margin.
     */
    function set_header($string, $margin) {

        if (strlen($string) >= 255) {
            trigger_error("Header string must be less than 255 characters",
                          E_USER_WARNING);
            return;
        }

        $this->_header      = $string;
        $this->_margin_head = $margin;
    }

    /*
     * Set the page footer caption and optional margin.
     */
    function set_footer($string, $margin) {
        if (strlen($string) >= 255) {
            trigger_error("Footer string must be less than 255 characters",
                          E_USER_WARNING);
            return;
        }

        $this->_footer      = $string;
        $this->_margin_foot = $margin;
    }

    /*
     * Center the page horizontally.
     */
    function center_horizontally($hcenter=1) {
        $this->_hcenter = $hcenter;
    }

    /*
     * Center the page horizontally.
     */
    function center_vertically($vcenter=1) {
        $this->_vcenter = $vcenter;
    }

    /*
     * Set all the page margins to the same value in inches.
     */
    function set_margins($margin) {
        $this->set_margin_left($margin);
        $this->set_margin_right($margin);
        $this->set_margin_top($margin);
        $this->set_margin_bottom($margin);
    }

    /*
     * Set the left and right margins to the same value in inches.
     */
    function set_margins_LR($margin) {
        $this->set_margin_left($margin);
        $this->set_margin_right($margin);
    }

    /*
     * Set the top and bottom margins to the same value in inches.
     */
    function set_margins_TB($margin) {
        $this->set_margin_top($margin);
        $this->set_margin_bottom($margin);
    }

    /*
     * Set the left margin in inches.
     */
    function set_margin_left($margin=0.75) {
        $this->_margin_left = $margin;
    }

    /*
     * Set the right margin in inches.
     */
    function set_margin_right($margin=0.75) {
        $this->_margin_right = $margin;
    }

    /*
     * Set the top margin in inches.
     */
    function set_margin_top($margin=1.00) {
        $this->_margin_top = $margin;
    }

    /*
     * Set the bottom margin in inches.
     */
    function set_margin_bottom($margin=1.00) {
        $this->_margin_bottom = $margin;
    }

###############################################################################
#
# repeat_rows($first_row, $last_row)
#
# Set the rows to repeat at the top of each printed page. See also the
# _store_name_xxxx() methods in Workbook.pm.
#
function repeat_rows() {

    $_=func_get_args();

    $this->_title_rowmin  = $_[0];
    $this->_title_rowmax  = isset($_[1]) ? $_[1] : $_[0]; # Second row is optional
}

###############################################################################
#
# repeat_columns($first_col, $last_col)
#
# Set the columns to repeat at the left hand side of each printed page.
# See also the _store_names() methods in Workbook.pm.
#
function repeat_columns() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    $this->_title_colmin  = $_[0];
    $this->_title_colmax  = isset($_[1]) ? $_[1] : $_[0]; # Second col is optional
}

###############################################################################
#
# print_area($first_row, $first_col, $last_row, $last_col)
#
# Set the area of each worksheet that will be printed. See also the
# _store_names() methods in Workbook.pm.
#
function print_area() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    if (sizeof($_) != 4) {
        # Require 4 parameters
        return;
    }

    $this->_print_rowmin = $_[0];
    $this->_print_colmin = $_[1];
    $this->_print_rowmax = $_[2];
    $this->_print_colmax = $_[3];
}

    /*
     * Set the option to hide gridlines on the screen and the printed page.
     * There are two ways of doing this in the Excel BIFF format: The first
     * is by setting the DspGrid field of the WINDOW2 record, this turns off
     * the screen and subsequently the print gridline. The second method is
     * to via the PRINTGRIDLINES and GRIDSET records, this turns off the
     * printed gridlines only. The first method is probably sufficient for
     * most cases. The second method is supported for backwards compatibility.
     */
    function hide_gridlines($option=1) {
        if ($option == 0) {
            $this->_print_gridlines  = 1; # 1 = display, 0 = hide
            $this->_screen_gridlines = 1;
        } elseif ($option == 1) {
            $this->_print_gridlines  = 0;
            $this->_screen_gridlines = 1;
        } else {
            $this->_print_gridlines  = 0;
            $this->_screen_gridlines = 0;
        }
    }

    /*
     * Set the option to print the row and column headers on the printed page.
     * See also the _store_print_headers() method below.
     */
    function print_row_col_headers($headers=1) {
        $this->_print_headers = $headers;
    }

    /*
     * Store the vertical and horizontal number of pages that will define
     * the maximum area printed. See also _store_setup() and _store_wsbool()
     * below.
     */
    function fit_to_pages($width, $height) {
        $this->_fit_page   = 1;
        $this->_fit_width  = $width;
        $this->_fit_height = $height;
    }

    /*
     * Store the horizontal page breaks on a worksheet.
     */
    function set_h_pagebreaks($breaks) {
        $this->_hbreaks=array_merge($this->_hbreaks, $breaks);
    }

    /*
     * Store the vertical page breaks on a worksheet.
     */
    function set_v_pagebreaks($breaks) {
        $this->_vbreaks=array_merge($this->_vbreaks, $breaks);
    }

    /*
     * Set the worksheet zoom factor.
     */
    function set_zoom($scale=100) {
        // Confine the scale to Excel's range
        if ($scale < 10 || $scale > 400) {
            trigger_error("Zoom factor $scale outside range: ".
                          "10 <= zoom <= 400", E_USER_WARNING);
            $scale = 100;
        }

        $this->_zoom = $scale;
    }

    /*
     * Set the scale factor for the printed page.
     */
    function set_print_scale($scale=100) {
        // Confine the scale to Excel's range
        if ($scale < 10 || $scale > 400) {
            trigger_error("Print scale $scale outside range: ".
                          "10 <= zoom <= 400", E_USER_WARNING);
            $scale = 100;
        }

        // Turn off "fit to page" option
        $this->_fit_page = 0;

        $this->_print_scale = $scale;
    }

###############################################################################
#
# write($row, $col, $token, $format)
#
# Parse $token call appropriate write method. $row and $column are zero
# indexed. $format is optional.
#
# Returns: return value of called subroutine
#
function write() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    $token = $_[2];

    # Match an array ref.
    if (is_array($token)) {
        return call_user_func_array(array(&$this, 'write_row'), $_);
    }

    # Match number
    if (preg_match('/^([+-]?)(?=\d|\.\d)\d*(\.\d*)?([Ee]([+-]?\d+))?$/', $token)) {
        return call_user_func_array(array(&$this, 'write_number'), $_);
    }
    # Match http, https or ftp URL
    elseif (preg_match('|^[fh]tt?ps?://|', $token)) {
        return call_user_func_array(array(&$this, 'write_url'), $_);
    }
    # Match mailto:
    elseif (preg_match('/^mailto:/', $token)) {
        return call_user_func_array(array(&$this, 'write_url'), $_);
    }
    # Match internal or external sheet link
    elseif (preg_match('[^(?:in|ex)ternal:]', $token)) {
        return call_user_func_array(array(&$this, 'write_url'), $_);
    }
    # Match formula
    elseif (preg_match('/^=/', $token)) {
        return call_user_func_array(array(&$this, 'write_formula'), $_);
    }
    # Match blank
    elseif ($token == '') {
        array_splice($_, 2, 1); # remove the empty string from the parameter list
        return call_user_func_array(array(&$this, 'write_blank'), $_);
    }
    # Default: match string
    else {
        return call_user_func_array(array(&$this, 'write_string'), $_);
    }
}

###############################################################################
#
# write_row($row, $col, $array_ref, $format)
#
# Write a row of data starting from ($row, $col). Call write_col() if any of
# the elements of the array ref are in turn array refs. This allows the writing
# of 1D or 2D arrays of data in one go.
#
# Returns: the first encountered error value or zero for no errors
#
function write_row() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    # Catch non array refs passed by user.
    if (!is_array($_[2])) {
        trigger_error("Not an array ref in call to write_row()!", E_USER_ERROR);
    }

    list($row, $col, $tokens)=array_splice($_, 0, 3);
    $options = $_[0];
    $error   = 0;

    foreach ($tokens as $token) {

        # Check for nested arrays
        if (is_array($token)) {
            $ret = $this->write_col($row, $col, $token, $options);
        } else {
            $ret = $this->write    ($row, $col, $token, $options);
        }

        # Return only the first error encountered, if any.
        $error = $error || $ret;
        $col++;
    }

    return $error;
}

###############################################################################
#
# _XF()
#
# Returns an index to the XF record in the workbook.
# TODO
#
# Note: this is a function, not a method.
#
function _XF($row=false, $col=false, $format=false) {

    if ($format) {
        return $format->get_xf_index();
    } elseif (isset($this->_row_formats[$row])) {
        return $this->_row_formats[$row]->get_xf_index();
    } elseif (isset($this->_col_formats[$col])) {
        return $this->_col_formats[$col]->get_xf_index();
    } else {
        return 0x0F;
    }
}

###############################################################################
#
# write_col($row, $col, $array_ref, $format)
#
# Write a column of data starting from ($row, $col). Call write_row() if any of
# the elements of the array ref are in turn array refs. This allows the writing
# of 1D or 2D arrays of data in one go.
#
# Returns: the first encountered error value or zero for no errors
#
function write_col() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    # Catch non array refs passed by user.
    if (!is_array($_[2])) {
        trigger_error("Not an array ref in call to write_row()!", E_USER_ERROR);
    }

    $row     = array_shift($_);
    $col     = array_shift($_);
    $tokens  = array_shift($_);
    $options = $_;

    $error   = 0;

    foreach ($tokens as $token) {

        # write() will deal with any nested arrays
        $ret = $this->write($row, $col, $token, $options);

        # Return only the first error encountered, if any.
        $error = $error || $ret;
        $row++;
    }

    return $error;
}

###############################################################################
###############################################################################
#
# Internal methods
#

###############################################################################
#
# _append(), overloaded.
#
# Store Worksheet data in memory using the base class _append() or to a
# temporary file, the default.
#
function _append($data) {

    if (func_num_args()>1) {
        trigger_error("writeexcel_worksheet::_append() ".
                      "called with more than one argument", E_USER_ERROR);
    }

    if ($this->_using_tmpfile) {

        if ($this->_debug) {
            print "worksheet::_append() called:";
            for ($c=0;$c<strlen($data);$c++) {
                if ($c%16==0) {
                    print "\n";
                }
                printf("%02X ", ord($data[$c]));
            }
            print "\n";
        }

        # Add CONTINUE records if necessary
        if (strlen($data) > $this->_limit) {
            $data = $this->_add_continue($data);
        }

        fputs($this->_filehandle, $data);
        $this->_datasize += strlen($data);
    } else {
        parent::_append($data);
    }
}

###############################################################################
#
# _substitute_cellref()
#
# Substitute an Excel cell reference in A1 notation for  zero based row and
# column values in an argument list.
#
# Ex: ("A4", "Hello") is converted to (3, 0, "Hello").
#
// Exactly one array must be passed!
function _substitute_cellref($_) {
    $cell = strtoupper(array_shift($_));

    # Convert a column range: 'A:A' or 'B:G'
    if (preg_match('/([A-I]?[A-Z]):([A-I]?[A-Z])/', $cell, $reg)) {
        list($dummy, $col1) =  $this->_cell_to_rowcol($reg[1] .'1'); # Add a dummy row
        list($dummy, $col2) =  $this->_cell_to_rowcol($reg[2] .'1'); # Add a dummy row
        return array_merge(array($col1, $col2), $_);
    }

    # Convert a cell range: 'A1:B7'
    if (preg_match('/\$?([A-I]?[A-Z]\$?\d+):\$?([A-I]?[A-Z]\$?\d+)/', $cell, $reg)) {
        list($row1, $col1) =  $this->_cell_to_rowcol($reg[1]);
        list($row2, $col2) =  $this->_cell_to_rowcol($reg[2]);
        return array_merge(array($row1, $col1, $row2, $col2), $_);
    }

    # Convert a cell reference: 'A1' or 'AD2000'
    if (preg_match('/\$?([A-I]?[A-Z]\$?\d+)/', $cell, $reg)) {
        list($row1, $col1) =  $this->_cell_to_rowcol($reg[1]);
        return array_merge(array($row1, $col1), $_);

    }

    trigger_error("Unknown cell reference $cell", E_USER_ERROR);
}

###############################################################################
#
# _cell_to_rowcol($cell_ref)
#
# Convert an Excel cell reference in A1 notation to a zero based row and column
# reference; converts C1 to (0, 2).
#
# Returns: row, column
#
# TODO use functions in Utility.pm
#
function _cell_to_rowcol($cell) {

    preg_match('/\$?([A-I]?[A-Z])\$?(\d+)/', $cell, $reg);

    $col     = $reg[1];
    $row     = $reg[2];

    # Convert base26 column string to number
    # All your Base are belong to us.
    $chars = preg_split('//', $col, -1, PREG_SPLIT_NO_EMPTY);
    $expn  = 0;
    $col      = 0;

    while (sizeof($chars)) {
        $char = array_pop($chars); # LS char first
        $col += (ord($char) -ord('A') +1) * pow(26, $expn);
        $expn++;
    }

    # Convert 1-index to zero-index
    $row--;
    $col--;

    return array($row, $col);
}

    /*
     * This is an internal method that is used to filter elements of the
     * array of pagebreaks used in the _store_hbreak() and _store_vbreak()
     * methods. It:
     *   1. Removes duplicate entries from the list.
     *   2. Sorts the list.
     *   3. Removes 0 from the list if present.
     */
    function _sort_pagebreaks($breaks) {
        // Hash slice to remove duplicates
        foreach ($breaks as $break) {
            $hash["$break"]=1;
        }

        // Numerical sort
        $breaks=array_keys($hash);
        sort($breaks, SORT_NUMERIC);

        // Remove zero
        if ($breaks[0] == 0) {
            array_shift($breaks);
        }

        // 1000 vertical pagebreaks appears to be an internal Excel 5 limit.
        // It is slightly higher in Excel 97/200, approx. 1026
        if (sizeof($breaks) > 1000) {
            array_splice($breaks, 1000);
        }

        return $breaks;
    }

    /*
     * Based on the algorithm provided by Daniel Rentz of OpenOffice.
     */
    function _encode_password($plaintext) {
        $chars=preg_split('//', $plaintext, -1, PREG_SPLIT_NO_EMPTY);
        $count=sizeof($chars);
        $i=0;

        for ($c=0;$c<sizeof($chars);$c++) {
            $char=&$chars[$c];
            $char    = ord($char) << ++$i;
            $low_15  = $char & 0x7fff;
            $high_15 = $char & 0x7fff << 15;
            $high_15 = $high_15 >> 15;
            $char    = $low_15 | $high_15;
        }

        $password = 0x0000;

        foreach ($chars as $char) {
            $password ^= $char;
        }

        $password ^= $count;
        $password ^= 0xCE4B;

        return $password;
    }

###############################################################################
###############################################################################
#
# BIFF RECORDS
#

###############################################################################
#
# write_number($row, $col, $num, $format)
#
# Write a double to the specified row and column (zero indexed).
# An integer can be written as a double. Excel will display an
# integer. $format is optional.
#
# Returns  0 : normal termination
#         -1 : insufficient number of arguments
#         -2 : row or column out of range
#
function write_number() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    # Check the number of args
    if (sizeof($_) < 3) {
        return -1;
    }

    $record  = 0x0203;                        # Record identifier
    $length  = 0x000E;                        # Number of bytes to follow

    $row     = $_[0];                         # Zero indexed row
    $col     = $_[1];                         # Zero indexed column
    $num     = $_[2];
//!!!
    $xf      = $this->_XF($row, $col, $_[3]); # The cell format

    # Check that row and col are valid and store max and min values
    if ($row >= $this->_xls_rowmax) { return -2; }
    if ($col >= $this->_xls_colmax) { return -2; }
    if ($row <  $this->_dim_rowmin) { $this->_dim_rowmin = $row; }
    if ($row >  $this->_dim_rowmax) { $this->_dim_rowmax = $row; }
    if ($col <  $this->_dim_colmin) { $this->_dim_colmin = $col; }
    if ($col >  $this->_dim_colmax) { $this->_dim_colmax = $col; }

    $header    = pack("vv",  $record, $length);
    $data      = pack("vvv", $row, $col, $xf);
    $xl_double = pack("d",   $num);

    if ($this->_byte_order) {
//TODO
        $xl_double = strrev($xl_double);
    }

    $this->_append($header . $data . $xl_double);

    return 0;
}

###############################################################################
#
# write_string ($row, $col, $string, $format)
#
# Write a string to the specified row and column (zero indexed).
# NOTE: there is an Excel 5 defined limit of 255 characters.
# $format is optional.
# Returns  0 : normal termination
#         -1 : insufficient number of arguments
#         -2 : row or column out of range
#         -3 : long string truncated to 255 chars
#
function write_string() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    # Check the number of args
    if (sizeof($_) < 3) {
        return -1;
    }

    $record  = 0x0204;                        # Record identifier
    $length  = 0x0008 + strlen($_[2]);        # Bytes to follow

    $row     = $_[0];                         # Zero indexed row
    $col     = $_[1];                         # Zero indexed column
    $strlen  = strlen($_[2]);
    $str     = $_[2];
    $xf      = $this->_XF($row, $col, $_[3]); # The cell format

    $str_error = 0;

    # Check that row and col are valid and store max and min values
    if ($row >= $this->_xls_rowmax) { return -2; }
    if ($col >= $this->_xls_colmax) { return -2; }
    if ($row <  $this->_dim_rowmin) { $this->_dim_rowmin = $row; }
    if ($row >  $this->_dim_rowmax) { $this->_dim_rowmax = $row; }
    if ($col <  $this->_dim_colmin) { $this->_dim_colmin = $col; }
    if ($col >  $this->_dim_colmax) { $this->_dim_colmax = $col; }

    if ($strlen > $this->_xls_strmax) { # LABEL must be < 255 chars
        $str       = substr($str, 0, $this->_xls_strmax);
        $length    = 0x0008 + $this->_xls_strmax;
        $strlen    = $this->_xls_strmax;
        $str_error = -3;
    }

    $header    = pack("vv",   $record, $length);
    $data      = pack("vvvv", $row, $col, $xf, $strlen);

    $this->_append($header . $data . $str);

    return $str_error;
}

###############################################################################
#
# write_blank($row, $col, $format)
#
# Write a blank cell to the specified row and column (zero indexed).
# A blank cell is used to specify formatting without adding a string
# or a number.
#
# A blank cell without a format serves no purpose. Therefore, we don't write
# a BLANK record unless a format is specified. This is mainly an optimisation
# for the write_row() and write_col() methods.
#
# Returns  0 : normal termination (including no format)
#         -1 : insufficient number of arguments
#         -2 : row or column out of range
#
function write_blank() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    # Check the number of args
    if (sizeof($_) < 2) {
        return -1;
    }

    # Don't write a blank cell unless it has a format
    if (!isset($_[2])) {
        return 0;
    }

    $record  = 0x0201;                        # Record identifier
    $length  = 0x0006;                        # Number of bytes to follow

    $row     = $_[0];                         # Zero indexed row
    $col     = $_[1];                         # Zero indexed column
    $xf      = $this->_XF($row, $col, $_[2]); # The cell format

    # Check that row and col are valid and store max and min values
    if ($row >= $this->_xls_rowmax) { return -2; }
    if ($col >= $this->_xls_colmax) { return -2; }
    if ($row <  $this->_dim_rowmin) { $this->_dim_rowmin = $row; }
    if ($row >  $this->_dim_rowmax) { $this->_dim_rowmax = $row; }
    if ($col <  $this->_dim_colmin) { $this->_dim_colmin = $col; }
    if ($col >  $this->_dim_colmax) { $this->_dim_colmax = $col; }

    $header    = pack("vv",  $record, $length);
    $data      = pack("vvv", $row, $col, $xf);

    $this->_append($header . $data);

    return 0;
}

###############################################################################
#
# write_formula($row, $col, $formula, $format)
#
# Write a formula to the specified row and column (zero indexed).
# The textual representation of the formula is passed to the parser in
# Formula.pm which returns a packed binary string.
#
# $format is optional.
#
# Returns  0 : normal termination
#         -1 : insufficient number of arguments
#         -2 : row or column out of range
#
function write_formula() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    # Check the number of args
    if (sizeof($_) < 3) {
        return -1;
    }

    $record    = 0x0006;     # Record identifier
    $length=0;                 # Bytes to follow

    $row       = $_[0];      # Zero indexed row
    $col       = $_[1];      # Zero indexed column
    $formula   = $_[2];      # The formula text string

    # Excel normally stores the last calculated value of the formula in $num.
    # Clearly we are not in a position to calculate this a priori. Instead
    # we set $num to zero and set the option flags in $grbit to ensure
    # automatic calculation of the formula when the file is opened.
    #
    $xf        = $this->_XF($row, $col, $_[3]); # The cell format
    $num       = 0x00;                          # Current value of formula
    $grbit     = 0x03;                          # Option flags
    $chn       = 0x0000;                        # Must be zero

    # Check that row and col are valid and store max and min values
    if ($row >= $this->_xls_rowmax) { return -2; }
    if ($col >= $this->_xls_colmax) { return -2; }
    if ($row <  $this->_dim_rowmin) { $this->_dim_rowmin = $row; }
    if ($row >  $this->_dim_rowmax) { $this->_dim_rowmax = $row; }
    if ($col <  $this->_dim_colmin) { $this->_dim_colmin = $col; }
    if ($col >  $this->_dim_colmax) { $this->_dim_colmax = $col; }

    # Strip the = sign at the beginning of the formula string
    $formula = preg_replace('/^=/', "", $formula);

    # Parse the formula using the parser in Formula.pm
    $parser =& $this->_parser;
    $formula   = $parser->parse_formula($formula);

    $formlen = strlen($formula); # Length of the binary string
    $length     = 0x16 + $formlen;  # Length of the record data

    $header    = pack("vv",      $record, $length);
    $data      = pack("vvvdvVv", $row, $col, $xf, $num,
                                  $grbit, $chn, $formlen);

    $this->_append($header . $data . $formula);

    return 0;
}

###############################################################################
#
# write_url($row, $col, $url, $string, $format)
#
# Write a hyperlink. This is comprised of two elements: the visible label and
# the invisible link. The visible label is the same as the link unless an
# alternative string is specified. The label is written using the
# write_string() method. Therefore the 255 characters string limit applies.
# $string and $format are optional and their order is interchangeable.
#
# The hyperlink can be to a http, ftp, mail, internal sheet, or external
# directory url.
#
# Returns  0 : normal termination
#         -1 : insufficient number of arguments
#         -2 : row or column out of range
#         -3 : long string truncated to 255 chars
#
function write_url() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    # Check the number of args
    if (sizeof($_) < 3) {
        return -1;
    }

    # Add start row and col to arg list
    return call_user_func_array(array(&$this, 'write_url_range'),
                                  array_merge(array($_[0], $_[1]), $_));
}

###############################################################################
#
# write_url_range($row1, $col1, $row2, $col2, $url, $string, $format)
#
# This is the more general form of write_url(). It allows a hyperlink to be
# written to a range of cells. This function also decides the type of hyperlink
# to be written. These are either, Web (http, ftp, mailto), Internal
# (Sheet1!A1) or external ('c:\temp\foo.xls#Sheet1!A1').
#
# See also write_url() above for a general description and return values.
#
function write_url_range() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    # Check the number of args
    if (sizeof($_) < 5) {
        return -1;
    }

    # Reverse the order of $string and $format if necessary.
//TODO    ($_[5], $_[6]) = ($_[6], $_[5]) if (ref $_[5]);

    $url = $_[4];

    # Check for internal/external sheet links or default to web link
    if (preg_match('[^internal:]', $url)) {
        return call_user_func_array(array(&$this, '_write_url_internal'), $_);
    }

    if (preg_match('[^external:]', $url)) {
        return call_user_func_array(array(&$this, '_write_url_external'), $_);
    }

    return call_user_func_array(array(&$this, '_write_url_web'), $_);
}

###############################################################################
#
# _write_url_web($row1, $col1, $row2, $col2, $url, $string, $format)
#
# Used to write http, ftp and mailto hyperlinks.
# The link type ($options) is 0x03 is the same as absolute dir ref without
# sheet. However it is differentiated by the $unknown2 data stream.
#
# See also write_url() above for a general description and return values.
#
function _write_url_web() {

    $_=func_get_args();

    $record      = 0x01B8;                       # Record identifier
    $length      = 0x00000;                      # Bytes to follow

    $row1        = $_[0];                        # Start row
    $col1        = $_[1];                        # Start column
    $row2        = $_[2];                        # End row
    $col2        = $_[3];                        # End column
    $url         = $_[4];                        # URL string
    if (isset($_[5])) {
        $str         = $_[5];                        # Alternative label
    }
    $xf          = $_[6] ? $_[6] : $this->_url_format;  # The cell format

    # Write the visible label using the write_string() method.
    if(!isset($str)) {
        $str            = $url;
    }

    $str_error   = $this->write_string($row1, $col1, $str, $xf);

    if ($str_error == -2) {
        return $str_error;
    }

    # Pack the undocumented parts of the hyperlink stream
    $unknown1    = pack("H*", "D0C9EA79F9BACE118C8200AA004BA90B02000000");
    $unknown2    = pack("H*", "E0C9EA79F9BACE118C8200AA004BA90B");

    # Pack the option flags
    $options     = pack("V", 0x03);

    # Convert URL to a null terminated wchar string
    $url            = join("\0", preg_split("''", $url, -1, PREG_SPLIT_NO_EMPTY));
    $url            = $url . "\0\0\0";

    # Pack the length of the URL
    $url_len     = pack("V", strlen($url));

    # Calculate the data length
    $length         = 0x34 + strlen($url);

    # Pack the header data
    $header      = pack("vv",   $record, $length);
    $data        = pack("vvvv", $row1, $row2, $col1, $col2);

    # Write the packed data
    $this->_append($header.
                   $data.
                   $unknown1.
                   $options.
                   $unknown2.
                   $url_len.
                   $url);

    return $str_error;
}

###############################################################################
#
# _write_url_internal($row1, $col1, $row2, $col2, $url, $string, $format)
#
# Used to write internal reference hyperlinks such as "Sheet1!A1".
#
# See also write_url() above for a general description and return values.
#
function _write_url_internal() {

    $_=func_get_args();

    $record      = 0x01B8;                       # Record identifier
    $length      = 0x00000;                      # Bytes to follow

    $row1        = $_[0];                        # Start row
    $col1        = $_[1];                        # Start column
    $row2        = $_[2];                        # End row
    $col2        = $_[3];                        # End column
    $url         = $_[4];                        # URL string
    if (isset($_[5])) {
        $str         = $_[5];                        # Alternative label
    }
    $xf          = $_[6] ? $_[6] : $this->_url_format;  # The cell format

    # Strip URL type
    $url = preg_replace('s[^internal:]', '', $url);

    # Write the visible label
    if (!isset($str)) {
        $str = $url;
    }
    $str_error   = $this->write_string($row1, $col1, $str, $xf);

    if ($str_error == -2) {
        return $str_error;
    }

    # Pack the undocumented parts of the hyperlink stream
    $unknown1    = pack("H*", "D0C9EA79F9BACE118C8200AA004BA90B02000000");

    # Pack the option flags
    $options     = pack("V", 0x08);

    # Convert the URL type and to a null terminated wchar string
    $url            = join("\0", preg_split("''", $url, -1, PREG_SPLIT_NO_EMPTY));
    $url            = $url . "\0\0\0";

    # Pack the length of the URL as chars (not wchars)
    $url_len     = pack("V", int(strlen($url)/2));

    # Calculate the data length
    $length         = 0x24 + strlen($url);

    # Pack the header data
    $header      = pack("vv",   $record, $length);
    $data        = pack("vvvv", $row1, $row2, $col1, $col2);

    # Write the packed data
    $this->_append($header.
                   $data.
                   $unknown1.
                   $options.
                   $url_len.
                   $url);

    return $str_error;
}

###############################################################################
#
# _write_url_external($row1, $col1, $row2, $col2, $url, $string, $format)
#
# Write links to external directory names such as 'c:\foo.xls',
# c:\foo.xls#Sheet1!A1', '../../foo.xls'. and '../../foo.xls#Sheet1!A1'.
#
# Note: Excel writes some relative links with the $dir_long string. We ignore
# these cases for the sake of simpler code.
#
# See also write_url() above for a general description and return values.
#
function _write_url_external() {

    $_=func_get_args();

    # Network drives are different. We will handle them separately
    # MS/Novell network drives and shares start with \\
    if (preg_match('[^external:\\\\]', $_[4])) {
        return call_user_func_array(array(&$this, '_write_url_external_net'), $_);
    }

    $record      = 0x01B8;                       # Record identifier
    $length      = 0x00000;                      # Bytes to follow

    $row1        = $_[0];                        # Start row
    $col1        = $_[1];                        # Start column
    $row2        = $_[2];                        # End row
    $col2        = $_[3];                        # End column
    $url         = $_[4];                        # URL string
    if (isset($_[5])) {
        $str         = $_[5];                        # Alternative label
    }
    $xf          = $_[6] ? $_[6] : $this->_url_format;  # The cell format

    # Strip URL type and change Unix dir separator to Dos style (if needed)
    #
    $url            = preg_replace('[^external:]', '', $url);
    $url            = preg_replace('[/]', "\\", $url);

    # Write the visible label
    if (!isset($str)) {
        $str = preg_replace('[\#]', ' - ', $url);
    }
    $str_error   = $this->write_string($row1, $col1, $str, $xf);
    if ($str_error == -2) {
        return $str_error;
    }

    # Determine if the link is relative or absolute:
    #   relative if link contains no dir separator, "somefile.xls"
    #   relative if link starts with up-dir, "..\..\somefile.xls"
    #   otherwise, absolute
    #
    $absolute    = 0x02; # Bit mask

    if (!preg_match('[\\]', $url)) {
        $absolute    = 0x00;
    }

    if (preg_match('[^\.\.\\]', $url)) {
        $absolute    = 0x00;
    }

    # Determine if the link contains a sheet reference and change some of the
    # parameters accordingly.
    # Split the dir name and sheet name (if it exists)
    #
    list($dir_long, $sheet) = preg_split('/\#/', $url);
    $link_type           = 0x01 | $absolute;

//!!!
    if (isset($sheet)) {
        $link_type |= 0x08;
        $sheet_len  = pack("V", length($sheet) + 0x01);
        $sheet      = join("\0", split('', $sheet));
        $sheet     .= "\0\0\0";
    } else {
        $sheet_len   = '';
        $sheet       = '';
    }

    # Pack the link type
    $link_type      = pack("V", $link_type);


    # Calculate the up-level dir count e.g.. (..\..\..\ == 3)
/* TODO
    $up_count    = 0;
    $up_count++       while $dir_long =~ s[^\.\.\\][];
    $up_count       = pack("v", $up_count);
*/

    # Store the short dos dir name (null terminated)
    $dir_short   = $dir_long . "\0";

    # Store the long dir name as a wchar string (non-null terminated)
    $dir_long       = join("\0", preg_split('', $dir_long, -1, PREG_SPLIT_NO_EMPTY));
    $dir_long       = $dir_long . "\0";

    # Pack the lengths of the dir strings
    $dir_short_len = pack("V", strlen($dir_short)      );
    $dir_long_len  = pack("V", strlen($dir_long)       );
    $stream_len    = pack("V", strlen($dir_long) + 0x06);

    # Pack the undocumented parts of the hyperlink stream
    $unknown1 =pack("H*",'D0C9EA79F9BACE118C8200AA004BA90B02000000'       );
    $unknown2 =pack("H*",'0303000000000000C000000000000046'               );
    $unknown3 =pack("H*",'FFFFADDE000000000000000000000000000000000000000');
    $unknown4 =pack("v",  0x03                                            );

    # Pack the main data stream
    $data        = pack("vvvv", $row1, $row2, $col1, $col2) .
                      $unknown1     .
                      $link_type    .
                      $unknown2     .
                      $up_count     .
                      $dir_short_len.
                      $dir_short    .
                      $unknown3     .
                      $stream_len   .
                      $dir_long_len .
                      $unknown4     .
                      $dir_long     .
                      $sheet_len    .
                      $sheet        ;

    # Pack the header data
    $length         = strlen($data);
    $header      = pack("vv",   $record, $length);

    # Write the packed data
    $this->_append($header . $data);

    return $str_error;
}

###############################################################################
#
# write_url_xxx($row1, $col1, $row2, $col2, $url, $string, $format)
#
# Write links to external MS/Novell network drives and shares such as
# '//NETWORK/share/foo.xls' and '//NETWORK/share/foo.xls#Sheet1!A1'.
#
# See also write_url() above for a general description and return values.
#
function _write_url_external_net() {

    $_=func_get_args();

    $record      = 0x01B8;                       # Record identifier
    $length      = 0x00000;                      # Bytes to follow

    $row1        = $_[0];                        # Start row
    $col1        = $_[1];                        # Start column
    $row2        = $_[2];                        # End row
    $col2        = $_[3];                        # End column
    $url         = $_[4];                        # URL string
    if(isset($_[5])) {
         $str         = $_[5];                        # Alternative label
    }
    $xf          = $_[6] ? $_[6] : $this->_url_format;  # The cell format

    # Strip URL type and change Unix dir separator to Dos style (if needed)
    #
    $url            = preg_replace('[^external:]', "", $url);
    $url            = preg_replace('[/]', "\\");

    # Write the visible label
    if (!isset($str)) {
        $str = preg_replace('[\#]', " - ", $url);
    }

    $str_error   = $this->write_string($row1, $col1, $str, $xf);
    if ($str_error == -2) {
        return $str_error;
    }

    # Determine if the link contains a sheet reference and change some of the
    # parameters accordingly.
    # Split the dir name and sheet name (if it exists)
    #
    list($dir_long , $sheet) = preg_split('\#', $url);
    $link_type           = 0x0103; # Always absolute

//!!!
    if (isset($sheet)) {
        $link_type |= 0x08;
        $sheet_len  = pack("V", strlen($sheet) + 0x01);
        $sheet      = join("\0", preg_split("''", $sheet, -1, PREG_SPLIT_NO_EMPTY));
        $sheet     .= "\0\0\0";
    } else {
        $sheet_len   = '';
        $sheet       = '';
    }

    # Pack the link type
    $link_type      = pack("V", $link_type);

    # Make the string null terminated
    $dir_long       = $dir_long . "\0";

    # Pack the lengths of the dir string
    $dir_long_len  = pack("V", strlen($dir_long));

    # Store the long dir name as a wchar string (non-null terminated)
    $dir_long       = join("\0", preg_split("''", $dir_long, -1, PREG_SPLIT_NO_EMPTY));
    $dir_long       = $dir_long . "\0";

    # Pack the undocumented part of the hyperlink stream
    $unknown1    = pack("H*",'D0C9EA79F9BACE118C8200AA004BA90B02000000');

    # Pack the main data stream
    $data        = pack("vvvv", $row1, $row2, $col1, $col2) .
                      $unknown1     .
                      $link_type    .
                      $dir_long_len .
                      $dir_long     .
                      $sheet_len    .
                      $sheet        ;

    # Pack the header data
    $length         = strlen($data);
    $header      = pack("vv",   $record, $length);

    # Write the packed data
    $this->_append($header . $data);

    return $str_error;
}

###############################################################################
#
# set_row($row, $height, $XF)
#
# This method is used to set the height and XF format for a row.
# Writes the  BIFF record ROW.
#
function set_row() {

    $_=func_get_args();

    $record      = 0x0208;               # Record identifier
    $length      = 0x0010;               # Number of bytes to follow

    $rw          = $_[0];                # Row Number
    $colMic      = 0x0000;               # First defined column
    $colMac      = 0x0000;               # Last defined column
    //$miyRw;                              # Row height
    $irwMac      = 0x0000;               # Used by Excel to optimise loading
    $reserved    = 0x0000;               # Reserved
    $grbit       = 0x01C0;               # Option flags. (monkey) see $1 do
    //$ixfe;                               # XF index
    if (isset($_[2])) {
        $format      = $_[2];                # Format object
    }

    # Check for a format object
    if (isset($_[2])) {
        $ixfe = $format->get_xf_index();
    } else {
        $ixfe = 0x0F;
    }

    # Use set_row($row, undef, $XF) to set XF without setting height
    if (isset($_[1])) {
        $miyRw = $_[1] *20;
    } else {
        $miyRw = 0xff;
    }

    $header   = pack("vv",       $record, $length);
    $data     = pack("vvvvvvvv", $rw, $colMic, $colMac, $miyRw,
                                 $irwMac,$reserved, $grbit, $ixfe);

    $this->_append($header . $data);

    # Store the row sizes for use when calculating image vertices.
    # Also store the column formats.
    #
    # Ensure at least $row and $height
    if (sizeof($_) < 2) {
        return;
    }

    $this->_row_sizes[$_[0]]  = $_[1];
    if (isset($_[2])) {
        $this->_row_formats[$_[0]] = $_[2];
    }
}

    /*
     * Writes Excel DIMENSIONS to define the area in which there is data.
     */
    function _store_dimensions() {
        $record    = 0x0000;               // Record identifier
        $length    = 0x000A;               // Number of bytes to follow
        $row_min   = $this->_dim_rowmin;   // First row
        $row_max   = $this->_dim_rowmax;   // Last row plus 1
        $col_min   = $this->_dim_colmin;   // First column
        $col_max   = $this->_dim_colmax;   // Last column plus 1
        $reserved  = 0x0000;               // Reserved by Excel

        $header    = pack("vv",    $record, $length);
        $data      = pack("vvvvv", $row_min, $row_max,
                                   $col_min, $col_max, $reserved);
        $this->_prepend($header . $data);
    }

    /*
     * Write BIFF record Window2.
     */
    function _store_window2() {
        $record         = 0x023E;       // Record identifier
        $length         = 0x000A;       // Number of bytes to follow

        $grbit          = 0x00B6;       // Option flags
        $rwTop          = 0x0000;       // Top row visible in window
        $colLeft        = 0x0000;       // Leftmost column visible in window
        $rgbHdr         = 0x00000000;   // Row/column heading and gridline
                                        // color

        // The options flags that comprise $grbit
        $fDspFmla       = 0;                          // 0 - bit
        $fDspGrid       = $this->_screen_gridlines;   // 1
        $fDspRwCol      = 1;                          // 2
        $fFrozen        = $this->_frozen;             // 3
        $fDspZeros      = 1;                          // 4
        $fDefaultHdr    = 1;                          // 5
        $fArabic        = 0;                          // 6
        $fDspGuts       = 1;                          // 7
        $fFrozenNoSplit = 0;                          // 0 - bit
        $fSelected      = $this->_selected;           // 1
        $fPaged         = 1;                          // 2

        $grbit             = $fDspFmla;
        $grbit            |= $fDspGrid       << 1;
        $grbit            |= $fDspRwCol      << 2;
        $grbit            |= $fFrozen        << 3;
        $grbit            |= $fDspZeros      << 4;
        $grbit            |= $fDefaultHdr    << 5;
        $grbit            |= $fArabic        << 6;
        $grbit            |= $fDspGuts       << 7;
        $grbit            |= $fFrozenNoSplit << 8;
        $grbit            |= $fSelected      << 9;
        $grbit            |= $fPaged         << 10;

        $header  = pack("vv",   $record, $length);
        $data    = pack("vvvV", $grbit, $rwTop, $colLeft, $rgbHdr);

        $this->_append($header . $data);
    }

    /*
     * Write BIFF record DEFCOLWIDTH if COLINFO records are in use.
     */
    function _store_defcol() {
        $record   = 0x0055;   // Record identifier
        $length   = 0x0002;   // Number of bytes to follow

        $colwidth = 0x0008;   // Default column width

        $header   = pack("vv", $record, $length);
        $data     = pack("v",  $colwidth);

        $this->_prepend($header . $data);
    }

###############################################################################
#
# _store_colinfo($firstcol, $lastcol, $width, $format, $hidden)
#
# Write BIFF record COLINFO to define column widths
#
# Note: The SDK says the record length is 0x0B but Excel writes a 0x0C
# length record.
#
function _store_colinfo($_) {

    $record   = 0x007D;          # Record identifier
    $length   = 0x000B;          # Number of bytes to follow

    $colFirst = $_[0] ? $_[0] : 0;      # First formatted column
    $colLast  = $_[1] ? $_[1] : 0;      # Last formatted column
    $coldx    = $_[2] ? $_[2] : 8.43;   # Col width, 8.43 is Excel default

    $coldx       += 0.72;           # Fudge. Excel subtracts 0.72 !?
    $coldx       *= 256;            # Convert to units of 1/256 of a char

    //$ixfe;                       # XF index
    $grbit    = $_[4] || 0;      # Option flags
    $reserved = 0x00;            # Reserved
    $format   = $_[3];           # Format object

    # Check for a format object
    if (isset($_[3])) {
        $ixfe = $format->get_xf_index();
    } else {
        $ixfe = 0x0F;
    }

    $header   = pack("vv",     $record, $length);
    $data     = pack("vvvvvC", $colFirst, $colLast, $coldx,
                               $ixfe, $grbit, $reserved);
    $this->_prepend($header . $data);
}

###############################################################################
#
# _store_selection($first_row, $first_col, $last_row, $last_col)
#
# Write BIFF record SELECTION.
#
function _store_selection($_) {

    $record   = 0x001D;                  # Record identifier
    $length   = 0x000F;                  # Number of bytes to follow

    $pnn      = $this->_active_pane;     # Pane position
    $rwAct    = $_[0];                   # Active row
    $colAct   = $_[1];                   # Active column
    $irefAct  = 0;                       # Active cell ref
    $cref     = 1;                       # Number of refs

    $rwFirst  = $_[0];                   # First row in reference
    $colFirst = $_[1];                   # First col in reference
    $rwLast   = $_[2] ? $_[2] : $rwFirst;       # Last  row in reference
    $colLast  = $_[3] ? $_[3] : $colFirst;      # Last  col in reference

    # Swap last row/col for first row/col as necessary
    if ($rwFirst > $rwLast) {
        list($rwFirst, $rwLast) = array($rwLast, $rwFirst);
    }

    if ($colFirst > $colLast) {
        list($colFirst, $colLast) = array($colLast, $colFirst);
    }

    $header   = pack("vv",           $record, $length);
    $data     = pack("CvvvvvvCC",    $pnn, $rwAct, $colAct,
                                     $irefAct, $cref,
                                     $rwFirst, $rwLast,
                                     $colFirst, $colLast);

    $this->_append($header . $data);
}

    /*
     * Write BIFF record EXTERNCOUNT to indicate the number of external
     * sheet references in a worksheet.
     *
     * Excel only stores references to external sheets that are used in
     * formulas. For simplicity we store references to all the sheets in
     * the workbook regardless of whether they are used or not. This reduces
     * the overall complexity and eliminates the need for a two way dialogue
     * between the formula parser the worksheet objects.
     */
    function _store_externcount($cxals) {
        // $cxals   Number of external references

        $record   = 0x0016;   // Record identifier
        $length   = 0x0002;   // Number of bytes to follow

        $header   = pack("vv", $record, $length);
        $data     = pack("v",  $cxals);

        $this->_prepend($header . $data);
    }

    /*
     * Writes the Excel BIFF EXTERNSHEET record. These references are used
     * by formulas. A formula references a sheet name via an index. Since we
     * store a reference to all of the external worksheets the EXTERNSHEET
     * index is the same as the worksheet index.
     */
    function _store_externsheet($sheetname) {
        $record    = 0x0017;         # Record identifier
        // $length   Number of bytes to follow

        // $cch      Length of sheet name
        // $rgch     Filename encoding

        // References to the current sheet are encoded differently to
        // references to external sheets.
        if ($this->_name == $sheetname) {
            $sheetname = '';
            $length    = 0x02;  // The following 2 bytes
            $cch       = 1;     // The following byte
            $rgch      = 0x02;  // Self reference
        } else {
            $length    = 0x02 + strlen($sheetname);
            $cch       = strlen($sheetname);
            $rgch      = 0x03;  // Reference to a sheet in the current
                                // workbook
        }

        $header     = pack("vv",  $record, $length);
        $data       = pack("CC", $cch, $rgch);

        $this->_prepend($header . $data . $sheetname);
    }

###############################################################################
#
# _store_panes()
#
#
# Writes the Excel BIFF PANE record.
# The panes can either be frozen or thawed (unfrozen).
# Frozen panes are specified in terms of a integer number of rows and columns.
# Thawed panes are specified in terms of Excel's units for rows and columns.
#
function _store_panes($_) {

    $record  = 0x0041;       # Record identifier
    $length  = 0x000A;       # Number of bytes to follow

    $y       = $_[0] ? $_[0] : 0;   # Vertical split position
    $x       = $_[1] ? $_[1] : 0;   # Horizontal split position
    if (isset($_[2])) {
        $rwTop   = $_[2];        # Top row visible
    }
    if (isset($_[3])) {
        $colLeft = $_[3];        # Leftmost column visible
    }
    if (isset($_[4])) {
        $pnnAct  = $_[4];        # Active pane
    }

    # Code specific to frozen or thawed panes.
    if ($this->_frozen) {
        # Set default values for $rwTop and $colLeft
        if (!isset($rwTop)) {
            $rwTop   = $y;
        }
        if (!isset($colLeft)) {
            $colLeft = $x;
        }
    } else {
        # Set default values for $rwTop and $colLeft
        if (!isset($rwTop)) {
            $rwTop   = 0;
        }
        if (!isset($colLeft)) {
            $colLeft = 0;
        }

        # Convert Excel's row and column units to the internal units.
        # The default row height is 12.75
        # The default column width is 8.43
        # The following slope and intersection values were interpolated.
        #
        $y = 20*$y      + 255;
        $x = 113.879*$x + 390;
    }

    # Determine which pane should be active. There is also the undocumented
    # option to override this should it be necessary: may be removed later.
    #
    if (!isset($pnnAct)) {
        # Bottom right
        if ($x != 0 && $y != 0) {
            $pnnAct = 0;
        }
        # Top right
        if ($x != 0 && $y == 0) {
            $pnnAct = 1;
        }
        # Bottom left
        if ($x == 0 && $y != 0) {
            $pnnAct = 2;
        }
        # Top left
        if ($x == 0 && $y == 0) {
            $pnnAct = 3;
        }
    }

    $this->_active_pane = $pnnAct; # Used in _store_selection

    $header     = pack("vv",    $record, $length);
    $data       = pack("vvvvv", $x, $y, $rwTop, $colLeft, $pnnAct);

    $this->_append($header . $data);
}

    /*
     * Store the page setup SETUP BIFF record.
     */
    function _store_setup() {
        $record       = 0x00A1;                // Record identifier
        $length       = 0x0022;                // Number of bytes to follow

        $iPaperSize   = $this->_paper_size;    // Paper size
        $iScale       = $this->_print_scale;   // Print scaling factor
        $iPageStart   = 0x01;                  // Starting page number
        $iFitWidth    = $this->_fit_width;     // Fit to number of pages wide
        $iFitHeight   = $this->_fit_height;    // Fit to number of pages high
        $grbit        = 0x00;                  // Option flags
        $iRes         = 0x0258;                // Print resolution
        $iVRes        = 0x0258;                // Vertical print resolution
        $numHdr       = $this->_margin_head;   // Header Margin
        $numFtr       = $this->_margin_foot;   // Footer Margin
        $iCopies      = 0x01;                  // Number of copies

        $fLeftToRight = 0x0;                   // Print over then down
        $fLandscape   = $this->_orientation;   // Page orientation
        $fNoPls       = 0x0;                   // Setup not read from printer
        $fNoColor     = 0x0;                   // Print black and white
        $fDraft       = 0x0;                   // Print draft quality
        $fNotes       = 0x0;                   // Print notes
        $fNoOrient    = 0x0;                   // Orientation not set
        $fUsePage     = 0x0;                   // Use custom starting page

        $grbit        = $fLeftToRight;
        $grbit       |= $fLandscape    << 1;
        $grbit       |= $fNoPls        << 2;
        $grbit       |= $fNoColor      << 3;
        $grbit       |= $fDraft        << 4;
        $grbit       |= $fNotes        << 5;
        $grbit       |= $fNoOrient     << 6;
        $grbit       |= $fUsePage      << 7;

        $numHdr = pack("d", $numHdr);
        $numFtr = pack("d", $numFtr);

        if ($this->_byte_order) {
            $numHdr = strrev($numHdr);
            $numFtr = strrev($numFtr);
        }

        $header = pack("vv",         $record, $length);
        $data1  = pack("vvvvvvvv",   $iPaperSize,
                                     $iScale,
                                     $iPageStart,
                                     $iFitWidth,
                                     $iFitHeight,
                                     $grbit,
                                     $iRes,
                                     $iVRes);
        $data2  = $numHdr . $numFtr;
        $data3  = pack("v", $iCopies);

        $this->_prepend($header . $data1 . $data2 . $data3);
    }

    /*
     * Store the header caption BIFF record.
     */
    function _store_header() {
        $record  = 0x0014;           // Record identifier

        $str     = $this->_header;   // header string
        $cch     = strlen($str);     // Length of header string
        $length  = 1 + $cch;         // Bytes to follow

        $header  = pack("vv",  $record, $length);
        $data    = pack("C",   $cch);

        $this->_append($header . $data . $str);
    }

    /*
     * Store the footer caption BIFF record.
     */
    function _store_footer() {
        $record  = 0x0015;           // Record identifier

        $str     = $this->_footer;   // Footer string
        $cch     = strlen($str);     // Length of footer string
        $length  = 1 + $cch;         // Bytes to follow

        $header  = pack("vv",  $record, $length);
        $data    = pack("C",   $cch);

        $this->_append($header . $data . $str);
    }

    /*
     * Store the horizontal centering HCENTER BIFF record.
     */
    function _store_hcenter() {
        $record   = 0x0083;   // Record identifier
        $length   = 0x0002;   // Bytes to follow

        $fHCenter = $this->_hcenter;   // Horizontal centering

        $header   = pack("vv",  $record, $length);
        $data     = pack("v",   $fHCenter);

        $this->_append($header . $data);
    }

     /*
      * Store the vertical centering VCENTER BIFF record.
      */
    function _store_vcenter() {
        $record   = 0x0084;   // Record identifier
        $length   = 0x0002;   // Bytes to follow

        $fVCenter = $this->_vcenter;   // Horizontal centering

        $header   = pack("vv",  $record, $length);
        $data     = pack("v",   $fVCenter);

        $this->_append($header . $data);
    }

    /*
     * Store the LEFTMARGIN BIFF record.
     */
    function _store_margin_left() {
        $record  = 0x0026;   // Record identifier
        $length  = 0x0008;   // Bytes to follow

        $margin  = $this->_margin_left;   // Margin in inches

        $header  = pack("vv",  $record, $length);
        $data    = pack("d",   $margin);

        if ($this->_byte_order) {
            $data = strrev($data);
        }

        $this->_append($header . $data);
    }

    /*
     * Store the RIGHTMARGIN BIFF record.
     */
    function _store_margin_right() {
        $record  = 0x0027;   // Record identifier
        $length  = 0x0008;   // Bytes to follow

        $margin  = $this->_margin_right;   // Margin in inches

        $header  = pack("vv",  $record, $length);
        $data    = pack("d",   $margin);

        if ($this->_byte_order) {
            $data = strrev($data);
        }

        $this->_append($header . $data);
    }

    /*
     * Store the TOPMARGIN BIFF record.
     */
    function _store_margin_top() {
        $record  = 0x0028;   // Record identifier
        $length  = 0x0008;   // Bytes to follow

        $margin  = $this->_margin_top;   // Margin in inches

        $header  = pack("vv",  $record, $length);
        $data    = pack("d",   $margin);

        if ($this->_byte_order) {
            $data = strrev($data);
        }

        $this->_append($header . $data);
    }

    /*
     * Store the BOTTOMMARGIN BIFF record.
     */
    function _store_margin_bottom() {
        $record  = 0x0029;   // Record identifier
        $length  = 0x0008;   // Bytes to follow

        $margin  = $this->_margin_bottom;   // Margin in inches

        $header  = pack("vv",  $record, $length);
        $data    = pack("d",   $margin);

        if ($this->_byte_order) {
            $data = strrev($data);
        }

        $this->_append($header . $data);
    }

###############################################################################
#
# merge_cells($first_row, $first_col, $last_row, $last_col)
#
# This is an Excel97/2000 method. It is required to perform more complicated
# merging than the normal align merge in Format.pm
#
function merge_cells() {

    $_=func_get_args();

    // Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    $record  = 0x00E5;                   # Record identifier
    $length  = 0x000A;                   # Bytes to follow

    $cref     = 1;                       # Number of refs
    $rwFirst  = $_[0];                   # First row in reference
    $colFirst = $_[1];                   # First col in reference
    $rwLast   = $_[2] ? $_[2] : $rwFirst;       # Last  row in reference
    $colLast  = $_[3] ? $_[3] : $colFirst;      # Last  col in reference

    // Swap last row/col for first row/col as necessary
    if ($rwFirst > $rwLast) {
        list($rwFirst, $rwLast) = array($rwLast, $rwFirst);
    }

    if ($colFirst > $colLast) {
        list($colFirst, $colLast) = array($colLast, $colFirst);
    }

    $header   = pack("vv",       $record, $length);
    $data     = pack("vvvvv",    $cref,
                                 $rwFirst, $rwLast,
                                 $colFirst, $colLast);

    $this->_append($header . $data);
}

    /*
     * Write the PRINTHEADERS BIFF record.
     */
    function _store_print_headers() {
        $record      = 0x002a;   // Record identifier
        $length      = 0x0002;   // Bytes to follow

        $fPrintRwCol = $this->_print_headers;   // Boolean flag

        $header      = pack("vv",  $record, $length);
        $data        = pack("v",   $fPrintRwCol);

        $this->_prepend($header . $data);
    }

    /*
     * Write the PRINTGRIDLINES BIFF record. Must be used in conjunction
     * with the GRIDSET record.
     */
    function _store_print_gridlines() {
        $record      = 0x002b;   // Record identifier
        $length      = 0x0002;   // Bytes to follow

        $fPrintGrid  = $this->_print_gridlines;   // Boolean flag

        $header      = pack("vv",  $record, $length);
        $data        = pack("v",   $fPrintGrid);

        $this->_prepend($header . $data);
    }

    /*
     * Write the GRIDSET BIFF record. Must be used in conjunction with the
     * PRINTGRIDLINES record.
     */
    function _store_gridset() {
        $record      = 0x0082;   // Record identifier
        $length      = 0x0002;   // Bytes to follow

        $fGridSet    = !$this->_print_gridlines;   // Boolean flag

        $header      = pack("vv",  $record, $length);
        $data        = pack("v",   $fGridSet);

        $this->_prepend($header . $data);
    }

    /*
     * Write the WSBOOL BIFF record, mainly for fit-to-page. Used in
     * conjunction with the SETUP record.
     */
    function _store_wsbool() {
        $record      = 0x0081;   # Record identifier
        $length      = 0x0002;   # Bytes to follow

        // $grbit   Option flags

        // The only option that is of interest is the flag for fit to page.
        // So we set all the options in one go.
        if ($this->_fit_page) {
            $grbit = 0x05c1;
        } else {
            $grbit = 0x04c1;
        }

        $header      = pack("vv",  $record, $length);
        $data        = pack("v",   $grbit);

        $this->_prepend($header . $data);
    }

    /*
     * Write the HORIZONTALPAGEBREAKS BIFF record.
     */
    function _store_hbreak() {
        // Return if the user hasn't specified pagebreaks
        if(sizeof($this->_hbreaks)==0) {
            return;
        }

        # Sort and filter array of page breaks
        $breaks  = $this->_sort_pagebreaks($this->_hbreaks);

        $record  = 0x001b;             // Record identifier
        $cbrk    = sizeof($breaks);    // Number of page breaks
        $length  = ($cbrk + 1) * 2;    // Bytes to follow

        $header  = pack("vv",  $record, $length);
        $data    = pack("v",   $cbrk);

        // Append each page break
        foreach ($breaks as $break) {
            $data .= pack("v", $break);
        }

        $this->_prepend($header . $data);
    }

    /*
     * Write the VERTICALPAGEBREAKS BIFF record.
     */
    function _store_vbreak() {
        // Return if the user hasn't specified pagebreaks
        if(sizeof($this->_vbreaks)==0) {
            return;
        }

        // Sort and filter array of page breaks
        $breaks  = $this->_sort_pagebreaks($this->_vbreaks);

        $record  = 0x001a;            // Record identifier
        $cbrk    = sizeof($breaks);   // Number of page breaks
        $length  = ($cbrk + 1) * 2;   // Bytes to follow

        $header  = pack("vv",  $record, $length);
        $data    = pack("v",   $cbrk);

        // Append each page break
        foreach ($breaks as $break) {
            $data .= pack("v", $break);
        }

        $this->_prepend($header . $data);
    }

    /*
     * Set the Biff PROTECT record to indicate that the worksheet is
     * protected.
     */
    function _store_protect() {
        // Exit unless sheet protection has been specified
        if (!$this->_protect) {
            return;
        }

        $record      = 0x0012;            // Record identifier
        $length      = 0x0002;            // Bytes to follow

        $fLock       = $this->_protect;   // Worksheet is protected

        $header      = pack("vv", $record, $length);
        $data        = pack("v",  $fLock);

        $this->_prepend($header . $data);
    }

    /*
     * Write the worksheet PASSWORD record.
     */
    function _store_password() {
        // Exit unless sheet protection and password have been specified
        if (!$this->_protect || !$this->_password) {
            return;
        }

        $record      = 0x0013;             // Record identifier
        $length      = 0x0002;             // Bytes to follow

        $wPassword   = $this->_password;   // Encoded password

        $header      = pack("vv", $record, $length);
        $data        = pack("v",  $wPassword);

        $this->_prepend($header . $data);
    }

###############################################################################
#
# insert_bitmap($row, $col, $filename, $x, $y, $scale_x, $scale_y)
#
# Insert a 24bit bitmap image in a worksheet. The main record required is
# IMDATA but it must be proceeded by a OBJ record to define its position.
#
function insert_bitmap() {

    $_=func_get_args();

    # Check for a cell reference in A1 notation and substitute row and column
    if (preg_match('/^\D/', $_[0])) {
        $_ = $this->_substitute_cellref($_);
    }

    $row         = $_[0];
    $col         = $_[1];
    $bitmap      = $_[2];
    $x           = $_[3] ? $_[3] : 0;
    $y           = $_[4] ? $_[4] : 0;
    $scale_x     = $_[5] ? $_[5] : 1;
    $scale_y     = $_[6] ? $_[6] : 1;

    list($width, $height, $size, $data) = $this->_process_bitmap($bitmap);

    # Scale the frame of the image.
    $width  *= $scale_x;
    $height *= $scale_y;

    # Calculate the vertices of the image and write the OBJ record
    $this->_position_image($col, $row, $x, $y, $width, $height);

    # Write the IMDATA record to store the bitmap data
    $record      = 0x007f;
    $length      = 8 + $size;
    $cf          = 0x09;
    $env         = 0x01;
    $lcb         = $size;

    $header      = pack("vvvvV", $record, $length, $cf, $env, $lcb);

    $this->_append($header . $data);
}

    /*
     * Calculate the vertices that define the position of the image as
     * required by the OBJ record.
     *
     *        +------------+------------+
     *        |     A      |      B     |
     *  +-----+------------+------------+
     *  |     |(x1,y1)     |            |
     *  |  1  |(A1)._______|______      |
     *  |     |    |              |     |
     *  |     |    |              |     |
     *  +-----+----|    BITMAP    |-----+
     *  |     |    |              |     |
     *  |  2  |    |______________.     |
     *  |     |            |        (B2)|
     *  |     |            |     (x2,y2)|
     *  +---- +------------+------------+
     *
     * Example of a bitmap that covers some of the area from cell A1 to
     * cell B2.
     *
     * Based on the width and height of the bitmap we need to calculate 8
     *vars:
     *    $col_start, $row_start, $col_end, $row_end, $x1, $y1, $x2, $y2.
     * The width and height of the cells are also variable and have to be
     * taken into account.
     * The values of $col_start and $row_start are passed in from the calling
     * function. The values of $col_end and $row_end are calculated by
     * subtracting the width and height of the bitmap from the width and
     * height of the underlying cells.
     * The vertices are expressed as a percentage of the underlying cell
     * width as follows (rhs values are in pixels):
     *
     *      x1 = X / W *1024
     *      y1 = Y / H *256
     *      x2 = (X-1) / W *1024
     *      y2 = (Y-1) / H *256
     *
     *      Where:  X is distance from the left side of the underlying cell
     *              Y is distance from the top of the underlying cell
     *              W is the width of the cell
     *              H is the height of the cell
     *
     * Note: the SDK incorrectly states that the height should be expressed
     * as a percentage of 1024.
     */
    function _position_image($col_start, $row_start, $x1, $y1,
                             $width, $height) {
        // $col_start   Col containing upper left corner of object
        // $x1          Distance to left side of object

        // $row_start   Row containing top left corner of object
        // $y1          Distance to top of object

        // $col_end     Col containing lower right corner of object
        // $x2          Distance to right side of object

        // $row_end     Row containing bottom right corner of object
        // $y2          Distance to bottom of object

        // $width       Width of image frame
        // $height      Height of image frame

        // Initialise end cell to the same as the start cell
        $col_end = $col_start;
        $row_end = $row_start;

        // Zero the specified offset if greater than the cell dimensions
        if ($x1 >= $this->_size_col($col_start)) {
            $x1 = 0;
        }
        if ($y1 >= $this->_size_row($row_start)) {
            $y1 = 0;
        }

        $width  = $width  + $x1 -1;
        $height = $height + $y1 -1;

        // Subtract the underlying cell widths to find the end cell of the
        // image
        while ($width >= $this->_size_col($col_end)) {
            $width -= $this->_size_col($col_end);
            $col_end++;
        }

        // Subtract the underlying cell heights to find the end cell of the
        // image
        while ($height >= $this->_size_row($row_end)) {
            $height -= $this->_size_row($row_end);
            $row_end++;
        }

        // Bitmap isn't allowed to start or finish in a hidden cell, i.e. a
        // cell with zero height or width.
        if ($this->_size_col($col_start) == 0) { return; }
        if ($this->_size_col($col_end)   == 0) { return; }
        if ($this->_size_row($row_start) == 0) { return; }
        if ($this->_size_row($row_end)   == 0) { return; }

        // Convert the pixel values to the percentage value expected by Excel
        $x1 = $x1     / $this->_size_col($col_start) * 1024;
        $y1 = $y1     / $this->_size_row($row_start) *  256;
        $x2 = $width  / $this->_size_col($col_end)   * 1024;
        $y2 = $height / $this->_size_row($row_end)   *  256;

        $this->_store_obj_picture($col_start, $x1, $row_start, $y1,
                                  $col_end, $x2, $row_end, $y2);
    }

    /*
     * Convert the width of a cell from user's units to pixels. By
     * interpolation the relationship is: y = 7x +5. If the width
     * hasn't been set by the user we use the default value. If the
     * col is hidden we use a value of zero.
     */
    function _size_col($col) {
        // Look up the cell value to see if it has been changed
        if (isset($this->_col_sizes[$col])) {
            if ($this->_col_sizes[$col] == 0) {
                return 0;
            } else {
                return floor(7 * $this->_col_sizes[$col] + 5);
            }
        } else {
            return 64;
        }
    }

    /*
     * Convert the height of a cell from user's units to pixels. By
     * interpolation # the relationship is: y = 4/3x. If the height
     * hasn't been set by the user we use the default value. If the
     * row is hidden we use a value of zero. (Not possible to hide row
     * yet).
     */
    function _size_row($row) {
        // Look up the cell value to see if it has been changed
        if (isset($this->_row_sizes[$row])) {
            if ($this->_row_sizes[$row] == 0) {
                return 0;
            } else {
                return floor(4/3 * $this->_row_sizes[$row]);
            }
        } else {
            return 17;
        }
    }

    /*
     * Store the OBJ record that precedes an IMDATA record. This could
     * be generalized to support other Excel objects.
     */
    function _store_obj_picture($col_start, $x1, $row_start, $y1,
                                $col_end, $x2, $row_end, $y2) {
        $record      = 0x005d;       // Record identifier
        $length      = 0x003c;       // Bytes to follow

        $cObj        = 0x0001;       // Count of objects in file (set to 1)
        $OT          = 0x0008;       // Object type. 8 = Picture
        $id          = 0x0001;       // Object ID
        $grbit       = 0x0614;       // Option flags

        $colL        = $col_start;   // Col containing upper left corner of
                                     // object
        $dxL         = $x1;          // Distance from left side of cell

        $rwT         = $row_start;   // Row containing top left corner of
                                     // object
        $dyT         = $y1;          // Distance from top of cell

        $colR        = $col_end;     // Col containing lower right corner of 
                                     // object
        $dxR         = $x2;          // Distance from right of cell

        $rwB         = $row_end;     // Row containing bottom right corner of
                                     // object
        $dyB         = $y2;          // Distance from bottom of cell

        $cbMacro     = 0x0000;       // Length of FMLA structure
        $Reserved1   = 0x0000;       // Reserved
        $Reserved2   = 0x0000;       // Reserved

        $icvBack     = 0x09;         // Background colour
        $icvFore     = 0x09;         // Foreground colour
        $fls         = 0x00;         // Fill pattern
        $fAuto       = 0x00;         // Automatic fill
        $icv         = 0x08;         // Line colour
        $lns         = 0xff;         // Line style
        $lnw         = 0x01;         // Line weight
        $fAutoB      = 0x00;         // Automatic border
        $frs         = 0x0000;       // Frame style
        $cf          = 0x0009;       // Image format, 9 = bitmap
        $Reserved3   = 0x0000;       // Reserved
        $cbPictFmla  = 0x0000;       // Length of FMLA structure
        $Reserved4   = 0x0000;       // Reserved
        $grbit2      = 0x0001;       // Option flags
        $Reserved5   = 0x0000;       // Reserved

        $header      = pack("vv", $record, $length);
        $data        = pack("V",  $cObj);
        $data       .= pack("v",  $OT);
        $data       .= pack("v",  $id);
        $data       .= pack("v",  $grbit);
        $data       .= pack("v",  $colL);
        $data       .= pack("v",  $dxL);
        $data       .= pack("v",  $rwT);
        $data       .= pack("v",  $dyT);
        $data       .= pack("v",  $colR);
        $data       .= pack("v",  $dxR);
        $data       .= pack("v",  $rwB);
        $data       .= pack("v",  $dyB);
        $data       .= pack("v",  $cbMacro);
        $data       .= pack("V",  $Reserved1);
        $data       .= pack("v",  $Reserved2);
        $data       .= pack("C",  $icvBack);
        $data       .= pack("C",  $icvFore);
        $data       .= pack("C",  $fls);
        $data       .= pack("C",  $fAuto);
        $data       .= pack("C",  $icv);
        $data       .= pack("C",  $lns);
        $data       .= pack("C",  $lnw);
        $data       .= pack("C",  $fAutoB);
        $data       .= pack("v",  $frs);
        $data       .= pack("V",  $cf);
        $data       .= pack("v",  $Reserved3);
        $data       .= pack("v",  $cbPictFmla);
        $data       .= pack("v",  $Reserved4);
        $data       .= pack("v",  $grbit2);
        $data       .= pack("V",  $Reserved5);

        $this->_append($header . $data);
    }

    /*
     * Convert a 24 bit bitmap into the modified internal format used by
     * Windows. This is described in BITMAPCOREHEADER and BITMAPCOREINFO
     * structures in the MSDN library.
     */
    function _process_bitmap($bitmap) {
        // Open file and binmode the data in case the platform needs it.
        $bmp=fopen($bitmap, "rb");
        if (!$bmp) {
            trigger_error("Could not open file '$bitmap'.", E_USER_ERROR);
        }

        $data=fread($bmp, filesize($bitmap));

        // Check that the file is big enough to be a bitmap.
        if (strlen($data) <= 0x36) {
            trigger_error("$bitmap doesn't contain enough data.",
                          E_USER_ERROR);
        }

        // The first 2 bytes are used to identify the bitmap.
        if (substr($data, 0, 2) != "BM") {
            trigger_error("$bitmap doesn't appear to to be a ".
                          "valid bitmap image.", E_USER_ERROR);
        }

        // Remove bitmap data: ID.
        $data = substr($data, 2);

        // Read and remove the bitmap size. This is more reliable than reading
        // the data size at offset 0x22.
        $array = unpack("Vsize", $data);
        $data = substr($data, 4);
        $size   =  $array["size"];
        $size  -=  0x36;   # Subtract size of bitmap header.
        $size  +=  0x0C;   # Add size of BIFF header.

        // Remove bitmap data: reserved, offset, header length.
        $data = substr($data, 12);

        // Read and remove the bitmap width and height. Verify the sizes.
        $array = unpack("Vwidth/Vheight", $data);
        $data = substr($data, 8);
        $width = $array["width"];
        $height = $array["height"];

        if ($width > 0xFFFF) {
            trigger_error("$bitmap: largest image width supported is 64k.",
                          E_USER_ERROR);
        }

        if ($height > 0xFFFF) {
            trigger_error("$bitmap: largest image height supported is 64k.",
                          E_USER_ERROR);
        }

        // Read and remove the bitmap planes and bpp data. Verify them.
        $array = unpack("vplanes/vbitcount", $data);
        $data = substr($data, 4);
        $planes = $array["planes"];
        $bitcount = $array["bitcount"];

        if ($bitcount != 24) {
            trigger_error("$bitmap isn't a 24bit true color bitmap.",
                          E_USER_ERROR);
        }

        if ($planes != 1) {
            trigger_error("$bitmap: only 1 plane supported in bitmap image.",
                          E_USER_ERROR);
        }

        // Read and remove the bitmap compression. Verify compression.
        $array = unpack("Vcompression", $data);
        $data = substr($data, 4);
        $compression = $array["compression"];

        if ($compression != 0) {
            trigger_error("$bitmap: compression not supported in bitmap image.",
                          E_USER_ERROR);
        }

        // Remove bitmap data: data size, hres, vres, colours, imp. colours.
        $data = substr($data, 20);

        // Add the BITMAPCOREHEADER data
        $header = pack("Vvvvv", 0x000c, $width, $height, 0x01, 0x18);
        $data = $header . $data;

        return array($width, $height, $size, $data);
    }

    /*
     * Store the window zoom factor. This should be a reduced fraction but for
     * simplicity we will store all fractions with a numerator of 100.
     */
    function _store_zoom() {
        // If scale is 100% we don't need to write a record
        if ($this->_zoom == 100) {
            return;
        }

        $record = 0x00A0; // Record identifier
        $length = 0x0004; // Bytes to follow

        $header = pack("vv", $record, $length);
        $data   = pack("vv", $this->_zoom, 100);

        $this->_append($header . $data);
    }

}

?>
