<?php
/**
 * Baseline regression tests for the custom SQL functions registered by the
 * SQLite3 driver (the MySQL-compatibility shims), plus an end-to-end check
 * that runs real queries through a real DoliDBSqlite3 instance.
 */

use PHPUnit\Framework\TestCase;

class CustomFunctionsTest extends TestCase
{
	/** @var DoliDBSqlite3|null */
	private $db;

	/** @var string */
	private $dbname = '';

	protected function setUp(): void
	{
		$this->dbname = 'unittest_' . getmypid() . '_' . substr(md5(uniqid('', true)), 0, 8);
		$this->db = new DoliDBSqlite3('sqlite3', 'localhost', 'test', 'test', $this->dbname);
		$this->assertTrue($this->db->connected, 'Driver should be connected');
	}

	protected function tearDown(): void
	{
		if ($this->db && $this->db->connected) {
			$this->db->close();
		}
		$file = ($GLOBALS['main_data_dir'] ?? sys_get_temp_dir()) . '/database_' . $this->dbname . '.sdb';
		if (is_file($file)) {
			@unlink($file);
		}
		$this->db = null;
	}

	/**
	 * Helper: run a SELECT and return the single scalar of the first row/column.
	 *
	 * @param string $sql Query to run
	 * @return mixed      First column of first row, or null
	 */
	private function scalar($sql)
	{
		$res = $this->db->query($sql);
		$this->assertNotFalse($res, 'Query failed: ' . $sql . ' :: ' . $this->db->lasterror());
		$row = $this->db->fetch_row($res);
		return $row ? $row[0] : null;
	}

	// ------------------------------------------------- direct static functions

	public function testDbMonth()
	{
		$this->assertSame(3, DoliDBSqlite3::dbMONTH('2026-03-15'));
		$this->assertNull(DoliDBSqlite3::dbMONTH(''));
	}

	public function testDbYear()
	{
		$this->assertSame(2026, DoliDBSqlite3::dbYEAR('2026-03-15'));
		$this->assertNull(DoliDBSqlite3::dbYEAR(''));
	}

	public function testDbDateComponentExtractors()
	{
		$dt = '2026-03-15 10:20:30';
		$this->assertSame(15, DoliDBSqlite3::dbDAY($dt));
		$this->assertSame(10, DoliDBSqlite3::dbHOUR($dt));
		$this->assertSame(20, DoliDBSqlite3::dbMINUTE($dt));
		$this->assertSame(30, DoliDBSqlite3::dbSECOND($dt));
		$this->assertNull(DoliDBSqlite3::dbDAY(''));
		$this->assertNull(DoliDBSqlite3::dbHOUR(''));
	}

	public function testDbQuarter()
	{
		$this->assertSame(1, DoliDBSqlite3::dbQUARTER('2026-03-15')); // March -> Q1
		$this->assertSame(2, DoliDBSqlite3::dbQUARTER('2026-04-01')); // April -> Q2
		$this->assertSame(4, DoliDBSqlite3::dbQUARTER('2026-11-01')); // November -> Q4
		$this->assertNull(DoliDBSqlite3::dbQUARTER(''));
	}

	public function testDbDayOfWeek()
	{
		// MySQL convention: 1=Sunday .. 7=Saturday.
		$this->assertSame(1, DoliDBSqlite3::dbDAYOFWEEK('2023-01-01')); // Sunday
		$this->assertSame(2, DoliDBSqlite3::dbDAYOFWEEK('2023-01-02')); // Monday
		$this->assertNull(DoliDBSqlite3::dbDAYOFWEEK(''));
	}

	public function testDbDayOfYear()
	{
		$this->assertSame(1, DoliDBSqlite3::dbDAYOFYEAR('2026-01-01'));
		$this->assertSame(74, DoliDBSqlite3::dbDAYOFYEAR('2026-03-15')); // 31 + 28 + 15 (2026 not leap)
		$this->assertNull(DoliDBSqlite3::dbDAYOFYEAR(''));
	}

	public function testDbLastDay()
	{
		$this->assertSame('2026-03-31', DoliDBSqlite3::dbLASTDAY('2026-03-15'));
		$this->assertSame('2026-02-28', DoliDBSqlite3::dbLASTDAY('2026-02-10')); // 2026 not leap
		$this->assertSame('2024-02-29', DoliDBSqlite3::dbLASTDAY('2024-02-10')); // 2024 leap
		$this->assertNull(DoliDBSqlite3::dbLASTDAY(''));
	}

	public function testDbLocate()
	{
		$this->assertSame(4, DoliDBSqlite3::dbLOCATE('lo', 'hello'));
		$this->assertSame(0, DoliDBSqlite3::dbLOCATE('xy', 'hello'));
		$this->assertSame(3, DoliDBSqlite3::dbLOCATE('l', 'hello', 3)); // search from pos 3
		$this->assertSame(1, DoliDBSqlite3::dbLOCATE('HE', 'hello')); // case-insensitive
		$this->assertNull(DoliDBSqlite3::dbLOCATE(null, 'hello'));
	}

