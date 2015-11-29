<?php
class M4db0e246acd4493eae330183cbdd56cb extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 */
	public $description = 'Initial migration of jobs table';

/**
 * Actions to be performed
 *
 * @var array $migration
 */
	public $migration = array(
		'up' => array(
			'create_table' => array(
				'jobs' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'),
					'handler' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
					'queue' => array('type' => 'string', 'null' => false, 'default' => 'default', 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
					'attempts' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10),
					'run_at' => array('type' => 'datetime', 'null' => true, 'default' => null),
					'locked_at' => array('type' => 'datetime', 'null' => true, 'default' => null),
					'locked_by' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
					'failed_at' => array('type' => 'datetime', 'null' => true, 'default' => null),
					'error' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
					'created_at' => array('type' => 'datetime', 'null' => false, 'default' => null),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
					),
					'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'InnoDB'),
				),
			),
		),
		'down' => array(
			'drop_table' => array(
				'jobs'
			),
		),
	);

/**
 * Before migration callback
 *
 * @param string $direction up or down direction of migration process
 * @return bool Should process continue
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction up or down direction of migration process
 * @return bool Should process continue
 */
	public function after($direction) {
		return true;
	}
}
