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

if (!defined('PHP_URL_AUTHORITY')) {
    define('PHP_URL_AUTHORITY', 8);
}

/**
 * A class for working with RFC 3986 compliant URIs.
 *
 * @TODO make encoding/decoding really solid
 * @TODO improve query param logic, including normalization
 *
 * @see http://tools.ietf.org/html/rfc3986
 * @see http://docs.python.org/library/urlparse.html
 */
class Uri {
    /**
     *
     */
    const SCHEME    = PHP_URL_SCHEME;
    const AUTHORITY = PHP_URL_AUTHORITY;
    const PATH      = PHP_URL_PATH;
    const QUERY     = PHP_URL_QUERY;
    const FRAGMENT  = PHP_URL_FRAGMENT;
    const HOST      = PHP_URL_HOST;
    const PORT      = PHP_URL_PORT;
    const USER      = PHP_URL_USER;
    const PASS      = PHP_URL_PASS;

    /**
     * An initialized array of default values for parsed URI components.
     *
     * @var array
     */
    protected static $default_components = array(
        'scheme'    => null,
        'authority' => null,
        'path'      => null,
        'query'     => null,
        'fragment'  => null,
        'user'      => null,
        'pass'      => null,
        'host'      => null,
        'port'      => null,
    );

    /**
     * An initialized array of default values for parsed URI components.
     *
     * @var array
     */
    protected static $component_map = array(
        self::SCHEME    => 'scheme',
        self::AUTHORITY => 'authority',
        self::PATH      => 'path',
        self::QUERY     => 'query',
        self::FRAGMENT  => 'fragment',
        self::HOST      => 'host',
        self::PORT      => 'port',
        self::USER      => 'user',
        self::PASS      => 'pass',
    );

    /**
     * The components of this URI.
     *
     * @var array
     */
    protected $components;

    /**
     * Returns a normalized version of the specified URI.
     *
     * @TODO sort query params alphabetically by key
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3
     * @see http://tools.ietf.org/html/rfc3986#section-6
     * @see http://en.wikipedia.org/wiki/URL_canonicalization
     * @see http://www2007.org/papers/paper194.pdf
     *
     * @param  mixed $uri
     * @return mixed
     */
    public static function normalize($uri) {
        $parsed_uri = self::parse($uri);
        // if (is_array($uri)) {
        // 	$parsed_uri = $uri + self::$default_components;
        // 	$return_parsed = true;
        // } else {
        // 	$parsed_uri = self::parse($uri);
        // 	$return_parsed = false;
        // }

        $parsed_uri['scheme'] = strtolower($parsed_uri['scheme']);
        $parsed_uri['host'] = strtolower($parsed_uri['host']);
        $parsed_uri['authority'] = self::authority($parsed_uri);
        $parsed_uri['path'] = self::normalizePath($parsed_uri['path']);

        // if ($return_parsed) {
        // 	return $parsed_uri;
        // }
        return self::unparse($parsed_uri);
    }

    /**
     * Returns a path with all dot segments ('.' and '..') interpretted and
     * removed.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.3
     * @see http://tools.ietf.org/html/rfc3986#section-5.2.4
     * @see http://en.wikipedia.org/wiki/URL_canonicalization
     * @see http://www2007.org/papers/paper194.pdf
     *
     * @param  string $path
     * @return string
     */
    public static function normalizePath($path) {
        if ($path == '/' || !isset($path[0])) {
            return $path;
        }

        $input_segments = explode('/', $path);
        $output_segments = false;
        if ($input_segments) {
            $output_segments = array();
            foreach ($input_segments as $segment) {
                if ($segment != '' && $segment != '.') {
                    if ($segment == '..') {
                        array_pop($output_segments);
                    } else {
                        $output_segments[] = $segment;
                    }
                }
            }
        }

        $normalized = '';
        if ($path[0] == '/') {
            $normalized = '/';
        }

        if ($output_segments) {
            $normalized .= implode('/', $output_segments);
            if ($segment == '.' || $segment == '..' || $segment == '') {
                $normalized .= '/';
            }
        }

        return $normalized;
    }

