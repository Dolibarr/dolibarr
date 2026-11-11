#!/usr/bin/env php
<?php
/**
 * \file         build/makepack-dolibarrmodule.php
 * \brief        Dolibarr module package builder (tgz, zip, rpm, deb, exe, aps)
  */

// ============================================================================
// Helper functions
// ============================================================================

/**
 * Return ANSI-colored text
 *
 * @param string $text  Text to colorize
 * @param string $color Color name (red, green, yellow, blue, magenta, cyan, white)
 * @return string       Colored text with reset suffix
 */
function colorText(string $text, string $color): string
{
	$colors = [
		'reset'   => "\033[0m",
		'red'     => "\033[31m",
		'green'   => "\033[32m",
		'yellow'  => "\033[33m",
		'blue'    => "\033[34m",
		'magenta' => "\033[35m",
		'cyan'    => "\033[36m",
		'white'   => "\033[37m",
	];
	$code = $colors[$color] ?? $colors['reset'];
	return $code . $text . "\033[0m";
}

/**
 * Execute a shell command and return stdout
 *
 * @param string $cmd Shell command to execute
 * @return string     Command output
 */
function run(string $cmd): string
{
	return shell_exec($cmd) ?? '';
}

/**
 * Read a line from STDIN
 *
 * @param string $message Prompt message to display
 * @return string         User input (trimmed)
 */
function prompt(string $message = ''): string
{
	if ($message !== '') {
		echo $message;
	}
	return trim(fgets(STDIN) ?: '');
}


// ============================================================================
// Configuration
// ============================================================================

$VERSION = '1.0';


// ============================================================================
// MAIN
// ============================================================================

// Detect script directory and name
$scriptPath = realpath($argv[0]) ?: $argv[0];
$DIR = dirname($scriptPath);
$PROG = pathinfo($scriptPath, PATHINFO_FILENAME);
$Extension = pathinfo($scriptPath, PATHINFO_EXTENSION);
$SOURCEDOL = preg_replace('/\/dev/', '', preg_replace('/\/build/', '', $DIR));
$SOURCE = preg_replace('/\/dev/', '', preg_replace('/\/build/', '', getcwd()));

print "----- ".$PROG." - ".$VERSION." -----\n";

if ($SOURCE[0] !== '/' && !preg_match('/^[a-z]:/i', $SOURCE)) {
	echo "Error: Launch the script $PROG.$Extension with its full path from /.\n";
	echo "$PROG.$Extension aborted.\n";
	echo "\n";
	sleep(1);
	exit(1);
}

// Detect OS type
$OS = '';
if (stripos(PHP_OS, 'linux') !== false || (is_dir('/etc') && is_dir('/var') && stripos(PHP_OS, 'cygwin') === false)) {
	$OS = 'linux';
} elseif (is_dir('/etc') && is_dir('/Users')) {
	$OS = 'macosx';
} elseif (stripos(PHP_OS, 'cygwin') !== false || stripos(PHP_OS, 'win') !== false || stripos(PHP_OS, 'msys') !== false) {
	$OS = 'windows';
}

if (!$OS) {
	echo "Error: Can't detect your OS.\n";
	echo "Can't continue.\n";
	echo "$PROG.$Extension aborted.\n";
	echo "\n";
	sleep(1);
	exit(1);
}

// Define buildroot
$TEMP = '';
if ($OS === 'linux' || $OS === 'macosx') {
	$TEMP = getenv('TEMP') ?: (getenv('TMP') ?: '/tmp');
}
if ($OS === 'windows') {
	$TEMP = getenv('TEMP') ?: (getenv('TMP') ?: 'c:/temp');
}

$batch = 0;
$error = 0;

$module = $argv[1];
if (empty($module)) {
	echo "Module name must be provided.\n";
	echo "Can't continue.\n";
	echo "$PROG.$Extension aborted.\n";
	echo "\n";
	sleep(1);
	exit(1);
}

$modulelowercase = strtolower($module);

$pathtofile = $SOURCE.'/'.$modulelowercase;
$DESTI = $SOURCE . '/'.$modulelowercase.'/bin';

print "Build module ".$module.", from ".$pathtofile." into ".$DESTI."\n";


// Zip file to build
$FILENAMEZIP = '';

// Load module
//dol_include_once($pathtofile);
$class = 'mod'.$module;

$pathtodescriptor = $pathtofile.'/core/modules/mod'.$module.'.class.php';
if (!file_exists($pathtodescriptor)) {
	print "Error: Can't find descriptor file ".$pathtodescriptor."\n";
	echo "$PROG.$Extension aborted.\n";
	echo "\n";
	exit(1);
}

//var_dump($SOURCEDOL.'/htdocs');
define('DOL_DOCUMENT_ROOT', $SOURCEDOL.'/htdocs');
include $SOURCEDOL.'/htdocs/master.inc.php';

include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

include_once $pathtodescriptor;

$moduleobj = null;
if (class_exists($class)) {
	try {
		$moduleobj = new $class(null);
		'@phan-var-force DolibarrModules $moduleobj';
		/** @var DolibarrModules $moduleobj */
	} catch (Exception $e) {
		$error++;
		print $e->getMessage();
	}
} else {
	$error++;
	echo "Can't find the class ".$class."\n";
	echo "$PROG.$Extension aborted.\n";
	echo "\n";
	exit(1);
}

$arrayversion = explode('.', $moduleobj->version, 3);
if (count($arrayversion)) {
	$FILENAMEZIP = "module_".$modulelowercase.'-'.$arrayversion[0].(empty($arrayversion[1]) ? '.0' : '.'.$arrayversion[1]).(empty($arrayversion[2]) ? '' : '.'.$arrayversion[2]).'.zip';

	$dirofmodule = $pathtofile;
	$outputfilezip = $DESTI.'/'.$FILENAMEZIP;

	if (!dol_is_dir($dirofmodule)) {
		dol_mkdir($dirofmodule);
	}
	// Note: We exclude /bin/ to not include the already generated zip
	echo "Source dir: $dirofmodule\n";
	echo "Target zip: $outputfilezip\n";
	$result = dol_compress_dir($dirofmodule, $outputfilezip, 'zip', '/\/bin\/|\.git|\.old|\.back|\.ssh/', $modulelowercase);

	if ($result > 0) {
		print "Zip file generated into ".$outputfilezip."\n";
		print "\n";
		exit(1);
	} else {
		$error++;
		print "Error: Failed to build the zip file ".$outputfilezip."\n";
		print "\n";
		exit(1);
	}
} else {
	$error++;
	print "Error: Can't find version in descriptor. Check version is defined\n";
	print "\n";
	exit(1);
}

if (!$batch) {
	prompt("\nPress key to finish...");
}

exit(0);
