<?php
//////////////////////////////////////////////////////////////////////////
// + $Id: bake.php,v 1.1 2005/08/11 01:55:37 Administrator Exp $
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * Creates controller, model, view files, and the required directories on demand.
 * Used by /scripts/bake.php.
 *
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 * @version $Revision: 1.1 $
 * @modifiedby $LastChangedBy: phpnut $
 * @lastmodified $Date: 2005/08/11 01:55:37 $
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Require needed libraries.
 */
uses('object', 'inflector');

/**
 * Bake class creates files in configured application directories. This is a
 * base class for /scripts/add.php.
 *
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
  */
class Bake extends Object {

	/**
	 * Standard input stream (php://stdin).
	 *
	 * @var resource
	 * @access private
	 */
	var $stdin = null;

	/**
	 * Standard output stream (php://stdout).
	 *
	 * @var resource
	 * @access private
	 */
	var $stdout = null;

	/**
	 * Standard error stream (php://stderr).
	 *
	 * @var resource
	 * @access private
	 */
	var $stderr = null;

	/**
	 * Counts actions taken.
	 *
	 * @var integer
	 * @access private
	 */
	var $actions = null;

	/**
	 * Decides whether to overwrite existing files without asking.
	 *
	 * @var boolean
	 * @access private
	 */
	var $dontAsk = false;

	/**
	 * Returns code template for PHP file generator.
	 *
	 * @param string $type
	 * @return string
	 * @access private
	 */
	function template ($type) {
		switch ($type) {
			case 'view': return "%s";
			case 'model': return "<?php\n\nclass %s extends AppModel {\n}\n\n?>";
			case 'action': return "\n\tfunction %s () {\n\t}\n";
			case 'ctrl': return "<?php\n\nclass %s extends %s {\n%s\n}\n\n?>";
			case 'helper': return "<?php\n\nclass %s extends AppController {\n}\n\n?>";
			case 'test': return '<?php

class %sTest extends TestCase {
	var $abc;

	// called before the tests
	function setUp() {
		$this->abc = new %s ();
	}

	// called after the tests
	function tearDown() {
		unset($this->abc);
	}
	
/*
	function testFoo () {
		$result = $this->abc->Foo();
		$expected = \'\';
		$this->assertEquals($result, $expected);
	}
*/
}

?>';
			default:
				return false;
		}
	}


	/**
	 * Baker's constructor method. Initialises bakery, and starts production.
	 *
	 * @param string $type
	 * @param array $names
	 * @access public
	 * @uses Bake::stdin Opens stream for reading.
	 * @uses Bake::stdout Opens stream for writing.
	 * @uses Bake::stderr Opens stream for writing.
	 * @uses Bake::newModel() Depending on the case, can create a new model.
	 * @uses Bake::newView() Depending on the case, can create a new view.
	 * @uses Bake::newController() Depending on the case, can create a new controller.
	 */
	function __construct ($type, $names) {

		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');

		// Output directory name
		fwrite($this->stderr, "\n".substr(ROOT,0,strlen(ROOT)-1).":\n".str_repeat('-',strlen(ROOT)+1)."\n");

		switch ($type) {

			case 'model':
			case 'models':
				foreach ($names as $model_name)
					$this->newModel($model_name);
			break;

			case 'controller':
			case 'ctrl':
				$controller = array_shift($names);

				$add_actions = array();
				foreach ($names as $action) {
					$add_actions[] = $action;
					$this->newView($controller, $action);
				}

				$this->newController($controller, $add_actions);
			break;

			case 'view':
			case 'views':
				$r = null;
				foreach ($names as $model_name) {
					if (preg_match('/^([a-z0-9_]+(?:\/[a-z0-9_]+)*)\/([a-z0-9_]+)$/i', $model_name, $r)) {
						$this->newView($r[1], $r[2]);
					}
				}
			break;
		}

		if (!$this->actions)
			fwrite($this->stderr, "Nothing to do, quitting.\n");
				
	}

	/**
	 * Creates new view in VIEWS/$controller/ directory.
	 *
	 * @param string $controller
	 * @param string $name
	 * @access private
	 * @uses Inflector::underscore() Underscores directory's name.
	 * @uses Bake::createDir() Creates new directory in views dir, named after the controller.
	 * @uses VIEWS
	 * @uses Bake::createFile() Creates view file.
	 * @uses Bake::template() Collects view template.
	 * @uses Bake::actions Adds one action for each run.
	 */
	function newView ($controller, $name) {
//		$controller = Inflector::pluralize($controller);
		$dir = Inflector::underscore($controller);
		$path = $dir.DS.strtolower($name).".thtml";
		$this->createDir(VIEWS.$dir);
		$fn = VIEWS.$path;
		$this->createFile($fn, sprintf($this->template('view'), "<p>Edit <b>app".DS."views".DS."{$path}</b> to change this message.</p>"));
		$this->actions++;
	}

