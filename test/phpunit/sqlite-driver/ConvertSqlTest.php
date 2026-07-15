<?php
/**
 * Baseline regression tests for DoliDBSqlite3::convertSQLFromMysql().
 *
 * These lock the CURRENT MySQL -> SQLite translation behaviour so future
 * changes to the driver cannot silently regress what already works. Each new
 * supported construction added in later batches gets its own case here.
 */

use PHPUnit\Framework\TestCase;

class ConvertSqlTest extends TestCase
{
	/**
	 * Run the driver translation the same way query() does (type 'auto').
	 *
	 * @param string $sql  Input MySQL SQL
	 * @return string      Translated SQLite SQL
	 */
	private function convert($sql)
	{
		// convertSQLFromMysql is an instance method (Database interface), but its
		// body is pure translation logic that never touches $this, so a bare
		// instance without a DB connection is enough to exercise it.
		static $driver = null;
		if ($driver === null) {
			$driver = (new ReflectionClass('DoliDBSqlite3'))->newInstanceWithoutConstructor();
		}
		return $driver->convertSQLFromMysql($sql, 'auto');
	}

	/**
	 * Collapse runs of whitespace and trim, for stable string comparisons.
	 *
	 * @param string $s String to normalise
	 * @return string   Normalised string
	 */
	private function norm($s)
	{
		return trim(preg_replace('/\s+/', ' ', $s));
	}

	// ----------------------------------------------------------------- DML

	/**
	 * @dataProvider dmlFunctionProvider
	 *
	 * @param string $in        Input SQL
	 * @param string $contains  Substring expected in output
	 * @param string $absent    Substring expected to be gone ('' to skip)
	 * @return void
	 */
	public function testDmlFunctionTranslation($in, $contains, $absent)
	{
		$out = $this->convert($in);
		$this->assertStringContainsStringIgnoringCase($contains, $out, "Input: $in");
		if ($absent !== '') {
			$this->assertStringNotContainsStringIgnoringCase($absent, $out, "Input: $in");
		}
	}

	/**
	 * @return array<string, array{0:string,1:string,2:string}>
	 */
	public static function dmlFunctionProvider()
	{
		return array(
			'NOW()' => array("SELECT NOW()", "datetime('now')", "NOW("),
			'UNIX_TIMESTAMP(col)' => array("SELECT UNIX_TIMESTAMP(d) FROM t", "strftime('%s', d)", "UNIX_TIMESTAMP"),
			'UNIX_TIMESTAMP()' => array("SELECT UNIX_TIMESTAMP()", "strftime('%s', 'now')", "UNIX_TIMESTAMP("),
			'FROM_UNIXTIME(col)' => array("SELECT FROM_UNIXTIME(ts) FROM t", "datetime(ts, 'unixepoch')", "FROM_UNIXTIME"),
			'SUBSTRING FROM' => array("SELECT SUBSTRING(ref FROM 5) FROM t", "SUBSTR(ref, 5)", "SUBSTRING"),
			'CAST AS SIGNED' => array("SELECT CAST(col AS SIGNED) FROM t", "CAST(col AS INTEGER)", "SIGNED"),
			'CAST AS UNSIGNED' => array("SELECT CAST(col AS UNSIGNED) FROM t", "CAST(col AS INTEGER)", "UNSIGNED"),
			'INSERT IGNORE' => array("INSERT IGNORE INTO llx_t (a) VALUES (1)", "INSERT OR IGNORE INTO", "INSERT IGNORE INTO"),
		);
	}

	/**
	 * @dataProvider intervalProvider
	 *
	 * @param string $in        Input SQL
	 * @param string $contains  Substring expected in output
	 * @param string $absent    Substring expected to be gone
	 * @return void
	 */
	public function testIntervalAndExtractTranslation($in, $contains, $absent)
	{
		$out = $this->convert($in);
		$this->assertStringContainsStringIgnoringCase($contains, $out, "Input: $in -> $out");
		$this->assertStringNotContainsStringIgnoringCase($absent, $out, "Input: $in -> $out");
	}

