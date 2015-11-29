<?php
App::uses('AppShell', 'Console/Command');

class CleanupTask extends AppShell {

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
			__d('cake_djjob', 'cleans a job queue')
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
		));
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		Configure::write('debug', $this->params['debug']);

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

		if (empty($this->params['date'])) {
			$this->params['date'] = date('Y-m-d H:i:s');
		} else {
			$this->params['date'] = date('Y-m-d H:i:s', strtotime($this->params['date']));
		}

		$conditions = array(
			$column . ' <=' => $this->params['date'],
			'not' => array($column => null),
		);
		if (!empty($this->params['queue'])) {
			$conditions['Job.queue'] = $this->params['queue'];
		}

		$JobModel = ClassRegistry::init('Job');
		$jobs = $JobModel->find('all', compact('conditions'));
		foreach ($jobs as $job) {
			switch ($this->_action) {
				case 'unlock':
					$job['Job']['locked_at'] = null;
					$job['Job']['locked_by'] = null;
					if ($this->params['save']) {
						$JobModel->save($job);
					}
				break;
				case 'clean':
					if ($this->params['save']) {
						$JobModel->delete($job['Job']['id']);
					}
				break;
			}
		}

		$this->out(sprintf('%d %s%s',
			count($jobs),
			($this->params['save'])? '':'not', " {$this->params['action']}ed\n"
		));
	}

}
