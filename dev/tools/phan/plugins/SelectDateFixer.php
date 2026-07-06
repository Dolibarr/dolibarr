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

		// Argument {INDEX} (${PARAMETER}) is {CODE} of type {TYPE}{DETAILS} but
		// {FUNCTIONLIKE} takes {TYPE}{DETAILS} defined at {FILE}:{LINE} (the inferred real argument type has nothing in common with the parameter's phpdoc type)

		//htdocs\supplier_proposal\card.php:1705 PhanTypeMismatchArgumentProbablyReal Argument 3 ($h) is '' of type '' but \Form::selectDate() takes int (no real type) defined at htdocs\core\class\html.form.class.php:6799 (the inferred real argument type has nothing in common with the parameter's phpdoc type)
		//htdocs\supplier_proposal\card.php:1705 PhanTypeMismatchArgumentProbablyReal Argument 4 ($m) is '' of type '' but \Form::selectDate() takes int (no real type) defined at htdocs\core\class\html.form.class.php:6799 (the inferred real argument type has nothing in common with the parameter's phpdoc type)

		$argument_index = (string) $instance->getTemplateParameters()[0];
		$argument_name = (string) $instance->getTemplateParameters()[1];
		$argument_code = (string) $instance->getTemplateParameters()[2];
		$argument_type = (string) $instance->getTemplateParameters()[3];
		$details = (string) $instance->getTemplateParameters()[4];
		$functionlike = (string) $instance->getTemplateParameters()[5];

		$expected_functionlike = "\\Form::selectDate()";
		$expected_name = "selectDate";
		if ($functionlike !== $expected_functionlike) {
			print "$functionlike != '$expected_functionlike'".PHP_EOL;
			return null;
		}

		// Check if we fix any of this
		if (
			($argument_name === 'h' && $argument_code === "''")
			|| ($argument_name === 'm' && $argument_code === "''")
			|| ($argument_name === 'empty' && $argument_code === "''")
		) {
			$replacement = '0';
			$argIdx = ($argument_index - 1) * 2;
			$expectedStringValue = "";
		} else {
			print "ARG$argument_index:$argument_name CODE:$argument_code".PHP_EOL;
			return null;
		}

		// At this point we established that the notification
		// matches some we fix.

		$line = $instance->getLine();

		$edits = [];
		foreach ($contents->getNodesAtLine($line) as $node) {
			if (!$node instanceof ArgumentExpressionList) {
				continue;
			}
			$arguments = $node->children;
			if (count($arguments) <= $argIdx) {
				// print "Arg Count is ".count($arguments)." - Skip $instance".PHP_EOL;
				continue;
			}

			$is_actual_call = $node->parent instanceof CallExpression;
			if (!$is_actual_call) {
				// print "Not actual call - Skip $instance".PHP_EOL;
				continue;
			}

			print "Actual call - $instance".PHP_EOL;
			$callable = $node->parent;

			$callableExpression = $callable->callableExpression;

			if ($callableExpression instanceof Microsoft\PhpParser\Node\QualifiedName) {
				$actual_name = $callableExpression->getResolvedName();
			} elseif ($callableExpression instanceof Microsoft\PhpParser\Node\Expression\MemberAccessExpression) {
				$memberNameToken = $callableExpression->memberName;
				$actual_name = (new NodeUtils($contents->getContents()))->tokenToString($memberNameToken);
			} else {
				print "Callable expression is ".get_class($callableExpression)."- Skip $instance".PHP_EOL;
				continue;
			}

			if ((string) $actual_name !== (string) $expected_name) {
				// print "Name unexpected '$actual_name'!='$expected_name' - Skip $instance".PHP_EOL;
				continue;
			}

			foreach ($arguments as $i => $argument) {
				if ($argument instanceof ArgumentExpression) {
					print "Type$i: ".get_class($argument->expression).PHP_EOL;
				}
			}

			$stringValue = null;


			$arg = $arguments[$argIdx];

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

			if ($stringValue !== $expectedStringValue) {
				print "Not replacing $argument_name which is '$stringValue'/".get_class($arg)."/".get_class($arg->expression)."- Skip $instance".PHP_EOL;
				continue;
			}

			print "Fixture elem on $line - $actual_name(...'$stringValue'...) - $instance".PHP_EOL;



			// Get the first argument (delimiter)
			$argument_to_replace = $arg;

			$arg_start_pos = $argument_to_replace->getStartPosition();
			$arg_end_pos = $argument_to_replace->getEndPosition();

			// Set edit instruction
			$edits[] = new FileEdit($arg_start_pos, $arg_end_pos, $replacement);
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
