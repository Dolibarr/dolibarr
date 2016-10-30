<?php
/*
 * This file is part of Raven.
 *
 * (c) Sentry Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Raven PHP Client
 *
 * @package raven
 */

class Raven_Client
{
    const VERSION = '0.12.1';
    const PROTOCOL = '6';

    const DEBUG = 'debug';
    const INFO = 'info';
    const WARN = 'warning';
    const WARNING = 'warning';
    const ERROR = 'error';
    const FATAL = 'fatal';

    const MESSAGE_LIMIT = 1024;

    public $severity_map;
    public $extra_data;

    public $store_errors_for_bulk_send = false;

    public function __construct($options_or_dsn=null, $options=array())
    {
        if (is_null($options_or_dsn) && !empty($_SERVER['SENTRY_DSN'])) {
            // Read from environment
            $options_or_dsn = $_SERVER['SENTRY_DSN'];
        }
        if (!is_array($options_or_dsn)) {
            if (!empty($options_or_dsn)) {
                // Must be a valid DSN
                $options_or_dsn = self::parseDSN($options_or_dsn);
            } else {
                $options_or_dsn = array();
            }
        }
        $options = array_merge($options_or_dsn, $options);

        $this->logger = Raven_Util::get($options, 'logger', 'php');
        $this->servers = Raven_Util::get($options, 'servers');
        $this->secret_key = Raven_Util::get($options, 'secret_key');
        $this->public_key = Raven_Util::get($options, 'public_key');
        $this->project = Raven_Util::get($options, 'project', 1);
        $this->auto_log_stacks = (bool) Raven_Util::get($options, 'auto_log_stacks', false);
        $this->name = Raven_Util::get($options, 'name', Raven_Compat::gethostname());
        $this->site = Raven_Util::get($options, 'site', $this->_server_variable('SERVER_NAME'));
        $this->tags = Raven_Util::get($options, 'tags', array());
        $this->release = Raven_util::get($options, 'release', null);
        $this->trace = (bool) Raven_Util::get($options, 'trace', true);
        $this->timeout = Raven_Util::get($options, 'timeout', 2);
        $this->message_limit = Raven_Util::get($options, 'message_limit', self::MESSAGE_LIMIT);
        $this->exclude = Raven_Util::get($options, 'exclude', array());
        $this->severity_map = null;
        $this->shift_vars = (bool) Raven_Util::get($options, 'shift_vars', true);
        $this->http_proxy = Raven_Util::get($options, 'http_proxy');
        $this->extra_data = Raven_Util::get($options, 'extra', array());
        $this->send_callback = Raven_Util::get($options, 'send_callback', null);
        $this->curl_method = Raven_Util::get($options, 'curl_method', 'sync');
        $this->curl_path = Raven_Util::get($options, 'curl_path', 'curl');
        $this->curl_ipv4 = Raven_util::get($options, 'curl_ipv4', true);
        $this->ca_cert = Raven_util::get($options, 'ca_cert', $this->get_default_ca_cert());
        $this->verify_ssl = Raven_util::get($options, 'verify_ssl', true);
        $this->curl_ssl_version = Raven_Util::get($options, 'curl_ssl_version');

        $this->processors = $this->setProcessorsFromOptions($options);

        $this->_lasterror = null;
        $this->_user = null;
        $this->context = new Raven_Context();

        if ($this->curl_method == 'async') {
            $this->_curl_handler = new Raven_CurlHandler($this->get_curl_options());
        }
    }

    public static function getDefaultProcessors()
    {
        return array(
            'Raven_SanitizeDataProcessor',
        );
    }

    /**
     * Sets the Raven_Processor sub-classes to be used when data is processed before being
     * sent to Sentry.
     *
     * @param $options
     * @return array
     */
    public function setProcessorsFromOptions($options)
    {
        $processors = array();
        foreach (Raven_util::get($options, 'processors', self::getDefaultProcessors()) as $processor) {
            $new_processor = new $processor($this);

            if (isset($options['processorOptions']) && is_array($options['processorOptions'])) {
                if (isset($options['processorOptions'][$processor]) && method_exists($processor, 'setProcessorOptions')) {
                    $new_processor->setProcessorOptions($options['processorOptions'][$processor]);
                }
            }
            $processors[] = $new_processor;
        }
        return $processors;
    }

