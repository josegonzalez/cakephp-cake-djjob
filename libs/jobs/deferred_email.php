<?php
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
 * Deferred Email class
 *
 * Allows emails to be constructed in the following fashion:
 *
 *   $e = new DeferredEmail($emailAddress, $vars);
 *   $e->send();
 *
 * @package default
 */
class DeferredEmail extends CakeJob {

/**
 * Whether email was sent or not
 *
 * @var boolean
 */
    protected $_sent = false;

/**
 * True if email was cancelled, false otherwise
 *
 * @var boolean
 */
    protected $_canceled = false;

/**
 * Email address of recipient
 *
 * @var string
 */
    protected $_email = null;

/**
 * Variables to be set for emailing
 *
 * @var array
 */
    protected $_vars = array();

/**
 * Test mode for emails
 *
 * @var boolean
 */
    protected $_test = false;

/**
 * Template for the view
 *
 * @var string
 */
    protected $_template = 'default';

/**
 * Message for the email
 *
 * @var string
 */
    protected $_message = null;

/**
 * Constructs the initial email object
 *
 * @param string $email 
 * @param array $vars Array of variables for the email
 */
    public function __construct($email, $vars = array()) {
        $this->_test = Configure::read('debug') > 0;
        $this->_email = $email;
        $this->updateVars($vars);
    }

/**
 * Generic build method to defer expensive work until just before
 * an email is sent. This is useful in combination with sendLater()
 *
 * @return void
 */
    protected function build() {
        // do nothing in most cases
    }

/**
 * Allow an email to be canceled before it's sent.
 * Can be used in the build() step
 *
 * @return void
 */
    public function cancel() {
        $this->_canceled = true;
    }

/**
 * Send step of email
 *
 * @return void
 */
    public function send() {
        if ($this->_sent) {
            throw new Exception("This " . get_class($this) . " was already sent");
        }

        $this->build(); // perform expensive work as late as possible

        if ($this->_canceled) return false;

        // Convert booleans to ints, otherwise the signature will be incorrect
        foreach ($this->_vars as &$var)
            if (is_bool($var)) $var = ($var) ? 1 : 0;

        $this->_vars['to'] = $this->_email;
        if ($this->_test) {
            $this->_vars['to'] = Configure::read('Email.test');
            if (!$this->_vars['to']) {
                $this->_vars['to'] = 'mail@example.com';
            }
        }

        if (!isset($this->_vars['from'])) {
            $this->_vars['from'] = Configure::read('Email.from');
        }
        if (!isset($this->_vars['replyTo'])) {
            $this->_vars['replyTo'] = $this->_vars['from'];
        }

        if (!isset($this->_vars['template'])) {
            $this->_vars['template'] = $this->_template;
        }

        if (!isset($this->_vars['sendAs'])) {
            $this->_vars['sendAs'] = 'both';
        }

        try {
            $this->loadComponent('Email');
            $this->Email->_set($this->_vars);
            if (isset($this->_vars['variables'])) {
                $this->_internals['controller']->set($this->_vars['variables']);
            }
            $this->_sent = $this->Email->send($this->_message);
        } catch (Exception $e) {
            $this->_sent = false;
            $this->sendLater(date('Y-m-d H:i:s', strtotime("+1 minute")));
        }

        return $this->_sent;
    }

/**
 * Enables requeing of an email
 *
 * @param datetime $send_at MySQL-compatible datetime
 * @param string $queue Name of queue
 * @return void
 */
    public function sendLater($send_at = null, $queue = "email") {
        DJJob::enqueue($this, $queue, $send_at);
    }

/**
 * Handles merging of current email variables, as well as setting
 * public properties for later ease of usage
 *
 * @param array $vars
 * @return void
 */
    protected function updateVars($vars) {
        $this->_vars = array_merge($this->_vars, $vars);

        if (isset($this->_vars['variables'])) {
            foreach ($this->_vars['variables'] as $name => $value) {
                $this->$name = $value;
            }
        }
    }

/**
 * Allow emails to be sent in a delayed fashion via
 * CakeDjjob
 *
 * @return void
 */
    public function perform() {
        $this->send();
    }

}