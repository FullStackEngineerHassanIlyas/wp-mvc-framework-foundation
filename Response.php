<?php 

namespace PluginName\core;

/**
 * Response Class
 */
class Response {
	
	public function setStatusCode( int $code ) {
		if ( function_exists('status_header') ) {
			status_header( $code );
		} else {
			http_response_code( $code );
		}

	}
}