	/**
	 * @return array<string, array{0:string,1:string,2:string}>
	 */
	public static function intervalProvider()
	{
		return array(
			'DATE_SUB month' => array("SELECT DATE_SUB(t.d, INTERVAL 1 MONTH) FROM t", "datetime(t.d, '-1 months')", "DATE_SUB"),
			'DATE_ADD day' => array("SELECT DATE_ADD(t.d, INTERVAL 7 DAY) FROM t", "datetime(t.d, '+7 days')", "DATE_ADD"),
			'DATE_SUB year' => array("SELECT DATE_SUB(t.d, INTERVAL 1 YEAR) FROM t", "datetime(t.d, '-1 years')", "INTERVAL"),
			'DATE_ADD week->days' => array("SELECT DATE_ADD(t.d, INTERVAL 2 WEEK) FROM t", "datetime(t.d, '+14 days')", "WEEK"),
			'DATE_ADD quarter->months' => array("SELECT DATE_ADD(t.d, INTERVAL 1 QUARTER) FROM t", "datetime(t.d, '+3 months')", "QUARTER"),
			'DATE_SUB with NOW' => array("SELECT DATE_SUB(NOW(), INTERVAL 1 MONTH)", "datetime(datetime('now'), '-1 months')", "NOW("),
			'EXTRACT year' => array("SELECT EXTRACT(YEAR FROM d) FROM t", "YEAR(d)", "EXTRACT"),
			'EXTRACT month qualified' => array("SELECT EXTRACT(MONTH FROM t.d) FROM t", "MONTH(t.d)", "EXTRACT"),
		);
	}

	/**
	 * @dataProvider groupConcatAndBinaryProvider
	 *
	 * @param string $in        Input SQL
	 * @param string $contains  Substring expected in output
	 * @param string $absent    Substring expected to be gone
	 * @return void
	 */
	public function testGroupConcatAndBinaryTranslation($in, $contains, $absent)
	{
		$out = $this->convert($in);
		$this->assertStringContainsStringIgnoringCase($contains, $out, "Input: $in -> $out");
		$this->assertStringNotContainsStringIgnoringCase($absent, $out, "Input: $in -> $out");
	}

	/**
	 * @return array<string, array{0:string,1:string,2:string}>
	 */
	public static function groupConcatAndBinaryProvider()
	{
		return array(
			'GROUP_CONCAT simple sep' => array("SELECT GROUP_CONCAT(x SEPARATOR ',') FROM t", "GROUP_CONCAT(x, ',')", "SEPARATOR"),
			'GROUP_CONCAT qualified sep' => array("SELECT GROUP_CONCAT(cat.label SEPARATOR ', ') FROM t", "GROUP_CONCAT(cat.label, ', ')", "SEPARATOR"),
			'GROUP_CONCAT distinct' => array("SELECT GROUP_CONCAT(DISTINCT x SEPARATOR ',') FROM t", "GROUP_CONCAT(DISTINCT x)", "SEPARATOR"),
			'BINARY after WHERE' => array("SELECT * FROM t WHERE BINARY name = 'X'", "WHERE name = 'X'", "BINARY"),
			'BINARY after AND' => array("SELECT * FROM t WHERE a=1 AND BINARY email = 'a'", "AND email = 'a'", "BINARY"),
		);
	}

	public function testOnDuplicateKeyBecomesInsertOrReplace()
	{
		$out = $this->convert("INSERT INTO llx_t (a,b) VALUES (1,2) ON DUPLICATE KEY UPDATE b=2");
		$this->assertStringContainsStringIgnoringCase("INSERT OR REPLACE INTO", $out);
		$this->assertStringNotContainsStringIgnoringCase("ON DUPLICATE", $out);
	}

	public function testDeleteUsingSameTableDropsDuplicate()
	{
		$out = $this->convert("DELETE FROM llx_t USING llx_t, llx_other WHERE llx_t.id = llx_other.fk");
		// The redundant self-reference in USING must be removed.
		$this->assertStringContainsStringIgnoringCase("USING llx_other", $out);
		$this->assertDoesNotMatchRegularExpression('/USING\s+llx_t\s*,/i', $out, "Input: $out");
	}

	/**
	 * SQLite has no row-level locking clause, so the MySQL / PostgreSQL
	 * pessimistic-locking tail must be stripped: "SELECT ... FOR UPDATE" has
	 * to run as a plain SELECT (the surrounding write transaction already
	 * locks the whole database) instead of raising 'near "FOR": syntax error'.
	 *
	 * @dataProvider lockingClauseProvider
	 *
	 * @param string $in       Input SQL carrying a locking tail
	 * @param string $expected Normalised SQL expected once the tail is stripped
	 * @return void
	 */
	public function testLockingClauseStripped($in, $expected)
	{
		$this->assertSame($expected, $this->norm($this->convert($in)));
	}

	/**
	 * @return array<string, array{0:string,1:string}>
	 */
	public static function lockingClauseProvider()
	{
		return array(
			'FOR UPDATE' => array("SELECT * FROM llx_societe WHERE rowid = 5 FOR UPDATE", "SELECT * FROM llx_societe WHERE rowid = 5"),
			'FOR UPDATE trailing semicolon' => array("SELECT a FROM llx_t WHERE id = 1 FOR UPDATE;", "SELECT a FROM llx_t WHERE id = 1;"),
			'FOR UPDATE NOWAIT' => array("SELECT a FROM llx_t FOR UPDATE NOWAIT", "SELECT a FROM llx_t"),
			'FOR UPDATE SKIP LOCKED' => array("SELECT a FROM llx_t FOR UPDATE SKIP LOCKED", "SELECT a FROM llx_t"),
			'FOR SHARE' => array("SELECT a FROM llx_t FOR SHARE", "SELECT a FROM llx_t"),
			'LOCK IN SHARE MODE' => array("SELECT a FROM llx_t LOCK IN SHARE MODE", "SELECT a FROM llx_t"),
		);
	}

