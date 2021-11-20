<?php 

namespace WpMvcFramework\Foundation;

use Illuminate\Routing\Controller as BaseController;

/**
 * Controller Class
 */
class Controller extends BaseController {
	
	public function view( $view, $args = [] ) {
		return Application::$app->view->render( $view, $args );
	}
}