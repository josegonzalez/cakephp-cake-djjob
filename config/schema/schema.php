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
		'handler' => array('type' => 'text', 'null' => false, 'default' => NULL, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'queue' => array('type' => 'string', 'null' => false, 'default' => 'default', 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'attempts' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
		'run_at' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'locked_at' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'locked_by' => array('type' => 'string', 'null' => true, 'default' => NULL, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'failed_at' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'error' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'created_at' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'InnoDB')
	);
}