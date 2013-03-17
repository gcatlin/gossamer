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
 * Env is a wrapper class for relevant constants and static method(s).
 */
class Env implements \ArrayAccess {
    /**
     * The host name to use if none is supplied.
     */
    const DefaultHost = 'localhost';

    /**
     * The assumed HTTP version of the request.
     */
    const DefaultHttpVersion = 'HTTP/1.1';

    /**
     * The HTTP method to use if none is specified.
     */
    const DefaultHttpMethod = 'GET';

    /**
     * The uri scheme to use if none is specified.
     *
     * @var string
     */
    const DefaultPort = '80';

    /**
     * The uri scheme to use if none is specified.
     *
     * @var string
     */
    const DefaultScheme = 'http';

    protected $env = array();

    /**
     * Creates a well-formed environment array based on the supplied inputs.
     *
     * @param string $uri
     * @param string $method
     * @param array  $headers
     * @param string $body
     * @return array
     */
    public static function create($uri='', $method=self::DefaultHttpMethod, $headers=array(), $body=null, $http_version=self::DefaultHttpVersion) {
        $uri = Uri::parse($uri);
        // normalize?

        $uri_scheme = (isset($uri['scheme']) ? strtolower($uri['scheme']) : self::DefaultScheme);
        $is_http = ($uri_scheme == 'http');
        $is_https = ($uri_scheme == 'https');

        $server_name = (isset($uri['host']) ? $uri['host'] : self::DefaultHost);
        $server_port = (isset($uri['port']) ? $uri['port'] : ($is_https ? '443' : self::DefaultPort));

        if (($is_https && $server_port != '443') || ($is_http && $server_port != '80') || (!$is_https && !$is_http)) {
            $http_host = $server_name . ':' . $server_port;
        } else {
            $http_host = $server_name;
        }

        $path = (isset($uri['path']) ? $uri['path'] : '');
        $path_parts = explode('.php', $path, 2);
        if (isset($path_parts[1])) {
            $script_name = Uri::normalizePath('/' . $path_parts[0] . '.php');
            $path_info = Uri::normalizePath('/' . $path_parts[1]);
        } else {
            $script_name = '';
            $path_info = Uri::normalizePath('/' . $path);
        }

        $query_string = (isset($uri['query']) ? $uri['query'] : '');

        $request_method = strtoupper($method);
        if (!isset(Request::$methods[$request_method])) {
            $request_method = self::DefaultHttpMethod;
        }

        $env = array(
            'HTTP_HOST'       => $http_host,
            'PATH_INFO'       => $path_info,
            'QUERY_STRING'    => $query_string,
            'REQUEST_METHOD'  => $request_method,
            'SCRIPT_NAME'     => $script_name,
            'SERVER_NAME'     => $server_name,
            'SERVER_PORT'     => $server_port,
            'SERVER_PROTOCOL' => $http_version,
        );

        $headers = (array) $headers;
        if ($headers) {
            foreach ($headers as $name => $value) {
                $normalized_name = strtoupper(str_replace('-', '_', $name));
                if ($name[0] == 'X' && $name[1] == '-') {
                    $env[$normalized_name] = $value;
                } else {
                    $env['HTTP_' . $normalized_name] = $value;
                }
            }

            unset($env['HTTP_CONTENT_LENGTH']);

            if (isset($env['HTTP_CONTENT_TYPE'])) {
                $env['CONTENT_TYPE'] = $env['HTTP_CONTENT_TYPE'];
                unset($env['HTTP_CONTENT_TYPE']);
            }
        }

        // @TODO Authorization, AUTH_TYPE, REMOTE_IDENT, REMOTE_USER

        $env['wsgi.input'] = $body;
        if (isset($body[0])) {
            // @TODO support files
            // @TODO determine content-type dynamically (or just use text/plain)
            // @TODO charset
            $env['CONTENT_LENGTH'] = (string) strlen($body);
        }

        $env['wsgi.uri_scheme'] = $uri_scheme;

        return new Env($env);
    }

    public function __construct($env) {
        $this->env = $env;
    }

    public function offsetExists($offset) {
        return isset($this->env[$offset]);
    }

    public function offsetGet($offset) {
        return (isset($this->env[$offset]) ? $this->env[$offset] : null);
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->env[] = $value;
        } else {
            $this->env[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->env[$offset]);
    }
}
