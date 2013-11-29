<?php
namespace EllipseSynergie\RedisOrm;

/**
 * Repository - Base model for Predis repositories
 *
 * @author Ellipse Synergie <info@ellipse-synergie.com>
 */
use Predis\Client;
use EllipseSynergie\RedisOrm\Exceptions\ModelNotFoundException;
use EllipseSynergie\RedisOrm\Exceptions\ModelIdRequiredException;

abstract class Model
{

	/**
	 * Namespace for hash butckets
	 *
	 * @var string
	 */
	protected static $bucketNamespace;

	/**
	 * Namespace for keys
	 *
	 * @var string
	 */
	protected static $namespace;

	/**
	 * The object id
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * The model's attributes.
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * The maximum lenght of element per hash into the bucket
	 *
	 * @var int
	 */
	protected static $bucketLength = 1000;

	/**
	 * Use Redis Hashing for this repository
	 *
	 * @var bool
	 */
	protected static $hash = false;

	/**
	 * The connection name for the model.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * The connection resolver instance.
	 *
	 * @var IsaSdk\Repository\Predis\ConnectionResolver
	 */
	protected static $resolver;

	/**
	 * Create a new model instance.
	 *
	 * @param array $attributes        	
	 */
	public function __construct(array $attributes = array())
	{
		$this->fill($attributes);
	}

	/**
	 * Delete the model
	 *
	 * @return bool
	 */
	public function delete()
	{
		if (static::$hash) {
			return $this->deleteFromHash();
		} else {
			return $this->deleteFromKey();
		}
	}

	/**
	 * Delete the object from hash
	 *
	 * @return bool
	 * @throws IsaSdk\Repository\Exceptions\ModelIdRequiredException
	 */
	protected function deleteFromHash()
	{
		if (! is_null($this->getAttribute('id'))) {
			
			// Get the hash from the id
			$hash = static::getHashById($this->getAttribute('id'));
			
			return $this->getConnection()->hdel(static::$bucketNamespace . ':' . $hash, $this->getAttribute('id'));
		}
		
		// Throw exception
		throw new ModelIdRequiredException();
	}

	/**
	 * Delete the object from key
	 *
	 * @return bool
	 * @throws IsaSdk\Repository\Exceptions\ModelIdRequiredException
	 */
	protected function deleteFromKey()
	{
		if (! is_null($this->getAttribute('id'))) {
			return $this->getConnection()->del(static::$namespace . ':' . $this->getAttribute('id'));
		}
		
		// Throw exception
		throw new ModelIdRequiredException();
	}

	/**
	 * Save the model
	 *
	 * @return bool
	 */
	public function save()
	{
		// Validate the object first
		$this->validate();
		
		// If we don't have the id in the objcet
		if (is_null($this->getAttribute('id'))) {
			
			// Generate a new id in th elist
			$id = $this->getConnection()->incr(static::$namespace . ':ids');
			
			// Set the id to the current object
			$this->setAttribute('id', $id);
		}
		
		// Prepare data to save
		$attributes = $this->attributes;
		
		// Remove the id from attributes
		unset($attributes['id']);
		
		// Encode attributes
		$attributes = json_encode($attributes);
		
		if (static::$hash) {
			return $this->saveToHash($attributes);
		} else {
			return $this->saveToKey($attributes);
		}
	}

	/**
	 * Save the model to key
	 *
	 * @return bool
	 */
	protected function saveToKey($attributes)
	{
		return $this->getConnection()->set(static::$namespace . ':' . $this->getAttribute('id'), $attributes);
	}

	/**
	 * Save the model to hash
	 *
	 * @return bool
	 */
	protected function saveToHash($attributes)
	{
		// Get the hash from the id
		$hash = static::getHashById($this->getAttribute('id'));
		
		// Save the object in the hash
		return $this->getConnection()->hset(static::$bucketNamespace . ':' . $hash, $this->getAttribute('id'), $attributes);
	}

	/**
	 * Find model by ID
	 *
	 * @return IsaSdk\Repository\Predis
	 */
	public static function findById($id)
	{
		if (static::$hash) {
			return static::findFromHashById($id);
		} else {
			return static::find($id);
		}
	}

	/**
	 * Find from hash by ID
	 *
	 * @param int $id        	
	 * @throws ModelNotFoundException
	 * @return \IsaSdk\Repository\Predis\Plot
	 */
	protected static function findFromHashById($id)
	{
		// Get the hash
		$hash = static::getHashById($id);
		
		// Create a new instance
		$instance = new static();
		$response = $instance->getConnection()->hget(static::$bucketNamespace . ':' . $hash, $id);
		
		// If we dont'have reponse
		if (empty($response)) {
			throw new ModelNotFoundException();
		}
		
		$attributes = json_decode($response, true);
		$attributes['id'] = $id;
		
		return new static($attributes);
	}

	/**
	 * Find model
	 *
	 * @throws IsaSdk\Repository\Exceptions\ModelNotFoundException
	 * @return IsaSdk\Repository\Predis
	 */
	protected static function find($id)
	{
		$instance = new static();
		$response = $instance->getConnection()->get(static::$namespace . ':' . $id);
		
		// If we dont'have reponse
		if (empty($response)) {
			throw new ModelNotFoundException();
		}
		
		$attributes = json_decode($response, true);
		$attributes['id'] = $id;
		
		return new static($attributes);
	}

