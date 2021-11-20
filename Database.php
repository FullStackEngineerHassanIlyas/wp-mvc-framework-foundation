<?php 

namespace PluginName\core;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

/**
 * Database class
 */
class Database {
	
	function __construct() {
		global $wpdb;
		$db = new DB;

		$db->addConnection([
		    'driver'    => 'mysql',
		    'host'      => DB_HOST,
		    'database'  => DB_NAME,
		    'username'  => DB_USER,
		    'password'  => DB_PASSWORD,
		    'charset'   => $wpdb->charset,
		    'collation' => $wpdb->collate, 
		    'prefix'    => $wpdb->prefix,
		]);

		// Set the event dispatcher used by Eloquent models... (optional)
		$db->setEventDispatcher( new Dispatcher( new Container ) );
		// Make this Capsule instance available globally via static methods... (optional)
		$db->setAsGlobal();
		// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
    	$db->bootEloquent();
	}
}