<?php
class M4e12028cb08847248aed12bccbdd56cb extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = '';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(),
		'down' => array(),
	);

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function after($direction) {
		$Job = $this->generateModel('Job');
		if ($direction == 'up') {
			return $Job->query('ALTER TABLE  `jobs` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
		} elseif ($direction == 'down') {
			return $Job->query('ALTER TABLE  `jobs` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci');
		}

		return false;
	}
}
?>