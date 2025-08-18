#!/usr/bin/env php
<?php
/* Copyright (C) 2024-2025	MDW				<mdeweerd@users.noreply.github.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Script to update year periods and user/email in headers.
 */

/**
 * Retrieve Git user information
 *
 * @return array{name:string,email:string}
 */
function getGitUserInfo()
{
	$name = trim(shell_exec('git config user.name'));
	$email = trim(shell_exec('git config user.email'));
	return ['name' => $name, 'email' => $email];
}

const PREFIXES = [
	'sh' => ['# ', '# ', '', '#!'],
	'php' => ['/* ', ' * ', ' */', '<?php'],
];


/**
 * Expand tabs in string to spaces.
 *
 * @param $str      String in which to expand the tabs to spaces
 * @param $tabWidth Width to use for a tabstop
 *
 * @return string   Expanded string value.
 */
function expandTabs($str, $tabWidth)
{
	$expanded = '';
	$col = 0;
	$len = mb_strlen($str);
	for ($i = 0; $i < $len; $i++) {
		if ($str[$i] == "\t") {
			$expanded .= str_repeat(' ', $tabWidth - ($col % $tabWidth));
			$col += $tabWidth - ($col % $tabWidth);
		} else {
			$expanded .= $str[$i];
			$col++;
		}
	}
	return $expanded;
}

/**
 * Update or add copyright notice in a file
 *
 * @param $filename string Path to the file to modify
 * @param $fileType string Filetype identification for the file
 * @param $name     string Name of developer to add in header
 * @param $email    string Email for the developer
 * @return          bool   True if license was updated
 */
function updateCopyrightNotice($filename, $fileType, $name, $email)
{
	// Determine the appropriate prefix based on file type
	if (!array_key_exists($fileType, PREFIXES)) {
		return false;
	}

	// Get configuration for the filetype
	list($prefix0, $prefix1, $prefix2, $prefix3) = PREFIXES[$fileType];
	$r_prefix0 = preg_quote($prefix0);
	$r_prefix1 = preg_quote($prefix1);
	$r_prefix3 = preg_quote($prefix3);
	$r_name = preg_quote($name);
	$r_email = preg_quote($email);

	// Read the first n lines of the file
	$n = 50;
	$lines = implode('', array_slice(file($filename), 0, $n));

	// Based on the tendency to limit the length of the spacing between the name
	// and email to the smallest one, determine the biggest offset from the start
	// of a name to the start of the email, and use that for the current name/email
	// offset if it is sufficient.

	// Pattern to match any copyright already present
	$allpattern = "~(?:{$r_prefix0}|{$r_prefix1})Copyright \(C\)\s+(?:(?:\d{4}-)?(?:\d{4}))\s+(\S.*)<\S+>~";
	// Set minimum offset based of width of new name
	$nameStartToMailStartOffset = 4 * (int) ((mb_strlen($r_name) + 4) / 4);

	if (preg_match_all($allpattern, $lines, $allmatches)) {
		foreach ($allmatches[1] as $nameAndSpaces) {
			//print $nameAndSpaces."\n";
			$nameAndSpaces = expandTabs($nameAndSpaces, 4);
			$currentOffset = mb_strlen($nameAndSpaces);
			$currentOffset = 4 * (int) ((3 + $currentOffset) / 4);
			//print "Other offset $nameAndSpaces: $currentOffset\n";
			if ($currentOffset > $nameStartToMailStartOffset) {
				$nameStartToMailStartOffset = $currentOffset;
			}
		}
	}

	// Pattern to match the line matching the current developer
	$pattern = "~(?:{$r_prefix0}|{$r_prefix1})Copyright \(C\)\s+(?:(?:(?<start>\d{4})-)?(?<last>\d{4}))\s+{$r_name}\s*\<{$r_email}>~";
	// Check if the lines match the pattern
	$matches = array();
	if (preg_match($pattern, $lines, $matches)) {
		$existingYear = $matches['last'];
		$startYear = null;
		if (array_key_exists('start', $matches)) {
			$startYear = $matches['start'];
		}
		if (empty($startYear)) {
			$startYear = $existingYear;
		}

		// Check if the existing year is different from the current year
		if ($existingYear !== date('Y')) {
			// Extend the year range to the current year
			$updatedNotice = preg_replace('/(?:\d{4}-)?\d{4}\s+/', $startYear . '-' . date('Y') . "\t", $matches[0]);
			// Replace the old notice with the updated one in the file
			file_put_contents($filename, preg_replace($pattern, $updatedNotice, file_get_contents($filename)));
			return true; // Change detected
		}
		// If the existing year is the same, no need to update
	} else {
		// Adjust tabs for proper alignment
		// print "Offset:".$nameStartToMailStartOffset."\n";
		$emailTabs = str_repeat("\t", (int) (max(0, ($nameStartToMailStartOffset + 4 - mb_strlen($name)) / 4)));

		// No match found, add a new line to the header
		$newNotice = "Copyright (C) " . date('Y') . "\t\t" . $name . $emailTabs . "<" . $email . ">";

		// Read the file content
		$fileContent = file_get_contents($filename);

		// Check if there are existing copyright notices
		$pos = max(strrpos($fileContent, "{$prefix0}Copyright"), strrpos($fileContent, "{$prefix1}Copyright"));

		if ($pos !== false) {
			// Add the new notice behind the last preceding copyright notices
			$pos = strpos($fileContent, "\n", $pos) + 1;
			$fileContent = substr_replace($fileContent, $prefix1 . $newNotice . "\n", $pos, 0);
		} elseif (strpos($fileContent, $prefix3) !== false) {
			// Add the new notice after the shebang or '<?php' line
			$fileContent = preg_replace("~{$r_prefix3}.*\n~", "$0$prefix0$newNotice\n$prefix2\n", $fileContent, 1);
		} else {
			return false; // No change detected
		}

		// Write the updated content back to the file
		file_put_contents($filename, $fileContent);
		// print $fileContent;
		return true; // Change detected
	}

	return false; // No change detected
}

// Main program

// Check if filenames are provided as parameters
if ($argc < 2) {
	echo "Usage: php " . __FILE__ . " <filename1> [<filename2> ...]" . PHP_EOL;
	exit(1);
}

// Process each filename provided
$changesDetected = false;
for ($i = 1; $i < $argc; $i++) {
	$filename = $argv[$i];

	// Determine file type based on extension
	$fileType = pathinfo($filename, PATHINFO_EXTENSION);

	// Retrieve Git user information
	$gitUserInfo = getGitUserInfo();
	$name = $gitUserInfo['name'];
	$email = $gitUserInfo['email'];

	// Update or add copyright notice based on file type
	$changeDetected = updateCopyrightNotice($filename, $fileType, $name, $email);
	$changesDetected |= $changeDetected;
	if ($changeDetected) {
		echo "Copyright notice updated in '$filename'" . PHP_EOL;
	}
}

if (!$changesDetected) {
	echo "No changes needed in any file" . PHP_EOL;
}
