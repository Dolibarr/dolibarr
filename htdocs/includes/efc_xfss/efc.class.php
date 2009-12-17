<?php 
// $Id$
/** 
 * @file 
 * EasyFileCrypt Extending Crypt Class
 * @Version: 1.0.1
 * @Released: 05/27/03
 *
 * Copyright (C) 2003-2009 Humaneasy, brainVentures Network. 
 * Licensed under GNU Lesser General Public License 3 or above.
 * Please visit http://www.gnu.org to now more about it.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE PROJECT AND CONTRIBUTORS ``AS IS'' AND
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 
 * 3. Neither the name of the project nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE PROJECT AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR 
 * PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE PROJECT OR CONTRIBUTORS 
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR 
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, 
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR 
 * OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF 
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * --------------------------------
 * 
 * Crypt Class
 * Copyright (C) 2002 Jason Sheets <jsheets@shadonet.com>.
 * All rights reserved.
 * 
 * This is licensed under a different, but compatible license scheme.
 * See crypt_class.php for more details.
 * 
 * --------------------------------
 * 
 * phpCrc16 v1.1 -- CRC16/CCITT implementation
 * By Matteo Beccati <matteo@beccati.com>
 * 
 * Original code by:
 *             Ashley Roll <ash@digitalnemesis.com>
 *             Digital Nemesis Pty Ltd
 *             www.digitalnemesis.com
 * 
 * Test Vector: "123456789" (character string, no quotes)
 * Generated CRC: 0x29B1
 * 
 */ 

// Define mandatory variables
if (!defined("__CONFIG_SECURE_PATH__"))
     define("__CONFIG_SECURE_PATH__", DOL_DATA_ROOT."/admin/", TRUE);

// Include Extended Class
require_once('crypt_class.php');

/**
* class easyfilecrypt(CRYPT_CLASS)
*
*  { EasyFileCrypt extends Crypt Class to work with files with very few
   values. Crypt Class itself is a wrapper around libmcrypt_ functions, it provides an
   easy way to encrypt and decrypt data. Crypt Class greatly simplifies encryption by
   adding automatic generation and creation of IV's, automatic initialization of
   encryption, and error handling and automatic trimming of keys that are too long.
   EasyFileCrypt will be modified to avoid the requirement of libmcrypt when not present.}
*
*/
class easyfilecrypt extends CRYPT_CLASS {

    // Set default variables for all new objects
    
    //!default cipher to use
    var $defaultcipher;
    
    //!default encryption mode to use
    var $defaultmode;

    // Our vars
    
    //!This is the array that will contain all needed variables
    var $efc;
    
    //!This is the array that will contain all file upload variables
    var $_userfile;
    
    //!This is the array that will contain all file path
    var $_userfilepath;
    
    //!Cipher config filename
    var $cfg_filename;

    /**
    * easyfilecrypt::easyfilecrypt()
    *
    * { Constructor }
    *
    */
    function easyfilecrypt () {

       $this->efc = array( 'name'   => '',
                           'type'   => '',
                           'ext'    => '',
                           'size'   => '',
                           'crc'    => '',
                           'key'    => '',
                           'cipher' => '' );

       $this->defaultcipher = $this->cipher = 'twofish';
       $this->defaultmode = $this->mode = 'cbc';

       $this->cfg_filename = __CONFIG_SECURE_PATH__.".efc.config.php";

    }

