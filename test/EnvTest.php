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

class EnvTest extends \PHPUnit_Framework_TestCase {
	protected $env;

	public function test_BodyParameterDeterminesContentLength() {
		$env = Env::create();

		self::assertNull($env['CONTENT_LENGTH']);

		$env = Env::create('', '', array(), '');
		self::assertNull($env['CONTENT_LENGTH']);

		$env = Env::create('', '', array('Content-Length' => 100));
		self::assertNull($env['CONTENT_LENGTH']);

		$env = Env::create('', '', array('Content-Length' => 100), 'test');
		self::assertEquals(4, $env['CONTENT_LENGTH']);
	}

	public function test_BodyParameterDeterminesWsgiInput() {
		$env = Env::create();
		self::assertEquals('', $env['wsgi.input']);

		$body = 'test';
		$env = Env::create('', '', array(), $body);
		self::assertEquals($body, $env['wsgi.input']);
	}

	public function test_ContentLengthHeaderIsSpecialCased() {
		$env = Env::create('', '', array('Content-Length' => '4'), 'test');
		self::assertEquals('4', $env['CONTENT_LENGTH']);
		self::assertNull($env['HTTP_CONTENT_LENGTH']);
	}

	public function test_ContentTypeHeaderIsSpecialCased() {
		$env = Env::create('', '', array('Content-Type' => 'text/plain'));
		self::assertEquals('text/plain', $env['CONTENT_TYPE']);
		self::assertNull($env['HTTP_CONTENT_TYPE']);
	}

	public function test_HeaderNamesAreNormalized() {
		$env = Env::create('', '', array('User-Agent' => 'value'));
		self::assertEquals('value', $env['HTTP_USER_AGENT']);

		$env = Env::create('', '', array('X-Custom-Header' => 'value'));
		self::assertEquals('value', $env['X_CUSTOM_HEADER']);

		$env = Env::create('', '', array('mIxEd_cAsE' => 'value'));
		self::assertEquals('value', $env['HTTP_MIXED_CASE']);
	}

	public function test_HostHeaderOverridesUriHost() {
		$env = Env::create('http://overridden.com', '', array('Host' => 'example.com'));
		self::assertEquals('example.com', $env['HTTP_HOST']);
	}

	public function test_HostHeaderDoesNotIncludeDefaultHttpOrHttpsPort() {
		$env = Env::create('http://example.com');
		self::assertEquals('example.com', $env['HTTP_HOST']);

		$env = Env::create('https://example.com');
		self::assertEquals('example.com', $env['HTTP_HOST']);
	}

	public function test_HostHeaderIncludesNonDefaultHttpOrHttpsPort() {
		$env = Env::create('http://example.com:8000');
		self::assertEquals('example.com:8000', $env['HTTP_HOST']);

		$env = Env::create('http://example.com:44300');
		self::assertEquals('example.com:44300', $env['HTTP_HOST']);
	}

	public function test_HttpVersionParameterDeterminesServerProtocol() {
		$env = Env::create('', '', array(), '', 'TEST/0.0');
		self::assertEquals('TEST/0.0', $env['SERVER_PROTOCOL']);
	}

	public function test_InvalidMethodIsSetToDefault() {
		$env = Env::create('', 'INVALID');
		self::assertEquals(Env::DefaultHttpMethod, $env['REQUEST_METHOD']);
	}

	public function test_MethodParameterDeterminesRequestMethod() {
		$env = Env::create('', 'PUT');
		self::assertEquals('PUT', $env['REQUEST_METHOD']);
	}

	public function test_RequestMethodIsNormalized() {
		$env = Env::create('', 'post');
		self::assertEquals('POST', $env['REQUEST_METHOD']);
	}

	public function test_ServerPortIncludesDefaultHttpPort() {
		$env = Env::create('http://example.com');
		self::assertEquals('80', $env['SERVER_PORT']);

		$env = Env::create('https://example.com');
		self::assertEquals('443', $env['SERVER_PORT']);
	}

	public function test_UnspecifiedHostIsSetToDefault() {
		$env = Env::create();
		self::assertEquals(Env::DefaultHost, $env['HTTP_HOST']);
	}

	public function test_UnspecifiedHttpVersionIsSetToDefault() {
		$env = Env::create();
		self::assertEquals(Env::DefaultHttpVersion, $env['SERVER_PROTOCOL']);
	}

	public function test_UnspecifiedMethodIsSetToDefault() {
		$env = Env::create();
		self::assertEquals(Env::DefaultHttpMethod, $env['REQUEST_METHOD']);
	}

	public function test_UnspecifiedPortIsSetToDefault() {
		$env = Env::create();
		self::assertEquals(Env::DefaultPort, $env['SERVER_PORT']);
	}

	public function test_UnspecifiedSchemeIsSetToDefault() {
		$env = Env::create();
		self::assertEquals(Env::DefaultScheme, $env['wsgi.uri_scheme']);
	}

	public function test_UriAuthorityComponentDeterminesHttpHostAndServerNameAndServerPort() {
		$env = Env::create('http://example.com:8000');
		self::assertEquals('example.com:8000', $env['HTTP_HOST']);
		self::assertEquals('example.com', $env['SERVER_NAME']);
		self::assertEquals('8000', $env['SERVER_PORT']);
	}

	public function test_UriPathComponentDeterminesPathInfoAndScriptName() {
		$env = Env::create();
		self::assertEquals('/', $env['PATH_INFO']);
		self::assertEquals('', $env['SCRIPT_NAME']);

		$env = Env::create('/path');
		self::assertEquals('/path', $env['PATH_INFO']);
		self::assertEquals('', $env['SCRIPT_NAME']);

		$env = Env::create('/index.php/path');
		self::assertEquals('/path', $env['PATH_INFO']);
		self::assertEquals('/index.php', $env['SCRIPT_NAME']);
	}

	public function test_UriQueryComponentDeterminesQueryString() {
		$env = Env::create();
		self::assertEquals('/', $env['PATH_INFO']);
		self::assertEquals('', $env['SCRIPT_NAME']);

		$env = Env::create('/path');
		self::assertEquals('/path', $env['PATH_INFO']);
		self::assertEquals('', $env['SCRIPT_NAME']);

		$env = Env::create('/index.php/path');
		self::assertEquals('/path', $env['PATH_INFO']);
		self::assertEquals('/index.php', $env['SCRIPT_NAME']);
	}

	public function test_UriSchemeComponentDeterminesWsgiUriScheme() {
		$env = Env::create('http://example.com');
		self::assertEquals('http', $env['wsgi.uri_scheme']);

		$env = Env::create('https://example.com');
		self::assertEquals('https', $env['wsgi.uri_scheme']);

		$env = Env::create('SCHEME://example.com');
		self::assertEquals('scheme', $env['wsgi.uri_scheme']);
	}
}
