<?php
class RunTask extends AppShell {

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
			__d('cake_djjob', 'runs jobs in the system')
		)->addOptions(array(
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
			'count' => array(
				'help' => __('Run <number> of jobs before exiting (0 for unlimited)'),
				'default' => '100',
			),
			'sleep' => array(
				'help' => __('Sleep <number> seconds after finding no jobs'),
				'default' => 5,
			),
			'max' => array(
				'help' => __('Max <number> of retries for a given job'),
				'default' => 5,
			),
		));
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		Configure::write('debug', $this->params['debug']);
		if (empty($this->params['queue'])) {
			$this->cakeError('error', array(array(
				'code' => '', 'name' => '',
				'message' => 'No queue set'
			)));
		}

		$worker = new DJWorker(array(
			"queue" => $this->params['queue'],
			"count" => $this->params['count'],
			"sleep" => $this->params['sleep'],
			"max_attempts" => $this->params['max'],
		));
		$worker->start();
	}

}