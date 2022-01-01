<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarDumper\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class CliDumperTest extends TestCase
{
	use VarDumperTestTrait;

	public function testGet()
	{
		require __DIR__.'/Fixtures/dumb-var.php';

		$dumper = new CliDumper('php://output');
		$dumper->setColors(false);
		$cloner = new VarCloner();
		$cloner->addCasters(array(
			':stream' => function ($res, $a) {
				unset($a['uri'], $a['wrapper_data']);

				return $a;
			},
		));
		$data = $cloner->cloneVar($var);

		ob_start();
		$dumper->dump($data);
		$out = ob_get_clean();
		$out = preg_replace('/[ \t]+$/m', '', $out);
		$intMax = PHP_INT_MAX;
		$res = (int) $var['res'];

		$r = defined('HHVM_VERSION') ? '' : '#%d';
		$this->assertStringMatchesFormat(
			<<<EOTXT
array:24 [
  "number" => 1
  0 => &1 null
  "const" => 1.1
  1 => true
  2 => false
  3 => NAN
  4 => INF
  5 => -INF
  6 => {$intMax}
  "str" => "déjà\\n"
  7 => b"é\\x00"
  "[]" => []
  "res" => stream resource {@{$res}
%A  wrapper_type: "plainfile"
    stream_type: "STDIO"
    mode: "r"
    unread_bytes: 0
    seekable: true
%A  options: []
  }
  "obj" => Symfony\Component\VarDumper\Tests\Fixture\DumbFoo {#%d
    +foo: "foo"
    +"bar": "bar"
  }
  "closure" => Closure {{$r}
    class: "Symfony\Component\VarDumper\Tests\CliDumperTest"
    this: Symfony\Component\VarDumper\Tests\CliDumperTest {{$r} …}
    parameters: {
      \$a: {}
      &\$b: {
        typeHint: "PDO"
        default: null
      }
    }
    file: "{$var['file']}"
    line: "{$var['line']} to {$var['line']}"
  }
  "line" => {$var['line']}
  "nobj" => array:1 [
    0 => &3 {#%d}
  ]
  "recurs" => &4 array:1 [
    0 => &4 array:1 [&4]
  ]
  8 => &1 null
  "sobj" => Symfony\Component\VarDumper\Tests\Fixture\DumbFoo {#%d}
  "snobj" => &3 {#%d}
  "snobj2" => {#%d}
  "file" => "{$var['file']}"
  b"bin-key-é" => ""
]

EOTXT
			,
			$out
		);
	}

	/**
	 * @requires extension xml
	 */
	public function testXmlResource()
	{
		$var = xml_parser_create();

		$this->assertDumpMatchesFormat(
			<<<'EOTXT'
xml resource {
  current_byte_index: %i
  current_column_number: %i
  current_line_number: 1
  error_code: XML_ERROR_NONE
}
EOTXT
			,
			$var
		);
	}

	public function testJsonCast()
	{
		$var = (array) json_decode('{"0":{},"1":null}');
		foreach ($var as &$v) {
		}
		$var[] = &$v;
		$var[''] = 2;

		if (\PHP_VERSION_ID >= 70200) {
			$this->assertDumpMatchesFormat(
				<<<'EOTXT'
array:4 [
  0 => {}
  1 => &1 null
  2 => &1 null
  "" => 2
]
EOTXT
				,
				$var
			);
		} else {
			$this->assertDumpMatchesFormat(
				<<<'EOTXT'
array:4 [
  "0" => {}
  "1" => &1 null
  0 => &1 null
  "" => 2
]
EOTXT
				,
				$var
			);
		}
	}

	public function testObjectCast()
	{
		$var = (object) array(1 => 1);
		$var->{1} = 2;

		if (\PHP_VERSION_ID >= 70200) {
			$this->assertDumpMatchesFormat(
				<<<'EOTXT'
{
  +"1": 2
}
EOTXT
				,
				$var
			);
		} else {
			$this->assertDumpMatchesFormat(
				<<<'EOTXT'
{
  +1: 1
  +"1": 2
}
EOTXT
				,
				$var
			);
		}
	}

	public function testClosedResource()
	{
		if (defined('HHVM_VERSION') && HHVM_VERSION_ID < 30600) {
			$this->markTestSkipped();
		}

		$var = fopen(__FILE__, 'r');
		fclose($var);

		$dumper = new CliDumper('php://output');
		$dumper->setColors(false);
		$cloner = new VarCloner();
		$data = $cloner->cloneVar($var);

		ob_start();
		$dumper->dump($data);
		$out = ob_get_clean();
		$res = (int) $var;

		$this->assertStringMatchesFormat(
			<<<EOTXT
Closed resource @{$res}

EOTXT
			,
			$out
		);
	}

	public function testFlags()
	{
		putenv('DUMP_LIGHT_ARRAY=1');
		putenv('DUMP_STRING_LENGTH=1');

		$var = array(
			range(1, 3),
			array('foo', 2 => 'bar'),
		);

		$this->assertDumpEquals(
			<<<EOTXT
[
  [
    1
    2
    3
  ]
  [
    0 => (3) "foo"
    2 => (3) "bar"
  ]
]
EOTXT
			,
			$var
		);

		putenv('DUMP_LIGHT_ARRAY=');
		putenv('DUMP_STRING_LENGTH=');
	}

	/**
	 * @requires function Twig\Template::getSourceContext
	 */
	public function testThrowingCaster()
	{
		$out = fopen('php://memory', 'r+b');

		require_once __DIR__.'/Fixtures/Twig.php';
		$twig = new \__TwigTemplate_VarDumperFixture_u75a09(new Environment(new FilesystemLoader()));

		$dumper = new CliDumper();
		$dumper->setColors(false);
		$cloner = new VarCloner();
		$cloner->addCasters(array(
			':stream' => function ($res, $a) {
				unset($a['wrapper_data']);

				return $a;
			},
		));
		$cloner->addCasters(array(
			':stream' => eval('return function () use ($twig) {
                try {
                    $twig->render(array());
                } catch (\Twig\Error\RuntimeError $e) {
                    throw $e->getPrevious();
                }
            };'),
		));
		$ref = (int) $out;

		$data = $cloner->cloneVar($out);
		$dumper->dump($data, $out);
		$out = stream_get_contents($out, -1, 0);

		$r = defined('HHVM_VERSION') ? '' : '#%d';
		$this->assertStringMatchesFormat(
			<<<EOTXT
stream resource {@{$ref}
  ⚠: Symfony\Component\VarDumper\Exception\ThrowingCasterException {{$r}
    #message: "Unexpected Exception thrown from a caster: Foobar"
    -trace: {
      %sTwig.php:2: {
        : foo bar
        :   twig source
        : 
      }
      %sTemplate.php:%d: {
        : try {
        :     \$this->doDisplay(\$context, \$blocks);
        : } catch (Twig%sError \$e) {
      }
      %sTemplate.php:%d: {
        : {
        :     \$this->displayWithErrorHandling(\$this->env->mergeGlobals(\$context), array_merge(\$this->blocks, \$blocks));
        : }
      }
      %sTemplate.php:%d: {
        : try {
        :     \$this->display(\$context);
        : } catch (%s \$e) {
      }
      %sCliDumperTest.php:%d: {
%A
      }
    }
  }
%Awrapper_type: "PHP"
  stream_type: "MEMORY"
  mode: "%s+b"
  unread_bytes: 0
  seekable: true
  uri: "php://memory"
%Aoptions: []
}

EOTXT
			,
			$out
		);
	}

	public function testRefsInProperties()
	{
		$var = (object) array('foo' => 'foo');
		$var->bar = &$var->foo;

		$dumper = new CliDumper();
		$dumper->setColors(false);
		$cloner = new VarCloner();

		$data = $cloner->cloneVar($var);
		$out = $dumper->dump($data, true);

		$r = defined('HHVM_VERSION') ? '' : '#%d';
		$this->assertStringMatchesFormat(
			<<<EOTXT
{{$r}
  +"foo": &1 "foo"
  +"bar": &1 "foo"
}

EOTXT
			,
			$out
		);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @requires PHP 5.6
	 */
	public function testSpecialVars56()
	{
		$var = $this->getSpecialVars();

		$this->assertDumpEquals(
			<<<'EOTXT'
array:3 [
  0 => array:1 [
    0 => &1 array:1 [
      0 => &1 array:1 [&1]
    ]
  ]
  1 => array:1 [
    "GLOBALS" => &2 array:1 [
      "GLOBALS" => &2 array:1 [&2]
    ]
  ]
  2 => &2 array:1 [&2]
]
EOTXT
			,
			$var
		);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testGlobalsNoExt()
	{
		$var = $this->getSpecialVars();
		unset($var[0]);
		$out = '';

		$dumper = new CliDumper(function ($line, $depth) use (&$out) {
			if ($depth >= 0) {
				$out .= str_repeat('  ', $depth).$line."\n";
			}
		});
		$dumper->setColors(false);
		$cloner = new VarCloner();

		$refl = new \ReflectionProperty($cloner, 'useExt');
		$refl->setAccessible(true);
		$refl->setValue($cloner, false);

		$data = $cloner->cloneVar($var);
		$dumper->dump($data);

		$this->assertSame(
			<<<'EOTXT'
array:2 [
  1 => array:1 [
    "GLOBALS" => &1 array:1 [
      "GLOBALS" => &1 array:1 [&1]
    ]
  ]
  2 => &1 array:1 [&1]
]

EOTXT
			,
			$out
		);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testBuggyRefs()
	{
		if (\PHP_VERSION_ID >= 50600) {
			$this->markTestSkipped('PHP 5.6 fixed refs counting');
		}

		$var = $this->getSpecialVars();
		$var = $var[0];

		$dumper = new CliDumper();
		$dumper->setColors(false);
		$cloner = new VarCloner();

		$data = $cloner->cloneVar($var)->withMaxDepth(3);
		$out = '';
		$dumper->dump($data, function ($line, $depth) use (&$out) {
			if ($depth >= 0) {
				$out .= str_repeat('  ', $depth).$line."\n";
			}
		});

		$this->assertSame(
			<<<'EOTXT'
array:1 [
  0 => array:1 [
    0 => array:1 [
      0 => array:1 [ …1]
    ]
  ]
]

EOTXT
			,
			$out
		);
	}

	public function testIncompleteClass()
	{
		$unserializeCallbackHandler = ini_set('unserialize_callback_func', null);
		$var = unserialize('O:8:"Foo\Buzz":0:{}');
		ini_set('unserialize_callback_func', $unserializeCallbackHandler);

		$this->assertDumpMatchesFormat(
			<<<EOTXT
__PHP_Incomplete_Class(Foo\Buzz) {}
EOTXT
			,
			$var
		);
	}

	private function getSpecialVars()
	{
		foreach (array_keys($GLOBALS) as $var) {
			if ('GLOBALS' !== $var) {
				unset($GLOBALS[$var]);
			}
		}

		$var = function &() {
			$var = array();
			$var[] = &$var;

			return $var;
		};

		return array($var(), $GLOBALS, &$GLOBALS);
	}
}
