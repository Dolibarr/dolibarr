<?php
/* Copyright (C) 2026		MDW	<mdeweerd@users.noreply.github.com>
 */
declare(strict_types=1);

/**
 * @phan-file-suppress PhanCompatibleTypedProperty
 * @phan-file-suppress PhanPluginUnreachableCode
 * @phan-file-suppress PhanUndeclaredClassMethod
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredInterface
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanUndeclaredTypeProperty
 * @phan-file-suppress PhanUnreferencedUseNormal
 * @phan-file-suppress PhanPluginUnknownObjectMethodCall
 */

/**
 * Phan plugin to detect unsafe SQL variable usage in Dolibarr codebase.
 *
 * This plugin checks for variables used in $sql or $sql_* assignments that are not
 * properly escaped, cast, or protected by safe methods.
 */

use ast\Node;
use Phan\Config;
use Phan\PluginV3;
use Phan\PluginV3\PostAnalyzeNodeCapability;

/**
 * Plugin class that registers the SQLinjection visitor.
 */
final class SqlInjectionPlugin extends PluginV3 implements PostAnalyzeNodeCapability
{
	/**
	 * @var bool If true, enable debug ('debug' option for plugin)
	 * @internal
	 */
	public static bool $debugEnabled = false;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// @phpstan-ignore-next-line nullCoalesce.property
		self::$debugEnabled = (bool) (Config::toArray()['SqlInjectionPlugin']['debug'] ?? false);
	}

	/**
	 * Get the class name of the visitor that will be used to analyze nodes.
	 *
	 * @return string The fully qualified class name of the visitor
	 */
	public static function getPostAnalyzeNodeVisitorClassName(): string
	{
		return SqlInjectionVisitor::class;
	}
}

/**
 * Visitor class that checks for unsafe SQL variable usage.
 *
 * Operation principle, on nodes that assign to an 'sql' variable, check
 * that the expression only has variables that are supposedly escaped
 * or "escaped" by proper casting of function calls.
 *
 * The function `checkExpressionForUnsafeVariables` is the entry point for
 * checking an expression and it operates recursively (on nodes).
 * Recursion is stopped when a safe cast, function or method call is found.
 * When a variable is verified, an issue is emitted if it is unsafe.
 *
 * @property-read \Phan\CodeBase $code_base
 * @property-read \Phan\Language\Context $context
 */
class SqlInjectionVisitor extends \Phan\PluginV3\PluginAwarePostAnalysisVisitor
{
	/**
	 * @property-read \Phan\CodeBase $code_base
	 * @phpstan-property-read Object $code_base
	 * @property-read \Phan\Language\Context $context
	 * @phpstan-property-read Object $context
	 * @phan-suppress PhanUndeclaredTypeProperty
	 */

	/**
	 * List of method names considered safe for SQL values.
	 *
	 * @var string[]
	 */
	private const SAFE_METHODS = [
		// CommonObject
		'quote',   // Safe, based on fields definition
		// DoliDB methods, ...
		'escape',    // Safe, goal is to escape
		'idate',  // Safe uses dol_print_date
		'jdate',  // Safe uses dol_mktime
		'order',  // Sanitizes arguments
		'plimit',  // Safe, limits are casted to int
		'prefix',  // Not fully safe - would be better to define prefix($tablebasename) and protect
		'sanitize',  // Safe, goal is to cleanup
		// 'order',  // Not safe - fields are not checked
		'regexpsql', // Partially safe - $subject is not escaped if $sqlstring is 0
		'encrypt',  // Safe, results in string
		// 'decrypt',  // Unsafe, decrypted value is unknown
		// 'ifsql', // Not safe because arguments are not escaped
		// 'stdevpop', // Not safe because field is not escaped/verified
		// 'hintindex', // Not safe because field is not escaped/verified
		// QuickMemo
		'getMemosQuery',  // Safe, variables protected in function
		'getTemplateMemosQuery',  // Safe, variables protected in function
		// Functions
		'GETPOSTFLOAT', //Returns float
		'GETPOSTINT', // Returns int
		'addMailingEventTypeSQL', // Returns sql
		'count', // Returns int
		'date_format', // Returns formatted string
		'dolSqlDateFilter', // Partially safe datefield not checked/escaped
		'dol_escape_json',
		'dol_hash', // Returns string
		'dol_print_date', // Returns formatted string
		'dol_sanitizeFileName', // Supposed ok for sql (?)
		'dol_strlen', // Returns int
		'floatval', // Returns float
		'forgeSQLFromUniversalSearchCriteria', // Returns sql
		'getDolUserInt', //Returns float
		'getEntity', // Returns entity
		'getSqlCalEvents', // Returns sql
		'intval', // Returns int
		'natural_search',
		'price2num', // Returns formatted number
		'sanititzekey', // Used with array_map
		'setEntity', // Returns int
		'strlen', // Returns int
		'strpos', // Returns int
		'transformToSQL', // Returns sql (advtargetemailing)
		// 'get_exdir',  // Not safe if directory could look like SQL (forged, e.g. sql as invoice ref)
		'getSQLFactLines', // intracommreport.class.php
	];

	/**
	 * Regex pattern for safe SQL string characters that can appear after an unclosed quote.
	 * These characters don't need escaping and can appear in SQL string literals.
	 */
	private const SAFE_STRING_CHARS_REGEX = '/^[\w\d\/\-\s%_=<>!,\(\)]+$/';

	/**
	 * List of methods that require their output to be wrapped in quotes in SQL strings.
	 * These methods escape/clean values but don't add the surrounding quotes.
	 *
	 * @var string[]
	 */
	private const METHODS_REQUIRING_QUOTES = [
		'escape',
		'escapeforlike',
		'idate',
		'date_format',
		'dol_escape_json',
		'dol_hash',
		'dol_print_date',
		'dol_sanitizeFileName',
	];

	/**
	 * List of variable names that are safe in SQL context.
	 *
	 * @var string[]
	 */
	private const SAFE_SQL_VARIABLES = [
		'table', 'table2', 'table3', 'tables', 'column', 'columns', 'field', 'field2', 'field3', 'fields', 'where', 'table_element',
		'key', 'row', 'value', 'tmptable', 'tmparray', 'tmpval', 'fieldid', 'dbtablename', 'dbt_keyfield',
		'fieldstoshow', 'fields_label', 'fieldlabel',
		'tmpsortfield',
		'mode', 'place', 'clause', 'type', 'like', 'tmpdatabase',
		'_SESSION',
		'tabletodelete', 'tabletodrop', 'tablealiastouse', 'tabletuse', 'tablename', 'tabledet', 'table_extraf', 'tables_from_used', 'tables_from', 'dictionarytable', 'aliastablesociete',
		'tabletouse', 'tmpdatabase',
		'element',
		'morewhere', 'sortfield', 'sortorder', 'morefilter', 'morewherefilter',
		'selectFields', 'selectFieldsGrouped', 'InfoFieldList',
		'extrafieldsTable', 'extrafieldsobjectkey',
		'alias_societe_perentity', 'alias_product_perentity',
		'excludefilter', 'addFilter',
		'tabrowid', 'tabsqlsort', 'tabfieldinsert', 'mode_info',
		'amountExpr', 'dateRange', 'countExpr',
		'q_escaped',
	];

	/**
	 * List of properties that are trusted in SQL context.
	 *
	 * @var string[]
	 */
	private const TRUSTED_PROPERTIES = [
		'database_name', // DoliDB
		'table_element', 'table', 'table2', 'fk_element', 'element', 'join', 'where', 'sortorder', 'table_element_line',
		'MAP_CAT_FK', 'MAP_CAT_TABLE', 'MAP_OBJ_TABLE',
		'field', 'field_line', 'field_date',
		'categ_link',  // commandestats
		'table_rowid',
		'from',
		'filtervalue', // advtargetemailing
	];

