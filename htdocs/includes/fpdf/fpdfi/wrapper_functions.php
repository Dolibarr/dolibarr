<?php
//
//  FPDI - Version 1.2
//
//    Copyright 2004-2007 Setasign - Jan Slabon
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


if (!defined("PHP_VER_LOWER43")) 
	define("PHP_VER_LOWER43", version_compare(PHP_VERSION, "4.3", "<"));


/**
 * ensure that strspn works correct if php-version < 4.3
 */
function _strspn($str1, $str2, $start=null, $length=null) {
    $numargs = func_num_args();

    if (PHP_VER_LOWER43 == 1) {
        if (isset($length)) {
            $str1 = substr($str1, $start, $length);
        } else {
            $str1 = substr($str1, $start);
        }
    }

    if ($numargs == 2 || PHP_VER_LOWER43 == 1) {
        return strspn($str1, $str2);
    } else if ($numargs == 3) {
        return strspn($str1, $str2, $start);
    } else {
        return strspn($str1, $str2, $start, $length);
    }
}


/**
 * ensure that strcspn works correct if php-version < 4.3
 */
function _strcspn($str1, $str2, $start=null, $length=null) {
    $numargs = func_num_args();

    if (PHP_VER_LOWER43 == 1) {
        if (isset($length)) {
            $str1 = substr($str1, $start, $length);
        } else {
            $str1 = substr($str1, $start);
        }
    }

    if ($numargs == 2 || PHP_VER_LOWER43 == 1) {
        return strcspn($str1, $str2);
    } else if ($numargs == 3) {
        return strcspn($str1, $str2, $start);
    } else {
        return strcspn($str1, $str2, $start, $length);
    }
}


/**
 * ensure that fgets works correct if php-version < 4.3
 */
function _fgets (&$h, $force=false) {
    $startpos = ftell($h);
	$s = fgets($h, 1024);
    
    if ((PHP_VER_LOWER43 == 1 || $force) && preg_match("/^([^\r\n]*[\r\n]{1,2})(.)/",trim($s), $ns)) {
		$s = $ns[1];
		fseek($h,$startpos+strlen($s));
	}
	
	return $s;
}

?>