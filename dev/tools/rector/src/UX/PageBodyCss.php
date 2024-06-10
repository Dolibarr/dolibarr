<?php

namespace Dolibarr\Rector\UX;

use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class PageBodyCss extends AbstractRector
{
	private $files = [];

	/**
	 * @throws PoorDocumentationException
	 */
	public function getRuleDefinition(): RuleDefinition
	{
		return new RuleDefinition(
			'Add UX page body css',
			[new CodeSample(
				'',
				''
			)]
		);
	}

	public function getNodeTypes(): array
	{
		return [Node\Expr\FuncCall::class];
	}

	public function refactor(Node $node)
	{
		if (!$node instanceof Node\Expr\FuncCall) {
			return null;
		}
		$name = $this->getName($node);
		if ($name !== 'llxHeader') {
			return null;
		}
		/** @var MutatingScope $scope */
		$scope = $node->getAttribute('scope');
		$file = $scope->getFile();
		if ($this->shouldSkip($file)) {
			return null;
		}


		$this->files[] = $file;
		if (preg_match("/admin/", $file)) {
			return null;
		}
		$args = $node->getArgs();
		if (!isset($args[0])) {
			$args[0] = new Node\Arg(new Node\Scalar\String_(''));
		}
		if (!isset($args[1])) {
			$args[1] = new Node\Arg(new Node\Scalar\String_(''));
		}
		if (!isset($args[2])) {
			$args[2] = new Node\Arg(new Node\Scalar\String_(''));
		}
		if (!isset($args[3])) {
			$args[3] = new Node\Arg(new Node\Scalar\String_(''));
		}
		if (!isset($args[4])) {
			$args[4] = new Node\Arg(new Node\Scalar\LNumber(0));
		}
		if (!isset($args[5])) {
			$args[5] = new Node\Arg(new Node\Scalar\LNumber(0));
		}
		if (!isset($args[6])) {
			$args[6] = new Node\Arg(new Node\Scalar\String_(''));
		}
		if (!isset($args[7])) {
			$args[7] = new Node\Arg(new Node\Scalar\String_(''));
		}
		if (!isset($args[8])) {
			$args[8] = new Node\Arg(new Node\Scalar\String_(''));
		}
		$value = explode(
			" ",
			$args[9] ?
				$args[9]->value->jsonSerialize()['value'] :
				''
		);
		$value[] = $this->getModuleCss($file);
		$value[] = $this->getPageCss($file);
		$value = implode(" ", array_unique($value));
		$args[9] = new Node\Arg(new Node\Scalar\String_(trim($value)));
		return new Node\Expr\FuncCall(new Node\Name('llxHeader'), $args, $node->getAttributes());
	}

	private function shouldSkip($file)
	{
		if (in_array($file, $this->files)) {
			return true;
		}
		if (preg_match("/admin|tpl/", $file)) {
			return true;
		}
		return false;
	}

	private function getPageCss($file)
	{
		$filename = explode("htdocs", preg_replace("/\.php$/", "", $file));
		if (!isset($filename[1])) {
			return '';
		}
		$paths = array_values(array_filter(explode("/", $filename[1]), function ($item) {
			return !empty($item);
		}));
		array_shift($paths);
		if (!preg_match("/list|card/", $file)) {
			array_unshift($paths, 'card');
		}
		$paths = implode("_", $paths);
		return "page-{$paths}";

	}

	private function getModuleCss($file)
	{
		$filename = explode("htdocs", $file);
		if (!isset($filename[1])) {
			return '';
		}
		$paths = array_values(array_filter(explode("/", $filename[1]), function ($item) {
			return !empty($item);
		}));
		return "mod-{$paths[0]}";
	}
}
