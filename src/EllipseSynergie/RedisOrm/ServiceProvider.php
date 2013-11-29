<?php
namespace EllipseSynergie\RedisOrm;

/**
 * Service provider
 *
 * @author Ellipse Synergie <info@ellipse-synergie.com>
 */
use Illuminate\Support\ServiceProvider;
use EllipseSynergie\RedisOrm\ConnectionResolver;
use EllipseSynergie\RedisOrm\Model as Model;
use Predis\Client;

class ServiceProvider extends ServiceProvider
{

	/**
	 * Boot the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('ellipsesynergie/redis-orm', 'ellipsesynergie/redis-orm');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Load package config
		$this->app['config']->package('ellipsesynergie/redis-orm', __DIR__ . '/../config');
		
		$this->registerPredisConnectionResolver();
	}

	/**
	 * Register connection resolver
	 */
	public function registerPredisConnectionResolver()
	{
		// Default
		$connections = array();
		
		// Generate connection
		foreach (\Config::get('redis-orm::redis') as $name => $configruations) {
			$connections[$name] = new Client($configruations);
		}
		
		// Create the connections resolver
		$resolver = new ConnectionResolver($connections);
		
		// Set the default connection name
		$resolver->setDefaultConnection('default');
		
		// Now you MUST set the resolver to the model
		Model::setConnectionResolver($resolver);
	}
}