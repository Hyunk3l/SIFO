<?php
/**
 * LICENSE
 *
 * Copyright 2010 Albert Lombarte
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

namespace Sifo;

// See available exceptions below this class.
class SEO_Exception extends \Exception
{
	/**
	 * HTTP code used for this exception.
	 *
	 * @var integer
	 */
	public $http_code = 302;

	/**
	 * HTTP code explanation.
	 *
	 * @var string
	 */
	public $http_code_msg = '';

	/**
	 * Whether the status code requires a redirection or not.
	 *
	 * @var boolean
	 */
	public $redirect = false;

	// Known HTTP codes by this framework.
	public static $http_codes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => '( Unused )',
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
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	);


	/**
	 * Set the correct status code on Exception invokation.
	 */
	public function __construct( $message = null, $code = 0 )
	{
		// Invoke parent to ensure all available data has been properly assigned:
		parent::__construct( $message, $code );

		$current_exception = get_class( $this );
		$current_exception_code = ( int ) str_replace( __NAMESPACE__ . '\\Exception_', '', $current_exception );

		// See if the http status code needs a redirection:
		if ( ( 300 <= $current_exception_code ) && ( 307 >= $current_exception_code ) )
		{
			$this->redirect = true;
		}

		if ( isset( self::$http_codes[$current_exception_code] ) )
		{
			$this->http_code = $current_exception_code;
			$this->http_code_msg = self::$http_codes[$current_exception_code];
		}
		else
		{
			// The passed exception is not in the list. Pass a 500 error.
			$this->http_code = 500;
			$this->http_code_msg = 'Internal Server Error';
		}

		// Set internal exception vars if they are empty (non declared in constructor).
		// This allows usage of methods as $e->getMessage() or $e->getCode()
		if ( 0 == $this->code )
		{
			$this->code = $this->http_code;
		}

		if ( null === $this->message )
		{
			$this->message = $this->http_code_msg;
		}
	}

	/**
	 * Raises a Sifo exceptions based on the given HTTP status code
	 * @param <type> $message Reason
	 * @param <type> $code HTTP status code
	 */
	public static function raise( $message, $code )
	{
		if ( isset( self::$http_codes[$code] ) )
		{
			$exception = '\Sifo\Exception_' . $code;
			throw new $exception( $message );
		}
		else
		{
			// Unknown status code.
			throw new Exception_500( $message, $code );
		}
	}
}

/**
 * Redirect (Moved permanently).
 */
class Exception_301 extends SEO_Exception{}

/**
 * Found (redirection).
 */
class Exception_302 extends SEO_Exception{}

/**
 * See other.
 */
class Exception_303 extends SEO_Exception{}

/**
 * Not modified headers. TODO, implement Etag.
 */
class Exception_304 extends SEO_Exception{}

/*
 * Temporary redirect
 */
class Exception_307 extends SEO_Exception{}

/**
 * Bad request
 */
class Exception_400 extends SEO_Exception{}

/**
 * Unauthorized Exception.
 */
class Exception_401 extends SEO_Exception{}

/**
 * Forbidden.
 */
class Exception_403 extends SEO_Exception{}

/**
 * Not found.
 */
class Exception_404 extends SEO_Exception{}

/**
 * Method not allowed.
 */
class Exception_405 extends SEO_Exception{}

/**
 * Oooops. Internal server error.
 */
class Exception_500 extends SEO_Exception{}

/**
 * Service unavailable.
 */
class Exception_503 extends SEO_Exception{}

/* Not so common status codes */
class Exception_100 extends SEO_Exception{}
class Exception_101 extends SEO_Exception{}
class Exception_201 extends SEO_Exception{}
class Exception_202 extends SEO_Exception{}
class Exception_203 extends SEO_Exception{}
class Exception_204 extends SEO_Exception{}
class Exception_205 extends SEO_Exception{}
class Exception_206 extends SEO_Exception{}
class Exception_300 extends SEO_Exception{}
class Exception_305 extends SEO_Exception{}
class Exception_402 extends SEO_Exception{}
class Exception_406 extends SEO_Exception{}
class Exception_407 extends SEO_Exception{}
class Exception_408 extends SEO_Exception{}
class Exception_409 extends SEO_Exception{}
class Exception_410 extends SEO_Exception{}
class Exception_411 extends SEO_Exception{}
class Exception_412 extends SEO_Exception{}
class Exception_413 extends SEO_Exception{}
class Exception_414 extends SEO_Exception{}
class Exception_415 extends SEO_Exception{}
class Exception_416 extends SEO_Exception{}
class Exception_417 extends SEO_Exception{}
class Exception_501 extends SEO_Exception{}
class Exception_502 extends SEO_Exception{}
class Exception_504 extends SEO_Exception{}
class Exception_505 extends SEO_Exception{}