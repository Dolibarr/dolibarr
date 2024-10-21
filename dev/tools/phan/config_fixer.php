<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 */


// Load default configuration (with many exclusions)
//
$config = include __DIR__.DIRECTORY_SEPARATOR."config.php";


//require_once __DIR__.'/plugins/DeprecatedModuleNameFixer.php';
//require_once __DIR__.'/plugins/PriceFormFixer.php';
//require_once __DIR__.'/plugins/UrlEncodeStringifyFixer.php';
require_once __DIR__.'/plugins/SelectDateFixer.php';

//$deprecatedModuleNameRegex = '/^(?!(?:'.implode('|', array_keys($DEPRECATED_MODULE_MAPPING)).')$).*/';

require_once __DIR__.'/plugins/DeprecatedModuleNameFixer.php';

$config['exclude_file_regex'] = '@^('  // @phpstan-ignore-line
		.'dummy'  // @phpstan-ignore-line
		.'|htdocs/.*/canvas/.*/tpl/.*.tpl.php'  // @phpstan-ignore-line
		.'|htdocs/modulebuilder/template/.*'  // @phpstan-ignore-line
		// Included as stub (old version + incompatible typing hints)
		.'|htdocs/includes/restler/.*'  // @phpstan-ignore-line
		// Included as stub (did not seem properly analysed by phan without it)
		.'|htdocs/includes/stripe/.*'  // @phpstan-ignore-line
		.'|htdocs/conf/conf.php'  // @phpstan-ignore-line
		//.'|htdocs/[^c][^o][^r][^e][^/].*'  // For testing @phpstan-ignore-line
		//.'|htdocs/[^h].*' // For testing on restricted set @phpstan-ignore-line
		.')@';  // @phpstan-ignore-line

// $config['plugins'][] = __DIR__.'/plugins/ParamMatchRegexPlugin.php';
$config['plugins'][] = 'DeprecateAliasPlugin';
$config['plugins'][] = 'DeprecateAliasPlugin';
// $config['plugins'][] = __DIR__.'/plugins/GetPostFixerPlugin.php';
// $config['plugins'][] = 'PHPDocToRealTypesPlugin';

return $config;
