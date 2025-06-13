<?php
/**
 * This rule to replace exit does not work because "exit" is not a function
 */

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
class UsePositiveExit extends AbstractRector
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
			'Change exit(-x) int exit(x)',
			[new CodeSample(
				'exit(-2)',
				'exit(2)'
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
		return [FuncCall::class];
	}

	/**
	 * refactor
	 *
	 * @param 	Node 					$node 		A node
	 * @return	FuncCall|BooleanNot
	 */
	public function refactor(Node $node)
	{
		if ($node instanceof FuncCall) {
			$tmpfunctionname = $this->getName($node);
			// If function is ok. We must avoid a lot of cases like isset(), empty()
			if (in_array($tmpfunctionname, array('exit'))) {
				//print "tmpfunctionname=".$tmpfunctionname."\n";
				$args = $node->getArgs();
				$nbofparam = count($args);

				if ($nbofparam >= 1) {
					$tmpargs = $args;
					foreach ($args as $key => $arg) {	// only 1 element in this array
						//var_dump($key);
						//var_dump($arg->value);exit;
						if (empty($arg->value)) {
							return;
						}
						$a = new FuncCall(new Name('exit'), [new Arg(abs($arg->value))]);
						//$tmpargs[$key] = new Arg($a);
						return $a;

						//$r = new FuncCall(new Name($tmpfunctionname), $tmpargs);
						//return $r;
					}
				}
			}
			return $node;
		}

		return null;
	}
}
