<?php
/**
 * Base Job class for all CakePHP-Based Jobs
 *
 * @package default
 * @author Jose Diaz-Gonzalez
 */
class CakeJob extends Object {
/**
 * Standard output stream.
 *
 * @var filehandle
 * @access public
 */
    var $_stdout;

/**
 * Standard error stream.
 *
 * @var filehandle
 * @access public
 */
    var $_stderr;

/**
 * An array containing the class names of the models this controller uses.
 *
 * @var array Array of model objects.
 * @access public
 */
    var $_modelNames = array();

/**
 * Used to create cached instances of models a controller uses.
 * When set to true, all models related to the controller will be cached.
 * This can increase performance in many cases.
 *
 * @var boolean
 * @access public
 */
    var $_persistModel = false;

/**
 * Internal reference to controller
 *
 * @var Controller
 **/
    var $_controller = null;

/**
 * Loads and instantiates models required by this controller.
 * If Controller::$persistModel; is true, controller will cache model instances on first request,
 * additional request will used cached models.
 * If the model is non existent, it will throw a missing database table error, as Cake generates
 * dynamic models for the time being.
 *
 * @param string $modelClass Name of model class to load
 * @param mixed $id Initial ID the instanced model class should have
 * @return mixed true when single model found and instance created, error returned if model not found.
 * @access public
 */
    function loadModel($modelClass, $id = null) {
        $cached = false;
        $object = null;
        list($plugin, $modelClass) = pluginSplit($modelClass, true, null);

        if ($this->_persistModel === true) {
            $cached = $this->_persist($modelClass, null, $object);
        }

        if (($cached === false)) {
            $this->_modelNames[] = $modelClass;

            if (!PHP5) {
                $this->{$modelClass} =& ClassRegistry::init(array(
                    'class' => $plugin . $modelClass, 'alias' => $modelClass, 'id' => $id
                ));
            } else {
                $this->{$modelClass} = ClassRegistry::init(array(
                    'class' => $plugin . $modelClass, 'alias' => $modelClass, 'id' => $id
                ));
            }

            if (!$this->{$modelClass}) {
                $message = sprintf("Missing Model: %s", $modelClass);
                if (!empty($plugin)) {
                    $message .= sprintf(" in %s Plugin", substr($plugin, 0, -1));
                }
                throw new Exception($message);
            }

            if ($this->_persistModel === true) {
                $this->_persist($modelClass, true, $this->{$modelClass});
                $registry =& ClassRegistry::getInstance();
                $this->_persist($modelClass . 'registry', true, $registry->__objects, 'registry');
            }
        } else {
            $this->_persist($modelClass . 'registry', true, $object, 'registry');
            $this->_persist($modelClass, true, $object);
            $this->_modelNames[] = $modelClass;
        }

        return true;
    }

/**
 * Loads a Controller and attaches the appropriate models
 *
 * @param string $controllerClass Name of model class to load
 * @param array $modelNames Array of models to load onto controller
 * @return mixed true when single controller found and instance created, error returned if controller not found.
 * @access public
 **/
    function loadController($controllerClass = 'CakeDjjob.CakeDjjobDummies', $modelNames = array()) {
        list($plugin, $controllerClass) = pluginSplit($controllerClass, true, null);

        $loaded = false;
        if ($plugin . $controllerClass == 'CakeDjjob.CakeDjjobDummies') {
            if (!empty($this->_controller)) {
                $loaded = true;
            }
        } else {
            if (!empty($this->{$controllerClass})) {
                $loaded = true;
            }
        }

        if ($loaded) {
          $message = sprintf("%s Controller", $controllerClass);
          if (!empty($plugin)) {
              $message .= sprintf(" in %s Plugin", substr($plugin, 0, -1));
          }
          throw new Exception(sprintf("%s is already loaded", $message));
        }

        if (!class_exists('Controller')) {
            App::import('Core', 'Controller');
        }
        if (!class_exists($controllerClass)) {
            App::import('Controller', $plugin . $controllerClass);
        }

        $controllerClassName = $controllerClass . 'Controller';
        $controller =& new $controllerClassName();
        $controller->constructClasses();
        $controller->startupProcess();

        foreach ($modelNames as $modelName) {
            $controller->loadModel($modelName);
        }

        if ($plugin . $controllerClass == 'CakeDjjob.Dummy') {
            $this->_controller = &$controller;
        } else {
            $this->{$controllerClass} = &$controller;
        }
        return true;
    }

/**
* Loads a Component
 *
 * @param string $componentClass Name of model class to load
 * @return void
 * @access public
 **/
    function loadComponent($componentClass) {
        App::import('Component', $componentClass);
        list($plugin, $componentClass) = pluginSplit($componentClass, true, null);
        $componentClassName = $componentClass . 'Component';
        $component =& new $componentClassName(null);

        if (empty($this->_controller)) {
            $this->loadController();
        }

        if (method_exists($component, 'initialize')) {
            $component->initialize($this->_controller);
        }

        if (method_exists($component, 'startup')) {
            $component->startup($this->_controller);
        }

        $this->{$componentClass} = &$component;
    }

/**
 * Outputs a single or multiple messages to stdout. If no parameters
 * are passed outputs just a newline.
 *
 * @param mixed $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @return integer Returns the number of bytes returned from writing to stdout.
 */
    function out($message = null, $newlines = 1) {
        if (is_array($message)) {
            $message = implode($this->nl(), $message);
        }
        return $this->stdout($message . $this->nl($newlines), false);
    }

/**
 * Outputs a single or multiple error messages to stderr. If no parameters
 * are passed outputs just a newline.
 *
 * @param mixed $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @access public
 */
    function err($message = null, $newlines = 1) {
        if (is_array($message)) {
            $message = implode($this->nl(), $message);
        }
        $this->stderr($message . $this->nl($newlines));
    }

/**
 * Outputs to the stdout filehandle.
 *
 * @param string $string String to output.
 * @param boolean $newline If true, the outputs gets an added newline.
 * @return integer Returns the number of bytes output to stdout.
 */
    function stdout($string, $newline = true) {
        if (empty($this->_stdout)) {
            $this->_stdout = fopen('php://stdout', 'w');
        }

        if ($newline) {
            return fwrite($this->_stdout, $string . "\n");
        } else {
            return fwrite($this->_stdout, $string);
        }
    }

/**
 * Outputs to the stderr filehandle.
 *
 * @param string $string Error text to output.
 * @access public
 */
    function stderr($string) {
        if (empty($this->_stderr)) {
            $this->_stderr = fopen('php://stderr', 'w');
        }

        fwrite($this->_stderr, $string);
    }

/**
 * Returns a single or multiple linefeeds sequences.
 *
 * @param integer $multiplier Number of times the linefeed sequence should be repeated
 * @return string
 */
    function nl($multiplier = 1, $print = false) {
        if ($print) return $this->stdout(str_repeat("\n", $multiplier), false);
        return str_repeat("\n", $multiplier);
    }

/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 * @param integer $newlines Number of newlines to pre- and append
 */
    function hr($newlines = 0) {
        $this->out(null, $newlines);
        $this->out('---------------------------------------------------------------');
        $this->out(null, $newlines);
    }

}