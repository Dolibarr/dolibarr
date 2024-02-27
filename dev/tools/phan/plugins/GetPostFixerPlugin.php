<?php

declare(strict_types=1);
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */

use ast\Node;
use Phan\CodeBase;
use Phan\Language\Context;
use Phan\AST\UnionTypeVisitor;
//use Phan\Language\Element\FunctionInterface;
use Phan\Language\UnionType;
use Phan\Language\Type;
use Phan\PluginV3;
use Phan\PluginV3\AnalyzeFunctionCallCapability;
use Phan\Language\Element\FunctionInterface;
use Phan\Config;

/*
 *    'GetPostFixerPlugin' => [ '\\Foo::bar', '\\Baz::bing' ],
 *    'plugins' => [
 *    	__DIR__.'plugins/GetPostFixerPlugin.php',
 *    	[...]
 *    ]
 */

/**
 * Prints out call sites of given functions or methods.
 */
final class GetPostFixerPlugin extends PluginV3 implements AnalyzeFunctionCallCapability
{
	/**
	 * @param CodeBase $code_base Code base
	 *
	 * @return array
	 */
	public function getAnalyzeFunctionCallClosures(CodeBase $code_base): array
	{
		static $function_call_closures;

		if ($function_call_closures === null) {
			$function_call_closures = [];
			$self = $this;
			$func = 'GETPOST';
			$function_call_closures[$func]
				= static function (CodeBase $code_base, Context $context, FunctionInterface $function, array $args, ?Node $node = null) use ($self, $func) {
					self::handleCall($code_base, $context, $node, $function, $args, $func, $self);
				};
		}
		return $function_call_closures;
	}

	/**
	 * @param CodeBase $code_base  Code base
	 * @param Context $context Context
	 * @param ?Node $node Node
	 * @param FunctionInterface $function  Visited function information
	 * @param array $args Arguments to the function
	 * @param string $func_to_analyze Name of the function to analyze (as we defined it)
	 * @param GetPostFixerPlugin $self This visitor
	 *
	 * @return void
	 */
	private static function handleCall(CodeBase $code_base, Context $context, ?Node $node, FunctionInterface $function, array $args, string $func_to_analyze, $self): void
	{
		$expr = $args[1] ?? null;
		if ($expr === null) {
			return;
		}
		try {
			$expr_type = UnionTypeVisitor::unionTypeFromNode($code_base, $context, $expr, false);
		} catch (Exception $_) {
			return;
		}

		$expr_value = $expr_type->getRealUnionType()->asValueOrNullOrSelf();
		if (!is_string($expr_value)) {
			return;
		}
		if ($expr_value !== 'int') {
			return;
		}

		$self->emitIssue(
			$code_base,
			$context,
			'GetPostShouldBeGetPostInt',
			'Convert {FUNCTION} to {FUNCTION}',
			[(string) $function->getFQSEN(), "GETPOSTINT"]
		);
	}
}

if (Config::isIssueFixingPluginEnabled()) {
	require_once __DIR__ . '/GetPostFixerPlugin/fixers.php';
}

return new GetPostFixerPlugin();
