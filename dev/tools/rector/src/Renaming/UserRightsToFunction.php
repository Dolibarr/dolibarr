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
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Equal;


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
			Node\Expr\BinaryOp\BooleanAnd::class,
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
			// var is left of = and expr is right of =
			if (!isset($node->var)) {
				return;
			}

			if (!$node->expr instanceof Node\Expr\PropertyFetch) {
				return;
			}

			$data = $this->getRights($node->expr);
			if (!isset($data)) {
				return;
			}
			$args = [new Arg($data['module']), new Arg($data['perm1'])];
			if (!empty($data['perm2'])) {
				$args[] = new Arg($data['perm2']);
			}
			$node->expr = $this->nodeFactory->createMethodCall($data['user'], 'hasRight', $args);

			return $node;
		}

		if ($node instanceof Node\Expr\BinaryOp\BooleanAnd) {
			/*$nodes = $this->resolveTwoNodeMatch($node);
			 if (!isset($nodes)) {
			 return;
			 }

			 $node = $nodes->getFirstExpr();
			 */
			$mustprocesstheleft = false;
			$mustprocesstheright = false;

			if ($node->left instanceof Node\Expr\PropertyFetch) {
				$data = $this->getRights($node->left);
				if (isset($data)) {
					$mustprocesstheleft = true;
				}
			}
			if (empty($mustprocesstheleft) && $node->right instanceof Node\Expr\PropertyFetch) {
				$data = $this->getRights($node->right);
				if (isset($data)) {
					$mustprocesstheright = true;
				}
			}

			if (isset($data)) {
				$args = [new Arg($data['module']), new Arg($data['perm1'])];
				if (!empty($data['perm2'])) {
					$args[] = new Arg($data['perm2']);
				}

				if ($mustprocesstheleft && !empty($data['module'])) {
					$node->left = $this->nodeFactory->createMethodCall($data['user'], 'hasRight', $args);
				}
				if ($mustprocesstheright && !empty($data['module'])) {
					$node->right = $this->nodeFactory->createMethodCall($data['user'], 'hasRight', $args);
				}
			}

			return $node;
		}

		$caseok = false;	// Will be true if we can make the replacement. We must not do it for assignment like when $user->right->aaa->bbb = ...

		$isInverse = false;
		if ($node instanceof Node\Expr\BooleanNot) {
			if (!$node->expr instanceof Node\Expr\Empty_) {
				return null;
			}
			$node = $node->expr->expr;
			$caseok = true;
		}
		if ($node instanceof Node\Expr\Empty_) {
			$node = $node->expr;
			$isInverse = true;
			$caseok = true;
		}
		if ($node instanceof Node\Expr\Isset_) {
			// Take first arg for isset (No code found with multiple isset).
			$node = $node->vars[0];
			$caseok = true;
		}
		if (!$node instanceof Node\Expr\PropertyFetch) {
			return null;
		}

		if ($caseok) {
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
		} else {
			return null;
		}
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

	/**
	 * Get nodes with check empty
	 *
	 * @param BooleanAnd $booleanAnd A BooleandAnd
	 * @return    TwoNodeMatch|null
	 */
	private function resolveTwoNodeMatch(BooleanAnd $booleanAnd): ?TwoNodeMatch
	{
		return $this->binaryOpManipulator->matchFirstAndSecondConditionNode(
			$booleanAnd,
			// Function to check if we are in the case $conf->global->... == $value
			function (Node $node): bool {
				if (!$node instanceof Equal) {
					return \false;
				}
				return $this->isGlobalVar($node->left);
			},
			// !empty(...) || isset(...)
			function (Node $node): bool {
				if ($node instanceof BooleanNot && $node->expr instanceof Empty_) {
					return $this->isGlobalVar($node->expr->expr);
				}
				if (!$node instanceof Isset_) {
					return $this->isGlobalVar($node);
				}
				return \true;
			}
			);
	}
}