    /**
     * Parses a Raven-compatible DSN and returns an array of its values.
     *
     * @param string    $dsn    Raven compatible DSN: http://raven.readthedocs.org/en/latest/config/#the-sentry-dsn
     * @return array            parsed DSN
     */
    public static function parseDSN($dsn)
    {
        $url = parse_url($dsn);
        $scheme = (isset($url['scheme']) ? $url['scheme'] : '');
        if (!in_array($scheme, array('http', 'https'))) {
            throw new InvalidArgumentException('Unsupported Sentry DSN scheme: ' . (!empty($scheme) ? $scheme : '<not set>'));
        }
        $netloc = (isset($url['host']) ? $url['host'] : null);
        $netloc.= (isset($url['port']) ? ':'.$url['port'] : null);
        $rawpath = (isset($url['path']) ? $url['path'] : null);
        if ($rawpath) {
            $pos = strrpos($rawpath, '/', 1);
            if ($pos !== false) {
                $path = substr($rawpath, 0, $pos);
                $project = substr($rawpath, $pos + 1);
            } else {
                $path = '';
                $project = substr($rawpath, 1);
            }
        } else {
            $project = null;
            $path = '';
        }
        $username = (isset($url['user']) ? $url['user'] : null);
        $password = (isset($url['pass']) ? $url['pass'] : null);
        if (empty($netloc) || empty($project) || empty($username) || empty($password)) {
            throw new InvalidArgumentException('Invalid Sentry DSN: ' . $dsn);
        }

        return array(
            'servers'    => array(sprintf('%s://%s%s/api/%s/store/', $scheme, $netloc, $path, $project)),
            'project'    => $project,
            'public_key' => $username,
            'secret_key' => $password,
        );
    }

    public function getLastError()
    {
        return $this->_lasterror;
    }

    /**
     * Given an identifier, returns a Sentry searchable string.
     */
    public function getIdent($ident)
    {
        // XXX: We don't calculate checksums yet, so we only have the ident.
        return $ident;
    }

    /**
     * Deprecated
     */
    public function message($message, $params=array(), $level=self::INFO,
                            $stack=false, $vars = null)
    {
        return $this->captureMessage($message, $params, $level, $stack, $vars);
    }

    /**
     * Deprecated
     */
    public function exception($exception)
    {
        return $this->captureException($exception);
    }

    /**
     * Log a message to sentry
     */
    public function captureMessage($message, $params=array(), $level_or_options=array(),
                            $stack=false, $vars = null)
    {
        // Gracefully handle messages which contain formatting characters, but were not
        // intended to be used with formatting.
        if (!empty($params)) {
            $formatted_message = vsprintf($message, $params);
        } else {
            $formatted_message = $message;
        }

        if ($level_or_options === null) {
            $data = array();
        } elseif (!is_array($level_or_options)) {
            $data = array(
                'level' => $level_or_options,
            );
        } else {
            $data = $level_or_options;
        }

        $data['message'] = $formatted_message;
        $data['sentry.interfaces.Message'] = array(
            'message' => $message,
            'params' => $params,
        );

        return $this->capture($data, $stack, $vars);
    }

