<?php
namespace EllipseSynergieTest\RedisOrm;

use EllipseSynergie\RedisOrm\Model;

class FakeModelHash extends Model
{

	protected static $hash = true;

	public function validate()
	{
		return true;
	}
}