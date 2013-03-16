<?php
//
// Copyright 2010 Geoff Catlin
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//

/**
 * A Request object is a wrapper for the environment array that gets passed to
 * its constructor.
 *
 * @see http://tools.ietf.org/html/rfc3875
 */
class Request {
    /**
     *
     */
    const CONNECT = 'CONNECT';
    const GET     = 'GET';
    const HEAD    = 'HEAD';
    const OPTIONS = 'OPTIONS';
    const POST    = 'POST';
    const PUT     = 'PUT';
    const TRACE   = 'TRACE';

    /**
     * @var array
     */
    public static $methods = array(
        'CONNECT' => 'connect',
        'GET'     => 'get',
        'HEAD'    => 'head',
        'OPTIONS' => 'options',
        'POST'    => 'post',
        'PUT'     => 'put',
        'TRACE'   => 'trace'
    );

    /**
     *
     * @var string
     */
    protected $body;

    /**
     *
     * @var string
     */
    protected $content_length;

    /**
     *
     * @var string
     */
    protected $content_type;

    /**
     *
     * @var array
     */
    protected $env;

    /**
     *
     * @var array
     */
    protected $headers;

    /**
     *
     * @var string
     */
    protected $host;

    /**
     *
     * @var string
     */
    protected $http_message;

    /**
     *
     * @var string
     */
    protected $http_version;

    /**
     *
     * @var bool
     */
    protected $is_secure;

    /**
     *
     * @var bool
     */
    protected $is_xmlhttprequest;

    /**
     *
     * @var string
     */
    protected $method;

    /**
     *
     * @var array
     */
    protected $parsed_body;

    /**
     *
     * @var array
     */
    protected $parsed_query;

    /**
     *
     * @var string
     */
    protected $path;

    /**
     *
     * @var string
     */
    protected $path_info;

    /**
     *
     * @var string
     */
    protected $port;

    /**
     *
     * @var string
     */
    protected $query_string;

    /**
     *
     * @var string
     */
    protected $remote_addr;

    /**
     *
     * @var string
     */
    protected $remote_host;

    /**
     *
     * @var string
     */
    protected $scheme;

    /**
     *
     * @var string
     */
    protected $script_name;

    /**
     *
     * @var string
     */
    protected $script_uri;

    /**
     *
     * @var string
     */
    protected $server_name;

    /**
     *
     * @var string
     */
    protected $server_software;

    /**
     * @param array $env
     */
    public function __construct($env=array()) {
        $this->env = (array) $env;
    }

    /**
     * Returns a list of the names of query or POST data arguments. An
     * argument name only appears once in the list, even if the data contains
     * multiple arguments with the same name.
     *
     * @return array
     */
    public function arguments() {
        if ($this->arguments === null) {
            $query = array_keys($this->getParsedQuery());
            $body = array_keys($this->getParsedBody());
            $this->arguments = array_merge($query, $body);
        }
        return $this->arguments;
    }

    /**
     * Returns values for arguments parsed from the query string, or null if the
     * argument does not exist. The method takes the argument name as its first
     * parameter.
     *
     * If the argument appears more than once in a request, this method returns
     * returns the last occurrence.
     *
     * @param $name string
     * @return string
     */
    public function get($name, $default_value=null) {
        $parsed_query = $this->getParsedQuery();
        if (isset($parsed_query[$name])) {
            return $this->parsed_query[$name];
        }

        $parsed_body = $this->getParsedBody();
        if (isset($parsed_body[$name])) {
            return $this->parsed_body[$name];
        }

        return $default_value;
    }

    /**
     * Returns the message body of the request.
     *
     * @return string
     */
    public function getBody() {
        if ($this->body === null) {
            if (isset($this->env['wsgi.input'])) {
                $this->body = $this->env['wsgi.input'];
            } else {
                $this->body = '';
            }
        }
        return $this->body;
    }