	/**
	 * Creates new controller with defined actions, controller's test and helper
	 * with helper's test.
	 *
	 * @param string $name
	 * @param array $actions
	 * @access private
	 * @uses Inflector::pluralize()
	 * @uses Bake::makeController()
	 * @uses Bake::makeControllerTest()
	 * @uses Bake::makeHelper()
	 * @uses Bake::makeHelperTest()
	 * @uses Bake::actions Adds one action for each run.
	 */
	function newController ($name, $actions=array()) {
//	    $name = Inflector::pluralize($name);
		$this->makeController($name, $actions);
		$this->makeControllerTest($name);
		$this->makeHelper($name);
		$this->makeHelperTest($name);
		$this->actions++;
	}

	/**
	 * Creates new controller file with defined actions.
	 *
	 * @param string $name
	 * @param array $actions
	 * @return boolean
	 * @access private
	 * @uses Bake::makeControllerName() CamelCase for controller's name.
	 * @uses Bake::makeHelperName() CamelCase for helper's name. 
	 * @uses Bake::template() Controller's template.
	 * @uses Bake::getActions() Actions' templates to be included in the controller.
	 * @uses Bake::createFile() Creates controller's file.
	 * @uses Bake::makeControllerFn() Underscored name for controller's filename.
	 */
	function makeController ($name, $actions) {
		$ctrl = $this->makeControllerName($name);
		$helper = $this->makeHelperName($name);
		$body = sprintf($this->template('ctrl'), $ctrl, $helper, join('', $this->getActions($actions)));
		return $this->createFile($this->makeControllerFn($name), $body);
	}

	/**
	 * Returns controller's name in CamelCase.
	 *
	 * @param string $name
	 * @return string
	 * @access private
	 * @uses Inflector::camelize CamelCase for controller name.
	 */
	function makeControllerName ($name) {
		return Inflector::camelize($name).'Controller';
	}

	/**
	 * Returns a name for controller's file, underscored.
	 *
	 * @param string $name
	 * @return string
	 * @access private
	 * @uses Inflector::underscore() Underscore for controller's file name.
	 */
	function makeControllerFn ($name) {
		return CONTROLLERS.Inflector::underscore($name).'_controller.php';
	}

	/**
	 * Creates new test for a controller.
	 *
	 * @param string $name
	 * @return boolean
	 * @access private
	 * @uses CONTROLLER_TESTS
	 * @uses Inflector::underscore()
	 * @uses Bake::getTestBody()
	 * @uses Bake::makeControllerName()
	 * @uses Bake::createFile()
	 */
	function makeControllerTest ($name) {
		$fn = CONTROLLER_TESTS.Inflector::underscore($name).'_controller_test.php';
		$body = $this->getTestBody($this->makeControllerName($name));
		return $this->createFile($fn, $body);
	}

	/**
	 * Creates new helper.
	 *
	 * @param string $name
	 * @return boolean
	 * @access private
	 * @uses Bake::template()
	 * @uses Bake::makeHelperName()
	 * @uses Bake::createFile()
	 * @uses Bake::makeHelperFn()
	 */
	function makeHelper ($name) {
		$body = sprintf($this->template('helper'), $this->makeHelperName($name));
		return $this->createFile($this->makeHelperFn($name), $body);
	}

	/**
	 * Returns CamelCase name for a helper.
	 *
	 * @param string $name
	 * @return string
	 * @access private
	 * @uses Inflector::camelize()
	 */
	function makeHelperName ($name) {
		return Inflector::camelize($name).'Helper';
	}

	/**
	 * Underscores file name for a helper.
	 *
	 * @param string $name
	 * @return string
	 * @access private
	 * @uses HELPERS
	 * @uses Inflector::underscore()
	 */
	function makeHelperFn ($name) {
		return HELPERS.Inflector::underscore($name).'_helper.php';
	}

