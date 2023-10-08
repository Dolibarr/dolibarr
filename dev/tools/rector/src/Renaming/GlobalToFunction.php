<?php

namespace Dolibarr\Rector\Renaming;

use PhpParser\Node;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class GlobalToFunction extends AbstractRector
{

	/**
	 * @throws PoorDocumentationException
	 */
	public function getRuleDefinition(): RuleDefinition
	{
		return new RuleDefinition(
			'Change $conf->global to getDolGlobal',
			[new CodeSample('$conf->global->CONSTANT'
				, 'getDolGlobalInt(\'CONSTANT\')'
			)]);
	}

	public function getNodeTypes(): array
	{
		return [Node\Expr\BinaryOp\Equal::class];
	}

	/**
	 * @param \PhpParser\Node $node
	 * @return \PhpParser\Node\Expr\BinaryOp\Equal|void
	 */
	public function refactor(Node $node)
	{
		if (!$node instanceof Node\Expr\BinaryOp\Equal) {
			return;
		};
		if (!$node->left instanceof Node\Expr\PropertyFetch) {
			return;
		}
		if (!$this->isName($node->left->var, 'global')) {
			return;
		}
		$global = $node->left->var;
		if (!$global instanceof Node\Expr\PropertyFetch) {
			return;
		}
		if (!$this->isName($global->var, 'conf')) {
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
		return new Node\Expr\BinaryOp\Equal(
			new Node\Expr\FuncCall(
				new Node\Name($funcName),
				[new Node\Arg(new Node\Scalar\String_($constName))]
			),
			$node->right
		);
	}
}