    /**
     *
     *
     * @return string
     */
    public function getContentLength() {
        if ($this->content_length === null) {
            if (isset($this->env['CONTENT_LENGTH'])) {
                $this->content_length = $this->env['CONTENT_LENGTH'];
            } else {
                $this->content_length = '';
            }
        }
        return $this->content_length;
    }

    /**
     *
     *
     * @return string
     */
    public function getContentType() {
        if ($this->content_type === null) {
            if (isset($this->env['CONTENT_TYPE'])) {
                $this->content_type = $this->env['CONTENT_TYPE'];
            } else {
                $this->content_type = '';
            }
        }
        return $this->content_type;
    }

    /**
     *
     *
     * @return string
     */
    public function getHeader($name) {
        if ($this->headers === null) {
            $this->getHeaders();
        }

        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }
        return null;
    }

    /**
     * Returns an associative array containing the HTTP headers for the request.
     *
     * @return array
     */
    public function getHeaders() {
        if ($this->headers === null) {
            $this->headers = array('Host' => '');

            // Converts "HTTP_HEADER_NAME" to "Header-Name" and "X_HEADER_NAME" to "X-Header-Name"
            foreach ($this->env as $name => $value) {
                if (strpos($name, 'HTTP_') === 0 || strpos($name, 'X_') === 0) {
                    if ($name[0] == 'H') {
                        $name = substr($name, 5);
                    }
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
                    $this->headers[$name] = $value;
                }
            }

            if (isset($this->env['HTTP_HOST'][0])) {
                $this->headers['Host'] = $this->env['HTTP_HOST'];
            } else {
                $this->headers['Host'] = $this->getHost();
            }

            if (isset($this->env['CONTENT_LENGTH'])) {
                $this->headers['Content-Length'] = $this->getContentLength();
            }

            if (isset($this->env['CONTENT_TYPE'])) {
                $this->headers['Content-Type'] = $this->getContentType();
            }

            // @TODO AUTH_TYPE
        }

        return $this->headers;
    }

    /**
     *
     *
     * @return string
     */
    public function getHost() {
        if ($this->host === null) {
            if (isset($this->env['HTTP_HOST'][0])) {
                $host_port = explode(':', $this->env['HTTP_HOST'], 2);
                $this->host = $host_port[0];
            } elseif (isset($this->env['SERVER_NAME'][0])) {
                $this->host = $this->env['SERVER_NAME'];
            } else {
                $this->host = Env::DefaultHost;
            }
        }
        return $this->host;
    }

    /**
     * Returns a reconstructed version of a HTTP request message that would have
     * yielded this request object. It is not necessarily the same as the
     * *actual* HTTP request message (assuming one exists).
     *
     * @return string
     */
    public function getHttpMessage() {
        if ($this->http_message === null) {
            $this->http_message = "{$this->getMethod()} {$this->getPath()} {$this->getHttpVersion()}\r\n";
            foreach ($this->getHeaders() as $name => $value) {
                $this->http_message .= "{$name}: {$value}\r\n";
            }
            $this->http_message .= "\r\n{$this->getBody()}";
        }
        return $this->http_message;
    }

    /**
     * Returns the HTTP version of the request.
     *
     * @return string
     */
    public function getHttpVersion() {
        if ($this->http_version === null) {
            if (isset($this->env['SERVER_PROTOCOL'])) {
                $this->http_version = $this->env['SERVER_PROTOCOL'];
            } else {
                $this->http_version = Env::DefaultHttpVersion;
            }
        }
        return $this->http_version;
    }

    /**
     * Returns the REQUEST_METHOD component of the request.
     *
     * @return string
     */
    public function getMethod() {
        if ($this->method === null) {
            if (isset($this->env['REQUEST_METHOD']) && isset(Request::$methods)) {
                $this->method = strtoupper($this->env['REQUEST_METHOD']);
            } else {
                $this->method = Env::DefaultHttpMethod;
            }
        }
        return $this->method;
    }

    /**
     *
     *
     * @return array
     */
    public function getParsedBody() {
        if ($this->parsed_body === null) {
            $this->parsed_body = array();
            $body = $this->getBody();
            $content_type = (isset($this->env['CONTENT_TYPE']) ? $this->env['CONTENT_TYPE'] : '');
            if ($body !== null && $content_type == 'application/x-www-form-urlencoded') {
                // @TODO work-around limitation of parse_str (e.g. 'a=1&a=2' -> 'a=2', 'a.b=1' or 'a b=1' -> 'a_b=1')
                $decoded_body = html_entity_decode($body);
                parse_str($decoded_body, $this->parsed_body);
            } else {
                $this->parsed_body = $body;
            }
        }
        return $this->parsed_body;
    }

    /**
     * Parses the query string of the request and returns an associative array.
     *
     * @return array
     */
    public function getParsedQuery() {
        if ($this->parsed_query === null) {
            $this->parsed_query = array();
            $query_string = $this->getQueryString();
            if ($query_string != '') {
                // @TODO work-around limitation of parse_str (e.g. 'a=1&a=2' -> 'a=2', 'a.b=1' or 'a b=1' -> 'a_b=1')
                // @TODO move this functionality to Uri class
                $decoded_query_string = html_entity_decode($query_string);
                parse_str($decoded_query_string, $this->parsed_query);
            }
        }
        return $this->parsed_query;
    }

    /**
     * Returns the path of the requested URI, between the host name and the
     * query parameters.
     *
     * @return string
     */
    public function getPath() {
        if ($this->path === null) {
            $this->path = '';
            if (isset($this->env['SCRIPT_NAME'])) {
                $this->path .= $this->env['SCRIPT_NAME']; // @TODO urlencode?
            }

            if (isset($this->env['PATH_INFO'])) {
                $this->path .= $this->env['PATH_INFO'];
            }

            if (!isset($this->path[0])) {
                $this->path = '/';
            }
        }
        return $this->path;
    }

    /**
     * Returns the PATH_INFO component of the requested URI.
     *
     * @return string
     */
    public function getPathInfo() {
        if ($this->path_info === null) {
            if (isset($this->env['PATH_INFO'])) {
                $this->path_info = $this->env['PATH_INFO']; // @TODO urlencode?
            } else {
                $this->path_info = '';
            }
        }
        return $this->path_info;
    }

    /**
     * Returns the port of the requested URI. It defaults to 80 for http and 443
     * for https requests.
     *
     * @return string
     */
    public function getPort() {
        if ($this->port === null) {
            if (isset($this->env['SERVER_PORT'])) {
                $this->port = (string) $this->env['SERVER_PORT'];
            } elseif (isset($this->env['HTTP_HOST'])) {
                $host_port = explode(':', $this->env['HTTP_HOST'], 2);
                if (isset($host_port[1])) {
                    $this->port = $host_port[1];
                }
            }

            if ($this->port === null) {
                if ($this->isSecure()) {
                    $this->port = '443';
                } else {
                    $this->port = '80';
                }
            }
        }
        return $this->port;
    }

    /**
     * Returns the query parameters of the requested URI, everything after the
     * first '?'.
     *
     * @return string
     */
    public function getQueryString() {
        if ($this->query_string === null) {
            if (isset($this->env['QUERY_STRING'])) {
                $this->query_string = $this->env['QUERY_STRING'];
            } else {
                $this->query_string = '';
            }
        }
        return $this->query_string;
    }

    /**
     * Parses the query or POST data argument with the given name as an integer
     * and returns it. The value is normalized to be within the given range,
     * if any.
     *
     * @param string $name
     * @param int $min_vaue
     * @param int $max_value
     * @param int $default_value (optional)
     * @return int
     */
    public function getRange($name, $min_value, $max_value, $default_value=null) {
        $value = $this->get($name, $default_value);
        return min(max($min_value, $value), $max_value);
    }

    /**
     *
     *
     * @return string
     */
    public function getRemoteAddress() {
        if ($this->remote_address === null) {
            if (isset($this->env['REMOTE_ADDR'])) {
                $this->remote_address = $this->env['REMOTE_ADDR'];
            } else {
                $this->remote_address = '';
            }
        }
        return $this->remote_address;
    }

    /**
     *
     *
     * @return string
     */
    public function getRemoteHost() {
        if ($this->remote_host === null) {
            if (isset($this->env['REMOTE_HOST'])) {
                $this->remote_host = $this->env['REMOTE_HOST'];
            } elseif (isset($this->env['REMOTE_ADDR'])) {
                $this->remote_host = $this->env['REMOTE_ADDR'];
            } else {
                $this->remote_host = '';
            }
        }
        return $this->remote_host;
    }

    /**
     * Returns the scheme of the requested URI, either 'http' or 'https'.
     *
     * @return string
     */
    public function getScheme() {
        if ($this->scheme === null) {
            if (isset($this->env['wsgi.uri_scheme']) && $this->env['wsgi.uri_scheme'] == 'https') {
                $this->scheme = 'https';
                $this->is_secure = true;
            } else {
                $this->scheme = 'http';
                $this->is_secure = false;
            }
        }
        return $this->scheme;
    }

    /**
     *
     *
     * @return string
     */
    public function getScriptName() {
        if ($this->script_name === null) {
            if (isset($this->env['SCRIPT_NAME'])) {
                $this->script_name = $this->env['SCRIPT_NAME'];
            } else {
                $this->script_name = '';
            }
        }
        return $this->script_name;
    }

    /**
     * Returns a reconstructed version of the requested URI. It is not
     * necessarily the same as the *actually* requested URI.
     *
     * @return string
     */
    public function getScriptUri() {
        if ($this->script_uri === null) {
            $uri = array();
            $uri['scheme'] = $this->getScheme();
            $uri['host'] = $this->getHost();
            $uri['path'] = $this->getPath();

            if (($query = $this->getQueryString()) !== '') {
                $uri['query'] = $query;
            }

            $port = $this->getPort();
            if (($this->is_secure && $port != '443') || (!$this->is_secure && $port != '80')) {
                $uri['port'] = $port;
            }

            $this->script_uri = Uri::unparse($uri); // @TODO return a Uri object?
        }
        return $this->script_uri;
    }

    /**
     *
     *
     * @return string
     */
    public function getServerName() {
        if ($this->server_name === null) {
            if (isset($this->env['SERVER_NAME'])) {
                $this->server_name = $this->env['SERVER_NAME'];
            } elseif (isset($this->env['HTTP_HOST'][0])) {
                $host_port = explode(':', $this->env['HTTP_HOST'], 2);
                $this->server_name = $host_port[0];
            } else {
                $this->server_name = '';
            }
        }
        return $this->server_name;
    }

    /**
     *
     *
     * @return string
     */
    public function getServerSoftware() {
        if ($this->server_software === null) {
            if (isset($this->env['SERVER_SOFTWARE'])) {
                $this->server_software = $this->env['SERVER_SOFTWARE'];
            } else {
                $this->server_software = '';
            }
        }
        return $this->server_software;
    }

    /**
     * @return bool
     */
    public function isSecure() {
        if ($this->is_secure === null) {
            $this->getScheme();
        }
        return $this->is_secure;
    }

    /**
     * @return bool
     */
    public function isXmlHttpRequest() {
        if ($this->is_xmlhttprequest === null) {
            if (isset($this->env['X_REQUESTED_WITH']) && strcasecmp($this->env['X_REQUESTED_WITH'], 'xmlhttprequest') === 0) {
                $this->is_xmlhttprequest = true;
            } else {
                $this->is_xmlhttprequest = false;
            }
        }
        return $this->is_xmlhttprequest;
    }
}