    /**
     * Log an exception to sentry
     */
    public function captureException($exception, $culprit_or_options=null, $logger=null, $vars=null)
    {
        $has_chained_exceptions = version_compare(PHP_VERSION, '5.3.0', '>=');

        if (in_array(get_class($exception), $this->exclude)) {
            return null;
        }

        if (!is_array($culprit_or_options)) {
            $data = array();
            if ($culprit_or_options !== null) {
                $data['culprit'] = $culprit_or_options;
            }
        } else {
            $data = $culprit_or_options;
        }

        // TODO(dcramer): DRY this up
        $message = $exception->getMessage();
        if (empty($message)) {
            $message = get_class($exception);
        }

        $exc = $exception;
        do {
            $exc_data = array(
                'value' => $exc->getMessage(),
                'type' => get_class($exc),
                'module' => $exc->getFile() .':'. $exc->getLine(),
            );

            /**'sentry.interfaces.Exception'
             * Exception::getTrace doesn't store the point at where the exception
             * was thrown, so we have to stuff it in ourselves. Ugh.
             */
            $trace = $exc->getTrace();
            $frame_where_exception_thrown = array(
                'file' => $exc->getFile(),
                'line' => $exc->getLine(),
            );

            array_unshift($trace, $frame_where_exception_thrown);

            // manually trigger autoloading, as it's not done in some edge cases due to PHP bugs (see #60149)
            if (!class_exists('Raven_Stacktrace')) {
                spl_autoload_call('Raven_Stacktrace');
            }

            $exc_data['stacktrace'] = array(
                'frames' => Raven_Stacktrace::get_stack_info(
                    $trace, $this->trace, $this->shift_vars, $vars, $this->message_limit
                ),
            );

            $exceptions[] = $exc_data;
        } while ($has_chained_exceptions && $exc = $exc->getPrevious());

        $data['message'] = $message;
        $data['sentry.interfaces.Exception'] = array(
            'values' => array_reverse($exceptions),
        );
        if ($logger !== null) {
            $data['logger'] = $logger;
        }

        if (empty($data['level'])) {
            if (method_exists($exception, 'getSeverity')) {
                $data['level'] = $this->translateSeverity($exception->getSeverity());
            } else {
                $data['level'] = self::ERROR;
            }
        }

        return $this->capture($data, $trace, $vars);
    }

    /**
     * Log an query to sentry
     */
    public function captureQuery($query, $level=self::INFO, $engine = '')
    {
        $data = array(
            'message' => $query,
            'level' => $level,
            'sentry.interfaces.Query' => array(
                'query' => $query
            )
        );

        if ($engine !== '') {
            $data['sentry.interfaces.Query']['engine'] = $engine;
        }
        return $this->capture($data, false);
    }

    protected function is_http_request()
    {
        return isset($_SERVER['REQUEST_METHOD']) && PHP_SAPI !== 'cli';
    }

