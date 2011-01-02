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

class ResponseTest extends PHPUnit_Framework_TestCase {
	public function test_AppendToHeader() {
		$res = new Response();
		$res->setHeader('a', 'b');
		$res->setHeader('a', 'c', true);
		self::assertEquals('b,c', $res->getHeader('a'));
	}

	public function test_AppendToBody() {
		$res = new Response();
		self::assertEquals(array(), $res->getBody());

		$res = new Response();
		$res->write('stuff');
		self::assertEquals(array('stuff'), $res->getBody());
	}

	public function test_ClearBody() {
		$res = new Response();
		$res->write('stuff');
		$res->clear();
		self::assertEquals(array(), $res->getBody());
	}

	public function test_SetBody() {
		$res = new Response();
		$res->setBody('stuff');
		self::assertEquals(array('stuff'), $res->getBody());

		$res = new Response();
		$res->setBody(array('stuff'));
		self::assertEquals(array('stuff'), $res->getBody());
	}

	public function test_Headers() {
		$res = new Response();
		self::assertEquals(array(), $res->getHeaders());
		self::assertNull($res->getHeader('MISSING'));

		$res = new Response();
		$res->setHeader('a', 'b');
		self::assertEquals(array('a' => 'b'), $res->getHeaders());
		self::assertEquals('b', $res->getHeader('a'));
	}

	public function test_Status() {
		self::assertEquals('', Response::getHttpStatusMessage(0));
		self::assertEquals('OK', Response::getHttpStatusMessage(200));

		$res = new Response();
		$res->setStatus(0);
		self::assertEquals('0 ', $res->getStatus());

		$res = new Response();
		$res->setStatus(200);
		self::assertEquals('200 OK', $res->getStatus());

		$res = new Response();
		$res->setStatus(200, 'Custom');
		self::assertEquals('200 Custom', $res->getStatus());
	}

	public function test_ToArray() {
		$res = new Response();
		self::assertEquals(array('200 OK', array(), array()), $res->toArray());

		$res = new Response();
		$res->setHeader('a', 'b');
		$res->write('hi');
		self::assertEquals(array('200 OK', array('a' => 'b'), array('hi')), $res->toArray());
	}
}
