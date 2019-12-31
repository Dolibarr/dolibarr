<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\Renderable;

/**
 * Renders the debug bar using the client side javascript implementation
 *
 * Generates all the needed initialization code of controls
 */
class JavascriptRenderer
{
    const INITIALIZE_CONSTRUCTOR = 2;

    const INITIALIZE_CONTROLS = 4;

    const REPLACEABLE_TAG = "{--DEBUGBAR_OB_START_REPLACE_ME--}";

    const RELATIVE_PATH = 'path';

    const RELATIVE_URL = 'url';

    protected $debugBar;

    protected $baseUrl;

    protected $basePath;

    protected $cssVendors = array(
        'fontawesome' => 'vendor/font-awesome/css/font-awesome.min.css',
        'highlightjs' => 'vendor/highlightjs/styles/github.css'
    );

    protected $jsVendors = array(
        'jquery' => 'vendor/jquery/dist/jquery.min.js',
        'highlightjs' => 'vendor/highlightjs/highlight.pack.js'
    );

    protected $includeVendors = true;

    protected $cssFiles = array('debugbar.css', 'widgets.css', 'openhandler.css');

    protected $jsFiles = array('debugbar.js', 'widgets.js', 'openhandler.js');

    protected $additionalAssets = array();

    protected $javascriptClass = 'PhpDebugBar.DebugBar';

    protected $variableName = 'phpdebugbar';

    protected $enableJqueryNoConflict = true;

    protected $initialization;

    protected $controls = array();

    protected $ignoredCollectors = array();

    protected $ajaxHandlerClass = 'PhpDebugBar.AjaxHandler';

    protected $ajaxHandlerBindToJquery = true;

    protected $ajaxHandlerBindToXHR = false;

    protected $openHandlerClass = 'PhpDebugBar.OpenHandler';

    protected $openHandlerUrl;

    /**
     * @param \DebugBar\DebugBar $debugBar
     * @param string $baseUrl
     * @param string $basePath
     */
    public function __construct(DebugBar $debugBar, $baseUrl = null, $basePath = null)
    {
        $this->debugBar = $debugBar;

        if ($baseUrl === null) {
            $baseUrl = '/vendor/maximebf/debugbar/src/DebugBar/Resources';
        }
        $this->baseUrl = $baseUrl;

        if ($basePath === null) {
            $basePath = __DIR__ . DIRECTORY_SEPARATOR . 'Resources';
        }
        $this->basePath = $basePath;

        // bitwise operations cannot be done in class definition :(
        $this->initialization = self::INITIALIZE_CONSTRUCTOR | self::INITIALIZE_CONTROLS;
    }

    /**
     * Sets options from an array
     *
     * Options:
     *  - base_path
     *  - base_url
     *  - include_vendors
     *  - javascript_class
     *  - variable_name
     *  - initialization
     *  - enable_jquery_noconflict
     *  - controls
     *  - disable_controls
     *  - ignore_collectors
     *  - ajax_handler_classname
     *  - ajax_handler_bind_to_jquery
     *  - open_handler_classname
     *  - open_handler_url
     *
     * @param array $options [description]
     */
    public function setOptions(array $options)
    {
        if (array_key_exists('base_path', $options)) {
            $this->setBasePath($options['base_path']);
        }
        if (array_key_exists('base_url', $options)) {
            $this->setBaseUrl($options['base_url']);
        }
        if (array_key_exists('include_vendors', $options)) {
            $this->setIncludeVendors($options['include_vendors']);
        }
        if (array_key_exists('javascript_class', $options)) {
            $this->setJavascriptClass($options['javascript_class']);
        }
        if (array_key_exists('variable_name', $options)) {
            $this->setVariableName($options['variable_name']);
        }
        if (array_key_exists('initialization', $options)) {
            $this->setInitialization($options['initialization']);
        }
        if (array_key_exists('enable_jquery_noconflict', $options)) {
            $this->setEnableJqueryNoConflict($options['enable_jquery_noconflict']);
        }
        if (array_key_exists('controls', $options)) {
            foreach ($options['controls'] as $name => $control) {
                $this->addControl($name, $control);
            }
        }
        if (array_key_exists('disable_controls', $options)) {
            foreach ((array) $options['disable_controls'] as $name) {
                $this->disableControl($name);
            }
        }
        if (array_key_exists('ignore_collectors', $options)) {
            foreach ((array) $options['ignore_collectors'] as $name) {
                $this->ignoreCollector($name);
            }
        }
        if (array_key_exists('ajax_handler_classname', $options)) {
            $this->setAjaxHandlerClass($options['ajax_handler_classname']);
        }
        if (array_key_exists('ajax_handler_bind_to_jquery', $options)) {
            $this->setBindAjaxHandlerToJquery($options['ajax_handler_bind_to_jquery']);
        }
        if (array_key_exists('open_handler_classname', $options)) {
            $this->setOpenHandlerClass($options['open_handler_classname']);
        }
        if (array_key_exists('open_handler_url', $options)) {
            $this->setOpenHandlerUrl($options['open_handler_url']);
        }
    }

