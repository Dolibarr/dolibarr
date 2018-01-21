<?php

/**
 * Tplfunc Runtime Methods callTemplateFunction
 *
 * @package    Smarty
 * @subpackage PluginsInternal
 * @author     Uwe Tews
 *
 **/
class Smarty_Internal_Runtime_TplFunction
{
    /**
     * Array of source information for known template functions
     *
     * @var array
     */
    private $tplFunctions = array();

    /**
     * Call template function
     *
     * @param \Smarty_Internal_Template $tpl     template object
     * @param string                    $name    template function name
     * @param array                     $params  parameter array
     * @param bool                      $nocache true if called nocache
     *
     * @throws \SmartyException
     */
    public function callTemplateFunction(Smarty_Internal_Template $tpl, $name, $params, $nocache)
    {
        if (isset($this->tplFunctions[ $name ])) {
            if (!$tpl->caching || ($tpl->caching && $nocache)) {
                $function = $this->tplFunctions[ $name ][ 'call_name' ];
            } else {
                if (isset($this->tplFunctions[ $name ][ 'call_name_caching' ])) {
                    $function = $this->tplFunctions[ $name ][ 'call_name_caching' ];
                } else {
                    $function = $this->tplFunctions[ $name ][ 'call_name' ];
                }
            }
            if (function_exists($function)) {
                $this->saveTemplateVariables($tpl, $name);
                $function ($tpl, $params);
                $this->restoreTemplateVariables($tpl, $name);
                return;
            }
            // try to load template function dynamically
            if ($this->addTplFuncToCache($tpl, $name, $function)) {
                $this->saveTemplateVariables($tpl, $name);
                $function ($tpl, $params);
                $this->restoreTemplateVariables($tpl, $name);
                return;
            }
        }
        throw new SmartyException("Unable to find template function '{$name}'");
    }

    /**
     * Register template functions defined by template
     *
     * @param \Smarty_Internal_Template $tpl
     * @param  array                    $tplFunctions source information array of template functions defined in template
     */
    public function registerTplFunctions(Smarty_Internal_Template $tpl, $tplFunctions)
    {
        $this->tplFunctions = array_merge($this->tplFunctions, $tplFunctions);
        $ptr = $tpl;
        // make sure that the template functions are known in parent templates
        while (isset($ptr->parent) && $ptr->parent->_objType === 2 && !isset($ptr->ext->_tplFunction)) {
            $ptr->ext->_tplFunction = $this;
            $ptr = $ptr->parent;
        }
    }

    /**
     * Return source parameter array for single or all template functions
     *
     * @param null|string $name template function name
     *
     * @return array|bool|mixed
     */
    public function getTplFunction($name = null)
    {
        if (isset($name)) {
            return $this->tplFunctions[ $name ] ? $this->tplFunctions[ $name ] : false;
        } else {
            return $this->tplFunctions;
        }
    }

    /**
     *
     * Add template function to cache file for nocache calls
     *
     * @param Smarty_Internal_Template $tpl
     * @param string                   $_name     template function name
     * @param string                   $_function PHP function name
     *
     * @return bool
     */
    public function addTplFuncToCache(Smarty_Internal_Template $tpl, $_name, $_function)
    {
        $funcParam = $this->tplFunctions[ $_name ];
        if (is_file($funcParam[ 'compiled_filepath' ])) {
            // read compiled file
            $code = file_get_contents($funcParam[ 'compiled_filepath' ]);
            // grab template function
            if (preg_match("/\/\* {$_function} \*\/([\S\s]*?)\/\*\/ {$_function} \*\//", $code, $match)) {
                // grab source info from file dependency
                preg_match("/\s*'{$funcParam['uid']}'([\S\s]*?)\),/", $code, $match1);
                unset($code);
                // make PHP function known
                eval($match[ 0 ]);
                if (function_exists($_function)) {
                    // search cache file template
                    $tplPtr = $tpl;
                    while (!isset($tplPtr->cached) && isset($tplPtr->parent)) {
                        $tplPtr = $tplPtr->parent;
                    }
                    // add template function code to cache file
                    if (isset($tplPtr->cached)) {
                        /* @var Smarty_CacheResource $cache */
                        $cache = $tplPtr->cached;
                        $content = $cache->read($tplPtr);
                        if ($content) {
                            // check if we must update file dependency
                            if (!preg_match("/'{$funcParam['uid']}'(.*?)'nocache_hash'/", $content, $match2)) {
                                $content = preg_replace("/('file_dependency'(.*?)\()/", "\\1{$match1[0]}", $content);
                            }
                            $tplPtr->smarty->ext->_updateCache->write($cache, $tplPtr,
                                                                      preg_replace('/\s*\?>\s*$/', "\n", $content) .
                                                                      "\n" . preg_replace(array('/^\s*<\?php\s+/',
                                                                                                '/\s*\?>\s*$/',), "\n",
                                                                                          $match[ 0 ]));
                        }
                    }
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Save current template variables on stack
     *
     * @param \Smarty_Internal_Template $tpl
     * @param  string                   $name stack name
     */
    public function saveTemplateVariables(Smarty_Internal_Template $tpl, $name)
    {
        $tpl->_cache[ 'varStack' ][] =
            array('tpl' => $tpl->tpl_vars, 'config' => $tpl->config_vars, 'name' => "_tplFunction_{$name}");
    }

    /**
     * Restore saved variables into template objects
     *
     * @param \Smarty_Internal_Template $tpl
     * @param  string                   $name stack name
     */
    public function restoreTemplateVariables(Smarty_Internal_Template $tpl, $name)
    {
        if (isset($tpl->_cache[ 'varStack' ])) {
            $vars = array_pop($tpl->_cache[ 'varStack' ]);
            $tpl->tpl_vars = $vars[ 'tpl' ];
            $tpl->config_vars = $vars[ 'config' ];
        }
    }
}
