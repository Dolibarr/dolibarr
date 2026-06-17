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
		'quote',   // Save, based on fields definition
		// DoliDB methods, ...
		'escape',    // Safe, goal is to escape
		'sanitize',  // Safe, goal is to cleanup
		'prefix',  // Not fully safe - would be better to define prefix($tablebasename) and protect
		'plimit',  // Safe, limits are casted to int
		'idate',  // Safe uses dol_print_date
		'jdate',  // Safe uses dol_mktime
		'order',  // Sanitizes arguments
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
		'sanititzekey', // Used with array_map
		'forgeSQLFromUniversalSearchCriteria', // Returns sql
		'addMailingEventTypeSQL', // Returns sql
		'transformToSQL', // Returns sql (advtargetemailing)
		'count', // Returns int
		'intval', // Returns int
		'floatval', // Returns float
		'strlen', // Returns int
		'strpos', // Returns int
		'dol_strlen', // Returns int
		'dol_sanitizeFileName', // Supposed ok for sql (?)
		'date_format', // Returns formatted string
		'dol_print_date', // Returns formatted string
		'price2num', // Returns formatted number
		'GETPOSTINT', // Returns int
		'GETPOSTFLOAT', //Returns float
		'getDolUserInt', //Returns float
		'getEntity', // Returns entity
		'dol_escape_json',
		'dol_hash', // Returns string
		'dolSqlDateFilter', // Partially safe datefield not checked/escaped
		'natural_search',
		'getSqlCalEvents', // Returns sql
		'setEntity', // Returns int
		// 'get_exdir',  // Not safe if directory could look like SQL (forged, e.g. sql as invoice ref)
		'getSQLFactLines', // intracommreport.class.php
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
	 * @return void
	 */
	private function checkExpressionForUnsafeVariables($expr, Node $contextNode): void
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
				if ($left instanceof Node) {
					$this->checkExpressionForUnsafeVariables($left, $contextNode);
				}
				if ($right instanceof Node) {
					$this->checkExpressionForUnsafeVariables($right, $contextNode);
				}
				break;

			case \ast\AST_CONDITIONAL:
				$trueExpr = $expr->children['true'] ?? null;
				$falseExpr = $expr->children['false'] ?? null;
				$this->debug("checkExpr: CONDITIONAL node");
				if ($trueExpr instanceof Node) {
					$this->checkExpressionForUnsafeVariables($trueExpr, $contextNode);
				}
				if ($falseExpr instanceof Node) {
					$this->checkExpressionForUnsafeVariables($falseExpr, $contextNode);
				}
				// Intentionally skip condition - it's not part of SQL
				break;

			case \ast\AST_CALL:
			case \ast\AST_METHOD_CALL:
				$method = $expr->children['expr'] ?? null;
				$methodKind = $method instanceof Node ? $method->kind : 'null';
				$methodName = $method->children['name'] ?? null;
				$this->debug("checkExpr: CALL/METHOD_CALL node, methodKind={$methodKind}, methodName=" . var_export($methodName, true));
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
									$this->checkExpressionForUnsafeVariables($arg, $contextNode);
								}
								// First argument of this function does not need to be safe
								continue;
							}
							$value = $arg; //$arg->children['value'] ?? $arg;
							if ($value instanceof Node) {
								$this->checkExpressionForUnsafeVariables($value, $contextNode);
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
					$this->checkExpressionForUnsafeVariables($inner, $contextNode);
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
						$this->checkExpressionForUnsafeVariables($child, $contextNode);
					}
				}
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
