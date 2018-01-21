<?php
/**
 * Smarty Internal Plugin Template
 * This file contains the Smarty template engine
 *
 * @package    Smarty
 * @subpackage Template
 * @author     Uwe Tews
 */

/**
 * Main class with template data structures and methods
 *
 * @package    Smarty
 * @subpackage Template
 *
 * @property Smarty_Template_Compiled             $compiled
 * @property Smarty_Template_Cached               $cached
 * @property Smarty_Internal_TemplateCompilerBase $compiler
 *
 * The following methods will be dynamically loaded by the extension handler when they are called.
 * They are located in a corresponding Smarty_Internal_Method_xxxx class
 *
 * @method bool mustCompile()
 */
class Smarty_Internal_Template extends Smarty_Internal_TemplateBase
{
    /**
     * This object type (Smarty = 1, template = 2, data = 4)
     *
     * @var int
     */
    public $_objType = 2;

    /**
     * Global smarty instance
     *
     * @var Smarty
     */
    public $smarty = null;

    /**
     * Source instance
     *
     * @var Smarty_Template_Source|Smarty_Template_Config
     */
    public $source = null;

    /**
     * Inheritance runtime extension
     *
     * @var Smarty_Internal_Runtime_Inheritance
     */
    public $inheritance = null;

    /**
     * Template resource
     *
     * @var string
     */
    public $template_resource = null;

    /**
     * flag if compiled template is invalid and must be (re)compiled
     *
     * @var bool
     */
    public $mustCompile = null;

    /**
     * Template Id
     *
     * @var null|string
     */
    public $templateId = null;

    /**
     * Scope in which variables shall be assigned
     *
     * @var int
     */
    public $scope = 0;

    /**
     * Flag which is set while rending a cache file
     *
     * @var bool
     */
    public $isRenderingCache = false;

    /**
     * Callbacks called before rendering template
     *
     * @var callback[]
     */
    public $startRenderCallbacks = array();

    /**
     * Callbacks called after rendering template
     *
     * @var callback[]
     */
    public $endRenderCallbacks = array();

    /**
     * Create template data object
     * Some of the global Smarty settings copied to template scope
     * It load the required template resources and caching plugins
     *
     * @param string                                                  $template_resource template resource string
     * @param Smarty                                                  $smarty            Smarty instance
     * @param \Smarty_Internal_Template|\Smarty|\Smarty_Internal_Data $_parent           back pointer to parent object
     *                                                                                   with variables or null
     * @param mixed                                                   $_cache_id         cache   id or null
     * @param mixed                                                   $_compile_id       compile id or null
     * @param bool                                                    $_caching          use caching?
     * @param int                                                     $_cache_lifetime   cache life-time in seconds
     *
     * @throws \SmartyException
     */
    public function __construct($template_resource, Smarty $smarty, Smarty_Internal_Data $_parent = null,
                                $_cache_id = null, $_compile_id = null, $_caching = null, $_cache_lifetime = null)
    {
        $this->smarty = $smarty;
        // Smarty parameter
        $this->cache_id = $_cache_id === null ? $this->smarty->cache_id : $_cache_id;
        $this->compile_id = $_compile_id === null ? $this->smarty->compile_id : $_compile_id;
        $this->caching = $_caching === null ? $this->smarty->caching : $_caching;
        if ($this->caching === true) {
            $this->caching = Smarty::CACHING_LIFETIME_CURRENT;
        }
        $this->cache_lifetime = $_cache_lifetime === null ? $this->smarty->cache_lifetime : $_cache_lifetime;
        $this->parent = $_parent;
        // Template resource
        $this->template_resource = $template_resource;
        $this->source = Smarty_Template_Source::load($this);
        parent::__construct();
        if ($smarty->security_policy && method_exists($smarty->security_policy, 'registerCallBacks')) {
            $smarty->security_policy->registerCallBacks($this);
        }
    }

