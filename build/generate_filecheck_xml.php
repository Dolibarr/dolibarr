<?php
parse_str($argv[1]);
$fp = fopen(dirname(__FILE__).'/../htdocs/core/filelist-'.$release.'.xml','w');
fputs($fp, '<?xml version="1.0" encoding="UTF-8" ?>'."\n");
fputs($fp, '<checksum_list>'."\n");
fputs($fp, '<dolibarr_root_dir version="'.$release.'">'."\n");
$dir_iterator = new RecursiveDirectoryIterator(dirname(__FILE__).'/../htdocs/');
$iterator = new RecursiveIteratorIterator($dir_iterator);
// need to ignore document custom etc
$files = new RegexIterator($iterator, '#^(?:[A-Z]:)?(?:/(?!(?:custom|documents|conf|install))[^/]+)+/[^/]+\.(?:php|html|js|json|tpl|jpg|png|gif|sql|lang)$#i');
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
fputs($fp, '</dolibarr_root_dir>'."\n");
fputs($fp, '</checksum_list>'."\n");
fclose($fp);
