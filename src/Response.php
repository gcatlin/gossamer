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
 *
 */
class Response {
	// 2xx: Success - The action was successfully received, understood, and accepted
	const OK = 200;
	const Created = 201;
	const Accepted = 202;
	const NonAuthoritativeInformation = 203;
	const NoContent = 204;
	const ResetContent = 205;
	const PartialContent = 206;

	// 3xx: Redirection - Further action must be taken in order to complete the request
	const MultipleChoices = 300;
	const MovedPermanently = 301;
	const Found = 302;
	const SeeOther = 303;
	const NotModified = 304;
	const UseProxy = 305;
	const TemporaryRedirect = 307;

	// 4xx: Client Error - The request contains bad syntax or cannot be fulfilled
	const BadRequest = 400;
	const Unauthorized = 401;
	const PaymentRequired = 402;
	const Forbidden = 403;
	const NotFound = 404;
	const MethodNotAllowed = 405;
	const NotAcceptable = 406;
	const ProxyAuthenticationRequired = 407;
	const RequestTimeout = 408;
	const Conflict = 409;
	const Gone = 410;
	const LengthRequired = 411;
	const PreconditionFailed = 412;
	const RequestEntityTooLarge = 413;
	const RequestURITooLarge = 414;
	const UnsupportedMediaType = 415;
	const RequestedRangeNotSatisfiable = 416;
	const ExpectationFailed = 417;

	// 5xx: Server Error - The server failed to fulfill an apparently valid request
	const InternalServerError = 500;
	const NotImplemented = 501;
	const BadGateway = 502;
	const ServiceUnavailable = 503;
	const GatewayTimeOut = 504;
	const HTTPVersionNotSupported = 505;

	/**
	 * @var array
	 */
	protected static $status_messages = array(
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request URI Too Large',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time Out',
		505 => 'HTTP Version Not Supported',
	);
	
	/**
	 * @var string
	 */
	// @TODO use php://out instead? php://memory? a stream
	protected $body = array();

	/**
	 * @var array
	 */
	protected $headers = array();

	/**
	 * @var string
	 */
	protected $status = '200 OK';
	
	/**
	 * @return string
	 */
	public static function getHttpStatusMessage($code) {
		if (isset(self::$status_messages[$code])) {
			return self::$status_messages[$code];
		}
		return '';
	}
	
	/**
	 * Erases the contents of the output buffer, leaving it empty.
	 */
	public function clear() {
		// @TODO use a stream? truncate and rewind
		// $this->headers = array(); // @TODO clear headers?
		$this->body = array();
	}

	/**
	 *
	 *
	 * @return array
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 *
	 *
	 * @return string
	 */
	// public function getCharacterSet() {
	// }

	/**
	 *
	 * @param string $name
	 * @return string
	 */
	public function getHeader($name) {
		if (isset($this->headers[$name])) {
			return $this->headers[$name];
		}
		return null;
	}

	/**
	 *
	 *
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 *
	 *
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * 
	 *
	 * @param $chunks array
	 */
	public function setBody($chunks) {
		$this->body = (array) $chunks;
	}

	/**
	 * For security reasons, the following HTTP response headers cannot be 
	 * modified by the application. Setting these in the Response object's 
	 * headers object has no effect.
	 *   - Content-Encoding
	 *   - Content-Length
	 *   - Date
	 *   - Server
	 *   - Transfer-Encoding
	 *
	 * @see http://tools.ietf.org/html/rfc2616#section-4.2
	 * @param $name string
	 * @param $value string
	 * @param $append bool
	 */
	public function setHeader($name, $value, $append=false) {
		if ($append && isset($this->headers[$name])) {
			$this->headers[$name] .= ",{$value}";
		} else {
			$this->headers[$name] = $value;
		}
	}
	
	/**
	 * Changes the status code for the response.
	 *
	 * The method takes the numeric status code as its first parameter. An 
	 * optional second parameter specifies a message to use instead of the 
	 * default for the given status code.
	 *
	 * @param $code int
	 * @param $message string
	 */
	public function setStatus($code, $message=null) {
		$code = (int) $code;
		if ($message === null) {
			if (isset(self::$status_messages[$code])) {
				$message = self::$status_messages[$code];
			} else {
				$message = '';
			}
		}
		$this->status = "{$code} {$message}";
	}

	/**
	 * 
	 *
	 * @return array
	 */
	public function toArray() {
		// @TODO chunked transfer?
		// if (is_file($body)) {
		// 	header('Content-Description: File Transfer');
		// 	header('Content-Type: application/octet-stream');
		// 	header('Content-Disposition: attachment; filename=' . basename($body));
		// 	header('Content-Transfer-Encoding: binary');
		// 	header('Expires: 0');
		// 	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		// 	header('Pragma: public');
		// 	header('Content-Length: ' . filesize($body));
		// 	readfile($body);
		// }
		return array($this->status, $this->headers, $this->body);
	}

	/**
	 * 
	 *
	 * @param $str string
	 */
	public function write($chunk) {
		$this->body[] = $chunk;
	}
}
