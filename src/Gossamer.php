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

namespace gcatlin\gossamer;

/**
 *
 */
class Gossamer {
    /**
     * Executes the WSGI application callback using the supplied environment. If
     * null, it will be created from the environment supplied by the PHP SAPI.
     *
     * @param callback $callback
     * @param array $env
     */
    public static function run($callback, $env=null) {
        if (PHP_SAPI == 'cli') {
            $runner = new GossamerCliRunner();
        } else {
            $runner = new GossamerRunner();
        }
        $runner->run($callback, $env);
    }
}


/**
 * @TODO reduce duplication with GossamerCliRunner
 */
class GossamerRunner {
    /**
     * The HTTP version of the response
     *
     * @var string
     */
    protected $http_version = 'HTTP/1.1';

    /**
     * Executes the supplied WSGI application using the supplied environment
     * configuration and sends the appropriate HTTP status line, headers, and
     * body content to the client. If no enviroment configuration is supplied,
     * PHP's $_SERVER super-global will be used.
     *
     * @param mixed $callback
     * @param array $env optional
     */
    public function run($callback, $env=null) {
        if ($env === null) {
            $env = $_SERVER;

            if (isset($env['HTTPS']) && $env['HTTPS'] != 'off') {
                $env['wsgi.uri_scheme'] = 'https';
            } else {
                $env['wsgi.uri_scheme'] = 'http';
            }

            $env['wsgi.input'] = stream_get_contents('php://input');
        }

        // @TODO validate $env?

        list($status, $headers, $body) = call_user_func($callback, $env);

        // @TODO validate $status, $headers, $body
        // @TODO set Content-Encoding, Content-Length, Transfer-Encoding headers

        header("{$this->http_version} {$status}");

        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }

        foreach ($body as $chunk) {
            echo $chunk;
        }
    }
}

/**
 * GossamerCliRunner allows any WsgiApplication to be executed from the
 * command line as if it was called by curl.
 *
 * The following two commands should produce identical output, assuming the
 * local webserver is configured properly:
 *  - $ curl http://localhost/a/b/c
 *  - $ php index.php /a/b/c
 *
 * @TODO reduce duplication with GossamerCliRunner
 */
class GossamerCliRunner {
    /**
     * The HTTP version of the response
     *
     * @var string
     */
    protected $http_version = 'HTTP/1.1';

    /**
     * The name and version of the information server software making the CGI
     * request (and running the gateway). It is the same as the server
     * description reported to the client via the 'Server' header. This is
     * handled automatically by most non-CLI PHP SAPIs.
     *
     * @var string
     */
    protected $server_software = 'Gossamer/0.0.1';

    /**
     * Executes the supplied WSGI application using the supplied environment
     * configuration and sends the appropriate HTTP status line, headers, and
     * body content to the client. If no enviroment configuration is supplied,
     * it will be constructed from the arguments passed to PHP via the CLI SAPI.
     *
     * @param mixed $callback
     * @param array $env optional
     */
    public function run($callback, $env=null) {
        // @TODO make this much better
        $options = getopt('Hd::iX::');
        $include_headers = isset($options['i']);

        if ($env === null) {
            $argv = array_slice($_SERVER['argv'], 1);
            $argc = $_SERVER['argc'] - 1;
            $uri = (strpos($argv[0], '-') !== 0 ? $argv[0] : $argv[$argc - 1]);
            $method = (isset($options['X']) ? $options['X'] : 'GET');
            $headers = (isset($options['H']) ? (array) $options['H'] : array());
            $body = (isset($options['d']) ? $options['d'] : null);

            $env = Env::create($uri, $method, $headers, $body);

            $included_files = get_included_files();
            $pathinfo = pathinfo($included_files[0]);
            $env['SCRIPT_NAME'] = '/' . $pathinfo['basename'];
            $env['PATH_INFO'] = parse_url($uri, PHP_URL_PATH);
            $env['PATH_TRANSLATED'] = $pathinfo['dirname'] . $env['PATH_INFO'];

            // $env['REMOTE_HOST'] = php_uname('n');
            // $dns_record = dns_get_record($env['REMOTE_HOST']);
            // $env['REMOTE_ADDR'] = $dns_record[0]['ip'];

            $env['SERVER_SOFTWARE'] = $this->server_software;
        }

        // @TODO validate $env?

        list($status, $headers, $body) = call_user_func($callback, $env);

        // @TODO validate $status, $headers, $body

        // @TODO implement this as middleware?
        if ($include_headers) {
            $forced_headers = array(
                'Date'       => gmdate('D, d M Y H:i:s') . ' GMT',
                'Server'     => $this->server_software,
                'Vary'       => 'Host', // @TODO do this correctly
                'Connection' => 'close',
            );

            if (ini_get('expose_php')) {
                $forced_headers['X-Powered-By'] = 'PHP/' . PHP_VERSION;
            }

            // @TODO Content-Type, Content-Encoding, Transfer-Encoding

            if (!empty($body)) {
                $content_length = array_sum(array_map('strlen', $body));
                if ($content_length >= 1) {
                    $forced_headers['Content-Length'] = $content_length;
                }
            }

            // Forced headers appear before user-supplied headers in the response
            $headers = $forced_headers + $headers;

            echo "{$this->http_version} {$status}\n";

            foreach ($headers as $name => $value) {
                echo "{$name}: {$value}\n";
            }
            echo "\n";
        }

        foreach ($body as $chunk) {
            echo $chunk;
        }
    }
}

// function simple_app($env) {
// 	return array(
// 		'200 OK',
// 		array('Content-Type' => 'text/plain'),
// 		array("Hello world!\n")
// 	);
// }
// Gossamer::run('simple_app');
// Gossamer::run('simple_app');
// Gossamer::run('simple_app');exit;
//
// class SimpleApp extends WsgiApplication {
// 	public function __invoke($env) {
// 		return array(
// 			'200 OK',
// 			array('Content-Type' => 'text/plain'),
// 			array("Hello world!\n")
// 		);
// 	}
// }
// $app = new SimpleApp();
// $app->run();
//
// class MainPage extends RequestHandler {
// 	public function get() {
// 		$this->response->setHeader('Content-Type', 'text/plain');
// 		$this->response->write("Hello world!\n");
// 	}
// }
// $app = new WsgiApplication(array('/(.*)' => 'MainPage'));
// $app->run();
