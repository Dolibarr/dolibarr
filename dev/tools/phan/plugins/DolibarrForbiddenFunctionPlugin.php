<?php
/* Copyright (C) 2026		MDW							<mdeweerd@users.noreply.github.com>
 */

declare(strict_types=1);

use ast\Node;
use Phan\PluginV3;
use Phan\PluginV3\PluginAwarePostAnalysisVisitor;
use Phan\PluginV3\PostAnalyzeNodeCapability;

/**
 * DolibarrForbiddenFunctionPlugin hooks into one event:
 *
 * - getPostAnalyzeNodeVisitorClassName
 *   This method returns a visitor that is called on every AST node from every
 *   file being analyzed
 *
 * A plugin file must
 *
 * - Contain a class that inherits from \Phan\PluginV3
 *
 * - End by returning an instance of that class.
 *
 * It is assumed without being checked that plugins aren't
 * mangling state within the passed code base or context.
 *
 * Note: When adding new plugins,
 * add them to the corresponding section of README.md
 */
class DolibarrForbiddenFunctionPlugin extends PluginV3 implements PostAnalyzeNodeCapability
{
	/**
	 * @return string - name of PluginAwarePostAnalysisVisitor subclass
	 */
	public static function getPostAnalyzeNodeVisitorClassName(): string
	{
		return DolibarrForbiddenFunctionVisitor::class;
	}
}

/**
 * When __invoke on this class is called with a node, a method
 * will be dispatched based on the `kind` of the given node.
 *
 * Visitors such as this are useful for defining lots of different
 * checks on a node based on its kind.
 */
class DolibarrForbiddenFunctionVisitor extends PluginAwarePostAnalysisVisitor
{
	// A plugin's visitors should not override visit() unless they need to.

	/**
	 * List of forbidden PHP functions and their Dolibarr replacements
	 *
	 * @var array<string, string>
	 */
	private const FORBIDDEN_FUNCTIONS = [
		'eval' => 'dol_eval', // secure eval
		'time' => 'dol_now', // ensure consistent timezone handling
		'getmypid' => 'dol_getmypid', // fallback for systems without getmypid
		'strtolower' => 'dol_strtolower', // explicitly "Never use strtolower" - does not work with UTF8 strings
		'strtoupper' => 'dol_strtoupper', // explicitly "Never use strtoupper" - does not work with UTF8 strings
		'strlen' => 'dol_strlen', // UTF-8 safe string length calculation
		'mktime' => 'dol_mktime', // handles Dolibarr timezone configuration
		'strftime' => 'dol_strftime', // handles Dolibarr timezone configuration
		'getdate' => 'dol_getdate', // handles Dolibarr timezone configuration
		'strtotime' => 'dol_stringtotime', // handles Dolibarr date parsing rules
		'ucfirst' => 'dol_ucfirst', // explicitly "Never use ucfirst" - does not work with UTF8 strings
		'ucwords' => 'dol_ucwords', // UTF-8 safe word capitalization
		'substr' => 'dol_substr', // UTF-8 safe substring extraction
		'is_file' => 'dol_is_file', // Encodes path to os
		'is_dir' => 'dol_is_dir', // Encodes path to os
		'is_link' => 'dol_is_link', // Encodes path to os
		'is_writable' => 'dol_is_writable', // Encodes path to os
		'filesize' => 'dol_filesize', // Encodes path to os
		'fileperm' => 'dol_fileperm', // Encodes path to os
		'filemtime' => 'dol_filemtime', // Encodes path to os
		// 'basename' => 'dol_basename', // Documented to work with cyrillic, but dol_basename is bugged in 09/2026
		// curl_init(), curl_exec(), curl_setopt_array(), curl_setopt(), curl_close(), curl_getinfo(), curl_error() -> use getURLContent() (Dolibarr HTTP client with proxy, timeout, and error handling)
		'curl_init' => 'getURLContent',
		'curl_exec' => 'getURLContent',
		// 'curl_setopt_array' => 'getURLContent',  // Not reporting (limit suppressions), any of the other functions will be used
		// 'curl_setopt' => 'getURLContent',  // Not reporting (limit suppressions), any of the other functions will be used
		'curl_close' => 'getURLContent',
		'curl_getinfo' => 'getURLContent',
		'curl_error' => 'getURLContent',
	];

	/**
	 * @param Node $node A node to analyze
	 *
	 * @return void
	 *
	 * @override
	 */
	public function visitCall(Node $node): void
	{
		$name = $node->children['expr']->children['name'] ?? null;
		if (!is_string($name)) {
			return;
		}
		if (!array_key_exists($name, self::FORBIDDEN_FUNCTIONS)) {
			return;
		}
		$this->emitPluginIssue(
			$this->code_base,
			$this->context,
			'DolibarrForbiddenFunctionPlugin',
			$name.'() is not allowed in Dolibarr code, use '.self::FORBIDDEN_FUNCTIONS[$name].'() instead',
			[]
		);
	}
}

// Every plugin needs to return an instance of itself at the
// end of the file in which it's defined.
return new DolibarrForbiddenFunctionPlugin();
