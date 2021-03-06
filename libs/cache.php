<?php
//////////////////////////////////////////////////////////////////////////
// + $Id: cache.php,v 1.1 2005/08/11 01:55:37 Administrator Exp $
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
  * Purpose: Cache
  * Description:
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
  * Enter description here...
  *
  */
uses('model');

/**
  * Enter description here...
  *
  * @package cake
  * @subpackage cake.libs
  * @since CakePHP v 0.2.9
  */
class Cache extends Model {

/**
  * Identifier. Either an MD5 string or NULL.
  *
  * @var unknown_type
  */
	var $id = null;

/**
  * Content container for cache data.
  *
  * @var unknown_type
  */
	var $data = null;

/**
  * Content to be cached.
  *
  * @var unknown_type
  */
	var $for_caching = null;

/**
  * Name of the database table used for caching.
  *
  * @var unknown_type
  */
	var $use_table = 'cache';

/**
  * Constructor. Generates an md5'ed id for internal use. Calls the constructor on Model as well.
  *
  * @param unknown_type $id
  */
	function __construct ($id) {
		$this->id = (md5($id));
		parent::__construct($this->id);
	}

/**
  * Returns this object's id after setting it. If no $id is given then $this->id is returned.
  *
  * @param unknown_type $id
  * @return unknown
  */
	function id ($id=null) {
		if (!$id) return $this->id;
		return ($this->id = $id);
	}

/**
  * Save $content in cache for $keep_for seconds.
  *
  * @param string $content Content to keep in cache.
  * @param int $keep_for Number of seconds to keep data in cache.
  * @return unknown
  */
	function remember ($content, $keep_for=CACHE_PAGES_FOR) {
		$data = addslashes($this->for_caching.$content);
		$expire = date("Y-m-d H:i:s",time()+($keep_for>0? $keep_for: 999999999));
		return $this->query("REPLACE {$this->use_table} (id,data,expire) VALUES ('{$this->id}', '{$data}', '{$expire}')");
	}

/**
  * Returns content from the Cache object itself, if the Cache object has a non-empty data property. Else from the database cache.
  *
  * @return unknown
  */
	function restore() {
		if (empty($this->data['data']))
			return $this->find("id='{$this->id}' AND expire>NOW()");
		
		return $this->data['data'];
	}

/**
  * Returns true if the cache data property has current (non-stale) content for given id.
  *
  * @return boolean
  */
	function has() {
		return is_array($this->data = $this->find("id='{$this->id}' AND expire>NOW()"));
	}

/**
  * Appends $string to the for_caching property of the Cache object.
  *
  * @param string $string
  */
	function append($string) {
		$this->for_caching .= $string;
	}

/**
  * Clears the cache database table.
  *
  * @return unknown
  */
	function clear() {
		return $this->query("DELETE FROM {$this->use_table}");
	}
}

?>