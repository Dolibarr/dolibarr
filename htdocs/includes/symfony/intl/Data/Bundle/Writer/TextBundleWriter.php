<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Intl\Data\Bundle\Writer;

/**
 * Writes .txt resource bundles.
 *
 * The resulting files can be converted to binary .res files using a
 * {@link \Symfony\Component\Intl\ResourceBundle\Compiler\BundleCompilerInterface}
 * implementation.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @see http://source.icu-project.org/repos/icu/icuhtml/trunk/design/bnf_rb.txt
 *
 * @internal
 */
class TextBundleWriter implements BundleWriterInterface
{
    public function write(string $path, string $locale, mixed $data, bool $fallback = true)
    {
        $file = fopen($path.'/'.$locale.'.txt', 'w');

        $this->writeResourceBundle($file, $locale, $data, $fallback);

        fclose($file);
    }

    /**
     * Writes a "resourceBundle" node.
     *
     * @param resource $file  The file handle to write to
     * @param mixed    $value The value of the node
     *
     * @see http://source.icu-project.org/repos/icu/icuhtml/trunk/design/bnf_rb.txt
     */
    private function writeResourceBundle($file, string $bundleName, mixed $value, bool $fallback)
    {
        fwrite($file, $bundleName);

        $this->writeTable($file, $value, 0, $fallback);

        fwrite($file, "\n");
    }

    /**
     * Writes a "resource" node.
     *
     * @param resource $file  The file handle to write to
     * @param mixed    $value The value of the node
     *
     * @see http://source.icu-project.org/repos/icu/icuhtml/trunk/design/bnf_rb.txt
     */
    private function writeResource($file, mixed $value, int $indentation, bool $requireBraces = true)
    {
        if (\is_int($value)) {
            $this->writeInteger($file, $value);

            return;
        }

        if ($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        }

        if (\is_array($value)) {
            $intValues = \count($value) === \count(array_filter($value, 'is_int'));

            // check that the keys are 0-indexed and ascending
            $intKeys = array_is_list($value);

            if ($intValues && $intKeys) {
                $this->writeIntVector($file, $value, $indentation);

                return;
            }

            if ($intKeys) {
                $this->writeArray($file, $value, $indentation);

                return;
            }

            $this->writeTable($file, $value, $indentation);

            return;
        }

        if (\is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $this->writeString($file, (string) $value, $requireBraces);
    }

    /**
     * Writes an "integer" node.
     *
     * @param resource $file The file handle to write to
     *
     * @see http://source.icu-project.org/repos/icu/icuhtml/trunk/design/bnf_rb.txt
     */
    private function writeInteger($file, int $value)
    {
        fprintf($file, ':int{%d}', $value);
    }

    /**
     * Writes an "intvector" node.
     *
     * @param resource $file The file handle to write to
     *
     * @see http://source.icu-project.org/repos/icu/icuhtml/trunk/design/bnf_rb.txt
     */
    private function writeIntVector($file, array $value, int $indentation)
    {
        fwrite($file, ":intvector{\n");

        foreach ($value as $int) {
            fprintf($file, "%s%d,\n", str_repeat('    ', $indentation + 1), $int);
        }

        fprintf($file, '%s}', str_repeat('    ', $indentation));
    }

    /**
     * Writes a "string" node.
     *
     * @param resource $file The file handle to write to
     *
     * @see http://source.icu-project.org/repos/icu/icuhtml/trunk/design/bnf_rb.txt
     */
    private function writeString($file, string $value, bool $requireBraces = true)
    {
        if ($requireBraces) {
            fprintf($file, '{"%s"}', $value);

            return;
        }

        fprintf($file, '"%s"', $value);
    }

    /**
     * Writes an "array" node.
     *
     * @param resource $file The file handle to write to
     *
     * @see http://source.icu-project.org/repos/icu/icuhtml/trunk/design/bnf_rb.txt
     */
    private function writeArray($file, array $value, int $indentation)
    {
        fwrite($file, "{\n");

        foreach ($value as $entry) {
            fwrite($file, str_repeat('    ', $indentation + 1));

            $this->writeResource($file, $entry, $indentation + 1, false);

            fwrite($file, ",\n");
        }

        fprintf($file, '%s}', str_repeat('    ', $indentation));
    }

    /**
     * Writes a "table" node.
     *
     * @param resource $file The file handle to write to
     */
    private function writeTable($file, iterable $value, int $indentation, bool $fallback = true)
    {
        if (!$fallback) {
            fwrite($file, ':table(nofallback)');
        }

        fwrite($file, "{\n");

        foreach ($value as $key => $entry) {
            fwrite($file, str_repeat('    ', $indentation + 1));

            // escape colons, otherwise they are interpreted as resource types
            if (str_contains($key, ':') || str_contains($key, ' ')) {
                $key = '"'.$key.'"';
            }

            fwrite($file, $key);

            $this->writeResource($file, $entry, $indentation + 1);

            fwrite($file, "\n");
        }

        fprintf($file, '%s}', str_repeat('    ', $indentation));
    }
}
