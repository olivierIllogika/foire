#!/usr/local/bin/php
<?php
//////////////////////////////////////////////////////////////////////////
// + $Id: bake.php,v 1.1 2005/08/11 01:55:40 Administrator Exp $
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
  * Enter description here...
  * 
  * @filesource 
  * @author CakePHP Authors/Developers
  * @copyright Copyright (c) 2005, CakePHP Authors/Developers
  * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.scripts
  * @since CakePHP v 0.2.9
  * @version $Revision: 1.1 $
  * @modifiedby $LastChangedBy: phpnut $
  * @lastmodified $Date: 2005/08/11 01:55:40 $
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
  * START-UP
  */
define ('DS', DIRECTORY_SEPARATOR);
define ('ROOT', dirname(dirname(__FILE__)).DS);

require (ROOT.'config'.DS.'paths.php');
require (LIBS.'basics.php');
uses ('bake');

$waste = array_shift($argv);
$product = array_shift($argv);

$bake = new Bake ($product, $argv);

?>