	/**
	 * Emit a debug message with file and line context
	 *
	 * @param string	$message	Debug message
	 * @param bool		$forceMsg	When true, always report messaga
	 * @return void
	 */
	private function debug(string $message, $forceMsg = false): void
	{
		if (!SqlInjectionPlugin::$debugEnabled || !$forceMsg) {
			return;
		}

		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$caller = $trace[0] ?? ['file' => 'unknown', 'line' => 0];
		$file = $caller['file'] ?? 'unknown';
		$line = $caller['line'] ?? 0;
		$shortFile = basename($file);
		// @phpstan-ignore-next-line method.notFound
		$this->emitPluginIssue(
			$this->code_base, // @phpstan-ignore property.notFound
			$this->context, // @phpstan-ignore property.notFound
			$forceMsg ? 'SqlInjectionDebugAlways' : 'SqlInjectionDebug',
			"[DEBUG {$shortFile}:{$line}] %s",
			[$message]
		);
	}

	/**
	 * Check if a quote at the given position is followed by safe SQL characters.
	 * Safe characters don't need escaping and can appear in SQL string literals.
	 *
	 * @param string $str The string to check
	 * @param int $quotePos The position of the quote in the string
	 * @return bool True if the quote is followed by safe characters
	 */
	private function quoteFollowedBySafeChars(string $str, int $quotePos): bool
	{
		$afterQuote = substr($str, $quotePos + 1);
		return $afterQuote !== '' && preg_match(self::SAFE_STRING_CHARS_REGEX, $afterQuote);
	}

	/**
	 * Check if a node (VAR or PROP) has an accepted name or is a DoliDB instance
	 *
	 * @param Node $node The node to check (VAR or PROP)
	 * @param string[] $acceptedNames List of variable names to accept
	 * @return bool True if name is accepted or variable is DoliDB
	 */
	private function isDoliDB(Node $node, array $acceptedNames): bool
	{
		if ($node->kind === \ast\AST_PROP) {
			$expr = $node->children['expr'] ?? null;
			if ($expr instanceof Node) {
				return $this->isDoliDB($expr, $acceptedNames);
			}
			return false;
		}

		if ($node->kind !== \ast\AST_VAR) {
			return false;
		}

		$varName = $node->children['name'] ?? null;
		if (!is_string($varName)) {
			return false;
		}

		$allAccepted = array_merge($acceptedNames, ['db', 'dbs', 'dbsession', 'database', 'dbconn', 'conn']);
		if (in_array($varName, $allAccepted, true) ||
		str_contains($varName, 'db') ||
		str_contains($varName, 'DB')) {
			return true;
		}

		try {
			// @phpstan-ignore-next-line property.notFound
			$scope = $this->context->getFunctionLikeScope() ?? $this->context->getScope();
			if ($scope === null) {
				return false;
			}
			$variable = $scope->getVariableByName($varName);
			if ($variable === null) {
				return false;
			}
			foreach ($variable->getUnionType()->getTypeSet() as $type) {
				if (method_exists($type, 'getFQSEN') && $type->getFQSEN() === '\\DoliDB') {
					return true;
				}
			}
		} catch (\Throwable $e) {
			// Fall through
			$this->debug((string) $e);
		}

		return false;
	}

	/**
	 * Check assignments to SQL variables.
	 *
	 * @param Node $node The assignment node being visited
	 * @return void
	 */
	public function visitAssign(Node $node): void
	{
		// $this->debug("visitAssign: node=".var_export($node, true));
		$var = $node->children['var'];
		if ($this->isSqlVariable($var)) {
			$varName = $this->getNodeVar($var);
			$this->debug("visitAssign: var={$varName}, kind={$var->kind}");
			$expr = $node->children['expr'] ?? null;
			if ($expr instanceof Node) {
				$this->checkExpressionForUnsafeVariables($expr, $node);
			}
		}
	}

	/**
	 * Return string representation of VAR or PROP, null is neither
	 *
	 * @param Node $node The var or property node
	 * @return ?string The string representation, or null of not a var nor property
	 */
	private function getNodeVar(Node $node): ?string
	{
		$varName = null;
		if ($node->kind === \ast\AST_VAR) {
			$varName = is_string($node->children['name'] ?? null) ? (string) '$'.$node->children['name'] : '$?';
		} elseif ($node->kind === \ast\AST_PROP) {
			$objName = $this->getNodeVar($node->children['expr']);
			$prop = $node->children['prop'] ?? '?';
			if ($prop instanceof Node) {
				$prop = $this->getNodeVar($prop);
			} elseif (!is_string($prop)) {
				$prop = '....';
			}
			$varName = "$objName->$prop";
		} elseif ($node->kind === \ast\AST_DIM) {
			$dimName = $this->getNodeVar($node->children['expr']);
			$idx = $node->children['dim'] ?? '?';
			if ($idx instanceof Node) {
				$idx = $this->getNodeVar($idx);
			} elseif (is_string($idx)) {
				$idx = "'$idx'";
			} elseif (is_int($idx)) {
				$idx = "$idx";
			} else {
				// $this->debug("idx=".var_export($idx,true));
				$idx = '....';
			}
			$varName = "{$dimName}[{$idx}]";
		}
		return $varName;
	}
	/**
	 * Handle compound assignments (e.g., $sql .= ...)
	 *
	 * @param Node $node The compound assignment node
	 * @return void
	 */
	public function visitAssignOp(Node $node): void
	{
		$var = $node->children['var'];
		if ($this->isSqlVariable($var)) {
			$varName = $this->getNodeVar($var);
			// $this->debug("visitAssignOp: var={$varName}, kind={$var->kind}");
			$expr = $node->children['expr'] ?? null;
			if ($expr instanceof Node) {
				$this->checkExpressionForUnsafeVariables($expr, $node);
			}
		}
	}

	/**
	 * Check if a node represents a SQL variable.
	 *
	 * @param mixed $node The node to check
	 * @return bool True if the node is a SQL variable
	 */
	private function isSqlVariable($node): bool
	{
		if (!($node instanceof Node)) {
			return false;
		}

		if ($node->kind === \ast\AST_VAR) {
			$name = $node->children['name'] ?? null;
			$lowername = is_string($name) ? (string) strtolower((string) $name) : 'not_a_string';
			$result = false;
			if (
				is_string($name) // Must be a string
				&& (!in_array(substr($name, 0, 5), ['resql'])) // Not the result of sql request
				&& (
					strpos($name, 'sql') === 0				// Starts with sql
					|| substr($lowername, -3) === 'sql'		// Ends with sql
					|| substr($name, -6) === '_query'		// Ends with _query
					|| strpos($lowername, 'sanitized') !== false // sanitized in name
					// || strpos($name, 'filter') === 0		// Too wide match, also filters for html (prefer $sqlFilter)
					// || substr($name, -6) === 'filter'	// Too wide match, also $moreforfilter (prefer $sqlFilter)
					|| ($name !== 'query' && in_array(substr($name, 0, 5), array('query', 'where')))	// Is a (partial) SQL clause (prefer $sqlXXXXX)
					|| in_array($name, ['from', 'join', 'sortorder', 'groupby', 'orderby', 'tabsqlsort', 'tabrowid', 'tabfieldinsert', 'resultsql'])				// Is safe SQL
				) && (false === strpos($lowername, 'sqlfile')) // Starts with sqlfile (is not an sql variable)
				&&	(false === strpos($lowername, 'pathtosql')) // Looks like path to sql file
			) {
				$result = true;
			}
			$this->debug("isSqlVariable: name=" . var_export($name, true) . ", result=" . var_export($result, true));
			return $result;
		}

		// Check for object properties ($obj->sql, $obj->where, $obj->from, $obj->order, etc.)
		if ($node->kind === \ast\AST_PROP) {
			$propName = $node->children['prop'] ?? null;
			if (!is_string($propName)) {
				$this->debug("isSqlVariable (NOPROPNAME):" . var_export($node, true));
				return false;
			}
			$result
				= (
					strpos($propName, 'sql') === 0
				  || strpos($propName, 'sanitized') === 0
				  || strpos($propName, 'MAP_CAT_') === 0
				  || strpos($propName, 'MAP_OBJ_') === 0
				  || in_array($propName, [/*'order',*/ 'where', 'from', 'join', 'sortorder'])  // A property that is part of SQL
				);
			$this->debug("isSqlVariable (PROP): prop=" . $this->getNodeVar($node) . ", result=" . var_export($result, true));
			return $result;
		}

		// Check for array dimensions ($array['sql...'] or $array['...sql'])
		if ($node->kind === \ast\AST_DIM) {
			if ($this->isSqlVariable($node->children['expr'] ?? null)) {
				return true;
			}
			// $this->debug("isSqlVariable: kind={$node->kind}");
			$dimName = $node->children['dim'] ?? null;
			// $this->debug("isSqlVariable: dim=".var_export($dim,true));
			if (is_string($dimName)) {
				$lowerDim = strtolower($dimName);
				if (strpos($dimName, 'sql') === 0 || strpos($lowerDim, 'sql') === 0) {
					$this->debug("isSqlVariable (DIM): dim={$dimName}, result=true");
					return true;
				} elseif ($dimName === 'takeposterminal') {
					// Supposing $_SESSION['takeposterminal]
					return true;
				}
			}
			// Recursively check the expression
			$expr = $node->children['expr'] ?? null;
			return $expr instanceof Node && $this->isSqlVariable($expr);
		}

		return false;
	}

