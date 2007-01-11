<?php


class EtudiantsController extends EtudiantsHelper
{

  function logout()
  {
    $this->sessionCheck();
    if (isset($_SESSION['etudiant']['id']))
    {
    	$this->models['evetudiant']->logEvent(409,$_SESSION['etudiant']['id'],"logout");
    }
    $this->sessionCheck(SECURITY_LEVEL_FORCE_LOGOUT);
  }

  function login()
  {
    $this->pageTitle .= htmlentities(" - Entr�e des consignataires");

    if (empty($this->params['data']['id']) || empty($this->params['data']['motpasse']))
    {
      $this->render();
      return;
    }
    else
    {
      $redirHint = strtolower(substr($this->params['data']['id'], -1));
      if ($redirHint == 'a' || $redirHint == 'b')
      {
        $this->params['data']['id'] = substr($this->params['data']['id'],0,strlen($this->params['data']['id'])-1);
      }
      $id = $this->params['data']['id'] = preg_replace('/[^0-9]/', '', $this->params['data']['id']);

      if (empty($id))
      {
        $this->models['etudiant']->validationErrors['id'] = 1;
        $this->render();
        return;
      }


      $etudiant = $this->models['etudiant']->find(
          "confirme =1 AND ".
          "(id=$id OR ( LEFT(id,5) = '29334' AND CAST( RIGHT(id,9) AS UNSIGNED) = $id  ))".
          " AND motpasse=PASSWORD('{$this->params['data']['motpasse']}')",
          array('id','nom','prenom','niveau'));

      if ($etudiant)
      {
        // update logdate
        $this->db->query("update etudiants set logdate=null where id={$etudiant['id']};");
        
        // removes numeric references (keeps associative info only)
        unset($etudiant[0]);
        unset($etudiant[1]);
        unset($etudiant[2]);
        unset($etudiant[3]);

        $_SESSION['etudiant'] = $etudiant;
        if ($redirHint == 'a')
        {

        }
        elseif ($redirHint == 'b')
        {

        }
        else
        {
          $this->models['evetudiant']->logEvent(406,$_SESSION['etudiant']['id'],"login");
          $this->redirect("/livres/index");
        }
      }
      else
      {
        $etudiant = $this->models['etudiant']->find(
            "id=$id OR ( LEFT(id,5) = '29334' AND CAST( RIGHT(id,9) AS UNSIGNED) = $id )");
//$this->print_pre($etudiant,true);
        if (!$etudiant)
        {
          // pas inscrit
          $this->models['etudiant']->validationErrors['id'] = 2;
        }
        elseif ($etudiant['confirme'] == 0)
        {
          // pas confirm�
          $this->models['etudiant']->validationErrors['confirme'] = 1;
        }
        else
        {
          // mauvais mot de passe
          $this->models['etudiant']->validationErrors['motpasse'] = 1;
        }
      }

      $this->render();
    }

  }

  function profil()
  {
    $this->sessionCheck(0);
    
    
  }

  function perdu_infos($courriel=null)
  {
    $this->pageTitle .= htmlentities(" - R�cup�ration de vos informations");

    if (empty($this->params['data']) )
    {
      if (!empty($courriel)) $this->params['data']['courriel'] = $courriel;
      $this->render();
    }
    else
    {
      $this->params['data']['courriel'] = trim($this->params['data']['courriel']);

      $ret = $this->models['etudiant']->findAll(
          "courriel='{$this->params['data']['courriel']}'",
          array('id','nom','prenom','courriel'));
          
      if ($ret)
      {
        $etudiant = current($ret);
        $this->models['evetudiant']->logEvent(405,$etudiant['id'],"infos perdu; pour {$this->params['data']['courriel']}");
        
        $newP = strtolower($this->randomPassword(6));

    		$sql = "UPDATE {$this->models['etudiant']->table} SET motpasse=PASSWORD('$newP') WHERE id={$etudiant['id']}";
    		$this->db->query($sql);

        $this->courrielPerduInfos($etudiant['courriel'], $etudiant['id'], $newP);
        $this->set('email_sent', true);
      }
      else
      {
        $this->models['evetudiant']->logEvent(455,0,"dem. infos; erreur!  {$this->params['data']['courriel']}");
        $this->models['etudiant']->validationErrors['courriel'] = 1;

      }


    }
  }

  function inscription_choix()
  {}

  function inscription_etudiant()
  {}

  function inscription_autre()
  {}

