<?php
/* Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 */


// Load default configuration (with many exclusions)
//
$config = include __DIR__.DIRECTORY_SEPARATOR."config.php";


// Note: When more than one fixer is attached to the same Notice, only the last fix is applied.
//require_once __DIR__.'/plugins/DeprecatedModuleNameFixer.php';
//require_once __DIR__.'/plugins/PriceFormFixer.php';
//require_once __DIR__.'/plugins/UrlEncodeStringifyFixer.php';
//require_once __DIR__.'/plugins/SelectDateFixer.php';
//require_once __DIR__.'/plugins/setPageOrientationFixer.php';
//require_once __DIR__.'/plugins/textwithpictoFixer.php';
//require_once __DIR__.'/plugins/ifsqlFixer.php';
require_once __DIR__.'/plugins/writeHTMLCellFixer.php';
//require_once __DIR__.'/plugins/MultiCellFixer.php';
//require_once __DIR__.'/plugins/setAutoPageBreakFixer.php';
//require_once __DIR__.'/plugins/CellFixer.php';

//$deprecatedModuleNameRegex = '/^(?!(?:'.implode('|', array_keys($DEPRECATED_MODULE_MAPPING)).')$).*/';

//require_once __DIR__.'/plugins/DeprecatedModuleNameFixer.php';

$config['exclude_file_regex'] = '@^('  // @phpstan-ignore-line
		.'dummy'  // @phpstan-ignore-line
		//.'|(?!htdocs/modulebuilderrtemplate/core/modules/mymodule/doc/pdf_standard_myobject.modules.php).*'  // Only this file for test @php-stan-ignore-line
		.'|htdocs/custom/.*'  // Ignore all custom modules @phpstan-ignore-line
		.'|htdocs/.*/canvas/.*/tpl/.*.tpl.php'  // @phpstan-ignore-line
		.'|htdocs/admin/tools/ui/.*'  // @phpstan-ignore-line
		//.'|htdocs/modulebuilder/template/.*'  // @phpstan-ignore-line
		// Included as stub (better analysis)
		.'|htdocs/includes/nusoap/.*'  // @phpstan-ignore-line
		// Included as stub (old version + incompatible typing hints)
		.'|htdocs/includes/restler/.*'  // @phpstan-ignore-line
		// Included as stub (did not seem properly analyzed by phan without it)
		.'|htdocs/includes/stripe/.*'  // @phpstan-ignore-line
		.'|htdocs/conf/conf.php'  // @phpstan-ignore-line
		//.'|htdocs/[^mi](?!.*(pdf_|tcpdf)).*\.php'  // @phpstan-ignore-line
		//.'|htdocs/(?!.*modules.*(pdf_|pdf.lib)).*\.php'  // @phpstan-ignore-line
		.')@';  // @phpstan-ignore-line

// $config['plugins'][] = __DIR__.'/plugins/ParamMatchRegexPlugin.php';
$config['plugins'] = [];
$config['plugins'][] = 'DeprecateAliasPlugin';
// $config['plugins'][] = __DIR__.'/plugins/GetPostFixerPlugin.php';
// $config['plugins'][] = 'PHPDocToRealTypesPlugin';

return $config;
