<?php 

namespace WpMvcFramework\Foundation;

use Illuminate\Routing\Router as RoutingRouter;
use Illuminate\Routing\Pipeline;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Router Class
 *
 * @package app\core
 */
class Router extends RoutingRouter {

	public Request $request;
	public Response $response;
	protected array $route_rule = [
		'regex' => '',
		'query' => '',
	];
	protected array $routeParams = [];
	protected $route = false;
	protected $routerDispatchedResponse;

	public function register_wp_route() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		
		$routeRuleRegex 	= '';
		$routeRuleMatches 	= '';

		try {
			// echo '<pre>';
			$this->route 	= $this->getRoutes()->match( $this->request );
			$routePrefix 	= $this->route->compiled->getStaticPrefix();
			$routeRuleRegex = $this->route->uri;
			// print_r($path . '<br>');
			// print_r($this->route->compiled->__serialize());

			if ( $this->route->hasParameters() ) {
				foreach ( $this->route->parameterNames() as $key => $parameter ) {
					$parameterRegex = '[a-z0-9\-_]+';
					if ( ! empty( $this->route->wheres[ $parameter ] ) ) {
						$parameterRegex = $this->route->wheres[ $parameter ];
					}
					$this->routeParams['regex'][ "{{$parameter}}" ] = $parameterRegex;
					$this->routeParams['matches'][ "{{$parameter}}" ] = '&' . $parameter . '=$matches['. ($key + 1) .']';
					add_rewrite_tag( "%{$parameter}%", "({$parameterRegex})" );
				}
			}

			if ( ! empty( $this->routeParams ) ) {
				$routeRuleRegex = str_replace( array_keys( $this->routeParams['regex'] ), array_values( $this->routeParams['regex'] ), $this->route->uri );
				$routeRuleMatches = implode( '', $this->routeParams['matches'] );
			}
			$this->route_rule['regex'] = '^' . $routeRuleRegex . '/?$';
			$this->route_rule['query'] = 'index.php?pagename=' . ltrim( $routePrefix, '/' ) . $routeRuleMatches;
 
			// print_r($this->route_rule);
			// print_r($this->routeParams);
			// print_r($routeParams);

			add_rewrite_rule( $this->route_rule['regex'], $this->route_rule['query'], 'top' );
			
		} catch (NotFoundHttpException $e) {
		}
		// exit;

	}

	public function resolve( $wp ) {
		global $wp_query, $wp_rewrite;
		$wp_rewrite->flush_rules();
		// echo '<pre>';
		// var_dump($wp_query->get( 'pagename' ));
		// print_r($wp_query);
		// print_r($this->getMiddleware());
		// var_dump( strpos( $path, $wp_query->get( 'route' ) ) );
		// print_r($wp_rewrite);
		// exit;


		if ( false !== $this->route && ! is_admin() ) {
			if ( strpos( $this->route->compiled->getStaticPrefix(), $wp_query->get( 'pagename' ) ) ) {
				
				$wp_query->is_404    = false;
	            $wp_query->is_custom = true;
				// Dispatch the request through the router
				// $this->routerDispatchedResponse = $this->dispatch( $this->request );

				$this->routerDispatchedResponse = (new Pipeline( $this->getContainer() ))
				    ->send( $this->request )
				    ->through( /*$this->globalMiddleware*/ [] )
				    ->then( $this->dispatchRouterResponse() );
				// Send the response back to the browser
				$this->routerDispatchedResponse->send();
			}

		}
	}

	protected function dispatchRouterResponse() {
		return function( $request ) {
			return $this->dispatch( $request );
	    };
	}

	protected function getContainer() {
		return $this->container;
	}
}
