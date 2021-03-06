<?php
/**
 * LICENSE
 *
 * Copyright 2012 Pablo Ros
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

/**
 * Handles the interaction with the application debug.
 */
class Debug
{
	/**
	 * Array where all the storage is done.
	 *
	 * @var array
	 */
	private static $storage 		= array();

	/**
	 * Debug config configuration.
	 * @var array
	 */
	private static $debug_config 	= array();

	/**
	 * Defines if all debug modules ara availables. That's defined in debug_config.config.php
	 * @var bool
	 */
	private static $all_modules_available = true;

	/**
	 * Avoid external construction of class without singleton usage.
	 *
	 */
	private function __construct()
	{
		self::$debug_config = Config::getInstance()->getConfig( 'debug_config', 'debug' );
		if ( !empty( self::$debug_config ) )
		{
			self::$all_modules_available = false;
		}
	}

	/**
	 * @static
	 * @param $message Puede ser un string, variable, objeto, etc.
	 * @param string $type [log|error|warn] en función de ellos se mostrará de una manera u de otra.
	 * @param string $display [html|browser_console|alert] O se muestra en el debug HTML, en la consola del browser o en un alert JS.
	 * @author Javier Ferrer
	 */
	public static function log( $message, $type = 'log', $display = 'html' )
	{
		$is_object = false;
		if ( $display != 'html')
		{
			if ( is_array( $message ) || is_object( $message ) )
			{
				$is_object 	= true;
				$message 	= "'" . str_replace( "'", "\\'", json_encode( $message ) ) . "'";
			}
			else
			{
				$message = "'" . str_replace( "'", "\\'", $message ) . "'";
			}
		}

		$message_log['type'] 		= $type;
		$message_log['is_object'] 	= $is_object;
		$message_log['message'] 	= $message;

		self::$storage[ 'log_messages' ][ $display ][] = $message_log;
	}

	/**
	 * Adds another element to the end of the array.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return int New number of elements in the array.
	 */
	public static function push( $key, $value )
	{
		if ( false === self::moduleAvailable( $key ) )
		{
			return null;
		}

		if ( !isset( self::$storage[$key] ) )
		{
			self::$storage[$key] = array();
		}

		if ( !is_array( self::$storage[$key] ) )
		{
			throw new \UnexpectedValueException( 'Failed to PUSH an element in the debug because the given key is not an array.' );
		}

		return array_push( self::$storage[$key], $value );
	}

	protected static function moduleAvailable( $key )
	{
		self::$debug_config = Config::getInstance()->getConfig( 'debug_config', 'debug' );
		if ( empty( self::$debug_config ) )
		{
			return true;
		}
		elseif ( isset( self::$debug_config[ $key ] ) && true === self::$debug_config[ $key ] )
		{
			return true;
		}
		return false;
	}

	/**
	 * Stores the object with the name given in $key and $sub_key.
	 *
	 * Example: array( $key => array( $subkey => $value ) )
	 *
	 * @param string $key Name you want to store the value with.
	 * @param mixed $value The object to store in the array.
	 * @return void
	 */
	public static function subSet( $key, $sub_key, $value  )
	{
		self::$storage[$key][$sub_key] = $value;
	}

	/**
	 * @static Push an element of the array.
	 * @param string $key
	 * @return mixed Element in the array or null if not exists.
	 */
	public static function get( $key, $pull = false )
	{
		if ( isset( self::$storage[$key] ) )
		{
			$value = self::$storage[$key];
			if ( true === $pull )
			{
				unset( self::$storage[$key] );
			}
			return $value;
		}

		return null;
	}

	/**
	 * @static Get all information stored in debug.
	 * @return array
	 */
	public static function getDebugInformation()
	{
		return self::$storage;
	}
}