<?php

require_once ROOT.'modules/smtp.php';

class EtudiantsHelper extends AppController
{
  var $uses = array('etudiant','foire', 'evetudiant');
  
  
  function courrielConfirmation($courriel, $id, $cartePoly)
  {
    $conf_link = 'http://'.$_SERVER['HTTP_HOST'].preg_replace('/public.*/','',$_SERVER['SCRIPT_NAME'])."etudiants/confirmer/$id";

    $body =
    "Vous avez effectuÃ© une demande d'inscription sur la Foire aux Livres.".
    "\n\nSi c'est Ã  votre demande, cliquez sur le lien pour confirmer votre inscription.".
    " Si ce n'est pas le cas, vous n'avez qu'Ã  ignorer ce message\n\n".

    "Votre code d'identification pour la section <<Mes livres>> est : $id\n".
    ($cartePoly ? "Vous pouvez entrer seulement les derniers chiffres, soit : ".(ltrim(substr($id,5),'0'))."\n" : '').

    "\nCliquez sur ce lien ou copiez l'adresse dans votre navigateur pour confirmer votre inscription Ã  la Foire aux Livres\n".
    "$conf_link\n\n".
    "Merci, et bonne Foire !";

    sendSMTP($courriel,'','','[Foire]Confirmation: suivez le lien pour continuer', $body, false,$GLOBALS['gNoReplyEmail']);

  }


  function courrielPerduInfos($courriel, $id, $nouveauPasse)
  {

    $cartePoly = (substr($id,0,5) == '29334');

    $body =
    "Vous avez effectuÃ© une demande d'information sur la Foire aux Livres.".
    "\n\nSi ce n'est pas le cas, vous n'avez qu'Ã  ignorer ce message\n\n".

    "Votre code d'identification pour la section <<Mes livres>> est : $id\n".
    ($cartePoly ? "Vous pouvez entrer seulement les derniers chiffres, soit : ".(ltrim(substr($id,5),'0'))."\n" : '').
    "Votre nouveau mot de passe est: $nouveauPasse\n\n".

    "Merci, et bonne Foire !";

    sendSMTP($courriel,'','','[Foire]Nouveau mot de passe', $body, false,$GLOBALS['gNoReplyEmail']);

  }
  
	function courrielRecup20123()
	{
    	$body = 
"Bonjour,\n\n".

"Ce message s'adresse aux étudiants qui n'ont pas récupéré leurs LIVRES et/ou leur ARGENT provenant de la vente de livres usagés. Il serait impératif de vous présenter à l'AEP (local C-219) avant la semaine de relâche, les livres ne seront pas disponibles toute la session.\n\n".

"Si vous avez un problème, des livres manquants ou des questions, contactez moi à l'adresse suivante: foire@step.polymtl.ca\n\n".

"merci de votre coopération,\n\n".

"Mathieu Desrosiers\n".
"Directeur de la Foire aux Livres";		
	
	    return $body;
	}
	
  function courrielRecuperationTardive()
  {

    $body =
    "Bonjour,\n\n".

    "La foire aux livres est terminÃ©e et il reste des gens qui ne sont pas venus ".
    "chercher leur argent et/ou leurs livres restants.  La procÃ©dure pour rÃ©cupÃ©rer ".
    "l'argent et/ou les livres est simple.  PrÃ©sentez-vous Ã  l'AEP pendant les ".
    "heures d'ouverture (9h00 @ 16h00) et prÃ©sentez votre carte Ã©tudiante.\n\n".
/*
    "Les dates pour la rÃ©cupÃ©ration sont les suivantes : du 21 septembre (mercredi) ".
    "au 27 septembre prochain (mardi).\n\n".
*/
    "La date limite pour la rÃ©cupÃ©ration est vendredi prochain, le 3 fÃ©vrier.\n\n".

    "Il est Ã  noter que 15% de la valeur totale des ventes a Ã©tÃ© prÃ©levÃ©.\n\n".

    "L'Ã©quipe de la foire";

    return $body;

  }

  function courrielRecuperation()
  {

    $body =
    "Vous recevez ce courriel car vous avez de l'argent ou des livres Ã  rÃ©cupÃ©rer Ã  la Foire aux Livres de l'AEP.".
    "\n\n".

    "Vous devez vous prÃ©senter Ã  la journÃ©e de rÃ©cupÃ©ration pour prendre livres et/ou argent. Consultez foire.aep.polymtl.ca pour l'horaire.\n\n".

    "Assurez-vous d'avoir en main une des piÃ¨ces d'identification suivante :\n\n".
    
    "(en ordre de prÃ©fÃ©rence)\n".
    "Votre carte Ã©tudiante\n".
    "OU\n".
    "Une carte d'identitÃ© avec photo\n\n";

    return $body;
  }

	function randomPassword ($length, $available_chars = 'ABDEFHKMNPRTWXYABDEFHKMNPRTWXY23456789')
	{
		$chars = preg_split('//', $available_chars, -1, PREG_SPLIT_NO_EMPTY);
		$char_count = count($chars);

		$out = '';
		for ($ii=0; $ii<$length; $ii++)
		{
			$out .= $chars[rand(1, $char_count)-1];
		}

		return $out;
	}

  function newId($foire=null)
  {

    $id_start = '';
    if ($foire)
    {
      $id_start = 'id>'.($foire*1000).' AND ';

      $lastID = $this->models['etudiant']->find("$id_start id<100000000", 'id', 'id DESC');

      if (!$lastID)
      {
        $year = date('Y');
        $month = date('n');
        if ($month > 9) $year++;
        $session = ($month > 2 && $month < 10 ? 3 : 1);
        return intval($year.$session)*1000+1;
      }
      else
      {
        return 1+$lastID['id'];
      }
    }

    return false;
  }
  
  function checkDuplicates($courriel='-', $id=-1)
  {
    /* AND confirme=1 */
    $courriel = strtolower($courriel);
    $data = $this->models['etudiant']->findAll("(id = $id OR courriel='$courriel') ", array('courriel','id','confirme'));
    
    if ($data)
    {
      foreach($data as $row)
      {
        if ($row['confirme'] == 0)
        {
          // found a duplicate but it's not confirmed -> erase it
          $this->models['etudiant']->del($row['id']);
        }
        else
        {
          if ($row['id'] == $id)  return $id;
          if (strtolower($row['courriel']) == $courriel)  return $courriel;
        }
      }
    }

    return false;
  }
  

}

?>
