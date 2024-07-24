<?php

namespace Dolibarr\Rector\Renaming;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use Rector\Core\PhpParser\Node\NodeFactory;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Class to refactor User rights
 */
class UserRightsToFunction extends AbstractRector
{
	/**
	 * @param \Rector\Core\PhpParser\Node\NodeFactory $nodeFactory node factory
	 */
	public function __construct(NodeFactory $nodeFactory)
	{
		$this->nodeFactory = $nodeFactory;
	}

	/**
	 * @throws \Symplify\RuleDocGenerator\Exception\PoorDocumentationException
	 * @return RuleDefinition
	 */
	public function getRuleDefinition(): RuleDefinition
	{
		return new RuleDefinition(
			'Change \$user->rights->module->permission to \$user->hasRight(\'module\', \'permission\')',
			[new CodeSample(
				'$user->rights->module->permission',
				'$user->hasRight(\'module\', \'permission\')'
			)]
		);
	}

	/**
	 * Return a node type from https://github.com/rectorphp/php-parser-nodes-docs/
	 *
	 * @return string[]
	 */
	public function getNodeTypes(): array
	{
		return [
			Node\Expr\Assign::class,
			Node\Expr\PropertyFetch::class,
			Node\Expr\BooleanNot::class,
			Node\Expr\Empty_::class,
			Node\Expr\Isset_::class,
			Node\Stmt\ClassMethod::class
		];
	}

	/**
	 * @param \PhpParser\Node $node node to be changed
	 * @return \PhpParser\Node|\PhpParser\Node[]|\PhpParser\Node\Expr\MethodCall|void|null| int
	 */
	public function refactor(Node $node)
	{
		if ($node instanceof Node\Stmt\ClassMethod) {
			$excludeMethods = ['getrights', 'hasRight'];
			/** @var \PHPStan\Analyser\MutatingScope $scope */
			$scope = $node->getAttribute('scope');
			$class = $scope->getClassReflection();
			$classes = ['UserGroup', 'User'];
			if (isset($class) && in_array($class->getName(), $classes)) {
				if (in_array($this->getName($node), $excludeMethods)) {
					return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
				}
			}
		}
		if ($node instanceof Node\Expr\Assign) {
			return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
		}
		$isInverse = false;
		if ($node instanceof Node\Expr\BooleanNot) {
			if (!$node->expr instanceof Node\Expr\Empty_) {
				return null;
			}
			$node = $node->expr->expr;
		}
		if ($node instanceof Node\Expr\Empty_) {
			$node = $node->expr;
			$isInverse = true;
		}
		if ($node instanceof Node\Expr\Isset_) {
			// Take first arg for isset (No code found with multiple isset).
			$node = $node->vars[0];
		}
		if (!$node instanceof Node\Expr\PropertyFetch) {
			return;
		}
		$data = $this->getRights($node);
		if (!isset($data)) {
			return;
		}
		$args = [new Arg($data['module']), new Arg($data['perm1'])];
		if (!empty($data['perm2'])) {
			$args[] = new Arg($data['perm2']);
		}
		$method = $this->nodeFactory->createMethodCall($data['user'], 'hasRight', $args);
		if ($isInverse) {
			return new Node\Expr\BooleanNot($method);
		}
		return $method;
	}

	/**
	 * @param \PhpParser\Node\Expr\PropertyFetch $node node
	 * @return array|null
	 */
	private function getRights(Node\Expr\PropertyFetch $node)
	{
		$perm2 = '';
		if (!$node->var instanceof Node\Expr\PropertyFetch) {
			return null;
		}
		// Add a test to avoid rector error on html.formsetup.class.php
		if (!$node->name instanceof Node\Expr\Variable && is_null($this->getName($node))) {
			//var_dump($node);
			return null;
			//exit;
		}
		$perm1 = $node->name instanceof Node\Expr\Variable ? $node->name : new String_($this->getName($node));
		$moduleNode = $node->var;
		if (!$moduleNode instanceof Node\Expr\PropertyFetch) {
			return null;
		}
		if (!$moduleNode->var instanceof Node\Expr\PropertyFetch) {
			return null;
		}
		if (!$this->isName($moduleNode->var, 'rights')) {
			$perm2 = $perm1;
			$perm1 = $moduleNode->name instanceof Node\Expr\Variable ? $moduleNode->name : new String_($this->getName($moduleNode));
			$moduleNode = $moduleNode->var;
		}
		$module = $moduleNode->name instanceof Node\Expr\Variable ? $moduleNode->name : new String_($this->getName($moduleNode));
		$rights = $moduleNode->var;
		if (!$this->isName($rights, 'rights') || !isset($perm1) || !isset($module)) {
			return null;
		}
		if (!$rights->var instanceof Node\Expr\Variable) {
			return null;
		}
		$user = $rights->var;
		return compact('user', 'module', 'perm1', 'perm2');
	}
}
