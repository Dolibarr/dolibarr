<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */
define('DOL_PROJECT_ROOT', __DIR__.'/../../..');
define('DOL_DOCUMENT_ROOT', DOL_PROJECT_ROOT.'/htdocs');
define('PHAN_DIR', __DIR__);
$sanitizeRegex
	= '/^(array:)?(?:'.implode(
		'|',
		array(
			// Documented:
			'none',
			'array',
			'int',
			'intcomma',
			'alpha',
			'alphawithlgt',
			'alphanohtml',
			'MS',
			'aZ',
			'aZ09',
			'aZ09arobase',
			'aZ09comma',
			'san_alpha',
			'restricthtml',
			'nohtml',
			'custom',
			// Not documented:
			'email',
			'restricthtmlallowclass',
			'restricthtmlallowunvalid',
			'restricthtmlnolink',
			//'ascii',
			//'categ_id',
			//'chaine',

			//'html',
			//'boolean',
			//'double',
			//'float',
			//'string',
		)
	).')*$/';

/**
 * Map deprecated module names to new module names
 */
$DEPRECATED_MODULE_MAPPING = array(
	'actioncomm' => 'agenda',
	'adherent' => 'member',
	'adherent_type' => 'member_type',
	'banque' => 'bank',
	'categorie' => 'category',
	'commande' => 'order',
	'contrat' => 'contract',
	'entrepot' => 'stock',
	'expedition' => 'delivery_note',
	'facture' => 'invoice',
	'ficheinter' => 'intervention',
	'product_fournisseur_price' => 'productsupplierprice',
	'product_price' => 'productprice',
	'projet'  => 'project',
	'propale' => 'propal',
	'socpeople' => 'contact',
);

/**
 * Map module names to the 'class' name (the class is: mod<CLASSNAME>)
 * Value is null when the module is not internal to the default
 * Dolibarr setup.
 */
$VALID_MODULE_MAPPING = array(
	'accounting' => 'Accounting',
	'agenda' => 'Agenda',
	'ai' => 'Ai',
	'anothermodule' => null,
	'api' => 'Api',
	'asset' => 'Asset',
	'bank' => 'Banque',
	'barcode' => 'Barcode',
	'blockedlog' => 'BlockedLog',
	'bom' => 'Bom',
	'bookcal' => 'BookCal',
	'bookmark' => 'Bookmark',
	'cashdesk' => null,  // TODO: fill in proper class
	'category' => 'Categorie',
	'clicktodial' => 'ClickToDial',
	'collab' => 'Collab',
	'comptabilite' => 'Comptabilite',
	'contact' => null,  // TODO: fill in proper class
	'contract' => 'Contrat',
	'cron' => 'Cron',
	'datapolicy' => 'DataPolicy',
	'dav' => 'Dav',
	'debugbar' => 'DebugBar',
	'delivery_note' => 'Expedition',
	'deplacement' => 'Deplacement',
	"documentgeneration" => 'DocumentGeneration',
	'don' => 'Don',
	'dynamicprices' => 'DynamicPrices',
	'ecm' => 'ECM',
	'ecotax' => null,  // TODO: External module ?
	'emailcollector' => 'EmailCollector',
	'eventorganization' => 'EventOrganization',
	'expensereport' => 'ExpenseReport',
	'export' => 'Export',
	'externalrss' => 'ExternalRss',
	'externalsite' => 'ExternalSite',
	'fckeditor' => 'Fckeditor',
	'fournisseur' => 'Fournisseur',
	'ftp' => 'FTP',
	'geoipmaxmind' => 'GeoIPMaxmind',
	'google' => null,  // External ?
	'gravatar' => 'Gravatar',
	'holiday' => 'Holiday',
	'hrm' => 'HRM',
	'import' => 'Import',
	'incoterm' => 'Incoterm',
	'intervention' => 'Ficheinter',
	'intracommreport' => 'Intracommreport',
	'invoice' => 'Facture',
	'knowledgemanagement' => 'KnowledgeManagement',
	'label' => 'Label',
	'ldap' => 'Ldap',
	'loan' => 'Loan',
	'mailing' => 'Mailing',
	'mailman' => null,  // Same module as mailmanspip -> MailmanSpip ??
	'mailmanspip' => 'MailmanSpip',
	'margin' => 'Margin',
	'member' => 'Adherent',
	'memcached' => null, // TODO: External module?
	'modulebuilder' => 'ModuleBuilder',
	'mrp' => 'Mrp',
	'multicompany' => null, // Not provided by default, no module tests
	'multicurrency' => 'MultiCurrency',
	'mymodule' => null, // modMyModule - Name used in module builder (avoid false positives)
	'notification' => 'Notification',
	'numberwords' => null, // Not provided by default, no module tests
	'oauth' => 'Oauth',
	'openstreetmap' => null,  // External module?
	'opensurvey' => 'OpenSurvey',
	'order' => 'Commande',
	'partnership' => 'Partnership',
	'paybox' => 'Paybox',
	'paymentbybanktransfer' => 'PaymentByBankTransfer',
	'paypal' => 'Paypal',
	'paypalplus' => null,
	'prelevement' => 'Prelevement',
	'printing' => 'Printing',
	'product' => 'Product',
	'productbatch' => 'ProductBatch',
	'productprice' => null,
	'productsupplierprice' => null,
	'project' => 'Projet',
	'propal' => 'Propale',
	'receiptprinter' => 'ReceiptPrinter',
	'reception' => 'Reception',
	'recruitment' => 'Recruitment',
	'resource' => 'Resource',
	'salaries' => 'Salaries',
	'service' => 'Service',
	'socialnetworks' => 'SocialNetworks',
	'societe' => 'Societe',
	'stock' => 'Stock',
	'stocktransfer' => 'StockTransfer',
	'stripe' => 'Stripe',
	'supplier_invoice' => null,  // Special case, uses invoice
	'supplier_order' => null,  // Special case, uses invoice
	'supplier_proposal' => 'SupplierProposal',
	'syslog' => 'Syslog',
	'takepos' => 'TakePos',
	'tax' => 'Tax',
	'ticket' => 'Ticket',
	'user' => 'User',
	'variants' => 'Variants',
	'webhook' => 'Webhook',
	'webportal' => 'WebPortal',
	'webservices' => 'WebServices',
	'webservicesclient' => 'WebServicesClient',
	'website' => 'Website',
	'workflow' => 'Workflow',
	'workstation' => 'Workstation',
	'zapier' => 'Zapier',
);