    protected function get_http_data()
    {
        $env = $headers = array();

        foreach ($_SERVER as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                if (in_array($key, array('HTTP_CONTENT_TYPE', 'HTTP_CONTENT_LENGTH'))) {
                    continue;
                }
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
            } elseif (in_array($key, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))))] = $value;
            } else {
                $env[$key] = $value;
            }
        }

        $result = array(
            'method' => $this->_server_variable('REQUEST_METHOD'),
            'url' => $this->get_current_url(),
            'query_string' => $this->_server_variable('QUERY_STRING'),
        );

        // dont set this as an empty array as PHP will treat it as a numeric array
        // instead of a mapping which goes against the defined Sentry spec
        if (!empty($_POST)) {
            $result['data'] = $_POST;
        }
        if (!empty($_COOKIE)) {
            $result['cookies'] = $_COOKIE;
        }
        if (!empty($headers)) {
            $result['headers'] = $headers;
        }
        if (!empty($env)) {
            $result['env'] = $env;
        }

        return array(
            'sentry.interfaces.Http' => $result,
        );
    }

    protected function get_user_data()
    {
        $user = $this->context->user;
        if ($user === null) {
            if (!session_id()) {
                return array();
            }
            $user = array(
                'id' => session_id(),
            );
            if (!empty($_SESSION)) {
                $user['data'] = $_SESSION;
            }
        }
        return array(
            'sentry.interfaces.User' => $user,
        );
    }

    protected function get_extra_data()
    {
        return $this->extra_data;
    }

    public function get_default_data()
    {
        return array(
            'server_name' => $this->name,
            'project' => $this->project,
            'site' => $this->site,
            'logger' => $this->logger,
            'tags' => $this->tags,
            'platform' => 'php',
        );
    }

    public function capture($data, $stack, $vars = null)
    {
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
        }
        if (!isset($data['level'])) {
            $data['level'] = self::ERROR;
        }
        if (!isset($data['tags'])) {
            $data['tags'] = array();
        }
        if (!isset($data['extra'])) {
            $data['extra'] = array();
        }
        if (!isset($data['event_id'])) {
            $data['event_id'] = $this->uuid4();
        }

        if (isset($data['message'])) {
            $data['message'] = substr($data['message'], 0, $this->message_limit);
        }

        $data = array_merge($this->get_default_data(), $data);

        if ($this->is_http_request()) {
            $data = array_merge($this->get_http_data(), $data);
        }

        $data = array_merge($this->get_user_data(), $data);

        if ($this->release) {
            $data['release'] = $this->release;
        }

        $data['tags'] = array_merge(
            $this->tags,
            $this->context->tags,
            $data['tags']);

        $data['extra'] = array_merge(
            $this->get_extra_data(),
            $this->context->extra,
            $data['extra']);

        // avoid empty arrays (which dont convert to dicts)
        if (empty($data['extra']))
            unset($data['extra']);
        if (empty($data['tags']))
            unset($data['tags']);

        if ((!$stack && $this->auto_log_stacks) || $stack === true) {
            $stack = debug_backtrace();

            // Drop last stack
            array_shift($stack);
        }

        if (!empty($stack)) {
            // manually trigger autoloading, as it's not done in some edge cases due to PHP bugs (see #60149)
            if (!class_exists('Raven_Stacktrace')) {
                spl_autoload_call('Raven_Stacktrace');
            }

            if (!isset($data['sentry.interfaces.Stacktrace'])) {
                $data['sentry.interfaces.Stacktrace'] = array(
                    'frames' => Raven_Stacktrace::get_stack_info(
                        $stack, $this->trace, $this->shift_vars, $vars, $this->message_limit
                    ),
                );
            }
        }

        $this->sanitize($data);
        $this->process($data);

        if (!$this->store_errors_for_bulk_send) {
            $this->send($data);
        } else {
            if (empty($this->error_data)) {
                $this->error_data = array();
            }
            $this->error_data[] = $data;
        }

        return $data['event_id'];
    }

    public function sanitize(&$data)
    {
        // manually trigger autoloading, as it's not done in some edge cases due to PHP bugs (see #60149)
        if (!class_exists('Raven_Serializer')) {
            spl_autoload_call('Raven_Serializer');
        }

        $data = Raven_Serializer::serialize($data);
    }

    /**
     * Process data through all defined Raven_Processor sub-classes
     *
     * @param array     $data       Associative array of data to log
     */
    public function process(&$data)
    {
        foreach ($this->processors as $processor) {
            $processor->process($data);
        }
    }

    public function sendUnsentErrors()
    {
        if (!empty($this->error_data)) {
            foreach ($this->error_data as $data) {
                $this->send($data);
            }
            unset($this->error_data);
        }
        if ($this->store_errors_for_bulk_send) {
            //in case an error occurs after this is called, on shutdown, send any new errors.
            $this->store_errors_for_bulk_send = !defined('RAVEN_CLIENT_END_REACHED');
        }
    }

    /**
     * Wrapper to handle encoding and sending data to all defined Sentry servers
     *
     * @param array     $data       Associative array of data to log
     */
    public function send($data)
    {
        if (is_callable($this->send_callback) && !call_user_func($this->send_callback, $data)) {
            // if send_callback returns falsely, end native send
            return;
        }

        if (!$this->servers) {
            return;
        }

        $message = Raven_Compat::json_encode($data);

        if (function_exists("gzcompress")) {
            $message = gzcompress($message);
        }
        $message = base64_encode($message); // PHP's builtin curl_* function are happy without this, but the exec method requires it

        foreach ($this->servers as $url) {
            $client_string = 'raven-php/' . self::VERSION;
            $timestamp = microtime(true);
            $headers = array(
                'User-Agent' => $client_string,
                'X-Sentry-Auth' => $this->get_auth_header(
                    $timestamp, $client_string, $this->public_key,
                    $this->secret_key),
                'Content-Type' => 'application/octet-stream'
            );

            $this->send_remote($url, $message, $headers);
        }
    }

    /**
     * Send data to Sentry
     *
     * @param string    $url        Full URL to Sentry
     * @param array     $data       Associative array of data to log
     * @param array     $headers    Associative array of headers
     */
    private function send_remote($url, $data, $headers=array())
    {
        $parts = parse_url($url);
        $parts['netloc'] = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : null);
        $this->send_http($url, $data, $headers);
    }

    protected function get_default_ca_cert()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cacert.pem';
    }

    protected function get_curl_options()
    {
        $options = array(
            CURLOPT_VERBOSE => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => $this->verify_ssl,
            CURLOPT_CAINFO => $this->ca_cert,
            CURLOPT_USERAGENT => 'raven-php/' . self::VERSION,
        );
        if ($this->http_proxy) {
            $options[CURLOPT_PROXY] = $this->http_proxy;
        }
        if ($this->curl_ssl_version) {
            $options[CURLOPT_SSLVERSION] = $this->curl_ssl_version;
        }
        if ($this->curl_ipv4) {
            $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        if (defined('CURLOPT_TIMEOUT_MS')) {
            // MS is available in curl >= 7.16.2
            $timeout = max(1, ceil(1000 * $this->timeout));

            // some versions of PHP 5.3 don't have this defined correctly
            if (!defined('CURLOPT_CONNECTTIMEOUT_MS')) {
                //see http://stackoverflow.com/questions/9062798/php-curl-timeout-is-not-working/9063006#9063006
                define('CURLOPT_CONNECTTIMEOUT_MS', 156);
            }

            $options[CURLOPT_CONNECTTIMEOUT_MS] = $timeout;
            $options[CURLOPT_TIMEOUT_MS] = $timeout;
        } else {
            // fall back to the lower-precision timeout.
            $timeout = max(1, ceil($this->timeout));
            $options[CURLOPT_CONNECTTIMEOUT] = $timeout;
            $options[CURLOPT_TIMEOUT] = $timeout;
        }
        return $options;
    }

    /**
     * Send the message over http to the sentry url given
     *
     * @param string $url       URL of the Sentry instance to log to
     * @param array $data       Associative array of data to log
     * @param array $headers    Associative array of headers
     */
    private function send_http($url, $data, $headers=array())
    {
        if ($this->curl_method == 'async') {
            $this->_curl_handler->enqueue($url, $data, $headers);
        } elseif ($this->curl_method == 'exec') {
            $this->send_http_asynchronous_curl_exec($url, $data, $headers);
        } else {
            $this->send_http_synchronous($url, $data, $headers);
        }
    }

    /**
     * Send the cURL to Sentry asynchronously. No errors will be returned from cURL
     *
     * @param string    $url        URL of the Sentry instance to log to
     * @param array     $data       Associative array of data to log
     * @param array     $headers    Associative array of headers
     * @return bool
     */
    private function send_http_asynchronous_curl_exec($url, $data, $headers)
    {
        // TODO(dcramer): support ca_cert
        $cmd = $this->curl_path.' -X POST ';
        foreach ($headers as $key => $value) {
            $cmd .= '-H \''. $key. ': '. $value. '\' ';
        }
        $cmd .= '-d \''. $data .'\' ';
        $cmd .= '\''. $url .'\' ';
        $cmd .= '-m 5 ';  // 5 second timeout for the whole process (connect + send)
        $cmd .= '> /dev/null 2>&1 &'; // ensure exec returns immediately while curl runs in the background

        exec($cmd);

        return true; // The exec method is just fire and forget, so just assume it always works
    }

    /**
     * Send a blocking cURL to Sentry and check for errors from cURL
     *
     * @param string    $url        URL of the Sentry instance to log to
     * @param array     $data       Associative array of data to log
     * @param array     $headers    Associative array of headers
     * @return bool
     */
    private function send_http_synchronous($url, $data, $headers)
    {
        $new_headers = array();
        foreach ($headers as $key => $value) {
            array_push($new_headers, $key .': '. $value);
        }
        // XXX(dcramer): Prevent 100-continue response form server (Fixes GH-216)
        $new_headers[] = 'Expect:';

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $new_headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $options = $this->get_curl_options();
        $ca_cert = $options[CURLOPT_CAINFO];
        unset($options[CURLOPT_CAINFO]);
        curl_setopt_array($curl, $options);

        curl_exec($curl);

        $errno = curl_errno($curl);
        // CURLE_SSL_CACERT || CURLE_SSL_CACERT_BADFILE
        if ($errno == 60 || $errno == 77) {
            curl_setopt($curl, CURLOPT_CAINFO, $ca_cert);
            curl_exec($curl);
        }

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $success = ($code == 200);
        if (!$success) {
            // It'd be nice just to raise an exception here, but it's not very PHP-like
            $this->_lasterror = curl_error($curl);
        } else {
            $this->_lasterror = null;
        }
        curl_close($curl);

        return $success;
    }

    /**
     * Generate a Sentry authorization header string
     *
     * @param string    $timestamp      Timestamp when the event occurred
     * @param string    $client         HTTP client name (not Raven_Client object)
     * @param string    $api_key        Sentry API key
     * @param string    $secret_key     Sentry API key
     * @return string
     */
    protected function get_auth_header($timestamp, $client, $api_key, $secret_key)
    {
        $header = array(
            sprintf('sentry_timestamp=%F', $timestamp),
            "sentry_client={$client}",
            sprintf('sentry_version=%s', self::PROTOCOL),
        );

        if ($api_key) {
            $header[] = "sentry_key={$api_key}";
        }

        if ($secret_key) {
            $header[] = "sentry_secret={$secret_key}";
        }


        return sprintf('Sentry %s', implode(', ', $header));
    }

    /**
     * Generate an uuid4 value
     *
     * @return string
     */
    private function uuid4()
    {
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        return str_replace('-', '', $uuid);
    }

    /**
     * Return the URL for the current request
     *
     * @return string|null
     */
    private function get_current_url()
    {
        // When running from commandline the REQUEST_URI is missing.
        if (!isset($_SERVER['REQUEST_URI'])) {
            return null;
        }

        $schema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

        // HTTP_HOST is a client-supplied header that is optional in HTTP 1.0
        $host = (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
            : (!empty($_SERVER['LOCAL_ADDR'])  ? $_SERVER['LOCAL_ADDR']
            : (!empty($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '')));

        return $schema . $host . $_SERVER['REQUEST_URI'];
    }

    /**
     * Get the value of a key from $_SERVER
     *
     * @param string $key       Key whose value you wish to obtain
     * @return string           Key's value
     */
    private function _server_variable($key)
    {
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return '';
    }

    /**
     * Translate a PHP Error constant into a Sentry log level group
     *
     * @param string $severity  PHP E_$x error constant
     * @return string           Sentry log level group
     */
    public function translateSeverity($severity)
    {
        if (is_array($this->severity_map) && isset($this->severity_map[$severity])) {
            return $this->severity_map[$severity];
        }
        switch ($severity) {
            case E_ERROR:              return Raven_Client::ERROR;
            case E_WARNING:            return Raven_Client::WARN;
            case E_PARSE:              return Raven_Client::ERROR;
            case E_NOTICE:             return Raven_Client::INFO;
            case E_CORE_ERROR:         return Raven_Client::ERROR;
            case E_CORE_WARNING:       return Raven_Client::WARN;
            case E_COMPILE_ERROR:      return Raven_Client::ERROR;
            case E_COMPILE_WARNING:    return Raven_Client::WARN;
            case E_USER_ERROR:         return Raven_Client::ERROR;
            case E_USER_WARNING:       return Raven_Client::WARN;
            case E_USER_NOTICE:        return Raven_Client::INFO;
            case E_STRICT:             return Raven_Client::INFO;
            case E_RECOVERABLE_ERROR:  return Raven_Client::ERROR;
        }
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            switch ($severity) {
            case E_DEPRECATED:         return Raven_Client::WARN;
            case E_USER_DEPRECATED:    return Raven_Client::WARN;
          }
        }
        return Raven_Client::ERROR;
    }

    /**
     * Provide a map of PHP Error constants to Sentry logging groups to use instead
     * of the defaults in translateSeverity()
     *
     * @param array $map
     */
    public function registerSeverityMap($map)
    {
        $this->severity_map = $map;
    }

    /**
     * Convenience function for setting a user's ID and Email
     *
     * @param string $id            User's ID
     * @param string|null $email    User's email
     * @param array $data           Additional user data
     */
    public function set_user_data($id, $email=null, $data=array())
    {
        $this->user_context(array_merge(array(
            'id'    => $id,
            'email' => $email,
        ), $data));
    }

    /**
     * Sets user context.
     *
     * @param array $data   Associative array of user data
     */
    public function user_context($data)
    {
        $this->context->user = $data;
    }

    /**
     * Appends tags context.
     *
     * @param array $data   Associative array of tags
     */
    public function tags_context($data)
    {
        $this->context->tags = array_merge($this->context->tags, $data);
    }

    /**
     * Appends additional context.
     *
     * @param array $data   Associative array of extra data
     */
    public function extra_context($data)
    {
        $this->context->extra = array_merge($this->context->extra, $data);
    }

    /**
     * @param array $processors
     */
    public function setProcessors(array $processors)
    {
        $this->processors = $processors;
    }
}
