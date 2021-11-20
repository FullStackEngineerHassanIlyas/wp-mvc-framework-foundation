<?php 

namespace WpMvcFramework\Foundation;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

/**
 * View Class
 */
class View {

	public string $layout = 'main';

	public function render( $view, $args = [] ) {
		$viewContent = view( $view, $args );

		add_filter( 'document_title_parts', function( $parts ) use( $viewContent ) {
			if ( ! empty( $titleSection = $viewContent->getFactory()->getSection('title') ) ) {
				$parts['title'] = $titleSection;				
			}
			return $parts;
		} );

		$params = [
			'viewContent' => $viewContent,
		];
		add_filter( 'template_include', function( $template ) use( $params ) {
			echo $params['viewContent'];
		} );
	}

	private function renderBladeView( $view, $args = [] ) {
		// Configuration
	    // Note that you can set several directories where your templates are located
	    $pathsToTemplates = [ PLUGIN_BASE_PATH . 'views' ];
	    $pathToCompiledTemplates = PLUGIN_BASE_PATH . 'cache/views';

	    // Dependencies
	    $filesystem 	 = new Filesystem;
	    $eventDispatcher = new Dispatcher( new Container );

	    // Create View Factory capable of rendering PHP and Blade templates
	    $viewResolver  = new EngineResolver;
	    $bladeCompiler = new BladeCompiler( $filesystem, $pathToCompiledTemplates );

	    $viewResolver->register('blade', function () use ( $bladeCompiler ) {
	        return new CompilerEngine( $bladeCompiler );
	    });

	    $viewResolver->register('php', function () {
	        return new PhpEngine;
	    });

	    $viewFinder  = new FileViewFinder( $filesystem, $pathsToTemplates );
	    $viewFactory = new Factory( $viewResolver, $viewFinder, $eventDispatcher );

	    // Render template
	    return $viewFactory->make( $view, $args );
	}

}
