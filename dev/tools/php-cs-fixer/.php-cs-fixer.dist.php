<?php

/* PHP 7.0 */
$finder = (new PhpCsFixer\Finder())
->in(__DIR__)
->exclude([
	'core/includes',
	'custom',
	'documents',
	'doctemplates',
	'vendor',
	'install/doctemplates',
	'htdocs/custom',
	'htdocs/includes',
	'htdocs/install/doctemplates',
])
->notPath('vendor');


/* PHP 7.4+ */
/*
$finder = (new PhpCsFixer\Finder())
	->in(__DIR__)
	->exclude([
		'custom',
		'documents',
		'htdocs/custom',
		'htdocs/includes',
	])
	->notPath([
		'vendor',
	]);
*/

return (new PhpCsFixer\Config())
	->setRules([
		// Apply PSR-12 as per https://wiki.dolibarr.org/index.php?title=Langages_et_normes#PHP:~:text=utiliser%20est%20le-,PSR%2D12,-(https%3A//www
		'@PSR12' => true,  // Disabled for now to limit number of changes

		// Minimum version Dolibarr v18.0.0
		// Compatibility with min 7.1 is announced with Dolibarr18.0 but
		// app is still working with 7.0 so no reason to abandon compatibility with this target for the moment.
		// So we use target PHP70 for the moment.
		'@PHP70Migration' => true,
		//'@PHP71Migration' => true,
                // Avoid adding public to const (incompatible with PHP 7.0):
                'visibility_required' => ['elements'=>['property', 'method']],

		//'strict_param' => true,
		//'array_syntax' => ['syntax' => 'short'],
		//'list_syntax' => false,
		//'visibility_required' => false,
		'array_syntax' => false,
		'ternary_to_null_coalescing' => false
	])
	->setFinder($finder)
	// TAB Indent violates PSR-12 "must" rule, but used in code
	// (See https://www.php-fig.org/psr/psr-12/#24-indenting )
	->setIndent("\t")
	// All files MUST use the Unix LF line ending only
	// https://www.php-fig.org/psr/psr-12/#22-files
	->setLineEnding("\n")
;