	public function testDbGreatestLeast()
	{
		$this->assertSame(5, DoliDBSqlite3::dbGREATEST(1, 5, 3));
		$this->assertSame(2, DoliDBSqlite3::dbLEAST(4, 2, 8));
		$this->assertNull(DoliDBSqlite3::dbGREATEST(1, null, 3));
		$this->assertNull(DoliDBSqlite3::dbLEAST(1, null, 3));
	}

	public function testDbMd5AndSha1()
	{
		$this->assertSame(md5('abc'), DoliDBSqlite3::dbMD5('abc'));
		$this->assertSame(sha1('abc'), DoliDBSqlite3::dbSHA1('abc'));
		$this->assertNull(DoliDBSqlite3::dbMD5(null));
		$this->assertNull(DoliDBSqlite3::dbSHA1(null));
	}

	public function testDbRandInRange()
	{
		for ($i = 0; $i < 20; $i++) {
			$r = DoliDBSqlite3::dbRAND();
			$this->assertIsFloat($r);
			$this->assertGreaterThanOrEqual(0.0, $r);
			$this->assertLessThan(1.0, $r);
		}
	}

	public function testDbRegexp()
	{
		$this->assertSame(1, DoliDBSqlite3::dbREGEXP('^h', 'hello'));
		$this->assertSame(0, DoliDBSqlite3::dbREGEXP('^z', 'hello'));
		$this->assertSame(1, DoliDBSqlite3::dbREGEXP('HELLO', 'hello')); // case-insensitive
		$this->assertNull(DoliDBSqlite3::dbREGEXP(null, 'hello'));
	}

	public function testDbIf()
	{
		$this->assertSame('a', DoliDBSqlite3::dbIF(1, 'a', 'b'));
		$this->assertSame('b', DoliDBSqlite3::dbIF(0, 'a', 'b'));
	}

	public function testDbConcat()
	{
		$this->assertSame('abc', DoliDBSqlite3::dbCONCAT('a', 'b', 'c'));
		$this->assertSame('ac', DoliDBSqlite3::dbCONCAT('a', null, 'c'));
		$this->assertSame('', DoliDBSqlite3::dbCONCAT());
	}

	public function testDbCurdateAndCurtimeFormat()
	{
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', DoliDBSqlite3::dbCURDATE());
		$this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', DoliDBSqlite3::dbCURTIME());
	}

	public function testDbWeekday()
	{
		// 2023-01-02 was a Monday; MySQL/this shim maps Monday -> 0.
		$this->assertSame(0, DoliDBSqlite3::dbWEEKDAY('2023-01-02'));
		// 2023-01-01 was a Sunday -> 6.
		$this->assertSame(6, DoliDBSqlite3::dbWEEKDAY('2023-01-01'));
	}

	public function testDbDateFormat()
	{
		$this->assertSame('2026-03-15', DoliDBSqlite3::dbdateformat('2026-03-15 10:20:30', '%Y-%m-%d'));
		$this->assertSame('10:20', DoliDBSqlite3::dbdateformat('2026-03-15 10:20:30', '%H:%i'));
	}

	public function testDbWeekReturnsIntInRange()
	{
		$w = DoliDBSqlite3::dbWEEK('2026-06-01', 1);
		$this->assertIsInt($w);
		$this->assertGreaterThanOrEqual(0, $w);
		$this->assertLessThanOrEqual(53, $w);
	}

	// ------------------------------------------------------------- end-to-end

	public function testEndToEndCreateInsertSelectWithShims()
	{
		$create = "CREATE TABLE llx_test (rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY, label varchar(50), d datetime)";
		$this->assertNotFalse($this->db->query($create), 'CREATE failed: ' . $this->db->lasterror());

		$this->assertNotFalse(
			$this->db->query("INSERT INTO llx_test (label, d) VALUES ('foo', '2026-03-15 10:00:00')"),
			'INSERT failed: ' . $this->db->lasterror()
		);

		$this->assertSame('foo-foo', $this->scalar("SELECT CONCAT(label, '-', label) FROM llx_test"));
		$this->assertEquals(3, $this->scalar("SELECT MONTH(d) FROM llx_test"));
		$this->assertEquals(2026, $this->scalar("SELECT YEAR(d) FROM llx_test"));
		$this->assertSame('one', $this->scalar("SELECT IF(rowid=1, 'one', 'other') FROM llx_test"));

		// Lot 2 date extractors, end-to-end through the engine.
		$this->assertEquals(15, $this->scalar("SELECT DAY(d) FROM llx_test"));
		$this->assertEquals(10, $this->scalar("SELECT HOUR(d) FROM llx_test"));
		$this->assertEquals(1, $this->scalar("SELECT QUARTER(d) FROM llx_test"));
		$this->assertSame('2026-03-31', $this->scalar("SELECT LAST_DAY(d) FROM llx_test"));
	}

