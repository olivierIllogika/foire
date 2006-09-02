<?php

require_once ROOT.'modules/class.book_hash.php';

class Livre extends AppModel
{
  var $countLastFind = null;

  var $validate = array(
          'titre'=>VALID_NOT_EMPTY,
//          'cours'=>VALID_NOT_EMPTY,
          'genie'=>VALID_NOT_EMPTY,
//          'isbn'=>'/^([0-9]{4}$)|([0-9]{9}[0-9X]$)|$/', // cleaned isbn/code4
          'prix'=>VALID_NUMBER);

  function isbnClean($raw_isbn)
  {
    return preg_replace('/[^0-9X]/', '', strtoupper($raw_isbn));
  }
  /*
  function format_isbn($isbn)
  {
    if ($isbn === '0')
    {
      return '-'; // code4
    }

    if (strlen($isbn) < 9)
    {
      return $isbn; // code4
    }
    
    if (strlen($isbn) == 9)
    {
      $isbn = $this->isbn10($isbn);
    }

    return preg_replace('/(.{1})(.{4})(.{4})(.{1})/', '$1-$2-$3-$4', $isbn).'<br />';
  }
*/
  function isbn10($isbn9)
  // calc ISBN checksum from 9 digit ISBN
  {
    if (strlen($isbn9) < 9)
    {
      return $isbn9; // code4
    }
    
    $n = $isbn9;

    $check_sum = 0;

    for ($i = 0; $i < 9; $i++)
      $check_sum += ($i+1)*$n[$i];

    $check_sum %= 11;

    $check_sum = $check_sum == 10 ? 'X' : $check_sum;

    return $isbn9.$check_sum;
  }

  function cours2genie($genie_array, $cours)
  {
    $map_array = array('Tronc-commun' => array('INF', 'MTH'),
                        'Electrique' => array('ELE'),
                        'Informatique' => array('INF', 'LOG'),
                        'Physique' => array('PHS'),
                        'SH' => array('SSH'),
                        'Mecanique' => array('MEC'),
                        'Chimique' => array('GCH'),
                        'Civil' => array('CIV'),
                        'Industriel' => array('IND')
                        );

    $id_cours = substr($cours,0,3);
                        
    $f = null;
    foreach($map_array as $genie => $ids_cours)
    {
      /*
echo '<pre>';
echo $id_cours.'<br />';
print_r($ids_cours);
echo '</pre>';
*/
      if (in_array($id_cours,$ids_cours))
      {
        $f = $genie;
        break;
      }
    }

    $genie_num = array_search($f, $genie_array);
    return $genie_num;
  }

  function isbn10_code4_Html()
  {
    return '<acronym title="International Standard Book Number">ISBN</acronym>'.
           '/'.
           '<acronym title="'.htmlentities('Code à 4 chiffres des polycopiés de Polytechnique').'">code4</acronym>';
  }
/*
  function titre($data)
  {
    if ($data['titre'] != '') return $data['titre'];
    if ($data['ctitre'] != '') return $data['ctitre'];
    if ($data['ititre'] != '') return $data['ititre'];
    return '';
  }
*/
	function insert ($data=null, $validate=true, $genie, $session, $lastname='-', $firstname='-')
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
				$values[] = $v == 'null' ? "''" : ( (ini_get('magic_quotes_gpc') == 1) ? $this->db->prepare(stripslashes($v)) : $this->db->prepare($v) );
			}
		}

		$fields[] = 'created';
		$values[] = date("'Y-m-d H:i:s'");


    $fields[] = 'id';
    $this->id = BookHash::getNewID($genie,$session,$lastname,$firstname);
//echo $this->id.'<br />';

    if (substr($this->id, 5, 1) != $genie)
    {
//echo $this->id.' => '.substr($this->id, 5, 1) .' =?= '. substr($id, 5, 1).'<br />';
      $this->id = $id = BookHash::getNewID($genie,$session,'-','-');
    }
    
    $values[] = $this->db->prepare($this->id);

		$fields = join(',', $fields);
		$values = join(',', $values);

		$sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$values})";

		if($this->db->query($sql))
		{
//			$this->id = $this->db->lastInsertId($this->table, 'id');
			return true;
		}
		else
		{
			return false;
		}

	} // insert
}

?>
