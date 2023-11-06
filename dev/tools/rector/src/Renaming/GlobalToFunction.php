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
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;

/**
 * Class with Rector custom rule to fix code
 */
class GlobalToFunction extends AbstractRector
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
			'Change $conf->global to getDolGlobal',
			[new CodeSample('$conf->global->CONSTANT',
				'getDolGlobalInt(\'CONSTANT\')'
			)]);
	}

	/**
	 * Return a node type from https://github.com/rectorphp/php-parser-nodes-docs/
	 *
	 * @return string[]
	 */
	public function getNodeTypes(): array
	{
		return [Equal::class, Greater::class, GreaterOrEqual::class, Smaller::class, SmallerOrEqual::class, BooleanAnd::class, Concat::class, ArrayDimFetch::class];
	}

	/**
	 * refactor
	 *
	 * @param Node 	$node 		A node
	 * @return    				Equal|Concat|ArrayDimFetch|void
	 */
	public function refactor(Node $node)
	{
		if ($node instanceof Node\Expr\ArrayDimFetch) {
			if (!isset($node->dim)) {
				return;
			}
			if ($this->isGlobalVar($node->dim)) {
				$constName = $this->getConstName($node->dim);
				if (empty($constName)) {
					return;
				}
				$node->dim = new FuncCall(
					new Name('getDolGlobalString'),
					[new Arg($constName)]
				);
			}
			return $node;
		}
		if ($node instanceof Concat) {
			if ($this->isGlobalVar($node->left)) {
				$constName = $this->getConstName($node->left);
				if (empty($constName)) {
					return;
				}
				$leftConcat = new FuncCall(
					new Name('getDolGlobalString'),
					[new Arg($constName)]
				);
				$rightConcat = $node->right;
			}
			if ($this->isGlobalVar($node->right)) {
				$constName = $this->getConstName($node->right);
				if (empty($constName)) {
					return;
				}
				$rightConcat = new FuncCall(
					new Name('getDolGlobalString'),
					[new Arg($constName)]
				);
				$leftConcat = $node->left;
			}
			if (!isset($leftConcat, $rightConcat)) {
				return;
			}
			return new Concat($leftConcat, $rightConcat);
		}
		if ($node instanceof BooleanAnd) {
			$nodes = $this->resolveTwoNodeMatch($node);
			if (!isset($nodes)) {
				return;
			}

			/** @var Equal $node */
			$node = $nodes->getFirstExpr();
		}

		$typeofcomparison = '';
		if ($node instanceof Equal) {
			$typeofcomparison = 'Equal';
		}
		if ($node instanceof Greater) {
			$typeofcomparison = 'Greater';
		}
		if ($node instanceof GreaterOrEqual) {
			$typeofcomparison = 'GreaterOrEqual';
		}
		if ($node instanceof Smaller) {
			$typeofcomparison = 'Smaller';
		}
		if ($node instanceof SmallerOrEqual) {
			$typeofcomparison = 'SmallerOrEqual';
		}
		if (empty($typeofcomparison)) {
			return;
		}

		if (!$this->isGlobalVar($node->left)) {
			return;
		}

		// Test the type after the comparison conf->global->xxx to know the name of function
		switch ($node->right->getType()) {
			case 'Scalar_LNumber':
				$funcName = 'getDolGlobalInt';
				break;
			case 'Scalar_String':
				$funcName = 'getDolGlobalString';
				break;
			default:
				return;
		}

		$constName = $this->getConstName($node->left);
		if (empty($constName)) {
			return;
		}

		if ($typeofcomparison == 'Equal') {
			return new Equal(
				new FuncCall(
					new Name($funcName),
					[new Arg($constName)]
				),
				$node->right
			);
		}
		if ($typeofcomparison == 'Greater') {
			return new Greater(
				new FuncCall(
					new Name($funcName),
					[new Arg($constName)]
					),
				$node->right
				);
		}
		if ($typeofcomparison == 'GreaterOrEqual') {
			return new GreaterOrEqual(
				new FuncCall(
					new Name($funcName),
					[new Arg($constName)]
					),
				$node->right
				);
		}
		if ($typeofcomparison == 'Smaller') {
			return new Smaller(
				new FuncCall(
					new Name($funcName),
					[new Arg($constName)]
					),
				$node->right
				);
		}
		if ($typeofcomparison == 'SmallerOrEqual') {
			return new SmallerOrEqual(
				new FuncCall(
					new Name($funcName),
					[new Arg($constName)]
					),
				$node->right
				);
		}
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
	 * @return bool			Return true if noe is conf->global->XXX
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
