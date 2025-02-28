<?php

declare(strict_types = 1);

namespace PHPMicroTemplate;

use ArrayAccess;
use PHPMicroTemplate\Exception\FileSystemException;
use PHPMicroTemplate\Exception\SyntaxErrorException;
use PHPMicroTemplate\Exception\UndefinedSymbolException;

use function call_user_func_array;
use function in_array;
use function is_callable;

/**
 * Class Render
 *
 * @package PHPMicroTemplate
 */
class Render
{
    private const REGEX_VARIABLE = '(?<expression>(?<variable>(\w+|\'[^\']+\'))(?<nestedVariable>(\.\w+)*)(?<methodCall>\((?<parameter>[^{}%]*)\))?)';

    /** @var array */
    private $templates = [];
    /** @var string */
    private $basePath = '';
    /** @var callable */
    private $resolveErrorCallback;

    /**
     * Render constructor.
     *
     * @param string $basePath Provide a base path to the templates. If no base path is provided you must provide
     *                         correct absolute/relative paths for the renderTemplate() function calls
     */
    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath;
    }

    /**
     * Add a callback to handle resolve errors (eg. call to an unknown variable). By default a resolve error will lead
     * to an UnknownSymbolException.
     *
     * @param callable $resolveErrorCallback
     *
     * @return $this
     */
    public function onResolveError(callable $resolveErrorCallback): self
    {
        $this->resolveErrorCallback = $resolveErrorCallback;

        return $this;
    }

    /**
     * Render a template file
     *
     * @param string $template  The path to the template file
     * @param array  $variables The variables assigned to the template
     *
     * @return string
     * @throws FileSystemException
     * @throws UndefinedSymbolException
     * @throws SyntaxErrorException
     */
    public function renderTemplate(string $template, array $variables = []): string
    {
        return $this->renderTemplateString($this->getTemplate($template), $variables);
    }

    /**
     * Render a given template string
     *
     * @param string $template  The template string
     * @param array  $variables The variables assigned to the template
     *
     * @return string
     * @throws UndefinedSymbolException
     * @throws SyntaxErrorException
     */
    public function renderTemplateString(string $template, array $variables = []): string
    {
        $output = $this->indexControlStructure($template, 'foreach');
        $output = $this->indexControlStructure($output, 'if', ['else']);

        $output = $this->resolveLoops($output, $variables);
        $output = $this->resolveConditionals($output, $variables);

        return $this->replaceVariablesInTemplate($output, $variables);
    }

    /**
     * Replace variables in a given template section and execute function calls
     *
     * @param string $template  The template section
     * @param array  $variables The current variable scope
     *
     * @return string
     * @throws UndefinedSymbolException
     * @throws SyntaxErrorException
     */
    protected function replaceVariablesInTemplate(string $template, array $variables) : string
    {
        $template = preg_replace_callback(
            '/\{\{\s*' . self::REGEX_VARIABLE . '\s*\}\}/i',
            function (array $matches) use ($variables): string {
                return (string) $this->getValue($matches, $variables);
            },
            $template
        );

        return $template;
    }

    /**
     * Resolve loops in a given template section
     *
     * @param string $template  The template section
     * @param array  $variables The current variable scope
     *
     * @return string
     * @throws UndefinedSymbolException
     * @throws SyntaxErrorException
     */
    protected function resolveLoops(string $template, array $variables): string
    {
        return preg_replace_callback(
            '/\{%\s*foreach(?<index>-[\d]+-[\d]+-)\s+' . self::REGEX_VARIABLE . '\s+as\s+((?<key>\w+)\s*,\s*)?(?<value>\w+)\s*%\}' .
                '(?<body>.+)' .
            '\{%\s*endforeach\k<index>\s*%\}/si',
            function (array $matches) use ($variables): string {
                $output = '';

                foreach ($this->getValue($matches, $variables) as $key => $value) {
                    $scope = array_merge(
                        $variables,
                        [$matches['value'] => $value],
                        $matches['key'] ? [$matches['key'] => $key] : []
                    );

                    $output .= $this->replaceVariablesInTemplate(
                        $this->resolveConditionals(
                            $this->resolveLoops($matches['body'], $scope),
                            $scope
                        ),
                        $scope
                    );
                }
                return $output;
            },
            $template
        );
    }

    /**
     * Resolve conditionals in a given template section
     *
     * @param string $template  The template section
     * @param array  $variables The current variable scope
     *
     * @return string
     * @throws UndefinedSymbolException
     * @throws SyntaxErrorException
     */
    protected function resolveConditionals(string $template, array $variables): string
    {
        do {
            $template = preg_replace_callback(
                '/\{%\s*if(?<index>-[\d]+-[\d]+-)\s+(?<not>not\s+)?' . self::REGEX_VARIABLE . '\s*%\}' .
                    '(?<body>.+)' .
                '\{%\s*endif\k<index>\s*%\}/si',
                function (array $matches) use ($variables): string {
                    $conditionalBody = preg_split("/{%\s*else{$matches['index']}\s*%\}/si", $matches['body']);
                    $ifCondition = $this->getValue($matches, $variables);

                    if (!empty($matches['not']) xor $ifCondition) {
                        return $conditionalBody[0];
                    }

                    return $conditionalBody[1] ?? '';
                },
                $template,
                -1,
                $count
            );
        } while ($count > 0);

        return $template;
    }

    /**
     * Get the content for a template
     *
     * @param string $template
     *
     * @return string
     * @throws FileSystemException
     */
    protected function getTemplate(string $template) : string
    {
        if (isset($this->templates[$template])) {
            return $this->templates[$template];
        }

        $file = $this->basePath . $template;

        if (file_exists($file)) {
            $this->templates[$template] = file_get_contents($file);
        }

        if (!isset($this->templates[$template]) || !$this->templates[$template]) {
            unset($this->templates[$template]);
            throw new FileSystemException("Template $template not found");
        }

        return $this->templates[$template];
    }

    /**
     * Get a value for a given match (Either a plain value of the current scope or a function call, in this case return
     * the result of the called function)
     *
     * @param array $matches
     * @param array $variables
     *
     * @return mixed
     * @throws UndefinedSymbolException
     * @throws SyntaxErrorException
     */
    protected function getValue(array $matches, array $variables)
    {
        $resolved     = $variables;
        $variablePath = [$matches['variable']];

        if (!empty($matches['nestedVariable'])) {
            array_push($variablePath, ...explode('.', trim($matches['nestedVariable'], '.')));
        } else {
            $variable = trim($matches['variable']);
            if ($variable === 'true' || $variable === 'false') {
                return $variable === 'true';
            }

            if (substr($variable, 0, 1) === "'" && substr($variable, -1) === "'") {
                return trim($variable, "'");
            }

            if (is_numeric($variable)) {
                return +$variable;
            }
        }

        if (empty($matches['methodCall'])) {
            $this->resolveNestedVariable($resolved, $variablePath, $matches);

            return $resolved;
        }

        return $this->methodCall($matches, $variablePath, $variables);
    }

    /**
     * @param array $matches
     * @param array $variablePath
     * @param array $variables
     *
     * @return mixed
     *
     * @throws SyntaxErrorException
     * @throws UndefinedSymbolException
     */
    private function methodCall(array $matches, array $variablePath, array $variables)
    {
        $resolved = $variables;
        $method = array_pop($variablePath);

        if (empty($variablePath)) {
            $resolvedMethod = array_key_exists($method, $variables) ? $variables[$method] : $method;

            if (!is_callable($resolvedMethod)) {
                throw new UndefinedSymbolException(sprintf('Function %s not callable', $method));
            }

            return call_user_func_array(
                $resolvedMethod,
                $this->extractParameter($matches['parameter'] ?? '', $variables)
            );
        }

        if (!$this->resolveNestedVariable($resolved, $variablePath, $matches)) {
            // resolve error callback result
            return $resolved;
        }

        if (!is_object($resolved)) {
            throw new UndefinedSymbolException(
                sprintf('Trying to call %s on non-object %s', $method, implode('.', $variablePath))
            );
        }

        if (!is_callable([$resolved, $method])) {
            throw new UndefinedSymbolException(
                sprintf('Function %s on object %s not callable', $method, implode('.', $variablePath))
            );
        }

        return call_user_func_array(
            [$resolved, $method],
            $this->extractParameter($matches['parameter'] ?? '', $variables)
        );
    }

    /**
     * Resolve nested variable access and object property access
     *
     * @param mixed $resolved
     * @param array $variablePath
     * @param array $matches
     *
     * @return bool
     *
     * @throws UndefinedSymbolException
     */
    protected function resolveNestedVariable(&$resolved, array $variablePath, array $matches): bool
    {
        foreach ($variablePath as $variable) {
            if (is_object($resolved) && !($resolved instanceof ArrayAccess)) {
                if (property_exists($resolved, $variable)) {
                    $resolved = $resolved->$variable;

                    continue;
                }
            } elseif (isset($resolved[$variable]) || (is_array($resolved) && array_key_exists($variable, $resolved))) {
                $resolved = $resolved[$variable];

                continue;
            }

            if ($this->resolveErrorCallback) {
                $resolved = ($this->resolveErrorCallback)($matches['expression']);

                return false;
            }

            throw new UndefinedSymbolException(sprintf('Unknown variable %s', implode('.', $variablePath)));
        }

        return true;
    }

    /**
     * Index a control structure in a given template section so a handling of nested control structures of the same
     * type can be offered
     *
     * @param string $template             The template section
     * @param string $structure            The control structure (eg. 'foreach', 'if')
     * @param array  $additionalComponents [optional] Holds additional components for the structure (eg. 'else')
     *
     * @return string
     */
    protected function indexControlStructure(
        string $template,
        string $structure,
        array $additionalComponents = []
    ): string {
        $structureDepthCounter = 0;
        $levelCounter = [];

        return preg_replace_callback(
            '/\{%\s*(?<structure>' . $this->getControlStructureRegEx($structure, $additionalComponents) . ')/i',
            function (array $matches) use (&$structureDepthCounter, &$levelCounter, $additionalComponents): string {
                if (in_array($matches['structure'], $additionalComponents)) {
                    return sprintf(
                        '%s-%s-%s-',
                        $matches[0],
                        $levelCounter[$structureDepthCounter - 1],
                        ($structureDepthCounter - 1)
                    );
                }

                $levelCounter[$structureDepthCounter] = $levelCounter[$structureDepthCounter] ?? 0;
                $isEndTag = strpos($matches['structure'], 'end') === 0;
                ($isEndTag) ? --$structureDepthCounter : $levelCounter[$structureDepthCounter]++;

                return sprintf(
                    '%s-%s-%s-',
                    $matches[0],
                    $levelCounter[$structureDepthCounter],
                    ($isEndTag ? $structureDepthCounter : $structureDepthCounter++)
                );
            },
            $template
        );
    }

    /**
     * Get the regular expression for finding control structures
     *
     * @param string $structure            The control structure (eg. 'foreach', 'if')
     * @param array  $additionalComponents [optional] Holds additional components for the structure (eg. 'else')
     *
     * @return string
     */
    protected function getControlStructureRegEx(string $structure, array $additionalComponents): string
    {
        $structureRegex = "(end)?$structure";

        if ($additionalComponents) {
            $structureRegex = "($structureRegex|" . join('|', $additionalComponents) . ')';
        }

        return $structureRegex;
    }

    /**
     * Extract a parameter from a given string
     *
     * @param string $parameter The parameter string of a function
     * @param array  $variables The current scope
     *
     * @return array
     * @throws SyntaxErrorException
     * @throws UndefinedSymbolException
     */
    protected function extractParameter(string $parameter, array $variables): array
    {
        if (empty($parameter)) {
            return [];
        }

        $result = preg_match(
            '/^\s*' . self::REGEX_VARIABLE . '(\s*,\s*(?<next>.+))?\s*$/is',
            $parameter,
            $matches
        );

        if ($result === 0) {
            throw new SyntaxErrorException("Invalid parameter list $parameter");
        }

        return empty($matches['next'])
            ? [$this->getValue($matches, $variables)]
            : array_merge(
                [$this->getValue($matches, $variables)],
                $this->extractParameter($matches['next'], $variables)
            );
    }
}