  function inscription()
  {
    $this->pageTitle .= htmlentities(" - Inscription des consignataires");


    /* pre-processing de donn�es */
    if ($this->params['data']['source'] == 'etudiant')
    {
      $this->params['data']['courriel'] = preg_replace('/@.+/', '', $this->params['data']['courriel']).'@polymtl.ca';
    }
    else
    {
      // autres
      
      // pre-processing
      $this->params['data']['id_poly'] = (!empty($this->params['data']['id_poly']) ?
                preg_replace('/[^0-9]/', '', $this->params['data']['id_poly']) : '');
                
      $this->params['data']['id_permis'] = (!empty($this->params['data']['id_permis']) ?
                preg_replace('/[^0-9]/', '', $this->params['data']['id_permis']) : '');

      // try to identify id source by content
      if ($this->params['data']['id_poly'] != '' && $this->params['data']['id_poly'] != '29334')
      {
        $this->params['data']['id'] = $this->params['data']['id_poly'];
      }
      elseif ($this->params['data']['id_permis'] != '')
      {
        $this->params['data']['id'] = $this->params['data']['id_permis'];
      }
      else
      {
        $this->params['data']['id'] = '';
      }

      if (!empty($this->params['data']['mode']))
      {
        // overwrite source guess if choice+content is valid
        if ($this->params['data']['mode'] == 1 && $this->params['data']['id_poly'] != '' && $this->params['data']['id_poly'] != '29334')
        {
          $this->params['data']['id'] = $this->params['data']['id_poly'];
        }
        elseif ($this->params['data']['mode'] == 2 && $this->params['data']['id_permis'] != '')
        {
          $this->params['data']['id'] = $this->params['data']['id_permis'];
        }
        else
        {
          $this->params['data']['id'] = '';
        }
      }

    }//etudiants vs autres

    $this->params['data']['id'] = preg_replace('/[^0-9]/', '', $this->params['data']['id']);
/*
echo '<pre>';
print_r($this->models['etudiant']->validate);
echo '</pre>';
*/
/*
echo '<pre>';
print_r($this->params['data']);
echo '</pre>';
*/

    $this->models['etudiant']->set($this->params['data']);
    $this->validateErrors($this->models['etudiant']);
    
    if ($this->params['data']['motpasse'] != $this->params['data']['motpasse2'])
    {
      $this->models['etudiant']->validationErrors['motpasse2'] = 1;
    }

    if ($this->params['data']['source'] == 'etudiant' &&
        ($this->params['data']['id'] == '' || strlen($this->params['data']['id']) != 14))
    {
      $this->models['etudiant']->validationErrors['id'] = 1;
    }
    /*
echo '<pre>';
print_r($this->validationErrors);
echo '</pre>';
echo '<pre>';
print_r($this->models['etudiant']->validationErrors);
echo '</pre>';
*/

    if ( $this->models['etudiant']->validationErrors  )
    {
      if ($this->params['data']['source'] == 'etudiant')
      {
        $this->params['data']['courriel'] = preg_replace('/@.+/', '', $this->params['data']['courriel']);
      }

      $this->render('inscription_'.$this->params['data']['source']);

    }
    else
    {
      if ($this->params['data']['id']) // id sugg�r� (carte/permis)
      {
        $id = $this->params['data']['id'];
        
      } else { // aucun id, attribuer en fonction de la session de la Foire
        $foire = $this->models['foire']->find(null,'session','session DESC');
        $foire = $foire['session'];
        $id = $this->newId($foire);
      }

      $duplicate = $this->checkDuplicates($this->params['data']['courriel'],$id);

      if ($duplicate)
      {
        $this->set('duplicate', $duplicate);
        $this->set('courriel', $this->params['data']['courriel']);
        if ($this->params['data']['source'] == 'etudiant')
        {
          $this->params['data']['courriel'] = preg_replace('/@.+/', '', $this->params['data']['courriel']);
        }
        $this->models['evetudiant']->logEvent(451,$id,"conflit! $duplicate");
        
        $this->render('inscription_'.$this->params['data']['source']);
        return;
      }

      $cartePoly = (substr($id,0,5) == '29334');
      $this->params['data']['motpasse'] = "PASSWORD({$this->params['data']['motpasse']})";

      $this->models['etudiant']->insertWithId($this->params['data'],false,$id);
      $this->models['evetudiant']->logEvent(401,$id,"inscription");

      $this->courrielConfirmation($this->params['data']['courriel'],$id,$cartePoly);
/*
echo '<pre>';
print_r($this->params['data']);
echo '</pre>';
die();
*/
      $this->flash(htmlentities('Courriel d\'inscription envoy�'),'/etudiants/inscription_choix');
      
    }

  }

