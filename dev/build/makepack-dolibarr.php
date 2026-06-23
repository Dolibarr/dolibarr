#!/usr/bin/env php
<?php
/**
 * \file         build/makepack-dolibarr.php
 * \brief        Dolibarr package builder (tgz, zip, rpm, deb, exe, aps)
 * \author       (c)2026 Eric Seigne  <eric.seigne@cap-rel.fr>
 *
 * PHP CLI rewrite of makepack-dolibarr.pl
 *
 * Environment variables you can set to have generated packages moved into a specific dir:
 * DESTIBETARC='/media/HDDATA1_LD/Mes Sites/Web/Dolibarr/dolibarr.org/files/lastbuild'
 * DESTISTABLE='/media/HDDATA1_LD/Mes Sites/Web/Dolibarr/dolibarr.org/files/stable'
 * DESTIMODULES='/media/HDDATA1_LD/Mes Sites/Web/Admin1/wwwroot/files/modules'
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

// Change this to defined target for option 98 and 99
$PROJECT = 'dolibarr';
$PUBLISHBETARC = (getenv('DESTIASSOLOGIN') ?: '') . '@vmprod1.dolibarr.org:/home/dolibarr/asso.dolibarr.org/dolibarr_documents/website/www.dolibarr.org/files';
$PUBLISHSTABLE = (getenv('DESTISFLOGIN') ?: '') . '@frs.sourceforge.net:/home/frs/project/dolibarr';

// Due to implicit origin on git commands
$GITREMOTENAME = getenv('GITREMOTENAME') ?: '';

$LISTETARGET = ['TGZ', 'ZIP', 'RPM_GENERIC', 'RPM_FEDORA', 'RPM_MANDRIVA', 'RPM_OPENSUSE', 'DEB', 'EXEDOLIWAMP', 'SNAPSHOT'];

$REQUIREMENTPUBLISH = [
	'SF'   => 'git ssh rsync',
	'ASSO' => 'git ssh rsync',
];

$REQUIREMENTTARGET = [
	'TGZ'          => 'tar',
	'ZIP'          => '7z',
	'XZ'           => 'xz',
	'RPM_GENERIC'  => 'rpmbuild',
	'RPM_FEDORA'   => 'rpmbuild',
	'RPM_MANDRIVA' => 'rpmbuild',
	'RPM_OPENSUSE' => 'rpmbuild',
	'DEB'          => 'dpkg,po2debconf',
	'FLATPACK'     => 'flatpack',
	'EXEDOLIWAMP'  => 'ISCC.exe',
	'SNAPSHOT'     => 'tar',
];

$ALTERNATEPATH = [
	'7z'           => '7-ZIP',
	'makensis.exe' => 'NSIS',
];

$RPMSUBVERSION = 'auto';
$RPMDIR = (getenv('HOME') ?: '') . '/rpmbuild';	// by default
if (is_dir('/usr/src/redhat')) { $RPMDIR = '/usr/src/redhat'; }   // redhat
if (is_dir('/usr/src/packages')) { $RPMDIR = '/usr/src/packages'; } // opensuse

$VERSION = '4.0';


// ============================================================================
// MAIN
// ============================================================================

// Detect script directory and name
$scriptPath = realpath($argv[0]) ?: $argv[0];
$DIR = dirname($scriptPath);
$PROG = pathinfo($scriptPath, PATHINFO_FILENAME);
$Extension = pathinfo($scriptPath, PATHINFO_EXTENSION);
$SOURCE = preg_replace('/\/dev/', '', preg_replace('/\/build/', '', $DIR));
$DESTI = $SOURCE . '/dev/build';

if ($SOURCE[0] !== '/' && !preg_match('/^[a-z]:/i', $SOURCE)) {
	echo "Error: Launch the script $PROG.$Extension with its full path from /.\n";
	echo "$PROG.$Extension aborted.\n";
	sleep(2);
	exit(1);
}

// Check environment variables
$ENVDESTIBETARC = getenv('DESTIBETARC') ?: '';
$ENVDESTISTABLE = getenv('DESTISTABLE') ?: '';

if (!$ENVDESTIBETARC || !$ENVDESTISTABLE) {
	echo "Error: Missing environment variables.\n";
	echo "You must define the environment variable DESTIBETARC and DESTISTABLE to point to the\ndirectories where you want to save the generated packages.\n";
	echo "$PROG.$Extension aborted.\n";
	echo "\n";
	echo "You can set them with\n";
	echo "On Linux:\n";
	echo "export DESTIBETARC='/tmp'; export DESTISTABLE='/tmp';\n";
	echo "On Windows:\n";
	echo "set DESTIBETARC=c:/tmp\n";
	echo "set DESTISTABLE=c:/tmp\n";
	echo "\n";
	echo "Example: DESTIBETARC='/media/HDDATA1_LD/Mes Sites/Web/Dolibarr/dolibarr.org/files/lastbuild'\n";
	echo "Example: DESTISTABLE='/media/HDDATA1_LD/Mes Sites/Web/Dolibarr/dolibarr.org/files/stable'\n";
	sleep(2);
	exit(1);
}

if (!is_dir($ENVDESTIBETARC) || !is_dir($ENVDESTISTABLE)) {
	echo "Error: Directory of environment variable DESTIBETARC ($ENVDESTIBETARC) or DESTISTABLE ($ENVDESTISTABLE) does not exist.\n";
	echo "$PROG.$Extension aborted.\n";
	sleep(2);
	exit(1);
}

if (!$GITREMOTENAME) {
	echo "Error: environment variable GITREMOTENAME does not exist. You can set it to 'origin' or any other git remote name.\n";
	echo "$PROG.$Extension aborted.\n";
	sleep(2);
	exit(1);
}

// Detect OS type
$OS = '';
$CR = '';
$PROGPATH = '';

if (stripos(PHP_OS, 'linux') !== false || (is_dir('/etc') && is_dir('/var') && stripos(PHP_OS, 'cygwin') === false)) {
	$OS = 'linux';
	$CR = '';
} elseif (is_dir('/etc') && is_dir('/Users')) {
	$OS = 'macosx';
	$CR = '';
} elseif (stripos(PHP_OS, 'cygwin') !== false || stripos(PHP_OS, 'win') !== false || stripos(PHP_OS, 'msys') !== false) {
	$OS = 'windows';
	$CR = "\r";
}

if (!$OS) {
	echo "Error: Can't detect your OS.\n";
	echo "Can't continue.\n";
	echo "$PROG.$Extension aborted.\n";
	sleep(2);
	exit(1);
}

// Define buildroot
$TEMP = '';
if ($OS === 'linux' || $OS === 'macosx') {
	$TEMP = getenv('TEMP') ?: (getenv('TMP') ?: '/tmp');
}
if ($OS === 'windows') {
	$TEMP = getenv('TEMP') ?: (getenv('TMP') ?: 'c:/temp');
	$PROGPATH = getenv('ProgramFiles') ?: '';
}

if (!$TEMP || !is_dir($TEMP)) {
	echo "Error: A temporary directory can not be find.\n";
	echo "Check that TEMP or TMP environment variable is set correctly.\n";
	echo "$PROG.$Extension aborted.\n";
	sleep(2);
	exit(2);
}

$BUILDROOT = $TEMP . '/buildroot';


// Get version $MAJOR, $MINOR and $BUILD
if (file_exists($SOURCE . '/htdocs/version.inc.php')) {
	$filefuncPath = $SOURCE . '/htdocs/version.inc.php';
} else {
	$filefuncPath = $SOURCE . '/htdocs/filefunc.inc.php';
}
$filefuncContent = file_get_contents($filefuncPath);
if ($filefuncContent === false) {
	echo "Error: Can't open descriptor file $filefuncPath\n";
	exit(1);
}

$PROJVERSION = '';
$matches = array();
if (preg_match("/define\('DOL_VERSION',\s*'([\d\.a-z\-]+)'\)/i", $filefuncContent, $matches)) {
	$PROJVERSION = $matches[1];
}
if (empty($PROJVERSION)) {
	if (preg_match("/define\('DOL_MAJOR_VERSION',\s*'([\d\.a-z\-]+)'\)/i", $filefuncContent, $matches)) {
		$DOL_MAJOR_VERSION = $matches[1];
	}
	if (preg_match("/define\('DOL_MINOR_VERSION',\s*'([\d\.a-z\-]+)'\)/i", $filefuncContent, $matches)) {
		$DOL_MINOR_VERSION = $matches[1];
	}
	$PROJVERSION = $DOL_MAJOR_VERSION.'.'.$DOL_MINOR_VERSION;
}

$versionParts = explode('.', $PROJVERSION, 3);
$MAJOR = $versionParts[0] ?? '';
$MINOR = $versionParts[1] ?? '';
$BUILD = $versionParts[2] ?? '';

if ($MINOR === '') {
	echo "Error can't detect version into $filefuncPath\n";
	exit(1);
}


// Set vars for packaging
$FILENAME            = $PROJECT;
$FILENAMESNAPSHOT    = "$PROJECT-snapshot";
$FILENAMETGZ         = "$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEZIP         = "$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEXZ          = "$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEDEB         = 'see later';
$FILENAMEEXEDOLIWAMP = "DoliWamp-$MAJOR.$MINOR.$BUILD";

// For RPM
$ARCH = 'noarch';
$newbuild = $BUILD;
$newbuild = preg_replace('/(dev|alpha)/i', '0.1.a', $newbuild);         // dev (fedora)
$newbuild = preg_replace('/beta(.?)/i', '0.2.beta', $newbuild);         // beta (fedora)
$newbuild = preg_replace('/rc(.?)/i', '0.3.rc', $newbuild);             // rc (fedora)
if (strpos($newbuild, '-') === false) {
	$newbuild .= '-0.4';  // finale (fedora)
}
$REL1 = preg_replace('/-.*$/', '', $newbuild);
if ($RPMSUBVERSION === 'auto') {
	$RPMSUBVERSION = preg_replace('/^.*-/', '', $newbuild);
}
$FILENAMETGZ2    = "$PROJECT-$MAJOR.$MINOR.$REL1";
$FILENAMERPM     = $FILENAMETGZ2 . '-' . $RPMSUBVERSION . '.' . $ARCH . '.rpm';
$FILENAMERPMSRC  = $FILENAMETGZ2 . '-' . $RPMSUBVERSION . '.src.rpm';

// For Deb
$newbuild = $BUILD;
$newbuild = preg_replace('/(dev|alpha)/i', '1', $newbuild);   // dev
$newbuild = preg_replace('/beta(.?)/i', '2', $newbuild);      // beta
$newbuild = preg_replace('/rc(.?)/i', '3', $newbuild);        // rc
if (strpos($newbuild, '-') === false) {
	$newbuild .= '-4';  // finale
}
// now newbuild is 0-1 or 0-4 for example
$build = preg_replace('/-.*$/', '', $newbuild);
// now build is 0 for example
$FILENAMEDEBNATIVE = "{$PROJECT}_{$MAJOR}.{$MINOR}.{$build}";
$FILENAMEDEB       = "{$PROJECT}_{$MAJOR}.{$MINOR}.{$newbuild}";
$FILENAMEDEBSHORT  = "{$PROJECT}_{$MAJOR}.{$MINOR}.{$build}";


// Parse command line arguments
$copyalreadydone = 0;
$batch = 0;
$target = '';
$PREFIX = '';

for ($i = 1; $i < $argc; $i++) {
	$m = array();
	if (preg_match('/^-*target=(\w+)/i', $argv[$i], $m)) {
		$target = $m[1];
		$batch = 1;
	}
	if (preg_match('/^-*desti=(.+)/i', $argv[$i], $m)) {
		$DESTI = $m[1];
	}
	if (preg_match('/^-*prefix=(.+)/i', $argv[$i], $m)) {
		$PREFIX = $m[1];
		$FILENAMESNAPSHOT .= '-' . $PREFIX;
	}
}

// Force output dir if env vars are defined
if ($ENVDESTIBETARC && preg_match('/[a-z]/i', $BUILD)) {
	$DESTI = $ENVDESTIBETARC;
}
if ($ENVDESTISTABLE && preg_match('/^[0-9]+$/', $BUILD)) {
	$DESTI = $ENVDESTISTABLE;
}

// Force target site for publishing if env vars are defined
$envPublishBetarc = getenv('PUBLISHBETARC') ?: '';
$envPublishStable = getenv('PUBLISHSTABLE') ?: '';
if ($envPublishBetarc && preg_match('/[a-z]/i', $BUILD)) {
	$PUBLISHBETARC = $envPublishBetarc;
}
if ($envPublishStable && preg_match('/^[0-9]+$/', $BUILD)) {
	$PUBLISHSTABLE = $envPublishStable;
}

echo "Makepack version $VERSION\n";
echo "Building/publishing package name: $PROJECT\n";
echo "Building/publishing package version: $MAJOR.$MINOR.$BUILD\n";
echo "Source directory (SOURCE): $SOURCE\n";
echo "Target directory (DESTI) : $DESTI\n";


// ============================================================================
// Choose package targets
// ============================================================================

$CHOOSEDTARGET = [];
$CHOOSEDPUBLISH = [];

if ($target) {
	$targetUpper = strtoupper($target);
	if ($targetUpper === 'ALL') {
		foreach ($LISTETARGET as $key) {
			if ($key !== 'SNAPSHOT' && $key !== 'SF' && $key !== 'ASSO') {
				$CHOOSEDTARGET[$key] = 1;
			}
		}
	}
	if ($targetUpper !== 'ALL' && $targetUpper !== 'SF' && $targetUpper !== 'ASSO') {
		$CHOOSEDTARGET[$targetUpper] = 1;
	}
	if ($targetUpper === 'SF') {
		$CHOOSEDPUBLISH['SF'] = 1;
	}
	if ($targetUpper === 'ASSO') {
		$CHOOSEDPUBLISH['ASSO'] = 1;
	}
} else {
	$found = false;
	$NUM_SCRIPT = '';

	while (!$found) {
		$cpt = 0;
		printf(" %2d - %-14s  (%s)\n", $cpt, 'ALL (1..10)', 'Need ' . implode(',', array_values($REQUIREMENTTARGET)));
		$cpt++;
		printf(" %2d - %-14s\n", $cpt, 'Generate check file');
		foreach ($LISTETARGET as $tgt) {
			$cpt++;
			printf(" %2d - %-14s  (%s)\n", $cpt, $tgt, 'Need ' . $REQUIREMENTTARGET[$tgt]);
		}
		$cpt = 98;
		printf(" %2d - %-14s  (%s)\n", $cpt, 'ASSO (publish)', 'Need ' . $REQUIREMENTPUBLISH['ASSO']);
		$cpt = 99;
		printf(" %2d - %-14s  (%s)\n", $cpt, 'SF (publish)', 'Need ' . $REQUIREMENTPUBLISH['SF']);

		$NUM_SCRIPT = prompt("Choose one target number or several separated with space (0 - $cpt): ");

		if (!preg_match('/^[0-9\s]+$/', $NUM_SCRIPT)) {
			echo "This is not a valid package number list.\n";
		} else {
			$found = true;
		}
	}

	echo "\n";

	if ($NUM_SCRIPT === '98') {
		$CHOOSEDPUBLISH['ASSO'] = 1;
	} elseif ($NUM_SCRIPT === '99') {
		$CHOOSEDPUBLISH['SF'] = 1;
	} elseif ($NUM_SCRIPT === '0') {
		$CHOOSEDTARGET['-CHKSUM'] = 1;
		foreach ($LISTETARGET as $key) {
			if ($key !== 'SNAPSHOT' && $key !== 'ASSO' && $key !== 'SF') {
				$CHOOSEDTARGET[$key] = 1;
			}
		}
	} elseif ($NUM_SCRIPT === '1') {
		$CHOOSEDTARGET['-CHKSUM'] = 1;
	} else {
		foreach (preg_split('/\s+/', $NUM_SCRIPT, -1, PREG_SPLIT_NO_EMPTY) as $num) {
			$idx = (int) $num - 2;
			if (isset($LISTETARGET[$idx])) {
				$CHOOSEDTARGET[$LISTETARGET[$idx]] = 1;
			}
		}
	}
}


// ============================================================================
// Test if requirements are ok
// ============================================================================

$atleastonerpm = 0;
ksort($CHOOSEDTARGET);

foreach ($CHOOSEDTARGET as $tgt => $val) {
	if (preg_match('/RPM/i', $tgt)) {
		if ($atleastonerpm && $DESTI === "$SOURCE/dev/build") {
			echo "Error: You asked creation of several rpms. Because all rpm have same name, you must defined an environment variable DESTI to tell packager where it can create subdirs for each generated package.\n";
			exit(1);
		}
		$atleastonerpm = 1;
	}

	if (!isset($REQUIREMENTTARGET[$tgt])) {
		continue;
	}

	foreach (preg_split('/[,\s]+/', $REQUIREMENTTARGET[$tgt], -1, PREG_SPLIT_NO_EMPTY) as $req) {
		echo "Test requirement for target $tgt: Search '$req'... ";

		$newreq = $req;
		$newparam = '';
		if ($newreq === 'zip') { $newparam .= '-h'; }
		if ($newreq === 'xz') { $newparam .= '-h'; }

		$cmd = "\"$newreq\" $newparam 2>&1";
		echo "Test command $cmd... ";

		$outputLines = [];
		exec($cmd, $outputLines, $coderetour);
		$ret = implode("\n", $outputLines);

		if ($coderetour !== 0 && (($coderetour === 1 && $OS === 'windows' && !preg_match('/Usage/i', $ret)) || ($coderetour === 127 && $OS !== 'windows')) && $PROGPATH) {
			// Not found, try in PROGPATH
			$altPath = $ALTERNATEPATH[$req] ?? '';
			$outputLines = [];
			exec("\"$PROGPATH/$altPath/$req\" 2>&1", $outputLines, $coderetour);
			$ret = implode("\n", $outputLines);
			$REQUIREMENTTARGET[$tgt] = "$PROGPATH/$altPath/$req";
		}

		if ($coderetour !== 0 && (($coderetour === 1 && $OS === 'windows' && !preg_match('/Usage/i', $ret)) || ($coderetour === 127 && $OS !== 'windows'))) {
			// Not found error
			echo "Not found\nCan't build target $tgt. Requirement '$req' not found in PATH\n";
			$CHOOSEDTARGET[$tgt] = -1;
			break;
		} else {
			echo " Found $req\n";
		}
	}
}

echo "\n";


// ============================================================================
// Check if there is at least one target to build
// ============================================================================

$nboftargetok = 0;
$nboftargetneedbuildroot = 0;
$nbofpublishneedtag = 0;
$nbofpublishneedchangelog = 0;

ksort($CHOOSEDTARGET);
foreach ($CHOOSEDTARGET as $tgt => $val) {
	if ($tgt === '-CHKSUM') { $nbofpublishneedchangelog++; }
	if ($val < 0) { continue; }
	if ($tgt !== 'EXE' && $tgt !== 'EXEDOLIWAMP' && $tgt !== '-CHKSUM') {
		$nboftargetneedbuildroot++;
	}
	$nboftargetok++;
}

ksort($CHOOSEDPUBLISH);
foreach ($CHOOSEDPUBLISH as $tgt => $val) {
	if ($val < 0) { continue; }
	if ($tgt === 'ASSO') { $nbofpublishneedchangelog++; }
	if ($tgt === 'SF') { $nbofpublishneedchangelog++; $nbofpublishneedtag++; }
	$nboftargetok++;
}


if ($nboftargetok) {
	// ========================================================================
	// Check Changelog
	// ========================================================================

	if ($nbofpublishneedchangelog) {
		$TMPBUILDTOCHECKCHANGELOG = preg_replace('/\-rc\d*/', '', $BUILD);
		$TMPBUILDTOCHECKCHANGELOG = preg_replace('/\-beta\d*/', '', $TMPBUILDTOCHECKCHANGELOG);
		$TMPBUILDTOCHECKCHANGELOG = preg_replace('/\-alpha\d*/', '', $TMPBUILDTOCHECKCHANGELOG);

		echo "\nCheck if ChangeLog is ok for version $MAJOR.$MINOR.$TMPBUILDTOCHECKCHANGELOG\n";
		$ret = run("grep \"ChangeLog for $MAJOR.$MINOR.$TMPBUILDTOCHECKCHANGELOG\" \"$SOURCE/ChangeLog\" 2>&1");

		if (!trim($ret)) {
			echo colorText("Error: The ChangeLogFile was not updated. Run the following command before building package for $MAJOR.$MINOR.$BUILD:\n", 'yellow');
		} else {
			echo "ChangeLog for $MAJOR.$MINOR.$BUILD was found into '$SOURCE/ChangeLog'. But you can regenerate it with command:\n";
		}

		if (!$BUILD || $BUILD === '0-alpha' || $BUILD === '0-beta' || $BUILD === '0-rc') {
			// For a major version
			print "Building changeLog file for a major version:\n";
			//print 'cd ~/git/dolibarr_dev; git log `git rev-list --boundary ' . $MAJOR . '.' . $MINOR . '..origin/develop | grep ^- | cut -c2- | head -n 1`.. --no-merges --pretty=short --oneline | sed -e "s/^[0-9a-z]* //" | grep -e \'^FIX\|NEW\|CLOSE\' | sort -u | sed \'s/FIXED:/FIX:/g\' | sed \'s/FIXED :/FIX:/g\' | sed \'s/FIX :/FIX:/g\' | sed \'s/FIX /FIX: /g\' | sed \'s/CLOSE/NEW/g\' | sed \'s/NEW :/NEW:/g\' | sed \'s/NEW /NEW: /g\' > /tmp/changelogtocopy';
			print 'cd ~/git/dolibarr_dev; git log `diff -u <(git rev-list --first-parent '. ( $MAJOR - 1 ). '.0)  <(git rev-list --first-parent develop) | sed -ne \'s/^ //p\' | head -1`.. --no-merges --pretty=short --oneline | sed -e "s/^[0-9a-z]* //" | grep -e \'^FIX\|NEW\' | sort -u | sed \'s/FIXED:/FIX:/g\' | sed \'s/FIXED :/FIX:/g\' | sed \'s/FIX :/FIX:/g\' | sed \'s/FIX /FIX: /g\' | sed \'s/NEW :/NEW:/g\' | sed \'s/NEW /NEW: /g\' > /tmp/changelogtocopy';
		} else {
			// For a maintenance release
			echo 'cd ~/git/dolibarr_' . $MAJOR . '.' . $MINOR . '; git log ' . $MAJOR . '.' . $MINOR . '.' . (((float) $BUILD) - 1) . '.. | grep -v "Merge branch" | grep -v "Merge pull" | grep "^ " | sed -e "s/^[0-9a-z]* *//" | grep -e \'^FIX\|NEW\|CLOSE\' | sort -u | sed \'s/FIXED:/FIX:/g\' | sed \'s/FIXED :/FIX:/g\' | sed \'s/FIX :/FIX:/g\' | sed \'s/FIX /FIX: /g\' | sed \'s/CLOSE/NEW/g\' | sed \'s/NEW :/NEW:/g\' | sed \'s/NEW /NEW: /g\' > /tmp/changelogtocopy';
		}
		echo "\n";

		if (!trim($ret)) {
			$WAITKEY = prompt("\nPress F to force and continue anyway (or other key to stop)... ");
			if ($WAITKEY !== 'F') {
				echo "Canceled.\n";
				exit(0);
			}
		} else {
			$WAITKEY = prompt("\nPress a key to continue (or CTRL+C to stop)... ");
		}
	}


	// ========================================================================
	// Build xml check file
	// ========================================================================

	if (isset($CHOOSEDTARGET['-CHKSUM']) && $CHOOSEDTARGET['-CHKSUM'] > 0) {
		echo "Go to directory $SOURCE\n";
		$olddir = getcwd();
		chdir($SOURCE);

		echo "Clean $SOURCE/htdocs/includes/autoload.php\n";
		run("rm -f $SOURCE/htdocs/includes/autoload.php");

		$ret = run("git ls-files . --exclude-standard --others");
		if (trim($ret)) {
			echo "Some files exists in source directory and are not indexed neither excluded in .gitignore.\n";
			echo $ret;
			echo "\nCanceled.\n";
			exit(0);
		}

		echo "Create xml check file with hash checksum with command php ".$SOURCE."/dev/build/generate_filelist_xml.php release=$MAJOR.$MINOR.$BUILD\n";
		$outputLines = [];
		$retcode = 0;
		exec("php $SOURCE/dev/build/generate_filelist_xml.php release=$MAJOR.$MINOR.$BUILD", $outputLines, $retcode);
		$ret = implode("\n", $outputLines);
		if ($retcode !== 0) {
			echo "Error running generate_filelist_xml.php please check\n";
			echo $ret;
			echo "\nCanceled.\n";
			exit(0);
		}
		echo $ret . "\n";

		// Copy to final dir
		$NEWDESTI = $DESTI;
		if (!is_dir("$NEWDESTI/signatures")) {
			mkdir("$NEWDESTI/signatures", 0777, true);
		}
		echo "Copy \"$SOURCE/htdocs/install/filelist-$MAJOR.$MINOR.$BUILD.xml\" to $NEWDESTI/signatures/filelist-$MAJOR.$MINOR.$BUILD.xml\n";
		copy("$SOURCE/htdocs/install/filelist-$MAJOR.$MINOR.$BUILD.xml", "$NEWDESTI/signatures/filelist-$MAJOR.$MINOR.$BUILD.xml");
	}


	// ========================================================================
	// Update GIT tag if required
	// ========================================================================

	if ($nbofpublishneedtag) {
		echo "Go to directory $SOURCE\n";
		$olddir = getcwd();
		chdir($SOURCE);

		echo 'Run git tag -a -m "' . $MAJOR . '.' . $MINOR . '.' . $BUILD . '" "' . $MAJOR . '.' . $MINOR . '.' . $BUILD . '"' . "\n";
		$ret = run("git tag -a -m \"$MAJOR.$MINOR.$BUILD\" \"$MAJOR.$MINOR.$BUILD\" 2>&1");

		if (preg_match('/(already exists|existe déjà)/', $ret)) {
			$QUESTIONOVERWRITETAG = prompt("WARNING: Tag $MAJOR.$MINOR.$BUILD already exists. Overwrite (y/N) ? ");
			if (preg_match('/[oy]/i', $QUESTIONOVERWRITETAG)) {
				echo 'Run git tag -a -f -m "' . $MAJOR . '.' . $MINOR . '.' . $BUILD . '" "' . $MAJOR . '.' . $MINOR . '.' . $BUILD . '"' . "\n";
				run("git tag -a -f -m \"$MAJOR.$MINOR.$BUILD\" \"$MAJOR.$MINOR.$BUILD\"");
				echo "Run git push $GITREMOTENAME -f --tags\n";
				run("git push $GITREMOTENAME -f --tags");
			}
		} else {
			echo "Run git push $GITREMOTENAME --tags\n";
			run("git push $GITREMOTENAME --tags");
		}

		chdir($olddir);
	}


	// ========================================================================
	// Update buildroot if required
	// ========================================================================

	if ($nboftargetneedbuildroot) {
		if (!$copyalreadydone) {
			echo "Creation of a buildroot used for all packages\n";

			echo "Delete directory $BUILDROOT\n";
			run("rm -fr \"$BUILDROOT\"");

			@mkdir($BUILDROOT, 0777, true);
			@mkdir("$BUILDROOT/$PROJECT", 0777, true);
			echo "Copy $SOURCE/. into $BUILDROOT/$PROJECT\n";
			run("cp -pr \"$SOURCE/.\" \"$BUILDROOT/$PROJECT\"");
		}

		echo "Clean $BUILDROOT\n";
		run("rm -f  $BUILDROOT/$PROJECT/.buildpath");
		run("rm -fr $BUILDROOT/$PROJECT/.cache");
		run("rm -fr $BUILDROOT/$PROJECT/.codeclimate");
		run("rm -fr $BUILDROOT/$PROJECT/.externalToolBuilders");
		run("rm -fr $BUILDROOT/$PROJECT/.git*");
		run("rm -fr $BUILDROOT/$PROJECT/.mailmap");
		run("rm -fr $BUILDROOT/$PROJECT/.phpunit.result.cache");
		run("rm -fr $BUILDROOT/$PROJECT/.project");
		run("rm -fr $BUILDROOT/$PROJECT/.pydevproject");
		run("rm -fr $BUILDROOT/$PROJECT/.pyproject.toml");
		run("rm -fr $BUILDROOT/$PROJECT/.settings");
		run("rm -fr $BUILDROOT/$PROJECT/.scrutinizer.yml");
		run("rm -fr $BUILDROOT/$PROJECT/.stickler.yml");
		run("rm -fr $BUILDROOT/$PROJECT/.travis.yml");
		run("rm -fr $BUILDROOT/$PROJECT/.tx");
		run("rm -f  $BUILDROOT/$PROJECT/build.xml");

		run("rm -f  $BUILDROOT/$PROJECT/.pre-commit-config.yaml");
		run("rm -fr $BUILDROOT/$PROJECT/.phan");

		run("rm -f  $BUILDROOT/$PROJECT/phpstan.neon");
		run("rm -fr $BUILDROOT/$PROJECT/phpstan.neon.dist");
		run("rm -f  $BUILDROOT/$PROJECT/pom.xml");
		run("rm -f  $BUILDROOT/$PROJECT/README-*.md");

		run("rm -fr $BUILDROOT/$PROJECT/dev/build/html");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/Doli*-*");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr_*.deb");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr_*.dsc");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr_*.tar.gz");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr_*.tar.xz");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr-*.deb");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr-*.rpm");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr-*.tar");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr-*.tar.gz");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr-*.tar.xz");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr-*.tgz");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr-*.xz");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/dolibarr-*.zip");
		run("rm -f  $BUILDROOT/$PROJECT/dev/build/doxygen/doxygen_warnings.log");
		run("rm -fr $BUILDROOT/$PROJECT/dev/build/phpstan/phpstan");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/cache.manifest");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php.mysql");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php.nova*");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php.old");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php.pgsql");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf*sav*");

		run("rm -f  $BUILDROOT/$PROJECT/htdocs/install/mssql/README");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/install/mysql/README");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/install/pgsql/README");

		run("rm -fr $BUILDROOT/$PROJECT/htdocs/install/mssql");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/install/sqlite3");

		run("rm -fr $BUILDROOT/$PROJECT/htdocs/install/install.forced.php");

		run("rm -fr $BUILDROOT/$PROJECT/node_modules");

		run("rm -fr $BUILDROOT/$PROJECT/dev/ansible");
		run("rm -fr $BUILDROOT/$PROJECT/dev/codesniffer");
		run("rm -fr $BUILDROOT/$PROJECT/dev/codetemplates");
		run("rm -fr $BUILDROOT/$PROJECT/dev/examples/ldap");
		run("rm -fr $BUILDROOT/$PROJECT/dev/examples/zapier");
		run("rm -fr $BUILDROOT/$PROJECT/dev/initdata");
		run("rm -fr $BUILDROOT/$PROJECT/dev/initdemo");
		run("rm -fr $BUILDROOT/$PROJECT/dev/resources/dbmodel");
		run("rm -fr $BUILDROOT/$PROJECT/dev/resources/iso-normes");
		run("rm -fr $BUILDROOT/$PROJECT/dev/resources/licence");
		run("rm -fr $BUILDROOT/$PROJECT/dev/mail");
		run("rm -fr $BUILDROOT/$PROJECT/dev/multitail");
		run("rm -fr $BUILDROOT/$PROJECT/dev/phpcheckstyle");
		run("rm -fr $BUILDROOT/$PROJECT/dev/phpunit");
		run("rm -fr $BUILDROOT/$PROJECT/dev/security");
		run("rm -fr $BUILDROOT/$PROJECT/dev/spec");
		run("rm -fr $BUILDROOT/$PROJECT/dev/test");
		run("rm -fr $BUILDROOT/$PROJECT/dev/tools/php-cs-fixer/vendor");
		run("rm -fr $BUILDROOT/$PROJECT/dev/tools/rector/vendor");
		run("rm -fr $BUILDROOT/$PROJECT/dev/uml");
		run("rm -fr $BUILDROOT/$PROJECT/dev/vagrant");
		run("rm -fr $BUILDROOT/$PROJECT/dev/xdebug");
		run("rm -f  $BUILDROOT/$PROJECT/dev/dolibarr_changes.txt");
		run("rm -f  $BUILDROOT/$PROJECT/dev/README");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot2.png");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot3.png");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot4.png");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot5.png");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot6.png");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot7.png");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot8.png");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot9.png");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot10.png");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot11.png");
		run("rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot12.png");

		// Security to avoid to package data files
		echo "Remove documents dir\n";
		run("rm -fr $BUILDROOT/$PROJECT/document");
		run("rm -fr $BUILDROOT/$PROJECT/documents");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/document");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/documents");

		echo "Remove subdir of custom dir\n";
		echo "find $BUILDROOT/$PROJECT/htdocs/custom/* -type d -exec rm -fr {} \\;\n";
		run("find $BUILDROOT/$PROJECT/htdocs/custom/* -type d -exec rm -fr {} \\; >/dev/null 2>&1");
		echo "find $BUILDROOT/$PROJECT/htdocs/custom/* -type l -exec rm -fr {} \\;\n";
		run("find $BUILDROOT/$PROJECT/htdocs/custom/* -type l -exec rm -fr {} \\; >/dev/null 2>&1");

		// Remove known external modules to avoid any error when packaging from env where external modules are tested
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/abricot*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/accountingexport*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/allscreens*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/ancotec*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/cabinetmed*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/calling*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/bootstrap*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/dolimed*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/dolimod*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/factory*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/forceproject*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/lead*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/langs/*/README.md");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/management*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/multicompany*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/ndf*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/nltechno*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/nomenclature*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/of/");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/oscim*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/pos*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/teclib*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/timesheet*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/webmail*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/theme/common/fontawesome-5/svgs");

		// Remove other test files
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/public/test");
		run("rm -fr $BUILDROOT/$PROJECT/test");
		run("rm -fr $BUILDROOT/$PROJECT/Thumbs.db $BUILDROOT/$PROJECT/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/*/Thumbs.db");
		run("rm -f  $BUILDROOT/$PROJECT/.cvsignore $BUILDROOT/$PROJECT/*/.cvsignore $BUILDROOT/$PROJECT/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/*/*/.cvsignore");
		run("rm -f  $BUILDROOT/$PROJECT/.gitignore $BUILDROOT/$PROJECT/*/.gitignore $BUILDROOT/$PROJECT/*/*/.gitignore $BUILDROOT/$PROJECT/*/*/*/.gitignore $BUILDROOT/$PROJECT/*/*/*/*/.gitignore $BUILDROOT/$PROJECT/*/*/*/*/*/.gitignore $BUILDROOT/$PROJECT/*/*/*/*/*/*/.gitignore");

		// Remove files installed by composer
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/geoip/sample*.*");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/bin");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/ckeditor/ckeditor/adapters");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/ckeditor/ckeditor/samples");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/ckeditor/_source");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/composer");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/doctrine");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/jquery/plugins/multiselect/MIT-LICENSE.txt");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/jquery/plugins/select2/release.sh");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/mike42/escpos-php/doc");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/mike42/escpos-php/example");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/mike42/escpos-php/test");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/mobiledetect/mobiledetectlib/.gitmodules");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/mobiledetect/mobiledetectlib/docs");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/nnnick/chartjs/.github");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/nnnick/chartjs/docs");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/nnnick/chartjs/samples");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/nnnick/chartjs/scripts");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/nnnick/chartjs/src");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/nnnick/chartjs/test");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/nusoap/samples");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/php-iban/docs");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/sabre/sabre/*/tests");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/stripe/tests");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/stripe/LICENSE");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/examples");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/fonts/dejavu-fonts-ttf-*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/fonts/freefont-*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/fonts/ae_fonts_*");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/fonts/utils");
		run("rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/tools");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/vendor");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/webmozart");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/autoload.php");

		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/sabre/sabre/bin");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/sabre/sabre/*/bin");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/sabre/sabre/*/*/bin");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/sabre/sabre/*/*/*/bin");
		run("rm -f  $BUILDROOT/$PROJECT/htdocs/includes/sabre/sabre/*/*/*/*/bin");
	}


	// ========================================================================
	// Build package for each target
	// ========================================================================

	ksort($CHOOSEDTARGET);
	foreach ($CHOOSEDTARGET as $tgt => $val) {
		if ($val < 0) { continue; }
		if ($tgt === '-CHKSUM') { continue; }

		echo "\nBuild package for target $tgt\n";


		// --- SNAPSHOT ---
		if ($tgt === 'SNAPSHOT') {
			$NEWDESTI = $DESTI;

			echo "Remove target $FILENAMESNAPSHOT.tgz...\n";
			@unlink("$NEWDESTI/$FILENAMESNAPSHOT.tgz");

			run("rm -fr $BUILDROOT/$FILENAMESNAPSHOT");
			echo "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMESNAPSHOT\n";
			$cmd = "cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$FILENAMESNAPSHOT\"";
			run($cmd);

			echo "Compress $BUILDROOT into $FILENAMESNAPSHOT.tgz...\n";
			$cmd = "tar --exclude doli*.tgz --exclude doli*.deb --exclude doli*.exe --exclude doli*.xz --exclude doli*.zip --exclude doli*.rpm --exclude .cache --exclude .settings --exclude conf.php --exclude conf.php.mysql --exclude conf.php.old --exclude conf.php.postgres --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$FILENAMESNAPSHOT.tgz\" $FILENAMESNAPSHOT";
			echo $cmd . "\n";
			run($cmd);

			// Move to final dir
			echo "Move $FILENAMESNAPSHOT.tgz to $NEWDESTI/$FILENAMESNAPSHOT.tgz\n";
			run("mv \"$FILENAMESNAPSHOT.tgz\" \"$NEWDESTI/$FILENAMESNAPSHOT.tgz\"");
			continue;
		}


		// --- TGZ ---
		if ($tgt === 'TGZ') {
			$NEWDESTI = $DESTI;
			if (preg_match('/stable/', $NEWDESTI)) {
				@mkdir($DESTI . '/standard');
				if (is_dir($DESTI . '/standard')) { $NEWDESTI = $DESTI . '/standard'; }
			}

			echo "Remove target $FILENAMETGZ.tgz...\n";
			@unlink("$NEWDESTI/$FILENAMETGZ.tgz");

			run("rm -fr $BUILDROOT/$FILENAMETGZ");
			echo "Copy $BUILDROOT/$PROJECT/ to $BUILDROOT/$FILENAMETGZ\n";
			$cmd = "cp -pr \"$BUILDROOT/$PROJECT/\" \"$BUILDROOT/$FILENAMETGZ\"";
			run($cmd);

			run("rm -fr $BUILDROOT/$FILENAMETGZ/dev/build/exe");
			run("rm -fr $BUILDROOT/$FILENAMETGZ/htdocs/includes/ckeditor/_source");

			echo "Compress $FILENAMETGZ into $FILENAMETGZ.tgz...\n";
			$cmd = "tar --exclude-vcs --exclude-from \"$BUILDROOT/$PROJECT/dev/build/tgz/tar_exclude.txt\" --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$BUILDROOT/$FILENAMETGZ.tgz\" $FILENAMETGZ";
			echo "$cmd\n";
			run($cmd);

			// Move to final dir
			echo "Move $BUILDROOT/$FILENAMETGZ.tgz to $NEWDESTI/$FILENAMETGZ.tgz\n";
			run("mv \"$BUILDROOT/$FILENAMETGZ.tgz\" \"$NEWDESTI/$FILENAMETGZ.tgz\"");
			continue;
		}


		// --- XZ ---
		if ($tgt === 'XZ') {
			$NEWDESTI = $DESTI;
			if (preg_match('/stable/', $NEWDESTI)) {
				@mkdir($DESTI . '/standard');
				if (is_dir($DESTI . '/standard')) { $NEWDESTI = $DESTI . '/standard'; }
			}

			echo "Remove target $FILENAMEXZ.xz...\n";
			@unlink("$NEWDESTI/$FILENAMEXZ.xz");

			run("rm -fr $BUILDROOT/$FILENAMEXZ");
			echo "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMEXZ\n";
			$cmd = "cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$FILENAMEXZ\"";
			run($cmd);

			run("rm -fr $BUILDROOT/$FILENAMEXZ/dev/build/exe");
			run("rm -fr $BUILDROOT/$FILENAMEXZ/htdocs/includes/ckeditor/_source");

			echo "Compress $FILENAMEXZ into $FILENAMEXZ.xz...\n";

			echo "Go to directory $BUILDROOT\n";
			$olddir = getcwd();
			chdir($BUILDROOT);
			$cmd = "xz -9 -r $BUILDROOT/$FILENAMEXZ.xz *";
			echo $cmd . "\n";
			run($cmd);
			chdir($olddir);

			// Move to final dir
			echo "Move $FILENAMEXZ.xz to $NEWDESTI/$FILENAMEXZ.xz\n";
			run("mv \"$BUILDROOT/$FILENAMEXZ.xz\" \"$NEWDESTI/$FILENAMEXZ.xz\"");
			continue;
		}


		// --- ZIP ---
		if ($tgt === 'ZIP') {
			$NEWDESTI = $DESTI;
			if (preg_match('/stable/', $NEWDESTI)) {
				@mkdir($DESTI . '/standard');
				if (is_dir($DESTI . '/standard')) { $NEWDESTI = $DESTI . '/standard'; }
			}

			echo "Remove target $FILENAMEZIP.zip...\n";
			@unlink("$NEWDESTI/$FILENAMEZIP.zip");

			run("rm -fr $BUILDROOT/$FILENAMEZIP");
			echo "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMEZIP\n";
			$cmd = "cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$FILENAMEZIP\"";
			run($cmd);

			run("rm -fr $BUILDROOT/$FILENAMEZIP/dev/build/exe");
			run("rm -fr $BUILDROOT/$FILENAMEZIP/htdocs/includes/ckeditor/_source");

			echo "Compress $FILENAMEZIP into $FILENAMEZIP.zip...\n";

			echo "Go to directory $BUILDROOT\n";
			$olddir = getcwd();
			chdir($BUILDROOT);
			$cmd = "7z a -r -tzip -xr@\"$BUILDROOT/$FILENAMEZIP/dev/build/zip/zip_exclude.txt\" -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMEZIP/*";
			echo $cmd . "\n";
			run($cmd);
			chdir($olddir);

			// Move to final dir
			echo "Move $FILENAMEZIP.zip to $NEWDESTI/$FILENAMEZIP.zip\n";
			run("mv \"$BUILDROOT/$FILENAMEZIP.zip\" \"$NEWDESTI/$FILENAMEZIP.zip\"");
			continue;
		}


		// --- RPM ---
		if (preg_match('/RPM/', $tgt)) {
			$NEWDESTI = $DESTI;
			$subdir = 'package_rpm_generic';
			if (preg_match('/FEDO/i', $tgt)) { $subdir = 'package_rpm_redhat-fedora'; }
			if (preg_match('/MAND/i', $tgt)) { $subdir = 'package_rpm_mandriva'; }
			if (preg_match('/OPEN/i', $tgt)) { $subdir = 'package_rpm_opensuse'; }
			if (preg_match('/stable/', $NEWDESTI)) {
				@mkdir($DESTI . '/' . $subdir);
				if (is_dir($DESTI . '/' . $subdir)) { $NEWDESTI = $DESTI . '/' . $subdir; }
			}

			if ($RPMDIR === '') { $RPMDIR = (getenv('HOME') ?: '') . '/rpmbuild'; }

			echo "Version is $MAJOR.$MINOR.$REL1-$RPMSUBVERSION\n";
			echo "RPMDIR = $RPMDIR\n";

			echo "Remove target $FILENAMERPM...\n";
			@unlink("$NEWDESTI/$FILENAMERPM");
			echo "Remove target $FILENAMERPMSRC...\n";
			@unlink("$NEWDESTI/$FILENAMERPMSRC");

			echo "Create directory $BUILDROOT/$FILENAMETGZ2\n";
			run("rm -fr $BUILDROOT/$FILENAMETGZ2");

			echo "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMETGZ2\n";
			$cmd = "cp -pr '$BUILDROOT/$PROJECT' '$BUILDROOT/$FILENAMETGZ2'";
			run($cmd);

			echo "Set permissions on files/dir\n";
			run("chmod -R 755 $BUILDROOT/$FILENAMETGZ2");
			$cmd = "find $BUILDROOT/$FILENAMETGZ2 -type f -exec chmod 644 {} \\; ";
			run($cmd);

			// Build tgz
			echo "Compress $FILENAMETGZ2 into $FILENAMETGZ2.tgz...\n";
			run("tar --exclude-from \"$SOURCE/dev/build/tgz/tar_exclude.txt\" --directory \"$BUILDROOT\" -czvf \"$BUILDROOT/$FILENAMETGZ2.tgz\" $FILENAMETGZ2");

			if (!is_dir($RPMDIR . '/SOURCES')) { @mkdir($RPMDIR . '/SOURCES', 0777, true); }
			echo "Move $BUILDROOT/$FILENAMETGZ2.tgz to $RPMDIR/SOURCES/$FILENAMETGZ2.tgz\n";
			$cmd = "mv $BUILDROOT/$FILENAMETGZ2.tgz $RPMDIR/SOURCES/$FILENAMETGZ2.tgz";
			run($cmd);

			$BUILDFIC = $FILENAME . '.spec';
			$BUILDFICSRC = $FILENAME . '_generic.spec';
			if (preg_match('/FEDO/i', $tgt)) { $BUILDFICSRC = $FILENAME . '_fedora.spec'; }
			if (preg_match('/MAND/i', $tgt)) { $BUILDFICSRC = $FILENAME . '_mandriva.spec'; }
			if (preg_match('/OPEN/i', $tgt)) { $BUILDFICSRC = $FILENAME . '_opensuse.spec'; }

			$day = str_pad(date('j'), 2, ' ', STR_PAD_LEFT);
			$datestring = date('D M ') . $day . date(' Y');
			$changelogstring = "* $datestring Laurent Destailleur (eldy) $MAJOR.$MINOR.$REL1-$RPMSUBVERSION\n- Upstream release\n";

			echo "Generate file $BUILDROOT/$BUILDFIC from $SOURCE/dev/build/rpm/$BUILDFICSRC\n";
			$specContent = file_get_contents("$SOURCE/dev/build/rpm/$BUILDFICSRC");
			if ($specContent === false) {
				echo "Error: Can't read $SOURCE/dev/build/rpm/$BUILDFICSRC\n";
				exit(1);
			}
			$specContent = str_replace('__FILENAMETGZ__', $FILENAMETGZ, $specContent);
			$specContent = str_replace('__VERSION__', "$MAJOR.$MINOR.$REL1", $specContent);
			$specContent = str_replace('__RELEASE__', $RPMSUBVERSION, $specContent);
			$specContent = str_replace('__CHANGELOGSTRING__', $changelogstring, $specContent);
			file_put_contents("$BUILDROOT/$BUILDFIC", $specContent);

			echo "Copy patch file to $RPMDIR/SOURCES\n";
			run("cp \"$SOURCE/dev/build/rpm/dolibarr-forrpm.patch\" \"$RPMDIR/SOURCES\"");
			run("chmod 644 $RPMDIR/SOURCES/dolibarr-forrpm.patch");

			echo "Launch RPM build (rpmbuild --clean -ba $BUILDROOT/$BUILDFIC)\n";
			run("rpmbuild --clean -ba $BUILDROOT/$BUILDFIC");

			// Move to final dir
			echo "Move $RPMDIR/RPMS/$ARCH/{$FILENAMETGZ2}-{$RPMSUBVERSION}*.{$ARCH}.rpm into $NEWDESTI/{$FILENAMETGZ2}-{$RPMSUBVERSION}*.{$ARCH}.rpm\n";
			$cmd = "mv $RPMDIR/RPMS/$ARCH/{$FILENAMETGZ2}-{$RPMSUBVERSION}*.{$ARCH}.rpm \"$NEWDESTI/\"";
			run($cmd);
			echo "Move $RPMDIR/SRPMS/{$FILENAMETGZ2}-{$RPMSUBVERSION}*.src.rpm into $NEWDESTI/{$FILENAMETGZ2}-{$RPMSUBVERSION}*.src.rpm\n";
			$cmd = "mv $RPMDIR/SRPMS/{$FILENAMETGZ2}-{$RPMSUBVERSION}*.src.rpm \"$NEWDESTI/\"";
			run($cmd);
			echo "Move $RPMDIR/SOURCES/$FILENAMETGZ2.tgz into $NEWDESTI/$FILENAMETGZ2.tgz\n";
			// This line was commented out in original: $cmd="mv ..."; $ret=`$cmd`;
			continue;
		}


		// --- DEB ---
		if ($tgt === 'DEB') {
			$NEWDESTI = $DESTI;
			if (preg_match('/stable/', $NEWDESTI)) {
				@mkdir($DESTI . '/package_debian-ubuntu');
				if (is_dir($DESTI . '/package_debian-ubuntu')) { $NEWDESTI = $DESTI . '/package_debian-ubuntu'; }
			}

			$olddir = getcwd();

			echo "Remove target ${FILENAMEDEB}_all.deb...\n";
			@unlink("$NEWDESTI/${FILENAMEDEB}_all.deb");
			echo "Remove target ${FILENAMEDEB}.dsc...\n";
			@unlink("$NEWDESTI/${FILENAMEDEB}.dsc");
			echo "Remove target ${FILENAMEDEB}.tar.gz...\n";
			@unlink("$NEWDESTI/${FILENAMEDEB}.tar.gz");
			echo "Remove target ${FILENAMEDEB}.changes...\n";
			@unlink("$NEWDESTI/${FILENAMEDEB}.changes");
			echo "Remove target ${FILENAMEDEB}.debian.tar.gz...\n";
			@unlink("$NEWDESTI/${FILENAMEDEB}.debian.tar.gz");
			echo "Remove target ${FILENAMEDEB}.debian.tar.xz...\n";
			@unlink("$NEWDESTI/${FILENAMEDEB}.debian.tar.xz");
			echo "Remove target ${FILENAMEDEBNATIVE}.orig.tar.gz...\n";
			@unlink("$NEWDESTI/${FILENAMEDEBNATIVE}.orig.tar.gz");

			run("rm -fr $BUILDROOT/$PROJECT.tmp");
			run("rm -fr $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build");

			echo "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$PROJECT.tmp\n";
			$cmd = "cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$PROJECT.tmp\"";
			run($cmd);
			$cmd = "cp -pr \"$BUILDROOT/$PROJECT/dev/build/debian/apache/.htaccess\" \"$BUILDROOT/$PROJECT.tmp/dev/build/debian/apache/.htaccess\"";
			run($cmd);

			echo "Remove other files\n";
			run("rm -f  $BUILDROOT/$PROJECT.tmp/README-FR.md");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/README");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/README-FR");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/aps");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/dmg");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/pad/README");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/tgz/README");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/debian/po");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/debian/source");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/changelog");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/compat");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/control*");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/copyright");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/dolibarr.config");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/dolibarr.desktop");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/dolibarr.docs");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/dolibarr.install");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/dolibarr.lintian-overrides");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/dolibarr.postrm");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/dolibarr.postinst");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/dolibarr.templates");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/dolibarr.templates.futur");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/rules");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/README.Debian");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/README.howto");
			run("rm -f  $BUILDROOT/$PROJECT.tmp/dev/build/debian/watch");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/doap");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/exe");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/launchpad");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/live");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/patch");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/perl");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/rpm");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/dev/build/zip");
			// Remove duplicate license files
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/_source/LICENSE.md");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/_source/plugins/scayt/LICENSE.md");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/_source/plugins/wsc/LICENSE.md");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/LICENSE.md");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/plugins/scayt/LICENSE.md");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/plugins/wsc/LICENSE.md");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/php-iban/LICENSE");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/flot/LICENSE.txt");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/datatables/extensions/ColReorder/License.txt");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/datatables/extensions/ColVis/License.txt");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/datatables/extensions/FixedColumns/License.txt");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/datatables/extensions/Responsive/License.txt");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/datatables/license.txt");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/select2/LICENSE");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/mike42/escpos-php/LICENSE.md");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/mobiledetect/mobiledetectlib/LICENSE.txt");

			// Remove files we don't need (already removed)
			run("rm -fr $BUILDROOT/$PROJECT.tmp/.codeclimate.yml");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/.pre-commit-config.yaml");
			run("rm -fr $BUILDROOT/$PROJECT.tmp/.vscode");
			run("find $BUILDROOT/$PROJECT.tmp/ -type f -name '.editorconfig' -exec rm {} \\;");
			run("find $BUILDROOT/$PROJECT.tmp/ -type f -name '.travis.yml' -exec rm {} \\;");

			// Rename upstream changelog to match debian rules
			run("mv $BUILDROOT/$PROJECT.tmp/ChangeLog $BUILDROOT/$PROJECT.tmp/changelog");

			// Prepare source package (init debian dir)
			echo "Create directory $BUILDROOT/$PROJECT.tmp/debian\n";
			run("mkdir \"$BUILDROOT/$PROJECT.tmp/debian\"");

			echo "Copy $SOURCE/dev/build/debian/xxx to $BUILDROOT/$PROJECT.tmp/debian\n";

			// Add files for dpkg-source (changelog)
			$changelogContent = file_get_contents("$SOURCE/dev/build/debian/changelog");
			if ($changelogContent === false) {
				echo "Error: Can't read $SOURCE/dev/build/debian/changelog\n";
				exit(1);
			}
			$changelogContent = str_replace('__VERSION__', "$MAJOR.$MINOR.$newbuild", $changelogContent);
			file_put_contents("$BUILDROOT/$PROJECT.tmp/debian/changelog", $changelogContent);

			// Add files for dpkg-source
			run("cp -f  \"$SOURCE/dev/build/debian/compat\"         \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/control\"        \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/copyright\"      \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/dolibarr.desktop\"        \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/dolibarr.docs\"           \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/dolibarr.install\"        \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/dolibarr.lintian-overrides\"  \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/dolibarr.xpm\"            \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/rules\"          \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/watch\"          \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -fr \"$SOURCE/dev/build/debian/patches\"        \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -fr \"$SOURCE/dev/build/debian/po\"             \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -fr \"$SOURCE/dev/build/debian/source\"         \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -fr \"$SOURCE/dev/build/debian/apache\"         \"$BUILDROOT/$PROJECT.tmp/debian/apache\"");
			run("cp -f  \"$SOURCE/dev/build/debian/apache/.htaccess\" \"$BUILDROOT/$PROJECT.tmp/debian/apache\"");
			run("cp -fr \"$SOURCE/dev/build/debian/lighttpd\"       \"$BUILDROOT/$PROJECT.tmp/debian/lighttpd\"");
			// Add files also required to build binary package
			run("cp -f  \"$SOURCE/dev/build/debian/dolibarr.config\"         \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/dolibarr.postinst\"       \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/dolibarr.postrm\"         \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/dolibarr.templates\"      \"$BUILDROOT/$PROJECT.tmp/debian\"");
			run("cp -f  \"$SOURCE/dev/build/debian/install.forced.php.install\"      \"$BUILDROOT/$PROJECT.tmp/debian\"");

			echo "Set permissions on files/dir\n";
			run("chmod -R 755 $BUILDROOT/$PROJECT.tmp");
			run("find $BUILDROOT/$PROJECT.tmp -type f -exec chmod 644 {} \\; ");
			run("find $BUILDROOT/$PROJECT.tmp/dev/build -name '*.php' -type f -exec chmod 755 {} \\; ");
			run("find $BUILDROOT/$PROJECT.tmp/dev/build -name '*.dpatch' -type f -exec chmod 755 {} \\; ");
			run("find $BUILDROOT/$PROJECT.tmp/dev/build -name '*.pl' -type f -exec chmod 755 {} \\; ");
			run("find $BUILDROOT/$PROJECT.tmp/dev -name '*.php' -type f -exec chmod 755 {} \\; ");
			run("chmod 755 $BUILDROOT/$PROJECT.tmp/debian/rules");
			run("chmod -R 644 $BUILDROOT/$PROJECT.tmp/dev/translation/autotranslator.class.php");
			run("chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/class/actions_mymodule.class.php");
			run("chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/class/api_mymodule.class.php");
			run("chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/class/myobject.class.php");
			run("chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/core/modules/modMyModule.class.php");
			run("chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/mymoduleindex.php");
			run("chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/myobject_card.php");
			run("chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/myobject_list.php");
			run("chmod -R 755 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/scripts/mymodule.php");
			run("find $BUILDROOT/$PROJECT.tmp/scripts -name '*.php' -type f -exec chmod 755 {} \\; ");
			run("find $BUILDROOT/$PROJECT.tmp/scripts -name '*.sh' -type f -exec chmod 755 {} \\; ");

			echo "Rename directory $BUILDROOT/$PROJECT.tmp into $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build\n";
			$cmd = "mv $BUILDROOT/$PROJECT.tmp $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build";
			run($cmd);

			echo "Go into directory $BUILDROOT\n";
			chdir($BUILDROOT);

			// We need a tarball to be able to build "quilt" debian package
			echo "Compress $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build into $BUILDROOT/$FILENAMEDEBNATIVE.orig.tar.gz...\n";
			$cmd = "tar --exclude-vcs --exclude-from \"$BUILDROOT/$PROJECT/dev/build/tgz/tar_exclude.txt\" --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$BUILDROOT/$FILENAMEDEBNATIVE.orig.tar.gz\" $PROJECT-$MAJOR.$MINOR.$build";
			echo $cmd . "\n";
			run($cmd);

			// Creation of source package
			echo "Go into directory $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build\n";
			chdir("$BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build");
			$cmd = "dpkg-buildpackage -us -uc --compression=gzip";
			echo "Launch DEB build ($cmd)\n";
			$ret = run("$cmd 2>&1");
			echo $ret . "\n";

			chdir($olddir);

			echo "You can check bin package with lintian --pedantic -E -I \"$NEWDESTI/${FILENAMEDEB}_all.deb\"\n";
			echo "You can check src package with lintian --pedantic -E -I \"$NEWDESTI/${FILENAMEDEB}.dsc\"\n";

			// Move to final dir
			echo "Move *_all.deb *.dsc *.orig.tar.gz *.changes to $NEWDESTI\n";
			run("mv $BUILDROOT/*_all.deb \"$NEWDESTI/\"");
			run("mv $BUILDROOT/*.dsc \"$NEWDESTI/\"");
			run("mv $BUILDROOT/*.orig.tar.gz \"$NEWDESTI/\"");
			run("mv $BUILDROOT/*.debian.tar.gz \"$NEWDESTI/\"");
			run("mv $BUILDROOT/*.changes \"$NEWDESTI/\"");
			continue;
		}


		// --- EXEDOLIWAMP ---
		if ($tgt === 'EXEDOLIWAMP') {
			$NEWDESTI = $DESTI;
			if (preg_match('/stable/', $NEWDESTI)) {
				@mkdir($DESTI . '/package_windows');
				if (is_dir($DESTI . '/package_windows')) { $NEWDESTI = $DESTI . '/package_windows'; }
			}

			echo "Remove target $NEWDESTI/$FILENAMEEXEDOLIWAMP.exe...\n";
			@unlink("$NEWDESTI/$FILENAMEEXEDOLIWAMP.exe");

			if ($OS === 'windows') {
				echo "Check that ISCC.exe is in your PATH.\n";
			} else {
				echo "Check that in your Wine setup, you have created a Z: drive that point to your / directory.\n";
			}

			$SOURCEBACK = str_replace('/', '\\', $SOURCE);

			echo "Prepare file \"$SOURCEBACK\\dev\\build\\exe\\doliwamp\\doliwamp.tmp.iss\" from \"$SOURCEBACK\\build\\exe\\doliwamp\\doliwamp.iss\"\n";

			$issContent = file_get_contents("$SOURCE/dev/build/exe/doliwamp/doliwamp.iss");
			if ($issContent === false) {
				echo "Error: Can't read $SOURCE/dev/build/exe/doliwamp/doliwamp.iss\n";
				exit(1);
			}
			$issContent = str_replace('__FILENAMEEXEDOLIWAMP__', $FILENAMEEXEDOLIWAMP, $issContent);
			file_put_contents("$SOURCE/dev/build/exe/doliwamp/doliwamp.tmp.iss", $issContent);

			echo "Compil exe $FILENAMEEXEDOLIWAMP.exe file from iss file \"$SOURCEBACK\\build\\exe\\doliwamp\\doliwamp.tmp.iss\" on OS $OS\n";

			$cmd = '';
			if ($OS === 'windows') {
				$cmd = "ISCC.exe \"$SOURCEBACK\\dev\\build\\exe\\doliwamp\\doliwamp.tmp.iss\"";
			}
			if ($cmd) {
				echo "$cmd\n";
				$ret = run($cmd);
				echo "ret=$ret\n";
			}

			// Move to final dir
			echo "Move \"$SOURCE/dev/build/$FILENAMEEXEDOLIWAMP.exe\" to $NEWDESTI/$FILENAMEEXEDOLIWAMP.exe\n";
			@rename("$SOURCE/dev/build/$FILENAMEEXEDOLIWAMP.exe", "$NEWDESTI/$FILENAMEEXEDOLIWAMP.exe");

			echo "Remove tmp file $SOURCE/dev/build/exe/doliwamp/doliwamp.tmp.iss\n";
			@unlink("$SOURCE/dev/build/exe/doliwamp/doliwamp.tmp.iss");

			continue;
		}
	}


	// ========================================================================
	// Publish package for each target
	// ========================================================================

	ksort($CHOOSEDPUBLISH);
	foreach ($CHOOSEDPUBLISH as $tgt => $val) {
		if ($val < 0) { continue; }

		echo "\nList of files to publish (BUILD=$BUILD)\n";

		if ($tgt === 'ASSO' && preg_match('/[a-z]/i', $BUILD)) {
			// Not stable
			$filestoscansf = [
				"$DESTI/$FILENAMERPM"                  => 'Dolibarr installer for Fedora-Redhat-Mandriva-Opensuse (DoliRpm)',
				"$DESTI/${FILENAMEDEB}_all.deb"         => 'Dolibarr installer for Debian-Ubuntu (DoliDeb)',
				"$DESTI/$FILENAMEEXEDOLIWAMP.exe"       => 'Dolibarr installer for Windows (DoliWamp)',
				"$DESTI/$FILENAMETGZ.tgz"              => 'Dolibarr ERP-CRM',
				"$DESTI/$FILENAMETGZ.zip"              => 'Dolibarr ERP-CRM',
			];
			$filestoscanstableasso = [
				"$DESTI/$FILENAMERPM"                  => '',
				"$DESTI/${FILENAMEDEB}_all.deb"         => '',
				"$DESTI/$FILENAMEEXEDOLIWAMP.exe"       => '',
				"$DESTI/$FILENAMETGZ.tgz"              => '',
				"$DESTI/$FILENAMETGZ.zip"              => '',
			];
		} else {
			$filestoscansf = [
				"$DESTI/signatures/filelist-$MAJOR.$MINOR.$BUILD.xml"            => 'none',
				"$DESTI/package_rpm_generic/$FILENAMERPM"                         => 'Dolibarr installer for Fedora-Redhat-Mandriva-Opensuse (DoliRpm)',
				"$DESTI/package_rpm_generic/$FILENAMERPMSRC"                      => 'none',
				"$DESTI/package_debian-ubuntu/${FILENAMEDEB}_all.deb"             => 'Dolibarr installer for Debian-Ubuntu (DoliDeb)',
				"$DESTI/package_debian-ubuntu/${FILENAMEDEB}_amd64.changes"       => 'none',
				"$DESTI/package_debian-ubuntu/${FILENAMEDEB}.dsc"                 => 'none',
				"$DESTI/package_debian-ubuntu/${FILENAMEDEB}.debian.tar.gz"       => 'none',
				"$DESTI/package_debian-ubuntu/${FILENAMEDEBSHORT}.orig.tar.gz"    => 'none',
				"$DESTI/package_windows/$FILENAMEEXEDOLIWAMP.exe"                => 'Dolibarr installer for Windows (DoliWamp)',
				"$DESTI/standard/$FILENAMETGZ.tgz"                               => 'Dolibarr ERP-CRM',
				"$DESTI/standard/$FILENAMETGZ.zip"                               => 'Dolibarr ERP-CRM',
			];
			$filestoscanstableasso = [
				"$DESTI/signatures/filelist-$MAJOR.$MINOR.$BUILD.xml"            => 'signatures',
				"$DESTI/package_rpm_generic/$FILENAMERPM"                         => 'package_rpm_generic',
				"$DESTI/package_rpm_generic/$FILENAMERPMSRC"                      => 'package_rpm_generic',
				"$DESTI/package_debian-ubuntu/${FILENAMEDEB}_all.deb"             => 'package_debian-ubuntu',
				"$DESTI/package_debian-ubuntu/${FILENAMEDEB}_amd64.changes"       => 'package_debian-ubuntu',
				"$DESTI/package_debian-ubuntu/${FILENAMEDEB}.dsc"                 => 'package_debian-ubuntu',
				"$DESTI/package_debian-ubuntu/${FILENAMEDEB}.debian.tar.gz"       => 'package_debian-ubuntu',
				"$DESTI/package_debian-ubuntu/${FILENAMEDEBSHORT}.orig.tar.gz"    => 'package_debian-ubuntu',
				"$DESTI/package_windows/$FILENAMEEXEDOLIWAMP.exe"                => 'package_windows',
				"$DESTI/standard/$FILENAMETGZ.tgz"                               => 'standard',
				"$DESTI/standard/$FILENAMETGZ.zip"                               => 'standard',
			];
		}

		ksort($filestoscansf);
		foreach ($filestoscansf as $file => $label) {
			$filesize = @filesize($file);
			$filedate = @filemtime($file);
			echo $file . ' ' . ($filesize ? '(found)' : '(not found)');
			if ($filesize) { echo ' - ' . $filesize; }
			if ($filedate) { echo ' - ' . date('Y-m-d H:i:s', $filedate); }
			echo "\n";
		}

		if ($tgt === 'SF' || $tgt === 'ASSO') {
			echo "\n";

			$PUBLISH = '';
			if ($tgt === 'SF') { $PUBLISH = $PUBLISHSTABLE; }
			if ($tgt === 'ASSO' && preg_match('/[a-z]/i', $BUILD)) { $PUBLISH = $PUBLISHBETARC . '/lastbuild'; }
			if ($tgt === 'ASSO' && preg_match('/^[0-9]+$/', $BUILD)) { $PUBLISH = $PUBLISHBETARC . '/stable'; }

			$NEWPUBLISH = $PUBLISH;
			prompt("Publish to target $NEWPUBLISH. Click enter or CTRL+C...\n");

			echo "Create empty dir /tmp/emptydir. We need it to create target dir using rsync.\n";
			run("mkdir -p \"/tmp/emptydir/\"");

			$filestoscan = $filestoscansf;
			ksort($filestoscan);

			foreach ($filestoscan as $file => $label) {
				$filesize = @filesize($file);
				if (!$filesize) { continue; }

				if ($tgt === 'SF') {
					if ($label === 'none') {
						continue;
					}
					$destFolder = "$NEWPUBLISH/$label/$MAJOR.$MINOR.$BUILD";
				} elseif ($tgt === 'ASSO' && preg_match('/stable/', $NEWPUBLISH)) {
					$destFolder = "$NEWPUBLISH/" . ($filestoscanstableasso[$file] ?? '');
				} elseif ($tgt === 'ASSO' && !preg_match('/stable/', $NEWPUBLISH)) {
					$destFolder = $NEWPUBLISH;
				} else {
					// No more used
					$dirnameonly = preg_replace('/.*\/([^\/]+)\/[^\/]+$/', '$1', $file);
					$destFolder = "$NEWPUBLISH/$dirnameonly";
				}

				echo "\n";
				echo "Publish file $file to $destFolder\n";

				$command = "rsync -s -e 'ssh' --recursive /tmp/emptydir/ \"$destFolder\"";
				echo "$command\n";
				run($command);

				$command = "rsync -s -e 'ssh' \"$file\" \"$destFolder\"";
				echo "$command\n";
				$ret = run($command);
				echo "$ret\n";
			}
		}
	}
}


// ============================================================================
// Summary
// ============================================================================

echo "\n----- Summary -----\n";

ksort($CHOOSEDTARGET);
foreach ($CHOOSEDTARGET as $tgt => $val) {
	if ($tgt === '-CHKSUM') {
		echo "Checksum was generated\n";
		continue;
	}
	if ($val < 0) {
		echo "Package $tgt not built (bad requirement).\n";
	} else {
		echo "Package $tgt built successfully in $DESTI\n";
	}
}

if (!$batch) {
	prompt("\nPress key to finish...");
}

exit(0);