    /**
     * Sets the path which assets are relative to
     *
     * @param string $path
     */
    public function setBasePath($path)
    {
        $this->basePath = $path;
        return $this;
    }

    /**
     * Returns the path which assets are relative to
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Sets the base URL from which assets will be served
     *
     * @param string $url
     */
    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * Returns the base URL from which assets will be served
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Whether to include vendor assets
     *
     * You can only include js or css vendors using
     * setIncludeVendors('css') or setIncludeVendors('js')
     *
     * @param boolean $enabled
     */
    public function setIncludeVendors($enabled = true)
    {
        if (is_string($enabled)) {
            $enabled = array($enabled);
        }
        $this->includeVendors = $enabled;

        if (!$enabled || (is_array($enabled) && !in_array('js', $enabled))) {
            // no need to call jQuery.noConflict() if we do not include our own version
            $this->enableJqueryNoConflict = false;
        }

        return $this;
    }

    /**
     * Checks if vendors assets are included
     *
     * @return boolean
     */
    public function areVendorsIncluded()
    {
        return $this->includeVendors !== false;
    }

    /**
     * Disable a specific vendor's assets.
     *
     * @param  string $name "jquery", "fontawesome", "highlightjs"
     *
     * @return void
     */
    public function disableVendor($name)
    {
        if (array_key_exists($name, $this->cssVendors)) {
            unset($this->cssVendors[$name]);
        }
        if (array_key_exists($name, $this->jsVendors)) {
            unset($this->jsVendors[$name]);
        }
    }

    /**
     * Sets the javascript class name
     *
     * @param string $className
     */
    public function setJavascriptClass($className)
    {
        $this->javascriptClass = $className;
        return $this;
    }

    /**
     * Returns the javascript class name
     *
     * @return string
     */
    public function getJavascriptClass()
    {
        return $this->javascriptClass;
    }

    /**
     * Sets the variable name of the class instance
     *
     * @param string $name
     */
    public function setVariableName($name)
    {
        $this->variableName = $name;
        return $this;
    }

    /**
     * Returns the variable name of the class instance
     *
     * @return string
     */
    public function getVariableName()
    {
        return $this->variableName;
    }

    /**
     * Sets what should be initialized
     *
     *  - INITIALIZE_CONSTRUCTOR: only initializes the instance
     *  - INITIALIZE_CONTROLS: initializes the controls and data mapping
     *  - INITIALIZE_CONSTRUCTOR | INITIALIZE_CONTROLS: initialize everything (default)
     *
     * @param integer $init
     */
    public function setInitialization($init)
    {
        $this->initialization = $init;
        return $this;
    }

    /**
     * Returns what should be initialized
     *
     * @return integer
     */
    public function getInitialization()
    {
        return $this->initialization;
    }

    /**
     * Sets whether to call jQuery.noConflict()
     *
     * @param boolean $enabled
     */
    public function setEnableJqueryNoConflict($enabled = true)
    {
        $this->enableJqueryNoConflict = $enabled;
        return $this;
    }

    /**
     * Checks if jQuery.noConflict() will be called
     *
     * @return boolean
     */
    public function isJqueryNoConflictEnabled()
    {
        return $this->enableJqueryNoConflict;
    }

