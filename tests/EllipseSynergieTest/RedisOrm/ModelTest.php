<?php
namespace EllipseSynergieTest\RedisOrm;

use Mockery as m;
use EllipseSynergieTest\RedisOrm\FakeModel as Model;
use EllipseSynergieTest\RedisOrm\FakeModelHash as ModelHash;
use EllipseSynergie\RedisOrm\ConnectionResolver;

/**
 * @group ModelTest
 */
class ModelTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * Teardown
	 */
	public function tearDown()
	{
		m::close();
	}

	public function setUp()
	{
		$this->model = new Model();
		$this->modelHash = new ModelHash();
	}

	public function testFillConstructor()
	{
		$model = new Model(array(
			'foo' => 'bar'
		));
		
		$this->assertEquals('bar', $model->foo);
	}

	public function testDeleteFromKey()
	{
		// Mock
		$connection = m::mock('Predis\Client');
		$connection->shouldReceive('del')
			->once()
			->andReturn(true);
		
		$resolver = m::mock('EllipseSynergie\RedisOrm\ConnectionResolver');
		$resolver->shouldReceive('connection')
			->once()
			->andReturn($connection);
		
		$this->model->setConnectionResolver($resolver);
		$this->model->id = 1;
		
		$this->assertTrue($this->model->delete());
	}

	public function testDeleteFromHash()
	{
		// Mock
		$connection = m::mock('Predis\Client');
		$connection->shouldReceive('hdel')
			->once()
			->andReturn(true);
		
		$resolver = m::mock('EllipseSynergie\RedisOrm\ConnectionResolver');
		$resolver->shouldReceive('connection')
			->once()
			->andReturn($connection);
		
		$this->modelHash->setConnectionResolver($resolver);
		$this->modelHash->id = 1;
		
		$this->assertTrue($this->modelHash->delete());
	}

	/**
	 * @expectedException EllipseSynergie\RedisOrm\Exceptions\ModelIdRequiredException
	 */
	public function testDeleteFromKeyMissingId()
	{
		$this->assertTrue($this->model->delete());
	}

	/**
	 * @expectedException EllipseSynergie\RedisOrm\Exceptions\ModelIdRequiredException
	 */
	public function testDeleteFromHashMissingId()
	{
		$this->assertTrue($this->modelHash->delete());
	}

	/**
	 * @expectedException EllipseSynergie\RedisOrm\Exceptions\ModelIdRequiredException
	 */
	public function testDeleteFromKeyIdIsRequired()
	{
		$this->model->delete();
	}

	public function testSaveToKey()
	{
		// Mock
		$connection = m::mock('Predis\Client');
		$connection->shouldReceive('incr')
			->once()
			->andReturn(1);
		$connection->shouldReceive('set')
			->once()
			->andReturn(true);
		
		$resolver = m::mock('EllipseSynergie\RedisOrm\ConnectionResolver');
		$resolver->shouldReceive('connection')
			->twice()
			->andReturn($connection);
		
		$this->modelHash->setConnectionResolver($resolver);
		$this->model->data = 1;
		$this->model->save();
		
		$this->assertTrue(is_int($this->model->id));
		;
	}

	public function testSaveToHash()
	{
		// Mock
		$connection = m::mock('Predis\Client');
		$connection->shouldReceive('incr')
			->once()
			->andReturn(1);
		$connection->shouldReceive('hset')
			->once()
			->andReturn(true);
		
		$resolver = m::mock('EllipseSynergie\RedisOrm\ConnectionResolver');
		$resolver->shouldReceive('connection')
			->twice()
			->andReturn($connection);
		
		$this->modelHash->setConnectionResolver($resolver);
		$this->modelHash->data = 1;
		$this->modelHash->save();
		
		$this->assertTrue(is_int($this->modelHash->id));
	}

	public function testFindFromHashById()
	{
		// Mock
		$connection = m::mock('Predis\Client');
		$connection->shouldReceive('hget')
			->once()
			->andReturn('{"id":1}');
		
		$resolver = m::mock('EllipseSynergie\RedisOrm\ConnectionResolver');
		$resolver->shouldReceive('connection')
			->once()
			->andReturn($connection);
		
		$this->modelHash->setConnectionResolver($resolver);
		
		$this->modelHash->findById(1);
	}

	/**
	 * @expectedException EllipseSynergie\RedisOrm\Exceptions\ModelNotFoundException
	 */
	public function testFindFromHashByIdNotFound()
	{
		// Mock
		$connection = m::mock('Predis\Client');
		$connection->shouldReceive('hget')
			->once()
			->andReturn(null);
		
		$resolver = m::mock('EllipseSynergie\RedisOrm\ConnectionResolver');
		$resolver->shouldReceive('connection')
			->once()
			->andReturn($connection);
		
		$this->modelHash->setConnectionResolver($resolver);
		
		$this->modelHash->findById(1);
	}

	public function testFindById()
	{
		// Mock
		$connection = m::mock('Predis\Client');
		$connection->shouldReceive('get')
			->once()
			->andReturn('{"id":1}');
		
		$resolver = m::mock('EllipseSynergie\RedisOrm\ConnectionResolver');
		$resolver->shouldReceive('connection')
			->once()
			->andReturn($connection);
		
		$this->model->setConnectionResolver($resolver);
		$this->model->findById(999);
	}

	/**
	 * @expectedException EllipseSynergie\RedisOrm\Exceptions\ModelNotFoundException
	 */
	public function testFindByIdNotFound()
	{
		// Mock
		$connection = m::mock('Predis\Client');
		$connection->shouldReceive('get')
			->once()
			->andReturn(null);
		
		$resolver = m::mock('EllipseSynergie\RedisOrm\ConnectionResolver');
		$resolver->shouldReceive('connection')
			->once()
			->andReturn($connection);
		
		$this->model->setConnectionResolver($resolver);
		$this->model->findById(999);
	}

	public function testSetAttribute()
	{
		$this->model->setAttribute('foo', 'bar');
		$this->assertEquals('bar', $this->model->foo);
	}

	public function testGetAttribute()
	{
		$this->model->setAttribute('foo', 'bar');
		$this->assertEquals('bar', $this->model->getAttribute('foo'));
	}

	public function testGetAttributeNotFoud()
	{
		$this->assertNull($this->model->getAttribute('foo'));
	}

	public function testFill()
	{
		$this->model->fill(array(
			'foo' => 'bar'
		));
		
		$this->assertEquals('bar', $this->model->foo);
	}

	public function testGetAttributes()
	{
		$this->model->fill(array(
			'foo' => 'bar'
		));
		
		$this->assertEquals(array(
			'foo' => 'bar'
		), $this->model->getAttributes());
	}

	public function testIsEmpty()
	{
		$this->assertTrue(empty($this->model->foo));
	}

	public function testCallOnPredisConnection()
	{
		// Mock
		$connection = m::mock('Predis\Client');
		$connection->shouldReceive('ping')
			->once()
			->andReturn(true);
		
		$resolver = m::mock('EllipseSynergie\RedisOrm\ConnectionResolver');
		$resolver->shouldReceive('connection')
			->once()
			->andReturn($connection);
		
		$this->model->setConnectionResolver($resolver);
		$this->assertTrue($this->model->ping());
	}

	public function testGetHashByIdLowerThenBucketLength()
	{
		$this->assertEquals(1, Model::getHashById(0));
		$this->assertEquals(1, Model::getHashById(1));
		$this->assertEquals(1, Model::getHashById(1000));
	}

	public function testGetHashByIdHigherThenBucketLength()
	{
		$this->assertEquals(1, Model::getHashById(1999));
		$this->assertEquals(2, Model::getHashById(2000));
		$this->assertEquals(5637, Model::getHashById(5637111));
		$this->assertEquals(5637, Model::getHashById(5637657));
	}

	public function testGetconnectionResolver()
	{
		$resolver = new ConnectionResolver();
		
		$this->model->setConnectionResolver($resolver);
		$this->assertInstanceOf('EllipseSynergie\RedisOrm\ConnectionResolver', $this->model->getConnectionResolver());
	}

	public function testGetAnfSetConnectionName()
	{
		$this->assertInstanceOf('EllipseSynergie\RedisOrm\Model', $this->model->setConnection('default'));
		$this->assertEquals('default', $this->model->getConnectionName());
	}

	public function testSetConnectionResolver()
	{
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
	}

	public function testMutator()
	{
		$this->model->test = 'foo';
		$this->assertEquals('bravo !', $this->model->test);
	}
}