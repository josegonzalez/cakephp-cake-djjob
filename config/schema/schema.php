<?php 
/* SVN FILE: $Id$ */
/* App schema generated on: 2011-04-10 19:04:44 : 1302463184*/
class CakeDjjobSchema extends CakeSchema {
	var $name = 'CakeDjjob';

	function before($event = array()) {
		return true;
	}

	function after($event = array()) {
	}

	var $jobs = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
		'handler' => array('type' => 'text', 'null' => false, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'queue' => array('type' => 'string', 'null' => false, 'default' => 'default', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'attempts' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
		'run_at' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'locked_at' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'locked_by' => array('type' => 'string', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'failed_at' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'error' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'created_at' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
	);
}