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

use gcatlin\gossamer;

class WsgiApplicationTest extends \PHPUnit_Framework_TestCase {
	public function test_CallingRunMethodInvokesWsgiApplicationInstance() {
		$app = new TestWsgiApplication();
		ob_start();
		$app->run();
		$output = ob_get_clean();
		self::assertEquals('Hello world!', $output);
	}

	public function test_NotFound() {
		$app = new WsgiApplication();
		ob_start();
		$app->run();
		$output = ob_get_clean();
		self::assertEquals("<h1>Not Found</h1>\n", $output);
		// @TODO check status
	}

	public function test_Routing() {
		$arg = 'abc';
		$app = new WsgiApplication(array('/(.*)' => __NAMESPACE__ . '\\TestRequestHandler'));
		$env = array('SCRIPT_NAME' => '', 'PATH_INFO' => '/' . $arg);
		ob_start();
		$app->run($env);
		$output = ob_get_clean();
		self::assertEquals($arg, $output);
	}
}

class TestWsgiApplication extends WsgiApplication {
	public function __invoke($env) {
		return array(
			'200 OK',
			array('Content-Type' => 'text/plain'),
			array('Hello world!')
		);
	}
}

class TestRequestHandler extends RequestHandler {
	public function get($arg) {
		$this->response->write($arg);
	}
}
