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
 * Please visit http://www.opensource.org/licenses/bsd-license.php 
 * to now more about it. 
 *  
 * --------------------------------
 *
 * Copyright (C) 2002 Jason Sheets <jsheets@shadonet.com>.
 * All rights reserved.
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
 */

/**
   Name: Crypt Class

   Version: 1.1
   Date Released: 11/18/02

   Description: Crypt Class is a wrapper around libmcrypt_ functions, it provides an easy
   way to encrypt and decrypt data. Crypt Class greatly simplifies encryption by adding
   automatic generation and creation of IV's, automatic initialization of encryption, and
   error handling and automatic trimming of keys that are too long.


   Simple Example:

   <?php
      // include crypt class file
      include('crypt_class.php');

      // create an instance of the crypt class
      $crypt_class = new CRYPT_CLASS;

      // set the encryption cipher to twofish
      $crypt_class->set_cipher('twofish'); // set the cipher

      // set the mode to cfb (you should use cfb for strings and cbc for files)
      $crypt_class->set_mode('cfb'); // set encryption mode

      // set the encryption key to 'test key'
      $crypt_class->set_key('test key')

      // this is the data we want to encrypt
      $data = 'this is a test message';

      // this will be the encrypted data
      $encrypted = $crypt_class->encrypt($data);


      // this is the decrypted data
      $decrypted = $crypt_class->decrypt($encrypted);
   ?>

   Usage:
      See the README.txt file.
	

   Author: Jason Sheets <jsheets@shadonet.com>

   License: This script is distributed under the BSD License, you are free
   to use, or modify it however you like.  If you find this script useful please
   e-mail me.
**/

   class CRYPT_CLASS {
      var $cipher; // cipher to encrypt with
      var $defaultmode = 'cfb'; // default encryption mode to use
      var $defaultcipher = 'twofish'; // default cipher to use
      var $key; // encryption/decription key
      var $mode; // encryption mode to use

      var $post_decrypt_filter; // filter to apply after decrypting, before base64_decode ie gzip_enflate
      var $pre_encrypt_filter; // filter to apply before encrypting ie gzip_deflate

      /* You should use cfb mode for strings and cbc mode for files */

      // constructor for CRYPT_CLASS
      function CRYPT_CLASS() {

         // make sure we can use mcrypt_generic_init
         if (!function_exists(mcrypt_generic_init)) {
            ?>

            <html><head><title>libmcrypt not available</title></head><body>
            <h3>libmcrypt not available</h3>
            <p>In order to use crypt class you must have libmcrypt >= 2.4.x installed and PHP must be compiled with --with-mcrypt, if you don't
            know what this means please contact your hosting provider or system admin.</p>
            </body></html>

            <?php
            exit;
         }

         // enable gzip compression if possible, if gzip is not installed but bzip2 is use bzip2 instead
         /*
         if (function_exists('gzdeflate')) {
            $this->set_pre_encrypt_filter('gzdeflate');
            $this->set_post_decrypt_filter('gzinflate');
         } elseif (function_exists('bzcompress')) {
            $this->set_pre_encrypt_filter('bzcompress');
            $this->set_post_decrypt_filter('bzuncompress');
         }
         */
      }

      // clears the key so it can't be fetched by get_key later
      function clear_key() {
         $this->key = '';
      }

      // clears the pre encrypt filter
      function clear_pre_encrypt_filter()
      {
         $this->pre_encrypt_filter = '';
      }

      // clears the post decrypt filter
      function clear_post_decrypt_filter()
      {
         $this->post_decrypt_filter = '';
      }

      // shortcut, clears both pre_encrypt and post_decrypt filters
      function clear_filters()
      {
         $this->clear_pre_encrypt_filter();
         $this->clear_post_decrypt_filter();
      }

      // creates an IV
      function create_iv()
      {
         // before we create an IV make sure cipher is set
         if ((!isset($this->cipher)) || (!isset($this->mode))) {
            trigger_error('create_iv: cipher and mode must be set before using create_iv', E_USER_ERROR);
            return 0;
         }

         // open encryption module
         $td = $this->_open_cipher();

         // try to generate the iv
         $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);

         // if we couldn't generate the iv display an error
         if (!$iv)
         {
            //trigger_error('create_iv: unable to create iv', E_USER_ERROR);
            return '';
         }

         // cleanup
         @mcrypt_module_close($td);

         // return iv
         return $iv;
      }

      function decrypt($encrypted, $keepIV = 0)
      {
         if ((!isset($this->cipher)) || (!isset($this->mode)) || (!isset($this->key))) {
            trigger_error('decrypt: cipher, mode, and key must be set before using decrypt', E_USER_ERROR);
         }

         // extract encrypted value from base64 encoded value
         $data = base64_decode($encrypted);

         // open encryption module
         $td = $this->_open_cipher();

         // get what size the IV should be
         $ivsize = mcrypt_enc_get_iv_size($td);

         // get the IV from the encrypted string
         $iv = substr($data, 0, $ivsize);

         // remove the IV from the data so we decrypt cleanly
         if ($keepIV != 1) {
            $data = substr($data, $ivsize);
         }

         // initialize decryption
         @mcrypt_generic_init ($td, $this->key, $iv);

         // decrypt the data
         $decrypted = mdecrypt_generic ($td, $data);

         // apply post-decrypt filter (this is usually a decompression call)
         if (!empty($this->post_decrypt_filter)) {
            $filter = $this->get_post_decrypt_filter();
            $decrypted = $filter($decrypted);
            unset($filter);
         }

         // cleanup
         mcrypt_generic_deinit($td);
         mcrypt_module_close($td);

         // get rid of original data
         unset($data);

         return $decrypted;

      }

      /* decrypts a file */
      function decrypt_file($sourcefile, $destfile)
      {
         // make sure required fields are specified
         if ((!isset($this->cipher)) || (!isset($this->mode)) || (!isset($this->key))) {
            trigger_error('decrypt_file: cipher, mode, and key must be set before using decrypt_file', E_USER_ERROR);
         }

         // make sure file exists and is readable
         if (!is_readable($sourcefile)) {
            return 0;
         }

         // touch destion file so it will exist when we check for it
         @touch($destfile);

         if (!is_writable($destfile)) {
            return 0;
         }

         // read the file into memory and encrypt it
         $fp = fopen($sourcefile, r);

         // return false if unable to open file
         if (!$fp) {
            return 0;
         }

         $filecontents = fread($fp, filesize($sourcefile));
         fclose($fp);

         // open the destionation file for writing
         $dest_fp = fopen($destfile, w);

         // return false if unable to open file
         if (!$dest_fp) {
            return 0;
         }

         // write decrypted data to file
         fwrite($dest_fp, $this->decrypt($filecontents));

         // close encrypted file pointer
         fclose($dest_fp);

         return 1;
      }

      function encrypt($data)
      {
         if ((!isset($this->cipher)) || (!isset($this->mode)) || (!isset($this->key))) {
            trigger_error('encrypt: cipher, mode, and key must be set before using encrypt', E_USER_ERROR);
         }

         // create an IV
         $iv = $this->create_iv();

         // open encryption module
         $td = $this->_open_cipher();

         // apply pre-encrypt filter (this is usually a compression call)
         if (!empty($this->pre_encrypt_filter)) {
            $filter = $this->get_pre_encrypt_filter();
            $data = $filter($data);
            unset($filter);
         }

         // initialize encryption
         mcrypt_generic_init ($td, $this->key, $iv);

         $encrypted_data = mcrypt_generic($td, $data);

         // cleanup
         mcrypt_generic_deinit($td);
         mcrypt_module_close($td);

         // get rid of original data
         unset($data);

         // return base64 encoded string
         return base64_encode($iv . $encrypted_data);
      }

      /* encrypts a file */
      function encrypt_file($sourcefile, $destfile) {
         // make sure required fields are specified
         if ((!isset($this->cipher)) || (!isset($this->mode)) || (!isset($this->key))) {
            trigger_error('encrypt_file: cipher, mode, and key must be set before using encrypt_file', E_USER_ERROR);
         }

         // make sure file exists and is readable
         if (!is_readable($sourcefile)) {
            //trigger_error("encrypt_file: cannot read '$sourcefile' ", E_USER_ERROR);
            return 0;
         }

         // touch destion file so it will exist when we check for it
         @touch($destfile);

         if (!is_writable($destfile)) {
            //trigger_error("encrypt_file: cannot write to '$destfile' ", E_USER_ERROR);
            return 0;
         }

         // read the file into memory and encrypt it
         $fp = fopen($sourcefile, r);

         // return false if unable to open file
         if (!$fp) {
            //trigger_error("encrypt_file: cannot open '$sourcefile' ", E_USER_ERROR);
            return 0;
         }

         $filecontents = fread($fp, filesize($sourcefile));
         fclose($fp);

         // open the destionation file for writing
         $dest_fp = fopen($destfile, w);

         // return false if unable to open file
         if (!$dest_fp) {
            //trigger_error("encrypt_file: cannot open '$destfile' ", E_USER_ERROR);
            return 0;
         }

         // write encrypted data to file
         fwrite($dest_fp, $this->encrypt($filecontents));

         // close encrypted file pointer
         fclose($dest_fp);

         return 1;

      }

      /* this function *ATTEMPTS* to generate a secure encryption/decryption key */
      function generate_key()
      {
         /* generate an random decryption key */
         $decryptkey = bin2hex(md5(uniqid(rand(),1)));

         /* get a unique id with a random prefix */
         $value = md5(uniqid(rand(),1));

         // backup current encryption key
         $oldkey = $this->key;

         // set the encryption/decryption key to the randomly generated decryption key
         $this->set_key($decryptkey);

         // decrypt $value with an invalid decryption key so we get garbage
         $returnkey = $this->decrypt($value, 1);

         // restore encryption key
         $this->key = $oldkey;

         // cleanup variables
         unset($oldkey, $decryptkey);

         // return encryption key, should be base64 encoded for storage
         return $returnkey;

      }

      /* return the name of the current cipher */
      function get_cipher()
      {
         return $this->cipher;
      }

      /* return the encryption/decryption key */
      function get_key()
      {
         return $this->key;
      }

      /* return the encryption mode */
      function get_mode()
      {
         return $this->mode;
      }

      // return current post decrypt filter
      function get_post_decrypt_filter()
      {
         return $this->post_decrypt_filter;
      }

      // return current pre encrypt filter
      function get_pre_encrypt_filter()
      {
         return $this->pre_encrypt_filter;
      }

      // wrapper around md5
      function md5($string)
      {
         // if the md5 function exists return md5($string), otherwise use built in md5
         if (function_exists('md5')) {
            return md5($string);
         } else {
            /* call to local md5 script goes here */
         }
      }

      /* attempt to set the cipher to $ciphername, verifies ciphername against list of supported ciphers */
      function set_cipher($ciphername)
      {
         if (in_array($ciphername, mcrypt_list_algorithms())) {
            $this->cipher = $ciphername;

            return 1;
         } else {
            return 0;
         }
      }

      // wrapper around sha1
      function sha1($string)
      {
         // if the sha1 function exists return sha1($string), otherwise use built in sha1
         if (function_exists('sha1')) {
            return sha1($string);
         } else {
            // note sha1 is only native to PHP 4.3.0 and newer
            /* call to local sha1 script goes here */
         }
      }

      /* set encryption key */
      function set_key($encryptkey)
      {
         // make sure cipher and mode are set before setting IV
         if ((!isset($this->cipher)) || (!isset($this->mode))) {
            trigger_error('set_key: cipher and mode must be set before using set_key', E_USER_ERROR);
         }

         if (!empty($encryptkey)) {

            // get the size of the encryption key
            $keysize = @mcrypt_get_key_size ($this->cipher, $this->mode);
            //$keysize = @mcrypt_get_key_size ($this->cipher);
            //trigger_error($keysize, E_USER_ERROR);
            

            // if the encryption key is less than 32 characters long and the expected keysize is at least 32 md5 the key
            if ((strlen($encryptkey) < 32) && ($keysize >= 32)) {
               $encryptkey = md5($encryptkey);
            // if encryption key is longer than $keysize and the keysize is 32 then md5 the encryption key
            } elseif ((strlen($encryptkey) > $keysize) && ($keysize == 32)) {
               $encryptkey = md5($encryptkey);
            } else {
            // if encryption key is longer than the keysize substr it to the correct keysize length
               $encryptkey = substr($encryptkey, 0, $keysize);
            }

            $this->key = $encryptkey;
         } else {
            return 0;
         }
      }

      /* attempt to set encryption mode to $encryptmode, verifies mode against list of supported modes */
      function set_mode($encryptmode)
      {
         // make sure encryption mode is a valid mode
         if (in_array($encryptmode, mcrypt_list_modes())) {
            $this->mode = $encryptmode;
         } else {
            return 0;
         }
      }

      function set_post_decrypt_filter($function)
      {
         // if the function exists set the filter and return true
         if (function_exists($function)) {
            $this->post_decrypt_filter = $function;

            return 1;
         // function does not exist, return false
         } else {
            return 0;
         }
      }

      function set_pre_encrypt_filter($function)
      {
         // if function exists set filter and return true
         if (function_exists($function)) {
            $this->pre_encrypt_filter = $function;
            return 1;

         // function does not exist, return false
         } else {
            return 0;
         }
      }

/* Everything below here are private methods and should not be called by anyone except the script */

      /* attempt to open cipher, verify cipher was opened otherwise throw an error */
      function _open_cipher()
      {
         // open encryption module
         $td = @mcrypt_module_open($this->cipher, '', $this->mode, '');

         // display error if we couldn't open the cipher
         if (!$td) {
            trigger_error('unable to open cipher ' . $this->cipher . ' in ' . $this->mode . ' mode', E_USER_ERROR);
         }

         return $td;
      }
   }
?>
