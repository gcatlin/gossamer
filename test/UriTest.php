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

class UriTest extends \PHPUnit_Framework_TestCase {
	public function test_ConstructorAcceptsParsedUriArray() {
		$uri_str = 'foo://username:password@example.com:8042/over/there/index.dtb;type=animal?name=ferret#nose';
		$uri_arr = Uri::parse($uri_str);
		$uri_obj = new Uri($uri_arr);
		self::assertEquals($uri_str, (string) $uri_obj);

		self::assertEquals('/', (string) new Uri(array('path' => '/')));
	}

	public function test_ConstructorAcceptsUriObject() {
		$uri_str = 'foo://username:password@example.com:8042/over/there/index.dtb;type=animal?name=ferret#nose';
		$uri_obj = new Uri($uri_str);
		self::assertEquals($uri_str, (string) new Uri($uri_obj));
	}

	/**
	 * @dataProvider dataProvider_ParsedUrisAreRecomposed
	 */
	public function test_ParsedUrisAreRecomposed($uri, $components) {
		$components = array(
			'scheme' => $components[0],
			'authority' => $components[1],
			'path' => $components[2],
			'query' => $components[3],
			'fragment' => $components[4],
			'user' => $components[5],
			'pass' => $components[6],
			'host' => $components[7],
			'port' => $components[8],
		);
		self::assertSame($uri, Uri::unparse($components));
	}

	public function dataProvider_ParsedUrisAreRecomposed() {
		return array(
			array('', array(null, null, null, null, null, null, null, null, null)),
			array('/', array(null, null, '/', null, null, null, null, null, null)),
			array('//', array(null, null, '//', null, null, null, null, null, null)),
			array('///', array(null, null, '///', null, null, null, null, null, null)),
			array('.', array(null, null, '.', null, null, null, null, null, null)),
			array('..', array(null, null, '..', null, null, null, null, null, null)),
			array('?', array(null, null, '?', null, null, null, null, null, null)),
			array('#', array(null, null, '#', null, null, null, null, null, null)),
			array('a:', array('a', null, null, null, null, null, null, null, null)),
			array('a:/', array('a', null, '/', null, null, null, null, null, null)),
			array('a://', array('a', null, '//', null, null, null, null, null, null)),
			array('a:///', array('a', '', '/', null, null, null, null, null, null)),
			array('a', array(null, null, 'a', null, null, null, null, null, null)),
			array('/a', array(null, null, '/a', null, null, null, null, null, null)),
			array('//a', array(null, 'a', null, null, null, null, null, null, null)),
			array('//a', array(null, null, '//a', null, null, null, null, null, null)),
			array('///a', array(null, null, '///a', null, null, null, null, null, null)),
			array('///a', array(null, '', '/a', null, null, null, null, null, null)),
			array(':a', array(null, null, ':a', null, null, null, null, null, null)), // it this valid?
			array('//a/b', array(null, 'a', 'b', null, null, null, null, null, null)),
			array('//a@b', array(null, 'a@b', null, null, null, null, null, null, null)),

			array('http://255.255.255.255/', array('http', '255.255.255.255', '/', null, null, null, null, '255.255.255.255', null)),
			array('foo://example.com:8042/over/there?name=ferret#nose', array('foo', 'example.com:8042', '/over/there', 'name=ferret', 'nose', null, null, 'example.com', '8042')),
			array('foo://username:password@[2001:4860:0:2001::68]:8042/over/there/index.dtb;type=animal?name=ferret#nose', array('foo', 'username:password@[2001:4860:0:2001::68]:8042', '/over/there/index.dtb;type=animal', 'name=ferret', 'nose', 'username', 'password', '[2001:4860:0:2001::68]', '8042')),
			array('urn:example:animal:ferret:nose', array('urn', null, 'example:animal:ferret:nose', null, null, null, null, null, null)),
			array('file:///etc/hosts', array('file', '', '/etc/hosts', null, null, null, null, null, null)),
			array('ldap://[2001:db8::7]/c=GB?objectClass?one', array('ldap', '[2001:db8::7]', '/c=GB', 'objectClass?one', null, null, null, '[2001:db8::7]', null)),
			array('mailto:John.Doe@example.com', array('mailto', null, 'John.Doe@example.com', null, null, null, null, null, null)),
			array('news:comp.infosystems.www.servers.unix', array('news', null, 'comp.infosystems.www.servers.unix', null, null, null, null, null, null)),
			array('tel:+1-816-555-1212', array('tel', null, '+1-816-555-1212', null, null, null, null, null, null)),
			array('telnet://192.0.2.16:80/', array('telnet', '192.0.2.16:80', '/', null, null, null, null, '192.0.2.16', '80')),
			array('urn:oasis:names:specification:docbook:dtd:xml:4.1.2', array('urn', null, 'oasis:names:specification:docbook:dtd:xml:4.1.2', null, null, null, null, null, null)),
		);
	}

