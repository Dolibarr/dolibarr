<?php
/* Copyright (C) 2026 Frédéric France <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/class/dolsafeeval.class.php
 *	\ingroup    core
 *	\brief      Evaluator of Dolibarr conditions/expressions that never calls PHP eval().
 *
 *	How it works: the string is split into tokens (lexer), turned into an AST (Pratt parser),
 *	then the AST is walked by an interpreter. The string is never handed to the PHP engine,
 *	so there is no way to "escape" from the grammar. Anything that is not explicitly
 *	supported is rejected (default deny), and any error makes the evaluation fail closed.
 *
 *	Supported grammar (subset of PHP expressions):
 *	- literals: integers, floats, 'single' and "double" quoted strings (no interpolation), true, false, null, [a, b] and array(a, b)
 *	- variables from the context given by the caller ($conf, $user, $object, ...), read only
 *	- property read ($a->b, $a?->b), array read ($a['key']), method call on a allowlist (class + method)
 *	- calls to allowed functions, isset() and empty()
 *	- operators: ! - + * / % . < <= > >= == != <> === !== && || and or ?? ?: ? : and casts (int) (float) (string) (bool)
 *
 *	Not supported (and rejected): assignments, ++/--, new, closures, include/require, backticks, variable variables,
 *	static access (A::B), constants, functions that are not in the allowlist, string interpolation, several statements.
 */

/**
 * Exception thrown when an expression is rejected or can not be evaluated.
 */
class DolSafeEvalException extends Exception
{
}

/**
 * Class to evaluate a condition or a simple expression without eval().
 */
class DolSafeEval
{
	// Limits (protect against huge or deeply nested expressions)
	const MAX_LENGTH = 2000;	// Max length of the expression, in bytes
	const MAX_TOKENS = 600;
	const MAX_NODES = 400;
	const MAX_DEPTH = 40;
	const MAX_ARGS = 8;			// Max number of arguments of a function or method call
	const MAX_STEPS = 2000;		// Max number of nodes visited during one evaluation
	const CACHE_SIZE = 256;		// Number of parsed expressions kept in memory

	/**
	 * Names (property, key, constant) that can contain a secret. Reading them is refused.
	 * The words are searched anywhere in the name (SECUREKEY, EXPORTKEY, ACCESSTOKEN... are also refused). It is voluntarily wide: a name refused by mistake is better than a secret read.
	 */
	const SENSITIVE_NAME_REGEX = '/(pass|pwd|secret|token|api_?key|private_?key|salt|unique_id|crypted|hmac|key|(^|_)pw(_|$))/i';

	/**
	 * Variables of the caller scope that are given to the expression. $db is voluntarily not in this list.
	 *
	 * @var string[]
	 */
	private static $contextVariables = array('conf', 'langs', 'user', 'mysoc', 'object', 'objectoffield', 'website', 'websitepage', 'action', 'mainmenu', 'leftmenu');

	/**
	 * @var array<string,array{0:int,1:string}>	Binary operators: operator => (binding power, 'l' left assoc or 'n' non associative)
	 */
	private static $binops = array(
		'or' => array(10, 'l'),
		'and' => array(20, 'l'),
		'||' => array(50, 'l'),
		'&&' => array(60, 'l'),
		'==' => array(70, 'n'), '!=' => array(70, 'n'), '===' => array(70, 'n'), '!==' => array(70, 'n'),
		'<' => array(80, 'n'), '<=' => array(80, 'n'), '>' => array(80, 'n'), '>=' => array(80, 'n'),
		'.' => array(90, 'l'),
		'+' => array(100, 'l'), '-' => array(100, 'l'),
		'*' => array(110, 'l'), '/' => array(110, 'l'), '%' => array(110, 'l'),
	);

	/**
	 * @var array<string,string>|null	Functions that can be called: lower case name => real name
	 */
	private static $allowedFunctions = null;

	/**
	 * @var array<string,array<string,bool>>	Methods that can be called: class => (lower case method name => true)
	 */
	private static $allowedMethods = array(
		'User' => array('hasright' => true, 'isadmin' => true, 'isexternaluser' => true),
	);

