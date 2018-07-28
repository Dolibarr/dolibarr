<?php
$stdin = fopen('php://stdin', 'r');
$input = stream_get_contents($stdin);
fclose($stdin);

foreach (explode(PHP_EOL, $input) as $file) {
    $skip = false;
    $f = @fopen($file, 'r');
    if ($f) {
        $firstLine = fgets($f);
        @fclose($f);

        if (!preg_match('~<?php\\s*\\/\\/\s*lint\s*([^\d\s]+)\s*([^\s]+)\s*~i', $firstLine, $m)) {
            $skip = false;
        }

        $skip = isset($m[2]) && !version_compare(PHP_VERSION, $m[2], $m[1]);
    }

    echo $file . ';' . ($skip ? '1' : '0') . PHP_EOL;
}