	/**
	 * Recursively check an expression for unsafe variable usage.
	 *
	 * @param mixed $expr The expression to check
	 * @param Node $contextNode The context node for error reporting
	 * @param ?Node $parentNode The parent node in the AST (for context checking)
	 * @return void
	 */
	private function checkExpressionForUnsafeVariables($expr, Node $contextNode, ?Node $parentNode = null): void
	{
		if (!($expr instanceof Node)) {
			return;
		}

		// $this->debug("checkExpr: kind=".$expr->kind.",expr=".var_export($expr));
		switch ($expr->kind) {
			case \ast\AST_VAR:
				$this->debug("checkExpr: VAR node");
				$this->checkVariable($expr, $contextNode);
				break;

			case \ast\AST_PROP:
				$this->debug("checkExpr: PROP node");
				$this->checkVariable($expr, $contextNode);
				break;

			case \ast\AST_DIM:
				$this->debug("checkExpr: DIM node");
				$this->checkVariable($expr, $contextNode);
				break;

			case \ast\AST_BINARY_OP:
				$left = $expr->children['left'] ?? null;
				$right = $expr->children['right'] ?? null;
				$flags = $expr->flags ?? null;

				// If this is a string concatenation (.), check for unquoted escape methods
				if ($flags === \ast\flags\BINARY_CONCAT) {
					$this->checkConcatenationForUnquotedEscape($expr, $contextNode);
				}

				if ($left instanceof Node) {
					$this->checkExpressionForUnsafeVariables($left, $contextNode, $expr);
				}
				if ($right instanceof Node) {
					$this->checkExpressionForUnsafeVariables($right, $contextNode, $expr);
				}
				break;

			case \ast\AST_CONDITIONAL:
				$trueExpr = $expr->children['true'] ?? null;
				$falseExpr = $expr->children['false'] ?? null;
				$this->debug("checkExpr: CONDITIONAL node");
				if ($trueExpr instanceof Node) {
					$this->checkExpressionForUnsafeVariables($trueExpr, $contextNode, $expr);
				}
				if ($falseExpr instanceof Node) {
					$this->checkExpressionForUnsafeVariables($falseExpr, $contextNode, $expr);
				}
				// Intentionally skip condition - it's not part of SQL
				break;

			case \ast\AST_CALL:
			case \ast\AST_METHOD_CALL:
				$method = $expr->children['expr'] ?? null;
				$methodKind = $method instanceof Node ? $method->kind : 'null';
				$methodName = $method->children['name'] ?? null;
				$objNode = ($expr->kind === \ast\AST_METHOD_CALL) ? ($expr->children['expr'] ?? null) : null;
				$this->debug("checkExpr: CALL/METHOD_CALL node, methodKind={$methodKind}, methodName=" . var_export($methodName, true));

				// Note: We check for unquoted escape/sanitize in the BINARY_OP case (concatenation)
				// This handles cases like: "..." . $db->escape($x) . "..."
				// For non-concatenation cases, we rely on the string context check in BINARY_OP

				if (!$this->isSafeMethodCall($expr)) {
					$argsNode = $expr->children['args'] ?? null;
					if ($argsNode instanceof Node) {
						$argCount = count($argsNode->children ?? []);  // @phpstan-ignore nullCoalesce.property
						$this->debug("Checking {$argCount} args");
						foreach ($argsNode->children ?? [] as $idx => $arg) {  // @phpstan-ignore nullCoalesce.property
							if ($idx == 0) {
								$isArgsOk = false;
							}
							if (in_array($methodName, ['preg_replace'])) {
								if ($idx == 0) {// && $arg->children['value']) {
									$isArgsOk = in_array($arg, ['/[^a-zA-Z]/']);
									// $this->debug("preg_replace$idx=".var_export($arg, true));
								} elseif ($isArgsOk && $idx == 1) {
									$isArgsOk = in_array($arg, ['']);
								} elseif ($isArgsOk && $idx == 2) {
									return;
								} else {
									$this->checkExpressionForUnsafeVariables($arg, $contextNode, $expr);
								}
								// First argument of this function does not need to be safe
								continue;
							}
							$value = $arg; //$arg->children['value'] ?? $arg;
							if ($value instanceof Node) {
								$this->checkExpressionForUnsafeVariables($value, $contextNode, $expr);
							}
						}
					}
				} else {
					$this->debug("Method is SAFE - skipping args");
				}
				break;

			case \ast\AST_CAST:
				$flags = $expr->flags ?? null;  // @phpstan-ignore nullCoalesce.property
				if (is_int($flags) && in_array($flags, [\ast\flags\TYPE_DOUBLE, \ast\flags\TYPE_LONG], true)) {
					return;
				}
				$inner = $expr->children['expr'] ?? null;
				if ($inner instanceof Node) {
					$this->checkExpressionForUnsafeVariables($inner, $contextNode, $expr);
				}
				break;

			case \ast\AST_CONST:
			case \ast\AST_NAME:
			case \ast\AST_MAGIC_CONST:
			case \ast\AST_CLASS_CONST:
				// Safe node types - no variables to check
				break;

			default:
				// For any other node type, recursively check all children
				$this->debug("Other node kind=".$expr->kind);
				foreach ($expr->children ?? [] as $child) {  // @phpstan-ignore nullCoalesce.property
					if ($child instanceof Node) {
						$this->checkExpressionForUnsafeVariables($child, $contextNode, $expr);
					}
				}
		}
	}

	/**
	 * Check a concatenation for unquoted escape/sanitize method calls
	 *
	 * @param Node $binaryOp The BINARY_OP node with BINARY_CONCAT flag
	 * @param Node $contextNode The context node for error reporting
	 * @return void
	 */
	private function checkConcatenationForUnquotedEscape(Node $binaryOp, Node $contextNode): void
	{
		$left = $binaryOp->children['left'] ?? null;
		$right = $binaryOp->children['right'] ?? null;

		// Check if either operand is an unquoted escape/sanitize method call
		if ($left instanceof Node && $this->isMethodCallNeedingQuotes($left)) {
			$this->checkMethodCallQuotingInOperand($left, $binaryOp, $contextNode, 'left', $right);
		}
		if ($right instanceof Node && $this->isMethodCallNeedingQuotes($right)) {
			$this->checkMethodCallQuotingInOperand($right, $binaryOp, $contextNode, 'right', $left);
		}
	}

	/**
	 * Check if a node is a DoliDB escape/sanitize method call that requires quoting
	 *
	 * @param Node $operand The operand node to check
	 * @return bool True if this is a method call that needs quote checking
	 */
	private function isMethodCallNeedingQuotes(Node $operand): bool
	{
		if ($operand->kind !== \ast\AST_METHOD_CALL && $operand->kind !== \ast\AST_CALL) {
			return false;
		}

		$methodName = null;
		$objNode = null;

		if ($operand->kind === \ast\AST_METHOD_CALL) {
			$methodName = $operand->children['method'] ?? null;
			$objNode = $operand->children['expr'] ?? null;
		} else {
			$method = $operand->children['expr'] ?? null;
			if ($method instanceof Node && $method->kind === \ast\AST_NAME) {
				$methodName = $method->children['name'] ?? null;
			}
		}

		if (!is_string($methodName) || !in_array($methodName, self::METHODS_REQUIRING_QUOTES, true)) {
			return false;
		}

		// Check if it's called on a DoliDB instance
		if ($operand->kind === \ast\AST_METHOD_CALL && $objNode instanceof Node) {
			return $this->isDoliDB($objNode, ['this', 'db']);
		}

		return false;
	}

