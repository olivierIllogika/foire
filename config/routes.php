<?php
//////////////////////////////////////////////////////////////////////////
// + $Id: routes.php,v 1.2 2005/08/15 00:25:00 Administrator Exp $
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
//////////////////////////////////////////////////////////////////////////

/**
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect 
 * different urls to chosen controllers and their actions (functions).
 * 
 * @package cake
 * @subpackage cake.config
 */

/**
 * Here, we are connecting '/' (base path) to controller called 'Pages', 
 * its action called 'display', and we pass a param to select the view file 
 * to use (in this case, /app/views/pages/home.thtml)...
 */
$Route->connect ('/', array('controller'=>'Journees', 'action'=>'index', 'index'));

/**
 * ...and connect the rest of 'Pages' controller's urls.
 */
$Route->connect ('/pages/*', array('controller'=>'Pages', 'action'=>'display'));

/**
 * Then we connect url '/test' to our test controller. This is helpfull in
 * developement.
 */
$Route->connect ('/test', array('controller'=>'Tests', 'action'=>'test_all'));

?>
