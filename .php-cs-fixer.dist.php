<?php

$finder = (new PhpCsFixer\Finder())
	->in(__DIR__)
	->exclude([
		'htdocs/includes',
	])
	->notPath([
		'vendor',
	])
;

return (new PhpCsFixer\Config())
	->setRules([
		// Apply PSR-12 as per https://wiki.dolibarr.org/index.php?title=Langages_et_normes#PHP:~:text=utiliser%20est%20le-,PSR%2D12,-(https%3A//www
		// '@PSR12' => true,  // Disabled for now to limit number of changes
		// Minimum version Dolibarr v18.0.0
		'@PHP71Migration' => true,
		//'strict_param' => true,
		//'array_syntax' => ['syntax' => 'short'],
		'array_syntax' => false,
	])
	->setFinder($finder)
	// TAB Indent violates PSR-12 "must" rule, but used in code
	// (See https://www.php-fig.org/psr/psr-12/#24-indenting )
	->setIndent("\t")
	// All files MUST use the Unix LF line ending only
	// https://www.php-fig.org/psr/psr-12/#22-files
	->setLineEnding("\n")
;
