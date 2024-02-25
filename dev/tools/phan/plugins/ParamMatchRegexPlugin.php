<?php
/**
 * Copyright (C) 2024        MDW                            <mdeweerd@users.noreply.github.com>
 *
 * Phan Plugin to validate that arguments match a regex
 */
declare(strict_types=1);


use ast\Node;
use Phan\Codebase;
use Phan\Config;
use Phan\Language\Context;
use Phan\AST\ContextNode;
use Phan\AST\UnionTypeVisitor;
use Phan\Exception\CodeBaseException;
//use Phan\Language\Element\FunctionInterface;
use Phan\Language\UnionType;
use Phan\PluginV3;
use Phan\PluginV3\PluginAwarePostAnalysisVisitor;
use Phan\PluginV3\PostAnalyzeNodeCapability;

/**
 * ParamMatchPlugin hooks into one event:
 *
 * - getPostAnalyzeNodeVisitorClassName
 *   This method returns a visitor that is called on every AST node from every
 *   file being analyzed
 *
 * A plugin file must
 *
 * - Contain a class that inherits from \Phan\PluginV3
 *
 * - End by returning an instance of that class.
 *
 * It is assumed without being checked that plugins aren't
 * mangling state within the passed code base or context.
 *
 * Note: When adding new plugins,
 * add them to the corresponding section of README.md
 */
class ParamMatchPlugin extends PluginV3 implements PostAnalyzeNodeCapability
{
	/**
	 * @return string - name of PluginAwarePostAnalysisVisitor subclass
	 */
	public static function getPostAnalyzeNodeVisitorClassName(): string
	{
		return ParamMatchVisitor::class;
	}
}

/**
 * When __invoke on this class is called with a node, a method
 * will be dispatched based on the `kind` of the given node.
 *
 * Visitors such as this are useful for defining lots of different
 * checks on a node based on its kind.
 */
class ParamMatchVisitor extends PluginAwarePostAnalysisVisitor
{
	// A plugin's visitors should not override visit() unless they need to.

	/**
	 * @override
	 *
	 * @param Node $node A node to analyze
	 *
	 * @return void
	 */
	public function visitCall(Node $node): void
	{
		$name = $node->children['expr']->children['name'] ?? null;
		if (!is_string($name)) {
			return;
		}

		$rules = Config::getValue('ParamMatchRegexPlugin');

		foreach ($rules as $regex => $rule) {
			if (preg_match($regex, $name)) {
				$this->checkParam($node, $rule[0], $rule[1]);
			}
		}
	}

	/**
	 * @param Node   $node        Visited node for which to verify arguments match regex
	 * @param int    $argPosition Position of argument to check
	 * @param string $argRegex    Regex to validate against argument
	 *
	 * @return void
	 */
	public function checkParam(Node $node, int $argPosition, string $argRegex): void
	{
		$functionName = $node->children['expr']->children['name'] ?? null;
		$args = $node->children['args']->children;

		if (!array_key_exists($argPosition, $args)) {
			/*
			$this->emitPluginIssue(
				$this->code_base,
				$this->context,
				'ParamMatchMissingArgument',
				"Argument at %s for %s is missing",
				[$argPosition, $function_name]
			);
			*/
			return;
		}
		$expr = $args[$argPosition];
		try {
			$expr_type = UnionTypeVisitor::unionTypeFromNode($this->code_base, $this->context, $expr, false);
		} catch (Exception $_) {
			return;
		}

		$expr_value = $expr_type->getRealUnionType()->asValueOrNullOrSelf();
		if (!is_object($expr_value)) {
			$list = [(string) $expr_value];
		} elseif ($expr_value instanceof UnionType) {
			$list = $expr_value->asStringScalarValues();
		} else {
			// Note: maybe more types could be supported
			return;
		}

		foreach ($list as $argValue) {
			if (!preg_match($argRegex, $argValue)) {
				// Emit an issue if the argument does not match the expected regex pattern
				$this->emitPluginIssue(
					$this->code_base,
					$this->context,
					'ParamMatchRegexError',
					"Argument {POS} function {FUNCTION} can have value '{VALUE}' that does not match the expected pattern '{PATTERN}'",
					[$argPosition, $functionName, $argValue, $argRegex]
				);
			}
		}
	}
}

// Every plugin needs to return an instance of itself at the
// end of the file in which it's defined.
return new ParamMatchPlugin();
