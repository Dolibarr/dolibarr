<?php
/**
 * Restler 2 compatibility mode enabler
 */
use Luracast\Restler\Defaults;
use Luracast\Restler\AutoLoader;
use Luracast\Restler\CommentParser;

//changes in auto loading
$classMap = array();
//find lowercase php files representing a class/interface
foreach (explode(PATH_SEPARATOR, get_include_path()) as $path)
    foreach (new DirectoryIterator($path) as $fileInfo)
        if ($fileInfo->isFile()
            && 'php' === $fileInfo->getExtension()
            && ctype_lower($fileInfo->getBasename('.php'))
            && preg_match(
                '/^ *(class|interface|abstract +class)'
                    . ' +([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/m',
                file_get_contents($fileInfo->getPathname()),
                $matches
            )
        )
            $classMap[$matches[2]] = $fileInfo->getPathname();

AutoLoader::seen($classMap);

//changes in iAuthenticate
Defaults::$authenticationMethod = '__isAuthenticated';

include __DIR__ . '/iAuthenticate.php';

//changes in auto routing
Defaults::$smartAutoRouting = false;
Defaults::$smartParameterParsing = false;
Defaults::$autoValidationEnabled = false;

//changes in parsing embedded data in comments
CommentParser::$embeddedDataPattern = '/\((\S+)\)/ms';
CommentParser::$embeddedDataIndex = 1;