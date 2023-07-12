<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Mapping\Loader;

/**
 * Base loader for loading validation metadata from a list of files.
 *
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @see YamlFilesLoader
 * @see XmlFilesLoader
 */
abstract class FilesLoader extends LoaderChain
{
    /**
     * Creates a new loader.
     *
     * @param array $paths An array of file paths
     */
    public function __construct(array $paths)
    {
        parent::__construct($this->getFileLoaders($paths));
    }

    /**
     * Returns an array of file loaders for the given file paths.
     *
     * @return LoaderInterface[]
     */
    protected function getFileLoaders(array $paths): array
    {
        $loaders = [];

        foreach ($paths as $path) {
            $loaders[] = $this->getFileLoaderInstance($path);
        }

        return $loaders;
    }

    /**
     * Creates a loader for the given file path.
     */
    abstract protected function getFileLoaderInstance(string $path): LoaderInterface;
}
