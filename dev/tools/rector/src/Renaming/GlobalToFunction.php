<?php

namespace Dolibarr\Rector\Renaming;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
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

/**
 * Class with Rector custom rule to fix code
 */
class GlobalToFunction extends AbstractRector
{
	/**
	 * @var \Rector\Core\NodeManipulator\BinaryOpManipulator
	 */
	private BinaryOpManipulator $binaryOpManipulator;

	/**
	 * Constructor
	 *
	 * @param BinaryOpManipulator $binaryOpManipulator 	The $binaryOpManipulator
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
	 * getNodeTypes
	 *
	 * @return array
	 */
	public function getNodeTypes(): array
	{
		return [Equal::class, BooleanAnd::class];
	}

	/**
	 * refactor
	 *
	 * @param 	Node 		$node	A node
	 * @return 	Equal|void
	 */
	public function refactor(Node $node)
	{
		if ($node instanceof BooleanAnd) {
			$nodes = $this->resolveTwoNodeMatch($node);
			if (!isset($nodes)) {
				return;
			}

			/** @var Equal $node */
			$node = $nodes->getFirstExpr();
		}
		if (!$node instanceof Equal) {
			return;
		};

		if (!$this->isGlobalVar($node->left)) {
			return;
		}

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
		$constName = $this->getName($node->left);
		if (empty($constName)) {
			return;
		}
		return new Equal(
			new FuncCall(
				new Name($funcName),
				[new Arg(new String_($constName))]
			),
			$node->right
		);
	}

	/**
	 * Get nodes with check empty
	 *
	 * @param	BooleanAnd 	$booleanAnd		A BooleandAnd
	 * @return 	TwoNodeMatch|null
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
	 * Check node is global access
	 *
	 * @param $node		A node
	 * @return bool
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
}
