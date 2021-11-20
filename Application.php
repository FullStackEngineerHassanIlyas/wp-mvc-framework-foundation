<?php 

namespace WpMvcFramework\Foundation;

use Illuminate\Http\Response;
use Illuminate\Foundation\Application as BaseApplication;

/**
 * Class Application
 *
 * @author Hassan Ilyas <hassan.ilyas0@gmail.com>
 * @package app\core
 */
class Application extends BaseApplication {

	public static string $ROOT_DIR;
	public static Application $app;
	public Request $request;
	public Response $response;
	public View $view;
	public Database $db;

	protected $kernel;
	protected $current_route = null;
	protected array $route_rule = [
		'regex' => '',
		'query' => '',
	];
	protected array $route_params = [];
	
	function __construct( $rootPath ) {
		parent::__construct( $rootPath );

		self::$ROOT_DIR = $rootPath;
		self::$app 		= $this;
		$this->request 	= Request::capture();
		$this->view 	= new View;
		$this->db 		= new Database;
	}

	public function run( $kernel ) {
		if ( is_admin() || in_array( $GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'] ) ) {
			return;
		}

		$this->kernel = $kernel;

		$this->initialize();
	}
	public function init_wp() {
		global $wp_rewrite;

		$wp_rewrite->flush_rules();

		$route_rule_regex 	= '';
		$route_rule_matches = '';
		
		$this->response = $this->kernel->handle( $this->request );

		if ( empty( $this->current_route = $this['router']->current() ) ) {
			return;
		}
		
		try {
			$route_prefix 			= $this->current_route->compiled->getStaticPrefix();
			$route_rule_regex 		= $this->current_route->uri;

			if ( $this->current_route->hasParameters() ) {
				foreach ( $this->current_route->parameterNames() as $key => $parameter ) {
					$parameterRegex = '[a-z0-9\-_]+';
					if ( ! empty( $this->current_route->wheres[ $parameter ] ) ) {
						$parameterRegex = $this->current_route->wheres[ $parameter ];
					}
					$this->route_params['regex'][ "{{$parameter}}" ] = $parameterRegex;
					$this->route_params['matches'][ "{{$parameter}}" ] = '&' . $parameter . '=$matches['. ($key + 1) .']';
					add_rewrite_tag( "%{$parameter}%", "({$parameterRegex})" );
				}
			}

			if ( ! empty( $this->route_params ) ) {
				$route_rule_regex = str_replace( array_keys( $this->route_params['regex'] ), array_values( $this->route_params['regex'] ), $this->current_route->uri );
				$route_rule_matches = implode( '', $this->route_params['matches'] );
			}
			$this->route_rule['regex'] = '^' . $route_rule_regex . '/?$';
			$this->route_rule['query'] = 'index.php?pagename=' . ltrim( $route_prefix, '/' ) . $route_rule_matches;

			add_rewrite_rule( $this->route_rule['regex'], $this->route_rule['query'], 'top' );
		} catch (NotFoundHttpException $e) {
			throw $e;
		}
	}
	public function resolve_wp( $wp ) {
		global $wp_query, $wp_rewrite;

		$wp_rewrite->flush_rules();


		if ( ! empty( $this->current_route ) && ! is_admin() ) {
			if ( strpos( $this->current_route->compiled->getStaticPrefix(), $wp_query->get( 'pagename' ) ) ) {
				
				$wp_query->is_404    = false;
	            $wp_query->is_custom = true;
				
				$this->response->send();
				$this->kernel->terminate( $this->request, $this->response );
			}
		}
	}

	private function initialize() {
		add_action( 'init', [ $this, 'init_wp' ] );
		add_action( 'wp', [ $this, 'resolve_wp' ] );
	}
}