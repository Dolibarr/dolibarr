<?php

namespace ParseCsv\tests\methods;

use PHPUnit\Framework\TestCase;

/**
 * This test checks for backwards compatibility: Does it work to
 *   - require the old "parsecsv.lib.php" instead of composer autoloading?
 *   - use the old class name "parseCSV"?
 */
class OldRequireTest extends TestCase {

    protected function setUp(): void {
        rename('vendor/autoload.php', '__autoload');
    }

    protected function tearDown(): void {
        rename('__autoload', 'vendor/autoload.php');
    }

    /**
     * @runInSeparateProcess so that disabled autoloading has an effect
     */
    public function testOldLibWithoutComposer() {
        file_put_contents('__eval.php', '<?php require "parsecsv.lib.php"; new \ParseCsv\Csv;');
        exec("php __eval.php", $output, $return_var);
        unlink('__eval.php');
        $this->assertEquals($output, []);
        $this->assertEquals(0, $return_var);
    }

    /**
     * @runInSeparateProcess so that disabled autoloading has an effect
     */
    public function testOldLibWithOldClassName() {
        file_put_contents('__eval.php', '<?php require "parsecsv.lib.php"; new parseCSV;');
        exec("php __eval.php", $output, $return_var);
        unlink('__eval.php');
        $this->assertEquals($output, []);
        $this->assertEquals(0, $return_var);
    }
}