	/**
	 * Check if a method call in a concatenation operand is properly quoted
	 *
	 * @param Node $operand The operand node to check
	 * @param Node $binaryOp The parent BINARY_OP node
	 * @param Node $contextNode The context node for error reporting
	 * @param string $position Either 'left' or 'right'
	 * @param mixed $oppositeOperand The opposite operand (for getting quote type)
	 * @return void
	 */
	private function checkMethodCallQuotingInOperand(Node $operand, Node $binaryOp, Node $contextNode, string $position, $oppositeOperand): void
	{
		// This method now only checks the quoting context for method calls that need it
		// The filtering of which methods need quoting is done in checkConcatenationForUnquotedEscape

		// Get the expected quote type from the opposite operand
		$expectedQuoteType = $this->getQuoteTypeFromOperand($oppositeOperand);

		// Check if the method call is properly quoted
		if (!$this->isMethodCallInQuotedContext($operand, $binaryOp, $position, $expectedQuoteType, $contextNode)) {
			$methodDisplay = $this->getNodeVarForMethodCall($operand);
			// @phpstan-ignore-next-line method.notFound
			$this->emitPluginIssue(
				$this->code_base, // @phpstan-ignore property.notFound
				$this->context, // @phpstan-ignore property.notFound
				'SqlInjectionUnquotedEscape',
				"Method call {$methodDisplay} used in SQL without surrounding quotes. Wrap in single or double quotes, e.g., \"'\" . {$methodDisplay} . \"'\".",
				[]
			);
		}
	}

	/**
	 * Extract the quote type from an operand (if it contains a quote)
	 *
	 * @param mixed $operand The operand to check
	 * @return ?string The quote type ('\'' or '"') or null if no quote found
	 */
	private function getQuoteTypeFromOperand($operand): ?string
	{
		if (is_string($operand)) {
			if (strpos($operand, "'") !== false) {
				return "'";
			}
			if (strpos($operand, '"') !== false) {
				return '"';
			}
			return null;
		}

		if ($operand instanceof Node && $operand->kind === \ast\AST_BINARY_OP && ($operand->flags ?? 0) === \ast\flags\BINARY_CONCAT) {
			// Recursively check nested concatenation
			$left = $operand->children['left'] ?? null;
			$right = $operand->children['right'] ?? null;

			// Try left first
			$leftQuote = $this->getQuoteTypeFromOperand($left);
			if ($leftQuote !== null) {
				return $leftQuote;
			}

			// Then try right
			return $this->getQuoteTypeFromOperand($right);
		}

		// Handle ENCAPS_LIST from string interpolation
		if ($operand instanceof Node && $operand->kind === \ast\AST_ENCAPS_LIST) {
			foreach ($operand->children as $child) {
				if (is_string($child)) {
					if (strpos($child, "'") !== false) {
						return "'" ;
					}
					if (strpos($child, '"') !== false) {
						return '"';
					}
				}
			}
			return null;
		}

		return null;
	}

	/**
	 * Check if a method call is properly quoted based on its position in a concatenation
	 *
	 * @param Node $methodCall The method call node
	 * @param Node $binaryOp The parent BINARY_OP node
	 * @param string $position The position ('left' or 'right') in the concatenation
	 * @param ?string $expectedQuoteType The expected quote type from the opposite operand
	 * @param Node|null $contextNode The context node (ASSIGN_OP or ASSIGN) for checking if we're at the top level
	 * @return bool True if properly quoted
	 */
	private function isMethodCallInQuotedContext(Node $methodCall, Node $binaryOp, string $position, ?string $expectedQuoteType = null, ?Node $contextNode = null): bool
	{
		// Get the sibling operand (the one we're not checking)
		$siblingOperand = ($position === 'left') ? ($binaryOp->children['right'] ?? null) : ($binaryOp->children['left'] ?? null);

		// If expectedQuoteType is not provided, compute it from the sibling
		if ($expectedQuoteType === null) {
			$expectedQuoteType = $this->getQuoteTypeFromOperand($siblingOperand);
		}

		// Helper function to check if the binaryOp is at the top level and should be flagged
		$checkTopLevel = function () use ($contextNode, $binaryOp, $position, $expectedQuoteType): bool {
			// If we determined that the method call is preceded by a quote (for right position)
			// or followed by a quote (for left position), we need to check if it's at the top level
			// If it is, there's no closing quote after/before the method call
			if ($contextNode !== null && ($contextNode->kind === \ast\AST_ASSIGN_OP || $contextNode->kind === \ast\AST_ASSIGN)) {
				$assignExpr = $contextNode->children['expr'] ?? null;
				if ($assignExpr === $binaryOp) {
					// This binaryOp IS the direct expression of the assignment
					// So the method call is at the end/beginning with no closing quote
					return false;
				}
				// The binaryOp is nested, so there might be a closing quote in the outer expression
				// Assume it's OK if the binaryOp is not the direct expression
				return true;
			}

			// No context node, we can't determine
			// Assume it's properly quoted
			return true;
		};

		// In AST, string literals in BINARY_OP are represented as direct string values
		if (is_string($siblingOperand)) {
			if ($position === 'left') {
				// Method call is on the left, check if sibling (right) starts with the expected quote
				if ($expectedQuoteType !== null) {
					return strpos($siblingOperand, $expectedQuoteType) === 0;
				}
				// Fallback: check for any quote
				return strpos($siblingOperand, "'") === 0 || strpos($siblingOperand, '"') === 0;
			} else {
				// Method call is on the right, check if sibling (left) ends with the expected quote or contains
				// an unclosed expected quote followed by acceptable characters
				if ($expectedQuoteType !== null) {
					$endsWithExpectedQuote = substr($siblingOperand, -1) === $expectedQuoteType;

					// Check if the left operand contains an unclosed quote of the expected type
					// followed by acceptable characters
					$containsUnclosedQuote = false;
					if (!$endsWithExpectedQuote) {
						$expectedQuotePos = strrpos($siblingOperand, $expectedQuoteType);
						if ($expectedQuotePos !== false && $this->quoteFollowedBySafeChars($siblingOperand, $expectedQuotePos)) {
							$containsUnclosedQuote = true;
						}
					}

					if (!$endsWithExpectedQuote && !$containsUnclosedQuote) {
						// Left operand doesn't end with the expected quote and doesn't contain an acceptable unclosed quote
						return false;
					}

					// Left operand ends with the expected quote or contains an unclosed expected quote followed by word chars
					// Now check if this binaryOp is at the top level of the expression
					return $checkTopLevel();
				}

				// Fallback: use the old logic for any quote type
				$hasLastQuote = substr($siblingOperand, -1) === "'" || substr($siblingOperand, -1) === '"';

				// Check if the left operand contains a quote (not necessarily at the end)
				// that might be an opening quote for a SQL string literal
				$containsUnclosedQuote = false;
				if (!$hasLastQuote) {
					// Check if there's a quote in the sibling that is followed by safe characters
					// This would indicate the escape call is being appended to a SQL string literal
					$lastSingleQuotePos = strrpos($siblingOperand, "'");
					$lastDoubleQuotePos = strrpos($siblingOperand, '"');

					if ($lastSingleQuotePos !== false && ($lastDoubleQuotePos === false || $lastSingleQuotePos > $lastDoubleQuotePos)) {
						// Last quote is single quote, check what comes after it
						if ($this->quoteFollowedBySafeChars($siblingOperand, $lastSingleQuotePos)) {
							$containsUnclosedQuote = true;
						}
					} elseif ($lastDoubleQuotePos !== false) {
						// Last quote is double quote, check what comes after it
						if ($this->quoteFollowedBySafeChars($siblingOperand, $lastDoubleQuotePos)) {
							$containsUnclosedQuote = true;
						}
					}
				}

				if (!$hasLastQuote && !$containsUnclosedQuote) {
					// Left operand doesn't end with a quote and doesn't contain an acceptable unclosed quote
					return false;
				}

				// Left operand ends with a quote or contains an unclosed quote followed by word chars
				// Now check if this binaryOp is at the top level of the expression
				return $checkTopLevel();
			}
		}

		// If the sibling is an ENCAPS_LIST (string with variable interpolation), check if it starts/ends with the expected quote
		if ($siblingOperand instanceof Node && $siblingOperand->kind === \ast\AST_ENCAPS_LIST) {
			if ($expectedQuoteType !== null) {
				if ($position === 'left') {
					// Method is on left, check if the ENCAPS_LIST starts with the expected quote
					return $this->encapsListHasFirstQuoteOfType($siblingOperand, $expectedQuoteType);
				} else {
					// Method is on right, check if the ENCAPS_LIST ends with the expected quote
					// or contains an unclosed quote of the expected type followed by acceptable characters
					if ($this->encapsListHasLastQuoteOfType($siblingOperand, $expectedQuoteType)) {
						// ENCAPS_LIST ends with the expected quote, now check if this is at the top level
						return $checkTopLevel();
					}
					// Check if the ENCAPS_LIST contains an unclosed quote of the expected type followed by acceptable characters
					if ($this->encapsListHasUnclosedQuoteOfType($siblingOperand, $expectedQuoteType)) {
						// ENCAPS_LIST contains an unclosed quote, now check if this is at the top level
						return $checkTopLevel();
					}
					// Doesn't end with or contain an unclosed quote of the expected type
					return false;
				}
			} else {
				// Fallback: check for any quote
				if ($position === 'left') {
					return $this->encapsListHasFirstQuote($siblingOperand);
				} else {
					if ($this->encapsListHasLastQuote($siblingOperand)) {
						// ENCAPS_LIST ends with a quote, now check if this is at the top level
						return $checkTopLevel();
					}
					if ($this->encapsListHasUnclosedQuote($siblingOperand)) {
						// ENCAPS_LIST contains an unclosed quote, now check if this is at the top level
						return $checkTopLevel();
					}
					// Doesn't end with or contain an unclosed quote
					return false;
				}
			}
		}

		// If the sibling is another concatenation, check if it starts/ends with the expected quote
		// or contains an unclosed expected quote followed by acceptable characters
		if ($siblingOperand instanceof Node && $siblingOperand->kind === \ast\AST_BINARY_OP && ($siblingOperand->flags ?? 0) === \ast\flags\BINARY_CONCAT) {
			if ($position === 'left') {
				// Method is on left, need to find the leftmost string in the sibling concat
				if ($expectedQuoteType !== null) {
					return $this->hasFirstQuoteOfType($siblingOperand, $expectedQuoteType);
				}
				return $this->hasFirstQuote($siblingOperand);
			} else {
				// Method is on right, need to find the rightmost string in the sibling concat
				// Also check if the concatenation contains a quote followed by word characters
				if ($expectedQuoteType !== null) {
					if ($this->hasLastQuoteOfType($siblingOperand, $expectedQuoteType)) {
						// Concatenation ends with the expected quote, now check if this is at the top level
						return $checkTopLevel();
					}
					// Check if the concatenation contains an unclosed quote of the expected type followed by word chars
					if ($this->concatHasUnclosedQuoteOfType($siblingOperand, $expectedQuoteType)) {
						// Concatenation contains an unclosed quote, now check if this is at the top level
						return $checkTopLevel();
					}
					// Doesn't end with or contain an unclosed quote of the expected type
					return false;
				}
				if ($this->hasLastQuote($siblingOperand)) {
					// Concatenation ends with a quote, now check if this is at the top level
					return $checkTopLevel();
				}
				// Check if the concatenation contains an unclosed quote followed by word chars
				if ($this->concatHasUnclosedQuote($siblingOperand)) {
					// Concatenation contains an unclosed quote, now check if this is at the top level
					return $checkTopLevel();
				}
				// Doesn't end with or contain an unclosed quote
				return false;
			}
		}

		// If we can't determine, assume it's not properly quoted
		return false;
	}

