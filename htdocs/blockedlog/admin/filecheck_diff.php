<?php
/* Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/blockedlog/admin/filecheck_diff.php
 *  \brief      Ajax fragment to show the diff of a modified file against its original version
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

$langs->loadLangs(array("admin", "errors"));

if (!$user->admin && !$user->hasRight('bockedlog', 'read')) {
	accessforbidden();
}

$file = GETPOST('file', 'alphanohtml');
$algo = GETPOST('algo', 'aZ09');
$expectedhash = GETPOST('expectedhash', 'aZ09');

if (!in_array($algo, array('md5', 'sha256'), true)) {
	$algo = 'sha256';
}


/**
 * Compute a line based diff between two arrays of lines, restricted to the changed
 * region (common prefix and suffix are trimmed first to keep it cheap and memory safe).
 *
 * @param	string[]	$a	Lines of the original file
 * @param	string[]	$b	Lines of the local file
 * @return	array<array{0:string,1:string}>		List of [type, line] where type is ' ' (context), '-' (removed) or '+' (added)
 */
function filecheckLineDiff($a, $b)
{
	$na = count($a);
	$nb = count($b);

	// Trim common prefix
	$start = 0;
	while ($start < $na && $start < $nb && $a[$start] === $b[$start]) {
		$start++;
	}
	// Trim common suffix
	$enda = $na - 1;
	$endb = $nb - 1;
	while ($enda >= $start && $endb >= $start && $a[$enda] === $b[$endb]) {
		$enda--;
		$endb--;
	}

	$result = array();
	for ($i = 0; $i < $start; $i++) {
		$result[] = array(' ', $a[$i]);
	}

	$midA = array_slice($a, $start, $enda - $start + 1);
	$midB = array_slice($b, $start, $endb - $start + 1);

	foreach (filecheckMiddleDiff($midA, $midB) as $d) {
		$result[] = $d;
	}

	for ($i = $enda + 1; $i < $na; $i++) {
		$result[] = array(' ', $a[$i]);
	}

	return $result;
}

/**
 * Diff of the changed region using a classic LCS dynamic programming matrix.
 * Falls back to a plain block replacement when the region is too large to keep
 * the matrix in memory.
 *
 * @param	string[]	$a	Changed lines of the original file
 * @param	string[]	$b	Changed lines of the local file
 * @return	array<array{0:string,1:string}>		List of [type, line]
 */
function filecheckMiddleDiff($a, $b)
{
	$na = count($a);
	$nb = count($b);

	if ($na == 0 && $nb == 0) {
		return array();
	}
	// Too big to build a full LCS matrix: show the region as a plain replacement.
	if ($na * $nb > 4000000) {
		$result = array();
		foreach ($a as $line) {
			$result[] = array('-', $line);
		}
		foreach ($b as $line) {
			$result[] = array('+', $line);
		}
		return $result;
	}

	// LCS length matrix
	$lcs = array();
	for ($i = 0; $i <= $na; $i++) {
		$lcs[$i] = array_fill(0, $nb + 1, 0);
	}
	for ($i = $na - 1; $i >= 0; $i--) {
		for ($j = $nb - 1; $j >= 0; $j--) {
			if ($a[$i] === $b[$j]) {
				$lcs[$i][$j] = $lcs[$i + 1][$j + 1] + 1;
			} else {
				$lcs[$i][$j] = max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
			}
		}
	}

	// Backtrack to build the diff
	$result = array();
	$i = 0;
	$j = 0;
	while ($i < $na && $j < $nb) {
		if ($a[$i] === $b[$j]) {
			$result[] = array(' ', $a[$i]);
			$i++;
			$j++;
		} elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
			$result[] = array('-', $a[$i]);
			$i++;
		} else {
			$result[] = array('+', $b[$j]);
			$j++;
		}
	}
	while ($i < $na) {
		$result[] = array('-', $a[$i]);
		$i++;
	}
	while ($j < $nb) {
		$result[] = array('+', $b[$j]);
		$j++;
	}

	return $result;
}

/**
 * Collapse long runs of unchanged context lines, keeping a few lines around each change.
 *
 * @param	array<array{0:string,1:string}>		$diff		Full diff as returned by filecheckLineDiff()
 * @param	int									$context	Number of context lines to keep around changes
 * @return	array<array{0:string,1:string}>		Diff with collapsed context (type '@' marks a collapsed gap)
 */