    /**
     * Adds a control to initialize
     *
     * Possible options:
     *  - icon: icon name
     *  - tooltip: string
     *  - widget: widget class name
     *  - title: tab title
     *  - map: a property name from the data to map the control to
     *  - default: a js string, default value of the data map
     *
     * "icon" or "widget" are at least needed
     *
     * @param string $name
     * @param array $options
     */
    public function addControl($name, array $options)
    {
        if (count(array_intersect(array_keys($options), array('icon', 'widget', 'tab', 'indicator'))) === 0) {
            throw new DebugBarException("Not enough options for control '$name'");
        }
        $this->controls[$name] = $options;
        return $this;
    }

    /**
     * Disables a control
     *
     * @param string $name
     */
    public function disableControl($name)
    {
        $this->controls[$name] = null;
        return $this;
    }

    /**
     * Returns the list of controls
     *
     * This does not include controls provided by collectors
     *
     * @return array
     */
    public function getControls()
    {
        return $this->controls;
    }

    /**
     * Ignores widgets provided by a collector
     *
     * @param string $name
     */
    public function ignoreCollector($name)
    {
        $this->ignoredCollectors[] = $name;
        return $this;
    }

    /**
     * Returns the list of ignored collectors
     *
     * @return array
     */
    public function getIgnoredCollectors()
    {
        return $this->ignoredCollectors;
    }

    /**
     * Sets the class name of the ajax handler
     *
     * Set to false to disable
     *
     * @param string $className
     */
    public function setAjaxHandlerClass($className)
    {
        $this->ajaxHandlerClass = $className;
        return $this;
    }

    /**
     * Returns the class name of the ajax handler
     *
     * @return string
     */
    public function getAjaxHandlerClass()
    {
        return $this->ajaxHandlerClass;
    }

    /**
     * Sets whether to call bindToJquery() on the ajax handler
     *
     * @param boolean $bind
     */
    public function setBindAjaxHandlerToJquery($bind = true)
    {
        $this->ajaxHandlerBindToJquery = $bind;
        return $this;
    }

    /**
     * Checks whether bindToJquery() will be called on the ajax handler
     *
     * @return boolean
     */
    public function isAjaxHandlerBoundToJquery()
    {
        return $this->ajaxHandlerBindToJquery;
    }

    /**
     * Sets whether to call bindToXHR() on the ajax handler
     *
     * @param boolean $bind
     */
    public function setBindAjaxHandlerToXHR($bind = true)
    {
        $this->ajaxHandlerBindToXHR = $bind;
        return $this;
    }

    /**
     * Checks whether bindToXHR() will be called on the ajax handler
     *
     * @return boolean
     */
    public function isAjaxHandlerBoundToXHR()
    {
        return $this->ajaxHandlerBindToXHR;
    }

    /**
     * Sets the class name of the js open handler
     *
     * @param string $className
     */
    public function setOpenHandlerClass($className)
    {
        $this->openHandlerClass = $className;
        return $this;
    }

    /**
     * Returns the class name of the js open handler
     *
     * @return string
     */
    public function getOpenHandlerClass()
    {
        return $this->openHandlerClass;
    }

    /**
     * Sets the url of the open handler
     *
     * @param string $url
     */
    public function setOpenHandlerUrl($url)
    {
        $this->openHandlerUrl = $url;
        return $this;
    }

    /**
     * Returns the url for the open handler
     *
     * @return string
     */
    public function getOpenHandlerUrl()
    {
        return $this->openHandlerUrl;
    }

    /**
     * Add assets to render in the head
     *
     * @param array $cssFiles An array of filenames
     * @param array $jsFiles  An array of filenames
     * @param string $basePath Base path of those files
     * @param string $baseUrl  Base url of those files
     */
    public function addAssets($cssFiles, $jsFiles, $basePath = null, $baseUrl = null)
    {
        $this->additionalAssets[] = array(
            'base_path' => $basePath,
            'base_url' => $baseUrl,
            'css' => (array) $cssFiles,
            'js' => (array) $jsFiles
        );
        return $this;
    }