    /**
     * render template
     *
     * @param  bool      $no_output_filter if true do not run output filter
     * @param  null|bool $display          true: display, false: fetch null: sub-template
     *
     * @return string
     * @throws \SmartyException
     */
    public function render($no_output_filter = true, $display = null)
    {
        $parentIsTpl = isset($this->parent) && $this->parent->_objType == 2;
        if ($this->smarty->debugging) {
            if (!isset($this->smarty->_debug)) {
                $this->smarty->_debug = new Smarty_Internal_Debug();
            }
            $this->smarty->_debug->start_template($this, $display);
        }
        // checks if template exists
        if (!$this->source->exists) {
            throw new SmartyException("Unable to load template '{$this->source->type}:{$this->source->name}'" .
                                      ($parentIsTpl ? " in '{$this->parent->template_resource}'" : ''));
        }
        // disable caching for evaluated code
        if ($this->source->handler->recompiled) {
            $this->caching = false;
        }
        // read from cache or render
        $isCacheTpl =
            $this->caching == Smarty::CACHING_LIFETIME_CURRENT || $this->caching == Smarty::CACHING_LIFETIME_SAVED;
        if ($isCacheTpl) {
            if (!isset($this->cached) || $this->cached->cache_id !== $this->cache_id ||
                $this->cached->compile_id !== $this->compile_id
            ) {
                $this->loadCached(true);
            }
            $this->cached->render($this, $no_output_filter);
        } else {
            if (!isset($this->compiled) || $this->compiled->compile_id !== $this->compile_id) {
                $this->loadCompiled(true);
            }
            $this->compiled->render($this);
        }

        // display or fetch
        if ($display) {
            if ($this->caching && $this->smarty->cache_modified_check) {
                $this->smarty->ext->_cacheModify->cacheModifiedCheck($this->cached, $this,
                                                                     isset($content) ? $content : ob_get_clean());
            } else {
                if ((!$this->caching || $this->cached->has_nocache_code || $this->source->handler->recompiled) &&
                    !$no_output_filter && (isset($this->smarty->autoload_filters[ 'output' ]) ||
                                           isset($this->smarty->registered_filters[ 'output' ]))
                ) {
                    echo $this->smarty->ext->_filterHandler->runFilter('output', ob_get_clean(), $this);
                } else {
                    echo ob_get_clean();
                }
            }
            if ($this->smarty->debugging) {
                $this->smarty->_debug->end_template($this);
                // debug output
                $this->smarty->_debug->display_debug($this, true);
            }
            return '';
        } else {
            if ($this->smarty->debugging) {
                $this->smarty->_debug->end_template($this);
                if ($this->smarty->debugging === 2 && $display === false) {
                    $this->smarty->_debug->display_debug($this, true);
                }
            }
            if ($parentIsTpl) {
                foreach ($this->compiled->required_plugins as $code => $tmp1) {
                    foreach ($tmp1 as $name => $tmp) {
                        foreach ($tmp as $type => $data) {
                            $this->parent->compiled->required_plugins[ $code ][ $name ][ $type ] = $data;
                        }
                    }
                }
            }
            if (!$no_output_filter &&
                (!$this->caching || $this->cached->has_nocache_code || $this->source->handler->recompiled) &&
                (isset($this->smarty->autoload_filters[ 'output' ]) ||
                 isset($this->smarty->registered_filters[ 'output' ]))
            ) {
                return $this->smarty->ext->_filterHandler->runFilter('output', ob_get_clean(), $this);
            }
            // return cache content
            return null;
        }
    }

