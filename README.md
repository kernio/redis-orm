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
$resolver = new \IsaSdk\Repository\Predis\ConnectionResolver($connections);

// Set the default connection name
$resolver->setDefaultConnection('default');
		
// Now you MUST set the resolver to the model
\IsaSdk\Repository\Predis::setConnectionResolver($resolver);
```

After this step, every model should be able to connect to redis. Also, you can change the connection name directly in your model after you have define it in the connections resolver.

```php
class ExamplePlot extends IsaSdk\Repository\Predis 
{
	protected $connection = 'my-redis-connection-name';
}
```