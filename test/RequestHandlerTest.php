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

class RequestHandlerTest extends PHPUnit_Framework_TestCase {
	protected $handler;
	protected $req;
	protected $res;
	
	public function setup() {
		$this->req = new Request();
		$this->res = new Response();
		$this->handler = new RequestHandler($this->req, $this->res);
	}
	
	public function test_ConnectReturnsMethodNotAllowedStatusByDefault() {
		$this->handler->connect();
		self::assertEquals(Response::MethodNotAllowed, (int) $this->res->getStatus());
	}
	
	public function test_DeleteReturnsMethodNotAllowedStatusByDefault() {
		$this->handler->delete();
		self::assertEquals(Response::MethodNotAllowed, (int) $this->res->getStatus());
	}
	
	public function test_ErrorsClearResponseBodyAndSetResponseStatus() {
		$this->res->write('To be deleted');
		$this->handler->error(999);
		self::assertEquals(999, (int) $this->res->getStatus());
		self::assertEquals(array(), $this->res->getBody());
	}
	
	public function test_ExceptionHandlerSetsResponseStatus() {
		$this->handler->handleException(new Exception());
		self::assertEquals(Response::InternalServerError, (int) $this->res->getStatus());
	}
	
	public function test_ExceptionHandlerOutputsExceptionMessageWhenDebugModeIsEnabled() {
		$message = 'test';
		$this->handler->handleException(new Exception($message), true);
		$body = $this->res->getBody();
		self::assertEquals($message, $body[1]);
	}
	
	public function test_GetReturnsMethodNotAllowedStatusByDefault() {
		$this->handler->get();
		self::assertEquals(Response::MethodNotAllowed, (int) $this->res->getStatus());
	}
	
	public function test_HeadReturnsMethodNotAllowedStatusByDefault() {
		$this->handler->head();
		self::assertEquals(Response::MethodNotAllowed, (int) $this->res->getStatus());
	}
	
	public function test_OptionsReturnsMethodNotAllowedStatusByDefault() {
		$this->handler->options();
		self::assertEquals(Response::MethodNotAllowed, (int) $this->res->getStatus());
	}
	
	public function test_PostReturnsMethodNotAllowedStatusByDefault() {
		$this->handler->post();
		self::assertEquals(Response::MethodNotAllowed, (int) $this->res->getStatus());
	}
	
	public function test_PutReturnsMethodNotAllowedStatusByDefault() {
		$this->handler->put();
		self::assertEquals(Response::MethodNotAllowed, (int) $this->res->getStatus());
	}
	
	public function test_RedirectionClearsResponseBody() {
		$this->handler->redirect('');
		self::assertEquals(array(), $this->res->getBody());
	}
	
	public function test_RedirectionResolvesUriAndSetsLocationHeader() {
		$req = new Request(array('wsgi.uri_scheme' => 'http', 'HTTP_HOST' => 'localhost', 'PATH_INFO' => '/a/b/c'));
		$res = new Response();
		$handler = new RequestHandler($req, $res);
		$handler->redirect('d');
		self::assertEquals('http://localhost/a/b/d', $res->getHeader('Location'));
	}
	
	public function test_RedirectionSetsResponseStatus() {
		$this->handler->redirect('');
		self::assertEquals(Response::SeeOther, (int) $this->res->getStatus());

		$this->handler->redirect('', true);
		self::assertEquals(Response::MovedPermanently, (int) $this->res->getStatus());

		$req = new Request(array('SERVER_PROTOCOL' => 'HTTP/1.0'));
		$res = new Response();
		$handler = new RequestHandler($req, $res);
		$handler->redirect('');
		self::assertEquals(Response::Found, (int) $res->getStatus());
	}
	
	public function test_TraceReturnsMethodNotAllowedStatusByDefault() {
		$this->handler->trace();
		self::assertEquals(Response::MethodNotAllowed, (int) $this->res->getStatus());
	}
}
