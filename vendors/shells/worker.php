<?php
if (!class_exists('ConnectionManager')) {
    App::import('Model', 'ConnectionManager');
}
if (!class_exists('CakeJob')) {
    App::import('Lib', 'CakeDjjob.cake_job', array(
        'file' => 'jobs' . DS . 'cake_job.php',
    ));
}
if (!class_exists('DJJob')) {
    App::import('Vendor', 'Djjob.DJJob', array(
        'file' => 'DJJob.php',
    ));
}

/**
 * Uses _ (underscore) to denote plugins, since php classnames
 * cannot use periods, and the unserialize_jobs method
 *
 * @package default
 * @author Jose Diaz-Gonzalez
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
        $plugin = $plugin . '.';
    }

    App::import('Lib', $plugin . 'jobs/' . Inflector::underscore($className));
}

/**
 * Worker runs jobs created by the DJJob system.
 *
 * @package       job_workers
 */
class WorkerShell extends Shell {

    var $settings = array(
        'connection'=> 'default',
        'type'      => 'mysql',
    );

    var $_queue = null;
    var $_count = 0;
    var $_sleep = 5;
    var $_max   = 5;
    var $_debug = 0;
    var $_action = 0;
    var $_column = 0;
    var $_date = null;
    var $_save = true;

/**
 * Override initialize
 *
 * @access public
 */
    function initialize() {
        $this->_welcome();
        $this->out('Jobs Worker Shell');
        $this->hr();
    }

/**
 * Override startup
 *
 * @access public
 */
    function startup() {
        if (!empty($this->params['queue'])) {
            $this->_queue   = $this->params['queue'];
        }
        if (!empty($this->params['count'])) {
            $this->_count   = $this->params['count'];
        }
        if (!empty($this->params['sleep'])) {
            $this->_sleep   = $this->params['sleep'];
        }
        if (!empty($this->params['max'])) {
            $this->_max     = $this->params['max'];
        }
        if (!empty($this->params['debug'])) {
            if ($this->params['debug'] == 'false') {
                $this->params['debug'] = 0;
            }
            if ($this->params['debug'] == 'true') {
                $this->params['debug'] = 1;
            }
            $this->_debug   = (int) $this->params['debug'];
        }

        if (!empty($this->params['save'])) {
            if ($this->params['save'] == 'false') {
                $this->params['save'] = 0;
            }
            if ($this->params['save'] == 'true') {
                $this->params['save'] = 1;
            }
            $this->_save   = (int) $this->params['save'];
        }
        if (!empty($this->params['connection'])) {
            $this->settings['connection'] = $this->params['connection'];
        }
        if (!empty($this->params['type'])) {
            $this->settings['type'] = $this->params['type'];
        }

        if (!empty($this->params['action'])) {
            if (!in_array($this->params['action'], array('clean', 'unlock'))) {
                $this->cakeError('error', array(array(
                    'code' => '', 'name' => '',
                    'message' => 'Invalid action ' . $this->params['action']
                )));
            }
            $this->_action  = $this->params['action'];
            if ($this->_action == 'clean') {
                $this->_column = 'failed_at';
            } elseif ($this->_action == 'unlock') {
                $this->_column = 'locked_at';
            }
        }

        if (empty($this->params['date'])) {
            $this->_date = date('Y-m-d H:i:s');
        } else {
            $this->_date = date('Y-m-d H:i:s', strtotime($this->params['date']));
        }

        ini_set('unserialize_callback_func', 'unserialize_jobs');
        $connection = ConnectionManager::getDataSource($this->settings['connection']);

        if ($this->settings['type'] == 'mysql') {
            DJJob::configure(
                implode(';', array(
                    "{$this->settings['type']}:host={$connection->config['host']}",
                    "dbname={$connection->config['database']}",
                    "port={$connection->config['port']}",
                )),
                $connection->config['login'],
                $connection->config['password']
            );
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
 * Override main
 *
 * @access public
 */
    function main() {
        $this->help();
    }

    function run() {
        Configure::write('debug', $this->_debug);
        if (empty($this->_queue)) {
            $this->cakeError('error', array(array(
                'code' => '', 'name' => '',
                'message' => 'No queue set'
            )));
        }

        $worker = new DJWorker(array(
            "queue" => $this->_queue,
            "count" => $this->_count,
            "sleep" => $this->_sleep,
            "max_attempts" => $this->_max,
        ));
        $worker->start();
    }

    function status() {
        Configure::write('debug', $this->_debug);
        if (empty($this->_queue)) {
            $this->cakeError('error', array(array(
                'code' => '', 'name' => '',
                'message' => 'No queue set'
            )));
        }

        $status = DJJob::status($this->_queue);
        foreach ($status as $name => $count) {
            $this->out(sprintf("%s Jobs: %d", Inflector::humanize($name), $count));
        }
    }

    function cleanup() {
        Configure::write('debug', $this->_debug);
        if (empty($this->_column)) {
            $this->cakeError('error', array(array(
                'code' => '', 'name' => '',
                'message' => 'No column to filter by is set'
            )));
        }

        $conditions = array(
            $this->_column . ' <=' => $this->_date,
            'not' => array($this->_column => null),
        );
        if (!empty($this->_queue)) {
            $conditions['Job.queue'] = $this->_queue;
        }

        $JobModel = ClassRegistry::init('Job');
        $jobs = $JobModel->find('all', array('conditions' => $conditions));
        foreach ($jobs as $job) {
            switch ($this->_action) {
                case 'unlock':
                    $job['Job']['locked_at'] = null;
                    $job['Job']['locked_by'] = null;
                    $JobModel->save($job);
                    if ($this->_save) $JobModel->save($job);
                break;
                case 'clean':
                    if ($this->_save) $JobModel->delete($job['Job']['id']);
                break;
            }
        }

        $this->out(sprintf('%d %s%s', count($jobs), ($this->_save)? '':'not', " {$this->_action}ed\n"));
    }

/**
 * Displays help contents
 *
 * @access public
 */
    function help() {
        $help = <<<TEXT
The Worker Shell runs jobs created by the DJJob system.
---------------------------------------------------------------
Usage: cake worker <command> <arg1> <arg2>...
---------------------------------------------------------------
Global Params:
    -connection <config>
        set db config <config>.
        default config: 'default'

    -type <name>
        pdo name for connection <type>.
        default type: 'mysql'

    -debug <boolean>
        set debug level dynamically for running jobs
        default value: {$this->_debug}
        valid values: true, false, 0, 1, 2

    -queue <name>
        queue <name> to pull jobs from.
        default queue: {$this->_queue}

Run Params:
    -count <number>
        run <number> of jobs to run before exiting (0 for unlimited).
        default count: {$this->_count}

    -sleep <name>
        sleep <number> seconds after finding no new jobs.
        default seconds: {$this->_sleep}

    -max <number>
        max <number> of retries for a given job.
        default retries: {$this->_max}

Clean Params:
    -action <string>
        action to perform on cleanup task
        default value: null

    -date <string>
        date offset
        default value: {$this->_date}

    -save <boolean>
        allow cleanup to modify database
        default value: {$this->_save}
        valid values: true, false, 0, 1

Commands:

    worker help
        shows this help message.

    worker run
        runs jobs in the system

    worker status
        returns the status of a job queue

    worker cleanup
        returns the status of a job queue

TEXT;
        $this->out($help);
        $this->_stop();
    }
}
