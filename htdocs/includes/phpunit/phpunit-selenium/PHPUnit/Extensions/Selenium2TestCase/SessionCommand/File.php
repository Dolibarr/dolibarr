<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2010-2013, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.3.2
 */

/**
 * Sends a file to a RC
 * Returns the FQ path to the transfered file
 *
 * @package    PHPUnit_Selenium
 * @author     Kevin Ran  <heilong24@gmail.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.3.2
 */
class PHPUnit_Extensions_Selenium2TestCase_SessionCommand_File
    extends PHPUnit_Extensions_Selenium2TestCase_Command
{

    /**
     * @var
     */
    private static $_zipArchive;

    public function __construct($argument, PHPUnit_Extensions_Selenium2TestCase_URL $url)
    {
        if (!is_file($argument)) {
            throw new BadMethodCallException("No such file: {$argument}");
        }

        $zipfile_path = $this->_zipArchiveFile($argument);
        $contents     = file_get_contents($zipfile_path);

        if ($contents === false) {
            throw new Exception("Unable to read generated zip file: {$zipfile_path}");
        }

        $file = base64_encode($contents);

        parent::__construct(array('file' => $file), $url);

        unlink($zipfile_path);
    }

    public function httpMethod()
    {
        return 'POST';
    }

    /**
     * Creates a zip archive with the given file
     *
     * @param   string $file_path   FQ path to file
     * @return  string              Generated zip file
     */
    protected function _zipArchiveFile( $file_path )
    {

        // file MUST be readable
        if( !is_readable( $file_path ) ) {

            throw new Exception( "Unable to read {$file_path}" );

        } // if !file_data

        $filename_hash  = sha1( time() . $file_path );
        $tmp_dir        = $this->_getTmpDir();
        $zip_filename   = "{$tmp_dir}{$filename_hash}.zip";
        $zip            = $this->_getZipArchiver();

        if ($zip->open($zip_filename, ZIPARCHIVE::CREATE) === FALSE) {
            throw new Exception( "Unable to create zip archive: {$zip_filename}" );
        }

        $zip->addFile($file_path, basename($file_path));
        $zip->close();

        return $zip_filename;
    }

    /**
     * Returns a runtime instance of a ZipArchive
     *
     * @return ZipArchive
     */
    protected function _getZipArchiver()
    {
        // create ZipArchive if necessary
        if (!static::$_zipArchive) {
            static::$_zipArchive = new ZipArchive();
        }

        return static::$_zipArchive;
    }

    /**
     * Calls sys_get_temp_dir and ensures that it has a trailing slash
     * ( behavior varies across systems )
     *
     * @return string
     */
    protected function _getTmpDir()
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}
