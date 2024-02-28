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
 * Implements --automatic-fix for GetPostFixerPlugin
 *
 * This is a prototype, there are various features it does not implement.
 */

call_user_func(static function (): void {
	/**
	 * @param $code_base @unused-param
	 * @return ?FileEditSet a representation of the edit to make to replace a call to a function alias with a call to the original function
	 */
	$fix = static function (CodeBase $code_base, FileCacheEntry $contents, IssueInstance $instance): ?FileEditSet {
		$line = $instance->getLine();
		$new_name = (string) $instance->getTemplateParameters()[1];
		if ($new_name !== "GETPOSTINT") {
			return null;
		}

		$function_repr = (string) $instance->getTemplateParameters()[0];
		if (!preg_match('{\\\\(\w+)}', $function_repr, $match)) {
			return null;
		}
		$expected_name = $match[1];
		$edits = [];
		foreach ($contents->getNodesAtLine($line) as $node) {
			if (!$node instanceof ArgumentExpressionList) {
				continue;
			}
			$arguments = $node->children;
			if (!in_array(count($arguments), [3, 5])) {  // ',' included !
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

			$arg2 = $arguments[2];

			if ($arg2 instanceof ArgumentExpression && $arg2->expression instanceof StringLiteral) {
				// Get the string value of the StringLiteral
				$stringValue = $arg2->expression->getStringContentsText();
			} else {
				print "Expression is not string ".get_class($arg2)."/".get_class($arg2->expression)."- Skip $instance".PHP_EOL;
				continue;
			}
			print "Fixture elem on $line - $new_name - $function_repr - arg: $stringValue".PHP_EOL;

			// Get the first argument (delimiter)
			$delimiter = $arguments[1];
			// Get the second argument
			$secondArgument = $arguments[2];

			// Get the start position of the delimiter
			$arg_start_pos = $delimiter->getStartPosition();

			// Get the end position of the second argument
			$arg_end_pos = $secondArgument->getEndPosition();



			// @phan-suppress-next-line PhanThrowTypeAbsentForCall
			$start = $callableExpression->getStartPosition();
			// @phan-suppress-next-line PhanThrowTypeAbsentForCall
			$end = $callableExpression->getEndPosition();

			// Remove second argument
			$edits[] = new FileEdit($arg_start_pos, $arg_end_pos, "");

			// Replace call with GETPOSTINT
			$edits[] = new FileEdit($start, $end, (($file_contents[$start] ?? '') === '\\' ? '\\' : '') . $new_name);
		}
		if ($edits) {
			return new FileEditSet($edits);
		}
		return null;
	};
	IssueFixer::registerFixerClosure(
		'GetPostShouldBeGetPostInt',
		$fix
	);
});