function filecheckCollapseContext($diff, $context = 3)
{
	$n = count($diff);
	$keep = array_fill(0, $n, false);
	for ($i = 0; $i < $n; $i++) {
		if ($diff[$i][0] !== ' ') {
			$from = max(0, $i - $context);
			$to = min($n - 1, $i + $context);
			for ($k = $from; $k <= $to; $k++) {
				$keep[$k] = true;
			}
		}
	}

	$result = array();
	$ingap = false;
	for ($i = 0; $i < $n; $i++) {
		if ($keep[$i]) {
			$result[] = $diff[$i];
			$ingap = false;
		} elseif (!$ingap) {
			$result[] = array('@', '');
			$ingap = true;
		}
	}

	return $result;
}


top_httphead('text/html');

print '<!-- filecheck_diff.php fragment -->'."\n";

// Validate the requested file: must be a relative path inside DOL_DOCUMENT_ROOT, with no traversal.
$errormsg = '';
$reallocal = '';
if (empty($file) || $file[0] !== '/' || strpos($file, '..') !== false || !preg_match('/^[A-Za-z0-9_\/.\-]+$/', $file)) {
	$errormsg = $langs->trans("ErrorBadValueForParameter", dol_escape_htmltag($file), "file");
}

if (empty($errormsg)) {
	$reallocal = realpath(DOL_DOCUMENT_ROOT.$file);
	if ($reallocal === false || strpos($reallocal, realpath(DOL_DOCUMENT_ROOT).'/') !== 0 || !is_file($reallocal)) {
		$errormsg = $langs->trans("ErrorFileNotFound", $file);
	}
}
// Only text files can be diffed
if (empty($errormsg) && preg_match('/\.(jpg|jpeg|png|gif|ico|svg|eot|woff|woff2|ttf|mp3|mp4|wav|mkv|z|gz|zip|rar|tar)$/i', $file)) {
	$errormsg = $langs->trans("DiffNotAvailableForBinaryFiles");
}

if (!empty($errormsg)) {
	print '<div class="warning">'.$errormsg.'</div>';
	llxFooterFragment();
}

// Build the URL of the original file for the running version (GitHub raw of the matching tag).
$baseurl = getDolGlobalString('MAIN_FILECHECK_DIFF_BASEURL', 'https://raw.githubusercontent.com/Dolibarr/dolibarr');
$originurl = $baseurl.'/'.DOL_VERSION.'/htdocs'.$file;

$res = getURLContent($originurl, 'GET', '', 1, array(), array('http', 'https'), 0);	// Accept http or https links on external remote server only.
if (!empty($res['curl_error_no']) || (isset($res['http_code']) && !in_array((int) $res['http_code'], array(0, 200), true))) {
	print '<div class="warning">'.$langs->trans("CouldNotFetchOriginalFile").': '.dol_escape_htmltag($originurl);
	print ' ('.dol_escape_htmltag((string) (empty($res['http_code']) ? $res['curl_error_msg'] : $res['http_code'])).')</div>';
	llxFooterFragment();
}

$origincontent = (string) $res['content'];
$localcontent = (string) file_get_contents($reallocal);

// Confirm the fetched original is the genuine reference file expected by the signature.
$verified = true;
if (!empty($expectedhash)) {
	$verified = (hash($algo, $origincontent) === $expectedhash);
}
if (!$verified) {
	print '<div class="warning">'.$langs->trans("OriginalFileChecksumMismatch").'</div>';
}

if ($origincontent === $localcontent) {
	print '<div class="opacitymedium">'.$langs->trans("NoDifferenceFound").'</div>';
	llxFooterFragment();
}

$diff = filecheckLineDiff(explode("\n", $origincontent), explode("\n", $localcontent));
$diff = filecheckCollapseContext($diff, 3);

print '<div class="opacitymedium" style="margin-bottom:4px">';
print img_picto('', 'split', 'class="pictofixedwidth"').dol_escape_htmltag($file).' &mdash; '.dol_escape_htmltag($originurl);
print '</div>';

print '<table class="filecheckdiff" style="width:100%;border-collapse:collapse;font-family:monospace;font-size:0.85em">';
foreach ($diff as $line) {
	$type = $line[0];
	if ($type === '@') {
		print '<tr><td style="background:#eef;color:#888;padding:1px 6px">&hellip;</td></tr>'."\n";
		continue;
	}
	$bg = '';
	$sign = ' ';
	if ($type === '+') {
		$bg = 'background:#e6ffed';
		$sign = '+';
	} elseif ($type === '-') {
		$bg = 'background:#ffeef0';
		$sign = '-';
	}
	print '<tr><td style="white-space:pre-wrap;word-break:break-all;padding:0 6px;'.$bg.'">';
	print dol_escape_htmltag($sign.' '.$line[1]);
	print '</td></tr>'."\n";
}
print '</table>';

llxFooterFragment();


/**
 * Close the fragment output and stop the script.
 *
 * @return void
 */
function llxFooterFragment()
{
	global $db;
	if (is_object($db)) {
		$db->close();
	}
	exit;
}
