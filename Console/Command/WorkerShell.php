<?php
App::uses('AppShell', 'Console/Command');
App::uses('ConnectionManager', 'Model');
App::uses('CakeJob', 'CakeDjjob.Job');
App::uses('DJJob', 'Djjob.Vendor');

/**
 * Convenience method to unserialize CakeDjjob classes properly
 *
 * Uses _ (underscore) to denote plugins, since php classnames
 * cannot use periods
 *
 * @package default
 */
function unserialize_jobs($className) {
	$plugin = null;
	if (strstr($className, '_')) {
		list($plugin, $className) = explode('_', $className);
	}

	if (empty($className)) {
		$className = $plugin;
		$plugin = null;
	}

	if (!empty($plugin)) {
		$plugin = "{$plugin}.";
	}

	App::uses($className, "{$plugin}Job");
}

/**
 * Worker runs jobs created by the DJJob system.
 *
 * @package job_workers
 */
class WorkerShell extends Shell {

/**
 * Override startup
 *
 * @access public
 */
	function startup() {
		parent::startup();
		ini_set('unserialize_callback_func', 'unserialize_jobs');
		$connection = ConnectionManager::getDataSource($this->args['connection']);

		if ($this->args['type'] == 'mysql') {
			DJJob::configure(
				implode(';', array(
					"{$this->args['type']}:host={$connection->config['host']}",
					"dbname={$connection->config['database']}",
					"port={$connection->config['port']}",
				)), array(
					'mysql_user' => $connection->config['login'],
					'mysql_pass' => $connection->config['password']
				)
			);
		} else {
			DJJob::configure(
				implode(';', array(
					"{$this->args['type']}:host={$connection->config['host']}",
					"dbname={$connection->config['database']}",
					"port={$connection->config['port']}",
					"user={$connection->config['login']}",
					"password={$connection->config['password']}"
				))
			);
		}
	}

	function run() {
		Configure::write('debug', $this->args['debug']);
		if (empty($this->args['queue'])) {
			$this->cakeError('error', array(array(
				'code' => '', 'name' => '',
				'message' => 'No queue set'
			)));
		}

		$worker = new DJWorker(array(
			"queue" => $this->args['queue'],
			"count" => $this->args['count'],
			"sleep" => $this->args['sleep'],
			"max_attempts" => $this->args['max'],
		));
		$worker->start();
	}

	function status() {
		Configure::write('debug', $this->args['debug']);
		if (empty($this->args['queue'])) {
			$this->cakeError('error', array(array(
				'code' => '', 'name' => '',
				'message' => 'No queue set'
			)));
		}

		$status = DJJob::status($this->args['queue']);
		foreach ($status as $name => $count) {
			$this->out(sprintf("%s Jobs: %d", Inflector::humanize($name), $count));
		}
	}

	function cleanup() {
		Configure::write('debug', $this->args['debug']);

		if ($this->params['action'] == 'clean') {
			$column = 'failed_at';
		} elseif ($this->params['action'] == 'unlock') {
			$column = 'locked_at';
		} else {
			$this->cakeError('error', array(array(
				'code' => '', 'name' => '',
				'message' => 'No column to filter by is set'
			)));
		}

		if (empty($this->args['date'])) {
			$this->args['date'] = date('Y-m-d H:i:s');
		} else {
			$this->args['date'] = date('Y-m-d H:i:s', strtotime($this->args['date']));
		}

		$conditions = array(
			$column . ' <=' => $this->args['date'],
			'not' => array($column => null),
		);
		if (!empty($this->args['queue'])) {
			$conditions['Job.queue'] = $this->args['queue'];
		}

		$JobModel = ClassRegistry::init('Job');
		$jobs = $JobModel->find('all', compact('conditions'));
		foreach ($jobs as $job) {
			switch ($this->_action) {
				case 'unlock':
					$job['Job']['locked_at'] = null;
					$job['Job']['locked_by'] = null;
					if ($this->args['save']) $JobModel->save($job);
				break;
				case 'clean':
					if ($this->args['save']) $JobModel->delete($job['Job']['id']);
				break;
			}
		}

		$this->out(sprintf('%d %s%s', count($jobs), ($this->args['save'])? '':'not', " {$this->_action}ed\n"));
	}

/**
 * Override main() for help message hook
 *
 * @return void
 */
	public function main() {
		$this->out(__d('cake_djjob', '<info>CakeDjjob Worker Shell</info>'));
		$this->hr();
		$this->out(__d('cake_djjob', '[R]un jobs in the system'));
		$this->out(__d('cake_djjob', '[S]tatus of system'));
		$this->out(__d('cake_djjob', '[C]leans a job queue'));
		$this->out(__d('cake_djjob', '[Q]uit'));

		$choice = strtolower($this->in(__d('cake_djjob', 'What would you like to do?'), array('R', 'S', 'C', 'Q')));
		switch ($choice) {
			case 'r':
				$this->run();
			break;
			case 's':
				$this->status();
			break;
			case 'c':
				$this->cleanup();
			break;
			case 'q':
				exit(0);
			break;
			default:
				$this->out(__d('cake_djjob', 'You have made an invalid selection. Please choose a command to execute by entering R, S, C, or Q.'));
		}
		$this->hr();
		$this->main();
	}

/**
 * Get and configure the Option parser
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description(
			__d('cake_djjob', 'The Worker Shell runs jobs created by the DJJob system.')
		);
		$parser->addOptions(array(
			'connection' => array(
				'help' => __('Set db config'),
				'default' => 'default',
			),
			'type' => array(
				'help' => __('PDO name for connection <type>'),
				'default' => 'mysql',
			),
			'debug' => array(
				'help' => __('Set debug level dynamically for running jobs'),
				'default' => 0,
				'choices' => array(0, 1, 2)
			),
			'queue' => array(
				'help' => __('Queue <name> to pul jobs from'),
				'default' => 'default',
			),
		));

		$parser->addSubcommand('run', array(
			'help' => __d('cake_djjob', 'runs jobs in the system'),
			'parser' => array(
				'options' => array(
					'count' => array(
						'help' => __('Run <number> of jobs before exiting (0 for unlimited)'),
						'default' => $this->defaults['count'],
					),
					'sleep' => array(
						'help' => __('Sleep <number> seconds after finding no jobs'),
						'default' => 5,
					),
					'max' => array(
						'help' => __('Max <number> of retries for a given job'),
						'default' => 5,
					),
    			),
			),
		));

		$parser->addSubcommand('status', array(
			'help' => __d('cake_djjob', 'returns the status of a job queue'),
		));

		$parser->addSubcommand('cleanup', array(
			'help' => __d('cake_djjob', 'cleans a job queue'),
			'parser' => array(
				'options' => array(
					'action' => array(
						'help' => __('Action to perform on cleanup task'),
						'default' => 'clean',
						'choices' => array('clean', 'unlock')
					),
					'date' => array(
						'help' => __('Date offset'),
						'default' => null,
					),
					'save' => array(
						'help' => __('Allow cleanup to modify database'),
						'default' => 1,
						'choices' => array(0, 1),
					),
    			),
			),
		));

		return $parser;
	}

}