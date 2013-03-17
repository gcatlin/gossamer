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

class RequestTest extends \PHPUnit_Framework_TestCase {
	public function test_ArgumentNames() {
		$req = new Request(array());
		self::assertEquals(array(), $req->arguments());

		$req = new Request(array('QUERY_STRING' => 'a&c', 'wsgi.input' => 'e&g'));
		self::assertEquals(array('a', 'c'), $req->arguments());

		$req = new Request(array('QUERY_STRING' => 'a&c', 'wsgi.input' => 'e&g', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded'));
		self::assertEquals(array('a', 'c', 'e', 'g'), $req->arguments());
	}

	public function test_ArgumentValues() {
		$req = new Request(array());
		self::assertEquals(null, $req->get('a'));
		self::assertEquals(1, $req->get('a', 1));
		self::assertEquals(1, $req->getRange('a', 1, 10));

		$req = new Request(array('QUERY_STRING' => 'a=b&c=d', 'wsgi.input' => 'e=f&g=h', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded'));
		self::assertEquals('b', $req->get('a'));
		self::assertEquals('d', $req->get('c'));
		self::assertEquals('f', $req->get('e'));
		self::assertEquals('h', $req->get('g'));
	}

	public function test_ContentLength() {
		$req = new Request(array());
		self::assertEquals('', $req->getContentLength());

		$len = 10;
		$req = new Request(array('CONTENT_LENGTH' => $len));
		self::assertEquals($len, $req->getContentLength());
		self::assertEquals($len, $req->getHeader('Content-Length'));
	}

	public function test_ContentType() {
		$req = new Request(array());
		self::assertEquals('', $req->getContentType());

		$type = 'text/plain';
		$req = new Request(array('CONTENT_TYPE' => $type));
		self::assertEquals($type, $req->getContentType());
		self::assertEquals($type, $req->getHeader('Content-Type'));
	}

	public function test_Headers() {
		$req = new Request(array());
		self::assertNull($req->getHeader('INVALID'));
		self::assertEquals(1, count($req->getHeaders())); // Host

		$req = new Request(array('HTTP_HOST' => 'host', 'X_HEADER' => 'value'));
		self::assertEquals('value', $req->getHeader('X-Header'));
		self::assertEquals(2, count($req->getHeaders())); // Host, X-Header
	}

	public function test_Host() {
		$req = new Request(array());
		self::assertEquals(Env::DefaultHost, $req->getHost('Host'));
		self::assertEquals(Env::DefaultHost, $req->getHeader('Host'));

		$req = new Request(array('HTTP_HOST' => '', 'SERVER_NAME' => ''));
		self::assertEquals(Env::DefaultHost, $req->getHost('Host'));
		self::assertEquals(Env::DefaultHost, $req->getHeader('Host'));

		$req = new Request(array('HTTP_HOST' => 'h:8000'));
		self::assertEquals('h', $req->getHost('Host'));
		self::assertEquals('h:8000', $req->getHeader('Host'));

		$req = new Request(array('SERVER_NAME' => 'h'));
		self::assertEquals('h', $req->getHost('Host'));
		self::assertEquals('h', $req->getHeader('Host'));
	}

	public function test_HttpMessage() {
		$req = new Request(array());
		self::assertEquals("GET / HTTP/1.1\r\nHost: localhost\r\n\r\n", $req->getHttpMessage());

		$req = new Request(array('HTTP_HOST' => 'h:1', 'X_Y' => 'Z', 'wsgi.input' => 'test'));
		self::assertEquals("GET / HTTP/1.1\r\nHost: h:1\r\nX-Y: Z\r\n\r\ntest", $req->getHttpMessage());
	}

	public function test_HttpMethod() {
		$req = new Request(array());
		self::assertEquals(Env::DefaultHttpMethod, $req->getMethod());

		$req = new Request(array('REQUEST_METHOD' => 'METHOD'));
		self::assertEquals('METHOD', $req->getMethod());
	}

	public function test_HttpVersion() {
		$req = new Request(array());
		self::assertEquals(Env::DefaultHttpVersion, $req->getHttpVersion());

		$req = new Request(array('SERVER_PROTOCOL' => 'TEST/0.0'));
		self::assertEquals('TEST/0.0', $req->getHttpVersion());
	}

	public function test_Path() {
		$req = new Request(array());
		self::assertEquals('/', $req->getPath());
		self::assertEquals('', $req->getScriptName());
		self::assertEquals('', $req->getPathInfo());

		$req = new Request(array('SCRIPT_NAME' => '/index.php', 'PATH_INFO' => '/a/b/c'));
		self::assertEquals('/index.php/a/b/c', $req->getPath());
		self::assertEquals('/a/b/c', $req->getPathInfo());
		self::assertEquals('/index.php', $req->getScriptName());
	}

	public function test_Port() {
		$req = new Request(array());
		self::assertEquals('80', $req->getPort());

		$req = new Request(array('wsgi.uri_scheme' => 'https'));
		self::assertEquals('443', $req->getPort());

		$req = new Request(array('HTTP_HOST' => 'h:8000'));
		self::assertEquals('8000', $req->getPort());

		$req = new Request(array('SERVER_PORT' => '8000'));
		self::assertEquals('8000', $req->getPort());
	}

	public function test_QueryString() {
		$req = new Request(array());
		self::assertEquals('', $req->getQueryString());

		$req = new Request(array('QUERY_STRING' => 'a=b'));
		self::assertEquals('a=b', $req->getQueryString());
	}

	public function test_RemoteAddress() {
		$req = new Request(array());
		self::assertEquals('', $req->getRemoteAddress());

		$req = new Request(array('REMOTE_ADDR' => '192.168.0.1'));
		self::assertEquals('192.168.0.1', $req->getRemoteAddress());
	}

	public function test_RemoteHost() {
		$req = new Request(array());
		self::assertEquals('', $req->getRemoteHost());

		$req = new Request(array('REMOTE_HOST' => 'remotehost'));
		self::assertEquals('remotehost', $req->getRemoteHost());

		$req = new Request(array('REMOTE_ADDR' => '192.168.0.1'));
		self::assertEquals('192.168.0.1', $req->getRemoteHost());
	}

	public function test_Scheme() {
		$req = new Request(array());
		self::assertEquals(Env::DefaultScheme, $req->getScheme());
		self::assertFalse($req->isSecure());

		$req = new Request(array('wsgi.uri_scheme' => 'https'));
		self::assertEquals('https', $req->getScheme());
		self::assertTrue($req->isSecure());
	}

	public function test_ScriptUri() {
		$req = new Request(array());
		self::assertEquals('http://localhost/', $req->getScriptUri());

		$req = new Request(array('HTTP_HOST' => 'h:8000', 'QUERY_STRING' => 'a=b'));
		self::assertEquals('http://h:8000/?a=b', $req->getScriptUri());
	}

	public function test_ServerName() {
		$req = new Request(array());
		self::assertEquals('', $req->getServerName());

		$req = new Request(array('SERVER_NAME' => 'h'));
		self::assertEquals('h', $req->getServerName());

		$req = new Request(array('HTTP_HOST' => 'h'));
		self::assertEquals('h', $req->getServerName());
	}

	public function test_ServerSoftware() {
		$req = new Request(array());
		self::assertEquals('', $req->getServerSoftware());

		$req = new Request(array('SERVER_SOFTWARE' => 'PHP/0.0.0'));
		self::assertEquals('PHP/0.0.0', $req->getServerSoftware());
	}

	public function test_XmlHttpRequest() {
		$req = new Request(array());
		self::assertFalse($req->isXmlHttpRequest());

		$req = new Request(array('X_REQUESTED_WITH' => 'XMLHttpRequest'));
		self::assertTrue($req->isXmlHttpRequest());
	}
}