  function confirmer($id)
  {
    $data = $this->models['etudiant']->findAll("id = $id");
    
    if ($data)
    {
      $row = current($data);
      if ($row['confirme'] == 1)
      {
        $this->models['evetudiant']->logEvent(452,$id,"reconfirmation!");
        $this->flash(htmlentities('Inscription d�j� confirm�e'),'/livres/');
      }
      else
      {
        $this->models['evetudiant']->logEvent(402,$id,"confirmation");
        $row['confirme'] = 1;
        $this->models['etudiant']->save($row,false);
        $this->flash(htmlentities('Inscription confirm�e'),'/livres/');
      }

    }
    else
    {
      // ne devrait jamais venir ici
      $this->models['evetudiant']->logEvent(451,$id,"conf:identit� inconnue");
      $this->flash(htmlentities('Identification inconnue'),'/etudiants/inscription_choix');
    }

  }

  function mass_mailer($action=null)
  {
    $this->sessionCheck(SECURITY_LEVEL_MANAGMENT);

    $mail_title = "R�cup�ration tardive des livres";
    $body = $this->courrielRecuperationTardive(); // $date, $heure
//    $body = $this->courrielRecuperationTardive(); // $date, $heure

    $operation = ($action == 'envoyer' ? '[envoi]' : '[test]');
    $this->set('body', $body);

    sendSMTP('Foire aux Livres <foire-noreply@step.polymtl.ca>','',
            "Olivier Martin <olivier-2.martin@polymtl.ca>, Charles Gagnon <charles.gagnon@polymtl.ca>",
            '[Foire] '.$operation.' '.$mail_title,
            $body, false,'Foire aux Livres <foire-noreply@step.polymtl.ca>');

    if ($action == 'envoyer')
    {
      $megaListe = array();

      $page = 0;
      $per_page = 30;
      do
      {
        $ret = $this->models['etudiant']->findBySql("SELECT DISTINCT CONCAT(prenom, ' ', nom, ' <', courriel, '>') AS formed FROM etudiants AS e LEFT JOIN livres AS l ON l.codebar=e.id WHERE en_consigne=1 AND e.id!=0 ORDER BY SUBSTRING(courriel,INSTR(courriel,'@')),nom,prenom LIMIT ".($per_page*$page++).",$per_page");
        
        if ($ret)
        {
          $listeCourriel = array_map(create_function('$v', 'return $v[0];'), $ret);


          sendSMTP('Foire aux Livres <foire-noreply@step.polymtl.ca>','',
                  implode(', ',$listeCourriel),
                  '[Foire] '.$mail_title,
                  $body, false,'Foire aux Livres <foire-noreply@step.polymtl.ca>');

//          $megaListe[] = $listeCourriel;
        }

      } while ($ret);
      
      $this->set('megaListe', $megaListe);

    }

    $this->render();

  }

  function mod_etudiants($id=null)
  {
//$this->print_pre($this->params)    ;
    $this->models['etudiant']->countLastFind = -1;
    $this->set('canModify', false);

    if ($id)
    {
        if (empty($this->params['data']))
        {
            // find the data and display it
            $this->models['etudiant']->setId($id);
            $this->params['data']= $this->models['etudiant']->read();
            $this->set('canModify', true);
        }
        else
        {
            // apply modification to the data
        }//data

    }
    else //id
    {
      if (!empty($this->params['data']))
      {
        $this->models['etudiant']->countLastFind = 0;
        $condition = '';

        // id
        $id = $this->params['data']['id'] ? preg_replace('/[^0-9]/','',$this->params['data']['id']) : 0;
    
        if ($id)
        {
            unset($this->params['data']);
            $this->mod_etudiants($id);
            return;
        }

        // prenom + nom
        $condition .= $this->params['data']['prenom'] ? ' prenom like \'%'.$this->params['data']['prenom']."%'" : '';
        $condition .= $this->params['data']['nom'] ? ($condition ? ' AND ': '').' nom like \'%'.$this->params['data']['nom']."%'" : '';
        // courriel
        $condition .= $this->params['data']['courriel'] ? ($condition ? ' AND ': '').' courriel like \'%'.$this->params['data']['courriel']."%'" : '';
        
        if ($condition)
        {        
            $this->set('listeEtudiants', $this->models['etudiant']->findAll(
            $condition, array('id','nom','prenom','courriel', 'confirme', 'logdate', 'created')));
            
            $this->models['etudiant']->countLastFind = $this->models['etudiant']->db->numRows;
        }
	  } //data
    }//id
    
    $this->render();
  }

	
}//class

?>
