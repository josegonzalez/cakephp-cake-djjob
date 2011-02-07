<?php
App::import('Model', 'ConnectionManager');
App::import('Lib', 'CakeDjjob.cake_job', array(
    'file' => 'jobs' . DS . 'cake_job.php',
));
App::import('Vendor', 'Djjob.DJJob', array(
    'file' => 'DJJob.php',
));

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

    var $_queue = 'default';
    var $_count = 0;
    var $_sleep = 5;
    var $_max   = 5;

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

        if (!empty($this->params['connection'])) {
            $this->settings['connection'] = $this->params['connection'];
        }
        if (!empty($this->params['type'])) {
            $this->settings['type'] = $this->params['type'];
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
        $worker = new DJWorker(array(
            "queue" => $this->_queue,
            "count" => $this->_count,
            "sleep" => $this->_sleep,
            "max_attempts" => $this->_max,
        ));
        $worker->start();
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
Params:
    -connection <config>
        set db config <config>.
        default config: 'default'

    -type <name>
        pdo name for connection <type>.
        default type: 'mysql'

    -queue <name>
        queue <name> to pull jobs from.
        default queue: {$this->_queue}

    -count <number>
        run <number> of jobs to run before exiting (0 for unlimited).
        default count: {$this->_count}

    -sleep <name>
        sleep <number> seconds after finding no new jobs.
        default seconds: {$this->_sleep}

    -max <number>
        max <number> of retries for a given job.
        default retries: {$this->_max}

Commands:

    worker help
        shows this help message.

    schema run
        runs jobs in the system
TEXT;
        $this->out($help);
        $this->_stop();
    }
}
