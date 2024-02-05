<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

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
	//Not yet ready: $rectorconfig->rule(Rector\CodeQuality\Rector\If_\CompleteMissingIfElseBracketRector::class);
	$rectorConfig->rule(Rector\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector::class);

	$rectorConfig->rule(Dolibarr\Rector\Renaming\EmptyGlobalToFunction::class);
	$rectorConfig->rule(Dolibarr\Rector\Renaming\EmptyUserRightsToFunction::class);
	$rectorConfig->rule(Dolibarr\Rector\Renaming\GlobalToFunction::class);
	$rectorConfig->rule(Dolibarr\Rector\Renaming\UserRightsToFunction::class);

	// Add all predefined rules to migrate to up to php 71.
	// Warning this break tab spacing of arrays on several lines
	/*$rectorConfig->sets([
		LevelSetList::UP_TO_PHP_70
	]);*/
	// Add predefined rules for a given version only
	//$rectorConfig->import(SetList::PHP_70);
	//$rectorConfig->import(SetList::PHP_71);
	//$rectorConfig->import(SetList::PHP_72);
	//$rectorConfig->import(SetList::PHP_73);
	//$rectorConfig->import(SetList::PHP_74);
	//$rectorConfig->import(SetList::PHP_80);
	//$rectorConfig->import(SetList::PHP_81);
	//$rectorConfig->import(SetList::PHP_82);
	//$rectorConfig->import(SetList::PHP_83);
};
