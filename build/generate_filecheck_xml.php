#!/usr/bin/env php
<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       build/generate_filecheck_xml.php
 *		\ingroup    dev
 * 		\brief      This script create a xml checksum file
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}


// Main
parse_str($argv[1]);
//$outputfile=dirname(__FILE__).'/../htdocs/install/filelist-'.$release.'.xml';
$outputfile=dirname(__FILE__).'/../htdocs/install/filelist.xml';
$fp = fopen($outputfile,'w');
fputs($fp, '<?xml version="1.0" encoding="UTF-8" ?>'."\n");
fputs($fp, '<checksum_list version="'.$release.'">'."\n");

fputs($fp, '<dolibarr_htdocs_dir>'."\n");

$dir_iterator1 = new RecursiveDirectoryIterator(dirname(__FILE__).'/../htdocs/');
$iterator1 = new RecursiveIteratorIterator($dir_iterator1);
// need to ignore document custom etc
$files = new RegexIterator($iterator1, '#^(?:[A-Z]:)?(?:/(?!(?:custom|documents|conf|install|nltechno))[^/]+)+/[^/]+\.(?:php|css|html|js|json|tpl|jpg|png|gif|sql|lang)$#i');
$dir='';
$needtoclose=0;
foreach ($files as $file) {
    $newdir = str_replace(dirname(__FILE__).'/../htdocs', '', dirname($file));
    if ($newdir!=$dir) {
        if ($needtoclose)
            fputs($fp, '</dir>'."\n");
        fputs($fp, '<dir name="'.$newdir.'" >'."\n");
        $dir = $newdir;
        $needtoclose=1;
    }
    if (filetype($file)=="file") {
        fputs($fp, '<md5file name="'.basename($file).'">'.md5_file($file).'</md5file>'."\n");
    }
}
fputs($fp, '</dir>'."\n");
fputs($fp, '</dolibarr_htdocs_dir>'."\n");


fputs($fp, '<dolibarr_script_dir version="'.$release.'">'."\n");

$dir_iterator2 = new RecursiveDirectoryIterator(dirname(__FILE__).'/../scripts/');
$iterator2 = new RecursiveIteratorIterator($dir_iterator2);
// need to ignore document custom etc
$files = new RegexIterator($iterator2, '#^(?:[A-Z]:)?(?:/(?!(?:custom|documents|conf|install|nltechno))[^/]+)+/[^/]+\.(?:php|css|html|js|json|tpl|jpg|png|gif|sql|lang)$#i');
$dir='';
$needtoclose=0;
foreach ($files as $file) {
    $newdir = str_replace(dirname(__FILE__).'/../scripts', '', dirname($file));
    if ($newdir!=$dir) {
        if ($needtoclose)
            fputs($fp, '</dir>'."\n");
        fputs($fp, '<dir name="'.$newdir.'" >'."\n");
        $dir = $newdir;
        $needtoclose=1;
    }
    if (filetype($file)=="file") {
        fputs($fp, '<md5file name="'.basename($file).'">'.md5_file($file).'</md5file>'."\n");
    }
}
fputs($fp, '</dir>'."\n");
fputs($fp, '</dolibarr_script_dir>'."\n");


fputs($fp, '</checksum_list>'."\n");
fclose($fp);

print "File ".$outputfile." generated\n";

exit(0);
