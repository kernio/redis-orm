<?php
namespace EllipseSynergieTest\RedisOrm;

use EllipseSynergie\RedisOrm\Model;

class FakeModel extends Model
{

	public function validate()
	{
		return true;
	}

	public function setTestAttribute($value)
	{
		$this->attributes['test'] = 'bravo !';
	}
}