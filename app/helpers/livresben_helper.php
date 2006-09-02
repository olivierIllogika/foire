<?php

class LivresbenHelper extends AppController
{
  var $uses = array('livre', 'foire', 'genie', 'evlivre', 'evetudiant', 'journee', 'facture', 'facture_ligne', 'etudiant','isbn', 'actions_recente'); // 'coopbook',
	var $helpers = array('html', 'htmlajax');

  var $mode_paiement = array('aucune' => 'Paiment comptant',
                          'visa' => 'Carte Visa',
                          'debit' => 'Carte d&eacute;bit',
                          'autre' => 'Autre carte ?!');

  var $vCutText = 55;

  function loadCache()
  {
    if (empty($_SESSION['foire']))
    {
      $_SESSION['foire'] = $this->models['foire']->findAll(null,null,'session DESC', 1);
      $_SESSION['foire'] = current($_SESSION['foire']);
    }
    $this->set('foire', $_SESSION['foire']);

    if (empty($_SESSION['genies_cache']))
    {
      $temp = $this->models['genie']->findAll(null,'genie','id');

      $_SESSION['genies_cache'] = array_map(create_function('$v', 'return $v[0];'),$temp);
    }
    $this->set('genie', $_SESSION['genies_cache']);

  }
  
  function cutText($text, $at, $suffix='...')
  {
    $len = strlen($text);
    if ($at < $len)
    {
      $text = substr($text,0,$at).$suffix;
    }
    return $text;
  }
}

?>
