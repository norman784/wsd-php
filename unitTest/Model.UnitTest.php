<?php
define('DB_ENGINE', 		'mysql');
define('DB_NAME',			'test');
define('DB_USER',			'root');
define('DB_PASS',			'toor');
define('DB_HOST',			'localhost');

require_once '../Model.php';

class Users extends Model
{
	static function getFieldsTest()
	{
		echo "\n----------------------\n\n";
		echo "<strong>Model::getFields()</strong>\n";
		print_r(self::getFields());
		echo  "\n";
	}
	
	static function getTableNameTest()
	{
		echo "\n----------------------\n\n";
		echo "<strong>Model::getTableName()</strong>\n";
		echo self::getTableName() . "\n\n";
	}
	
	static function getSelectTest()
	{
		echo "\n----------------------\n\n";
		echo "<strong>Model::getSelect()</strong>\n";
		echo self::getSelect() . "\n\n";
	}
	
	static function firstTestID()
	{
		echo "\n----------------------\n\n";
		echo "<strong>Model::first(1)</strong>\nResult: ";
		print_r(self::first(1)) . "\n";
	}
	
	static function firstTestCondition()
	{
		$options = array();
		$options['conditions'] = array('`userID` = ? AND `userKey` = ?', 1, '*752E6A3326EDB051598CD050487E9E04BEA88F75');
		echo "\n----------------------\n\n";
		echo "<strong>Model::find(" . print_r($options, true) . ")</strong>\nResult: ";
		print_r(self::find($options)) . "\n";
	}
	
	static function createTest()
	{
		$data = array('id'=>1, 'userID'=>rand(0,99999), 'userKey'=>'*752E6A3326EDB051598CD050487E9E04BEA88F75');
		echo "\n----------------------\n\n";
		echo "<strong>Model::create(" . print_r($data, true) . ")</strong>\nResult: ";
		print_r(self::create($data)) . "\n";
	}
	
	static function updateTest()
	{
		$me = self::first(1);
		$data = array();
		$data['id'] = $me->id;
		$data['userID'] = $me->userID;
		$data['userKey'] = 'lolz';
		echo "\n----------------------\n\n";
		echo "<strong>Model::update(" . print_r($data, true) . ")</strong>\nResult: ";
		print_r($me->update($data)) . "\n";
	}
	
	static function updatePropertyTest()
	{
		$me = self::first(2);
		echo "\n----------------------\n\n";
		echo "<strong>Model::updateProperty('userKey', 'kek')</strong>\nResult: ";
		print_r($me->updateProperty('userKey', 'kek')) . "\n";
	}
	
	static function deleteTest()
	{
		$me = self::first(18);
		if(is_object($me)) $me->delete();
		echo "\n----------------------\n\n";
		echo "<strong>Model::delete()</strong>\nResult: ";
		print_r($me) . "\n";
	}
}

echo '<pre>';
Users::getFieldsTest();
Users::getTableNameTest();
Users::getSelectTest();
Users::firstTestID();
Users::firstTestCondition();
//Users::createTest();
Users::updateTest();
Users::updatePropertyTest();
Users::deleteTest();