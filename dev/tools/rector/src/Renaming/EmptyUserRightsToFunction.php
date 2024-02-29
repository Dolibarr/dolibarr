<?php

namespace Dolibarr\Rector\Renaming;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Empty_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Rector\Core\NodeManipulator\BinaryOpManipulator;
use Rector\Core\Rector\AbstractRector;
use Rector\Php71\ValueObject\TwoNodeMatch;
use Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Rector\Strict\Rector\BooleanNot\BooleanInBooleanNotRuleFixerRector;

/**
 * Class with Rector custom rule to fix code
 */
class EmptyUserRightsToFunction extends AbstractRector
{
	/**
	 * @var \Rector\Core\NodeManipulator\BinaryOpManipulator
	 */
	private $binaryOpManipulator;

	/**
	 * Constructor
	 *
	 * @param BinaryOpManipulator $binaryOpManipulator The $binaryOpManipulator
	 */
	public function __construct(BinaryOpManipulator $binaryOpManipulator)
	{
		$this->binaryOpManipulator = $binaryOpManipulator;
	}

	/**
	 * getRuleDefinition
	 *
	 * @return RuleDefinition
	 * @throws PoorDocumentationException
	 */
	public function getRuleDefinition(): RuleDefinition
	{
		return new RuleDefinition(
			'Change empty(\$user->rights->module->permission) to !\$user->hasRight(\'module\', \'permission\')',
			[new CodeSample(
				'empty($user->rights->module->permission)',
				'!$user->hasRight(\'module\', \'permission\')'
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
		return [Node\Expr\BooleanNot::class, Node\Expr\Empty_::class];
	}

	/**
	 * refactor
	 *
	 * @param 	Node 					$node 		A node
	 * @return	FuncCall|BooleanNot
	 */
	public function refactor(Node $node)
	{
		if ($node instanceof Node\Expr\BooleanNot) {
			if (!$node->expr instanceof Node\Expr\Empty_) {
				return null;
			}
			// node is !empty(...) so we set newnode to ...
			$newnode = $node->expr->expr;

			$tmpperm = $newnode->var;		//name of tmpperm is modulex
			if (is_null($tmpperm)) {
				return null;
			}

			$tmprights = $tmpperm->var;		// name of tmprights is 'rights'
			if (is_null($tmprights)) {
				return null;
			}
			if (!$this->isName($tmprights, 'rights')) {
				$tmprights2 = $tmprights->var;	// name of tmprights is 'rights'
				if (is_null($tmprights2)) {
					return null;
				}
				if (!$this->isName($tmprights2, 'rights')) {
					return null;
				}
				$tmprights = $tmprights2;
			}

			$tmpuser = $tmprights->var;			// name of tmpuser is 'user'
			if (!$this->isName($tmpuser, 'user')) {
				return null;
			}

			$data = $this->getRights($newnode);
			if (!isset($data)) {
				return;
			}

			$args = [new Arg($data['module']), new Arg($data['perm1'])];
			if (!empty($data['perm2'])) {
				$args[] = new Arg($data['perm2']);
			}
			$method = $this->nodeFactory->createMethodCall($data['user'], 'hasRight', $args);

			return $method;
		}

		if ($node instanceof Node\Expr\Empty_) {
			// node is empty(...) so we set newnode to ...
			$newnode = $node->expr;			// name of node is perm

			$tmpperm = $newnode->var;		//name of tmpperm is modulex
			if (is_null($tmpperm)) {
				return null;
			}

			$tmprights = $tmpperm->var;		// name of tmprights is 'rights'
			if (is_null($tmprights)) {
				return null;
			}
			if (!$this->isName($tmprights, 'rights')) {
				$tmprights2 = $tmprights->var;	// name of tmprights is 'rights'
				if (is_null($tmprights2)) {
					return null;
				}
				if (!$this->isName($tmprights2, 'rights')) {
					return null;
				}
				$tmprights = $tmprights2;
			}

			$tmpuser = $tmprights->var;			// name of tmpuser is 'user'
			if (!$this->isName($tmpuser, 'user')) {
				return null;
			}

			$data = $this->getRights($newnode);
			if (!isset($data)) {
				return;
			}

			$args = [new Arg($data['module']), new Arg($data['perm1'])];
			if (!empty($data['perm2'])) {
				$args[] = new Arg($data['perm2']);
			}
			$method = $this->nodeFactory->createMethodCall($data['user'], 'hasRight', $args);

			return new Node\Expr\BooleanNot($method);
		}

		return null;
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
			// $conf->global == $value
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

	/**
	 * Check if node is a global access with format conf->global->XXX
	 *
	 * @param Node 	$node 	A node
	 * @return bool			Return true if node is conf->global->XXX
	 */
	private function isGlobalVar($node)
	{
		if (!$node instanceof PropertyFetch) {
			return false;
		}
		if (!$this->isName($node->var, 'global')) {
			return false;
		}
		$global = $node->var;
		if (!$global instanceof PropertyFetch) {
			return false;
		}
		if (!$this->isName($global->var, 'conf')) {
			return false;
		}
		return true;
	}

	/**
	 * @param 	Node 		$node 	Node to be parsed
	 * @return 	Node|void			Return the name of the constant
	 */
	private function getConstName($node)
	{
		if ($node instanceof PropertyFetch && $node->name instanceof Node\Expr) {
			return $node->name;
		}
		$name = $this->getName($node);
		if (empty($name)) {
			return;
		}
		return new String_($name);
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
