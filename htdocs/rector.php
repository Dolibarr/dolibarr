<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->paths([
		__DIR__ . '/accountancy',
	/*    __DIR__ . '/adherents',
		__DIR__ . '/admin',
		__DIR__ . '/api',
		__DIR__ . '/asset',
		__DIR__ . '/asterisk',
		__DIR__ . '/barcode',
		__DIR__ . '/blockedlog',
		__DIR__ . '/bom',
		__DIR__ . '/bookcal',
		__DIR__ . '/bookmarks',
		__DIR__ . '/categories',
		__DIR__ . '/collab',
		__DIR__ . '/comm',
		__DIR__ . '/commande',
		__DIR__ . '/compta',
		__DIR__ . '/conf',
		__DIR__ . '/contact',
		__DIR__ . '/contrat',
		__DIR__ . '/core',
		__DIR__ . '/cron',
		__DIR__ . '/custom',
		__DIR__ . '/datapolicy',
		__DIR__ . '/dav',
		__DIR__ . '/debugbar',
		__DIR__ . '/delivery',
		__DIR__ . '/don',
		__DIR__ . '/ecm',
		__DIR__ . '/emailcollector',
		__DIR__ . '/eventorganization',
		__DIR__ . '/expedition',
		__DIR__ . '/expensereport',
		__DIR__ . '/exports',
		__DIR__ . '/externalsite',
		__DIR__ . '/fichinter',
		__DIR__ . '/fourn',
		__DIR__ . '/ftp',
		__DIR__ . '/holiday',
		__DIR__ . '/hrm',
		__DIR__ . '/imports',
		__DIR__ . '/install',
		__DIR__ . '/intracommreport',
		__DIR__ . '/knowledgemanagement',
		__DIR__ . '/loan',
		__DIR__ . '/mailmanspip',
		__DIR__ . '/margin',
		__DIR__ . '/mrp',
		__DIR__ . '/multicurrency',
		__DIR__ . '/opensurvey',
		__DIR__ . '/partnership',
		__DIR__ . '/paybox',
		__DIR__ . '/paypal',
		__DIR__ . '/printing',
		__DIR__ . '/product',
		__DIR__ . '/projet',
		__DIR__ . '/public',
		__DIR__ . '/reception',
		__DIR__ . '/recruitment',
		__DIR__ . '/resource',
		__DIR__ . '/salaries',
		__DIR__ . '/societe',
		__DIR__ . '/stripe',
		__DIR__ . '/supplier_proposal',
		__DIR__ . '/support',
		__DIR__ . '/takepos',
		__DIR__ . '/theme',
		__DIR__ . '/ticket',
		__DIR__ . '/user',
		__DIR__ . '/webhook',
		__DIR__ . '/webservices',
		__DIR__ . '/website',
		__DIR__ . '/workstation',
		__DIR__ . '/zapier',*/
	]);

	// register a single rule
	$rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

	// define sets of rules
		$rectorConfig->sets([
			//LevelSetList::UP_TO_PHP_81,
			SetList::CODE_QUALITY,
			SetList::CODING_STYLE,
			SetList::DEAD_CODE,
			SetList::EARLY_RETURN,
			SetList::NAMING
		]);
};
