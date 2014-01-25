<?php

class LivresHelper extends AppController
{
  var $uses = array('livre', 'foire', 'genie', 'isbn', 'coopbook', 'evlivre', 'isbnstat');
	var $helpers = array('html'); // , 'htmlajax'


  function loadCache()
  {
    if (empty($_SESSION['foire']))
    {
      $_SESSION['foire'] = $this->models['foire']->find(null,null,'session DESC');
    }
    $this->set('foire', $_SESSION['foire']);

    if (empty($_SESSION['genies_cache']))
    {
      $temp = $this->models['genie']->findAll(null,'genie','id');

      $_SESSION['genies_cache'] = array_map(create_function('$v', 'return $v[0];'),$temp);
    }
    $this->set('genie', $_SESSION['genies_cache']);

  }
  
  function prepare_common($data)
  {
    $livre = $this->models['livre'];

    $data['cours'] = preg_replace('/[- ]/','',strtoupper($data['cours']));

    $isbn = $data['isbn'] = $livre->isbnClean($data['isbn']);

    $livre->set($data);
    $this->validateErrors($livre);
/*
    if ($isbn != $livre->isbn10($isbn,0,9)
      || (strlen($isbn == 4) && substr($isbn, -1, 1) == 'X'))
    {
      $livre->validationErrors['isbn'] = 1;
    }
*/

    if ( $livre->validationErrors  )
    {
      $this->render();
    }
    else
    {

      $data['codebar'] = $_SESSION['etudiant']['id'];

      $session = $_SESSION['foire']['session'];
      $genie = $data['genie'];
      $nom = $_SESSION['etudiant']['nom'];
      $prenom = $_SESSION['etudiant']['prenom'];
      $data['isbn'] = substr($data['isbn'], 0, 9);

      if ($livre->insert($data,false,$genie,$session,$nom,$prenom))
      {
        $data['id'] = $livre->id;
        return $data;
      }
      else
      {
        // bad (shouldn't be here)
        $this->render();
      }
    }
    
    return false;
  }

}

?>
