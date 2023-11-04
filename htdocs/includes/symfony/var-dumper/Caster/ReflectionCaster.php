<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarDumper\Caster;

use Symfony\Component\VarDumper\Cloner\Stub;

/**
 * Casts Reflector related classes to array representation.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class ReflectionCaster
{
    private static $extraMap = array(
        'docComment' => 'getDocComment',
        'extension' => 'getExtensionName',
        'isDisabled' => 'isDisabled',
        'isDeprecated' => 'isDeprecated',
        'isInternal' => 'isInternal',
        'isUserDefined' => 'isUserDefined',
        'isGenerator' => 'isGenerator',
        'isVariadic' => 'isVariadic',
    );

    public static function castClosure(\Closure $c, array $a, Stub $stub, $isNested)
    {
        $prefix = Caster::PREFIX_VIRTUAL;
        $c = new \ReflectionFunction($c);

        $stub->class = 'Closure'; // HHVM generates unique class names for closures
        $a = static::castFunctionAbstract($c, $a, $stub, $isNested);

        if (isset($a[$prefix.'parameters'])) {
            foreach ($a[$prefix.'parameters']->value as &$v) {
                $param = $v;
                $v = new EnumStub(array());
                foreach (static::castParameter($param, array(), $stub, true) as $k => $param) {
                    if ("\0" === $k[0]) {
                        $v->value[substr($k, 3)] = $param;
                    }
                }
                unset($v->value['position'], $v->value['isVariadic'], $v->value['byReference'], $v);
            }
        }

        if ($f = $c->getFileName()) {
            $a[$prefix.'file'] = $f;
            $a[$prefix.'line'] = $c->getStartLine().' to '.$c->getEndLine();
        }

        $prefix = Caster::PREFIX_DYNAMIC;
        unset($a['name'], $a[$prefix.'0'], $a[$prefix.'this'], $a[$prefix.'parameter'], $a[Caster::PREFIX_VIRTUAL.'extra']);

        return $a;
    }

    public static function castGenerator(\Generator $c, array $a, Stub $stub, $isNested)
    {
        return class_exists('ReflectionGenerator', false) ? self::castReflectionGenerator(new \ReflectionGenerator($c), $a, $stub, $isNested) : $a;
    }

    public static function castType(\ReflectionType $c, array $a, Stub $stub, $isNested)
    {
        $prefix = Caster::PREFIX_VIRTUAL;

        $a += array(
            $prefix.'type' => $c->__toString(),
            $prefix.'allowsNull' => $c->allowsNull(),
            $prefix.'isBuiltin' => $c->isBuiltin(),
        );

        return $a;
    }

    public static function castReflectionGenerator(\ReflectionGenerator $c, array $a, Stub $stub, $isNested)
    {
        $prefix = Caster::PREFIX_VIRTUAL;

        if ($c->getThis()) {
            $a[$prefix.'this'] = new CutStub($c->getThis());
        }
        $x = $c->getFunction();
        $frame = array(
            'class' => isset($x->class) ? $x->class : null,
            'type' => isset($x->class) ? ($x->isStatic() ? '::' : '->') : null,
            'function' => $x->name,
            'file' => $c->getExecutingFile(),
            'line' => $c->getExecutingLine(),
        );
        if ($trace = $c->getTrace(DEBUG_BACKTRACE_IGNORE_ARGS)) {
            $x = new \ReflectionGenerator($c->getExecutingGenerator());
            array_unshift($trace, array(
                'function' => 'yield',
                'file' => $x->getExecutingFile(),
                'line' => $x->getExecutingLine() - 1,
            ));
            $trace[] = $frame;
            $a[$prefix.'trace'] = new TraceStub($trace, false, 0, -1, -1);
        } else {
            $x = new FrameStub($frame, false, true);
            $x = ExceptionCaster::castFrameStub($x, array(), $x, true);
            $a[$prefix.'executing'] = new EnumStub(array(
                $frame['class'].$frame['type'].$frame['function'].'()' => $x[$prefix.'src'],
            ));
        }

        return $a;
    }

    public static function castClass(\ReflectionClass $c, array $a, Stub $stub, $isNested, $filter = 0)
    {
        $prefix = Caster::PREFIX_VIRTUAL;

        if ($n = \Reflection::getModifierNames($c->getModifiers())) {
            $a[$prefix.'modifiers'] = implode(' ', $n);
        }

        self::addMap($a, $c, array(
            'extends' => 'getParentClass',
            'implements' => 'getInterfaceNames',
            'constants' => 'getConstants',
        ));

        foreach ($c->getProperties() as $n) {
            $a[$prefix.'properties'][$n->name] = $n;
        }

        foreach ($c->getMethods() as $n) {
            $a[$prefix.'methods'][$n->name] = $n;
        }

        if (!($filter & Caster::EXCLUDE_VERBOSE) && !$isNested) {
            self::addExtra($a, $c);
        }

        return $a;
    }

    public static function castFunctionAbstract(\ReflectionFunctionAbstract $c, array $a, Stub $stub, $isNested, $filter = 0)
    {
        $prefix = Caster::PREFIX_VIRTUAL;

        self::addMap($a, $c, array(
            'returnsReference' => 'returnsReference',
            'returnType' => 'getReturnType',
            'class' => 'getClosureScopeClass',
            'this' => 'getClosureThis',
        ));

        if (isset($a[$prefix.'returnType'])) {
            $a[$prefix.'returnType'] = (string) $a[$prefix.'returnType'];
        }
        if (isset($a[$prefix.'this'])) {
            $a[$prefix.'this'] = new CutStub($a[$prefix.'this']);
        }

        foreach ($c->getParameters() as $v) {
            $k = '$'.$v->name;
            if ($v->isPassedByReference()) {
                $k = '&'.$k;
            }
            if (method_exists($v, 'isVariadic') && $v->isVariadic()) {
                $k = '...'.$k;
            }
            $a[$prefix.'parameters'][$k] = $v;
        }
        if (isset($a[$prefix.'parameters'])) {
            $a[$prefix.'parameters'] = new EnumStub($a[$prefix.'parameters']);
        }

        if ($v = $c->getStaticVariables()) {
            foreach ($v as $k => &$v) {
                $a[$prefix.'use']['$'.$k] = &$v;
            }
            unset($v);
            $a[$prefix.'use'] = new EnumStub($a[$prefix.'use']);
        }

        if (!($filter & Caster::EXCLUDE_VERBOSE) && !$isNested) {
            self::addExtra($a, $c);
        }

        // Added by HHVM
        unset($a[Caster::PREFIX_DYNAMIC.'static']);

        return $a;
    }

    public static function castMethod(\ReflectionMethod $c, array $a, Stub $stub, $isNested)
    {
        $a[Caster::PREFIX_VIRTUAL.'modifiers'] = implode(' ', \Reflection::getModifierNames($c->getModifiers()));

        return $a;
    }

    public static function castParameter(\ReflectionParameter $c, array $a, Stub $stub, $isNested)
    {
        $prefix = Caster::PREFIX_VIRTUAL;

        // Added by HHVM
        unset($a['info']);

        self::addMap($a, $c, array(
            'position' => 'getPosition',
            'isVariadic' => 'isVariadic',
            'byReference' => 'isPassedByReference',
        ));

        try {
            if (method_exists($c, 'hasType')) {
                if ($c->hasType()) {
                    $a[$prefix.'typeHint'] = $c->getType()->__toString();
                }
            } elseif ($c->isArray()) {
                $a[$prefix.'typeHint'] = 'array';
            } elseif (method_exists($c, 'isCallable') && $c->isCallable()) {
                $a[$prefix.'typeHint'] = 'callable';
            } elseif ($v = $c->getClass()) {
                $a[$prefix.'typeHint'] = $v->name;
            }
        } catch (\ReflectionException $e) {
            if (preg_match('/^Class ([^ ]++) does not exist$/', $e->getMessage(), $m)) {
                $a[$prefix.'typeHint'] = $m[1];
            }
        }

        try {
            $a[$prefix.'default'] = $v = $c->getDefaultValue();
            if (method_exists($c, 'isDefaultValueConstant') && $c->isDefaultValueConstant()) {
                $a[$prefix.'default'] = new ConstStub($c->getDefaultValueConstantName(), $v);
            }
        } catch (\ReflectionException $e) {
            if (isset($a[$prefix.'typeHint']) && $c->allowsNull()) {
                $a[$prefix.'default'] = null;
            }
        }

        return $a;
    }

    public static function castProperty(\ReflectionProperty $c, array $a, Stub $stub, $isNested)
    {
        $a[Caster::PREFIX_VIRTUAL.'modifiers'] = implode(' ', \Reflection::getModifierNames($c->getModifiers()));
        self::addExtra($a, $c);

        return $a;
    }

    public static function castExtension(\ReflectionExtension $c, array $a, Stub $stub, $isNested)
    {
        self::addMap($a, $c, array(
            'version' => 'getVersion',
            'dependencies' => 'getDependencies',
            'iniEntries' => 'getIniEntries',
            'isPersistent' => 'isPersistent',
            'isTemporary' => 'isTemporary',
            'constants' => 'getConstants',
            'functions' => 'getFunctions',
            'classes' => 'getClasses',
        ));

        return $a;
    }

    public static function castZendExtension(\ReflectionZendExtension $c, array $a, Stub $stub, $isNested)
    {
        self::addMap($a, $c, array(
            'version' => 'getVersion',
            'author' => 'getAuthor',
            'copyright' => 'getCopyright',
            'url' => 'getURL',
        ));

        return $a;
    }

    private static function addExtra(&$a, \Reflector $c)
    {
        $x = isset($a[Caster::PREFIX_VIRTUAL.'extra']) ? $a[Caster::PREFIX_VIRTUAL.'extra']->value : array();

        if (method_exists($c, 'getFileName') && $m = $c->getFileName()) {
            $x['file'] = $m;
            $x['line'] = $c->getStartLine().' to '.$c->getEndLine();
        }

        self::addMap($x, $c, self::$extraMap, '');

        if ($x) {
            $a[Caster::PREFIX_VIRTUAL.'extra'] = new EnumStub($x);
        }
    }

    private static function addMap(&$a, \Reflector $c, $map, $prefix = Caster::PREFIX_VIRTUAL)
    {
        foreach ($map as $k => $m) {
            if (method_exists($c, $m) && false !== ($m = $c->$m()) && null !== $m) {
                $a[$prefix.$k] = $m instanceof \Reflector ? $m->name : $m;
            }
        }
    }
}
