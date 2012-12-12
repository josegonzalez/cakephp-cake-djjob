<?php
App::uses('ConnectionManager', 'Model');
App::uses('CakeJob', 'CakeDjjob.Job');
App::uses('DJJob', 'Djjob.Vendor');

/**
 * CakeDjjob Component
 *
 * Wrapper around DJJob library
 *
 * @copyright   Copyright 2011, Jose Diaz-Gonzalez. (http://josediazgonzalez.com)
 * @link        http://github.com/josegonzalez/cake_djjob
 * @package     cake_djjob
 * @subpackage  cake_djjob.controller.components
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class CakeDjjobComponent extends Component {

	public $settings = array(
		'connection'=> 'default',
		'type' => 'mysql',
	);

/**
 * Constructor.
 *
 * @param ComponentCollection $collection
 * @param array $settings
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$settings = array_merge($this->settings, $settings);
		parent::__construct($collection, $settings);
	}

/**
 * Called before the Controller::beforeFilter().
 *
 * @param object  A reference to the controller
 * @return void
 * @access public
 * @link http://book.cakephp.org/view/65/MVC-Class-Access-Within-Components
 */
	public function initialize(Controller $controller) {
		$connection = ConnectionManager::getDataSource($this->settings['connection']);

		if ($this->settings['type'] == 'mysql') {
			DJJob::setConnection($connection->getConnection());
		} else {
			DJJob::configure(
				implode(';', array(
					"{$this->settings['type']}:host={$connection->config['host']}",
					"dbname={$connection->config['database']}",
					"port={$connection->config['port']}",
					"user={$connection->config['login']}",
					"password={$connection->config['password']}"
				))
			);
		}
	}

/**
 * Returns a job
 * 
 * Auto imports and passes through the constructor parameters to newly created job
 * Note: (PHP 5 >= 5.1.3) - requires ReflectionClass if passing arguments
 *
 * @param string $jobName Name of job being loaded
 * @param mixed $argument Some argument to pass to the job
 * @param mixed ... etc.
 * @return mixed Job instance if available, null otherwise
 */
	public function load() {
		$args = func_get_args();
		if (empty($args) || !is_string($args[0])) {
			return null;
		}

		$jobName = array_shift($args);
		list($plugin, $className) = pluginSplit($jobName);
		if ($plugin) {
			$plugin = "{$plugin}.";
		}

		if (!class_exists($className)) {
			App::uses($className, "{$plugin}Job");
		}

		if (empty($args)) {
			return new $className();
		}

		if (!class_exists('ReflectionClass')) {
			return null;
		}

		$ref = new ReflectionClass($className);
		return $ref->newInstanceArgs($args);
	}

/**
 * Enqueues Jobs using DJJob
 *
 * Note that all Jobs enqueued using this system must extend the base CakeJob
 * class which is included in this plugin
 *
 * @param Job $job
 * @param string $queue
 * @param string $run_at
 * @return boolean True if enqueue is successful, false on failure
 */
	public function enqueue($job, $queue = "default", $run_at = null) {
		return DJJob::enqueue($job, $queue, $run_at);
	}

/**
 * Bulk Enqueues Jobs using DJJob
 *
 * @param array $jobs
 * @param string $queue
 * @param string $run_at
 * @return boolean True if bulk enqueue is successful, false on failure
 */
	public function bulkEnqueue($jobs, $queue = "default", $run_at = null) {
		return DJJob::bulkEnqueue($jobs, $queue, $run_at);
	}

/**
 * Returns an array containing the status of a given queue
 *
 * @param string $queue
 * @return array
 **/
	public function status($queue = "default") {
		return DJJob::status($queue);
	}

}
