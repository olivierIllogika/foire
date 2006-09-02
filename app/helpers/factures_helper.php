<?php

class FacturesHelper extends AppController
{
  var $uses = array('facture', 'livre', 'foire', 'evlivre', 'journee', 'facture_ligne');
}

?>
