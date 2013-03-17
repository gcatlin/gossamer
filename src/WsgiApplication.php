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
class WsgiApplication {
	/**
	 *
	 *
	 * @var bool
	 */
	protected $debug;

	/**
	 *
	 *
	 * @var array
	 */
	protected $uri_mappings;

	/**
	 *
	 *
	 * @param array $uri_mappings
	 * @param bool  $debug
	 */
	public function __construct($uri_mappings=array(), $debug=false) {
		$this->uri_mappings = (array) $uri_mappings;
		$this->debug = (bool) $debug;
	}

	/**
	 *
	 *
	 * @param  array $env
	 * @return array (status string, headers array, body array)
	 */
	public function __invoke($env) {
		$request = new Request($env);
		$response = new Response();

		list($handler_class, $args) = $this->route($request->getPathInfo());

		if ($handler_class) {
			try {
				// $handler = new $handler_class();
				// $handler->initialize($request, $response);
				$handler = new $handler_class($request, $response);
				$method = Request::$methods[$request->getMethod()];
				call_user_func_array(array($handler, $method), $args);
			} catch (Exception $e) {
				try {
					$handler->handleException($e, $this->debug);
				} catch (Exception $e) {
					// @TODO improve handling and formatting of exception handler failure
					$response->setStatus(Response::InternalServerError);
					if ($this->debug) {
						$response->write($e->getMessage() . "\n");
					}
				}
			}
		} else {
			// @TODO allow overriding this behavior, use magic uri_mappings (e.g. [404])
			// $handler->notFound();
			$response->setStatus(Response::NotFound);
			$response->setHeader('Content-Type', 'text/html'); // @TODO tailor output to requested content types
			$response->write("<h1>Not Found</h1>\n"); // @TODO tailor output to requested content types
		}

		return $response->toArray();
	}

	/**
	 * Executes this WsgiApplication instance using the supplied environment
	 * configuration. If null, it will be created from the environment supplied
	 * by the PHP SAPI.
	 *
	 * @param array $env (optional)
	 */
	public function run($env=null) {
		Gossamer::run(array($this, '__invoke'), $env);
	}

	/**
	 *
	 *
	 * @param  string $path
	 * @return array  (class name string, args array)
	 */
	protected function route($path) {
		// @TODO make this more sophisticated
		foreach ($this->uri_mappings as $pattern => $handler_class) {
			$pattern = "`^{$pattern}$`U";
			if (preg_match($pattern, $path, $matches)) {
				$args = array_slice($matches, 1);
				return array($handler_class, $args);
			}
		}
		return array(false, array());
	}
}
