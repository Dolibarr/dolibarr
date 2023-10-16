#!/usr/bin/env php
<?php

$tasks = [
    'buildzip' => [
        'init', 'test', 'clean',
    ],
    'markrelease' => [
        'init', 'test', 'clean',
    ],
    'clean' => [],
    'test' => [
        'composerupdate',
    ],
    'init' => [],
    'composerupdate' => [],
 ];

$default = 'buildzip';

$baseDir = __DIR__.'/../';
chdir($baseDir);

$currentTask = $default;
if ($argc > 1) {
    $currentTask = $argv[1];
}
$version = null;
if ($argc > 2) {
    $version = $argv[2];
}

if (!isset($tasks[$currentTask])) {
    echo 'Task not found: ',  $currentTask, "\n";
    exit(1);
}

// Creating the dependency graph
$newTaskList = [];
$oldTaskList = [$currentTask => true];

while (count($oldTaskList) > 0) {
    foreach ($oldTaskList as $task => $foo) {
        if (!isset($tasks[$task])) {
            echo 'Dependency not found: '.$task, "\n";
            exit(1);
        }
        $dependencies = $tasks[$task];

        $fullFilled = true;
        foreach ($dependencies as $dependency) {
            if (isset($newTaskList[$dependency])) {
                // Already in the fulfilled task list.
                continue;
            } else {
                $oldTaskList[$dependency] = true;
                $fullFilled = false;
            }
        }
        if ($fullFilled) {
            unset($oldTaskList[$task]);
            $newTaskList[$task] = 1;
        }
    }
}

foreach (array_keys($newTaskList) as $task) {
    echo 'task: '.$task, "\n";
    call_user_func($task);
    echo "\n";
}

function init()
{
    global $version;
    if (!$version) {
        include __DIR__.'/../vendor/autoload.php';
        $version = Sabre\DAV\Version::VERSION;
    }

    echo '  Building sabre/dav '.$version, "\n";
}

function clean()
{
    global $baseDir;
    echo "  Removing build files\n";
    $outputDir = $baseDir.'/build/SabreDAV';
    if (is_dir($outputDir)) {
        system('rm -r '.$baseDir.'/build/SabreDAV');
    }
}

function composerupdate()
{
    global $baseDir;
    echo "  Updating composer packages to latest version\n\n";
    system('cd '.$baseDir.'; composer update');
}

function test()
{
    global $baseDir;

    echo "  Running all unittests.\n";
    echo "  This may take a while.\n\n";
    system(__DIR__.'/phpunit --configuration '.$baseDir.'/tests/phpunit.xml.dist --stop-on-failure', $code);
    if (0 != $code) {
        echo "PHPUnit reported error code $code\n";
        exit(1);
    }
}

function buildzip()
{
    global $baseDir, $version;
    echo "  Generating composer.json\n";

    $input = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
    $newComposer = [
        'require' => $input['require'],
        'config' => [
            'bin-dir' => './bin',
        ],
        'prefer-stable' => true,
        'minimum-stability' => 'alpha',
    ];
    unset(
        $newComposer['require']['sabre/vobject'],
        $newComposer['require']['sabre/http'],
        $newComposer['require']['sabre/uri'],
        $newComposer['require']['sabre/event']
    );
    $newComposer['require']['sabre/dav'] = $version;
    mkdir('build/SabreDAV');
    file_put_contents('build/SabreDAV/composer.json', json_encode($newComposer, JSON_PRETTY_PRINT));

    echo "  Downloading dependencies\n";
    system('cd build/SabreDAV; composer install -n', $code);
    if (0 !== $code) {
        echo "Composer reported error code $code\n";
        exit(1);
    }

    echo "  Removing pointless files\n";
    unlink('build/SabreDAV/composer.json');
    unlink('build/SabreDAV/composer.lock');

    echo "  Moving important files to the root of the project\n";

    $fileNames = [
        'CHANGELOG.md',
        'LICENSE',
        'README.md',
        'examples',
    ];
    foreach ($fileNames as $fileName) {
        echo "    $fileName\n";
        rename('build/SabreDAV/vendor/sabre/dav/'.$fileName, 'build/SabreDAV/'.$fileName);
    }

    // <zip destfile="build/SabreDAV-${sabredav.version}.zip" basedir="build/SabreDAV" prefix="SabreDAV/" />

    echo "\n";
    echo "Zipping the sabredav distribution\n\n";
    system('cd build; zip -qr sabredav-'.$version.'.zip SabreDAV');

    echo 'Done.';
}
