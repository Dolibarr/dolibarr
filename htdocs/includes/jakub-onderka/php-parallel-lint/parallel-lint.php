<?php
use JakubOnderka\PhpParallelLint;

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50303) {
    echo "PHP Parallel Lint require PHP 5.3.3 or newer.\n";
    die(255);
}
const SUCCESS = 0,
    WITH_ERRORS = 1,
    FAILED = 255;

function showOptions()
{
?>
Options:
    -p <php>        Specify PHP-CGI executable to run (default: 'php').
    -s, --short     Set short_open_tag to On (default: Off).
    -a, -asp        Set asp_tags to On (default: Off).
    -e <ext>        Check only files with selected extensions separated by comma.
                    (default: php,php3,php4,php5,phtml)
    --exclude       Exclude directory. If you want exclude multiple directories, use
                    multiple exclude parameters.
    -j <num>        Run <num> jobs in parallel (default: 10).
    --no-colors     Disable colors in console output.
    --json          Output results as JSON string (require PHP 5.4).
    --blame         Try to show git blame for row with error.
    --git <git>     Path to Git executable to show blame message (default: 'git').
    --stdin         Load files and folder to test from standard input.
    --ignore-fails  Ignore failed tests.
    -h, --help      Print this help.
<?php
}

function showUsage()
{
    ?>
PHP Parallel Lint version 0.9.1
-------------------------------
Usage:
    parallel-lint [sa] [-p php] [-e ext] [-j num] [--exclude dir] [files or directories]

<?php
showOptions();
die();
}

if (in_array('-h', $_SERVER['argv']) || in_array('--help', $_SERVER['argv'])) {
    showUsage();
}

$files = array(
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/vendor/autoload.php'
);

$autoloadFileFound = false;
foreach ($files as $file) {
    if (file_exists($file)) {
        require $file;
        $autoloadFileFound = true;
        break;
    }
}

if (!$autoloadFileFound) {
    echo 'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
        'php composer.phar install' . PHP_EOL;
    die(FAILED);
}

try {
    $settings = PhpParallelLint\Settings::parseArguments($_SERVER['argv']);

    if ($settings->json && PHP_VERSION_ID < 50400) {
        throw new \Exception('JSON output require PHP version 5.4 and newer.');
    }

    if ($settings->stdin) {
        $settings->addPaths(PhpParallelLint\Settings::getPathsFromStdIn());
    }

    if (empty($settings->paths)) {
        showUsage();
    }

    $manager = new PhpParallelLint\Manager;
    $result = $manager->run($settings);

    if ($settings->ignoreFails) {
        die($result->hasSyntaxError() ? WITH_ERRORS : SUCCESS);
    } else {
        die($result->hasError() ? WITH_ERRORS : SUCCESS);
    }

} catch (PhpParallelLint\InvalidArgumentException $e) {
    echo "Invalid option {$e->getArgument()}" . PHP_EOL . PHP_EOL;
    showOptions();
    die(FAILED);

} catch (PhpParallelLint\Exception $e) {
    if ($settings->json) {
        echo json_encode($e);
    } else {
        echo $e->getMessage(), PHP_EOL;
    }
    die(FAILED);

} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
    die(FAILED);
}
