<?php 
//////////////////////////////////////////////////////////////////////////
// + $Id: validators.php,v 1.1 2005/08/11 01:55:38 Administrator Exp $
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
  * Purpose: Tort Validators
  * Used to validate data in Models.
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

/**
  * Not empty.
  */
define('VALID_NOT_EMPTY', '/.+/');

/**
  * Numbers [0-9] only.
  */
define('VALID_NUMBER', '/^[0-9]+$/');

/**
  * A valid email address.
  */
define('VALID_EMAIL', '/^([a-z0-9][a-z0-9_\-\.\+]*)@([a-z0-9][a-z0-9\.\-]{0,63}\.[a-z]{2,3})$/i');

/**
  * A valid year (1000-2999).
  */
define('VALID_YEAR', '/^[12][0-9]{3}$/');

?>