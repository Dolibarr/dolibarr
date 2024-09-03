<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * For 'price()', replace $form parameter that is '' with 0.
 */

declare(strict_types=1);

use ast\flags;
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
use Microsoft\PhpParser\Node\ReservedWord;
use Microsoft\PhpParser\Token;

/**
 * This is a prototype, there are various features it does not implement.
 */

call_user_func(static function (): void {
	/**
	 * @param $code_base @unused-param
	 * @return ?FileEditSet a representation of the edit to make to replace a call to a function alias with a call to the original function
	 */
	$fix = static function (CodeBase $code_base, FileCacheEntry $contents, IssueInstance $instance): ?FileEditSet {
		$line = $instance->getLine();
		// print flags\TYPE_NULL;
		$expected_name = 'price';
		$edits = [];
		foreach ($contents->getNodesAtLine($line) as $node) {
			if (!$node instanceof ArgumentExpressionList) {
				continue;
			}
			$arguments = $node->children;
			if (count($arguments) < 2) {
				// print "Arg Count is ".count($arguments)." - Skip $instance".PHP_EOL;
				continue;
			}

			$is_actual_call = $node->parent instanceof CallExpression;
			if (!$is_actual_call) {
				print "Not actual call - Skip $instance".PHP_EOL;
				continue;
			}
			print "Actual call - $instance".PHP_EOL;
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
				if ($argument instanceof ArgumentExpression) {
					print "Type$i: ".get_class($argument->expression).PHP_EOL;
				}
			}

			$stringValue = null;
			foreach ([1] as $argIdx) {
				$arg = $arguments[$argIdx * 2];  // Pair number to skip "TOKEN" (,)

				if (
					$arg instanceof ArgumentExpression
					&& $arg->expression instanceof StringLiteral
				) {
					// Get the string value of the StringLiteral
					$stringValue = $arg->expression->getStringContentsText();
					print "String is '$stringValue'".PHP_EOL;
				} elseif ($arg instanceof ArgumentExpression && $arg->expression instanceof ReservedWord) {
					$child = $arg->expression->children;
					if (!$child instanceof Token) {
						continue;
					}
					$token_str = (new NodeUtils($contents->getContents()))->tokenToString($child);
					print "$token_str KIND:".($child->kind ?? 'no kind')." ".get_class($child).PHP_EOL;

					if ($token_str !== 'null') {
						continue;
					}

					$stringValue = '';  // Fake empty
				} else {
					print "Expression is not string or null ".get_class($arg)."/".get_class($arg->expression)."- Skip $instance".PHP_EOL;
					continue;
				}

				if ($stringValue !== '') {
					print "Not replacing \$form which is '$stringValue'/".get_class($arg)."/".get_class($arg->expression)."- Skip $instance".PHP_EOL;
					continue;
				}

				// Reached end of switch case without "continue" -> replace
				$replacement = 0;

				print "Fixture elem on $line - $actual_name(...'$stringValue'...) - $instance".PHP_EOL;

				// Determine replacement
				$replacement = '0';

				// Get the first argument (delimiter)
				$argument_to_replace = $arg;

				$arg_start_pos = $argument_to_replace->getStartPosition();
				$arg_end_pos = $argument_to_replace->getEndPosition();

				// Remove deprecated module name
				$edits[] = new FileEdit($arg_start_pos, $arg_end_pos, $replacement);
			}
		}
		if ($edits) {
			return new FileEditSet($edits);
		}
		return null;
	};
	IssueFixer::registerFixerClosure(
		'PhanTypeMismatchArgumentProbablyReal',
		$fix
	);
});
