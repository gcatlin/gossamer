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
class RequestHandler {
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     *
     *
     * @param $request Request
     * @param $response Response
     */
    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Called to handle an HTTP CONNECT request. Overridden by handler subclasses.
     */
    public function connect() {
        // @TODO return Allow header listing allowed methods
        return $this->error(Response::MethodNotAllowed);
    }

    /**
     * Called to handle an HTTP DELETE request. Overridden by handler subclasses.
     */
    public function delete() {
        // @TODO return Allow header listing allowed methods
        $this->error(Response::MethodNotAllowed);
    }

    /**
     * Called to handle an HTTP GET request. Overridden by handler subclasses.
     */
    public function get() {
        // @TODO return Allow header listing allowed methods
        $this->error(Response::MethodNotAllowed);
    }

    /**
     * A shortcut method for handlers to use to return an error response.
     *
     * It takes a numeric HTTP status code, and prepares the request handler's
     * response to use that status code. It also clears the output buffer, so
     * the handler can prepare successful output then call error(...) later if
     * there is a problem.
     *
     * @param int $status
     */
    public function error($status=Response::InternalServerError) {
        $this->response->clear();
        $this->response->setStatus($status);
    }

    /**
     * Called when an exception is thrown by a handler. By default,
     * handleException() sets an HTTP status code of 500 ("Server error"). If
     * $debug is true it prints a backtrace to the browser. Otherwise it just
     * prints a plain error message. A RequestHandler sub-class can override
     * this method to provide custom behavior.
     *
     * @param Exception $e
     * @param bool $debug
     */
    public function handleException(Exception $e, $debug=false) {
        // @TODO make output content-type agnostic (i.e. not tied to text/html)???
        $this->error(Response::InternalServerError);
        $this->response->write("<h1>Server Error</h1>\n");

        $backtrace = debug_backtrace();
        // $this->log->error($backtrace); // @TODO use wsgi.errors?

        if ($debug) {
            // @TODO prettier and more useful output
            $this->response->write("{$e->getMessage()}");
            // $this->response->write("<pre>" . print_r($backtrace, 1) . "</pre>\n");
        }
    }

    /**
     * Called to handle an HTTP HEAD request. Overridden by handler subclasses.
     */
    public function head() {
        // @TODO return Allow header listing allowed methods
        return $this->error(Response::MethodNotAllowed);
    }

    /**
     * Called to handle an HTTP POST request. Overridden by handler subclasses.
     */
    public function post() {
        // @TODO return Allow header listing allowed methods
        return $this->error(Response::MethodNotAllowed);
    }

    /**
     * Called to handle an HTTP OPTIONS request. Overridden by handler subclasses.
     */
    public function options() {
        // @TODO return Allow header listing allowed methods
        return $this->error(Response::MethodNotAllowed);
    }

    /**
     * Called to handle an HTTP PUT request. Overridden by handler subclasses.
     */
    public function put() {
        // @TODO return Allow header listing allowed methods
        return $this->error(Response::MethodNotAllowed);
    }

    /**
     * A shortcut method for handlers to use to return a redirect response.
     * Sets the HTTP error code and Location: header to redirect to uri, and
     * clears the response output stream. If permanent is true, it uses the HTTP
     * status code 301 for a permanent redirect. Otherwise, it uses the HTTP
     * status code 303 for a temporary redirect for HTTP/1.0 requests or a
     * status code 307 for a temporary redirect for HTTP/1.1 requests.
     *
     * @param string $uri
     * @param bool $permanent
     */
    public function redirect($uri, $permanent=false) {
        if ($permanent) {
            $status = Response::MovedPermanently;
        } elseif ($this->request->getHttpVersion() == 'HTTP/1.0') {
            $status = Response::Found;
        } else {
            $status = Response::SeeOther;
        }

        $this->response->clear();
        $this->response->setStatus($status);

        $location = Uri::resolve($this->request->getScriptUri(), $uri);
        $this->response->setHeader('Location', $location);

        // end execution of RequestHandler?
    }

    /**
     * Called to handle an HTTP TRACE request. Overridden by handler subclasses.
     */
    public function trace() {
        // @TODO return Allow header listing allowed methods
        return $this->error(Response::MethodNotAllowed);
    }
}
