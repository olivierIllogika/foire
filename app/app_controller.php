<?php
//////////////////////////////////////////////////////////////////////////
// + $Id: app_controller.php,v 1.4 2005/09/04 19:15:40 Administrator Exp $
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
 * @subpackage cake.app
 * @version $Revision: 1.4 $
 * @modifiedby $LastChangedBy: phpnut $
 * @lastmodified $Date: 2005/09/04 19:15:40 $
 */

/**
 * This file is application-wide controller file. You can put all 
 * application-wide controller-related methods here.
 *
 * Add your application-wide methods in the class below, your controllers 
 * will inherit them.
 * 
 * @package cake
 * @subpackage cake.app
 */

define("SECURITY_LEVEL_FORCE_LOGOUT", 1000);
define("SECURITY_LEVEL_FULL_ADMIN",	9);
define("SECURITY_LEVEL_ADMIN", 8);
define("SECURITY_LEVEL_MANAGMENT", 7);

define("SECURITY_LEVEL_GIVER", 4);
define("SECURITY_LEVEL_SELLER", 3);
define("SECURITY_LEVEL_PICKER", 2);

define("SECURITY_LEVEL_HIGHER_USER", 1);
define("SECURITY_LEVEL_BASIC_USER", 0);

class AppController extends Controller {

  var $pageTitle = "Foire aux Livres";

/*
  function __construct()
  {
    set_magic_quotes_runtime(0);
    session_start();
    parent::__construct();
  }
*/
  function sessionCheck($requiredSecurityLevel=SECURITY_LEVEL_BASIC_USER)
  {
    
    if (!isset($_SESSION['etudiant'])
        || !isset($_SESSION['etudiant']['id'])
        || $requiredSecurityLevel > $_SESSION['etudiant']['niveau'])
    {
      // page display not allowed
      
      if ($requiredSecurityLevel > SECURITY_LEVEL_BASIC_USER && 
					$requiredSecurityLevel < SECURITY_LEVEL_FORCE_LOGOUT)
      {
        // logged user accessing unauthorized page
        $data = addslashes("kicked! requis:$requiredSecurityLevel pour {$_SERVER['QUERY_STRING']}");
        $codebar = isset($_SESSION['etudiant']['id']) ? $_SESSION['etudiant']['id'] : 0;
        
    		$sql = "INSERT IGNORE INTO evetudiants (id,evenement,data) VALUES ($codebar,471,'$data')";

    		$this->db->query($sql);
      }

			// copying persistent data
      if (!empty($_SESSION['persistent']))
      {
        $persistent = $_SESSION['persistent'];
      }

			// destroying session
      unset($_SESSION);
      session_destroy();
      session_start();
      
      // restoring persistent data
      if (!empty($persistent))
      {
        $_SESSION['persistent'] = $persistent;
      }

			// redirecting to login page
      $this->redirect("/etudiants/login");
      return false;
    }
    
    return true;
  }

  function print_pre($arrayToPrint, $die=false)
  {
    echo '<pre>'.print_r($arrayToPrint,true).'</pre>';
    if ($die) die();
  }
}

?>
