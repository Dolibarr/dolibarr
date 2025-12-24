<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */

declare(strict_types=1);

use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\QualifiedName;
use Phan\AST\TolerantASTConverter\NodeUtils;
use Phan\CodeBase;
use Phan\IssueInstance;
use Phan\Library\FileCacheEntry;
use Phan\Plugin\Internal\IssueFixingPlugin\FileEdit;
use Phan\Plugin\Internal\IssueFixingPlugin\FileEditSet;
use Phan\Plugin\Internal\IssueFixingPlugin\IssueFixer;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;
use Microsoft\PhpParser\Node\DelimitedList\ArgumentExpressionList;
use Microsoft\PhpParser\Node\StringLiteral;

/**
 * This is a prototype, there are various features it does not implement.
 */

call_user_func(static function (): void {
	/**
	 * @param $code_base @unused-param
	 * @return ?FileEditSet a representation of the edit to make to replace a call to a function alias with a call to the original function
	 */
	$fix = static function (CodeBase $code_base, FileCacheEntry $contents, IssueInstance $instance): ?FileEditSet {
		$DEPRECATED_MODULE_MAPPING = array(
		'actioncomm' => 'agenda',
		'adherent' => 'member',
		'adherent_type' => 'member_type',
		'banque' => 'bank',
		'categorie' => 'category',
		'commande' => 'order',
		'contrat' => 'contract',
		'entrepot' => 'stock',
		'expedition' => 'shipping',
		'facture' => 'invoice',
		'ficheinter' => 'intervention',
		'product_fournisseur_price' => 'productsupplierprice',
		'product_price' => 'productprice',
		'projet'  => 'project',
		'propale' => 'propal',
		'socpeople' => 'contact',
		);

		$line = $instance->getLine();
		$expected_name = 'isModEnabled';
		$edits = [];
		foreach ($contents->getNodesAtLine($line) as $node) {
			if (!$node instanceof ArgumentExpressionList) {
				continue;
			}
			$arguments = $node->children;
			if (count($arguments) != 1) {
				print "Arg Count is ".count($arguments)." - Skip $instance".PHP_EOL;
				continue;
			}

			$is_actual_call = $node->parent instanceof CallExpression;
			if (!$is_actual_call) {
				print "Not actual call - Skip $instance".PHP_EOL;
				continue;
			}
			$callable = $node->parent;

			$callableExpression = $callable->callableExpression;

			if ($callableExpression instanceof Microsoft\PhpParser\Node\QualifiedName) {
				$actual_name = $callableExpression->getResolvedName();
			} else {
				print "Callable expression is ".get_class($callableExpression)."- Skip $instance".PHP_EOL;
				continue;
			}

			if ((string) $actual_name !== (string) $expected_name) {
				print "Name unexpected '$actual_name'!='$expected_name' - Skip $instance".PHP_EOL;
				continue;
			}

			foreach ($arguments as $i => $argument) {
				print "Type$i: ".get_class($argument).PHP_EOL;
			}

			$arg1 = $arguments[0];

			if ($arg1 instanceof ArgumentExpression && $arg1->expression instanceof StringLiteral) {
				// Get the string value of the StringLiteral
				$stringValue = $arg1->expression->getStringContentsText();
			} else {
				print "Expression is not string ".get_class($arg1)."/".get_class($arg1->expression)."- Skip $instance".PHP_EOL;
				continue;
			}
			print "Fixture elem on $line - $actual_name('$stringValue') - $instance".PHP_EOL;

			// Check that module is deprecated
			if (isset($DEPRECATED_MODULE_MAPPING[$stringValue])) {
				$replacement = $DEPRECATED_MODULE_MAPPING[$stringValue];
			} else {
				print "Module is not deprecated in $expected_name - Skip $instance".PHP_EOL;
				continue;
			}

			// Get the first argument (delimiter)
			$moduleargument = $arguments[0];

			$arg_start_pos = $moduleargument->getStartPosition() + 1;
			$arg_end_pos = $moduleargument->getEndPosition() - 1;

			// Remove deprecated module name
			$edits[] = new FileEdit($arg_start_pos, $arg_end_pos, $replacement);
		}
		if ($edits) {
			return new FileEditSet($edits);
		}
		return null;
	};
	IssueFixer::registerFixerClosure(
		'DeprecatedModuleName',
		$fix
	);
});