    /**
     * Parses the supplied URI into its individual components and returns them
     * as an associative array. If $component is supplied, a string with the
     * specified component is returned, or null if invalid.
     *
     * @see http://tools.ietf.org/html/rfc3986#appendix-B
     *
     * @param  string  $uri
     * @param  integer $component optional
     * @return array
     */
    public static function parse($uri, $component=-1) {
        $uri = (string) $uri;

        // Compensate for limited behavior of PHP's parse_url function
        $reset = false;
        if (isset($uri[1]) && $uri[0] == '/' && $uri[1] == '/') { # //*
            if ($uri[2] == '/') { # ///*
                $uri = 'scheme://host/' . substr($uri, 3);
                $reset = array('scheme', 'host');
            } elseif (!isset($uri[2])) { # //
                $uri = 'scheme://host';
                $reset = array('scheme', 'host');
            } else { # //*
                $uri = 'scheme:' . $uri;
                $reset = array('scheme');
            }
        } else {
            $parts = explode(':', $uri, 2);
            // if (!isset($parts[0][0])) { # :*  prepend scheme?
            // 	$uri = 'scheme' . $uri;
            // 	$rest = array('scheme');
            // }
            if (isset($parts[1]) && $parts[1][0] == '/' && $parts[1][1] == '/') { # *://*
                if (!isset($parts[1][2])) { # *://
                    $uri .= 'host';
                    $reset = array('host');
                } elseif ($parts[1][2] == '/') { # *:///*
                    $path = explode('///', $parts[1], 2);
                    $uri = $parts[0] . '://host/' . $path[1];
                    $reset = array('host');
                }
            }
        }

        $parsed_uri = parse_url((string) $uri);

        // Normalize
        if (isset($parsed_uri['port'])) {
            $parsed_uri['port'] = (string) $parsed_uri['port'];
        }
        if (isset($parsed_uri['path']) && $parsed_uri['path'] === '') {
            $parsed_uri['path'] = null;
        }

        // Clean up parse_url hacks
        if ($reset) {
            foreach ($reset as $_component) {
                $parsed_uri[$_component] = null;
            }
        }

        $parsed_uri = array_merge(self::$default_components, $parsed_uri);
        $parsed_uri['authority'] = self::authority($parsed_uri);

        if ($component == -1) {
            return $parsed_uri;
        } elseif (isset($parsed_uri[self::$component_map[$component]])) {
            return $parsed_uri[self::$component_map[$component]];
            // } elseif ($component == 'PHP_URL_AUTHORITY') {
            // 	return $parsed_uri['authority'];
        }
        return null;
    }

    /**
     * Resolves $relative_uri against $base_uri.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-5.2.2
     * @see http://tools.ietf.org/html/rfc3986#section-5.4
     *
     * @param  string $base_uri
     * @param  string $relative_uri
     * @return string
     */
    public static function resolve($base_uri, $relative_uri) {
        if (!isset($relative_uri[0])) {
            return $base_uri;
        }

        $base_uri = self::parse($base_uri);
        $relative_uri = self::parse($relative_uri);
        $target_uri = self::$default_components;

        if (isset($relative_uri['scheme'][0])) {
            $target_uri['scheme'] = $relative_uri['scheme'];
            $target_uri['authority'] = $relative_uri['authority'];
            $target_uri['path'] = self::normalizePath($relative_uri['path']);
            $target_uri['query'] = $relative_uri['query'];
        } else {
            $target_uri['scheme'] = $base_uri['scheme'];
            if (isset($relative_uri['authority'][0])) {
                $target_uri['authority'] = $relative_uri['authority'];
                $target_uri['path'] = self::normalizePath($relative_uri['path']);
                $target_uri['query'] = $relative_uri['query'];
            } else {
                $target_uri['authority'] = $base_uri['authority'];
                if (!isset($relative_uri['path'][0])) {
                    $target_uri['path'] = $base_uri['path'];
                    if (isset($relative_uri['query'][0])) {
                        $target_uri['query'] = $relative_uri['query'];
                    } else {
                        $target_uri['query'] = $base_uri['query'];
                    }
                } else {
                    if ($relative_uri['path'][0] == '/') {
                        $target_uri['path'] = self::normalizePath($relative_uri['path']);
                    } else {
                        // Merge Paths
                        if (isset($base_uri['authority'][0]) && !isset($base_uri['path'][0])) {
                            $target_uri['path'] = '/' . $relative_uri['path'];
                        } elseif (isset($base_uri['path'][0])) {
                            $target_uri['path'] = dirname($base_uri['path']) . '/' . $relative_uri['path'];
                        } else {
                            $target_uri['path'] = $relative_uri['path'];
                        }
                        $target_uri['path'] = self::normalizePath($target_uri['path']);
                    }
                    $target_uri['query'] = $relative_uri['query'];
                }
            }
        }
        $target_uri['fragment'] = $relative_uri['fragment'];

        return self::unparse($target_uri);
    }

