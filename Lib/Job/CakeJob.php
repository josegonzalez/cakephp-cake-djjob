<?php
/**
 * Base Job class for all CakePHP-Based Jobs
 *
 * @package default
 * @author Jose Diaz-Gonzalez
 */

App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Controller', 'Controller');
class CakeJob extends Object {

/**
 * Internal array
 *
 * May contains the following keys
 *  stdout:         filehandle  Standard output stream.
 *  stderr:         filehandle  Standard error stream.
 *  modelNames:     array       An array containing the class names of the models this controller uses.
 *  persistModel:   boolean     Used to create cached instances of models a controller uses.
 *  controller:     object      Internal reference to a dummy controller
 *
 * @var string
 **/
	public $_internals = array();

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
	public function loadModel($modelClass, $id = null) {
		$cached = false;
		$object = null;
		list($plugin, $modelClass) = pluginSplit($modelClass, true, null);

		if (isset($this->_internals['persistModel']) && $this->_internals['persistModel'] === true) {
			$cached = $this->_persist($modelClass, null, $object);
		}

		if (($cached === false)) {
			$this->_internals['modelNames'][] = $modelClass;

			$this->{$modelClass} = ClassRegistry::init(array(
				'class' => $plugin . $modelClass, 'alias' => $modelClass, 'id' => $id
			));

			if (!$this->{$modelClass}) {
				$message = sprintf("Missing Model: %s", $modelClass);
				if (!empty($plugin)) {
					$message .= sprintf(" in %s Plugin", substr($plugin, 0, -1));
				}
				throw new Exception($message);
			}

			if (isset($this->_internals['persistModel']) && $this->_internals['persistModel'] === true) {
				$this->_persist($modelClass, true, $this->{$modelClass});
				$registry =& ClassRegistry::getInstance();
				$this->_persist($modelClass . 'registry', true, $registry->__objects, 'registry');
			}
		} else {
			$this->_persist($modelClass . 'registry', true, $object, 'registry');
			$this->_persist($modelClass, true, $object);
			$this->_internals['modelNames'][] = $modelClass;
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
	public function loadController($controllerClass = 'Controller', $modelNames = array()) {
		list($pluginName, $controllerClass) = pluginSplit($controllerClass, true, null);

		$pluginPath = '';
		if (!empty($pluginName)) {
			$pluginPath = $pluginName . '.';
		}

		$loaded = false;
		if ($pluginPath . $controllerClass == 'Controller') {
			if (!empty($this->_internals['controller'])) {
				$loaded = true;
			}
		} else {
			if (!empty($this->{$controllerClass})) {
				$loaded = true;
			}
		}

		if ($loaded) {
			$message = sprintf("%s Controller", $controllerClass);
			if (!empty($pluginName)) {
				$message .= sprintf(" in %s Plugin", substr($pluginName, 0, -1));
			}
			throw new Exception(sprintf("%s is already loaded", $message));
		}

		if (!($pluginPath . $controllerClass)) {
			return false;
		}

		$class = $controllerClass;
		if ($class != 'Controller') {
			$class = $controllerClass . 'Controller';
		}

		App::uses('AppController', 'Controller');
		App::uses($pluginName . 'AppController', $pluginPath . 'Controller');
		App::uses($class, $pluginPath . 'Controller');
		if (!class_exists($class)) {
			return false;
		}

		if (!$class) {
			return false;
		}
		$reflection = new ReflectionClass($class);
		if ($reflection->isAbstract() || $reflection->isInterface()) {
			return false;
		}
		$Controller = $reflection->newInstance(new CakeRequest, new CakeResponse);
		$Controller->constructClasses();
		$Controller->startupProcess();

		foreach ($modelNames as $modelName) {
			$Controller->loadModel($modelName);
		}

		if ($class == 'Controller') {
			$this->_internals['controller'] = &$Controller;
		} else {
			$this->{$controllerClass} = &$Controller;
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
	public function loadComponent($componentClass, $settings = array()) {
		if (empty($this->_internals['controller'])) {
			$this->loadController();
		}

		$components = ComponentCollection::normalizeObjectArray(array($componentClass => $settings));
		foreach ($components as $name => $properties) {
			$this->_internals['controller']->{$name} = $this->_internals['controller']->Components->load(
				$properties['class'],
				$properties['settings']
			);
			$this->{$name} = $this->_internals['controller']->{$name};
		}

		if (method_exists($this->_internals['controller']->{$name}, 'initialize')) {
			$this->_internals['controller']->{$name}->initialize(
				$this->_internals['controller']
			);
		}

		if (method_exists($this->_internals['controller']->{$name}, 'startup')) {
			$this->_internals['controller']->{$name}->startup(
				$this->_internals['controller']
			);
		}
	}

/**
 * Outputs a single or multiple messages to stdout. If no parameters
 * are passed outputs just a newline.
 *
 * @param mixed $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @return integer Returns the number of bytes returned from writing to stdout.
 */
	public function out($message = null, $newlines = 1) {
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
	public function err($message = null, $newlines = 1) {
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
	public function stdout($string, $newline = true) {
		if (empty($this->_internals['stdout'])) {
			$this->_internals['stdout'] = fopen('php://stdout', 'w');
		}

		if ($newline) {
			return fwrite($this->_internals['stdout'], $string . "\n");
		} else {
			return fwrite($this->_internals['stdout'], $string);
		}
	}

/**
 * Outputs to the stderr filehandle.
 *
 * @param string $string Error text to output.
 * @access public
 */
	public function stderr($string) {
		if (empty($this->_internals['stderr'])) {
			$this->_internals['stderr'] = fopen('php://stderr', 'w');
		}

		fwrite($this->_internals['stderr'], $string);
	}

/**
 * Returns a single or multiple linefeeds sequences.
 *
 * @param integer $multiplier Number of times the linefeed sequence should be repeated
 * @return string
 */
	public function nl($multiplier = 1, $print = false) {
		if ($print) return $this->stdout(str_repeat("\n", $multiplier), false);
		return str_repeat("\n", $multiplier);
	}

/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 * @param integer $newlines Number of newlines to pre- and append
 */
	public function hr($newlines = 0) {
		$this->out(null, $newlines);
		$this->out('---------------------------------------------------------------');
		$this->out(null, $newlines);
	}

}