	/**
	 * @dataProvider dataProvider_ParseReturnsSpecifiedComponent
	 */
	public function test_ParseReturnsSpecifiedComponent($component, $value) {
		$uri = 'foo://username:password@example.com:8042/over/there?name=ferret#nose';
		self::assertEquals($value, Uri::parse($uri, $component));
	}
	
	public function dataProvider_ParseReturnsSpecifiedComponent() {
		return array(
			array('INVALID', null),
			array(Uri::Scheme, 'foo'),
			array(Uri::Authority, 'username:password@example.com:8042'),
			array(Uri::Path, '/over/there'),
			array(Uri::Query, 'name=ferret'),
			array(Uri::Fragment, 'nose'),
			array(Uri::Host, 'example.com'),
			array(Uri::Port, '8042'),
			array(Uri::User, 'username'),
			array(Uri::Pass, 'password'),
		);
	}

	/**
	 * @dataProvider dataProvider_PathsNormalizedPerRfc3986
	 */
	public function test_PathsNormalizedPerRfc3986($path, $normalized_path) {
		self::assertEquals($normalized_path, Uri::normalizePath($path));
	}

	public function dataProvider_PathsNormalizedPerRfc3986() {
		return array(
			array('', ''),
			array('.', ''),
			array('..', ''),
			array('./a', 'a'),
			array('../a', 'a'),
			array('a/..', ''),
			array('/', '/'),
			array('//', '/'),
			array('///', '/'),
		);
	}

	/**
	 * @dataProvider dataProvider_UrisNormalizedPerRfc3986
	 */
	public function test_UrisNormalizedPerRfc3986($uri, $normalized_uri) {
		self::assertEquals($normalized_uri, Uri::normalize($uri));
	}

	public function dataProvider_UrisNormalizedPerRfc3986() {
		return array(
			array('HTTP://www.Example.com/', 'http://www.example.com/'), // Convert scheme and host to lower case
			array('http://www.example.com/../a/b/../c/./d.html', 'http://www.example.com/a/c/d.html'), // Remove dot-segments
			array('http://www.example.com/foo//bar.html', 'http://www.example.com/foo/bar.html'), // Remove duplicate slashes
			// array('http://www.example.com/a%c2%b1b', 'http://www.example.com/a%C2%B1b'), // Capitalize letters in escape sequences
			// array('example://a/b/%63', 'example://a/b/c'), // Decode allowed entities
		);
	}

	public function test_UriPropertiesAreAccessible() {
		$uri = new Uri('foo://username:password@example.com:8042/over/there/index.dtb;type=animal?name=ferret#nose');
		self::assertEquals('foo', $uri->getScheme());
		self::assertEquals('username:password@example.com:8042', $uri->getAuthority());
		self::assertEquals('/over/there/index.dtb;type=animal', $uri->getPath());
		self::assertEquals('name=ferret', $uri->getQuery());
		self::assertEquals('nose', $uri->getFragment());
		self::assertEquals('username', $uri->getUser());
		self::assertEquals('password', $uri->getPassword());
		self::assertEquals('example.com', $uri->getHost());
		self::assertEquals('8042', $uri->getPort());

	}

	/**
	 * @dataProvider dataProvider_UrisParsedPerRfc3986
	 */
	public function test_UrisParsedPerRfc3986($uri, $components) {
		$components = array(
			'scheme' => $components[0],
			'authority' => $components[1],
			'path' => $components[2],
			'query' => $components[3],
			'fragment' => $components[4],
			'user' => $components[5],
			'pass' => $components[6],
			'host' => $components[7],
			'port' => $components[8],
		);
		self::assertSame($components, Uri::parse($uri));
	}