    /**
    * easyfilecrypt::encryptfile()
    *
    * { Encrypt the uploaded file contents and save it as a new one }
    *
    */
    function encryptfile(&$refer_userfile) {

       // Reference File Array
       $this->_userfile =& $refer_userfile;

       // Get and sort available cipher methods
       $ciphers = mcrypt_list_algorithms();
       natsort($ciphers);

       // Random choose one to get more security
       srand ((float) microtime() * 10000000);
       $this->efc['cipher'] = $ciphers[array_rand ($ciphers, 1)];
       if ($this->efc['cipher'] == "")
            $this->efc['cipher'] = $this->defaultcipher;

       // set the encryption cipher
       if (!$this->set_cipher($this->efc['cipher']))
            $this->cipher = $this->defaultcipher;
       $this->efc['cipher'] = $this->cipher;

       // set the mode to cbc
       // (you should use cfb for strings and cbc for files if possible)
       @include_once($this->cfg_filename);

       if (!is_array($xfss)) {
           if (!$this->set_mode($this->efc['mode'])) {

              // Neither User nor Default Mode available
              if (!$this->set_mode($this->defaultmode)) {

                // Get one of the available cipher Modes
                srand ((float) microtime() * 10000000);
                $modes = mcrypt_list_modes();

                $this->set_mode($modes[array_rand ($modes, 1)]);
              }
           }
       } else {

           if ($this->efc['mode'] == "")
              $this->efc['mode'] = $this->defaultmode;
/**
$td = @mcrypt_module_open ('arcfour', '', 'stream', '');
if (!$td) $msgteste = 'unable to open cipher ARCFOUR in STREAM mode<br>';
unset($td); $td = @mcrypt_module_open ('wake', '', 'stream', '');
if (!$td) $msgteste = 'unable to open cipher WAKE in STREAM mode<br>';
unset($td); $td = @mcrypt_module_open ('enigma', '', 'stream', '');
if (!$td) $msgteste = 'unable to open cipher ENIGMA in STREAM mode<br>';
if ($msgteste) trigger_error($msgteste, E_USER_ERROR);

echo "Default: ".$this->efc['mode']."<br>".strtoupper($this->cipher)." => ";
print_r($xfss[$this->cipher]);
**/

           if (empty($xfss[$this->cipher])) {
              $this->set_mode('stream');

           } else {

              if ( in_array ($this->efc['mode'], $xfss[$this->cipher]) ) {
                 $this->set_mode($this->efc['mode']);

              } else {
                 $count = count ($xfss[$this->cipher]);
                 srand ((float) microtime() * 10000000);
                
                 $this->set_mode($xfss[$this->cipher][array_rand ($xfss[$this->cipher], 1)]);
              }
           }
       }
       $this->efc['mode'] = $this->mode;
       
       // Set the encryption key
       $this->efc['key'] = $this->generate_key();  // md5(time() . getmypid());
       $this->efc['key'] = substr( md5( $this->efc['key'] ), 0, strlen( $this->efc['key'] ) );
       $this->set_key($this->efc['key']);

       // Save new filename name and mime-type
       $this->efc['name'] = md5($this->_userfile['name'] . time() . getmypid());
       $this->efc['type'] = $this->_userfile['type'];
       $this->efc['ext']  = $this->getExtension($this->_userfile['name']);

       // Set source and destination files name
       $src_filename = $this->_userfile['tmp_name'];
       $dst_filename = $this->_userfilepath.$this->efc['name'];

       // make sure file exists and is readable
       $msg = "encrypt_file: cannot read ".$this->_userfile['tmp_name']." ";
       if (!is_readable($src_filename))
          trigger_error($msg, E_USER_ERROR);

       // touch destination file so it will exist when we check for it
       @touch($dst_filename);

       // can we write to it
       $msg = "encrypt_file: cannot write to ".$dst_filename." ";
       if (!is_writable($dst_filename))
          trigger_error($msg, E_USER_ERROR);

       // read the file into memory and encrypt it
       $fp = fopen($src_filename, r);

       // return false if unable to open file
       $msg = "encrypt_file: cannot open ".$dst_filename." ";
       if (!$fp) trigger_error($msg, E_USER_ERROR);

       $filecontents = fread($fp, filesize($src_filename));
       fclose($fp);

       // open the destination file for writing
       $dest_fp = fopen($dst_filename, w);

       // return false if unable to open file
       $msg = "encrypt_file: cannot open ".$dst_filename." ";
       if (!$dest_fp) trigger_error($msg, E_USER_ERROR);
       
       // adds length of content for cleanly removing the padding
       $length = strlen($filecontents);
       $cleanfilecontents = $length.'|'.$filecontents;

       // write encrypted data to file
       fwrite($dest_fp, $this->encrypt($cleanfilecontents));

       // close encrypted file pointer
       fclose($dest_fp);

       // Save some checksums to test on decrypt
       $this->efc['crc']  = $this->CRC16HexDigest($filecontents);
       $this->efc['size'] = @filesize($dst_filename);

       @unlink($src_filename);
    }

