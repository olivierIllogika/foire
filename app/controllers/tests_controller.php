<?php
//////////////////////////////////////////////////////////////////////////
// + $Id: tests_controller.php,v 1.1 2005/08/11 01:55:35 Administrator Exp $
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
//////////////////////////////////////////////////////////////////////////

/**
 *
 * @filesource 
 * @package cake
 * @subpackage cake.app.controllers
 * @version $Revision: 1.1 $
 * @modifiedby $LastChangedBy: phpnut $
 * @lastmodified $Date: 2005/08/11 01:55:35 $
 */

/**
 * 
 * @package cake
 * @subpackage cake.app.controllers
 */
class TestsController extends TestsHelper {

/**
 * Runs all library and application tests
 *
 */
	function test_all () 
	{
		$this->layout = null;
		require_once SCRIPTS.'test.php';
	}
}

?>
