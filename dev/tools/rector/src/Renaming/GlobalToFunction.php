<?php

namespace Dolibarr\Rector\Renaming;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Empty_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Rector\Core\NodeManipulator\BinaryOpManipulator;
use Rector\Core\Rector\AbstractRector;
use Rector\Php71\ValueObject\TwoNodeMatch;
use Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;

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
			'Change $conf->global to getDolGlobal in context (1) conf->global Operator Value or (2) function(conf->global...)',
			[new CodeSample(
				'$conf->global->CONSTANT',
				'getDolGlobalInt(\'CONSTANT\')'
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
		return [Assign::class, FuncCall::class, MethodCall::class, Equal::class, NotEqual::class, Greater::class, GreaterOrEqual::class, Smaller::class, SmallerOrEqual::class, NotIdentical::class, BooleanAnd::class, Concat::class, ArrayItem::class, ArrayDimFetch::class];
	}

	/**
	 * refactor
	 *
	 * @param Node 	$node 		A node
	 * @return    				FuncCall|Equal|Concat|ArrayDimFetch|void
	 * 							return $node unchanged or void to do nothing
	 */
	public function refactor(Node $node)
	{
		if ($node instanceof Node\Expr\Assign) {
			// var is left of = and expr is right
			if (!isset($node->var)) {
				return;
			}
			if ($this->isGlobalVar($node->expr)) {
				$constName = $this->getConstName($node->expr);
				if (empty($constName)) {
					return;
				}
				$node->expr = new FuncCall(
					new Name('getDolGlobalString'),
					[new Arg($constName)]
					);
			}
			return $node;
		}
		if ($node instanceof Node\Expr\ArrayItem) {
			if (!isset($node->key)) {
				return;
			}
			if ($this->isGlobalVar($node->value)) {
				$constName = $this->getConstName($node->value);
				if (empty($constName)) {
					return;
				}
				$node->value = new FuncCall(
					new Name('getDolGlobalString'),
					[new Arg($constName)]
					);
			}
			return $node;
		}

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

		if ($node instanceof FuncCall) {
			$tmpfunctionname = $this->getName($node);
			// If function is ok. We must avoid a lot of cases like isset(), empty()
			if (in_array($tmpfunctionname, array('dol_escape_htmltag', 'dol_hash', 'explode', 'is_numeric', 'length_accountg', 'length_accounta', 'make_substitutions', 'min', 'max', 'trunc', 'urlencode', 'yn'))) {
				//print "tmpfunctionname=".$tmpfunctionname."\n";
				$args = $node->getArgs();
				$nbofparam = count($args);

				if ($nbofparam >= 1) {
					$tmpargs = $args;
					foreach ($args as $key => $arg) {	// only 1 element in this array
						//var_dump($key);
						//var_dump($arg->value);exit;
						if ($this->isGlobalVar($arg->value)) {
							$constName = $this->getConstName($arg->value);
							if (empty($constName)) {
								return;
							}
							$a = new FuncCall(new Name('getDolGlobalString'), [new Arg($constName)]);
							$tmpargs[$key] = new Arg($a);

							$r = new FuncCall(new Name($tmpfunctionname), $tmpargs);
							return $r;
						}
					}
				}
			}
			return $node;
		}

		if ($node instanceof MethodCall) {
			$tmpmethodname = $this->getName($node->name);
			// If function is ok. We must avoid a lot of cases
			if (in_array($tmpmethodname, array('fetch', 'idate', 'sanitize', 'select_language', 'trans'))) {
				//print "tmpmethodname=".$tmpmethodname."\n";
				$expr = $node->var;
				$args = $node->getArgs();
				$nbofparam = count($args);

				if ($nbofparam >= 1) {
					$tmpargs = $args;
					foreach ($args as $key => $arg) {	// only 1 element in this array
						//var_dump($key);
						//var_dump($arg->value);exit;
						if ($this->isGlobalVar($arg->value)) {
							$constName = $this->getConstName($arg->value);
							if (empty($constName)) {
								return;
							}
							$a = new FuncCall(new Name('getDolGlobalString'), [new Arg($constName)]);
							$tmpargs[$key] = new Arg($a);

							$r = new MethodCall($expr, $tmpmethodname, $tmpargs);
							return $r;
						}
					}
				}
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


		// Now process all comparison like:
		// $conf->global->... Operator Value

		$typeofcomparison = '';
		if ($node instanceof Equal) {
			$typeofcomparison = 'Equal';
		}
		if ($node instanceof NotEqual) {
			$typeofcomparison = 'NotEqual';
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
		if ($node instanceof NotIdentical) {
			$typeofcomparison = 'NotIdentical';
			//var_dump($node->left);
		}

		if (empty($typeofcomparison)) {
			return;
		}

		$isconfglobal = $this->isGlobalVar($node->left);
		if (!$isconfglobal) {
			// The left side is not conf->global->xxx, so we leave
			return;
		}

		// Test the type after the comparison conf->global->xxx to know the name of function
		$typeright = $node->right->getType();
		switch ($typeright) {
			case 'Scalar_LNumber':
				$funcName = 'getDolGlobalInt';
				break;
			case 'Scalar_String':
				$funcName = 'getDolGlobalString';
				break;
			default:
				$funcName = 'getDolGlobalString';
				break;
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
		if ($typeofcomparison == 'NotEqual') {
			return new NotEqual(
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
		if ($typeofcomparison == 'NotIdentical') {
			return new NotIdentical(
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