    /**
     * Returns the list of asset files
     *
     * @param string $type Only return css or js files
     * @param string $relativeTo The type of path to which filenames must be relative (path, url or null)
     * @return array
     */
    public function getAssets($type = null, $relativeTo = self::RELATIVE_PATH)
    {
        $cssFiles = $this->cssFiles;
        $jsFiles = $this->jsFiles;

        if ($this->includeVendors !== false) {
            if ($this->includeVendors === true || in_array('css', $this->includeVendors)) {
                $cssFiles = array_merge($this->cssVendors, $cssFiles);
            }
            if ($this->includeVendors === true || in_array('js', $this->includeVendors)) {
                $jsFiles = array_merge($this->jsVendors, $jsFiles);
            }
        }

        if ($relativeTo) {
            $root = $this->getRelativeRoot($relativeTo, $this->basePath, $this->baseUrl);
            $cssFiles = $this->makeUriRelativeTo($cssFiles, $root);
            $jsFiles = $this->makeUriRelativeTo($jsFiles, $root);
        }

        $additionalAssets = $this->additionalAssets;
        // finds assets provided by collectors
        foreach ($this->debugBar->getCollectors() as $collector) {
            if (($collector instanceof AssetProvider) && !in_array($collector->getName(), $this->ignoredCollectors)) {
                $additionalAssets[] = $collector->getAssets();
            }
        }

        foreach ($additionalAssets as $assets) {
            $basePath = isset($assets['base_path']) ? $assets['base_path'] : null;
            $baseUrl = isset($assets['base_url']) ? $assets['base_url'] : null;
            $root = $this->getRelativeRoot($relativeTo,
                $this->makeUriRelativeTo($basePath, $this->basePath),
                $this->makeUriRelativeTo($baseUrl, $this->baseUrl));
            $cssFiles = array_merge($cssFiles, $this->makeUriRelativeTo((array) $assets['css'], $root));
            $jsFiles = array_merge($jsFiles, $this->makeUriRelativeTo((array) $assets['js'], $root));
        }

        return $this->filterAssetArray(array($cssFiles, $jsFiles), $type);
    }

    /**
     * Returns the correct base according to the type
     *
     * @param string $relativeTo
     * @param string $basePath
     * @param string $baseUrl
     * @return string
     */
    protected function getRelativeRoot($relativeTo, $basePath, $baseUrl)
    {
        if ($relativeTo === self::RELATIVE_PATH) {
            return $basePath;
        }
        if ($relativeTo === self::RELATIVE_URL) {
            return $baseUrl;
        }
        return null;
    }

    /**
     * Makes a URI relative to another
     *
     * @param string|array $uri
     * @param string $root
     * @return string
     */
    protected function makeUriRelativeTo($uri, $root)
    {
        if (!$root) {
            return $uri;
        }

        if (is_array($uri)) {
            $uris = array();
            foreach ($uri as $u) {
                $uris[] = $this->makeUriRelativeTo($u, $root);
            }
            return $uris;
        }

        if (substr($uri, 0, 1) === '/' || preg_match('/^([a-zA-Z]+:\/\/|[a-zA-Z]:\/|[a-zA-Z]:\\\)/', $uri)) {
            return $uri;
        }
        return rtrim($root, '/') . "/$uri";
    }

    /**
     * Filters a tuple of (css, js) assets according to $type
     *
     * @param array $array
     * @param string $type 'css', 'js' or null for both
     * @return array
     */
    protected function filterAssetArray($array, $type = null)
    {
        $type = strtolower($type);
        if ($type === 'css') {
            return $array[0];
        }
        if ($type === 'js') {
            return $array[1];
        }
        return $array;
    }

    /**
     * Returns a tuple where the both items are Assetic AssetCollection,
     * the first one being css files and the second js files
     *
     * @param string $type Only return css or js collection
     * @return array or \Assetic\Asset\AssetCollection
     */
    public function getAsseticCollection($type = null)
    {
        list($cssFiles, $jsFiles) = $this->getAssets();
        return $this->filterAssetArray(array(
            $this->createAsseticCollection($cssFiles),
            $this->createAsseticCollection($jsFiles)
        ), $type);
    }

    /**
     * Create an Assetic AssetCollection with the given files.
     * Filenames will be converted to absolute path using
     * the base path.
     *
     * @param array $files
     * @return \Assetic\Asset\AssetCollection
     */
    protected function createAsseticCollection($files)
    {
        $assets = array();
        foreach ($files as $file) {
            $assets[] = new \Assetic\Asset\FileAsset($file);
        }
        return new \Assetic\Asset\AssetCollection($assets);
    }

    /**
     * Write all CSS assets to standard output or in a file
     *
     * @param string $targetFilename
     */
    public function dumpCssAssets($targetFilename = null)
    {
        $this->dumpAssets($this->getAssets('css'), $targetFilename);
    }

