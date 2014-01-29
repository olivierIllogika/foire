<?php
//////////////////////////////////////////////////////////////////////////
// + $Id: index.php,v 1.1 2005/08/11 01:55:40 Administrator Exp $
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
 * Purpose: Dispatch
 * The main "loop"
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.public
 * @since CakePHP v 0.2.9
 * @version $Revision: 1.1 $
 * @modifiedby $LastChangedBy: phpnut $
 * @lastmodified $Date: 2005/08/11 01:55:40 $
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

$url = empty($_GET['url'])? null: $_GET['url'];

session_start();
header('Content-Type: text/html; charset=utf-8');

/**
 * Get Cake's root directory
 */
if (!defined('DS'))
{
/**
 * Enter description here...
 *
 */
	define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('ROOT'))
{
/**
 * Enter description here...
 *
 */
	define('ROOT', dirname(dirname(__FILE__)).DS);
}

if (strpos($url, 'ccss/') === 0)
{
	include ROOT.'public'.DS.'css.php';
	die;
}
	
/**
 * Configuration, directory layout and standard libraries
 */
require_once ROOT.'config/core.php';
require_once ROOT.'config/paths.php';
require_once ROOT.'libs/basics.php';
require_once ROOT.'libs/log.php';
require_once ROOT.'libs/object.php';
require_once ROOT.'libs/neat_array.php';
require_once ROOT.'libs/inflector.php';

require_once ROOT.'modules/smtpcfg.php';
require_once ROOT.'config/school.php';

DEBUG? error_reporting(E_ALL & ~E_STRICT): error_reporting(0);

$TIME_START = getMicrotime();

uses('folder', 'dispatcher', 'dbo_factory');

config('tags', 'database');

if (class_exists('DATABASE_CONFIG'))
{
    $host = $_SERVER["HTTP_HOST"];
    if (strpos($host, 'poly') !== false)
    {
        $DB = DboFactory::getInstance('poly');
        School::$instance = new SchoolPoly();
    }
    elseif (strpos($host, 'ets') !== false)
    {
        $DB = DboFactory::getInstance('ets');
        School::$instance = new SchoolETS();
    }
    else
    {
        $DB = DboFactory::getInstance('localhost');
        School::$instance = new SchoolETS();
    }
    loadModels();
}

//RUN THE SCRIPT
	if(isset($_GET['url']) && $_GET['url'] === 'favicon.ico')
	{
	}else{
		$DISPATCHER = new Dispatcher ();
		$DISPATCHER->dispatch($url);
	}
//CLEANUP
if (DEBUG) echo "<!-- ". round(getMicrotime() - $TIME_START, 2) ."s -->";

?>
