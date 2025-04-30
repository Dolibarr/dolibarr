<?php
/**
 * Copyright (C) 2024        MDW                            <mdeweerd@users.noreply.github.com>
 *
 * Phan Plugin to validate that arguments match a regex
 *
 *
 *  "ParamMatchRegexPlugin" => [
 *      "/^test1$/" => [ 0, "/^OK$/"],  // Argument 0 must be 'OK'
 *      "/^test2$/" => [ 1, "/^NOK$/", "Test2Arg1NokError"], // Argument 1 must be 'NOK', error code
 *      "/^\\MyTest::mymethod$/" => [ 0, "/^NOK$/"], // Argument 0 must be 'NOK'
 *  ],
 *  'plugins' => [
 *      ".phan/plugins/ParamMatchRegexPlugin.php",
 *      // [...]
 *  ],
 */
declare(strict_types=1);


use ast\Node;
use Phan\Config;
use Phan\AST\UnionTypeVisitor;
//use Phan\Language\Element\FunctionInterface;
use Phan\Language\UnionType;
use Phan\Language\Type;
use Phan\PluginV3;
use Phan\PluginV3\PluginAwarePostAnalysisVisitor;
use Phan\PluginV3\PostAnalyzeNodeCapability;
use Phan\Exception\NodeException;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use Phan\Exception\FQSENException;

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
	 * @param    Node $node Node to analyze
	 *
	 * @return void
	 */
	public function visitMethodCall(Node $node): void
	{
		$method_name = $node->children['method'] ?? null;
		if (!\is_string($method_name)) {
			return; // Not handled, TODO: handle variable(?) methods
			// throw new NodeException($node);
		}
		try {
			// Fetch the list of valid classes, and warn about any undefined classes.
			$union_type = UnionTypeVisitor::unionTypeFromNode($this->code_base, $this->context, $node->children['expr']);
		} catch (Exception $_) {
			// Phan should already throw for this
			return;
		}

		$class_list = [];
		foreach ($union_type->getTypeSet() as $type) {
			$class_fqsen = "NoFSQENType";
			if ($type instanceof Type) {
				try {
					$class_fqsen = (string) FullyQualifiedClassName::fromFullyQualifiedString($type->getName());
				} catch (FQSENException $_) {
					// var_dump([$_, $node]);
					continue;
				}
			} else {
				//    var_dump( $type) ;
				continue;
			}
			$class_name = (string) $class_fqsen;
			$class_list[] = $class_name;
		}

		/* May need to check list of classes
		*/

		/*
		if (!$class->hasMethodWithName($this->code_base, $method_name, true)) {
			throw new NodeException($expr, 'does not have method');
		}
		$class_name = $class->getName();
		*/
		foreach ($class_list as $class_name) {
			$this->checkRule($node, "$class_name::$method_name");
		}
	}

	/**
	 * @override
	 * @param    Node $node Node to analyze
	 *
	 * @return void
	 */
	public function visitStaticCall(Node $node): void
	{
		$class_name = $node->children['class']->children['name'] ?? null;
		if (!\is_string($class_name)) {
			// May happen for $this->className::$name(...$arguments); (variable class name)
			$location = $this->context->getFile().":".$node->lineno;
			print "$location: Node does not have fixed string class_name - node type ".(is_object($class_name) ? get_class_name($class_name) : gettype($class_name)).PHP_EOL;
			return;
			// throw new NodeException($node, 'does not have class');
		}
		// } else {
		//	$location = $this->context->getFile().":".$node->lineno;
		//	print "$location: Static call - node type ".get_class($node).PHP_EOL;
		//}
		try {
			$class_name = (string) FullyQualifiedClassName::fromFullyQualifiedString($class_name);
		} catch (FQSENException $_) {
		}
		$method_name = $node->children['method'] ?? null;

		if (!\is_string($method_name)) {
			return;
		}
		$this->checkRule($node, "$class_name::$method_name");
	}
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
		if (!\is_string($name)) {
			return;
		}


		$this->checkRule($node, $name);
	}

	/**
	 *
	 * @param Node   $node A node to analyze
	 * @param string $name function name or fqsn of class::<method>
	 *
	 * @return void
	 */
	public function checkRule(Node $node, string $name)
	{
		$rules = Config::getValue('ParamMatchRegexPlugin');
		foreach ($rules as $regex => $rule) {
			if (preg_match($regex, $name)) {
				$this->checkParam($node, $rule[0], $rule[1], $name, $rule[2] ?? null);
			}
		}
	}

	/**
	 * Check that argument matches regex at node
	 *
	 * @param Node   $node         Visited node for which to verify arguments match regex
	 * @param int    $argPosition  Position of argument to check
	 * @param string $argRegex     Regex to validate against argument
	 * @param string $functionName Function name for report
	 * @param string $messageCode  Message code to provide in message
	 *
	 * @return void
	 */
	public function checkParam(Node $node, int $argPosition, string $argRegex, $functionName, $messageCode = null): void
	{
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
			$list = $expr_value->asScalarValues();
		} else {
			// Note: maybe more types could be supported
			return;
		}

		foreach ($list as $argValue) {
			if (!\preg_match($argRegex, (string) $argValue)) {
				// Emit an issue if the argument does not match the expected regex pattern
				// var_dump([$node,$expr_value,$expr_type->getRealUnionType()]); // Information about node
				$this->emitPluginIssue(
					$this->code_base,
					$this->context,
					$messageCode ?? 'ParamMatchRegexError',
					"Argument {INDEX} function {FUNCTION} can't have the value {STRING_LITERAL} that does not match the expected pattern '{STRING_LITERAL}'",
					[$argPosition, $functionName, json_encode($argValue), $argRegex]
				);
			}
		}
	}
}

// Every plugin needs to return an instance of itself at the
// end of the file in which it's defined.
return new ParamMatchPlugin();