    /**
     * Runtime function to render sub-template
     *
     * @param string  $template       template name
     * @param mixed   $cache_id       cache id
     * @param mixed   $compile_id     compile id
     * @param integer $caching        cache mode
     * @param integer $cache_lifetime life time of cache data
     * @param array   $data           passed parameter template variables
     * @param int     $scope          scope in which {include} should execute
     * @param bool    $forceTplCache  cache template object
     * @param string  $uid            file dependency uid
     * @param string  $content_func   function name
     *
     */
    public function _subTemplateRender($template, $cache_id, $compile_id, $caching, $cache_lifetime, $data, $scope,
                                       $forceTplCache, $uid = null, $content_func = null)
    {
        $tpl = clone $this;
        $tpl->parent = $this;
        $smarty = &$this->smarty;
        $_templateId = $smarty->_getTemplateId($template, $cache_id, $compile_id, $caching, $tpl);
        // recursive call ?
        if (isset($tpl->templateId) ? $tpl->templateId : $tpl->_getTemplateId() != $_templateId) {
            // already in template cache?
            if (isset($smarty->_cache[ 'tplObjects' ][ $_templateId ])) {
                // copy data from cached object
                $cachedTpl = &$smarty->_cache[ 'tplObjects' ][ $_templateId ];
                $tpl->templateId = $cachedTpl->templateId;
                $tpl->template_resource = $cachedTpl->template_resource;
                $tpl->cache_id = $cachedTpl->cache_id;
                $tpl->compile_id = $cachedTpl->compile_id;
                $tpl->source = $cachedTpl->source;
                if (isset($cachedTpl->compiled)) {
                    $tpl->compiled = $cachedTpl->compiled;
                } else {
                    unset($tpl->compiled);
                }
                if ($caching != 9999 && isset($cachedTpl->cached)) {
                    $tpl->cached = $cachedTpl->cached;
                } else {
                    unset($tpl->cached);
                }
            } else {
                $tpl->templateId = $_templateId;
                $tpl->template_resource = $template;
                $tpl->cache_id = $cache_id;
                $tpl->compile_id = $compile_id;
                if (isset($uid)) {
                    // for inline templates we can get all resource information from file dependency
                    list($filepath, $timestamp, $type) = $tpl->compiled->file_dependency[ $uid ];
                    $tpl->source = new Smarty_Template_Source($smarty, $filepath, $type, $filepath);
                    $tpl->source->filepath = $filepath;
                    $tpl->source->timestamp = $timestamp;
                    $tpl->source->exists = true;
                    $tpl->source->uid = $uid;
                } else {
                    $tpl->source = Smarty_Template_Source::load($tpl);
                    unset($tpl->compiled);
                }
                if ($caching != 9999) {
                    unset($tpl->cached);
                }
            }
        } else {
            // on recursive calls force caching
            $forceTplCache = true;
        }
        $tpl->caching = $caching;
        $tpl->cache_lifetime = $cache_lifetime;
        // set template scope
        $tpl->scope = $scope;
        if (!isset($smarty->_cache[ 'tplObjects' ][ $tpl->templateId ]) && !$tpl->source->handler->recompiled) {
            // check if template object should be cached
            if ($forceTplCache || (isset($smarty->_cache[ 'subTplInfo' ][ $tpl->template_resource ]) &&
                                   $smarty->_cache[ 'subTplInfo' ][ $tpl->template_resource ] > 1) ||
                ($tpl->_isParentTemplate() && isset($smarty->_cache[ 'tplObjects' ][ $tpl->parent->templateId ]))
            ) {
                $smarty->_cache[ 'tplObjects' ][ $tpl->templateId ] = $tpl;
            }
        }

        if (!empty($data)) {
            // set up variable values
            foreach ($data as $_key => $_val) {
                $tpl->tpl_vars[ $_key ] = new Smarty_Variable($_val, $this->isRenderingCache);
            }
        }
        if ($tpl->caching == 9999) {
            if (!isset($tpl->compiled)) {
                $this->loadCompiled(true);
            }
            if ($tpl->compiled->has_nocache_code) {
                $this->cached->hashes[ $tpl->compiled->nocache_hash ] = true;
            }
        }
        $tpl->_cache = array();
        if (isset($uid)) {
            if ($smarty->debugging) {
                if (!isset($smarty->_debug)) {
                    $smarty->_debug = new Smarty_Internal_Debug();
                }
                $smarty->_debug->start_template($tpl);
                $smarty->_debug->start_render($tpl);
            }
            $tpl->compiled->getRenderedTemplateCode($tpl, $content_func);
            if ($smarty->debugging) {
                $smarty->_debug->end_template($tpl);
                $smarty->_debug->end_render($tpl);
            }
        } else {
            if (isset($tpl->compiled)) {
                $tpl->compiled->render($tpl);
            } else {
                $tpl->render();
            }
        }
    }

    /**
     * Get called sub-templates and save call count
     *
     */
    public function _subTemplateRegister()
    {
        foreach ($this->compiled->includes as $name => $count) {
            if (isset($this->smarty->_cache[ 'subTplInfo' ][ $name ])) {
                $this->smarty->_cache[ 'subTplInfo' ][ $name ] += $count;
            } else {
                $this->smarty->_cache[ 'subTplInfo' ][ $name ] = $count;
            }
        }
    }

