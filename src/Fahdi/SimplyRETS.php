<?php

namespace Fahdi;

class SimplyRETS {
	/**
	 * Magic method for handling API methods.
	 *
	 * @param   string $method
	 * @param   array $args
	 *
	 * @return  array
	 */
	public static function __callStatic( $method, $args ) {
		// capture username/password
		$api_key    = ex( $args[0], 'api_key' );
		$api_secret = ex( $args[0], 'api_secret' );
		unset( $args[0]['api_key'], $args[0]['api_secret'] ); // strip from payload

		// catch error...
		if ( ! $api_key or ! $api_secret ) {
			trigger_error( 'Username and password required.' );
		}

		// detect if requesting specific listing
		$mls_id = ex( $args[0], 'mls_id' );
		unset( $args[0]['mls_id'] ); // strip from payload

		// build uri
		$uri = strtolower( $method ) . ( $mls_id ? '/' . $mls_id : '' );

		// build query
		$query = '';
		foreach ( $args[0] as $key => $value ) {
			// if value is an array...
			if ( is_array( $value ) ) {
				// add multiple times...
				foreach ( $value as $v ) {
					$query .= urlencode( $key ) . '=' . urlencode( $v ) . '&';
				}
			} else {
				// add to query
				$query .= urlencode( $key ) . '=' . urlencode( $value ) . '&';
			}
		}

		// build endpoint
		$endpoint = 'https://api.simplyrets.com/' . $uri . '?' . $query;

		// make headers
		$headers = array(
			'accept:application/json', // will use latest response format
			#'accept:application/vnd.simplyrets-v0.1+json', // will use specific response format (stable)
		);

		// setup curl request
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $endpoint );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_USERPWD, "$api_key:$api_secret" );
		curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $ch, CURLOPT_VERBOSE, 1 );

		// this function is called by curl for each header received
		curl_setopt( $ch, CURLOPT_HEADERFUNCTION,
			function ( $curl, $header ) use ( &$headers ) {
				$len    = strlen( $header );
				$header = explode( ':', $header, 2 );
				if ( count( $header ) < 2 ) // ignore invalid headers
				{
					return $len;
				}

				$name = strtolower( trim( $header[0] ) );
				if ( ! array_key_exists( $name, $headers ) ) {
					$headers[ $name ] = [ trim( $header[1] ) ];
				} else {
					$headers[ $name ][] = trim( $header[1] );
				}

				return $len;
			}
		);

		$response = curl_exec( $ch );
		$code     = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

		// catch error...
		if ( curl_errno( $ch ) ) {
			// report
			#$errors = curl_error($ch);

			// close
			curl_close( $ch );

			// return false
			return false;
		}

		// close
		curl_close( $ch );

		// decode
		$response = json_decode( $response );

		$response['total-count'] = $headers['x-total-count'][0];

		// echo $endpoint;

		// catch error...
		if ( $code !== 200 ) {
			trigger_error( ex( $response, 'message', 'error' ) );
		}

		// return
		return $response;
	}
}