    /**
     * Write all JS assets to standard output or in a file
     *
     * @param string $targetFilename
     */
    public function dumpJsAssets($targetFilename = null)
    {
        $this->dumpAssets($this->getAssets('js'), $targetFilename);
    }

    /**
     * Write assets to standard output or in a file
     *
     * @param array $files
     * @param string $targetFilename
     */
    protected function dumpAssets($files, $targetFilename = null)
    {
        $content = '';
        foreach ($files as $file) {
            $content .= file_get_contents($file) . "\n";
        }
        if ($targetFilename !== null) {
            file_put_contents($targetFilename, $content);
        } else {
            echo $content;
        }
    }

    /**
     * Renders the html to include needed assets
     *
     * Only useful if Assetic is not used
     *
     * @return string
     */
    public function renderHead()
    {
        list($cssFiles, $jsFiles) = $this->getAssets(null, self::RELATIVE_URL);
        $html = '';

        foreach ($cssFiles as $file) {
            $html .= sprintf('<link rel="stylesheet" type="text/css" href="%s">' . "\n", $file);
        }

        foreach ($jsFiles as $file) {
            $html .= sprintf('<script type="text/javascript" src="%s"></script>' . "\n", $file);
        }

        if ($this->enableJqueryNoConflict) {
            $html .= '<script type="text/javascript">jQuery.noConflict(true);</script>' . "\n";
        }

        return $html;
    }

    /**
     * Register shutdown to display the debug bar
     *
     * @param boolean $here Set position of HTML. True if is to current position or false for end file
     * @param boolean $initialize Whether to render the de bug bar initialization code
     * @return string Return "{--DEBUGBAR_OB_START_REPLACE_ME--}" or return an empty string if $here == false
     */
    public function renderOnShutdown($here = true, $initialize = true, $renderStackedData = true, $head = false)
    {
        register_shutdown_function(array($this, "replaceTagInBuffer"), $here, $initialize, $renderStackedData, $head);

        if (ob_get_level() === 0) {
            ob_start();
        }

        return ($here) ? self::REPLACEABLE_TAG : "";
    }

    /**
     * Same as renderOnShutdown() with $head = true
     *
     * @param boolean $here
     * @param boolean $initialize
     * @param boolean $renderStackedData
     * @return string
     */
    public function renderOnShutdownWithHead($here = true, $initialize = true, $renderStackedData = true)
    {
        return $this->renderOnShutdown($here, $initialize, $renderStackedData, true);
    }

    /**
     * Is callback function for register_shutdown_function(...)
     *
     * @param boolean $here Set position of HTML. True if is to current position or false for end file
     * @param boolean $initialize Whether to render the de bug bar initialization code
     */
    public function replaceTagInBuffer($here = true, $initialize = true, $renderStackedData = true, $head = false)
    {
        $render = ($head ? $this->renderHead() : "")
                . $this->render($initialize, $renderStackedData);

        $current = ($here && ob_get_level() > 0) ? ob_get_clean() : self::REPLACEABLE_TAG;

        echo str_replace(self::REPLACEABLE_TAG, $render, $current, $count);

        if ($count === 0) {
            echo $render;
        }
    }

    /**
     * Returns the code needed to display the debug bar
     *
     * AJAX request should not render the initialization code.
     *
     * @param boolean $initialize Whether to render the de bug bar initialization code
     * @return string
     */
    public function render($initialize = true, $renderStackedData = true)
    {
        $js = '';

        if ($initialize) {
            $js = $this->getJsInitializationCode();
        }

        if ($renderStackedData && $this->debugBar->hasStackedData()) {
            foreach ($this->debugBar->getStackedData() as $id => $data) {
                $js .= $this->getAddDatasetCode($id, $data, '(stacked)');
            }
        }

        $suffix = !$initialize ? '(ajax)' : null;
        $js .= $this->getAddDatasetCode($this->debugBar->getCurrentRequestId(), $this->debugBar->getData(), $suffix);

        return "<script type=\"text/javascript\">\n$js\n</script>\n";
    }