    /**
     * Check if parent is template object
     *
     * @return bool true if parent is template
     */
    public function _isParentTemplate()
    {
        return isset($this->parent) && $this->parent->_objType == 2;
    }

    /**
     * Assign variable in scope
     *
     * @param string $varName variable name
     * @param mixed  $value   value
     * @param bool   $nocache nocache flag
     * @param int    $scope   scope into which variable shall be assigned
     *
     */
    public function _assignInScope($varName, $value, $nocache = false, $scope = 0)
    {
        if (isset($this->tpl_vars[ $varName ])) {
            $this->tpl_vars[ $varName ] = clone $this->tpl_vars[ $varName ];
            $this->tpl_vars[ $varName ]->value = $value;
            if ($nocache || $this->isRenderingCache) {
                $this->tpl_vars[ $varName ]->nocache = true;
            }
        } else {
            $this->tpl_vars[ $varName ] = new Smarty_Variable($value, $nocache || $this->isRenderingCache);
        }
        if ($scope >= 0) {
            if (isset($scope) || isset($this->scope)) {
                $this->smarty->ext->_updateScope->_updateScope($this, $varName, $scope);
            }
        }
    }

    /**
     * This function is executed automatically when a compiled or cached template file is included
     * - Decode saved properties from compiled template and cache files
     * - Check if compiled or cache file is valid
     *
     * @param \Smarty_Internal_Template $tpl
     * @param  array                    $properties special template properties
     * @param  bool                     $cache      flag if called from cache file
     *
     * @return bool flag if compiled or cache file is valid
     * @throws \SmartyException
     */
    public function _decodeProperties(Smarty_Internal_Template $tpl, $properties, $cache = false)
    {
        $is_valid = true;
        if (Smarty::SMARTY_VERSION != $properties[ 'version' ]) {
            // new version must rebuild
            $is_valid = false;
        } elseif ($is_valid && !empty($properties[ 'file_dependency' ]) &&
                  ((!$cache && $tpl->smarty->compile_check) || $tpl->smarty->compile_check == 1)
        ) {
            // check file dependencies at compiled code
            foreach ($properties[ 'file_dependency' ] as $_file_to_check) {
                if ($_file_to_check[ 2 ] == 'file' || $_file_to_check[ 2 ] == 'php') {
                    if ($tpl->source->filepath == $_file_to_check[ 0 ]) {
                        // do not recheck current template
                        continue;
                        //$mtime = $tpl->source->getTimeStamp();
                    } else {
                        // file and php types can be checked without loading the respective resource handlers
                        $mtime = is_file($_file_to_check[ 0 ]) ? filemtime($_file_to_check[ 0 ]) : false;
                    }
                } else {
                    $handler = Smarty_Resource::load($tpl->smarty, $_file_to_check[ 2 ]);
                    if ($handler->checkTimestamps()) {
                        $source = Smarty_Template_Source::load($tpl, $tpl->smarty, $_file_to_check[ 0 ]);
                        $mtime = $source->getTimeStamp();
                    } else {
                        continue;
                    }
                }
                if ($mtime === false || $mtime > $_file_to_check[ 1 ]) {
                    $is_valid = false;
                    break;
                }
            }
        }
        if ($cache) {
            // CACHING_LIFETIME_SAVED cache expiry has to be validated here since otherwise we'd define the unifunc
            if ($tpl->caching === Smarty::CACHING_LIFETIME_SAVED && $properties[ 'cache_lifetime' ] >= 0 &&
                (time() > ($tpl->cached->timestamp + $properties[ 'cache_lifetime' ]))
            ) {
                $is_valid = false;
            }
            $tpl->cached->cache_lifetime = $properties[ 'cache_lifetime' ];
            $tpl->cached->valid = $is_valid;
            $resource = $tpl->cached;
        } else {
            $tpl->mustCompile = !$is_valid;
            $resource = $tpl->compiled;
            $resource->includes = isset($properties[ 'includes' ]) ? $properties[ 'includes' ] : array();
        }
        if ($is_valid) {
            $resource->unifunc = $properties[ 'unifunc' ];
            $resource->has_nocache_code = $properties[ 'has_nocache_code' ];
            //            $tpl->compiled->nocache_hash = $properties['nocache_hash'];
            $resource->file_dependency = $properties[ 'file_dependency' ];
        }
        return $is_valid && !function_exists($properties[ 'unifunc' ]);
    }

