<?php
/**
 * Self-contained PHPUnit bootstrap for the SQLite3 Dolibarr DB driver tests.
 *
 * It loads ONLY what htdocs/core/db/sqlite3.class.php needs (the DoliDB base
 * class and the Database interface), plus minimal stubs for the few Dolibarr
 * globals/functions the driver touches at runtime. No composer autoload, no
 * full Dolibarr environment: these tests target the MySQL -> SQLite translation
 * layer in isolation so they stay fast and dependency-free.
 */

error_reporting(E_ALL);

// Repo root is three levels up from test/phpunit/sqlite-driver/.
$repoRoot = dirname(__DIR__, 3);
$htdocs = realpath($repoRoot . '/htdocs');
if ($htdocs === false || !is_dir($htdocs)) {
	fwrite(STDERR, "Cannot locate htdocs from " . __DIR__ . "\n");
	exit(1);
}

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', $htdocs);
}
if (!defined('MAIN_DB_PREFIX')) {
	define('MAIN_DB_PREFIX', 'llx_');
}

// Dolibarr syslog levels (values mirror the PHP LOG_* constants Dolibarr uses).
foreach (array('LOG_EMERG' => 0, 'LOG_ALERT' => 1, 'LOG_CRIT' => 2, 'LOG_ERR' => 3,
	'LOG_WARNING' => 4, 'LOG_NOTICE' => 5, 'LOG_INFO' => 6, 'LOG_DEBUG' => 7) as $name => $value) {
	if (!defined($name)) {
		define($name, $value);
	}
}

// Temporary data dir used by the driver to create its .sdb file when a test
// instantiates the real DoliDBSqlite3 driver (connect() uses $main_data_dir).
$GLOBALS['main_data_dir'] = sys_get_temp_dir() . '/sqlite_driver_tests_' . getmypid();
if (!is_dir($GLOBALS['main_data_dir'])) {
	@mkdir($GLOBALS['main_data_dir'], 0755, true);
}
if (!defined('DOL_DATA_ROOT')) {
	define('DOL_DATA_ROOT', $GLOBALS['main_data_dir']);
}

// Minimal $conf consumed by the driver constructor and query() error path.
global $conf;
if (!isset($conf) || !is_object($conf)) {
	$conf = new stdClass();
}
if (!isset($conf->db) || !is_object($conf->db)) {
	$conf->db = new stdClass();
}
if (!isset($conf->global) || !is_object($conf->global)) {
	$conf->global = new stdClass();
}
// Keep the driver's "log again on error" branch quiet but well-defined.
$conf->global->SYSLOG_LEVEL = defined('LOG_DEBUG') ? LOG_DEBUG : 7;

global $dolibarr_main_db_readonly;
$dolibarr_main_db_readonly = 0;

// Minimal dol_syslog stub: silent by default. Set SQLITE_TESTS_VERBOSE=1 to echo.
if (!function_exists('dol_syslog')) {
	/**
	 * Stub for Dolibarr dol_syslog used by the driver under test.
	 *
	 * @param string $message Message to log
	 * @param int    $level   Syslog level
	 * @return void
	 */
	function dol_syslog($message = '', $level = 6)
	{
		if (getenv('SQLITE_TESTS_VERBOSE')) {
			fwrite(STDERR, '[dol_syslog] ' . (is_scalar($message) ? $message : gettype($message)) . "\n");
		}
	}
}

// Load the driver (pulls DoliDB.class.php and Database.interface.php).
require_once DOL_DOCUMENT_ROOT . '/core/db/DoliDB.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/db/sqlite3.class.php';

// Clean the temporary data dir on shutdown.
register_shutdown_function(function () {
	$dir = $GLOBALS['main_data_dir'] ?? null;
	if ($dir && is_dir($dir)) {
		foreach (glob($dir . '/*') ?: array() as $f) {
			@unlink($f);
		}
		@rmdir($dir);
	}
});