	public function dataProvider_UrisParsedPerRfc3986() {
		return array(
			array('', array(null, null, null, null, null, null, null, null, null)),
			array('/', array(null, null, '/', null, null, null, null, null, null)),
			array('//', array(null, null, null, null, null, null, null, null, null)),
			array('///', array(null, null, '/', null, null, null, null, null, null)),
			array('.', array(null, null, '.', null, null, null, null, null, null)),
			array('..', array(null, null, '..', null, null, null, null, null, null)),
			array('?', array(null, null, null, null, null, null, null, null, null)),
			array('#', array(null, null, null, null, null, null, null, null, null)),
			array('a:', array('a', null, null, null, null, null, null, null, null)),
			array('a:/', array('a', null, '/', null, null, null, null, null, null)),
			array('a://', array('a', null, null, null, null, null, null, null, null)),
			array('a:///', array('a', null, '/', null, null, null, null, null, null)),
			array('a', array(null, null, 'a', null, null, null, null, null, null)),
			array('/a', array(null, null, '/a', null, null, null, null, null, null)),
			array('//a', array(null, 'a', null, null, null, null, null, 'a', null)),
			array('///a', array(null, null, '/a', null, null, null, null, null, null)),
			array(':a', array(null, null, ':a', null, null, null, null, null, null)), // it this valid?
			array('//a/b', array(null, 'a', '/b', null, null, null, null, 'a', null)),
			array('//a@b', array(null, 'a@b', null, null, null, 'a', null, 'b', null)),

			array('http://255.255.255.255/', array('http', '255.255.255.255', '/', null, null, null, null, '255.255.255.255', null)),
			array('foo://example.com:8042/over/there?name=ferret#nose', array('foo', 'example.com:8042', '/over/there', 'name=ferret', 'nose', null, null, 'example.com', '8042')),
			array('foo://username:password@[2001:4860:0:2001::68]:8042/over/there/index.dtb;type=animal?name=ferret#nose', array('foo', 'username:password@[2001:4860:0:2001::68]:8042', '/over/there/index.dtb;type=animal', 'name=ferret', 'nose', 'username', 'password', '[2001:4860:0:2001::68]', '8042')),
			array('urn:example:animal:ferret:nose', array('urn', null, 'example:animal:ferret:nose', null, null, null, null, null, null)),
			array('file:///etc/hosts', array('file', null, '/etc/hosts', null, null, null, null, null, null)),
			array('ldap://[2001:db8::7]/c=GB?objectClass?one', array('ldap', '[2001:db8::7]', '/c=GB', 'objectClass?one', null, null, null, '[2001:db8::7]', null)),
			array('mailto:John.Doe@example.com', array('mailto', null, 'John.Doe@example.com', null, null, null, null, null, null)),
			array('news:comp.infosystems.www.servers.unix', array('news', null, 'comp.infosystems.www.servers.unix', null, null, null, null, null, null)),
			array('tel:+1-816-555-1212', array('tel', null, '+1-816-555-1212', null, null, null, null, null, null)),
			array('telnet://192.0.2.16:80/', array('telnet', '192.0.2.16:80', '/', null, null, null, null, '192.0.2.16', '80')),
			array('urn:oasis:names:specification:docbook:dtd:xml:4.1.2', array('urn', null, 'oasis:names:specification:docbook:dtd:xml:4.1.2', null, null, null, null, null, null)),
		);
	}

	/**
	 * @dataProvider dataProvider_UrisResolvedPerRfc3986
	 */
	public function test_UrisResolvedPerRfc3986($base_uri, $relative_uri, $expected_uri) {
		self::assertEquals($expected_uri, Uri::resolve($base_uri, $relative_uri));
	}

