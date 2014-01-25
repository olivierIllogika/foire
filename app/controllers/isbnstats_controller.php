<?php

class IsbnstatsController extends IsbnstatsHelper
{
  /*
  function index()
  {

  }
*/

  function afficher($id)
  {
    
    if ($id)
    {
      $id = substr(preg_replace('/[^0-9]/', '', $id),0,9);
      $this->set('data', $this->models['isbnstat']->find("id = $id"));
      
      $this->models['evetudiant']->logEvent(461, empty($_SESSION['etudiant']) ? 0 : $_SESSION['etudiant']['id'], "consulte stats $id");
    }

    $this->render(null,'isbnstats');

  }


}

?>
