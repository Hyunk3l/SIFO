<?php

/**
 * LICENSE
 *
 * Copyright 2010 Albert Garcia
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
 * Class for extracting info from application client: IP, origin country, region & city, browser (version, capabilities, preferences...), SO.
 */
class Client
{

	static private $instance;

	/**
	 * Filter server.
	 *
	 * @var FilterServer
	 */
	static private $server;

	/**
	 * Singleton of Client class.
	 *
	 * @param string $instance_name Instance Name, needed to determine correct paths.
	 *
	 * @return object Client
	 */
	public static function getInstance()
	{
		if ( !isset( self::$instance ) )
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct()
	{

	}

	/**
	 * Get 2 letter country code from client IP.
	 *
	 * @static
	 *
	 * @param null $ip Associated IP or leave null for current user's IP.
	 *
	 * @return bool|mixed
	 */
	public static function getCountryCode( $ip = null )
	{
		$registry_key = 'Client_CountryCode_' . $ip;

		if ( Registry::KeyExists( $registry_key ) )
		{
			$country_code = Registry::get( $registry_key );
		}
		else
		{
			if ( null == $ip )
			{
				$ip = self::getIP();
			}

			require_once ROOT_PATH . '/libs/GeoIP-Lite/geoip.php';
			$gi           = geoip_open( ROOT_PATH . '/libs/GeoIP-Lite/GeoIP.dat', GEOIP_MEMORY_CACHE );
			$country_code = geoip_country_code_by_addr( $gi, $ip );
			geoip_close( $gi );
			Registry::set( $registry_key, $country_code );
		}

		return $country_code;
	}

	/**
	 * Get country name from client IP.
	 *
	 * @static
	 *
	 * @param null $ip Associated IP or leave null for current user's IP.
	 *
	 * @return bool|mixed
	 */
	public static function getCountryName( $ip = null )
	{
		$registry_key = 'Client_CountryName_' . $ip;

		if ( Registry::KeyExists( $registry_key ) )
		{
			$country_name = Registry::get( $registry_key );
		}
		else
		{
			if ( null == $ip )
			{
				$ip = self::getIP();
			}

			require_once ROOT_PATH . '/libs/GeoIP-Lite/geoip.php';
			$gi           = geoip_open( ROOT_PATH . '/libs/GeoIP-Lite/GeoIP.dat', GEOIP_MEMORY_CACHE );
			$country_name = geoip_country_name_by_addr( $gi, $ip );
			geoip_close( $gi );
			Registry::set( $registry_key, $country_name );
		}

		return $country_name;
	}

	/**
	 * Get browser information.
	 */
	public static function getBrowser( $useragent = null, $return_array = false )
	{
		if ( Registry::keyExists( 'Client_Browser' ) )
		{
			$browser = Registry::get( 'Client_Browser' );
		}
		else
		{
			require_once ROOT_PATH . '/libs/Browscap/Browscap.php';
			$bc      = new \phpbrowscap\Browscap( ROOT_PATH . '/libs/Browscap/' );
			$browser = $bc->getBrowser( $useragent, $return_array );
			Registry::set( 'Client_Browser', $browser );
		}

		return $browser;
	}

	/**
	 * Get browser default language.
	 */
	public static function getBrowserLanguage()
	{
		$server = FilterServer::getInstance();

		if ( $lang = $server->getString( 'HTTP_ACCEPT_LANGUAGE' ) )
		{
			return self::parseDefaultLanguage( $lang );
		}
		else
		{
			return self::parseDefaultLanguage( NULL );
		}
	}

	private static function parseDefaultLanguage( $http_accept, $deflang = "es-es" )
	{
		if ( isset( $http_accept ) && strlen( $http_accept ) > 1 )
		{
			# Split possible languages into array
			$x = explode( ",", $http_accept );
			foreach ( $x as $val )
			{
				#check for q-value and create associative array. No q-value means 1 by rule
				if ( preg_match( "/(.*);q=([0-1]{0,1}\.\d{0,4})/i", $val, $matches ) )
				{
					$lang[$matches[1]] = ( float )$matches[2];
				}
				else
				{
					$lang[$val] = 1.0;
				}
			}

			#return default language (highest q-value)
			$qval = 0.0;
			foreach ( $lang as $key => $value )
			{
				if ( $value > $qval )
				{
					$qval    = ( float )$value;
					$deflang = $key;
				}
			}
		}
		return strtolower( $deflang );
	}

	/**
	 * Get real client IP.
	 */
	public static function getIP()
	{
		$client_ip = "";

		$server = FilterServer::getInstance();

		if ( $server->getIp( "HTTP_X_FORWARDED_FOR" ) )
		{
			$ip = $server->getIp( "HTTP_X_FORWARDED_FOR" );
		}
		elseif ( $server->getIp( "HTTP_CLIENT_IP" ) )
		{
			$ip = $server->getIp( "HTTP_CLIENT_IP" );
		}
		else
		{
			$ip = $server->getIp( "REMOTE_ADDR" );
		}

		// From http://www.eslomas.com/index.php/archives/2005/04/26/obtencion-ip-real-php/
		$entries = preg_split( '/[,\s]/', $ip );

		reset( $entries );

		while ( list ( , $entry ) = each( $entries ) )
		{
			$entry = trim( $entry );

			if ( preg_match( "/^([0-9]+.[0-9]+.[0-9]+.[0-9]+)/", $entry, $ip_list ) )
			{
				// http://www.faqs.org/rfcs/rfc1918.html
				$private_ip = array(
					'/^0./',
					'/^127.0.0.1/',
					'/^192.168..*/',
					'/^172.((1[6-9])|(2[0-9])|(3[0-1]))..*/',
					'/^10..*/'
				);

				$found_ip = preg_replace( $private_ip, $client_ip, $ip_list[1] );

				if ( $client_ip != $found_ip )
				{
					$ip = $found_ip;
					break;
				}
			}
		}

		return trim( $ip );
	}

	/**
	 * Determines if actual client is a Mobile Device
	 * based on USERAGENTS contained in Browscap library.
	 */
	public static function isMobile()
	{
		if ( Registry::keyExists( 'Client_isMobile' ) )
		{
			return Registry::get( 'Client_isMobile' );
		}
		else
		{
			$answer = false;
			$useragent = FilterServer::getInstance()->getString( 'HTTP_USER_AGENT' );

			// Regular expression from http://detectmobilebrowsers.com/
			// Updated on 20th June 2012
			if ( preg_match( '/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent ) || preg_match( '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr( $useragent, 0, 4 ) ) )
			{
				$answer = true;
			}

			Registry::set( 'Client_isMobile', $answer );

			return $answer;
		}
	}

	/**
	 * Determines if actual client is a Crawler
	 * based on USERAGENTS contained in Browscap library.
	 */
	public static function isCrawler()
	{
		if ( Registry::keyExists( 'Client_isCrawler' ) )
		{
			return Registry::get( 'Client_isCrawler' );
		}
		else
		{
			$browser_info = self::getBrowser();
			$answer       = true;
			if ( empty( $browser_info->Crawler ) )
			{
				$answer = false;
			}

			Registry::set( 'Client_isCrawler', $answer );

			return $answer;
		}
	}

	/**
	 * Returns true if an IP belongs to a private range.
	 *
	 * @static
	 * @param $ip IP you want to check or null for current user's IP.
	 * @return bool
	 */
	public static function isPrivateIP( $ip = null )
	{
		if ( null == $ip )
		{
			$ip = self::getIP();
		}

		$private_ip_patterns = array(
			'/^192\.168\..*/',
			'/^172\.((1[6-9])|(2[0-9])|(3[0-1]))\..*/',
			'/^10\..*/',
			'/^0\./',
			'/^127\.0\.0\.1/'
		);

		foreach ( $private_ip_patterns as $pattern )
		{
			if ( preg_match( $pattern, $ip ) )
			{
				return true;
			}
		}

		return false;
	}
}