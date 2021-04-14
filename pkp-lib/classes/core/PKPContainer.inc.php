<?php

import('lib.pkp.classes.core.PKPEventServiceProvider');

use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;

class PKPContainer extends Container {

	/**
	 * @return void
	 * @brief Create own container instance, initialize bindings
	 */
	public function __construct() {
		$this->registerBaseBindings();
		$this->registerCoreContainerAliases();
	}

	/**
	 * @return void
	 * @brief Bind the current container and set it globally
	 * let helpers, facades and services know to which container refer to
	 */
	protected function registerBaseBindings() {

		static::setInstance($this);
		$this->instance('app', $this);
		$this->instance(Container::class, $this);
		$this->singleton(Illuminate\Contracts\Debug\ExceptionHandler::class, function () {
			return new class implements Illuminate\Contracts\Debug\ExceptionHandler {
				public function shouldReport(Throwable $e) {
					return true;
				}

				public function report(Throwable $e) {
					error_log((string) $e);
				}

				public function render($request, Throwable $e) {
					return null;
				}

				public function renderForConsole($output, Throwable $e) {
					echo (string) $e;
				}
			};
		});

		Facade::setFacadeApplication($this);
	}

	/**
	 * @return void
	 * @brief Register used service providers within the container
	 */
	public function registerConfiguredProviders() {
		// Load main settings, this should be done before registering services, e.g., it's used by Database Service
		$this->loadConfiguration();

		$this->register(new PKPEventServiceProvider($this));
		$this->register(new Illuminate\Database\DatabaseServiceProvider($this));
		$this->register(new Illuminate\Bus\BusServiceProvider($this));
		$this->register(new Illuminate\Queue\QueueServiceProvider($this));
	}

	/**
	 * @param Illuminate\Support\ServiceProvider $provider
	 * @return void
	 * @brief Simplified service registration
	 */
	public function register($provider) {
		$provider->register();
		if (method_exists($provider, 'boot')) {
			$provider->boot();
		}
	}

	/**
	 * @return void
	 * @brief Bind aliases with contracts
	 */
	public function registerCoreContainerAliases() {
		foreach([
			'app'              => [self::class, Illuminate\Contracts\Container\Container::class, Psr\Container\ContainerInterface::class],
			'config'           => [Illuminate\Config\Repository::class, Illuminate\Contracts\Config\Repository::class],
			'db'               => [Illuminate\Database\DatabaseManager::class, Illuminate\Database\ConnectionResolverInterface::class],
			'db.connection'    => [Illuminate\Database\Connection::class, Illuminate\Database\ConnectionInterface::class],
			'events'           => [Illuminate\Events\Dispatcher::class, Illuminate\Contracts\Events\Dispatcher::class],
			'queue'            => [Illuminate\Queue\QueueManager::class, Illuminate\Contracts\Queue\Factory::class, Illuminate\Contracts\Queue\Monitor::class],
			'queue.connection' => [Illuminate\Contracts\Queue\Queue::class],
			'queue.failer'     => [Illuminate\Queue\Failed\FailedJobProviderInterface::class],
		] as $key => $aliases) {
			foreach ($aliases as $alias) {
				$this->alias($key, $alias);
			}
		}
	}

	/**
	 * @return void
	 * @brief Bind and load container configurations
	 * usage from Facade, see Illuminate\Support\Facades\Config
	 */
	protected function loadConfiguration() {
		$items = [];

		// Database connection
		$driver = strtolower(Config::getVar('database', 'driver'));
		if (substr($driver, 0, 8) === 'postgres') {
			$driver = 'pgsql';
		} else {
			$driver = 'mysql';
		}

		$items['database']['default'] = $driver;
		$items['database']['connections'][$driver] = [
			'driver'    => $driver,
			'host'      => Config::getVar('database', 'host'),
			'database'  => Config::getVar('database', 'name'),
			'username'  => Config::getVar('database', 'username'),
			'port'      => Config::getVar('database', 'port'),
			'unix_socket'=> Config::getVar('database', 'unix_socket'),
			'password'  => Config::getVar('database', 'password'),
			'charset'   => Config::getVar('i18n', 'connection_charset', 'utf8'),
			'collation' => Config::getVar('database', 'collation', 'utf8_general_ci'),
		];

		// Queue connection
		$items['queue']['default'] = 'database';
		$items['queue']['connections']['sync']['driver'] = 'sync';
		$items['queue']['connections']['database'] = [
			'driver' => 'database',
			'table' => 'jobs',
			'queue' => 'default',
			'retry_after' => 90,
		];

		$this->instance('config', $config = new Repository($items)); // create instance and bind to use globally
	}
}