	/**
	 * Check if a concatenation has a first quote of any type (recursively through nested concatenations)
	 *
	 * @param Node $node The BINARY_OP concatenation node
	 * @return bool True if the concatenation has a first quote
	 */
	private function hasFirstQuote(Node $node): bool
	{
		if (!($node instanceof Node) || $node->kind !== \ast\AST_BINARY_OP || ($node->flags ?? 0) !== \ast\flags\BINARY_CONCAT) {
			return false;
		}

		$left = $node->children['left'] ?? null;
		if ($left instanceof Node && $left->kind === \ast\AST_BINARY_OP && ($left->flags ?? 0) === \ast\flags\BINARY_CONCAT) {
			return $this->hasFirstQuote($left);
		}

		if (is_string($left)) {
			return strpos($left, "'") === 0 || strpos($left, '"') === 0;
		}

		// Check if left is an ENCAPS_LIST
		if ($left instanceof Node && $left->kind === \ast\AST_ENCAPS_LIST) {
			return $this->encapsListHasFirstQuote($left);
		}

		return false;
	}

	/**
	 * Check if a concatenation has a last quote of any type (recursively through nested concatenations)
	 *
	 * @param Node $node The BINARY_OP concatenation node
	 * @return bool True if the concatenation has a last quote
	 */
	private function hasLastQuote(Node $node): bool
	{
		if (!($node instanceof Node) || $node->kind !== \ast\AST_BINARY_OP || ($node->flags ?? 0) !== \ast\flags\BINARY_CONCAT) {
			return false;
		}

		$right = $node->children['right'] ?? null;
		if ($right instanceof Node && $right->kind === \ast\AST_BINARY_OP && ($right->flags ?? 0) === \ast\flags\BINARY_CONCAT) {
			return $this->hasLastQuote($right);
		}

		if (is_string($right)) {
			return substr($right, -1) === "'" || substr($right, -1) === '"';
		}

		// Check if right is an ENCAPS_LIST
		if ($right instanceof Node && $right->kind === \ast\AST_ENCAPS_LIST) {
			return $this->encapsListHasLastQuote($right);
		}

		return false;
	}