    /**
     * Recompose a URI from its components and return a URI string.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-5.3
     *
     * @param  array $parsed_uri
     * @return string
     */
    public static function unparse($parsed_uri) {
        $parsed_uri = (array) $parsed_uri + self::$default_components;

        if (!isset($parsed_uri['authority'])) {
            $parsed_uri['authority'] = self::authority($parsed_uri);
        }

        $uri = '';

        if ($parsed_uri['scheme'] !== null) {
            $uri .= $parsed_uri['scheme'] . ':';
        }

        if ($parsed_uri['authority'] !== null) {
            $uri .= '//' . $parsed_uri['authority'];

            // http://tools.ietf.org/html/rfc3986#section-3.2
            if (isset($parsed_uri['path'][0]) && $parsed_uri['path'][0] != '/') {
                $uri .= '/';
            }
        }

        $uri .= $parsed_uri['path'];

        if ($parsed_uri['query'] !== null) {
            $uri .= '?' . $parsed_uri['query'];
        }

        if ($parsed_uri['fragment'] !== null) {
            $uri .= '#' . $parsed_uri['fragment'];
        }

        return $uri;
    }

    /**
     * Creates a new Uri object, based on the supplied string, array, or Uri
     * instance.
     *
     * @TODO URI validation
     *
     * @param mixed $uri string, array, or Uri object
     */
    public function __construct($uri=null) {
        if ($uri instanceof Uri) {
            $this->components = $uri->components;
        } else {
            if ($uri === (array) $uri) {
                $this->components = array_merge(self::$default_components, $uri);
                if (!isset($uri['authority'])) {
                    $this->components['authority'] = self::authority($uri);
                }
            } else {
                $this->components = self::parse($uri);
            }
        }
    }

    /**
     * Returns a string representation of this URI.
     *
     * @return string
     */
    public function __toString() {
        return self::unparse($this->components);
    }

    /**
     * Returns the authority component of this URI.
     *
     * @return string
     */
    public function getAuthority() {
        return $this->components['authority'];
    }

    /**
     * Returns the fragment component of this URI.
     *
     * @return string
     */
    public function getFragment() {
        return $this->components['fragment'];
    }

    /**
     * Returns the host component of this URI.
     *
     * @return string
     */
    public function getHost() {
        return $this->components['host'];
    }

    /**
     * Returns the password component of this URI.
     *
     * @return string
     */
    public function getPassword() {
        return $this->components['pass'];
    }

    /**
     * Returns the path component of this URI.
     *
     * @return string
     */
    public function getPath() {
        return $this->components['path'];
    }

    /**
     * Returns the port component of this URI.
     *
     * @return string
     */
    public function getPort() {
        return $this->components['port'];
    }

    /**
     * Returns the query component of this URI.
     *
     * @return string
     */
    public function getQuery() {
        return $this->components['query'];
    }

    /**
     * Returns the scheme component of this URI.
     *
     * @return string
     */
    public function getScheme() {
        return $this->components['scheme'];
    }

    /**
     * Returns the user component of this URI.
     *
     * @return string
     */
    public function getUser() {
        return $this->components['user'];
    }

    /**
     * Determines the authority component using the supplied parsed URI
     * components and returns as a string.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2
     *
     * @param  array $parsed_uri
     * @return string
     */
    protected static function authority($parsed_uri) {
        $authority = null;

        if (isset($parsed_uri['user'][0])) {
            if (isset($parsed_uri['pass'][0])) {
                $authority = $parsed_uri['user'] . ':' . $parsed_uri['pass'] . '@';
            } else {
                $authority = $parsed_uri['user'] . '@';
            }
        }

        if ($parsed_uri['host'] !== null) {
            $authority .= $parsed_uri['host'];
            if (isset($parsed_uri['port'][0])) {
                $authority .= ':' . $parsed_uri['port'];
            }
        }

        return $authority;
    }
}