    /**
    * easyfilecrypt::decryptfile()
    *
    * { Decrypt the file contents and save it as a new one }
    *
    */
    function decryptfile() {

       // make sure required fields are specified
       if ((!isset($this->efc['cipher'])) || (!isset($this->efc['key'])))
          trigger_error('Decryption: cipher, mode, and key must be set before using this.', E_USER_ERROR);

       // make sure file exists and is readable
       $src_filename = $this->_userfilepath.$this->efc['name'];

       if (!is_readable($src_filename))
           trigger_error('Encrypted data is corrupted: Not readable', E_USER_ERROR);

       // make sure file wasn't modified by someone
       $msg = "Encrypted data is corrupted: Wrong Size (".filesize($src_filename)." / ".$this->efc['size'].")";
       if( $this->efc['size'] != filesize($src_filename))
           trigger_error($msg, E_USER_ERROR);

       // get file contents
       $contents = @file_get_contents($src_filename);

       // set the encryption cipher
       if (!$this->set_cipher($this->efc['cipher']))
            $this->cipher = $this->efc['cipher'];

       // set the mode to cbc (you should use cfb for strings and cbc for files)
       if (!$this->set_mode($this->efc['mode']))
            $this->mode = $this->efc['mode'];

       // Set the encryption key
       $this->set_key($this->efc['key']);
       $this->efc['key'] = $this->key;

       // decrypt file contents
       $contents = $this->decrypt($contents);
       
       // remove the padding
       list($length, $padded_data) = explode('|', $contents, 2);
       $contents = substr($padded_data, 0, $length);

       // make sure contents where not modified
       if( $this->efc['crc'] != $this->CRC16HexDigest($contents))
           trigger_error('Original Data is corrupted: Bad CRC', E_USER_ERROR);

       // return file contents
       return $contents;
    }

    /**
    * easyfilecrypt::getExtension()
    *
    * { Returns the extension of a given filename }
    *
    */
    function getExtension ($filename) {
        return substr($str = substr($filename, ($pos = strrpos($filename, '/')) !== false ? ++$pos : 0), strpos($str, '.') + 1);
    }

