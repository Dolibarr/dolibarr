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

class writeexcel_olewriter {
    var $_OLEfilename;
    var $_OLEtmpfilename; /* ABR */
    var $_filehandle;
    var $_fileclosed;
    var $_internal_fh;
    var $_biff_only;
    var $_size_allowed;
    var $_biffsize;
    var $_booksize;
    var $_big_blocks;
    var $_list_blocks;
    var $_root_start;
    var $_block_count;

    /*
     * Constructor
     */
    function writeexcel_olewriter($filename) {

        $this->_OLEfilename  = $filename;
        $this->_filehandle   = false;
        $this->_fileclosed   = 0;
        $this->_internal_fh  = 0;
        $this->_biff_only    = 0;
        $this->_size_allowed = 0;
        $this->_biffsize     = 0;
        $this->_booksize     = 0;
        $this->_big_blocks   = 0;
        $this->_list_blocks  = 0;
        $this->_root_start   = 0;
        $this->_block_count  = 4;

        $this->_initialize();
    }

    /*
     * Check for a valid filename and store the filehandle.
     */
    function _initialize() {
        $OLEfile = $this->_OLEfilename;

        /* Check for a filename. Workbook.pm will catch this first. */
        if ($OLEfile == '') {
            trigger_error("Filename required", E_USER_ERROR);
        }

        /*
         * If the filename is a resource it is assumed that it is a valid
         * filehandle, if not we create a filehandle.
         */
        if (is_resource($OLEfile)) {
            $fh = $OLEfile;
        } else {
            // Create a new file, open for writing
            $fh = fopen($OLEfile, "wb");
            // The workbook class also checks this but something may have
            // happened since then.
            if (!$fh) {
                trigger_error("Can't open $OLEfile. It may be in use or ".
                              "protected", E_USER_ERROR);
            }

            $this->_internal_fh = 1;
        }

        // Store filehandle
        $this->_filehandle = $fh;
    }

    /*
     * Set the size of the data to be written to the OLE stream
     *
     * $big_blocks = (109 depot block x (128 -1 marker word)
     *               - (1 x end words)) = 13842
     * $maxsize    = $big_blocks * 512 bytes = 7087104
     */
    function set_size($size) {
        $maxsize = 7087104;

        if ($size > $maxsize) {
            trigger_error("Maximum file size, $maxsize, exceeded. To create ".
                          "files bigger than this limit please use the ".
                          "workbookbig class.", E_USER_ERROR);
            return ($this->_size_allowed = 0);
        }

        $this->_biffsize = $size;

        // Set the min file size to 4k to avoid having to use small blocks
        if ($size > 4096) {
            $this->_booksize = $size;
        } else {
            $this->_booksize = 4096;
        }

        return ($this->_size_allowed = 1);
    }

    /*
     * Calculate various sizes needed for the OLE stream
     */
    function _calculate_sizes() {
        $datasize = $this->_booksize;

        if ($datasize % 512 == 0) {
            $this->_big_blocks = $datasize/512;
        } else {
            $this->_big_blocks = floor($datasize/512)+1;
        }
        // There are 127 list blocks and 1 marker blocks for each big block
        // depot + 1 end of chain block
        $this->_list_blocks = floor(($this->_big_blocks)/127)+1;
        $this->_root_start  = $this->_big_blocks;

        //print $this->_biffsize.    "\n";
        //print $this->_big_blocks.  "\n";
        //print $this->_list_blocks. "\n";
    }

    /*
     * Write root entry, big block list and close the filehandle.
     * This method must be called so that the file contents are
     * actually written.
     */
    function close() {

        if (!$this->_size_allowed) {
            return;
        }

        if (!$this->_biff_only) {
            $this->_write_padding();
            $this->_write_property_storage();
            $this->_write_big_block_depot();
        }

        // Close the filehandle if it was created internally.
        if ($this->_internal_fh) {
            fclose($this->_filehandle);
        }
/* ABR */
        if ($this->_OLEtmpfilename != '') {
            $fh = fopen($this->_OLEtmpfilename, "rb");
            if ($fh == false) {
                trigger_error("Can't read temporary file.", E_USER_ERROR);
            }
            fpassthru($fh);
            fclose($fh);
            unlink($this->_OLEtmpfilename);
        };

        $this->_fileclosed = 1;
    }

    /*
     * Write BIFF data to OLE file.
     */
    function write($data) {
        fputs($this->_filehandle, $data);
    }

