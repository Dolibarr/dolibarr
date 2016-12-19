<?php
$im = new Imagick();
try {
	$im -> readImage("doc.pdf[5]");
	$im -> destroy();
} catch(ImagickException $e) {
	echo "Error: " . $e -> getMessage() . "\n";
}

$im = new Imagick();
try {
	ob_start();
	@$im -> readImage("doc.pdf[5]");
	ob_end_clean();
	$im -> destroy();
} catch(ImagickException $e) {
	echo "Error: " . $e -> getMessage() . "\n";
}

