<?php

use Doctum\Doctum;
use Doctum\RemoteRepository\GitHubRemoteRepository;
use Symfony\Component\Finder\Finder;

$rootDir = __DIR__ . '/../';

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->notPath('cache')
    ->notPath('build')
    ->notPath('fonts')
    ->notPath('vendor')
    ->notPath('tests')
    ->notPath('examples')
    ->in($rootDir);

return new Doctum($iterator, [
    'title'                => 'TCPDF',
    'build_dir'            => $rootDir . '/build',
    'cache_dir'            => $rootDir . '/cache',
    'source_dir'           => $rootDir,
    'remote_repository'    => new GitHubRemoteRepository('tecnickcom/TCPDF', $rootDir),
    'footer_link'          => [
        'href'        => 'https://github.com/tecnickcom/TCPDF#readme',
        'rel'         => 'noreferrer noopener',
        'target'      => '_blank',
        'before_text' => 'This documentation is for',
        'link_text'   => 'TCPDF',
        'after_text'  => 'the PHP library to build PDFs.',
    ],
]);
