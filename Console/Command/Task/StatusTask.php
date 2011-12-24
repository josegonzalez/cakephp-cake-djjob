<?php
class StatusTask extends AppShell {

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
			__d('cake_djjob', 'returns the status of a job queue')
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

		$status = DJJob::status($this->params['queue']);
		foreach ($status as $name => $count) {
			$this->out(sprintf("%s Jobs: %d", Inflector::humanize($name), $count));
		}
	}

}