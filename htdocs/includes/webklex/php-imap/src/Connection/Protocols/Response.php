<?php
/*
* File: Response.php
* Category: -
* Author: M.Goldenbaum
* Created: 30.12.22 19:46
* Updated: -
*
* Description:
*  -
*/


namespace Webklex\PHPIMAP\Connection\Protocols;

use Webklex\PHPIMAP\Exceptions\ResponseException;

/**
 * Class Response
 *
 * @package Webklex\PHPIMAP\Connection\Protocols
 */
class Response {

    /**
     * The commands used to fetch or manipulate data
     * @var array $command
     */
    protected array $commands = [];

    /**
     * The original response received
     * @var array $response
     */
    protected array $response = [];

    /**
     * Errors that have occurred while fetching or parsing the response
     * @var array $errors
     */
    protected array $errors = [];

    /**
     * Result to be returned
     * @var mixed|null $result
     */
    protected mixed $result = null;

    /**
     * Noun to identify the request / response
     * @var int $noun
     */
    protected int $noun = 0;

    /**
     * Other related responses
     * @var array $response_stack
     */
    protected array $response_stack = [];

    /**
     * Debug flag
     * @var bool $debug
     */
    protected bool $debug = false;

    /**
     * Can the response be empty?
     * @var bool $can_be_empty
     */
    protected bool $can_be_empty = false;

    /**
     * Create a new Response instance
     */
    public function __construct(int $noun, bool $debug = false) {
        $this->debug = $debug;
        $this->noun = $noun > 0 ? $noun : (int)str_replace(".", "", (string)microtime(true));
    }

    /**
     * Make a new response instance
     * @param int $noun
     * @param array $commands
     * @param array $responses
     * @param bool $debug
     *
     * @return Response
     */
    public static function make(int $noun, array $commands = [], array $responses = [], bool $debug = false): Response {
        return (new self($noun, $debug))->setCommands($commands)->setResponse($responses);
    }

    /**
     * Create a new empty response
     * @param bool $debug
     *
     * @return Response
     */
    public static function empty(bool $debug = false): Response {
        return (new self(0, $debug));
    }

    /**
     * Stack another response
     * @param Response $response
     *
     * @return void
     */
    public function stack(Response $response): void {
        $this->response_stack[] = $response;
    }

    /**
     * Get the associated response stack
     *
     * @return array
     */
    public function getStack(): array {
        return $this->response_stack;
    }

    /**
     * Get all assigned commands
     *
     * @return array
     */
    public function getCommands(): array {
        return $this->commands;
    }

    /**
     * Add a new command
     * @param string $command
     *
     * @return Response
     */
    public function addCommand(string $command): Response {
        $this->commands[] = $command;
        return $this;
    }

    /**
     * Set and overwrite all commands
     * @param array $commands
     *
     * @return Response
     */
    public function setCommands(array $commands): Response {
        $this->commands = $commands;
        return $this;
    }

    /**
     * Get all set errors
     *
     * @return array
     */
    public function getErrors(): array {
        $errors = $this->errors;
        foreach($this->getStack() as $response) {
            $errors = array_merge($errors, $response->getErrors());
        }
        return $errors;
    }

    /**
     * Set and overwrite all existing errors
     * @param array $errors
     *
     * @return Response
     */
    public function setErrors(array $errors): Response {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Set the response
     * @param string $error
     *
     * @return Response
     */
    public function addError(string $error): Response {
        $this->errors[] = $error;
        return $this;
    }

    /**
     * Set the response
     * @param array $response
     *
     * @return Response
     */
    public function addResponse(mixed $response): Response {
        $this->response[] = $response;
        return $this;
    }

    /**
     * Set the response
     * @param array $response
     *
     * @return Response
     */
    public function setResponse(array $response): Response {
        $this->response = $response;
        return $this;
    }

    /**
     * Get the assigned response
     *
     * @return array
     */
    public function getResponse(): array {
        return $this->response;
    }

    /**
     * Set the result data
     * @param mixed $result
     *
     * @return Response
     */
    public function setResult(mixed $result): Response  {
        $this->result = $result;
        return $this;
    }

    /**
     * Wrap a result bearing action
     * @param callable $callback
     *
     * @return Response
     */
    public function wrap(callable $callback): Response  {
        $this->result = call_user_func($callback, $this);
        return $this;
    }

    /**
     * Get the response data
     *
     * @return mixed
     */
    public function data(): mixed {
        if ($this->result !== null) {
            return $this->result;
        }
        return $this->getResponse();
    }

    /**
     * Get the response data as array
     *
     * @return array
     */
    public function array(): array {
        $data = $this->data();
        if(is_array($data)){
            return $data;
        }
        return [$data];
    }

    /**
     * Get the response data as string
     *
     * @return string
     */
    public function string(): string {
        $data = $this->data();
        if(is_array($data)){
            return implode(" ", $data);
        }
        return (string)$data;
    }

    /**
     * Get the response data as integer
     *
     * @return int
     */
    public function integer(): int {
        $data = $this->data();
        if(is_array($data) && isset($data[0])){
            return (int)$data[0];
        }
        return (int)$data;
    }

    /**
     * Get the response data as boolean
     *
     * @return bool
     */
    public function boolean(): bool {
        return (bool)$this->data();
    }

    /**
     * Validate and retrieve the response data
     *
     * @throws ResponseException
     */
    public function validatedData(): mixed {
        return $this->validate()->data();
    }

    /**
     * Validate the response date
     *
     * @throws ResponseException
     */
    public function validate(): Response {
        if ($this->failed()) {
            throw ResponseException::make($this, $this->debug);
        }
        return $this;
    }

    /**
     * Check if the Response can be considered successful
     *
     * @return bool
     */
    public function successful(): bool {
        foreach(array_merge($this->getResponse(), $this->array()) as $data) {
            if (!$this->verify_data($data)) {
                return false;
            }
        }
        foreach($this->getStack() as $response) {
            if (!$response->successful()) {
                return false;
            }
        }
        return ($this->boolean() || $this->canBeEmpty()) && !$this->getErrors();
    }


    /**
     * Check if the Response can be considered failed
     * @param mixed $data
     *
     * @return bool
     */
    public function verify_data(mixed $data): bool {
        if (is_array($data)) {
            foreach ($data as $line) {
                if (is_array($line)) {
                    if(!$this->verify_data($line)){
                        return false;
                    }
                }else{
                    if (!$this->verify_line((string)$line)) {
                        return false;
                    }
                }
            }
        }else{
            if (!$this->verify_line((string)$data)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verify a single line
     * @param string $line
     *
     * @return bool
     */
    public function verify_line(string $line): bool {
        return !str_starts_with($line, "TAG".$this->noun." BAD ") && !str_starts_with($line, "TAG".$this->noun." NO ");
    }

    /**
     * Check if the Response can be considered failed
     *
     * @return bool
     */
    public function failed(): bool {
        return !$this->successful();
    }

    /**
     * Get the Response noun
     *
     * @return int
     */
    public function Noun(): int {
        return $this->noun;
    }

    /**
     * Set the Response to be allowed to be empty
     * @param bool $can_be_empty
     *
     * @return $this
     */
    public function setCanBeEmpty(bool $can_be_empty): Response {
        $this->can_be_empty = $can_be_empty;
        return $this;
    }

    /**
     * Check if the Response can be empty
     *
     * @return bool
     */
    public function canBeEmpty(): bool {
        return $this->can_be_empty;
    }
}