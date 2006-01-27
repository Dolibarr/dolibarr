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
require_once "class.writeexcel_format.inc.php";
require_once "class.writeexcel_formula.inc.php";
require_once "class.writeexcel_olewriter.inc.php";

class writeexcel_workbook extends writeexcel_biffwriter {

    var $_filename;
    var $_tmpfilename;
    var $_parser;
    var $_tempdir;
    var $_1904;
    var $_activesheet;
    var $_firstsheet;
    var $_selected;
    var $_xf_index;
    var $_fileclosed;
    var $_biffsize;
    var $_sheetname;
    var $_tmp_format;
    var $_url_format;
    var $_codepage;
    var $_worksheets;
    var $_sheetnames;
    var $_formats;
    var $_palette;

###############################################################################
#
# new()
#
# Constructor. Creates a new Workbook object from a BIFFwriter object.
#
function writeexcel_workbook($filename) {

    $this->writeexcel_biffwriter();

    $tmp_format  = new writeexcel_format();
    $byte_order  = $this->_byte_order;
    $parser      = new writeexcel_formula($byte_order);

    $this->_filename          = $filename;
    $this->_parser            = $parser;
//?    $this->_tempdir           = undef;
    $this->_1904              = 0;
    $this->_activesheet       = 0;
    $this->_firstsheet        = 0;
    $this->_selected          = 0;
    $this->_xf_index          = 16; # 15 style XF's and 1 cell XF.
    $this->_fileclosed        = 0;
    $this->_biffsize          = 0;
    $this->_sheetname         = "Sheet";
    $this->_tmp_format        = $tmp_format;
    $this->_url_format        = false;
    $this->_codepage          = 0x04E4;
    $this->_worksheets        = array();
    $this->_sheetnames        = array();
    $this->_formats           = array();
    $this->_palette           = array();

    # Add the default format for hyperlinks
    $this->_url_format =& $this->addformat(array('color' => 'blue', 'underline' => 1));

    # Check for a filename
    if ($this->_filename == '') {
//todo: print error
        return;
    }

    # Try to open the named file and see if it throws any errors.
    # If the filename is a reference it is assumed that it is a valid
    # filehandle and ignore
    #
//todo

    # Set colour palette.
    $this->set_palette_xl97();
}

###############################################################################
#
# close()
#
# Calls finalization methods and explicitly close the OLEwriter file
# handle.
#
function close() {
    # Prevent close() from being called twice.
    if ($this->_fileclosed) {
        return;
    }

    $this->_store_workbook();
    $this->_fileclosed = 1;
}

//PHPport: method DESTROY deleted

###############################################################################
#
# sheets()
#
# An accessor for the _worksheets[] array
#
# Returns: a list of the worksheet objects in a workbook
#
function &sheets() {
    return $this->_worksheets;
}

//PHPport: method worksheets deleted:
# This method is now deprecated. Use the sheets() method instead.

###############################################################################
#
# addworksheet($name)
#
# Add a new worksheet to the Excel workbook.
# TODO: Add accessor for $self->{_sheetname} for international Excel versions.
#
# Returns: reference to a worksheet object
#
function &addworksheet($name="") {

    # Check that sheetname is <= 31 chars (Excel limit).
    if (strlen($name) > 31) {
        trigger_error("Sheetname $name must be <= 31 chars", E_USER_ERROR);
    }

    $index     = sizeof($this->_worksheets);
    $sheetname = $this->_sheetname;

    if ($name == "") {
        $name = $sheetname . ($index+1);
    }

    # Check that the worksheet name doesn't already exist: a fatal Excel error.
    foreach ($this->_worksheets as $tmp) {
        if ($name == $tmp->get_name()) {
            trigger_error("Worksheet '$name' already exists", E_USER_ERROR);
        }
    }

    $worksheet =& new writeexcel_worksheet($name, $index, $this->_activesheet,
                                          $this->_firstsheet,
                                          $this->_url_format, $this->_parser,
                                          $this->_tempdir);

    $this->_worksheets[$index] = &$worksheet;    # Store ref for iterator
    $this->_sheetnames[$index] = $name;         # Store EXTERNSHEET names
    $this->_parser->set_ext_sheet($name, $index); # Store names in Formula.pm
    return $worksheet;
}

###############################################################################
#
# addformat(%properties)
#
# Add a new format to the Excel workbook. This adds an XF record and
# a FONT record. Also, pass any properties to the Format::new().
#
function &addformat($para=false) {
    if($para===false) {
        $format =& new writeexcel_format($this->_xf_index);
    } else {
        $format =& new writeexcel_format($this->_xf_index, $para);
    }

    $this->_xf_index += 1;
    # Store format reference
    $this->_formats[]=&$format;

    return $format;
}

###############################################################################
#
# set_1904()
#
# Set the date system: 0 = 1900 (the default), 1 = 1904
#
function set_1904($_1904) {
    $this->_1904 = $_1904;
}

###############################################################################
#
# get_1904()
#
# Return the date system: 0 = 1900, 1 = 1904
#
function get_1904() {
    return $this->_1904;
}

###############################################################################
#
# set_custom_color()
#
# Change the RGB components of the elements in the colour palette.
#
function set_custom_color($index, $red, $green, $blue) {
// todo
/*
    # Match a HTML #xxyyzz style parameter
    if (defined $_[1] and $_[1] =~ /^#(\w\w)(\w\w)(\w\w)/ ) {
        @_ = ($_[0], hex $1, hex $2, hex $3);
    }
*/

    $aref    = &$this->_palette;

    # Check that the colour index is the right range
    if ($index < 8 or $index > 64) {
//todo        carp "Color index $index outside range: 8 <= index <= 64";
        return;
    }

    # Check that the colour components are in the right range
    if ( ($red   < 0 || $red   > 255) ||
         ($green < 0 || $green > 255) ||
         ($blue  < 0 || $blue  > 255) )
    {
//todo        carp "Color component outside range: 0 <= color <= 255";
        return;
    }

    $index -=8; # Adjust colour index (wingless dragonfly)

    # Set the RGB value
    $aref[$index] = array($red, $green, $blue, 0);

    return $index +8;
}

###############################################################################
#
# set_palette_xl97()
#
# Sets the colour palette to the Excel 97+ default.
#
function set_palette_xl97() {
    $this->_palette = array(
                            array(0x00, 0x00, 0x00, 0x00),   # 8
                            array(0xff, 0xff, 0xff, 0x00),   # 9
                            array(0xff, 0x00, 0x00, 0x00),   # 10
                            array(0x00, 0xff, 0x00, 0x00),   # 11
                            array(0x00, 0x00, 0xff, 0x00),   # 12
                            array(0xff, 0xff, 0x00, 0x00),   # 13
                            array(0xff, 0x00, 0xff, 0x00),   # 14
                            array(0x00, 0xff, 0xff, 0x00),   # 15
                            array(0x80, 0x00, 0x00, 0x00),   # 16
                            array(0x00, 0x80, 0x00, 0x00),   # 17
                            array(0x00, 0x00, 0x80, 0x00),   # 18
                            array(0x80, 0x80, 0x00, 0x00),   # 19
                            array(0x80, 0x00, 0x80, 0x00),   # 20
                            array(0x00, 0x80, 0x80, 0x00),   # 21
                            array(0xc0, 0xc0, 0xc0, 0x00),   # 22
                            array(0x80, 0x80, 0x80, 0x00),   # 23
                            array(0x99, 0x99, 0xff, 0x00),   # 24
                            array(0x99, 0x33, 0x66, 0x00),   # 25
                            array(0xff, 0xff, 0xcc, 0x00),   # 26
                            array(0xcc, 0xff, 0xff, 0x00),   # 27
                            array(0x66, 0x00, 0x66, 0x00),   # 28
                            array(0xff, 0x80, 0x80, 0x00),   # 29
                            array(0x00, 0x66, 0xcc, 0x00),   # 30
                            array(0xcc, 0xcc, 0xff, 0x00),   # 31
                            array(0x00, 0x00, 0x80, 0x00),   # 32
                            array(0xff, 0x00, 0xff, 0x00),   # 33
                            array(0xff, 0xff, 0x00, 0x00),   # 34
                            array(0x00, 0xff, 0xff, 0x00),   # 35
                            array(0x80, 0x00, 0x80, 0x00),   # 36
                            array(0x80, 0x00, 0x00, 0x00),   # 37
                            array(0x00, 0x80, 0x80, 0x00),   # 38
                            array(0x00, 0x00, 0xff, 0x00),   # 39
                            array(0x00, 0xcc, 0xff, 0x00),   # 40
                            array(0xcc, 0xff, 0xff, 0x00),   # 41
                            array(0xcc, 0xff, 0xcc, 0x00),   # 42
                            array(0xff, 0xff, 0x99, 0x00),   # 43
                            array(0x99, 0xcc, 0xff, 0x00),   # 44
                            array(0xff, 0x99, 0xcc, 0x00),   # 45
                            array(0xcc, 0x99, 0xff, 0x00),   # 46
                            array(0xff, 0xcc, 0x99, 0x00),   # 47
                            array(0x33, 0x66, 0xff, 0x00),   # 48
                            array(0x33, 0xcc, 0xcc, 0x00),   # 49
                            array(0x99, 0xcc, 0x00, 0x00),   # 50
                            array(0xff, 0xcc, 0x00, 0x00),   # 51
                            array(0xff, 0x99, 0x00, 0x00),   # 52
                            array(0xff, 0x66, 0x00, 0x00),   # 53
                            array(0x66, 0x66, 0x99, 0x00),   # 54
                            array(0x96, 0x96, 0x96, 0x00),   # 55
                            array(0x00, 0x33, 0x66, 0x00),   # 56
                            array(0x33, 0x99, 0x66, 0x00),   # 57
                            array(0x00, 0x33, 0x00, 0x00),   # 58
                            array(0x33, 0x33, 0x00, 0x00),   # 59
                            array(0x99, 0x33, 0x00, 0x00),   # 60
                            array(0x99, 0x33, 0x66, 0x00),   # 61
                            array(0x33, 0x33, 0x99, 0x00),   # 62
                            array(0x33, 0x33, 0x33, 0x00),   # 63
                        );

    return 0;
}

###############################################################################
#
# set_palette_xl5()
#
# Sets the colour palette to the Excel 5 default.
#
function set_palette_xl5() {
    $this->_palette = array(
                            array(0x00, 0x00, 0x00, 0x00),   # 8
                            array(0xff, 0xff, 0xff, 0x00),   # 9
                            array(0xff, 0x00, 0x00, 0x00),   # 10
                            array(0x00, 0xff, 0x00, 0x00),   # 11
                            array(0x00, 0x00, 0xff, 0x00),   # 12
                            array(0xff, 0xff, 0x00, 0x00),   # 13
                            array(0xff, 0x00, 0xff, 0x00),   # 14
                            array(0x00, 0xff, 0xff, 0x00),   # 15
                            array(0x80, 0x00, 0x00, 0x00),   # 16
                            array(0x00, 0x80, 0x00, 0x00),   # 17
                            array(0x00, 0x00, 0x80, 0x00),   # 18
                            array(0x80, 0x80, 0x00, 0x00),   # 19
                            array(0x80, 0x00, 0x80, 0x00),   # 20
                            array(0x00, 0x80, 0x80, 0x00),   # 21
                            array(0xc0, 0xc0, 0xc0, 0x00),   # 22
                            array(0x80, 0x80, 0x80, 0x00),   # 23
                            array(0x80, 0x80, 0xff, 0x00),   # 24
                            array(0x80, 0x20, 0x60, 0x00),   # 25
                            array(0xff, 0xff, 0xc0, 0x00),   # 26
                            array(0xa0, 0xe0, 0xe0, 0x00),   # 27
                            array(0x60, 0x00, 0x80, 0x00),   # 28
                            array(0xff, 0x80, 0x80, 0x00),   # 29
                            array(0x00, 0x80, 0xc0, 0x00),   # 30
                            array(0xc0, 0xc0, 0xff, 0x00),   # 31
                            array(0x00, 0x00, 0x80, 0x00),   # 32
                            array(0xff, 0x00, 0xff, 0x00),   # 33
                            array(0xff, 0xff, 0x00, 0x00),   # 34
                            array(0x00, 0xff, 0xff, 0x00),   # 35
                            array(0x80, 0x00, 0x80, 0x00),   # 36
                            array(0x80, 0x00, 0x00, 0x00),   # 37
                            array(0x00, 0x80, 0x80, 0x00),   # 38
                            array(0x00, 0x00, 0xff, 0x00),   # 39
                            array(0x00, 0xcf, 0xff, 0x00),   # 40
                            array(0x69, 0xff, 0xff, 0x00),   # 41
                            array(0xe0, 0xff, 0xe0, 0x00),   # 42
                            array(0xff, 0xff, 0x80, 0x00),   # 43
                            array(0xa6, 0xca, 0xf0, 0x00),   # 44
                            array(0xdd, 0x9c, 0xb3, 0x00),   # 45
                            array(0xb3, 0x8f, 0xee, 0x00),   # 46
                            array(0xe3, 0xe3, 0xe3, 0x00),   # 47
                            array(0x2a, 0x6f, 0xf9, 0x00),   # 48
                            array(0x3f, 0xb8, 0xcd, 0x00),   # 49
                            array(0x48, 0x84, 0x36, 0x00),   # 50
                            array(0x95, 0x8c, 0x41, 0x00),   # 51
                            array(0x8e, 0x5e, 0x42, 0x00),   # 52
                            array(0xa0, 0x62, 0x7a, 0x00),   # 53
                            array(0x62, 0x4f, 0xac, 0x00),   # 54
                            array(0x96, 0x96, 0x96, 0x00),   # 55
                            array(0x1d, 0x2f, 0xbe, 0x00),   # 56
                            array(0x28, 0x66, 0x76, 0x00),   # 57
                            array(0x00, 0x45, 0x00, 0x00),   # 58
                            array(0x45, 0x3e, 0x01, 0x00),   # 59
                            array(0x6a, 0x28, 0x13, 0x00),   # 60
                            array(0x85, 0x39, 0x6a, 0x00),   # 61
                            array(0x4a, 0x32, 0x85, 0x00),   # 62
                            array(0x42, 0x42, 0x42, 0x00),   # 63
                        );

    return 0;
}

###############################################################################
#
# set_tempdir()
#
# Change the default temp directory used by _initialize() in Worksheet.pm.
#
function set_tempdir($tempdir) {
//todo
/*
    croak "$_[0] is not a valid directory"                 unless -d $_[0];
    croak "set_tempdir must be called before addworksheet" if $self->sheets();
*/

    $this->_tempdir = $tempdir;
}

###############################################################################
#
# set_codepage()
#
# See also the _store_codepage method. This is used to store the code page, i.e.
# the character set used in the workbook.
#
function set_codepage($cp) {

    if($cp==1)
      $codepage   = 0x04E4;
    else if($cp==2)
      $codepage   = 0x8000;
    if($codepage)
      $this->_codepage = $codepage;
}


###############################################################################
#
# _store_workbook()
#
# Assemble worksheets into a workbook and send the BIFF data to an OLE
# storage.
#
function _store_workbook() {

    # Ensure that at least one worksheet has been selected.
    if ($this->_activesheet == 0) {
        $this->_worksheets[0]->_selected = 1;
    }

    # Calculate the number of selected worksheet tabs and call the finalization
    # methods for each worksheet
    for ($c=0;$c<sizeof($this->_worksheets);$c++) {
        $sheet=&$this->_worksheets[$c];
        if ($sheet->_selected) {
            $this->_selected++;
        }
        $sheet->_close($this->_sheetnames);
    }

    # Add Workbook globals
    $this->_store_bof(0x0005);

    $this->_store_externs();    # For print area and repeat rows

    $this->_store_names();      # For print area and repeat rows

    $this->_store_codepage();
    
    $this->_store_window1();

    $this->_store_1904();

    $this->_store_all_fonts();

    $this->_store_all_num_formats();

    $this->_store_all_xfs();

    $this->_store_all_styles();

    $this->_store_palette();

    $this->_calc_sheet_offsets();

    # Add BOUNDSHEET records
    for ($c=0;$c<sizeof($this->_worksheets);$c++) {
       $sheet=&$this->_worksheets[$c];
        $this->_store_boundsheet($sheet->_name, $sheet->_offset);
    }

    # End Workbook globals
    $this->_store_eof();

    # Store the workbook in an OLE container
    $this->_store_OLE_file();
}

###############################################################################
#
# _store_OLE_file()
#
# Store the workbook in an OLE container if the total size of the workbook data
# is less than ~ 7MB.
#
function _store_OLE_file() {
## ABR
    if ($this->_tmpfilename != '') {
        $OLE  = new writeexcel_olewriter('/tmp/'.$this->_tmpfilename);
        $OLE->_OLEtmpfilename = '/tmp/'.$this->_tmpfilename;
    } else {
        $OLE  = new writeexcel_olewriter($this->_filename);
        $OLE->_OLEtmpfilename = '';
    };
## END ABR
					            
    # Write Worksheet data if data <~ 7MB
    if ($OLE->set_size($this->_biffsize)) {
        $OLE->write_header();
        $OLE->write($this->_data);

        for ($c=0;$c<sizeof($this->_worksheets);$c++) {
            $sheet=&$this->_worksheets[$c];
            while ($tmp = $sheet->get_data()) {
                $OLE->write($tmp);
            }
            $sheet->cleanup();
        }
    }

    $OLE->close();
}

###############################################################################
#
# _calc_sheet_offsets()
#
# Calculate offsets for Worksheet BOF records.
#
function _calc_sheet_offsets() {

    $BOF     = 11;
    $EOF     = 4;
    $offset  = $this->_datasize;

    foreach ($this->_worksheets as $sheet) {
        $offset += $BOF + strlen($sheet->_name);
    }

    $offset += $EOF;

    for ($c=0;$c<sizeof($this->_worksheets);$c++) {
        $sheet=&$this->_worksheets[$c];
        $sheet->_offset = $offset;
        $offset += $sheet->_datasize;
    }

    $this->_biffsize = $offset;
}

###############################################################################
#
# _store_all_fonts()
#
# Store the Excel FONT records.
#
function _store_all_fonts() {
    # _tmp_format is added by new(). We use this to write the default XF's
    $format = $this->_tmp_format;
    $font   = $format->get_font();

    # Note: Fonts are 0-indexed. According to the SDK there is no index 4,
    # so the following fonts are 0, 1, 2, 3, 5
    #
    for ($c=0;$c<5;$c++) {
        $this->_append($font);
    }

    # Iterate through the XF objects and write a FONT record if it isn't the
    # same as the default FONT and if it hasn't already been used.
    #
    $index = 6;                  # The first user defined FONT

    $key = $format->get_font_key(); # The default font from _tmp_format
    $fonts[$key] = 0;               # Index of the default font

    for ($c=0;$c<sizeof($this->_formats);$c++) {
        $format=&$this->_formats[$c];

        $key = $format->get_font_key();

        if (isset($fonts[$key])) {
            # FONT has already been used
            $format->_font_index = $fonts[$key];
        } else {
            # Add a new FONT record
            $fonts[$key]           = $index;
            $format->_font_index = $index;
            $index++;
            $font = $format->get_font();
            $this->_append($font);
        }
    }
}

###############################################################################
#
# _store_all_num_formats()
#
# Store user defined numerical formats i.e. FORMAT records
#
function _store_all_num_formats() {

    # Leaning num_format syndrome
    $num_formats_list=array();
    $index = 164;

    # Iterate through the XF objects and write a FORMAT record if it isn't a
    # built-in format type and if the FORMAT string hasn't already been used.
    #

    for ($c=0;$c<sizeof($this->_formats);$c++) {
        $format=&$this->_formats[$c];

        $num_format = $format->_num_format;

        # Check if $num_format is an index to a built-in format.
        # Also check for a string of zeros, which is a valid format string
        # but would evaluate to zero.
        #
        if (!preg_match('/^0+\d/', $num_format)) {
            if (preg_match('/^\d+$/', $num_format)) {
                # built-in
                continue;
            }
        }

        if (isset($num_formats[$num_format])) {
            # FORMAT has already been used
            $format->_num_format = $num_formats[$num_format];
        } else {
            # Add a new FORMAT
            $num_formats[$num_format] = $index;
            $format->_num_format    = $index;
            array_push($num_formats_list, $num_format);
            $index++;
        }
    }

    # Write the new FORMAT records starting from 0xA4
    $index = 164;
    foreach ($num_formats_list as $num_format) {
        $this->_store_num_format($num_format, $index);
        $index++;
    }
}

###############################################################################
#
# _store_all_xfs()
#
# Write all XF records.
#
function _store_all_xfs() {
    # _tmp_format is added by new(). We use this to write the default XF's
    # The default font index is 0
    #
    $format = $this->_tmp_format;
    $xf;

    for ($c=0;$c<15;$c++) {
        $xf = $format->get_xf('style'); # Style XF
        $this->_append($xf);
    }

    $xf = $format->get_xf('cell');      # Cell XF
    $this->_append($xf);

    # User defined XFs
    foreach ($this->_formats as $format) {
        $xf = $format->get_xf('cell');
        $this->_append($xf);
    }
}

###############################################################################
#
# _store_all_styles()
#
# Write all STYLE records.
#
function _store_all_styles() {
    $this->_store_style();
}

###############################################################################
#
# _store_externs()
#
# Write the EXTERNCOUNT and EXTERNSHEET records. These are used as indexes for
# the NAME records.
#
function _store_externs() {

    # Create EXTERNCOUNT with number of worksheets
    $this->_store_externcount(sizeof($this->_worksheets));

    # Create EXTERNSHEET for each worksheet
    foreach ($this->_sheetnames as $sheetname) {
        $this->_store_externsheet($sheetname);
    }
}

###############################################################################
#
# _store_names()
#
# Write the NAME record to define the print area and the repeat rows and cols.
#
function _store_names() {

    # Create the print area NAME records
    foreach ($this->_worksheets as $worksheet) {
        # Write a Name record if the print area has been defined
        if ($worksheet->_print_rowmin!==false) {
            $this->_store_name_short(
                $worksheet->_index,
                0x06, # NAME type
                $worksheet->_print_rowmin,
                $worksheet->_print_rowmax,
                $worksheet->_print_colmin,
                $worksheet->_print_colmax
            );
        }
    }

    # Create the print title NAME records
    foreach ($this->_worksheets as $worksheet) {

        $rowmin = $worksheet->_title_rowmin;
        $rowmax = $worksheet->_title_rowmax;
        $colmin = $worksheet->_title_colmin;
        $colmax = $worksheet->_title_colmax;

        # Determine if row + col, row, col or nothing has been defined
        # and write the appropriate record
        #
        if ($rowmin!==false && $colmin!==false) {
            # Row and column titles have been defined.
            # Row title has been defined.
            $this->_store_name_long(
                $worksheet->_index,
                0x07, # NAME type
                $rowmin,
                $rowmax,
                $colmin,
                $colmax
           );
        } elseif ($rowmin!==false) {
            # Row title has been defined.
            $this->_store_name_short(
                $worksheet->_index,
                0x07, # NAME type
                $rowmin,
                $rowmax,
                0x00,
                0xff
            );
        } elseif ($colmin!==false) {
            # Column title has been defined.
            $this->_store_name_short(
                $worksheet->_index,
                0x07, # NAME type
                0x0000,
                0x3fff,
                $colmin,
                $colmax
            );
        } else {
            # Print title hasn't been defined.
        }
    }
}

###############################################################################
###############################################################################
#
# BIFF RECORDS
#

###############################################################################
#
# _store_window1()
#
# Write Excel BIFF WINDOW1 record.
#
function _store_window1() {

    $record    = 0x003D;                 # Record identifier
    $length    = 0x0012;                 # Number of bytes to follow

    $xWn       = 0x0000;                 # Horizontal position of window
    $yWn       = 0x0000;                 # Vertical position of window
    $dxWn      = 0x25BC;                 # Width of window
    $dyWn      = 0x1572;                 # Height of window

    $grbit     = 0x0038;                 # Option flags
    $ctabsel   = $this->_selected;     # Number of workbook tabs selected
    $wTabRatio = 0x0258;                 # Tab to scrollbar ratio

    $itabFirst = $this->_firstsheet;   # 1st displayed worksheet
    $itabCur   = $this->_activesheet;  # Active worksheet

    $header    = pack("vv",        $record, $length);
    $data      = pack("vvvvvvvvv", $xWn, $yWn, $dxWn, $dyWn,
                                   $grbit,
                                   $itabCur, $itabFirst,
                                   $ctabsel, $wTabRatio);

    $this->_append($header . $data);
}

###############################################################################
#
# _store_boundsheet()
#
# Writes Excel BIFF BOUNDSHEET record.
#
function _store_boundsheet($sheetname, $offset) {
    $record    = 0x0085;               # Record identifier
    $length    = 0x07 + strlen($sheetname); # Number of bytes to follow

    //$sheetname = $_[0];                # Worksheet name
    //$offset    = $_[1];                # Location of worksheet BOF
    $grbit     = 0x0000;               # Sheet identifier
    $cch       = strlen($sheetname);   # Length of sheet name

    $header    = pack("vv",  $record, $length);
    $data      = pack("VvC", $offset, $grbit, $cch);

    $this->_append($header . $data . $sheetname);
}

###############################################################################
#
# _store_style()
#
# Write Excel BIFF STYLE records.
#
function _store_style() {
    $record    = 0x0293; # Record identifier
    $length    = 0x0004; # Bytes to follow

    $ixfe      = 0x8000; # Index to style XF
    $BuiltIn   = 0x00;   # Built-in style
    $iLevel    = 0xff;   # Outline style level

    $header    = pack("vv",  $record, $length);
    $data      = pack("vCC", $ixfe, $BuiltIn, $iLevel);

    $this->_append($header . $data);
}

###############################################################################
#
# _store_num_format()
#
# Writes Excel FORMAT record for non "built-in" numerical formats.
#
function _store_num_format($num_format, $index) {
    $record    = 0x041E;                 # Record identifier
    $length    = 0x03 + strlen($num_format);   # Number of bytes to follow

    $format    = $num_format;                  # Custom format string
    $ifmt      = $index;                  # Format index code
    $cch       = strlen($format);        # Length of format string

    $header    = pack("vv", $record, $length);
    $data      = pack("vC", $ifmt, $cch);

    $this->_append($header . $data . $format);
}

###############################################################################
#
# _store_1904()
#
# Write Excel 1904 record to indicate the date system in use.
#
function _store_1904() {
    $record    = 0x0022;         # Record identifier
    $length    = 0x0002;         # Bytes to follow

    $f1904     = $this->_1904; # Flag for 1904 date system

    $header    = pack("vv",  $record, $length);
    $data      = pack("v", $f1904);

    $this->_append($header . $data);
}

###############################################################################
#
# _store_externcount($count)
#
# Write BIFF record EXTERNCOUNT to indicate the number of external sheet
# references in the workbook.
#
# Excel only stores references to external sheets that are used in NAME.
# The workbook NAME record is required to define the print area and the repeat
# rows and columns.
#
# A similar method is used in Worksheet.pm for a slightly different purpose.
#
function _store_externcount($par0) {
    $record   = 0x0016;          # Record identifier
    $length   = 0x0002;          # Number of bytes to follow

    $cxals    = $par0;           # Number of external references

    $header   = pack("vv", $record, $length);
    $data     = pack("v",  $cxals);

    $this->_append($header . $data);
}

###############################################################################
#
# _store_externsheet($sheetname)
#
#
# Writes the Excel BIFF EXTERNSHEET record. These references are used by
# formulas. NAME record is required to define the print area and the repeat
# rows and columns.
#
# A similar method is used in Worksheet.pm for a slightly different purpose.
#
function _store_externsheet($par0) {
    $record      = 0x0017;               # Record identifier
    $length      = 0x02 + strlen($par0); # Number of bytes to follow

    $sheetname   = $par0;                # Worksheet name
    $cch         = strlen($sheetname);   # Length of sheet name
    $rgch        = 0x03;                 # Filename encoding

    $header      = pack("vv",  $record, $length);
    $data        = pack("CC", $cch, $rgch);

    $this->_append($header . $data . $sheetname);
}

###############################################################################
#
# _store_name_short()
#
#
# Store the NAME record in the short format that is used for storing the print
# area, repeat rows only and repeat columns only.
#
function _store_name_short($par0, $par1, $par2, $par3, $par4, $par5) {
    $record          = 0x0018;       # Record identifier
    $length          = 0x0024;       # Number of bytes to follow

    $index           = $par0;        # Sheet index
    $type            = $par1;

    $grbit           = 0x0020;       # Option flags
    $chKey           = 0x00;         # Keyboard shortcut
    $cch             = 0x01;         # Length of text name
    $cce             = 0x0015;       # Length of text definition
    $ixals           = $index +1;    # Sheet index
    $itab            = $ixals;       # Equal to ixals
    $cchCustMenu     = 0x00;         # Length of cust menu text
    $cchDescription  = 0x00;         # Length of description text
    $cchHelptopic    = 0x00;         # Length of help topic text
    $cchStatustext   = 0x00;         # Length of status bar text
    $rgch            = $type;        # Built-in name type

    $unknown03       = 0x3b;
    $unknown04       = 0xffff-$index;
    $unknown05       = 0x0000;
    $unknown06       = 0x0000;
    $unknown07       = 0x1087;
    $unknown08       = 0x8005;

    $rowmin          = $par2;        # Start row
    $rowmax          = $par3;        # End row
    $colmin          = $par4;        # Start column
    $colmax          = $par5;        # end column

    $header          = pack("vv",  $record, $length);
    $data            = pack("v", $grbit);
    $data              .= pack("C", $chKey);
    $data              .= pack("C", $cch);
    $data              .= pack("v", $cce);
    $data              .= pack("v", $ixals);
    $data              .= pack("v", $itab);
    $data              .= pack("C", $cchCustMenu);
    $data              .= pack("C", $cchDescription);
    $data              .= pack("C", $cchHelptopic);
    $data              .= pack("C", $cchStatustext);
    $data              .= pack("C", $rgch);
    $data              .= pack("C", $unknown03);
    $data              .= pack("v", $unknown04);
    $data              .= pack("v", $unknown05);
    $data              .= pack("v", $unknown06);
    $data              .= pack("v", $unknown07);
    $data              .= pack("v", $unknown08);
    $data              .= pack("v", $index);
    $data              .= pack("v", $index);
    $data              .= pack("v", $rowmin);
    $data              .= pack("v", $rowmax);
    $data              .= pack("C", $colmin);
    $data              .= pack("C", $colmax);

    $this->_append($header . $data);
}

###############################################################################
#
# _store_name_long()
#
#
# Store the NAME record in the long format that is used for storing the repeat
# rows and columns when both are specified. This share a lot of code with
# _store_name_short() but we use a separate method to keep the code clean.
# Code abstraction for reuse can be carried too far, and I should know. ;-)
#
function _store_name_long($par0, $par1, $par2, $par3, $par4, $par5) {
    $record          = 0x0018;       # Record identifier
    $length          = 0x003d;       # Number of bytes to follow

    $index           = $par0;        # Sheet index
    $type            = $par1;

    $grbit           = 0x0020;       # Option flags
    $chKey           = 0x00;         # Keyboard shortcut
    $cch             = 0x01;         # Length of text name
    $cce             = 0x002e;       # Length of text definition
    $ixals           = $index +1;    # Sheet index
    $itab            = $ixals;       # Equal to ixals
    $cchCustMenu     = 0x00;         # Length of cust menu text
    $cchDescription  = 0x00;         # Length of description text
    $cchHelptopic    = 0x00;         # Length of help topic text
    $cchStatustext   = 0x00;         # Length of status bar text
    $rgch            = $type;        # Built-in name type

    $unknown01       = 0x29;
    $unknown02       = 0x002b;
    $unknown03       = 0x3b;
    $unknown04       = 0xffff-$index;
    $unknown05       = 0x0000;
    $unknown06       = 0x0000;
    $unknown07       = 0x1087;
    $unknown08       = 0x8008;

    $rowmin          = $par2;        # Start row
    $rowmax          = $par3;        # End row
    $colmin          = $par4;        # Start column
    $colmax          = $par5;        # end column

    $header          = pack("vv",  $record, $length);
    $data            = pack("v", $grbit);
    $data              .= pack("C", $chKey);
    $data              .= pack("C", $cch);
    $data              .= pack("v", $cce);
    $data              .= pack("v", $ixals);
    $data              .= pack("v", $itab);
    $data              .= pack("C", $cchCustMenu);
    $data              .= pack("C", $cchDescription);
    $data              .= pack("C", $cchHelptopic);
    $data              .= pack("C", $cchStatustext);
    $data              .= pack("C", $rgch);
    $data              .= pack("C", $unknown01);
    $data              .= pack("v", $unknown02);
    # Column definition
    $data              .= pack("C", $unknown03);
    $data              .= pack("v", $unknown04);
    $data              .= pack("v", $unknown05);
    $data              .= pack("v", $unknown06);
    $data              .= pack("v", $unknown07);
    $data              .= pack("v", $unknown08);
    $data              .= pack("v", $index);
    $data              .= pack("v", $index);
    $data              .= pack("v", 0x0000);
    $data              .= pack("v", 0x3fff);
    $data              .= pack("C", $colmin);
    $data              .= pack("C", $colmax);
    # Row definition
    $data              .= pack("C", $unknown03);
    $data              .= pack("v", $unknown04);
    $data              .= pack("v", $unknown05);
    $data              .= pack("v", $unknown06);
    $data              .= pack("v", $unknown07);
    $data              .= pack("v", $unknown08);
    $data              .= pack("v", $index);
    $data              .= pack("v", $index);
    $data              .= pack("v", $rowmin);
    $data              .= pack("v", $rowmax);
    $data              .= pack("C", 0x00);
    $data              .= pack("C", 0xff);
    # End of data
    $data              .= pack("C", 0x10);

    $this->_append($header . $data);
}

###############################################################################
#
# _store_palette()
#
# Stores the PALETTE biff record.
#
function _store_palette() {
    $aref            = &$this->_palette;

    $record          = 0x0092;                  # Record identifier
    $length          = 2 + 4 * sizeof($aref);   # Number of bytes to follow
    $ccv             =         sizeof($aref);   # Number of RGB values to follow
    //$data;                                      # The RGB data

    # Pack the RGB data
    foreach($aref as $dat) {
        $data .= call_user_func_array('pack', array_merge(array("CCCC"), $dat));
    }

    $header = pack("vvv",  $record, $length, $ccv);

    $this->_append($header . $data);
}

###############################################################################
#
# _store_codepage()
#
# Stores the CODEPAGE biff record.
#
function _store_codepage() {

    $record          = 0x0042;               # Record identifier
    $length          = 0x0002;               # Number of bytes to follow
    $cv              = $this->_codepage;     # The code page

    $header          = pack("vv", $record, $length);
    $data            = pack("v",  $cv);

    $this->_append($header.$data);
}

}

?>
