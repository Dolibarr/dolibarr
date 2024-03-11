<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->phpVersion(PhpVersion::PHP_71);
	//$rectorConfig->indent(' ', 4);
	$rectorConfig->paths([
		__DIR__ . '/../../../htdocs/custom/googlepeopleconnector',
	]);
	$rectorConfig->skip([
	]);
	$rectorConfig->parallel(240);


	// Register rules

	//$rectorConfig->rule(Rector\Php71\Rector\List_\ListToArrayDestructRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\CreateFunctionToAnonymousFunctionRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\GetClassOnNullRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\Assign\ListEachRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\ParseStrWithResultArgumentRector::class);
	//$rectorConfig->rule(Rector\Php72\Rector\FuncCall\StringifyDefineRector::class);

	$rectorConfig->rule(Dolibarr\Rector\Renaming\GlobalToFunction::class);
	$rectorConfig->rule(Dolibarr\Rector\Renaming\UserRightsToFunction::class);
	$rectorConfig->rule(Dolibarr\Rector\Renaming\EmptyGlobalToFunction::class);

	// Add all predefined rules to migrate to up to php 71
	// $rectorConfig->sets([
	//	LevelSetList::UP_TO_PHP_71
	// ]);
};