	/**
	 * Get the database connection for the model.
	 *
	 * @return Predis\Client
	 */
	public function getConnection()
	{
		return static::resolveConnection($this->connection);
	}

	/**
	 * Get the current connection name for the model.
	 *
	 * @return string
	 */
	public function getConnectionName()
	{
		return $this->connection;
	}

	/**
	 * Set the connection associated with the model.
	 *
	 * @param string $name        	
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function setConnection($name)
	{
		$this->connection = $name;
		
		return $this;
	}

	/**
	 * Resolve a connection instance.
	 *
	 * @param string $connection        	
	 * @return Predis\Client
	 */
	public static function resolveConnection($connection = null)
	{
		return static::$resolver->connection($connection);
	}

	/**
	 * Get the connection resolver instance.
	 *
	 * @return IsaSdk\Repository\Predis\ConnectionResolver
	 */
	public static function getConnectionResolver()
	{
		return static::$resolver;
	}

	/**
	 * Set the connection resolver instance.
	 *
	 * @param IsaSdk\Repository\Predis\ConnectionResolver $resolver        	
	 * @return void
	 */
	public static function setConnectionResolver($resolver)
	{
		static::$resolver = $resolver;
	}

	/**
	 * Set a given attribute on the model.
	 *
	 * @param string $key        	
	 * @param mixed $value        	
	 */
	public function setAttribute($key, $value)
	{
		// First we will check for the presence of a mutator for the set operation
		// which simply lets the developers tweak the attribute as it is set on
		// the model, such as "json_encoding" an listing of data for storage.
		if ($this->hasSetMutator($key)) {
			$method = 'set' . static::studlyCase($key) . 'Attribute';
			
			return $this->{$method}($value);
		}
		
		$this->attributes[$key] = $value;
	}

	/**
	 * Determine if a set mutator exists for an attribute.
	 *
	 * @param string $key        	
	 * @return bool
	 */
	public function hasSetMutator($key)
	{
		return method_exists($this, 'set' . static::studlyCase($key) . 'Attribute');
	}

	/**
	 * Get an attribute from the model.
	 *
	 * @param string $key        	
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		$inAttributes = array_key_exists($key, $this->attributes);
		
		// If the key references an attribute, we can just go ahead and return the
		// plain attribute value from the model. This allows every attribute to
		// be dynamically accessed through the _get method without accessors.
		if ($inAttributes or $this->hasGetMutator($key)) {
			return $this->getAttributeValue($key);
		}
	}

	/**
	 * Get an attribute from the $attributes array.
	 *
	 * @param string $key        	
	 * @return mixed
	 */
	protected function getAttributeFromArray($key)
	{
		if (array_key_exists($key, $this->attributes)) {
			return $this->attributes[$key];
		}
	}

	/**
	 * Get a plain attribute (not a relationship).
	 *
	 * @param string $key        	
	 * @return mixed
	 */
	protected function getAttributeValue($key)
	{
		$value = $this->getAttributeFromArray($key);
		
		// If the attribute has a get mutator, we will call that then return what
		// it returns as the value, which is useful for transforming values on
		// retrieval from the model to a form that is more useful for usage.
		if ($this->hasGetMutator($key)) {
			return $this->mutateAttribute($key, $value);
		}
		
		return $value;
	}

	/**
	 * Determine if a get mutator exists for an attribute.
	 *
	 * @param string $key        	
	 * @return bool
	 */
	public function hasGetMutator($key)
	{
		return method_exists($this, 'get' . static::studlyCase($key) . 'Attribute');
	}

	/**
	 * Get the value of an attribute using its mutator.
	 *
	 * @param string $key        	
	 * @param mixed $value        	
	 * @return mixed
	 */
	protected function mutateAttribute($key, $value)
	{
		return $this->{'get' . static::studlyCase($key) . 'Attribute'}($value);
	}

	/**
	 * Fill the model with an array of attributes.
	 *
	 * @param array $attributes        	
	 * @return \IsaSdk\Repository\Predis
	 */
	public function fill(array $attributes)
	{
		foreach ($attributes as $key => $value) {
			$this->setAttribute($key, $value);
		}
		
		return $this;
	}

	/**
	 * Get all of the current attributes on the model.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Get the hash value from a ID
	 *
	 * @param int $id        	
	 * @return int
	 */
	public static function getHashById($id)
	{
		// Convert to id to int
		$id = (int) $id;
		
		// If the $id is smaller then bucket lenght
		if ($id < static::$bucketLength) {
			$hash = 1;
		} else {
			$hash = $id / static::$bucketLength;
		}
		
		return (int) $hash;
	}

	/**
	 * Validate the current object
	 */
	abstract public function validate();
	
	/**
	 * Convert a value to studly caps case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function studlyCase($value)
	{
		$value = ucwords(str_replace(array('-', '_'), ' ', $value));

		return str_replace(' ', '', $value);
	}

	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @param string $key        	
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Dynamically set attributes on the model.
	 *
	 * @param string $key        	
	 * @param mixed $value        	
	 */
	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * Determine if an attribute exists on the model.
	 *
	 * @param string $key        	
	 * @return bool
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Handle dynamic method calls into the connection
	 *
	 * @param string $method        	
	 * @param array $parameters        	
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(array(
			$this->getConnection(),
			$method
		), $parameters);
	}
}