	public function testEndToEndDateInterval()
	{
		$this->db->query("CREATE TABLE llx_dt (rowid integer PRIMARY KEY, d datetime)");
		$this->db->query("INSERT INTO llx_dt (rowid, d) VALUES (1, '2026-03-15 10:00:00')");

		$this->assertSame('2026-02-15 10:00:00', $this->scalar("SELECT DATE_SUB(d, INTERVAL 1 MONTH) FROM llx_dt"));
		$this->assertSame('2026-03-25 10:00:00', $this->scalar("SELECT DATE_ADD(d, INTERVAL 10 DAY) FROM llx_dt"));
		$this->assertSame('2026-03-29 10:00:00', $this->scalar("SELECT DATE_ADD(d, INTERVAL 2 WEEK) FROM llx_dt"));

		// DATE_SUB(NOW(), ...) must run without error and return a datetime string.
		$res = $this->scalar("SELECT DATE_SUB(NOW(), INTERVAL 1 DAY)");
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $res);
	}

	public function testEndToEndRegexpOperator()
	{
		$this->db->query("CREATE TABLE llx_rx (rowid integer PRIMARY KEY, v varchar(20))");
		$this->db->query("INSERT INTO llx_rx (rowid, v) VALUES (1, 'hello'), (2, 'world')");
		$this->assertSame('hello', $this->scalar("SELECT v FROM llx_rx WHERE v REGEXP '^hel'"));
	}

	public function testEndToEndMiscFunctions()
	{
		$this->assertEquals(5, $this->scalar("SELECT GREATEST(1, 5, 3)"));
		$this->assertEquals(2, $this->scalar("SELECT LEAST(4, 2, 8)"));
		$this->assertEquals(4, $this->scalar("SELECT LOCATE('lo', 'hello')"));
		$this->assertSame(md5('abc'), $this->scalar("SELECT MD5('abc')"));

		$rand = $this->scalar("SELECT RAND()");
		$this->assertGreaterThanOrEqual(0.0, (float) $rand);
		$this->assertLessThan(1.0, (float) $rand);
	}

	public function testEndToEndGroupConcatSeparator()
	{
		$this->db->query("CREATE TABLE llx_gc (rowid integer PRIMARY KEY, v varchar(10))");
		$this->db->query("INSERT INTO llx_gc (rowid, v) VALUES (1, 'a'), (2, 'b'), (3, 'c')");
		$out = $this->scalar("SELECT GROUP_CONCAT(v SEPARATOR '|') FROM llx_gc");
		// Row order for GROUP_CONCAT without ORDER BY is not guaranteed; check content.
		$this->assertSame(array('a', 'b', 'c'), $this->sortedPieces((string) $out, '|'));
	}

	/**
	 * Split a delimited string and sort the pieces, for order-independent checks.
	 *
	 * @param string $s   Delimited string
	 * @param string $sep Separator
	 * @return string[]   Sorted pieces
	 */
	private function sortedPieces($s, $sep)
	{
		$pieces = explode($sep, $s);
		sort($pieces);
		return $pieces;
	}

	public function testEndToEndBigintAutoIncrement()
	{
		// A bigint AUTO_INCREMENT PK must actually auto-assign ids on SQLite.
		$this->assertNotFalse(
			$this->db->query("CREATE TABLE llx_ai (rowid bigint AUTO_INCREMENT PRIMARY KEY, label varchar(20))"),
			'CREATE failed: ' . $this->db->lasterror()
		);
		$this->db->query("INSERT INTO llx_ai (label) VALUES ('first')");
		$this->db->query("INSERT INTO llx_ai (label) VALUES ('second')");
		$this->assertEquals(2, $this->scalar("SELECT rowid FROM llx_ai WHERE label='second'"));
		$this->assertEquals(2, $this->scalar("SELECT COUNT(*) FROM llx_ai"));
	}

	public function testEndToEndCreateTableWithFulltextAndCharset()
	{
		// FULLTEXT index and column CHARACTER SET/COLLATE must be stripped so the
		// table is actually created and usable on SQLite.
		$create = "CREATE TABLE llx_ft (rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY, "
			. "code varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci, "
			. "body text, FULLTEXT KEY ftidx (body))";
		$this->assertNotFalse($this->db->query($create), 'CREATE failed: ' . $this->db->lasterror());

		$this->assertNotFalse(
			$this->db->query("INSERT INTO llx_ft (code, body) VALUES ('AB', 'hello world')"),
			'INSERT failed: ' . $this->db->lasterror()
		);
		$this->assertSame('hello world', $this->scalar("SELECT body FROM llx_ft WHERE code='AB'"));
	}

	public function testEndToEndNowTranslation()
	{
		$now = $this->scalar("SELECT NOW()");
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $now);
	}

	public function testEndToEndInsertIgnore()
	{
		$this->db->query("CREATE TABLE llx_u (id integer PRIMARY KEY, v varchar(10))");
		$this->db->query("INSERT INTO llx_u (id, v) VALUES (1, 'a')");
		// Second insert on same PK must be ignored, not error out.
		$res = $this->db->query("INSERT IGNORE INTO llx_u (id, v) VALUES (1, 'b')");
		$this->assertNotFalse($res, 'INSERT IGNORE failed: ' . $this->db->lasterror());
		$this->assertEquals('a', $this->scalar("SELECT v FROM llx_u WHERE id=1"));
	}
}