	/**
	 * A literal 'FOR UPDATE' inside a string value (not a trailing clause)
	 * must be left untouched.
	 *
	 * @return void
	 */
	public function testLockingClauseNotStrippedInsideStringLiteral()
	{
		$in = "SELECT * FROM llx_t WHERE label = 'flagged FOR UPDATE'";
		$this->assertSame($in, $this->norm($this->convert($in)));
	}

	// ----------------------------------------------------------------- DDL

	public function testCreateTableRemovesEngine()
	{
		$out = $this->convert("CREATE TABLE llx_t (rowid integer) ENGINE=InnoDB;");
		$this->assertStringNotContainsStringIgnoringCase("ENGINE", $out);
		$this->assertStringNotContainsStringIgnoringCase("InnoDB", $out);
	}

	public function testCreateTableAutoIncrementBecomesAutoincrement()
	{
		$out = $this->convert("CREATE TABLE llx_t (rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY, label varchar(255))");
		$this->assertStringContainsStringIgnoringCase("AUTOINCREMENT", $out);
		$this->assertStringNotContainsStringIgnoringCase("AUTO_INCREMENT", $out);
	}

	public function testCreateTableBigintAutoIncrementBecomesIntegerAutoincrement()
	{
		// bigint/smallint/... AUTO_INCREMENT must also become "integer PRIMARY KEY AUTOINCREMENT",
		// since SQLite only auto-increments a column declared exactly as INTEGER PRIMARY KEY.
		$out = $this->convert("CREATE TABLE llx_t (rowid bigint AUTO_INCREMENT PRIMARY KEY, label varchar(255))");
		$this->assertStringContainsStringIgnoringCase("integer PRIMARY KEY AUTOINCREMENT", $out);
		$this->assertStringNotContainsStringIgnoringCase("AUTO_INCREMENT", $out);
		$this->assertStringNotContainsStringIgnoringCase("bigint", $out);
	}

