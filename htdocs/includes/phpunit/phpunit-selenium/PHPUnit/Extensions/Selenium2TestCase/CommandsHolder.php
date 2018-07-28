<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2010-2013, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.2.4
 */

/**
 * Object representing elements, or everything that may have subcommands.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.4
 */
abstract class PHPUnit_Extensions_Selenium2TestCase_CommandsHolder
{
    /**
     * @var PHPUnit_Extensions_Selenium2TestCase_Driver
     */
    protected $driver;

    /**
     * @var string  the API URL for this element,
     */
    protected $url;

    /**
     * @var array   instances of
     *              PHPUnit_Extensions_Selenium2TestCase_ElementCommand
     */
    protected $commands;

    public function __construct($driver,
                                PHPUnit_Extensions_Selenium2TestCase_URL $url)
    {
        $this->driver = $driver;
        $this->url = $url;
        $this->commands = array();
        foreach ($this->initCommands() as $commandName => $handler) {
            if (is_string($handler)) {
                $this->commands[$commandName] = $this->factoryMethod($handler);
            } else if (is_callable($handler)) {
                $this->commands[$commandName] = $handler;
            } else {
                throw new InvalidArgumentException("Command $commandName is not configured correctly.");
            }
        }
    }

    /**
     * @return array    class names, or
     *                  callables of the form function($parameter, $commandUrl)
     */
    protected abstract function initCommands();

    public function __call($commandName, $arguments)
    {
        $jsonParameters = $this->extractJsonParameters($arguments);
        $response = $this->driver->execute($this->newCommand($commandName, $jsonParameters));
        return $response->getValue();
    }

    protected function postCommand($name, PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria)
    {
        $response = $this->driver->curl('POST',
                                        $this->url->addCommand($name),
                                        $criteria->getArrayCopy());
        return $response->getValue();
    }

    /**
     * @params string $commandClass     a class name, descending from
                                        PHPUnit_Extensions_Selenium2TestCase_Command
     * @return callable
     */
    private function factoryMethod($commandClass)
    {
        return function($jsonParameters, $url) use ($commandClass) {
            return new $commandClass($jsonParameters, $url);
        };
    }

    private function extractJsonParameters($arguments)
    {
        $this->checkArguments($arguments);

        if (count($arguments) == 0) {
            return NULL;
        }
        return $arguments[0];
    }

    private function checkArguments($arguments)
    {
        if (count($arguments) > 1) {
            throw new Exception('You cannot call a command with multiple method arguments.');
        }
    }

    /**
     * @param string $commandName  The called method name
     *                              defined as a key in initCommands()
     * @param array $jsonParameters
     * @return PHPUnit_Extensions_Selenium2TestCase_Command
     */
    protected function newCommand($commandName, $jsonParameters)
    {
        if (isset($this->commands[$commandName])) {
            $factoryMethod = $this->commands[$commandName];
            $url = $this->url->addCommand($commandName);
            $command = $factoryMethod($jsonParameters, $url);
            return $command;
        }
        throw new BadMethodCallException("The command '$commandName' is not existent or not supported yet.");
    }
}
