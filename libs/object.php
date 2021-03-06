<?php
//////////////////////////////////////////////////////////////////////////
// + $Id: object.php,v 1.1 2005/08/11 01:55:38 Administrator Exp $
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
 * Purpose: Object
 * Allows for __construct to be used in PHP4.
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
 * @lastmodified $Date: 2005/08/11 01:55:38 $
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

uses('log');

/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 */
class Object
{
	/**
	 * Database connection, if available.
	 *
	 * @var DBO
	 */
	var $db = null;
	
	var $_log = null;

	/**
	 * A hack to support __construct() on PHP 4
	 * Hint: descendant classes have no PHP4 class_name() constructors,
	 * so this constructor gets called first and calls the top-layer __construct()
	 * which (if present) should call parent::__construct()
	 *
	 * @return Object
	 */
	function Object()
	{
		$this->db =& DboFactory::getInstance();
		$args = func_get_args();
		register_shutdown_function(array(&$this, '__destruct'));
		call_user_func_array(array(&$this, '__construct'), $args);
	}

	/**
	 * Class constructor, overridden in descendant classes.
	 */
	function __construct() {}

	/**
	 * Class destructor, overridden in descendant classes.
	 */
	function __destruct() {}

	/**
	 * Object-to-string conversion.
	 * Each class can override it as necessary.
	 *
	 * @return string This name of this class
	 */
	function toString()
	{
		return get_class($this);
	}

	/**
	 * API for logging events.
	 *
	 * @param string $msg Log message
	 * @param int $type Error type constant. Defined in /libs/log.php.
	 */
	function log ($msg, $type=LOG_ERROR)
	{
		if (!$this->_log)
		{
			$this->_log = new Log ();
		}

		switch ($type)
		{
			case LOG_DEBUG:
				return $this->_log->write('debug', $msg);
			default:
				return $this->_log->write('error', $msg);
		}
	}
}

?>