<?php

class Etudiant extends AppModel
{
  var $countLastFind = null;

  var $validate = array(
          'nom'=>VALID_NOT_EMPTY,
          'prenom'=>VALID_NOT_EMPTY,
          'motpasse'=>VALID_NOT_EMPTY,
          'courriel'=>VALID_EMAIL);


}

?>
