<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */

declare(strict_types=1);

use ast\Node;
use Phan\PluginV3;
use Phan\PluginV3\PluginAwarePostAnalysisVisitor;
use Phan\PluginV3\PostAnalyzeNodeCapability;

/**
 * NoVarDumpPlugin hooks into one event:
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
class NoVarDumpPlugin extends PluginV3 implements PostAnalyzeNodeCapability
{
	/**
	 * @return string - name of PluginAwarePostAnalysisVisitor subclass
	 */
	public static function getPostAnalyzeNodeVisitorClassName(): string
	{
		return NoVarDumpVisitor::class;
	}
}

/**
 * When __invoke on this class is called with a node, a method
 * will be dispatched based on the `kind` of the given node.
 *
 * Visitors such as this are useful for defining lots of different
 * checks on a node based on its kind.
 */
class NoVarDumpVisitor extends PluginAwarePostAnalysisVisitor
{
	// A plugin's visitors should not override visit() unless they need to.

	/**
	 * @param Node $node A node to analyze
	 *
	 * @return void
	 *
	 * @override
	 */
	public function visitCall(Node $node): void
	{
		$name = $node->children['expr']->children['name'] ?? null;
		if (!is_string($name)) {
			return;
		}
		if (strcasecmp($name, 'var_dump') !== 0) {
			return;
		}
		$this->emitPluginIssue(
			$this->code_base,
			$this->context,
			'NoVarDumpPlugin',
			'var_dump() should be commented in submitted code',
			[]
		);
	}
}

// Every plugin needs to return an instance of itself at the
// end of the file in which it's defined.
return new NoVarDumpPlugin();