    /**
    * easyfilecrypt::_CRC16()
    *
    * { Returns CRC16 of a string as int value. Used internaly. }
    *
    *
    */
    function _CRC16($str)
    {
        static $CRC16_Lookup = array(
	        0x0000, 0x1021, 0x2042, 0x3063, 0x4084, 0x50A5, 0x60C6, 0x70E7,
	        0x8108, 0x9129, 0xA14A, 0xB16B, 0xC18C, 0xD1AD, 0xE1CE, 0xF1EF,
	        0x1231, 0x0210, 0x3273, 0x2252, 0x52B5, 0x4294, 0x72F7, 0x62D6,
	        0x9339, 0x8318, 0xB37B, 0xA35A, 0xD3BD, 0xC39C, 0xF3FF, 0xE3DE,
	        0x2462, 0x3443, 0x0420, 0x1401, 0x64E6, 0x74C7, 0x44A4, 0x5485,
	        0xA56A, 0xB54B, 0x8528, 0x9509, 0xE5EE, 0xF5CF, 0xC5AC, 0xD58D,
	        0x3653, 0x2672, 0x1611, 0x0630, 0x76D7, 0x66F6, 0x5695, 0x46B4,
	        0xB75B, 0xA77A, 0x9719, 0x8738, 0xF7DF, 0xE7FE, 0xD79D, 0xC7BC,
	        0x48C4, 0x58E5, 0x6886, 0x78A7, 0x0840, 0x1861, 0x2802, 0x3823,
	        0xC9CC, 0xD9ED, 0xE98E, 0xF9AF, 0x8948, 0x9969, 0xA90A, 0xB92B,
	        0x5AF5, 0x4AD4, 0x7AB7, 0x6A96, 0x1A71, 0x0A50, 0x3A33, 0x2A12,
	        0xDBFD, 0xCBDC, 0xFBBF, 0xEB9E, 0x9B79, 0x8B58, 0xBB3B, 0xAB1A,
	        0x6CA6, 0x7C87, 0x4CE4, 0x5CC5, 0x2C22, 0x3C03, 0x0C60, 0x1C41,
	        0xEDAE, 0xFD8F, 0xCDEC, 0xDDCD, 0xAD2A, 0xBD0B, 0x8D68, 0x9D49,
	        0x7E97, 0x6EB6, 0x5ED5, 0x4EF4, 0x3E13, 0x2E32, 0x1E51, 0x0E70,
	        0xFF9F, 0xEFBE, 0xDFDD, 0xCFFC, 0xBF1B, 0xAF3A, 0x9F59, 0x8F78,
	        0x9188, 0x81A9, 0xB1CA, 0xA1EB, 0xD10C, 0xC12D, 0xF14E, 0xE16F,
	        0x1080, 0x00A1, 0x30C2, 0x20E3, 0x5004, 0x4025, 0x7046, 0x6067,
	        0x83B9, 0x9398, 0xA3FB, 0xB3DA, 0xC33D, 0xD31C, 0xE37F, 0xF35E,
	        0x02B1, 0x1290, 0x22F3, 0x32D2, 0x4235, 0x5214, 0x6277, 0x7256,
	        0xB5EA, 0xA5CB, 0x95A8, 0x8589, 0xF56E, 0xE54F, 0xD52C, 0xC50D,
	        0x34E2, 0x24C3, 0x14A0, 0x0481, 0x7466, 0x6447, 0x5424, 0x4405,
	        0xA7DB, 0xB7FA, 0x8799, 0x97B8, 0xE75F, 0xF77E, 0xC71D, 0xD73C,
	        0x26D3, 0x36F2, 0x0691, 0x16B0, 0x6657, 0x7676, 0x4615, 0x5634,
	        0xD94C, 0xC96D, 0xF90E, 0xE92F, 0x99C8, 0x89E9, 0xB98A, 0xA9AB,
	        0x5844, 0x4865, 0x7806, 0x6827, 0x18C0, 0x08E1, 0x3882, 0x28A3,
	        0xCB7D, 0xDB5C, 0xEB3F, 0xFB1E, 0x8BF9, 0x9BD8, 0xABBB, 0xBB9A,
	        0x4A75, 0x5A54, 0x6A37, 0x7A16, 0x0AF1, 0x1AD0, 0x2AB3, 0x3A92,
	        0xFD2E, 0xED0F, 0xDD6C, 0xCD4D, 0xBDAA, 0xAD8B, 0x9DE8, 0x8DC9,
	        0x7C26, 0x6C07, 0x5C64, 0x4C45, 0x3CA2, 0x2C83, 0x1CE0, 0x0CC1,
	        0xEF1F, 0xFF3E, 0xCF5D, 0xDF7C, 0xAF9B, 0xBFBA, 0x8FD9, 0x9FF8,
	        0x6E17, 0x7E36, 0x4E55, 0x5E74, 0x2E93, 0x3EB2, 0x0ED1, 0x1EF0
	);
	
	$crc16 = 0xFFFF; // the CRC
	$len = strlen($str);
	
	for($i = 0; $i < $len; $i++ )
	{
		$t = ($crc16 >> 8) ^ ord($str[$i]); // High byte Xor Message Byte to get index
		$crc16 = (($crc16 << 8) & 0xffff) ^ $CRC16_Lookup[$t]; // Update the CRC from table
	}
	
	// crc16 now contains the CRC value
	return $crc16;
}

    /**
    * easyfilecrypt::CRC16HexDigest()
    *
    * { Returns CRC16 of a string as hexadecimal string. }
    *
    *
    */
    function CRC16HexDigest($str)
    {
        return sprintf('%04X', $this->_CRC16($str));
    }

/** EOF Class **/
}
?>
