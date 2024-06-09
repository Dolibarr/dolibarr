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
class EmptyGlobalToFunction extends AbstractRector
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
			'Change empty($conf->global->...) to getDolGlobal',
			[new CodeSample(
				'empty($conf->global->CONSTANT)',
				'!getDolGlobalInt(\'CONSTANT\')'
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
			$newnode = $node->expr->expr;		// newnode is conf->global->...

			$tmpglobal = $newnode->var;			// tmpglobal is global->...
			if (is_null($tmpglobal)) {
				return null;
			}
			if (!$this->isName($tmpglobal, 'global')) {
				return null;
			}

			$tmpconf = $tmpglobal->var;			// tmpconf is conf->
			if (!$this->isName($tmpconf, 'conf')) {
				return null;
			}

			$nameforconst = $this->getName($newnode);
			if (is_null($nameforconst)) {
				return null;
			}
			$constName = new String_($nameforconst);

			// We found a node !empty(conf->global->XXX)
			return new FuncCall(
				new Name('getDolGlobalString'),
				[new Arg($constName)]
			);
		}


		if ($node instanceof Node\Expr\Empty_) {
			// node is empty(...) so we set newnode to ...
			$newnode = $node->expr;			// newnode is conf->global->...

			$tmpglobal = $newnode->var;		// tmpglobal is global->...
			if (is_null($tmpglobal)) {
				return null;
			}
			if (!$this->isName($tmpglobal, 'global')) {
				return null;
			}

			$tmpconf = $tmpglobal->var;		// tmpconf is conf->
			if (!$this->isName($tmpconf, 'conf')) {
				return null;
			}

			$nameforconst = $this->getName($newnode);
			if (is_null($nameforconst)) {
				return null;
			}
			$constName = new String_($nameforconst);

			return new BooleanNot(new FuncCall(
				new Name('getDolGlobalString'),
				[new Arg($constName)]
			));
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
}