    /**
     * Returns the js code needed to initialize the debug bar
     *
     * @return string
     */
    protected function getJsInitializationCode()
    {
        $js = '';

        if (($this->initialization & self::INITIALIZE_CONSTRUCTOR) === self::INITIALIZE_CONSTRUCTOR) {
            $js .= sprintf("var %s = new %s();\n", $this->variableName, $this->javascriptClass);
        }

        if (($this->initialization & self::INITIALIZE_CONTROLS) === self::INITIALIZE_CONTROLS) {
            $js .= $this->getJsControlsDefinitionCode($this->variableName);
        }

        if ($this->ajaxHandlerClass) {
            $js .= sprintf("%s.ajaxHandler = new %s(%s);\n", $this->variableName, $this->ajaxHandlerClass, $this->variableName);
            if ($this->ajaxHandlerBindToXHR) {
                $js .= sprintf("%s.ajaxHandler.bindToXHR();\n", $this->variableName);
            } elseif ($this->ajaxHandlerBindToJquery) {
                $js .= sprintf("if (jQuery) %s.ajaxHandler.bindToJquery(jQuery);\n", $this->variableName);
            }
        }

        if ($this->openHandlerUrl !== null) {
            $js .= sprintf("%s.setOpenHandler(new %s(%s));\n", $this->variableName,
                $this->openHandlerClass,
                json_encode(array("url" => $this->openHandlerUrl)));
        }

        return $js;
    }

    /**
     * Returns the js code needed to initialized the controls and data mapping of the debug bar
     *
     * Controls can be defined by collectors themselves or using {@see addControl()}
     *
     * @param string $varname Debug bar's variable name
     * @return string
     */
    protected function getJsControlsDefinitionCode($varname)
    {
        $js = '';
        $dataMap = array();
        $excludedOptions = array('indicator', 'tab', 'map', 'default', 'widget', 'position');

        // finds controls provided by collectors
        $widgets = array();
        foreach ($this->debugBar->getCollectors() as $collector) {
            if (($collector instanceof Renderable) && !in_array($collector->getName(), $this->ignoredCollectors)) {
                if ($w = $collector->getWidgets()) {
                    $widgets = array_merge($widgets, $w);
                }
            }
        }
        $controls = array_merge($widgets, $this->controls);

        foreach (array_filter($controls) as $name => $options) {
            $opts = array_diff_key($options, array_flip($excludedOptions));

            if (isset($options['tab']) || isset($options['widget'])) {
                if (!isset($opts['title'])) {
                    $opts['title'] = ucfirst(str_replace('_', ' ', $name));
                }
                $js .= sprintf("%s.addTab(\"%s\", new %s({%s%s}));\n",
                    $varname,
                    $name,
                    isset($options['tab']) ? $options['tab'] : 'PhpDebugBar.DebugBar.Tab',
                    substr(json_encode($opts, JSON_FORCE_OBJECT), 1, -1),
                    isset($options['widget']) ? sprintf('%s"widget": new %s()', count($opts) ? ', ' : '', $options['widget']) : ''
                );
            } elseif (isset($options['indicator']) || isset($options['icon'])) {
                $js .= sprintf("%s.addIndicator(\"%s\", new %s(%s), \"%s\");\n",
                    $varname,
                    $name,
                    isset($options['indicator']) ? $options['indicator'] : 'PhpDebugBar.DebugBar.Indicator',
                    json_encode($opts, JSON_FORCE_OBJECT),
                    isset($options['position']) ? $options['position'] : 'right'
                );
            }

            if (isset($options['map']) && isset($options['default'])) {
                $dataMap[$name] = array($options['map'], $options['default']);
            }
        }

        // creates the data mapping object
        $mapJson = array();
        foreach ($dataMap as $name => $values) {
            $mapJson[] = sprintf('"%s": ["%s", %s]', $name, $values[0], $values[1]);
        }
        $js .= sprintf("%s.setDataMap({\n%s\n});\n", $varname, implode(",\n", $mapJson));

        // activate state restoration
        $js .= sprintf("%s.restoreState();\n", $varname);

        return $js;
    }

    /**
     * Returns the js code needed to add a dataset
     *
     * @param string $requestId
     * @param array $data
     * @return string
     */
    protected function getAddDatasetCode($requestId, $data, $suffix = null)
    {
        $js = sprintf("%s.addDataSet(%s, \"%s\"%s);\n",
            $this->variableName,
            json_encode($data),
            $requestId,
            $suffix ? ", " . json_encode($suffix) : ''
        );
        return $js;
    }
}
