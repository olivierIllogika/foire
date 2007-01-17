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
  

  function courrielLivresVendus($email, $bookList, $returnedBooks, $returnedMoney)
  {

    $body =
    "Vous venez de récupérer $returnedMoney $ de la vente de vos livres.  ".
    "Cette somme correspond au total de vos ventes moins le montant de la commission prélevée.\n\n".
    "Voici le détail de vos livres vendus :\n\n".
    $bookList.

    "\n\nMerci d'avoir participé à cette édition de la Foire !";

    sendSMTP($email,'','','[Foire]Livres vendus', $body, false,'Foire aux Livres <foire-noreply@step.polymtl.ca>');

  }  
  
  function logLostBooks($etudiantId, $etudiantNom)
  {

    // look for books en_consigne and not part of a bill    
    $ret = $this->models['livre']->findBySql("SELECT id, titre, prix FROM livres AS l LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id WHERE en_consigne=1 AND ISNULL(livre_id) AND codebar=$etudiantId ");
    
    if ($ret)
    {
        // save a file with the data from student
        $sqlBooks = '';
        foreach($ret as $row)
        {
            $sqlBooks .= ($sqlBooks ? ',' : '').$row['id'];
        }
        $sqlBooks = "UPDATE livres SET codebar=$etudiantId WHERE id IN ($sqlBooks);";
        //$this->print_pre($bookList,true);
        
        $this->log("perdus $etudiantNom / ".$sqlBooks);
    }
            
  }
}

?>