	/**
	 * Creates new test for a helper.
	 *
	 * @param string $name
	 * @return boolean
	 * @access private
	 * @uses HELPER_TESTS
	 * @uses Inflector::underscore()
	 * @uses Bake::getTestBody()
	 * @uses Bake::makeHelperName()
	 * @uses Bake::createFile()
	 */
	function makeHelperTest ($name) {
		$fn = HELPER_TESTS.Inflector::underscore($name).'_helper_test.php';
		$body = $this->getTestBody($this->makeHelperName($name));
		return $this->createFile($fn, $body);
	}

	/**
	 * Returns an array of actions' templates.
	 *
	 * @param array $as
	 * @return array
	 * @access private
	 * @uses Bake::template()
	 */
	function getActions ($as) {
		$out = array();
		foreach ($as as $a)
			$out[] = sprintf($this->template('action'), $a);
		return $out;
	}

	/**
	 * Returns a test template for given class.
	 *
	 * @param string $class
	 * @return string
	 * @access private
	 * @uses Bake::template()
	 */
	function getTestBody ($class) {
		return sprintf($this->template('test'), $class, $class);
	}

	/**
	 * Creates new model.
	 *
	 * @param string $name
	 * @access private
	 * @uses Bake::createFile()
	 * @uses Bake::getModelFn()
	 * @uses Bake::template()
	 * @uses Bake::getModelName()
	 * @uses Bake::makeModelTest()
	 * @uses Bake::actions
	 */
	function newModel ($name) {
		$this->createFile($this->getModelFn($name), sprintf($this->template('model'), $this->getModelName($name)));
		$this->makeModelTest ($name);
		$this->actions++;
	}

	/**
	 * Returns an underscored filename for a model.
	 *
	 * @param string $name
	 * @return string
	 * @access private
	 * @uses MODELS
	 * @uses Inflector::underscore()
	 */
	function getModelFn ($name) {
		return MODELS.Inflector::underscore($name).'.php';
	}

	/**
	 * Creates a test for a given model.
	 *
	 * @param string $name
	 * @return boolean
	 * @access private
	 * @uses MODEL_TESTS
	 * @uses Inflector::underscore()
	 * @uses Bake::getTestBody()
	 * @uses Bake::getModelName()
	 * @uses Bake::createFile()
	 */
	function makeModelTest ($name) {
		$fn = MODEL_TESTS.Inflector::underscore($name).'_test.php';
		$body = $this->getTestBody($this->getModelName($name));
		return $this->createFile($fn, $body);
	}

	/**
	 * Returns CamelCased name of a model.
	 *
	 * @param string $name
	 * @return string
	 * @access private
	 * @uses Inflector::camelize()
	 */
	function getModelName ($name) {
		return Inflector::camelize($name);
	}

	/**
	 * Creates a file with given path and contents.
	 *
	 * @param string $path
	 * @param string $contents
	 * @return boolean
	 * @access private
	 * @uses Bake::dontAsk
	 * @uses Bake::stdin
	 * @uses Bake::stdout
	 * @uses Bake::stderr
	 */
	function createFile ($path, $contents) {
		$shortPath = str_replace(ROOT,null,$path);

		if (is_file($path) && !$this->dontAsk) {
			fwrite($this->stdout, "File {$shortPath} exists, overwrite? (yNaq) "); 
			$key = trim(fgets($this->stdin));

			if ($key=='q') {
				fwrite($this->stdout, "Quitting.\n");
				exit;
			}
			elseif ($key=='a') {
				$this->dont_ask = true;
			}
			elseif ($key=='y') {
			}
			else {
				fwrite($this->stdout, "Skip   {$shortPath}\n");
				return false;
			}
		}

		if ($f = fopen($path, 'w')) {
			fwrite($f, $contents);
			fclose($f);
			fwrite($this->stdout, "Wrote   {$shortPath}\n");
//			debug ("Wrote {$path}");
			return true;
		}
		else {
			fwrite($this->stderr, "Error! Couldn't open {$shortPath} for writing.\n");
//			debug ("Error! Couldn't open {$path} for writing.");
			return false;
		}
	}

	/**
	 * Creates a directory with given path.
	 *
	 * @param string $path
	 * @return boolean
	 * @access private
	 * @uses Bake::stdin
	 * @uses Bake::stdout
	 */
	function createDir ($path) {
		if (is_dir($path))
			return true;

		$shortPath = str_replace(ROOT, null, $path);

		if (mkdir($path)) {
			fwrite($this->stdout, "Created {$shortPath}\n");
//			debug ("Created {$path}");
			return true;
		}
		else {
			fwrite($this->stderr, "Error! Couldn't create dir {$shortPath}\n");
//			debug ("Error! Couldn't create dir {$path}");
			return false;
		}
	}

}

?>
