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

class writeexcel_biffwriter {
    var $byte_order;
    var $BIFF_version;
    var $_byte_order;
    var $_data;
    var $_datasize;
    var $_limit;

    /*
     * Constructor
     */
    function writeexcel_biffwriter() {

        $this->byte_order   = '';
        $this->BIFF_version = 0x0500;
        $this->_byte_order  = '';
        $this->_data        = false;
        $this->_datasize    = 0;
        $this->_limit       = 2080;

        $this->_set_byte_order();
    }

    /*
     * Determine the byte order and store it as class data to avoid
     * recalculating it for each call to new().
     */
    function _set_byte_order() {
        $this->byteorder=0;
        // Check if "pack" gives the required IEEE 64bit float
        $teststr = pack("d", 1.2345);
        $number  = pack("C8", 0x8D, 0x97, 0x6E, 0x12, 0x83, 0xC0, 0xF3, 0x3F);

        if ($number == $teststr) {
            $this->byte_order = 0; // Little Endian
        } elseif ($number == strrev($teststr)) {
            $this->byte_order = 1; // Big Endian
        } else {
            // Give up
            trigger_error("Required floating point format not supported ".
                          "on this platform. See the portability section ".
                          "of the documentation.", E_USER_ERROR);
        }

        $this->_byte_order = $this->byte_order;
    }

    /*
     * General storage function
     */
    function _prepend($data) {

        if (func_num_args()>1) {
            trigger_error("writeexcel_biffwriter::_prepend() ".
                          "called with more than one argument", E_USER_ERROR);
        }

        if ($this->_debug) {
            print "*** writeexcel_biffwriter::_prepend() called:";
            for ($c=0;$c<strlen($data);$c++) {
                if ($c%16==0) {
                    print "\n";
                }
                printf("%02X ", ord($data[$c]));
            }
            print "\n";
        }

        if (strlen($data) > $this->_limit) {
            $data = $this->_add_continue($data);
        }

        $this->_data      = $data . $this->_data;
        $this->_datasize += strlen($data);
    }

    /*
     * General storage function
     */
    function _append($data) {

        if (func_num_args()>1) {
            trigger_error("writeexcel_biffwriter::_append() ".
                          "called with more than one argument", E_USER_ERROR);
        }

        if ($this->_debug) {
            print "*** writeexcel_biffwriter::_append() called:";
            for ($c=0;$c<strlen($data);$c++) {
                if ($c%16==0) {
                    print "\n";
                }
                printf("%02X ", ord($data[$c]));
            }
            print "\n";
        }

        if (strlen($data) > $this->_limit) {
            $data = $this->_add_continue($data);
        }

        $this->_data      = $this->_data . $data;
        $this->_datasize += strlen($data);
    }

    /*
     * Writes Excel BOF record to indicate the beginning of a stream or
     * sub-stream in the BIFF file.
     *
     * $type = 0x0005, Workbook
     * $type = 0x0010, Worksheet
     */
    function _store_bof($type) {

        $record  = 0x0809; // Record identifier
        $length  = 0x0008; // Number of bytes to follow

        $version = $this->BIFF_version;

        // According to the SDK $build and $year should be set to zero.
        // However, this throws a warning in Excel 5. So, use these
        // magic numbers.
        $build  = 0x096C;
        $year   = 0x07C9;

        $header = pack("vv",   $record, $length);
        $data   = pack("vvvv", $version, $type, $build, $year);

        $this->_prepend($header . $data);
    }

    /*
     * Writes Excel EOF record to indicate the end of a BIFF stream.
     */
    function _store_eof() {

        $record = 0x000A; // Record identifier
        $length = 0x0000; // Number of bytes to follow

        $header = pack("vv", $record, $length);

        $this->_append($header);
    }

    /*
     * Excel limits the size of BIFF records. In Excel 5 the limit is 2084
     * bytes. In Excel 97 the limit is 8228 bytes. Records that are longer
     * than these limits must be split up into CONTINUE blocks.
     *
     * This function take a long BIFF record and inserts CONTINUE records as
     * necessary.
     */
    function _add_continue($data) {

        $limit  = $this->_limit;
        $record = 0x003C; // Record identifier

        // The first 2080/8224 bytes remain intact. However, we have to change
        // the length field of the record.
        $tmp = substr($data, 0, $limit);
        $data = substr($data, $limit);
        $tmp = substr($tmp, 0, 2) . pack ("v", $limit-4) . substr($tmp, 4);

        // Strip out chunks of 2080/8224 bytes +4 for the header.
        while (strlen($data) > $limit) {
            $header  = pack("vv", $record, $limit);
            $tmp    .= $header;
            $tmp    .= substr($data, 0, $limit);
            $data    = substr($data, $limit);
        }

        // Mop up the last of the data
        $header  = pack("vv", $record, strlen($data));
        $tmp    .= $header;
        $tmp    .= $data;

        return $tmp;
    }

}

?>
