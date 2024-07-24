<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->phpVersion(PhpVersion::PHP_71);
	//$rectorConfig->indent(' ', 4);

	// Traits seems not supported correctly by rector without declaring them as bootstrapFiles
	$arrayoftraitfiles = array(
		__DIR__ . '/../../../htdocs/core/class/commonincoterm.class.php',
		__DIR__ . '/../../../htdocs/core/class/commonpeople.class.php',
		__DIR__ . '/../../../htdocs/core/class/commonsocialnetworks.class.php'
	);
	$rectorConfig->bootstrapFiles($arrayoftraitfiles);

	$rectorConfig->paths([
		__DIR__ . '/../../../htdocs/',
		__DIR__ . '/../../../scripts/',
		__DIR__ . '/../../../test/phpunit/',
	]);
	$rectorConfig->skip([
		'**/includes/**',
		'**/custom/**',
		'**/vendor/**',
		'**/rector/**',		// Disable this line to test the "test.php" file.
		__DIR__ . '/../../../htdocs/custom/',
		__DIR__ . '/../../../htdocs/install/doctemplates/*'
	]);
	$rectorConfig->parallel(240);


	// Register rules

	//$rectorConfig->rule(Rector\Php71\Rector\List_\ListToArrayDestructRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\CreateFunctionToAnonymousFunctionRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\GetClassOnNullRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\Assign\ListEachRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\ParseStrWithResultArgumentRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\StringifyDefineRector::class);

	//$rectorConfig->rule(ReplaceEachAssignmentWithKeyCurrentRector::class);

	$rectorConfig->rule(Rector\CodeQuality\Rector\FuncCall\FloatvalToTypeCastRector::class);
	$rectorConfig->rule(Rector\CodeQuality\Rector\FuncCall\BoolvalToTypeCastRector::class);
	$rectorConfig->rule(Rector\CodeQuality\Rector\NotEqual\CommonNotEqualRector::class);
	//$rectorconfig->rule(Rector\CodeQuality\Rector\If_\CompleteMissingIfElseBracketRector::class);
	$rectorConfig->rule(Rector\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector::class);

	$rectorConfig->rule(Dolibarr\Rector\Renaming\GlobalToFunction::class);
	$rectorConfig->rule(Dolibarr\Rector\Renaming\UserRightsToFunction::class);
	$rectorConfig->rule(Dolibarr\Rector\Renaming\EmptyGlobalToFunction::class);

	// Add all predefined rules to migrate to up to php 71
	// $rectorConfig->sets([
	//	LevelSetList::UP_TO_PHP_71
	// ]);
};