	/**
	 * Regression: real Dolibarr DDL is written ")ENGINE=innodb" (no space) and the
	 * installer strips the trailing ';'. The ENGINE clause and all trailing table
	 * options must still be removed in that form.
	 *
	 * @dataProvider engineProvider
	 *
	 * @param string $in CREATE TABLE input
	 * @return void
	 */
	public function testEngineClauseRemovedInAllForms($in)
	{
		$out = $this->convert($in);
		$this->assertStringNotContainsStringIgnoringCase("ENGINE", $out, "Input: $in -> $out");
		$this->assertStringNotContainsStringIgnoringCase("CHARSET", $out, "Input: $in -> $out");
		$this->assertStringNotContainsStringIgnoringCase("AUTO_INCREMENT", $out, "Input: $in -> $out");
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function engineProvider()
	{
		return array(
			'no space no semicolon' => array("CREATE TABLE llx_t (a integer)ENGINE=innodb"),
			'space no semicolon' => array("CREATE TABLE llx_t (a integer) ENGINE=InnoDB"),
			'no semicolon trailing options' => array("CREATE TABLE llx_t (a integer)ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=5"),
			'with semicolon' => array("CREATE TABLE llx_t (a integer) ENGINE=InnoDB DEFAULT CHARSET=utf8;"),
		);
	}

	/**
	 * @dataProvider datatypeProvider
	 *
	 * @param string $in        CREATE TABLE fragment
	 * @param string $contains  Expected resulting type token
	 * @param string $absent    Type token that must be gone
	 * @return void
	 */
	public function testDatatypeConversion($in, $contains, $absent)
	{
		$out = $this->convert($in);
		$this->assertStringContainsStringIgnoringCase($contains, $out, "Input: $in");
		$this->assertStringNotContainsStringIgnoringCase($absent, $out, "Input: $in");
	}

	/**
	 * @return array<string, array{0:string,1:string,2:string}>
	 */
	public static function datatypeProvider()
	{
		return array(
			'tinyint' => array("CREATE TABLE llx_t (a tinyint)", "smallint", "tinyint"),
			'mediumtext' => array("CREATE TABLE llx_t (a mediumtext)", "text", "mediumtext"),
			'blob' => array("CREATE TABLE llx_t (a longblob)", "text", "blob"),
			'datetime' => array("CREATE TABLE llx_t (a datetime)", "timestamp", "datetime"),
			'integer unsigned' => array("CREATE TABLE llx_t (a integer unsigned)", "integer", "unsigned"),
		);
	}

	/**
	 * @dataProvider ddlEdgeProvider
	 *
	 * @param string $in      CREATE TABLE input
	 * @param string $absent  Token that must be gone from the output
	 * @return void
	 */
	public function testDdlEdgeCasesStripped($in, $absent)
	{
		$out = $this->convert($in);
		$this->assertStringNotContainsStringIgnoringCase($absent, $out, "Input: $in -> $out");
	}

	/**
	 * @return array<string, array{0:string,1:string}>
	 */
	public static function ddlEdgeProvider()
	{
		return array(
			'FULLTEXT KEY named' => array("CREATE TABLE llx_t (a integer, body text, FULLTEXT KEY ft_idx (body))", "FULLTEXT"),
			'FULLTEXT unnamed' => array("CREATE TABLE llx_t (body text, FULLTEXT (body))", "FULLTEXT"),
			'SPATIAL KEY' => array("CREATE TABLE llx_t (g text, SPATIAL KEY sp_idx (g))", "SPATIAL"),
			'column CHARACTER SET' => array("CREATE TABLE llx_t (code varchar(20) CHARACTER SET utf8)", "CHARACTER SET"),
			'column CHARSET+COLLATE' => array("CREATE TABLE llx_t (code varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci)", "COLLATE"),
		);
	}

	public function testCreateTableRemovesInlineComment()
	{
		$out = $this->convert("CREATE TABLE llx_t (a integer COMMENT 'hello world')");
		$this->assertStringNotContainsStringIgnoringCase("COMMENT", $out);
		$this->assertStringNotContainsStringIgnoringCase("hello world", $out);
	}

	public function testCreateTableRemovesInlineIndexButKeepsKeySuffixedColumns()
	{
		// Regression: a column named *_key must NOT be mangled by the INDEX/KEY strip.
		$out = $this->convert("CREATE TABLE llx_t (rowid integer, import_key varchar(14), KEY idx_a (rowid))");
		$this->assertStringContainsStringIgnoringCase("import_key", $out);
		$this->assertDoesNotMatchRegularExpression('/\bKEY\s+idx_a\b/i', $out, "Input: $out");
	}

	// ----------------------------------------------------------- ALTER TABLE

	public function testAlterTableAddIndexBecomesCreateIndex()
	{
		$out = $this->convert("ALTER TABLE llx_t ADD INDEX idx_a (fieldname)");
		$this->assertMatchesRegularExpression('/CREATE\s+INDEX\s+idx_a\s+ON\s+llx_t\s*\(\s*fieldname\s*\)/i', $this->norm($out));
	}

	public function testAlterTableAddUniqueIndexBecomesCreateUniqueIndex()
	{
		$out = $this->convert("ALTER TABLE llx_t ADD UNIQUE INDEX idx_a (fieldname)");
		$this->assertMatchesRegularExpression('/CREATE\s+UNIQUE\s+INDEX/i', $out);
	}

	public function testAlterTableChangeBecomesRenameColumn()
	{
		$out = $this->convert("ALTER TABLE llx_t CHANGE oldcol newcol varchar(50)");
		$this->assertStringContainsStringIgnoringCase("RENAME COLUMN oldcol TO newcol", $out);
	}

	public function testAlterTableDropForeignKeyBecomesDropConstraint()
	{
		$out = $this->convert("ALTER TABLE llx_t DROP FOREIGN KEY fk_t_other");
		$this->assertStringContainsStringIgnoringCase("DROP CONSTRAINT fk_t_other", $out);
	}

	public function testAlterTableAddPrimaryKeyBecomesUniqueIndex()
	{
		$out = $this->convert("ALTER TABLE llx_t ADD PRIMARY KEY pk_t (numero, entity)");
		$this->assertStringContainsStringIgnoringCase("CREATE UNIQUE INDEX", $out);
	}

	public function testAlterTableAddConstraintUniqueBecomesUniqueIndex()
	{
		$out = $this->convert("ALTER TABLE llx_product_pricerules ADD CONSTRAINT unique_level UNIQUE (level)");
		$this->assertMatchesRegularExpression(
			'/CREATE\s+UNIQUE\s+INDEX\s+unique_level\s+ON\s+llx_product_pricerules\s*\(\s*level\s*\)/i',
			$this->norm($out)
		);
		$this->assertStringNotContainsStringIgnoringCase("ADD CONSTRAINT", $out);
	}

	// --------------------------------------------------------------- Comments

	public function testCommentLinesArePreserved()
	{
		$this->assertSame("# a comment", $this->convert("# a comment"));
		$this->assertSame("-- another comment", $this->convert("-- another comment"));
	}
}
