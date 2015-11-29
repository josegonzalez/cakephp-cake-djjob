<?php
App::uses('ConnectionManager', 'Model');
App::uses('CakeJob', 'CakeDjjob.Job');
App::uses('DJJob', 'Djjob.Vendor');

/**
 * CakeDjjob Model Behavior
 *
 * Wrapper around DJJob library
 *
 * @copyright    Copyright 2011, Jose Diaz-Gonzalez. (http://josediazgonzalez.com)
 * @link         http://github.com/josegonzalez/cake_djjob
 * @package      cake_djjob
 * @subpackage   cake_djjob.models.behaviors
 * @license      MIT License (http://www.opensource.org/licenses/mit-license.php)
 **/
class CakeDjjobBehavior extends ModelBehavior {

/**
 * Contains configuration settings for use with individual model objects.
 * Individual model settings should be stored as an associative array,
 * keyed off of the model name.
 *
 * @var array
 * @access public
 * @see Model::$alias
 */
	public $settings = array(
		'connection' => 'default',
		'type' => 'mysql',
	);

/**
 * Initiate CakeDjjob Behavior
 *
 * @param Model $Model Model using the behavior
 * @param array $config
 * @return void
 * @access public
 */
	public function setup(Model $model, $config = array()) {
		$this->settings = array_merge($this->settings, $config);
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
 * @param Model $Model Model using the behavior
 * @param string $jobName Name of job being loaded
 * @param mixed $argument Some argument to pass to the job
 * @param mixed ... etc.
 * @return mixed Job instance if available, null otherwise
 */
	public function load(Model $Model) {
		$args = func_get_args();
		array_shift($args);

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
 * @param Model $Model Model using the behavior
 * @param Job $job
 * @param string $queue
 * @param string $runAt
 * @return bool True if enqueue is successful, false on failure
 */
	public function enqueue(Model $Model, $job, $queue = "default", $runAt = null) {
		return DJJob::enqueue($job, $queue, $runAt);
	}

/**
 * Bulk Enqueues Jobs using DJJob
 *
 * @param Model $Model Model using the behavior
 * @param array $jobs
 * @param string $queue
 * @param string $runAt
 * @return bool True if bulk enqueue is successful, false on failure
 */
	public function bulkEnqueue(Model $Model, $jobs, $queue = "default", $runAt = null) {
		return DJJob::bulkEnqueue($jobs, $queue, $runAt);
	}

/**
 * Returns an array containing the status of a given queue
 *
 * @param Model $Model Model using the behavior
 * @param string $queue
 * @return array
 **/
	public function status(Model $Model, $queue = "default") {
		return DJJob::status($queue);
	}

}
