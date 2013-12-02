# Redis ORM

### Status

[![Build Status](https://travis-ci.org/ellipsesynergie/redis-orm.png?branch=master)](https://travis-ci.org/ellipsesynergie/redis-orm)

## Documentation

##Installation

Begin by installing this package through Composer. Edit your project's `composer.json` file to require `ellipsesynergie/redis-orm`.

```javascript
{
    "require": {
        "ellipsesynergie/redis-orm": "dev-master"
    }
}
```

## Usage
The Predis ORM provide a simple way  to handle multiple Redis connection. You can also take a look at [Predis connection configuration](https://github.com/nrk/predis#connecting-to-redis "Connection to Redis") to know how to configure correctly the client instance.

```php
// The connections list. You must provide a name and a `Predis\Client` object.
$connections = array(
	'default' => new \Predis\Client()
);

// Create the connections resolver
$resolver = new \EllipseSynergie\RedisOrm\ConnectionResolver($connections);

// Set the default connection name
$resolver->setDefaultConnection('default');
		
// Now you MUST set the resolver to the model
\EllipseSynergie\RedisOrm\Model::setConnectionResolver($resolver);
```

After this step, every model should be able to connect to redis. Also, you can change the connection name directly in your model after you have define it in the connections resolver.

```php
class ExamplePlot extends EllipseSynergie\RedisOrm\Model 
{
	protected $connection = 'my-redis-connection-name';
}
```

### Example
Using the ORM with SET/GET

```php
class ExampleModel extends EllipseSynergie\RedisOrm\Model 
{

	/**
	 * Namespace used to generates keys
	 *
	 * @var string
	 */
	protected static $namespace = 'my-namespace';
}

//Will throw the redis command : SET "my-namespace:1" "{'foo':'bar'}"
$model = new ExampleModel;
$model->foo = 'bar';
$model->save();
```
Using the ORM with HSET/HGET (Hashes)

```php
class ExampleModel extends EllipseSynergie\RedisOrm\Model 
{
	/**
	 * Use Redis Hashing for this repository
	 *
	 * @var bool
	 */
	protected static $hash = true;
	
	/**
	 * Namespace for hash butckets
	 *
	 * @var string
	 */
	protected static $bucketNamespace = 'my-bucket';

	/**
	 * Namespace used to generates keys
	 *
	 * @var string
	 */
	protected static $namespace = 'my-namespace';
}

//Will throw the redis command : HSET "my-bucket:1" "my-namespace:1" "{'foo':'bar'}"
$model = new ExampleModel;
$model->foo = 'bar';
$model->save();
```

##Predis

You can access Predis command directly from the Model

```php
// @todo add example
```

##Laravel 4

For laravel 4, you need to add the service provider. Open app/config/app.php, and add a new item to the providers array.

```php
'EllipseSynergie\RedisOrm\RedisOrmServiceProvider'
```

###Package configurations

To configure the package to meet your needs, you must publish the configuration in your application before you can modify them. Run this artisan command.

```bash
php artisan config:publish ellipsesynergie/redis-orm
```

The configuration files could now be found in `app/config/packages/ellipsesynergie/redis-orm`. Read the description for each configurations to know what you can override.