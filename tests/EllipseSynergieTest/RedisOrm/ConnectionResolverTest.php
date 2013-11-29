<?php
namespace EllipseSynergieTest\RedisOrm;

use Mockery as m;
use EllipseSynergie\RedisOrm\ConnectionResolver;

/**
 * @group ConnectionResolverTest
 */
class ConnectionResolverTest extends \PHPUnit_Framework_TestCase
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
		//
	}

	public function testCreateDefaultConnections()
	{
		$client = m::mock('Predis\Client');
		$connection = array(
			'default' => $client
		);
		
		$resolver = new ConnectionResolver($connection);
		$resolver->setDefaultConnection('default');
		
		$this->assertInstanceOf('Predis\Client', $resolver->connection('default'));
		$this->assertFalse($resolver->hasConnection('foo'));
		$this->assertTrue($resolver->hasConnection('default'));
		$this->assertEquals('default', $resolver->getDefaultConnection());
	}

	public function testGetConnection()
	{
		$client = m::mock('Predis\Client');
		$connection = array(
			'default' => $client
		);
		
		$resolver = new ConnectionResolver($connection);
		$resolver->setDefaultConnection('default');
		$resolver->connection();
	}
}