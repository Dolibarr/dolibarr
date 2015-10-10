<?php
// Authors:
// - cadorn, Christoph Dorn <christoph@christophdorn.com>, Copyright 2007, New BSD License
// - qbbr, Michael Day <manveru.alma@gmail.com>, Copyright 2008, New BSD License
// - cadorn, Christoph Dorn <christoph@christophdorn.com>, Copyright 2011, MIT License

/* ***** BEGIN LICENSE BLOCK *****
 *  
 * [MIT License](http://www.opensource.org/licenses/mit-license.php)
 * 
 * Copyright (c) 2007+ [Christoph Dorn](http://www.christophdorn.com/)
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * ***** END LICENSE BLOCK *****
 * 
 * @copyright   Copyright (C) 2007+ Christoph Dorn
 * @author      Christoph Dorn <christoph@christophdorn.com>
 * @author      Michael Day <manveru.alma@gmail.com>
 * @license     [MIT License](http://www.opensource.org/licenses/mit-license.php)
 * @package     FirePHPCore
 */

require_once dirname(__FILE__).'/FirePHP.class.php4';

/**
 * Sends the given data to the FirePHP Firefox Extension.
 * The data can be displayed in the Firebug Console or in the
 * "Server" request tab.
 * 
 * @see http://www.firephp.org/Wiki/Reference/Fb
 * @param mixed $Object
 * @return true
 * @throws Exception
 */
function fb()
{
  $instance =& FirePHP::getInstance(true);

  $args = func_get_args();
  return call_user_func_array(array(&$instance,'fb'),$args);
}


class FB
{
  /**
   * Enable and disable logging to Firebug
   * 
   * @see FirePHP->setEnabled()
   * @param boolean $Enabled TRUE to enable, FALSE to disable
   * @return void
   */
  function setEnabled($Enabled) {
    $instance =& FirePHP::getInstance(true);
    $instance->setEnabled($Enabled);
  }
  
  /**
   * Check if logging is enabled
   * 
   * @see FirePHP->getEnabled()
   * @return boolean TRUE if enabled
   */
  function getEnabled() {
    $instance =& FirePHP::getInstance(true);
    return $instance->getEnabled();
  }  
  
  /**
   * Specify a filter to be used when encoding an object
   * 
   * Filters are used to exclude object members.
   * 
   * @see FirePHP->setObjectFilter()
   * @param string $Class The class name of the object
   * @param array $Filter An array or members to exclude
   * @return void
   */
  function setObjectFilter($Class, $Filter) {
    $instance =& FirePHP::getInstance(true);
    $instance->setObjectFilter($Class, $Filter);
  }
  
  /**
   * Set some options for the library
   * 
   * @see FirePHP->setOptions()
   * @param array $Options The options to be set
   * @return void
   */
  function setOptions($Options) {
    $instance =& FirePHP::getInstance(true);
    $instance->setOptions($Options);
  }

  /**
   * Get options for the library
   * 
   * @see FirePHP->getOptions()
   * @return array The options
   */
  function getOptions() {
    $instance =& FirePHP::getInstance(true);
    return $instance->getOptions();
  }

  /**
   * Log object to firebug
   * 
   * @see http://www.firephp.org/Wiki/Reference/Fb
   * @param mixed $Object
   * @return true
   */
  function send()
  {
    $instance =& FirePHP::getInstance(true);
    $args = func_get_args();
    return call_user_func_array(array(&$instance,'fb'),$args);
  }

  /**
   * Start a group for following messages
   * 
   * Options:
   *   Collapsed: [true|false]
   *   Color:     [#RRGGBB|ColorName]
   *
   * @param string $Name
   * @param array $Options OPTIONAL Instructions on how to log the group
   * @return true
   */
  function group($Name, $Options=null) {
    $instance =& FirePHP::getInstance(true);
    return $instance->group($Name, $Options);
  }

  /**
   * Ends a group you have started before
   *
   * @return true
   */
  function groupEnd() {
    return FB::send(null, null, FirePHP_GROUP_END);
  }

  /**
   * Log object with label to firebug console
   *
   * @see FirePHP::LOG
   * @param mixes $Object
   * @param string $Label
   * @return true
   */
  function log($Object, $Label=null) {
    return FB::send($Object, $Label, FirePHP_LOG);
  } 

  /**
   * Log object with label to firebug console
   *
   * @see FirePHP::INFO
   * @param mixes $Object
   * @param string $Label
   * @return true
   */
  function info($Object, $Label=null) {
    return FB::send($Object, $Label, FirePHP_INFO);
  } 

  /**
   * Log object with label to firebug console
   *
   * @see FirePHP::WARN
   * @param mixes $Object
   * @param string $Label
   * @return true
   */
  function warn($Object, $Label=null) {
    return FB::send($Object, $Label, FirePHP_WARN);
  } 

  /**
   * Log object with label to firebug console
   *
   * @see FirePHP::ERROR
   * @param mixes $Object
   * @param string $Label
   * @return true
   */
  function error($Object, $Label=null) {
    return FB::send($Object, $Label, FirePHP_ERROR);
  } 

  /**
   * Dumps key and variable to firebug server panel
   *
   * @see FirePHP::DUMP
   * @param string $Key
   * @param mixed $Variable
   * @return true
   */
  function dump($Key, $Variable) {
    return FB::send($Variable, $Key, FirePHP_DUMP);
  } 

  /**
   * Log a trace in the firebug console
   *
   * @see FirePHP::TRACE
   * @param string $Label
   * @return true
   */
  function trace($Label) {
    return FB::send($Label, FirePHP_TRACE);
  } 

  /**
   * Log a table in the firebug console
   *
   * @see FirePHP::TABLE
   * @param string $Label
   * @param string $Table
   * @return true
   */
  function table($Label, $Table) {
    return FB::send($Table, $Label, FirePHP_TABLE);
  } 
}
