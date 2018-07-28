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
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.0.0
 */

$directory = realpath(__DIR__);
while ($directory != '/') {
    $autoloadCandidate = $directory . '/vendor/autoload.php';
    if (file_exists($autoloadCandidate)) {
        require_once $autoloadCandidate;
        break;
    }
    $directory = realpath($directory . '/..');
}

// Set this to the directory that contains the code coverage files.
// It defaults to getcwd(). If you have configured a different directory
// in prepend.php, you need to configure the same directory here.
$GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY'] = getcwd();

if (isset($_GET['PHPUNIT_SELENIUM_TEST_ID'])) {
    $facade = new File_Iterator_Facade;
    $sanitizedCookieName = str_replace(array('\\'), '_', $_GET['PHPUNIT_SELENIUM_TEST_ID']);
    $files  = $facade->getFilesAsArray(
      $GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY'],
      $sanitizedCookieName
    );

    $coverage = array();

    foreach ($files as $file) {
        $data = unserialize(file_get_contents($file));
        unlink($file);
        unset($file);
        $filter = new PHP_CodeCoverage_Filter();

        foreach ($data as $file => $lines) {
            if ($filter->isFile($file)) {
                if (!isset($coverage[$file])) {
                    $coverage[$file] = array(
                      'md5' => md5_file($file), 'coverage' => $lines
                    );
                } else {
                    foreach ($lines as $line => $flag) {
                        if (!isset($coverage[$file]['coverage'][$line]) ||
                            $flag > $coverage[$file]['coverage'][$line]) {
                            $coverage[$file]['coverage'][$line] = $flag;
                        }
                    }
                }
            }
        }
    }

    print serialize($coverage);
}
