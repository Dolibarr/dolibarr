<?php
namespace Luracast\Restler\UI;

use Luracast\Restler\CommentParser;
use Luracast\Restler\Data\ApiMethodInfo;
use Luracast\Restler\Data\Text;
use Luracast\Restler\Data\ValidationInfo;
use Luracast\Restler\Data\Validator;
use Luracast\Restler\Defaults;
use Luracast\Restler\Format\UploadFormat;
use Luracast\Restler\Format\UrlEncodedFormat;
use Luracast\Restler\iFilter;
use Luracast\Restler\RestException;
use Luracast\Restler\Restler;
use Luracast\Restler\Routes;
use Luracast\Restler\Scope;
use Luracast\Restler\UI\Tags as T;
use Luracast\Restler\User;
use Luracast\Restler\Util;


/**
 * Utility class for automatically generating forms for the given http method
 * and api url
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class Forms implements iFilter
{
    const FORM_KEY = 'form_key';
    public static $filterFormRequestsOnly = false;

    public static $excludedPaths = array();

    public static $style;
    /**
     * @var bool should we fill up the form using given data?
     */
    public static $preFill = true;
    /**
     * @var ValidationInfo
     */
    public static $validationInfo = null;
    protected static $inputTypes = array(
        'hidden',
        'password',
        'button',
        'image',
        'file',
        'reset',
        'submit',
        'search',
        'checkbox',
        'radio',
        'email',
        'text',
        'color',
        'date',
        'datetime',
        'datetime-local',
        'email',
        'month',
        'number',
        'range',
        'search',
        'tel',
        'time',
        'url',
        'week',
    );
    protected static $fileUpload = false;
    private static $key = array();
    /**
     * @var ApiMethodInfo;
     */
    private static $info;

    /**
     * Get the form
     *
     * @param string $method   http method to submit the form
     * @param string $action   relative path from the web root. When set to null
     *                         it uses the current api method's path
     * @param bool   $dataOnly if you want to render the form yourself use this
     *                         option
     * @param string $prefix   used for adjusting the spacing in front of
     *                         form elements
     * @param string $indent   used for adjusting indentation
     *
     * @return array|T
     *
     * @throws \Luracast\Restler\RestException
     */
    public static function get($method = 'POST', $action = null, $dataOnly = false, $prefix = '', $indent = '    ')
    {
        if (!static::$style)
            static::$style = FormStyles::$html;

        try {
            /** @var Restler $restler */
            $restler = Scope::get('Restler');
            if (is_null($action))
                $action = $restler->url;

            $info = $restler->url == $action
            && Util::getRequestMethod() == $method
                ? $restler->apiMethodInfo
                : Routes::find(
                    trim($action, '/'),
                    $method,
                    $restler->getRequestedApiVersion(),
                    static::$preFill ||
                    ($restler->requestMethod == $method &&
                        $restler->url == $action)
                        ? $restler->getRequestData()
                        : array()
                );

        } catch (RestException $e) {
            //echo $e->getErrorMessage();
            $info = false;
        }
        if (!$info)
            throw new RestException(500, 'invalid action path for form `' . $method . ' ' . $action . '`');
        static::$info = $info;
        $m = $info->metadata;
        $r = static::fields($dataOnly);
        if ($method != 'GET' && $method != 'POST') {
            if (empty(Defaults::$httpMethodOverrideProperty))
                throw new RestException(
                    500,
                    'Forms require `Defaults::\$httpMethodOverrideProperty`' .
                    "for supporting HTTP $method"
                );
            if ($dataOnly) {
                $r[] = array(
                    'tag' => 'input',
                    'name' => Defaults::$httpMethodOverrideProperty,
                    'type' => 'hidden',
                    'value' => 'method',
                );
            } else {
                $r[] = T::input()
                    ->name(Defaults::$httpMethodOverrideProperty)
                    ->value($method)
                    ->type('hidden');
            }

            $method = 'POST';
        }
        if (session_id() != '') {
            $form_key = static::key($method, $action);
            if ($dataOnly) {
                $r[] = array(
                    'tag' => 'input',
                    'name' => static::FORM_KEY,
                    'type' => 'hidden',
                    'value' => 'hidden',
                );
            } else {
                $key = T::input()
                    ->name(static::FORM_KEY)
                    ->type('hidden')
                    ->value($form_key);
                $r[] = $key;
            }
        }

        $s = array(
            'tag' => 'button',
            'type' => 'submit',
            'label' =>
                Util::nestedValue($m, 'return', CommentParser::$embeddedDataName, 'label')
                    ? : 'Submit'
        );

        if (!$dataOnly)
            $s = Emmet::make(static::style('submit', $m), $s);
        $r[] = $s;
        $t = array(
            'action' => $restler->getBaseUrl() . '/' . rtrim($action, '/'),
            'method' => $method,
        );
        if (static::$fileUpload) {
            static::$fileUpload = false;
            $t['enctype'] = 'multipart/form-data';
        }
        if (isset($m[CommentParser::$embeddedDataName])) {
            $t += $m[CommentParser::$embeddedDataName];
        }
        if (!$dataOnly) {
            $t = Emmet::make(static::style('form', $m), $t);
            $t->prefix = $prefix;
            $t->indent = $indent;
            $t[] = $r;
        } else {
            $t['fields'] = $r;
        }
        return $t;
    }

    public static function style($name, array $metadata, $type = '')
    {
        return isset($metadata[CommentParser::$embeddedDataName][$name])
            ? $metadata[CommentParser::$embeddedDataName][$name]
            : (!empty($type) && isset(static::$style["$name-$type"])
                ? static::$style["$name-$type"]
                : (isset(static::$style[$name])
                    ? static::$style[$name]
                    : null
                )
            );
    }

    public static function fields($dataOnly = false)
    {
        $m = static::$info->metadata;
        $params = $m['param'];
        $values = static::$info->parameters;
        $r = array();
        foreach ($params as $k => $p) {
            $value = Util::nestedValue($values, $k);
            if (
                is_scalar($value) ||
                ($p['type'] == 'array' && is_array($value) && $value == array_values($value)) ||
                is_object($value) && $p['type'] == get_class($value)
            )
                $p['value'] = $value;
            static::$validationInfo = $v = new ValidationInfo($p);
            if ($v->from == 'path')
                continue;
            if (!empty($v->children)) {
                $t = Emmet::make(static::style('fieldset', $m), array('label' => $v->label));
                foreach ($v->children as $n => $c) {
                    $value = Util::nestedValue($v->value, $n);
                    if (
                        is_scalar($value) ||
                        ($c['type'] == 'array' && is_array($value) && $value == array_values($value)) ||
                        is_object($value) && $c['type'] == get_class($value)
                    )
                        $c['value'] = $value;
                    static::$validationInfo = $vc = new ValidationInfo($c);
                    if ($vc->from == 'path')
                        continue;
                    $vc->name = $v->name . '[' . $vc->name . ']';
                    $t [] = static::field($vc, $dataOnly);
                }
                $r[] = $t;
                static::$validationInfo = null;
            } else {
                $f = static::field($v, $dataOnly);
                $r [] = $f;
            }
            static::$validationInfo = null;
        }
        return $r;
    }

    /**
     * @param ValidationInfo $p
     *
     * @param bool           $dataOnly
     *
     * @return array|T
     */
    public static function field(ValidationInfo $p, $dataOnly = false)
    {
        if (is_string($p->value)) {
            //prevent XSS attacks
            $p->value = htmlspecialchars($p->value, ENT_QUOTES | ENT_HTML401, 'UTF-8');
        }
        $type = $p->field ? : static::guessFieldType($p);
        $tag = in_array($type, static::$inputTypes)
            ? 'input' : $type;
        $options = array();
        $name = $p->name;
        $multiple = null;
        if ($p->type == 'array' && $p->contentType != 'associative') {
            $name .= '[]';
            $multiple = true;
        }
        if ($p->choice) {
            foreach ($p->choice as $i => $choice) {
                $option = array('name' => $name, 'value' => $choice);
                $option['text'] = isset($p->rules['select'][$i])
                    ? $p->rules['select'][$i]
                    : $choice;
                if ($choice == $p->value)
                    $option['selected'] = true;
                $options[] = $option;
            }
        } elseif ($p->type == 'boolean' || $p->type == 'bool') {
            if (Text::beginsWith($type, 'radio') || Text::beginsWith($type, 'select')) {
                $options[] = array('name' => $p->name, 'text' => ' Yes ',
                    'value' => 'true');
                $options[] = array('name' => $p->name, 'text' => ' No ',
                    'value' => 'false');
                if ($p->value || $p->default)
                    $options[0]['selected'] = true;
            } else { //checkbox
                $r = array(
                    'tag' => $tag,
                    'name' => $name,
                    'type' => $type,
                    'label' => $p->label,
                    'value' => 'true',
                    'default' => $p->default,
                );
                $r['text'] = 'Yes';
                if ($p->default) {
                    $r['selected'] = true;
                }
                if (isset($p->rules)) {
                    $r += $p->rules;
                }
            }
        }
        if (empty($r)) {
            $r = array(
                'tag' => $tag,
                'name' => $name,
                'type' => $type,
                'label' => $p->label,
                'value' => $p->value,
                'default' => $p->default,
                'options' => & $options,
                'multiple' => $multiple,
            );
            if (isset($p->rules)) {
                $r += $p->rules;
            }
        }
        if ($type == 'file') {
            static::$fileUpload = true;
            if (empty($r['accept'])) {
                $r['accept'] = implode(', ', UploadFormat::$allowedMimeTypes);
            }
        }
        if (!empty(Validator::$exceptions[$name]) && static::$info->url == Scope::get('Restler')->url) {
            $r['error'] = 'has-error';
            $r['message'] = Validator::$exceptions[$p->name]->getMessage();
        }

        if (true === $p->required)
            $r['required'] = 'required';
        if (isset($p->rules['autofocus']))
            $r['autofocus'] = 'autofocus';
        /*
        echo "<pre>";
        print_r($r);
        echo "</pre>";
        */
        if ($dataOnly)
            return $r;
        if (isset($p->rules['form']))
            return Emmet::make($p->rules['form'], $r);
        $m = static::$info->metadata;
        $t = Emmet::make(static::style($type, $m, $p->type) ? : static::style($tag, $m, $p->type), $r);
        return $t;
    }

    protected static function guessFieldType(ValidationInfo $p, $type = 'type')
    {
        if (in_array($p->$type, static::$inputTypes))
            return $p->$type;
        if ($p->choice)
            return $p->type == 'array' ? 'checkbox' : 'select';
        switch ($p->$type) {
            case 'boolean':
                return 'radio';
            case 'int':
            case 'number':
            case 'float':
                return 'number';
            case 'array':
                return static::guessFieldType($p, 'contentType');
        }
        if ($p->name == 'password')
            return 'password';
        return 'text';
    }

    /**
     * Get the form key
     *
     * @param string $method   http method for form key
     * @param string $action   relative path from the web root. When set to null
     *                         it uses the current api method's path
     *
     * @return string generated form key
     */
    public static function key($method = 'POST', $action = null)
    {
        if (is_null($action))
            $action = Scope::get('Restler')->url;
        $target = "$method $action";
        if (empty(static::$key[$target]))
            static::$key[$target] = md5($target . User::getIpAddress() . uniqid(mt_rand()));
        $_SESSION[static::FORM_KEY] = static::$key;
        return static::$key[$target];
    }

    /**
     * Access verification method.
     *
     * API access will be denied when this method returns false
     *
     * @return boolean true when api access is allowed false otherwise
     *
     * @throws RestException 403 security violation
     */
    public function __isAllowed()
    {
        if (session_id() == '') {
            session_start();
        }
        /** @var Restler $restler */
        $restler = $this->restler;
        $url = $restler->url;
        foreach (static::$excludedPaths as $exclude) {
            if (empty($exclude)) {
                if ($url == $exclude)
                    return true;
            } elseif (Text::beginsWith($url, $exclude)) {
                return true;
            }
        }
        $check = static::$filterFormRequestsOnly
            ? $restler->requestFormat instanceof UrlEncodedFormat || $restler->requestFormat instanceof UploadFormat
            : true;
        if (!empty($_POST) && $check) {
            if (
                isset($_POST[static::FORM_KEY]) &&
                ($target = Util::getRequestMethod() . ' ' . $restler->url) &&
                isset($_SESSION[static::FORM_KEY][$target]) &&
                $_POST[static::FORM_KEY] == $_SESSION[static::FORM_KEY][$target]
            ) {
                return true;
            }
            throw new RestException(403, 'Insecure form submission');
        }
        return true;
    }
}