	public function dataProvider_UrisResolvedPerRfc3986() {
		return array(
			array('http://a/b/c/d;p?q', 'g:h', 'g:h'),
			array('http://a/b/c/d;p?q', 'g', 'http://a/b/c/g'),
			array('http://a/b/c/d;p?q', './g', 'http://a/b/c/g'),
			array('http://a/b/c/d;p?q', 'g/', 'http://a/b/c/g/'),
			array('http://a/b/c/d;p?q', '/g', 'http://a/g'),
			array('http://a/b/c/d;p?q', '//g', 'http://g'),
			array('http://a/b/c/d;p?q', '?y', 'http://a/b/c/d;p?y'),
			array('http://a/b/c/d;p?q', 'g?y', 'http://a/b/c/g?y'),
			array('http://a/b/c/d;p?q', '#s', 'http://a/b/c/d;p?q#s'),
			array('http://a/b/c/d;p?q', 'g#s', 'http://a/b/c/g#s'),
			array('http://a/b/c/d;p?q', 'g?y#s', 'http://a/b/c/g?y#s'),
			array('http://a/b/c/d;p?q', ';x', 'http://a/b/c/;x'),
			array('http://a/b/c/d;p?q', 'g;x', 'http://a/b/c/g;x'),
			array('http://a/b/c/d;p?q', 'g;x?y#s', 'http://a/b/c/g;x?y#s'),
			array('http://a/b/c/d;p?q', '', 'http://a/b/c/d;p?q'),
			array('http://a/b/c/d;p?q', '.', 'http://a/b/c/'),
			array('http://a/b/c/d;p?q', './', 'http://a/b/c/'),
			array('http://a/b/c/d;p?q', '..', 'http://a/b/'),
			array('http://a/b/c/d;p?q', '../', 'http://a/b/'),
			array('http://a/b/c/d;p?q', '../g', 'http://a/b/g'),
			array('http://a/b/c/d;p?q', '../..', 'http://a/'),
			array('http://a/b/c/d;p?q', '../../', 'http://a/'),
			array('http://a/b/c/d;p?q', '../../g', 'http://a/g'),
			array('http://a/b/c/d;p?q', '../../../g', 'http://a/g'),
			array('http://a/b/c/d;p?q', '../../../../g', 'http://a/g'),
			array('http://a/b/c/d;p?q', '/./g', 'http://a/g'),
			array('http://a/b/c/d;p?q', '/../g', 'http://a/g'),
			array('http://a/b/c/d;p?q', 'g.', 'http://a/b/c/g.'),
			array('http://a/b/c/d;p?q', '.g', 'http://a/b/c/.g'),
			array('http://a/b/c/d;p?q', 'g..', 'http://a/b/c/g..'),
			array('http://a/b/c/d;p?q', '..g', 'http://a/b/c/..g'),
			array('http://a/b/c/d;p?q', './../g', 'http://a/b/g'),
			array('http://a/b/c/d;p?q', './g/.', 'http://a/b/c/g/'),
			array('http://a/b/c/d;p?q', 'g/./h', 'http://a/b/c/g/h'),
			array('http://a/b/c/d;p?q', 'g/../h', 'http://a/b/c/h'),
			array('http://a/b/c/d;p?q', 'g;x=1/./y', 'http://a/b/c/g;x=1/y'),
			array('http://a/b/c/d;p?q', 'g;x=1/../y', 'http://a/b/c/y'),
			array('http://a/b/c/d;p?q', 'g?y/./x', 'http://a/b/c/g?y/./x'),
			array('http://a/b/c/d;p?q', 'g?y/../x', 'http://a/b/c/g?y/../x'),
			array('http://a/b/c/d;p?q', 'g#s/./x', 'http://a/b/c/g#s/./x'),
			array('http://a/b/c/d;p?q', 'g#s/../x', 'http://a/b/c/g#s/../x'),
			array('http://a/b/c/d;p?q', 'http:g', 'http:g'),

			array('http://a/b/c/d;p?q', '//g/', 'http://g/'),
			array('http://a/b/c/d;p?q', '///g', 'http://a/g'),
			array('http://a/b/c/d;p?q', 'g:', 'g:'),
			array('http://a/b/c/d;p?q', 'g:/h', 'g:/h'),
			array('http://a/b/c/d;p?q', 'g://h', 'g://h'),
			array('http://a/b/c/d;p?q', 'g:///h', 'g:/h'),
			array('http://a/b/c/d;p?q', '/:g', 'http://a/:g'),

			array('g:', '//', 'g:'),
			array('g:', '///', 'g:/'),
			array('g:/', 'h', 'g:/h'),
			array('g:/a', 'h', 'g:/h'),
			array('g://', 'h', 'g:h'),
			array('g://a', 'h', 'g://a/h'),
			array('g:///', 'h', 'g:/h'),
			array('g:///a', 'h', 'g:/h'),
		);
	}

	public function test_UrisUnparsedInStringContext() {
		$uri_str = 'foo://username:password@example.com:8042/over/there/index.dtb;type=animal?name=ferret#nose';
		$uri_obj = new Uri($uri_str);
		self::assertEquals($uri_str, (string) $uri_obj);
	}
}
