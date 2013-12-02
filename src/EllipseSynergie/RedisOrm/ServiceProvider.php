<?php
namespace EllipseSynergie\RedisOrm;

/**
 * This file is part of the Redis ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Maxime Beaudoin <maxime.beaudoin@ellipse-synergie.com>
 *        
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