	/**
	 * @var array<string,bool>|null		If not null, names (lower case) of the functions AND methods that are allowed, whatever the class.
	 * 									This is the value of $dolibarr_main_restrict_eval_methods when it is set, and it replaces the default allowlists.
	 */
	private $restrict = null;

	/**
	 * @var array<string,bool>	Property names that can never be read (lower case)
	 */
	private static $forbiddenProperties = array('db' => true);

	/**
	 * @var array<string,array<int,mixed>>	Cache of parsed expressions (md5 => AST)
	 */
	private static $cache = array();

	/**
	 * @var array<int,array<int,mixed>>	Tokens of the expression being parsed
	 */
	private $tokens = array();

	/**
	 * @var int	Position in $tokens
	 */
	private $pos = 0;

	/**
	 * @var int	Number of AST nodes created for the expression being parsed
	 */
	private $nodeCount = 0;


	/**
	 * Body of dol_eval() when the safe evaluator is enabled. Build the context from the global variables
	 * (never $db) and evaluate. The list of allowed functions and methods is the one of $dolibarr_main_restrict_eval_methods.
	 * A rejected or failing expression is logged and, like dol_eval(), returns a string starting with
	 * 'Bad string syntax to evaluate' (that verifCond() understands).
	 *
	 * @param	string		$s					String to evaluate
	 * @param	int			$hideerrors			Kept for compatibility. Rejections are always logged.
	 * @param	string		$onlysimplestring	Kept for compatibility. The grammar is always the strict one.
	 * @return	mixed							Result of the expression, or a string 'Bad string syntax to evaluate...' if rejected
	 */
	public static function dolEval($s, $hideerrors = 1, $onlysimplestring = '1')
	{
		global $dolibarr_main_restrict_eval_methods;

		$names = self::$contextVariables;
		if (function_exists('getDolGlobalString') && getDolGlobalString('MAIN_ALLOW_OLD_VAR_OBJ_IN_DOL_EVAL')) {
			$names[] = 'obj';
		}
		$vars = array();
		foreach ($names as $name) {
			$vars[$name] = isset($GLOBALS[$name]) ? $GLOBALS[$name] : null;
		}

		$restrict = null;
		if (!empty($dolibarr_main_restrict_eval_methods)) {
			$restrict = explode(',', str_replace(' ', '', $dolibarr_main_restrict_eval_methods));
		}

		if (!is_scalar($s)) {
			return 'Bad call of dol_eval. First parameter must be a string, found '.var_export($s, true);
		}

		$evaluator = new self($restrict);
		try {
			return $evaluator->evaluate((string) $s, $vars);
		} catch (DolSafeEvalException $e) {
			if (function_exists('dol_syslog')) {
				dol_syslog('DolSafeEval rejected "'.substr((string) $s, 0, 200).'": '.$e->getMessage(), LOG_WARNING);
			}
			return 'Bad string syntax to evaluate. '.$e->getMessage().' in '.$s;
		}
	}

	/**
	 * Constructor
	 *
	 * @param	string[]|null	$restrict	Names of functions and methods that are allowed (value of $dolibarr_main_restrict_eval_methods
	 * 										as an array), or null to use the default allowlists.
	 */
	public function __construct($restrict = null)
	{
		if (is_array($restrict)) {
			$this->restrict = array();
			foreach ($restrict as $name) {
				if ($name !== '') {
					$this->restrict[strtolower($name)] = true;
				}
			}
		}
	}

	/**
	 * Add a function to the allowlist. It must be a function without side effects.
	 *
	 * @param	string	$name	Function name
	 * @return	void
	 */
	public static function allowFunction($name)
	{
		self::allowedFunctions();
		self::$allowedFunctions[strtolower($name)] = $name;
	}

	/**
	 * Add a method to the allowlist. It must be a method without side effects.
	 *
	 * @param	string	$class	Class name (the object must be an instance of it)
	 * @param	string	$method	Method name
	 * @return	void
	 */
	public static function allowMethod($class, $method)
	{
		self::$allowedMethods[$class][strtolower($method)] = true;
	}

