<?php

require_once ROOT.'modules/smtp.php';

class EtudiantsHelper extends AppController
{
  var $uses = array('etudiant','foire', 'evetudiant');
  
  
  function courrielConfirmation($courriel, $id, $cartePoly)
  {
    $conf_link = 'http://'.$_SERVER['HTTP_HOST'].preg_replace('/public.*/','',$_SERVER['SCRIPT_NAME'])."etudiants/confirmer/$id";

    $body =
    "Vous avez effectué une demande d'inscription sur la Foire aux Livres.".
    "\n\nSi c'est à votre demande, cliquez sur le lien pour confirmer votre inscription.".
    " Si ce n'est pas le cas, vous n'avez qu'à ignorer ce message\n\n".

    "Votre code d'identification pour la section <<Mes livres>> est : $id\n".
    ($cartePoly ? "Vous pouvez entrer seulement les derniers chiffres, soit : ".(ltrim(substr($id,5),'0'))."\n" : '').

    "\nCliquez sur ce lien ou copiez l'adresse dans votre navigateur pour confirmer votre inscription à la Foire aux Livres\n".
    "$conf_link\n\n".
    "Merci, et bonne Foire !";

    sendSMTP($courriel,'','','[Foire]Confirmation: suivez le lien pour continuer', $body, false,'Foire aux Livres <foire-noreply@step.polymtl.ca>');

  }


  function courrielPerduInfos($courriel, $id, $nouveauPasse)
  {

    $cartePoly = (substr($id,0,5) == '29334');

    $body =
    "Vous avez effectué une demande d'information sur la Foire aux Livres.".
    "\n\nSi c'est à votre demande, cliquez sur le lien pour confirmer votre inscription.".
    " Si ce n'est pas le cas, vous n'avez qu'à ignorer ce message\n\n".

    "Votre code d'identification pour la section <<Mes livres>> est : $id\n".
    ($cartePoly ? "Vous pouvez entrer seulement les derniers chiffres, soit : ".(ltrim(substr($id,5),'0'))."\n" : '').
    "Votre nouveau mot de passe est: $nouveauPasse\n\n".

    "Merci, et bonne Foire !";

    sendSMTP($courriel,'','','[Foire]Nouveau mot de passe', $body, false,'Foire aux Livres <foire-noreply@step.polymtl.ca>');

  }

  function courrielRecuperationTardive()
  {

    $body =
    "Bonjour,\n\n".

    "La foire aux livres est terminée et il reste des gens qui ne sont pas venus ".
    "chercher leur argent et/ou leurs livres restants.  La procédure pour récupérer ".
    "l'argent et/ou les livres est simple.  Présentez-vous à l'AEP pendant les ".
    "heures d'ouverture (9h00 @ 16h00) et présentez votre carte étudiante.\n\n".
/*
    "Les dates pour la récupération sont les suivantes : du 21 septembre (mercredi) ".
    "au 27 septembre prochain (mardi).\n\n".
*/
    "La date limite pour la récupération est vendredi prochain, le 3 février.\n\n".

    "Il est à noter que 15% de la valeur totale des ventes a été prélevé.\n\n".

    "L'équipe de la foire";

    return $body;

  }

  function courrielRecuperation()
  {

    $body =
    "Vous recevez ce courriel car vous avez de l'argent ou des livres à récupérer à la Foire aux Livres de l'AEP.".
    "\n\n".

    "Vous devez vous présenter à la Galerie Rolland demain, le mercredi 18 janvier, entre 9h et 15h30\n\n".

    "Assurez-vous d'avoir en main une des pièces d'identification suivante :\n\n".
    
    "(en ordre de préférence)\n".
    "Votre carte étudiante\n".
    "OU\n".
    "Votre reçu (ou celui de quelqu'un d'autre) en mentionnant le nom du consignataire\n".
    "OU\n".
    "Une carte d'identité avec photo\n\n";

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
  
  function checkDuplicates($courriel, $id)
  {
    /* AND confirme=1 */
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
          if ($row['courriel'] == $courriel)  return $courriel;
        }
      }
    }

    return false;
  }
  

}

?>