    /*
     * Write OLE header block.
     */
    function write_header() {
        if ($this->_biff_only) {
            return;
        }

        $this->_calculate_sizes();

        $root_start      = $this->_root_start;
        $num_lists       = $this->_list_blocks;

        $id              = pack("C8", 0xD0, 0xCF, 0x11, 0xE0,
                                      0xA1, 0xB1, 0x1A, 0xE1);
        $unknown1        = pack("VVVV", 0x00, 0x00, 0x00, 0x00);
        $unknown2        = pack("vv",   0x3E, 0x03);
        $unknown3        = pack("v",    -2);
        $unknown4        = pack("v",    0x09);
        $unknown5        = pack("VVV",  0x06, 0x00, 0x00);
        $num_bbd_blocks  = pack("V",    $num_lists);
        $root_startblock = pack("V",    $root_start);
        $unknown6        = pack("VV",   0x00, 0x1000);
        $sbd_startblock  = pack("V",    -2);
        $unknown7        = pack("VVV",  0x00, -2 ,0x00);
        $unused          = pack("V",    -1);

        fputs($this->_filehandle, $id);
        fputs($this->_filehandle, $unknown1);
        fputs($this->_filehandle, $unknown2);
        fputs($this->_filehandle, $unknown3);
        fputs($this->_filehandle, $unknown4);
        fputs($this->_filehandle, $unknown5);
        fputs($this->_filehandle, $num_bbd_blocks);
        fputs($this->_filehandle, $root_startblock);
        fputs($this->_filehandle, $unknown6);
        fputs($this->_filehandle, $sbd_startblock);
        fputs($this->_filehandle, $unknown7);

        for ($c=1;$c<=$num_lists;$c++) {
            $root_start++;
            fputs($this->_filehandle, pack("V", $root_start));
        }

        for ($c=$num_lists;$c<=108;$c++) {
            fputs($this->_filehandle, $unused);
        }
    }

    /*
     * Write big block depot.
     */
    function _write_big_block_depot() {
        $num_blocks   = $this->_big_blocks;
        $num_lists    = $this->_list_blocks;
        $total_blocks = $num_lists * 128;
        $used_blocks  = $num_blocks + $num_lists + 2;

        $marker       = pack("V", -3);
        $end_of_chain = pack("V", -2);
        $unused       = pack("V", -1);

        for ($i=1;$i<=($num_blocks-1);$i++) {
            fputs($this->_filehandle, pack("V", $i));
        }

        fputs($this->_filehandle, $end_of_chain);
        fputs($this->_filehandle, $end_of_chain);

        for ($c=1;$c<=$num_lists;$c++) {
            fputs($this->_filehandle, $marker);
        }

        for ($c=$used_blocks;$c<=$total_blocks;$c++) {
            fputs($this->_filehandle, $unused);
        }
    }

    /*
     * Write property storage. TODO: add summary sheets
     */
    function _write_property_storage() {
        $rootsize = -2;
        $booksize = $this->_booksize;

        //                name          type  dir start  size
        $this->_write_pps('Root Entry', 0x05,   1,   -2, 0x00);
        $this->_write_pps('Book',       0x02,  -1, 0x00, $booksize);
        $this->_write_pps('',           0x00,  -1, 0x00, 0x0000);
        $this->_write_pps('',           0x00,  -1, 0x00, 0x0000);
    }

    /*
     * Write property sheet in property storage
     */
    function _write_pps($name, $type, $dir, $start, $size) {
        $names           = array();
        $length          = 0;

        if ($name != '') {
            $name   = $name . "\0";
            // Simulate a Unicode string
            $chars=preg_split("''", $name, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($chars as $char) {
                array_push($names, ord($char));
            }
            $length = strlen($name) * 2;
        }

        $rawname         = call_user_func_array('pack', array_merge(array("v*"), $names));
        $zero            = pack("C",  0);

        $pps_sizeofname  = pack("v",  $length);   //0x40
        $pps_type        = pack("v",  $type);     //0x42
        $pps_prev        = pack("V",  -1);        //0x44
        $pps_next        = pack("V",  -1);        //0x48
        $pps_dir         = pack("V",  $dir);      //0x4c

        $unknown1        = pack("V",  0);

        $pps_ts1s        = pack("V",  0);         //0x64
        $pps_ts1d        = pack("V",  0);         //0x68
        $pps_ts2s        = pack("V",  0);         //0x6c
        $pps_ts2d        = pack("V",  0);         //0x70
        $pps_sb          = pack("V",  $start);    //0x74
        $pps_size        = pack("V",  $size);     //0x78

        fputs($this->_filehandle, $rawname);
        fputs($this->_filehandle, str_repeat($zero, (64-$length)));
        fputs($this->_filehandle, $pps_sizeofname);
        fputs($this->_filehandle, $pps_type);
        fputs($this->_filehandle, $pps_prev);
        fputs($this->_filehandle, $pps_next);
        fputs($this->_filehandle, $pps_dir);
        fputs($this->_filehandle, str_repeat($unknown1, 5));
        fputs($this->_filehandle, $pps_ts1s);
        fputs($this->_filehandle, $pps_ts1d);
        fputs($this->_filehandle, $pps_ts2d);
        fputs($this->_filehandle, $pps_ts2d);
        fputs($this->_filehandle, $pps_sb);
        fputs($this->_filehandle, $pps_size);
        fputs($this->_filehandle, $unknown1);
    }

    /*
     * Pad the end of the file
     */
    function _write_padding() {
        $biffsize = $this->_biffsize;

        if ($biffsize < 4096) {
            $min_size = 4096;
        } else {
            $min_size = 512;
        }

        if ($biffsize % $min_size != 0) {
            $padding  = $min_size - ($biffsize % $min_size);
            fputs($this->_filehandle, str_repeat("\0", $padding));
        }
    }

}

?>