	/**
	 * Check if a concatenation contains an unclosed quote followed by word characters, numbers, slashes, dashes, or other safe SQL characters
	 *
	 * @param Node $node The BINARY_OP concatenation node
	 * @return bool True if the concatenation contains an unclosed quote followed by acceptable characters
	 */
	private function concatHasUnclosedQuote(Node $node): bool
	{
		if (!($node instanceof Node) || $node->kind !== \ast\AST_BINARY_OP || ($node->flags ?? 0) !== \ast\flags\BINARY_CONCAT) {
			return false;
		}

		$left = $node->children['left'] ?? null;
		$right = $node->children['right'] ?? null;

		// Check right operand first (it's the rightmost)
		if (is_string($right)) {
			$lastSingleQuotePos = strrpos($right, "'");
			$lastDoubleQuotePos = strrpos($right, '"');

			if ($lastSingleQuotePos !== false && ($lastDoubleQuotePos === false || $lastSingleQuotePos > $lastDoubleQuotePos)) {
				// Last quote is single quote, check what comes after it
				if ($this->quoteFollowedBySafeChars($right, $lastSingleQuotePos)) {
					return true;
				}
			} elseif ($lastDoubleQuotePos !== false) {
				// Last quote is double quote, check what comes after it
				if ($this->quoteFollowedBySafeChars($right, $lastDoubleQuotePos)) {
					return true;
				}
			}
		} elseif ($right instanceof Node && $right->kind === \ast\AST_ENCAPS_LIST) {
			// Check if the ENCAPS_LIST contains an unclosed quote
			if ($this->encapsListHasUnclosedQuote($right)) {
				return true;
			}
		}

		// Check left operand (recursively)
		if ($left instanceof Node && $left->kind === \ast\AST_BINARY_OP && ($left->flags ?? 0) === \ast\flags\BINARY_CONCAT) {
			if ($this->concatHasUnclosedQuote($left)) {
				return true;
			}
		} elseif ($left instanceof Node && $left->kind === \ast\AST_ENCAPS_LIST) {
			// Check if the ENCAPS_LIST contains an unclosed quote
			if ($this->encapsListHasUnclosedQuote($left)) {
				return true;
			}
		}

		// Check left operand if it's a string
		if (is_string($left)) {
			$lastSingleQuotePos = strrpos($left, "'");
			$lastDoubleQuotePos = strrpos($left, '"');

			if ($lastSingleQuotePos !== false && ($lastDoubleQuotePos === false || $lastSingleQuotePos > $lastDoubleQuotePos)) {
				// Last quote is single quote, check what comes after it
				if ($this->quoteFollowedBySafeChars($left, $lastSingleQuotePos)) {
					return true;
				}
			} elseif ($lastDoubleQuotePos !== false) {
				// Last quote is double quote, check what comes after it
				if ($this->quoteFollowedBySafeChars($left, $lastDoubleQuotePos)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if a concatenation has a first quote of the specified type (recursively through nested concatenations)
	 *
	 * @param Node $node The BINARY_OP concatenation node
	 * @param string $quoteType The specific quote type to check for ('\'' or '"')
	 * @return bool True if the concatenation has a first quote of the specified type
	 */
	private function hasFirstQuoteOfType(Node $node, string $quoteType): bool
	{
		if (!($node instanceof Node) || $node->kind !== \ast\AST_BINARY_OP || ($node->flags ?? 0) !== \ast\flags\BINARY_CONCAT) {
			return false;
		}

		$left = $node->children['left'] ?? null;
		if ($left instanceof Node && $left->kind === \ast\AST_BINARY_OP && ($left->flags ?? 0) === \ast\flags\BINARY_CONCAT) {
			return $this->hasFirstQuoteOfType($left, $quoteType);
		}

		if (is_string($left)) {
			return strpos($left, $quoteType) === 0;
		}

		// Check if left is an ENCAPS_LIST
		if ($left instanceof Node && $left->kind === \ast\AST_ENCAPS_LIST) {
			return $this->encapsListHasFirstQuoteOfType($left, $quoteType);
		}

		return false;
	}

	/**
	 * Check if a concatenation has a last quote of the specified type (recursively through nested concatenations)
	 *
	 * @param Node $node The BINARY_OP concatenation node
	 * @param string $quoteType The specific quote type to check for ('\'' or '"')
	 * @return bool True if the concatenation has a last quote of the specified type
	 */
	private function hasLastQuoteOfType(Node $node, string $quoteType): bool
	{
		if (!($node instanceof Node) || $node->kind !== \ast\AST_BINARY_OP || ($node->flags ?? 0) !== \ast\flags\BINARY_CONCAT) {
			return false;
		}

		$right = $node->children['right'] ?? null;
		if ($right instanceof Node && $right->kind === \ast\AST_BINARY_OP && ($right->flags ?? 0) === \ast\flags\BINARY_CONCAT) {
			return $this->hasLastQuoteOfType($right, $quoteType);
		}

		if (is_string($right)) {
			return substr($right, -1) === $quoteType;
		}

		// Check if right is an ENCAPS_LIST
		if ($right instanceof Node && $right->kind === \ast\AST_ENCAPS_LIST) {
			return $this->encapsListHasLastQuoteOfType($right, $quoteType);
		}

		return false;
	}

	/**
	 * Check if a concatenation contains an unclosed quote of a specific type followed by word characters, numbers, slashes, dashes, or other safe SQL characters
	 *
	 * @param Node $node The BINARY_OP concatenation node
	 * @param string $quoteType The specific quote type to check for ('\'' or '"')
	 * @return bool True if the concatenation contains an unclosed quote of the specified type followed by acceptable characters
	 */
	private function concatHasUnclosedQuoteOfType(Node $node, string $quoteType): bool
	{
		if (!($node instanceof Node) || $node->kind !== \ast\AST_BINARY_OP || ($node->flags ?? 0) !== \ast\flags\BINARY_CONCAT) {
			return false;
		}

		$left = $node->children['left'] ?? null;
		$right = $node->children['right'] ?? null;

		// Check right operand first (it's the rightmost)
		if (is_string($right)) {
			$quotePos = strrpos($right, $quoteType);
			if ($quotePos !== false && $this->quoteFollowedBySafeChars($right, $quotePos)) {
				return true;
			}
		} elseif ($right instanceof Node && $right->kind === \ast\AST_ENCAPS_LIST) {
			// Check if the ENCAPS_LIST contains an unclosed quote of the expected type
			if ($this->encapsListHasUnclosedQuoteOfType($right, $quoteType)) {
				return true;
			}
		}

		// Check left operand (recursively)
		if ($left instanceof Node && $left->kind === \ast\AST_BINARY_OP && ($left->flags ?? 0) === \ast\flags\BINARY_CONCAT) {
			if ($this->concatHasUnclosedQuoteOfType($left, $quoteType)) {
				return true;
			}
		} elseif ($left instanceof Node && $left->kind === \ast\AST_ENCAPS_LIST) {
			// Check if the ENCAPS_LIST contains an unclosed quote of the expected type
			if ($this->encapsListHasUnclosedQuoteOfType($left, $quoteType)) {
				return true;
			}
		}

		// Check left operand if it's a string
		if (is_string($left)) {
			$quotePos = strrpos($left, $quoteType);
			if ($quotePos !== false && $this->quoteFollowedBySafeChars($left, $quotePos)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if an ENCAPS_LIST starts with a specific quote type
	 *
	 * @param Node $node The ENCAPS_LIST node
	 * @param string $quoteType The specific quote type to check for ('\'' or '"')
	 * @return bool True if the ENCAPS_LIST starts with the specified quote
	 */
	private function encapsListHasFirstQuoteOfType(Node $node, string $quoteType): bool
	{
		if ($node->kind !== \ast\AST_ENCAPS_LIST) {
			return false;
		}

		// Get the first child
		foreach ($node->children as $child) {
			if (is_string($child)) {
				// Found a string part, check if it starts with the quote
				return strpos($child, $quoteType) === 0;
			}
			// If it's a variable or other node, skip it and continue
			// We only care about the first string part
		}

		return false;
	}

	/**
	 * Check if an ENCAPS_LIST ends with a specific quote type
	 *
	 * @param Node $node The ENCAPS_LIST node
	 * @param string $quoteType The specific quote type to check for ('\'' or '"')
	 * @return bool True if the ENCAPS_LIST ends with the specified quote
	 */
	private function encapsListHasLastQuoteOfType(Node $node, string $quoteType): bool
	{
		if ($node->kind !== \ast\AST_ENCAPS_LIST) {
			return false;
		}

		// Get the children in order and find the last string part
		$lastString = null;
		foreach ($node->children as $child) {
			if (is_string($child)) {
				$lastString = $child;
			}
			// If it's a variable or other node, skip it
		}

		if ($lastString !== null) {
			return substr($lastString, -1) === $quoteType;
		}

		return false;
	}

	/**
	 * Check if an ENCAPS_LIST contains an unclosed quote of a specific type followed by acceptable characters
	 *
	 * @param Node $node The ENCAPS_LIST node
	 * @param string $quoteType The specific quote type to check for ('\'' or '"')
	 * @return bool True if the ENCAPS_LIST contains an unclosed quote of the specified type followed by acceptable characters
	 */
	private function encapsListHasUnclosedQuoteOfType(Node $node, string $quoteType): bool
	{
		if ($node->kind !== \ast\AST_ENCAPS_LIST) {
			return false;
		}

		// Get the last string that contains the quote type
		$lastStringWithQuote = null;
		foreach ($node->children as $child) {
			if (is_string($child)) {
				if (strpos($child, $quoteType) !== false) {
					$lastStringWithQuote = $child;
				}
			}
		}

		if ($lastStringWithQuote !== null) {
			// Find the last occurrence of the quote type
			$quotePos = strrpos($lastStringWithQuote, $quoteType);
			if ($quotePos !== false && $this->quoteFollowedBySafeChars($lastStringWithQuote, $quotePos)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if an ENCAPS_LIST starts with any quote
	 *
	 * @param Node $node The ENCAPS_LIST node
	 * @return bool True if the ENCAPS_LIST starts with a quote
	 */
	private function encapsListHasFirstQuote(Node $node): bool
	{
		if ($node->kind !== \ast\AST_ENCAPS_LIST) {
			return false;
		}

		foreach ($node->children as $child) {
			if (is_string($child)) {
				return strpos($child, "'") === 0 || strpos($child, '"') === 0;
			}
		}

		return false;
	}

	/**
	 * Check if an ENCAPS_LIST ends with any quote
	 *
	 * @param Node $node The ENCAPS_LIST node
	 * @return bool True if the ENCAPS_LIST ends with a quote
	 */
	private function encapsListHasLastQuote(Node $node): bool
	{
		if ($node->kind !== \ast\AST_ENCAPS_LIST) {
			return false;
		}

		$lastString = null;
		foreach ($node->children as $child) {
			if (is_string($child)) {
				$lastString = $child;
			}
		}

		if ($lastString !== null) {
			return substr($lastString, -1) === "'" || substr($lastString, -1) === '"';
		}

		return false;
	}

	/**
	 * Check if an ENCAPS_LIST contains an unclosed quote of any type followed by acceptable characters
	 *
	 * @param Node $node The ENCAPS_LIST node
	 * @return bool True if the ENCAPS_LIST contains an unclosed quote followed by acceptable characters
	 */
	private function encapsListHasUnclosedQuote(Node $node): bool
	{
		if ($node->kind !== \ast\AST_ENCAPS_LIST) {
			return false;
		}

		// Check for single quotes
		$lastSingleQuoteString = null;
		$lastDoubleQuoteString = null;
		foreach ($node->children as $child) {
			if (is_string($child)) {
				if (strpos($child, "'") !== false) {
					$lastSingleQuoteString = $child;
				}
				if (strpos($child, '"') !== false) {
					$lastDoubleQuoteString = $child;
				}
			}
		}
		// Check single quote
		if ($lastSingleQuoteString !== null) {
			$lastSingleQuotePos = strrpos($lastSingleQuoteString, "'");
			if ($lastSingleQuotePos !== false && $this->quoteFollowedBySafeChars($lastSingleQuoteString, $lastSingleQuotePos)) {
				return true;
			}
		}

		// Check double quote
		if ($lastDoubleQuoteString !== null) {
			$lastDoubleQuotePos = strrpos($lastDoubleQuoteString, '"');
			if ($lastDoubleQuotePos !== false && $this->quoteFollowedBySafeChars($lastDoubleQuoteString, $lastDoubleQuotePos)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a string representation of a method or function call for error messages
	 *
	 * @param Node $node The method or function call node
	 * @return string A string representation of the call
	 */
	private function getNodeVarForMethodCall(Node $node): string
	{
		if ($node->kind === \ast\AST_METHOD_CALL) {
			$objNode = $node->children['expr'] ?? null;
			$methodName = $node->children['method'] ?? '?:';
			$objStr = $this->getNodeVar($objNode) ?? '?';
			$argsStr = $this->getMethodCallArgs($node);
			return "{$objStr}->{$methodName}({$argsStr})";
		} elseif ($node->kind === \ast\AST_CALL) {
			$method = $node->children['expr'] ?? null;
			if ($method instanceof Node && $method->kind === \ast\AST_NAME) {
				$methodName = $method->children['name'] ?? '?:';
				$argsStr = $this->getMethodCallArgs($node);
				return $methodName . "({$argsStr})";
			}
			return '?()';
		}
		return 'unknown()';
	}

	/**
	 * Get the arguments of a method or function call as a string
	 *
	 * @param Node $node The METHOD_CALL or CALL node
	 * @return string The arguments string
	 */
	private function getMethodCallArgs(Node $node): string
	{
		$argsNode = $node->children['args'] ?? null;
		if ($argsNode === null) {
			return '';
		}

		if (!($argsNode instanceof Node)) {
			return '';
		}

		// AST_ARG_LIST contains children as an array of nodes
		if ($argsNode->kind === \ast\AST_ARG_LIST) {
			$args = [];
			foreach ($argsNode->children as $child) {
				if ($child instanceof Node) {
					$args[] = $this->reconstructArg($child);
				} else {
					$args[] = var_export($child, true);
				}
			}
			return implode(', ', $args);
		}

		// If it's a single argument (not a list), just reconstruct it
		return $this->reconstructArg($argsNode);
	}

	/**
	 * Reconstruct a single argument from AST node
	 *
	 * @param Node|mixed $argNode The argument node
	 * @return string Reconstructed argument string
	 */
	private function reconstructArg($argNode): string
	{
		if (is_string($argNode) || is_int($argNode) || is_float($argNode) || is_bool($argNode)) {
			return var_export($argNode, true);
		}

		if (!($argNode instanceof Node)) {
			return '';
		}

		switch ($argNode->kind) {
			case \ast\AST_VAR:
				$name = $argNode->children['name'] ?? '?';
				return '\$' . $name;

			case \ast\AST_PROP:
				$expr = $argNode->children['expr'] ?? null;
				$prop = $argNode->children['prop'] ?? '?';
				return $this->reconstructArg($expr) . '->' . $prop;

			case \ast\AST_DIM:
				$expr = $argNode->children['expr'] ?? null;
				$dim = $argNode->children['dim'] ?? null;
				$exprStr = $this->reconstructArg($expr);
				if ($dim instanceof Node) {
					$dimStr = $this->reconstructArg($dim);
				} else {
					$dimStr = is_string($dim) ? var_export($dim, true) : (is_int($dim) ? $dim : '?');
				}
				return "{$exprStr}[{$dimStr}]";

			case \ast\AST_CONST:
			case \ast\AST_NAME:
				$name = $argNode->children['name'] ?? '?';
				return is_string($name) ? $name : (is_scalar($name) ? (string) $name : '?');

			case \ast\AST_CLASS_CONST:
				$class = $argNode->children['class'] ?? null;
				$name = $argNode->children['name'] ?? '?';
				$classStr = $class instanceof Node ? $this->reconstructArg($class) : (is_string($class) ? $class : '?');
				$nameStr = is_string($name) ? $name : (is_scalar($name) ? (string) $name : '?');
				return "{$classStr}::{$nameStr}";

			case \ast\AST_CALL:
			case \ast\AST_METHOD_CALL:
				return $this->getNodeVarForMethodCall($argNode);

			case \ast\AST_CAST:
				$expr = $argNode->children['expr'] ?? null;
				$exprStr = $this->reconstructArg($expr);
				$flags = $argNode->flags ?? 0;
				if ($flags === \ast\flags\TYPE_STRING) {
					return "(string){$exprStr}";
				} elseif ($flags === \ast\flags\TYPE_LONG) {
					return "(int){$exprStr}";
				} elseif ($flags === \ast\flags\TYPE_BOOL) {
					return "(bool){$exprStr}";
				} elseif ($flags === \ast\flags\TYPE_DOUBLE) {
					return "(float){$exprStr}";
				} elseif ($flags === \ast\flags\TYPE_ARRAY) {
					return "(array){$exprStr}";
				} elseif ($flags === \ast\flags\TYPE_OBJECT) {
					return "(object){$exprStr}";
				}
				return "({$exprStr})";

			case \ast\AST_BINARY_OP:
				// For concatenation, reconstruct both sides
				$left = $argNode->children['left'] ?? null;
				$right = $argNode->children['right'] ?? null;
				$leftStr = $this->reconstructArg($left);
				$rightStr = $this->reconstructArg($right);
				$flags = $argNode->flags ?? 0;
				if ($flags === \ast\flags\BINARY_CONCAT) {
					return $leftStr . '.' . $rightStr;
				}
				return $leftStr . $rightStr;

			default:
				// For other node types, try to get a string representation
				return '...';
		}
	}

	/**
	 * Check if a variable node is protected by a safe method call.
	 *
	 * @param Node $varNode The variable node to check
	 * @return bool True if the variable is protected
	 */
	private function isProtected(Node $varNode): bool
	{
		if ($varNode->kind !== \ast\AST_PROP) {
			return false;
		}

		$expr = $varNode->children['expr'] ?? null;
		$propName = $varNode->children['prop'] ?? null;
		if ($expr instanceof Node && $expr->kind === \ast\AST_VAR) {
			$varName = $expr->children['name'] ?? null;
			if (!is_string($varName) || !is_string($propName)) {
				$this->debug("Non-string name/prop in isProtected");
				return false;
			}
			if ($this->isDoliDB($expr, ['this', 'db']) && in_array($propName, self::SAFE_METHODS, true)) {
				$this->debug("TRUE in isProtected: obj={$varName}, prop={$propName}");
				return true;
			}
			if (in_array($propName, self::TRUSTED_PROPERTIES, true)) {
				$this->debug("TRUE (trusted) in isProtected: prop={$propName}");
				return true;
			}
			if ($varName === 'hookmanager' && $propName === 'resPrint') {
				return true;
			}
			if ($propName === 'table_element') {
				return true;
			}
		}

		// Nested property access: $this->db->prop or $obj->db->prop
		if ($expr instanceof Node && $expr->kind === \ast\AST_PROP) {
			$innerProp = $expr->children['prop'] ?? null;
			$innerExpr = $expr->children['expr'] ?? null;

			if (is_string($innerProp) && $innerProp === 'db' && $innerExpr instanceof Node) {
				if ($innerExpr->kind === \ast\AST_VAR) {
					if ($this->isDoliDB($innerExpr, ['this', 'db'])) {
						if (in_array($propName, self::SAFE_METHODS, true)) {
							$this->debug("TRUE in isProtected: nested db->{$propName}");
							return true;
						}
					}
				}
			}
		}

		$this->debug("FALSE in isProtected");
		return false;
	}

	/**
	 * Check a variable usage and emit an issue if it's unsafe.
	 *
	 * @param Node $varNode The variable node to check
	 * @param Node $contextNode The context node for error reporting
	 * @return void
	 */
	private function checkVariable(Node $varNode, Node $contextNode): void
	{
		if ($this->isSqlVariable($varNode)) {
			// Already (checked) sql variable
			return;
		}

		if ($varNode->kind === \ast\AST_VAR) {
			$varName = $varNode->children['name'] ?? null;
			if (!is_string($varName)) {
				return;
			}
			$this->debug("checkVariable: VAR: name={$varName}");
			if (
				$this->isSqlVariable($varNode)
				|| strpos($varName, 'SqlList') !== false  // Not checking sanitization
				|| $this->isProtected($varNode)
				|| in_array($varName, self::SAFE_SQL_VARIABLES, true)
			) {
				$this->debug("VAR {$varName} is SAFE");
				return;
			}
			// @phpstan-ignore-next-line method.notFound
			$this->emitPluginIssue(
				$this->code_base, // @phpstan-ignore property.notFound
				$this->context, // @phpstan-ignore property.notFound
				'SqlInjection',
				"Variable \${$varName} used in SQL without protection.",
				[]
			);
		} elseif ($varNode->kind === \ast\AST_PROP) {
			$expr = $varNode->children['expr'] ?? null;
			if ($expr instanceof Node && $expr->kind === \ast\AST_VAR) {
				$varName = $this->getNodeVar($varNode);
				$this->debug("checkVariable: PROP: {$varName}");
				if ($this->isProtected($varNode)) {
					$this->debug("PROP {$varName} is SAFE");
					return;
				}
				// @phpstan-ignore-next-line method.notFound
				$this->emitPluginIssue(
					$this->code_base, // @phpstan-ignore property.notFound
					$this->context, // @phpstan-ignore property.notFound
					'SqlInjection',
					"Property {$varName} used in SQL without protection.",
					[]
				);
			}
		} elseif ($varNode->kind === \ast\AST_DIM) {
			// $this->debug("checkVariable: DIM: node=".var_export($varNode,true));
			$varName = $this->getNodeVar($varNode);
			$this->emitPluginIssue(
				$this->code_base, // @phpstan-ignore property.notFound
				$this->context, // @phpstan-ignore property.notFound
				'SqlInjection',
				"Property {$varName} used in SQL without protection.",
				[]
			);
		}
	}

	/**
	 * Check a that the method call returns a safe value
	 *
	 * The method call is safe if the:
	 *  - Variable or property has an accepted name, or
	 *    it is of type 'DoliDB'
	 *  - and, the method is on the safe list.
	 *
	 * @param Node $node The method call node to check
	 * @return bool
	 */
	private function isSafeMethodCall(Node $node): bool
	{
		if ($node->kind !== \ast\AST_CALL && $node->kind !== \ast\AST_METHOD_CALL) {
			$this->debug("Not CALL or METHOD_CALL");
			return false;
		}

		$methodName = null;
		$objNode = null;

		if ($node->kind === \ast\AST_METHOD_CALL) {
			// For METHOD_CALL: $obj->method()
			$methodName = $node->children['method'] ?? null;
			$objNode = $node->children['expr'] ?? null;
			$this->debug("METHOD_CALL: methodName=" . var_export($methodName, true) . ", objNode kind=" . ($objNode instanceof Node ? $objNode->kind : 'null'));
		} else {
			// For CALL: method()
			$method = $node->children['expr'] ?? null;
			if ($method instanceof Node && $method->kind === \ast\AST_NAME) {
				$methodName = $method->children['name'] ?? null;
			}
			$this->debug("CALL: methodName=" . var_export($methodName, true));
		}

		if (!is_string($methodName)) {
			$this->debug("methodName not string");
			return false;
		}

		if ($methodName === 'array_map') {
			// Handle array_map
			return $this->isArrayMapSafe($node);
		}

		// Check if method is in safe list
		if (!in_array($methodName, self::SAFE_METHODS, true)) {
			$this->debug("Method '{$methodName}' not in safe list");
			return false;
		}

		// For function calls (no object), it's always safe (TODO: maybe exclude function calls...)
		if ($objNode === null) {
			$this->debug("Function call '{$methodName}' is safe");
			return true;
		}

		// For method calls, check the object
		if ($this->isDoliDB($objNode, ['this', 'db', 'staticMemo', 'memoStatic',])) {
			$this->debug("Method '{$methodName}' on accepted object is safe");
			return true;
		}

		$this->debug("Method '{$methodName}' on unaccepted object");
		return false;
	}

	/**
	 * Check if an array_map call has a safe callback
	 *
	 * @param Node $node The array_map call node
	 * @return bool True if the callback is safe
	 */
	private function isArrayMapSafe(Node $node): bool
	{
		$argsNode = $node->children['args'] ?? null;
		if (!$argsNode instanceof Node || count($argsNode->children) < 2) {
			return false;
		}

		// First argument is the callback
		$callback = $argsNode->children[0] ?? null;
		// $this->debug("isArrayMapSafe: ARRAY callback=" . var_export($callback, true));

		// Case 1: callback is a string literal (e.g., 'escape')
		if (is_string($callback)) {
			if (!in_array($callback, self::SAFE_METHODS, true)) {
				$this->debug("Method '{$methodName}' not in safe list");
				return false;
			} else {
				// TODO: restrict to functions, not "SAFE_METHODS"
				return true;
			}
		}


		if (!$callback instanceof Node) {
			return false;
		}



		// Case 2: callback is array($obj, 'method') format
		if ($callback->kind === \ast\AST_ARRAY) {
			$elems = $callback->children ?? [];
			if (count($elems) !== 2) {
				return false;
			}

			$obj = $elems[0]->children['value'] ?? null;
			$method = $elems[1] ?? null;

			// $this->debug("isArrayMapSafe: ARRAY obj=" . var_export($obj, true). ",method=".var_export($method,true));
			if (!$obj instanceof Node || !$method instanceof Node) {
				return false;
			}

			if ($method->kind === \ast\AST_ARRAY_ELEM) {
				$methodName = $method->children['value'] ?? null;
				if (!is_string($methodName) || !in_array($methodName, self::SAFE_METHODS, true)) {
					return false;
				}
				// Method is ok, if DoliDb
				return $this->isDoliDB($obj, ['this', 'db']);
			}
		}

		// Case 3: callback is a closure - can't verify, assume unsafe
		return false;
	}
}

return new SqlInjectionPlugin();