    /**
     * Compiles the template
     * If the template is not evaluated the compiled template is saved on disk
     */
    public function compileTemplateSource()
    {
        return $this->compiled->compileTemplateSource($this);
    }

    /**
     * Writes the content to cache resource
     *
     * @param string $content
     *
     * @return bool
     */
    public function writeCachedContent($content)
    {
        return $this->smarty->ext->_updateCache->writeCachedContent($this->cached, $this, $content);
    }

    /**
     * Get unique template id
     *
     * @return string
     */
    public function _getTemplateId()
    {
        return isset($this->templateId) ? $this->templateId : $this->templateId =
            $this->smarty->_getTemplateId($this->template_resource, $this->cache_id, $this->compile_id);
    }

    /**
     * runtime error not matching capture tags
     */
    public function capture_error()
    {
        throw new SmartyException("Not matching {capture} open/close in \"{$this->template_resource}\"");
    }

    /**
     * Load compiled object
     *
     * @param bool $force force new compiled object
     */
    public function loadCompiled($force = false)
    {
        if ($force || !isset($this->compiled)) {
            $this->compiled = Smarty_Template_Compiled::load($this);
        }
    }

    /**
     * Load cached object
     *
     * @param bool $force force new cached object
     */
    public function loadCached($force = false)
    {
        if ($force || !isset($this->cached)) {
            $this->cached = Smarty_Template_Cached::load($this);
        }
    }

    /**
     * Load inheritance object
     *
     */
    public function _loadInheritance()
    {
        if (!isset($this->inheritance)) {
            $this->inheritance = new Smarty_Internal_Runtime_Inheritance();
        }
    }

    /**
     * Unload inheritance object
     *
     */
    public function _cleanUp()
    {
        $this->startRenderCallbacks = array();
        $this->endRenderCallbacks = array();
        $this->inheritance = null;
    }

    /**
     * Load compiler object
     *
     * @throws \SmartyException
     */
    public function loadCompiler()
    {
        if (!class_exists($this->source->compiler_class)) {
            $this->smarty->loadPlugin($this->source->compiler_class);
        }
        $this->compiler =
            new $this->source->compiler_class($this->source->template_lexer_class, $this->source->template_parser_class,
                                              $this->smarty);
    }

    /**
     * Handle unknown class methods
     *
     * @param string $name unknown method-name
     * @param array  $args argument array
     *
     * @return mixed
     * @throws SmartyException
     */
    public function __call($name, $args)
    {
        // method of Smarty object?
        if (method_exists($this->smarty, $name)) {
            return call_user_func_array(array($this->smarty, $name), $args);
        }
        // parent
        return parent::__call($name, $args);
    }

    /**
     * set Smarty property in template context
     *
     * @param string $property_name property name
     * @param mixed  $value         value
     *
     * @throws SmartyException
     */
    public function __set($property_name, $value)
    {
        switch ($property_name) {
            case 'compiled':
            case 'cached':
            case 'compiler':
                $this->$property_name = $value;
                return;
            default:
                // Smarty property ?
                if (property_exists($this->smarty, $property_name)) {
                    $this->smarty->$property_name = $value;
                    return;
                }
        }
        throw new SmartyException("invalid template property '$property_name'.");
    }

    /**
     * get Smarty property in template context
     *
     * @param string $property_name property name
     *
     * @return mixed|Smarty_Template_Cached
     * @throws SmartyException
     */
    public function __get($property_name)
    {
        switch ($property_name) {
            case 'compiled':
                $this->loadCompiled();
                return $this->compiled;

            case 'cached':
                $this->loadCached();
                return $this->cached;

            case 'compiler':
                $this->loadCompiler();
                return $this->compiler;
            default:
                // Smarty property ?
                if (property_exists($this->smarty, $property_name)) {
                    return $this->smarty->$property_name;
                }
        }
        throw new SmartyException("template property '$property_name' does not exist.");
    }

    /**
     * Template data object destructor
     */
    public function __destruct()
    {
        if ($this->smarty->cache_locking && isset($this->cached) && $this->cached->is_locked) {
            $this->cached->handler->releaseLock($this->smarty, $this->cached);
        }
    }
}
