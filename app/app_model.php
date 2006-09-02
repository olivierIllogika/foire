<?php
//////////////////////////////////////////////////////////////////////////
// + $Id: app_model.php,v 1.4 2005/08/22 04:19:01 Administrator Exp $
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
 * @lastmodified $Date: 2005/08/22 04:19:01 $
 */

/**
 * This file is application-wide model file. You can put all 
 * application-wide model-related methods here.
 * 
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 * 
 * @package cake
 * @subpackage cake.app
 */

class AppModel extends Model {
/*
   function setTablePrefix() {
   		$this->tablePrefix = 'v2_';
   }
*/
	function insertWithId ($data=null, $validate=true, $id)
	{
		if ($data) $this->set($data);

		if ($validate && !$this->validates())
			return false;

		$fields = $values = array();
		foreach ($this->data as $n=>$v)
		{
			if ($this->hasField($n))
			{
				$fields[] = $n;
				if ($v == 'null')
				{
          $values[] = 'null';
        }
        elseif (substr($v, 0, 9) == 'PASSWORD(')
        {
          $values[] = "PASSWORD(".$this->db->prepare(preg_replace('/.*\((.*)\).*/', '\1', $v)).")";
        }
        else
        {
          $values[] = $this->db->prepare($v);
        }
			}
		}
		/*
echo '<pre>';
print_r($values);
echo '</pre>';
die();
*/
		if ($this->hasField('created') && !in_array('created', $fields))
		{
			$fields[] = 'created';
			$values[] = date("'Y-m-d H:i:s'");
		}
		if ($this->hasField('modified') && !in_array('modified', $fields))
		{
			$fields[] = 'modified';
			$values[] = 'NOW()';
		}

    $fields[] = 'id';
    $values[] = $this->db->prepare($id);

		$fields = join(',', $fields);
		$values = join(',', $values);

		$sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$values})";

		if($this->db->query($sql))
		{
			$this->id = $id;
			return true;
		}
		else
		{
			return false;
		}

	} // insert


	function print_pre($arrayToPrint, $die=false)
	{
		echo '<pre>'.print_r($arrayToPrint,true).'</pre>';
		if ($die) die();
	}
}

?>
