<?php
/**
 * Temporary script to apply the PHPStan fix on htdocs/compta/accounting-files.php.
 *
 * The '@phan-var-force' declaration line was placed between the /** @var */ PHPDoc
 * block and the $filesarray assignment, so phpstan did not apply the @var type
 * annotation and the template type of dol_sort_array() could not be resolved.
 * Move the '@phan-var-force' line before the PHPDoc block to fix it.
 */

$filepath = __DIR__ . '/htdocs/compta/accounting-files.php';

$original = file_get_contents($filepath);
if ($original === false) {
	echo "Unable to read " . $filepath . "\n";
	exit(1);
}

$lines = explode("\n", $original);
$found = false;
for ($i = 0; $i < count($lines) - 2; $i++) {
	if (substr($lines[$i], 0, 9) === '/** @var '
		&& strpos($lines[$i], ' $filesarray */') !== false
		&& substr($lines[$i + 1], 0, 17) === "'@phan-var-force "
		&& strpos($lines[$i + 1], ' $filesarray\';') !== false
		&& $lines[$i + 2] === '$filesarray = array();'
	) {
		// Swap the phan declaration line and the PHPDoc block, so that the
		// @var annotation is applied by phpstan to the next statement.
		$tmp = $lines[$i + 1];
		$lines[$i + 1] = $lines[$i];
		$lines[$i] = $tmp;
		$found = true;
		break;
	}
}

if (!$found) {
	echo "Pattern not found in " . $filepath . "\n";
	exit(1);
}

$result = implode("\n", $lines);
if (file_put_contents($filepath, $result) === false) {
	echo "Unable to write " . $filepath . "\n";
	exit(1);
}
echo "Fix applied\n";