$moduleNameRegex = '/^(?:'.implode('|', array_merge(array_keys($DEPRECATED_MODULE_MAPPING), array_keys($VALID_MODULE_MAPPING))).')$/';

$deprecatedModuleNameRegex = '/^(?!(?:'.implode('|', array_keys($DEPRECATED_MODULE_MAPPING)).')$).*/';

/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command line arguments will be applied
 * after this file is read.
 */
return [
	//	'processes' => 6,
	'backward_compatibility_checks' => false,
	'simplify_ast' => true,
	'analyzed_file_extensions' => ['php','inc'],
	'globals_type_map' => [
		'conf' => '\Conf',
		'db' => '\DoliDB',
		'extrafields' => '\ExtraFields',
		'hookmanager' => '\HookManager',
		'langs' => '\Translate',
		'mysoc' => '\Societe',
		'nblines' => '\int',
		'user' => '\User',
		'dolibarr_main_data_root' => 'string',
		'dolibarr_main_authentication' => 'string',
		'dolibarr_main_demo' => 'string',
		'menumanager' => 'string',
		'errormsg' => 'string',
		'form' => '\Form',
		'object_rights' => 'int|stdClass',
		'disableedit' => 'int<0,1>',
		'disablemove' => 'int<0,1>',
		'disableremove' => 'int<0,1>',
		// Found in dol_eval
		'website' => '\WebSite',
		'websitepage' => '\WebSitePage',
		'websitepagefile' => 'string',
		'action' => 'string',
		'mainmenu' => 'string',
		'leftmenu' => 'string',
		'objectoffield' => '\CommonObject',
		// 'object' => '\CommonObject',  // Deprecated, not enabled because conflicts with $object assignments
		'obj' => '\CommonObject',     // Deprecated
	],

	// Supported values: `'5.6'`, `'7.0'`, `'7.1'`, `'7.2'`, `'7.3'`, `'7.4'`, `null`.
	// If this is set to `null`,
	// then Phan assumes the PHP version which is closest to the minor version
	// of the php executable used to execute Phan.
	//"target_php_version" => null,
	"target_php_version" => '8.2',
	//"target_php_version" => '7.3',
	//"target_php_version" => '5.6',

	// A list of directories that should be parsed for class and
	// method information. After excluding the directories
	// defined in exclude_analysis_directory_list, the remaining
	// files will be statically analyzed for errors.
	//
	// Thus, both first-party and third-party code being used by
	// your application should be included in this list.
	'directory_list' => [
		'htdocs',
		PHAN_DIR . '/stubs/',
	],

	// A directory list that defines files that will be excluded
	// from static analysis, but whose class and method
	// information should be included.
	//
	// Generally, you'll want to include the directories for
	// third-party code (such as "vendor/") in this list.
	//
	// n.b.: If you'd like to parse but not analyze 3rd
	//	party code, directories containing that code
	//	should be added to the `directory_list` as
	//	to `exclude_analysis_directory_list`.
	"exclude_analysis_directory_list" => [
		'htdocs/includes/',
		'htdocs/core/class/lessc.class.php', // External library
		PHAN_DIR . '/stubs/',
	],
	//'exclude_file_regex' => '@^vendor/.*/(tests?|Tests?)/@',
	'exclude_file_regex' => '@^('  // @phpstan-ignore-line
		.'dummy'  // @phpstan-ignore-line
		.'|htdocs/.*/canvas/.*/tpl/.*.tpl.php'  // @phpstan-ignore-line
		.'|htdocs/modulebuilder/template/.*'  // @phpstan-ignore-line
		// Included as stub (old version + incompatible typing hints)
		.'|htdocs/includes/restler/.*'  // @phpstan-ignore-line
		// Included as stub (did not seem properly analysed by phan without it)
		.'|htdocs/includes/stripe/.*'  // @phpstan-ignore-line
		.')@',  // @phpstan-ignore-line

	// A list of plugin files to execute.
	// Plugins which are bundled with Phan can be added here by providing their name
	// (e.g. 'AlwaysReturnPlugin')
	//
	// Documentation about available bundled plugins can be found
	// at https://github.com/phan/phan/tree/master/.phan/plugins
	//
	// Alternately, you can pass in the full path to a PHP file
	// with the plugin's implementation (e.g. 'vendor/phan/phan/.phan/plugins/AlwaysReturnPlugin.php')
	'ParamMatchRegexPlugin' => [
		'/^GETPOST$/' => [1, $sanitizeRegex, 'GetPostUnknownSanitizeType'],
		'/^isModEnabled$/' => [0, $moduleNameRegex, 'UnknownModuleName'],
		// Note: trick to have different key for same regex:
		'/^isModEnable[d]$/' => [0, $deprecatedModuleNameRegex, "DeprecatedModuleName"],
		'/^sanitizeVal$/' => [1, $sanitizeRegex,"UnknownSanitizeType"],
	],
	'plugins' => [
		__DIR__.'/plugins/NoVarDumpPlugin.php',
		__DIR__.'/plugins/ParamMatchRegexPlugin.php',
		'DeprecateAliasPlugin',
		//'EmptyMethodAndFunctionPlugin',
		'InvalidVariableIssetPlugin',
		//'MoreSpecificElementTypePlugin',
		'NoAssertPlugin',
		'NotFullyQualifiedUsagePlugin',
		'PHPDocRedundantPlugin',
		'PHPUnitNotDeadCodePlugin',
		//'PossiblyStaticMethodPlugin',
		'PreferNamespaceUsePlugin',
		'PrintfCheckerPlugin',
		'RedundantAssignmentPlugin',

		'ConstantVariablePlugin', // Warns about values that are actually constant
		//'HasPHPDocPlugin', // Requires PHPDoc
		'InlineHTMLPlugin', // html in PHP file, or at end of file
		'NonBoolBranchPlugin', // Requires test on bool, nont on ints
		'NonBoolInLogicalArithPlugin',
		'NumericalComparisonPlugin',
		// 'PHPDocToRealTypesPlugin',  // Report/Add types to function definitions
		'PHPDocInWrongCommentPlugin', // Missing /** (/* was used)
		//'ShortArrayPlugin', // Checks that [] is used
		//'StrictLiteralComparisonPlugin',
		'UnknownClassElementAccessPlugin',
		'UnknownElementTypePlugin',
		'WhitespacePlugin',
		//'RemoveDebugStatementPlugin', // Reports echo, print, ...
		'SimplifyExpressionPlugin',
		//'StrictComparisonPlugin', // Expects ===
		'SuspiciousParamOrderPlugin',
		'UnsafeCodePlugin',
		//'UnusedSuppressionPlugin',

		'AlwaysReturnPlugin',
		//'DollarDollarPlugin',
		'DuplicateArrayKeyPlugin',
		'DuplicateExpressionPlugin',
		'PregRegexCheckerPlugin',
		'PrintfCheckerPlugin',
		'SleepCheckerPlugin',
		// Checks for syntactically unreachable statements in
		// the global scope or function bodies.
		'UnreachableCodePlugin',
		'UseReturnValuePlugin',
		'EmptyStatementListPlugin',
		'LoopVariableReusePlugin',
	],

	// Add any issue types (such as 'PhanUndeclaredMethod')
	// here to inhibit them from being reported
	'suppress_issue_types' => [
		'PhanCompatibleNegativeStringOffset',	// return false positive

		'PhanPluginWhitespaceTab',		// Dolibarr used tabs
		'PhanPluginCanUsePHP71Void',	// Dolibarr is maintaining 7.0 compatibility
		'PhanPluginShortArray',			// Dolibarr uses array()
		'PhanPluginShortArrayList',		// Dolibarr uses array()
		// Fixers From PHPDocToRealTypesPlugin:
		'PhanPluginCanUseParamType',			// Fixer - Report/Add types in the function definition (function abc(string $var) (adds string)
		'PhanPluginCanUseReturnType',			// Fixer - Report/Add return types in the function definition (function abc(string $var) (adds string)
		'PhanPluginCanUseNullableParamType',	// Fixer - Report/Add nullable parameter types in the function definition
		'PhanPluginCanUseNullableReturnType',	// Fixer - Report/Add nullable return types in the function definition

		'PhanPluginNonBoolBranch',			// Not essential - 31240+ occurrences
		'PhanPluginNumericalComparison',	// Not essential - 19870+ occurrences
		'PhanTypeMismatchArgument',			// Not essential - 12300+ occurrences
		'PhanPluginNonBoolInLogicalArith',	// Not essential - 11040+ occurrences
		'PhanPluginConstantVariableScalar',	// Not essential - 5180+ occurrences
		'PhanPluginDuplicateAdjacentStatement',
		'PhanPluginDuplicateConditionalTernaryDuplication',		// 2750+ occurrences
		'PhanPluginDuplicateConditionalNullCoalescing',	// Not essential - 990+ occurrences
		'PhanPluginRedundantAssignmentInGlobalScope',	// Not essential, a lot of false warning
	],
	// You can put relative paths to internal stubs in this config option.
	// Phan will continue using its detailed type annotations,
	// but load the constants, classes, functions, and classes (and their Reflection types)
	// from these stub files (doubling as valid php files).
	// Use a different extension from php (and preferably a separate folder)
	// to avoid accidentally parsing these as PHP (includes projects depending on this).
	// The 'mkstubs' script can be used to generate your own stubs (compatible with php 7.0+ right now)
	// Note: The array key must be the same as the extension name reported by `php -m`,
	// so that phan can skip loading the stubs if the extension is actually available.
	'autoload_internal_extension_signatures' => [
				// Stubs may be available at https://github.com/JetBrains/phpstorm-stubs/tree/master

	// Xdebug stubs are bundled with Phan 0.10.1+/0.8.9+ for usage,
	// because Phan disables xdebug by default.
	//'xdebug'	=> 'vendor/phan/phan/.phan/internal_stubs/xdebug.phan_php',
	//'memcached'  => PHAN_DIR . '/your_internal_stubs_folder_name/memcached.phan_php',
	//'PDO'  => PHAN_DIR . '/stubs/PDO.phan_php',
	'brotli'  => PHAN_DIR . '/stubs/brotli.phan_php',
	'curl'  => PHAN_DIR . '/stubs/curl.phan_php',
	'calendar'  => PHAN_DIR . '/stubs/calendar.phan_php',
	'fileinfo'  => PHAN_DIR . '/stubs/fileinfo.phan_php',
	'ftp'  => PHAN_DIR . '/stubs/ftp.phan_php',
	'gd'  => PHAN_DIR . '/stubs/gd.phan_php',
	'geoip'  => PHAN_DIR . '/stubs/geoip.phan_php',
	'imap'  => PHAN_DIR . '/stubs/imap.phan_php',
	'intl'  => PHAN_DIR . '/stubs/intl.phan_php',
	'ldap'  => PHAN_DIR . '/stubs/ldap.phan_php',
	'mcrypt'  => PHAN_DIR . '/stubs/mcrypt.phan_php',
	'memcache'  => PHAN_DIR . '/stubs/memcache.phan_php',
	'mysqli'  => PHAN_DIR . '/stubs/mysqli.phan_php',
	'pdo_cubrid'  => PHAN_DIR . '/stubs/pdo_cubrid.phan_php',
	'pdo_mysql'  => PHAN_DIR . '/stubs/pdo_mysql.phan_php',
	'pdo_pgsql'  => PHAN_DIR . '/stubs/pdo_pgsql.phan_php',
	'pdo_sqlite'  => PHAN_DIR . '/stubs/pdo_sqlite.phan_php',
	'pgsql'  => PHAN_DIR . '/stubs/pgsql.phan_php',
	'session'  => PHAN_DIR . '/stubs/session.phan_php',
	'simplexml'  => PHAN_DIR . '/stubs/SimpleXML.phan_php',
	'soap'  => PHAN_DIR . '/stubs/soap.phan_php',
	'sockets'  => PHAN_DIR . '/stubs/sockets.phan_php',
	'zip'  => PHAN_DIR . '/stubs/zip.phan_php',
	],

	];
