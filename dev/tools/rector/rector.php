<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->phpVersion(PhpVersion::PHP_71);
	$rectorConfig->paths([
		__DIR__ . '/../../../htdocs/',
		__DIR__ . '/../../../scripts/',
		__DIR__ . '/../../../test/phpunit/',
	]);
	$rectorConfig->skip([
		__DIR__ . '*/includes/*',
		__DIR__ . '/../../../htdocs/includes/*',
		__DIR__ . '/../../../htdocs/install/doctemplates/*'
	]);
	$rectorConfig->parallel(240);


	// register a single rule
	$rectorConfig->rule(Dolibarr\Rector\Renaming\GlobalToFunction::class);
	$rectorConfig->rule(Dolibarr\Rector\Renaming\UserRightsToFunction::class);

	// define sets of rules
	// $rectorConfig->sets([
	//	LevelSetList::UP_TO_PHP_71
	// ]);
};
