<?php

namespace AppBundle;

// use Illuminate\Database\Capsule\Manager;

class Eloquent {

	private $capsule_ = null;

	public function __construct() {
		$this->capsule_ = new \Illuminate\Database\Capsule\Manager();

		$this->capsule_->addConnection([
			'driver'    => 'mysql',
			'host'      => '127.0.0.1',
			'database'  => 'sql_injections',
			'username'  => 'root',
			'password'  => '',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		]);

		// Set the event dispatcher used by Eloquent models... (optional)
		// use Illuminate\Events\Dispatcher;
		// use Illuminate\Container\Container;
		// $this->capsule_->setEventDispatcher(new Dispatcher(new Container));

		// Make this Capsule instance available globally via static methods... (optional)
		// $this->capsule_->setAsGlobal();

		// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
		$this->capsule_->bootEloquent();
	}

	public function connection() {
		return $this->capsule_->getConnection('default');
	}

}