	/**
	 * Evaluate an expression.
	 *
	 * @param	string					$expression	Expression
	 * @param	array<string,mixed>		$vars		Variables available in the expression ('conf' => $conf for $conf)
	 * @return	mixed								Result of the expression
	 * @throws	DolSafeEvalException				If the expression is rejected or fails
	 */
	public function evaluate($expression, array $vars = array())
	{
		set_error_handler(array(self::class, 'errorHandler'));
		try {
			$ast = $this->compile($expression);
			$ctx = array('vars' => $vars, 'steps' => 0);
			return $this->evalNode($ast, $ctx);
		} catch (DolSafeEvalException $e) {
			throw $e;
		} catch (Throwable $e) {
			throw new DolSafeEvalException('Exception during evaluation: '.$e->getMessage(), 0, $e);
		} finally {
			restore_error_handler();
		}
	}

	/**
	 * Error handler used during the evaluation: a warning or a notice makes the evaluation fail.
	 *
	 * @param	int		$errno		Error level
	 * @param	string	$errstr		Error message
	 * @return	bool
	 * @throws	ErrorException
	 */
	public static function errorHandler($errno, $errstr)
	{
		if (!(error_reporting() & $errno)) {
			return false;	// Error silenced with @
		}
		if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
			return true;	// Deprecations are not a reason to fail
		}
		throw new ErrorException($errstr, 0, $errno);
	}

	/**
	 * Return the allowlist of functions.
	 *
	 * @return	array<string,string>	lower case name => real name
	 */
	private static function allowedFunctions()
	{
		if (self::$allowedFunctions === null) {
			// Same functions than the default value of $dolibarr_main_restrict_eval_methods (the methods are in $allowedMethods)
			$names = array(
				'getDolGlobalString', 'getDolGlobalInt', 'getDolCurrency', 'getDolEntity', 'getDolDBType', 'isModEnabled', 'isStringVarMatching',
				'abs', 'min', 'max', 'round', 'dol_now', 'preg_match',
				// Pure mathematical functions and tests of type
				'floor', 'ceil', 'intdiv', 'fmod', 'sqrt', 'pow', 'exp', 'log', 'log10', 'sin', 'cos', 'tan', 'pi',
				'is_numeric', 'is_nan', 'is_finite', 'intval', 'floatval',
			);
			self::$allowedFunctions = array();
			foreach ($names as $name) {
				self::$allowedFunctions[strtolower($name)] = $name;
			}
		}
		return self::$allowedFunctions;
	}


	// ------------------------------------------------------------------ Lexer

	/**
	 * Split the expression into tokens. Each token is array(type, value, offset)
	 * with type in: num, str, var, id, op, cast, eof.
	 *
	 * @param	string	$src	Expression
	 * @return	array<int,array<int,mixed>>
	 * @throws	DolSafeEvalException
	 */
	private function tokenize($src)
	{
		$len = strlen($src);
		if ($len > self::MAX_LENGTH) {
			throw new DolSafeEvalException('Expression too long');
		}
		if (!preg_match('//u', $src) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $src)) {
			throw new DolSafeEvalException('Invalid character in expression');
		}

		$tokens = array();
		$pos = 0;
		while ($pos < $len) {
			if (preg_match('/\G\s+/', $src, $m, 0, $pos)) {
				$pos += strlen($m[0]);
				continue;
			}
			if (count($tokens) >= self::MAX_TOKENS) {
				throw new DolSafeEvalException('Expression too long (too many tokens)');
			}
			$start = $pos;

			if (preg_match('/\G\(\s*(int|integer|float|double|string|bool|boolean)\s*\)/i', $src, $m, 0, $pos)) {
				$tokens[] = array('cast', strtolower($m[1]), $start);
			} elseif (preg_match('/\G(?:\d+\.\d+|\d+)(?:[eE][+-]?\d+)?/', $src, $m, 0, $pos)) {
				$tokens[] = array('num', ((ctype_digit($m[0]) && (float) $m[0] < PHP_INT_MAX) ? (int) $m[0] : (float) $m[0]), $start);
			} elseif (preg_match('/\G\'((?:[^\'\\\\]|\\\\[\'\\\\])*)\'/', $src, $m, 0, $pos)) {
				$tokens[] = array('str', strtr($m[1], array('\\\'' => '\'', '\\\\' => '\\')), $start);
			} elseif (preg_match('/\G"((?:[^"\\\\$]|\\\\["\\\\])*)"/', $src, $m, 0, $pos)) {
				// No $ in double quoted strings (no interpolation), only \" and \\ are escape sequences
				$tokens[] = array('str', strtr($m[1], array('\\"' => '"', '\\\\' => '\\')), $start);
			} elseif (preg_match('/\G\$([A-Za-z_][A-Za-z0-9_]*)/', $src, $m, 0, $pos)) {
				$tokens[] = array('var', $m[1], $start);
			} elseif (preg_match('/\G[A-Za-z_][A-Za-z0-9_]*/', $src, $m, 0, $pos)) {
				$tokens[] = array('id', $m[0], $start);
			} elseif (preg_match('/\G(?:===|!==|\?->|\?\?|==|!=|<>|<=|>=|&&|\|\||->|[-+*\/%.!<>?:()\[\],;=])/', $src, $m, 0, $pos)) {
				$tokens[] = array('op', ($m[0] === '<>' ? '!=' : $m[0]), $start);
			} else {
				throw new DolSafeEvalException('Unexpected character at position '.$pos);
			}
			$pos += strlen($m[0]);
		}
		$tokens[] = array('eof', null, $len);

		return $tokens;
	}


	// ------------------------------------------------------------------ Parser

	/**
	 * Parse an expression and return its AST (cached).
	 *
	 * @param	string	$src	Expression
	 * @return	array<int,mixed>	AST root node
	 * @throws	DolSafeEvalException
	 */
	private function compile($src)
	{
		$key = md5($src.'|'.($this->restrict === null ? '' : implode(',', array_keys($this->restrict))));
		if (isset(self::$cache[$key])) {
			return self::$cache[$key];
		}

		$this->tokens = $this->tokenize($src);
		$this->pos = 0;
		$this->nodeCount = 0;

		$ast = $this->parseExpression(0, 0);
		$tok = $this->advance();
		if ($this->isOp($tok, ';')) {	// One optional ending ;
			$tok = $this->advance();
		}
		if ($tok[0] !== 'eof') {
			throw new DolSafeEvalException('Unexpected token at position '.$tok[2]);
		}

		if (count(self::$cache) >= self::CACHE_SIZE) {
			self::$cache = array();
		}
		self::$cache[$key] = $ast;

		return $ast;
	}

	/**
	 * Create a node (and count it).
	 *
	 * @param	mixed	...$parts	Type of node, then its data
	 * @return	array<int,mixed>
	 * @throws	DolSafeEvalException
	 */
	private function node(...$parts)
	{
		if (++$this->nodeCount > self::MAX_NODES) {
			throw new DolSafeEvalException('Expression too complex');
		}
		return $parts;
	}

	/**
	 * Return current token and move to the next one (never moves beyond the end token).
	 *
	 * @return	array<int,mixed>
	 */
	private function advance()
	{
		$tok = $this->tokens[$this->pos];
		if ($tok[0] !== 'eof') {
			$this->pos++;
		}
		return $tok;
	}

	/**
	 * Return true if token is the operator $op.
	 *
	 * @param	array<int,mixed>	$tok	Token
	 * @param	string	$op		Operator
	 * @return	bool
	 */
	private function isOp(array $tok, $op)
	{
		return $tok[0] === 'op' && $tok[1] === $op;
	}

	/**
	 * Consume operator $op or fail.
	 *
	 * @param	string	$op		Operator
	 * @return	void
	 * @throws	DolSafeEvalException
	 */
	private function expectOp($op)
	{
		$tok = $this->advance();
		if (!$this->isOp($tok, $op)) {
			throw new DolSafeEvalException('Expected "'.$op.'" at position '.$tok[2]);
		}
	}

	/**
	 * Parse an expression whose operators have a binding power >= $minBp (Pratt parser).
	 *
	 * @param	int		$minBp		Minimum binding power
	 * @param	int		$depth		Depth of recursion
	 * @return	array<int,mixed>	AST node
	 * @throws	DolSafeEvalException
	 */
	private function parseExpression($minBp, $depth)
	{
		if ($depth > self::MAX_DEPTH) {
			throw new DolSafeEvalException('Expression too deeply nested');
		}

		$left = $this->parsePrefix($depth);

		while (true) {
			$tok = $this->tokens[$this->pos];

			// Ternary operator ? : and short ternary ?:
			if ($this->isOp($tok, '?')) {
				if ($minBp > 30) {
					break;
				}
				$this->pos++;
				if ($this->isOp($this->tokens[$this->pos], ':')) {
					$this->pos++;
					$then = null;
				} else {
					$then = $this->parseExpression(0, $depth + 1);
					$this->expectOp(':');
				}
				$else = $this->parseExpression(31, $depth + 1);
				if ($then !== null && $this->isOp($this->tokens[$this->pos], '?')) {
					throw new DolSafeEvalException('Nested ternary without parentheses is not allowed');
				}
				$left = $this->node('tern', $left, $then, $else);
				continue;
			}

			// Null coalescing (right associative)
			if ($this->isOp($tok, '??')) {
				if ($minBp > 40) {
					break;
				}
				$this->pos++;
				$right = $this->parseExpression(40, $depth + 1);
				$left = $this->node('logic', '??', $left, $right);
				continue;
			}

			// Binary operators
			$op = null;
			if ($tok[0] === 'op') {
				$op = $tok[1];
			} elseif ($tok[0] === 'id') {
				$lc = strtolower($tok[1]);
				if ($lc === 'and' || $lc === 'or') {
					$op = $lc;
				}
			}
			if ($op === null || !isset(self::$binops[$op])) {
				break;
			}
			$bp = self::$binops[$op][0];
			if ($bp < $minBp) {
				break;
			}
			$this->pos++;
			$right = $this->parseExpression($bp + 1, $depth + 1);

			if ($op === 'and') {
				$op = '&&';
			} elseif ($op === 'or') {
				$op = '||';
			}
			$type = ($op === '&&' || $op === '||') ? 'logic' : 'bin';
			$left = $this->node($type, $op, $left, $right);

			if (self::$binops[$tok[0] === 'id' ? strtolower($tok[1]) : $tok[1]][1] === 'n') {
				// Comparison operators are not associative in PHP: a == b == c is a syntax error
				$next = $this->tokens[$this->pos];
				if ($next[0] === 'op' && isset(self::$binops[$next[1]]) && self::$binops[$next[1]][0] === $bp) {
					throw new DolSafeEvalException('Chained comparison without parentheses is not allowed');
				}
			}
		}

		return $left;
	}

	/**
	 * Parse a prefix operator, a literal, a variable, a call or a group, with its postfix operators.
	 *
	 * @param	int		$depth	Depth of recursion
	 * @return	array<int,mixed>	AST node
	 * @throws	DolSafeEvalException
	 */
	private function parsePrefix($depth)
	{
		$tok = $this->advance();

		switch ($tok[0]) {
			case 'num':
			case 'str':
				return $this->parsePostfix($this->node('lit', $tok[1]), $depth);

			case 'var':
				$name = $tok[1];
				if ($name[0] === '_' || $name === 'GLOBALS' || $name === 'this') {
					throw new DolSafeEvalException('Variable $'.$name.' is forbidden');
				}
				return $this->parsePostfix($this->node('var', $name), $depth);

			case 'cast':
				return $this->node('cast', $tok[1], $this->parseExpression(130, $depth + 1));

			case 'id':
				return $this->parsePostfix($this->parseIdentifier($tok, $depth), $depth);

			case 'op':
				switch ($tok[1]) {
					case '(':
						$inner = $this->parseExpression(0, $depth + 1);
						$this->expectOp(')');
						return $this->parsePostfix($inner, $depth);
					case '[':
						$items = $this->parseList(']', self::MAX_NODES, $depth);
						return $this->parsePostfix($this->node('arr', $items), $depth);
					case '!':
						return $this->node('un', '!', $this->parseExpression(120, $depth + 1));
					case '-':
					case '+':
						return $this->node('un', $tok[1], $this->parseExpression(130, $depth + 1));
				}
				break;
		}

		throw new DolSafeEvalException('Unexpected '.($tok[0] === 'eof' ? 'end of expression' : 'token at position '.$tok[2]));
	}

	/**
	 * Parse an identifier: true, false, null, array(), isset(), empty() or a call to a allowed function.
	 *
	 * @param	array<int,mixed>	$tok	Identifier token (already consumed)
	 * @param	int		$depth	Depth of recursion
	 * @return	array<int,mixed>	AST node
	 * @throws	DolSafeEvalException
	 */
	private function parseIdentifier(array $tok, $depth)
	{
		$lc = strtolower($tok[1]);
		if ($lc === 'true') {
			return $this->node('lit', true);
		}
		if ($lc === 'false') {
			return $this->node('lit', false);
		}
		if ($lc === 'null') {
			return $this->node('lit', null);
		}

		// An identifier is only accepted if it is a function call
		if (!$this->isOp($this->tokens[$this->pos], '(')) {
			throw new DolSafeEvalException('Identifier "'.$tok[1].'" is not allowed');
		}
		$this->pos++;
		$args = $this->parseList(')', self::MAX_ARGS, $depth);

		if ($lc === 'array') {
			return $this->node('arr', $args);
		}
		if ($lc === 'isset') {
			if (count($args) < 1) {
				throw new DolSafeEvalException('isset() needs an argument');
			}
			return $this->node('isset', $args);
		}
		if ($lc === 'empty') {
			if (count($args) !== 1) {
				throw new DolSafeEvalException('empty() needs one argument');
			}
			return $this->node('empty', $args[0]);
		}

		if ($this->restrict !== null) {
			$name = isset($this->restrict[$lc]) ? $tok[1] : null;
		} else {
			$allowed = self::allowedFunctions();
			$name = isset($allowed[$lc]) ? $allowed[$lc] : null;
		}
		if ($name === null) {
			throw new DolSafeEvalException('Function "'.$tok[1].'" is not allowed');
		}

		if ($lc === 'preg_match') {
			// The pattern must be a literal (so it is reviewed with the condition), without the 3rd parameter (passed by reference)
			if (count($args) !== 2 || $args[0][0] !== 'lit' || !is_string($args[0][1])) {
				throw new DolSafeEvalException('preg_match() needs a literal pattern and a subject');
			}
		}

		return $this->node('call', $name, $args);
	}

	/**
	 * Parse a list of expressions separated by commas until the closing operator (consumed).
	 *
	 * @param	string	$close	Closing operator: ) or ]
	 * @param	int		$max	Max number of items
	 * @param	int		$depth	Depth of recursion
	 * @return	array<int,array<int,mixed>>
	 * @throws	DolSafeEvalException
	 */
	private function parseList($close, $max, $depth)
	{
		$items = array();
		if ($this->isOp($this->tokens[$this->pos], $close)) {
			$this->pos++;
			return $items;
		}
		while (true) {
			if (count($items) >= $max) {
				throw new DolSafeEvalException('Too many items or arguments');
			}
			$items[] = $this->parseExpression(0, $depth + 1);
			$tok = $this->advance();
			if ($this->isOp($tok, $close)) {
				break;
			}
			if (!$this->isOp($tok, ',')) {
				throw new DolSafeEvalException('Unexpected token at position '.$tok[2]);
			}
		}
		return $items;
	}

	/**
	 * Parse ->property, ->method(...) and [key] following a primary expression.
	 * A call is never accepted after anything else than an identifier or a method name.
	 *
	 * @param	array<int,mixed>	$node	AST node of the primary expression
	 * @param	int		$depth	Depth of recursion
	 * @return	array<int,mixed>	AST node
	 * @throws	DolSafeEvalException
	 */
	private function parsePostfix(array $node, $depth)
	{
		while (true) {
			$tok = $this->tokens[$this->pos];
			if ($tok[0] !== 'op') {
				break;
			}

			if ($tok[1] === '->' || $tok[1] === '?->') {
				$this->pos++;
				$nameTok = $this->advance();
				if ($nameTok[0] !== 'id') {
					throw new DolSafeEvalException('Property or method name expected at position '.$nameTok[2]);
				}
				$name = $nameTok[1];
				if (strncmp($name, '__', 2) === 0) {
					throw new DolSafeEvalException('Member "'.$name.'" is forbidden');
				}

				if ($this->isOp($this->tokens[$this->pos], '(')) {
					$lc = strtolower($name);
					$known = false;
					if ($this->restrict !== null) {
						$known = isset($this->restrict[$lc]);
					} else {
						foreach (self::$allowedMethods as $methods) {
							if (isset($methods[$lc])) {
								$known = true;
							}
						}
					}
					if (!$known) {
						throw new DolSafeEvalException('Method "'.$name.'" is not allowed');
					}
					$this->pos++;
					$args = $this->parseList(')', self::MAX_ARGS, $depth);
					$node = $this->node('mcall', $node, $name, $args, $tok[1] === '?->');
				} else {
					if (isset(self::$forbiddenProperties[strtolower($name)]) || preg_match(self::SENSITIVE_NAME_REGEX, $name)) {
						throw new DolSafeEvalException('Property "'.$name.'" is forbidden');
					}
					$node = $this->node('prop', $node, $name);
				}
			} elseif ($tok[1] === '[') {
				$this->pos++;
				$key = $this->parseExpression(0, $depth + 1);
				$this->expectOp(']');
				$node = $this->node('idx', $node, $key);
			} else {
				break;
			}
		}

		return $node;
	}


	// ------------------------------------------------------------ Interpreter

	/**
	 * Evaluate an AST node.
	 *
	 * @param	array<int,mixed>	$n		AST node
	 * @param	array<string,mixed>	$ctx	Context: 'vars' (variables), 'steps' (counter)
	 * @return	mixed
	 * @throws	DolSafeEvalException
	 */
	private function evalNode(array $n, array &$ctx)
	{
		if (++$ctx['steps'] > self::MAX_STEPS) {
			throw new DolSafeEvalException('Expression too complex to evaluate');
		}

		switch ($n[0]) {
			case 'lit':
				return $n[1];

			case 'var':
				return isset($ctx['vars'][$n[1]]) ? $ctx['vars'][$n[1]] : null;

			case 'arr':
				$result = array();
				foreach ($n[1] as $item) {
					$result[] = $this->evalNode($item, $ctx);
				}
				return $result;

			case 'prop':
				$obj = $this->evalNode($n[1], $ctx);
				if (!is_object($obj)) {
					return null;
				}
				if ($obj instanceof Conf && ($n[2] === 'file' || $n[2] === 'global')) {
					// Constants are read with getDolGlobalString() or getDolGlobalInt(), that refuse the ones that contain a secret
					throw new DolSafeEvalException('Property "'.$n[2].'" of $conf is forbidden');
				}
				// get_object_vars() called from here only returns the public properties
				$props = get_object_vars($obj);
				return array_key_exists($n[2], $props) ? $props[$n[2]] : null;

			case 'idx':
				$arr = $this->evalNode($n[1], $ctx);
				$key = $this->evalNode($n[2], $ctx);
				if (is_array($arr) && (is_int($key) || is_string($key)) && array_key_exists($key, $arr)) {
					return $arr[$key];
				}
				return null;

			case 'call':
				$name = $n[1];
				if (!function_exists($name)) {
					throw new DolSafeEvalException('Function "'.$name.'" does not exist');
				}
				$args = array();
				foreach ($n[2] as $arg) {
					$args[] = $this->evalNode($arg, $ctx);
				}
				if ((strtolower($name) === 'getdolglobalstring' || strtolower($name) === 'getdolglobalint') && isset($args[0]) && is_string($args[0]) && preg_match(self::SENSITIVE_NAME_REGEX, $args[0])) {
					throw new DolSafeEvalException('Constant "'.$args[0].'" is forbidden');
				}
				if (strtolower($name) === 'preg_match') {
					// Limit the cost of a regex (ReDoS)
					if (!is_scalar($args[1]) || strlen((string) $args[1]) > 1000) {
						throw new DolSafeEvalException('Subject of preg_match() is not a string or is too long');
					}
					$matched = false;
					$oldlimit = ini_set('pcre.backtrack_limit', '20000');
					try {
						$matched = preg_match($args[0], (string) $args[1]);
					} finally {
						ini_set('pcre.backtrack_limit', (string) $oldlimit);
					}
					if ($matched === false) {
						throw new DolSafeEvalException('Error in preg_match() (PCRE error code '.preg_last_error().')');
					}
					return $matched;
				}
				return $name(...$args);

			case 'mcall':
				$obj = $this->evalNode($n[1], $ctx);
				if ($obj === null && $n[4]) {
					return null;
				}
				if (!is_object($obj)) {
					throw new DolSafeEvalException('Method "'.$n[2].'" called on something that is not an object');
				}
				$lc = strtolower($n[2]);
				$allowed = false;
				if ($this->restrict !== null) {
					$allowed = isset($this->restrict[$lc]);
				} else {
					foreach (self::$allowedMethods as $class => $methods) {
						if (isset($methods[$lc]) && $obj instanceof $class) {
							$allowed = true;
							break;
						}
					}
				}
				if (!$allowed) {
					throw new DolSafeEvalException('Method "'.$n[2].'" is not allowed on '.get_class($obj));
				}
				$args = array();
				foreach ($n[3] as $arg) {
					$args[] = $this->evalNode($arg, $ctx);
				}
				// @phan-suppress-next-line PhanPluginUnknownObjectMethodCall
				return $obj->{$n[2]}(...$args);

			case 'un':
				$value = $this->evalNode($n[2], $ctx);
				if ($n[1] === '!') {
					return !$value;
				}
				return ($n[1] === '-') ? -$value : +$value;

			case 'cast':
				$value = $this->evalNode($n[2], $ctx);
				switch ($n[1]) {
					case 'int':
					case 'integer':
						return (int) $value;
					case 'float':
					case 'double':
						return (float) $value;
					case 'string':
						return (string) $value;
					default:
						return (bool) $value;
				}

			case 'logic':
				$left = $this->evalNode($n[2], $ctx);
				if ($n[1] === '??') {
					return ($left !== null) ? $left : $this->evalNode($n[3], $ctx);
				}
				if ($n[1] === '&&') {
					return $left ? (bool) $this->evalNode($n[3], $ctx) : false;
				}
				return $left ? true : (bool) $this->evalNode($n[3], $ctx);	// ||

			case 'tern':
				$cond = $this->evalNode($n[1], $ctx);
				if ($n[2] === null) {
					return $cond ? $cond : $this->evalNode($n[3], $ctx);
				}
				return $cond ? $this->evalNode($n[2], $ctx) : $this->evalNode($n[3], $ctx);

			case 'isset':
				foreach ($n[1] as $arg) {
					if ($this->evalNode($arg, $ctx) === null) {
						return false;
					}
				}
				return true;

			case 'empty':
				$value = $this->evalNode($n[1], $ctx);
				return empty($value);

			case 'bin':
				$left = $this->evalNode($n[2], $ctx);
				$right = $this->evalNode($n[3], $ctx);
				switch ($n[1]) {
					case '==':
						return $left == $right;
					case '!=':
						return $left != $right;
					case '===':
						return $left === $right;
					case '!==':
						return $left !== $right;
					case '<':
						return $left < $right;
					case '<=':
						return $left <= $right;
					case '>':
						return $left > $right;
					case '>=':
						return $left >= $right;
					case '.':
						return $left . $right;
					case '+':
						return $left + $right;
					case '-':
						return $left - $right;
					case '*':
						return $left * $right;
					case '/':
						return $left / $right;
					case '%':
						return $left % $right;
				}
				break;
		}

		throw new DolSafeEvalException('Unknown node type');
	}
}
