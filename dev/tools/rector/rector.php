<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->phpVersion(PhpVersion::PHP_71);
	//$rectorConfig->indent(' ', 4);
	$rectorConfig->paths([
		__DIR__ . '/../../../htdocs/',
		__DIR__ . '/../../../scripts/',
		__DIR__ . '/../../../test/phpunit/',
	]);
	$rectorConfig->skip([
		'**/includes/**',
		__DIR__ . '/../../../htdocs/custom',
		__DIR__ . '/../../../htdocs/install/doctemplates/*'
	]);
	$rectorConfig->parallel(240);


	// register rules

	// Remove use of list($a, $b) = ...
	//$rectorConfig->rule(Rector\Php71\Rector\List_\ListToArrayDestructRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\CreateFunctionToAnonymousFunctionRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\GetClassOnNullRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\Assign\ListEachRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\ParseStrWithResultArgumentRector::class);
	//$rectorConfig->rule(ReplaceEachAssignmentWithKeyCurrentRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\StringifyDefineRector::class);

	$rectorConfig->rule(Dolibarr\Rector\Renaming\GlobalToFunction::class);
	$rectorConfig->rule(Dolibarr\Rector\Renaming\UserRightsToFunction::class);

	// define sets of rules
	// $rectorConfig->sets([
	//	LevelSetList::UP_TO_PHP_71
	// ]);
};
