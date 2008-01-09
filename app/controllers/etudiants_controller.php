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
    $this->pageTitle .= htmlentities(" - Entrée des consignataires");

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
          // pas confirmé
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

    if (empty($this->params['data']) )
    {
      $this->params['data']['id'] = $_SESSION['etudiant']['id'];
      $this->render();
    }
    else
    {
      $id = $_SESSION['etudiant']['id'];
      $this->params['data']['id'] = preg_replace('/[^0-9]/', '', $this->params['data']['id']);

      $this->params['data']['ancienmotpasse'] = $this->db->prepare($this->params['data']['ancienmotpasse']);
      $etudiant = $this->models['etudiant']->find(
          "confirme =1 AND id=$id".
          " AND motpasse=PASSWORD({$this->params['data']['ancienmotpasse']})",
          array('id'));
        
      $duplicate = '';
      if ($id != $this->params['data']['id']) {
        $duplicate = $this->checkDuplicates('-',$this->params['data']['id']);
      }
      
      if ($duplicate)
      {
        $this->models['etudiant']->validationErrors['id'] = 1;
      }
        
      if ($this->params['data']['motpasse'] != $this->params['data']['motpasse2'])
      {
        $this->models['etudiant']->validationErrors['motpasse2'] = 1;
      }
  
      if ( empty($etudiant) ) {
        $this->models['etudiant']->validationErrors['ancienmotpasse'] = 1;
      }
        
      if ( empty($this->models['etudiant']->validationErrors) )
      {
        $modifiedFields = '';

        if ($this->params['data']['id']) {
          $modifiedFields = 'id='.$this->params['data']['id'];
          $_SESSION['etudiant']['id'] = $this->params['data']['id'];
        }
        
        if ($this->params['data']['motpasse'] != '') {
          $modifiedFields .= $modifiedFields != '' ? ',' : '' ;
          $motpasse = $this->db->prepare($this->params['data']['motpasse']);
          $modifiedFields .= "motpasse = PASSWORD($motpasse)";
        }
        
        if ($modifiedFields) {
          $this->db->query("UPDATE etudiants SET $modifiedFields WHERE id=$id");
          $this->flash(htmlentities('Mise à jour terminée...'),'/livres');
        }
        else {
          $this->flash(htmlentities('Aucun changement...'),'/livres');
        }
        
      } //validation
    }//form submit
    unset($this->params['data']['ancienmotpasse']);
    unset($this->params['data']['motpasse']);
    unset($this->params['data']['motpasse2']);
  }

  function perdu_infos($courriel=null)
  {
    $this->pageTitle .= htmlentities(" - Récupération de vos informations");

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


    /* pre-processing de données */
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
      if ($this->params['data']['id']) // id suggéré (carte/permis)
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
      $this->flash(htmlentities('Courriel d\'inscription envoyé'),'/etudiants/inscription_choix');
      
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
        $this->flash(htmlentities('Inscription déjà confirmée'),'/livres/');
      }
      else
      {
        $this->models['evetudiant']->logEvent(402,$id,"confirmation");
        $row['confirme'] = 1;
        $this->models['etudiant']->save($row,false);
        $this->flash(htmlentities('Inscription confirmée'),'/livres/');
      }

    }
    else
    {
      // ne devrait jamais venir ici
      $this->models['evetudiant']->logEvent(451,$id,"conf:identité inconnue");
      $this->flash(htmlentities('Identification inconnue'),'/etudiants/inscription_choix');
    }

  }

  function mass_mailer($action=null)
  {
    $this->sessionCheck(SECURITY_LEVEL_MANAGMENT);
    
    $sender = 'Foire aux Livres <foire-noreply@step.polymtl.ca>';

    $mail_title = "Récupération des livres";
    $body = $this->courrielRecuperation(); // $date, $heure
//    $body = $this->courrielRecuperationTardive(); // $date, $heure

    $operation = ($action == 'envoyer' ? '[envoi]' : '[test]');
    $this->set('body', $body);

    sendSMTP($sender,'',
            "Olivier Martin <olivier-2.martin@polymtl.ca>",
            '[Foire] '.$operation.' '.$mail_title,
            $body, false,$sender);

    if ($action == 'envoyer')
    {
      $megaListe = array();

      $page = 0;
      $per_page = 30;
      do
      {
        // list based on books marked as picked up now (can contain books that have never been returned from previous years; over 40 false positive in 20073)
        //$ret = $this->models['etudiant']->findBySql("SELECT DISTINCT CONCAT(prenom, ' ', nom, ' <', courriel, '>') AS formed FROM etudiants AS e LEFT JOIN livres AS l ON l.codebar=e.id WHERE en_consigne=1 AND e.id!=0 ORDER BY SUBSTRING(courriel,INSTR(courriel,'@')),nom,prenom LIMIT ".($per_page*$page++).",$per_page");
        
        // list based on books picked up in the last 30 days
        $ret = $this->models['etudiant']->findBySql("SELECT DISTINCT CONCAT(prenom, ' ', nom, ' <', courriel, '>') AS formed FROM etudiants AS e lEFT jOIN livres AS l ON l.codebar=e.id LEFT JOIN evlivres AS evl ON evl.id=l.id WHERE evl.evenement = 101 AND e.id!=0 AND evl.created > DATE_SUB(CURDATE(),INTERVAL 30 DAY) ORDER BY SUBSTRING(courriel,INSTR(courriel,'@')),nom,prenom LIMIT ".($per_page*$page++).",$per_page");

        if ($ret)
        {
          $listeCourriel = array_map(create_function('$v', 'return $v[0];'), $ret);


          sendSMTP($sender,'',
                  implode(', ',$listeCourriel),
                  '[Foire] '.$mail_title,
                  $body, false,$sender);

//          $megaListe[] = $listeCourriel;
        }

      } while ($ret);
      
      $this->set('megaListe', $megaListe);

    }

    $this->render();

  }

  function mod_etudiants($id=null)
  {
    $this->sessionCheck(SECURITY_LEVEL_MANAGMENT);

//$this->print_pre($this->params)    ;
    $this->models['etudiant']->countLastFind = -1;
    $this->set('canModify', false);


    if ($id)
    {
        if (empty($this->params['data']))
        {
            // find the data and display it
            $id = preg_replace('/[^0-9]/','',$id);
            $this->models['etudiant']->setId($id);
            $this->params['data']= $this->models['etudiant']->read();
            $this->set('canModify', true);
        }

    }
    else //id
    {
      if (!empty($this->params['data']))
      {
        if ($this->params['data']['submit'] == 'Modifier')
        {
            // apply modification to the data
            $this->set('canModify', true);

            $newId = preg_replace('/[^0-9]/','',$this->params['data']['id']);
            $oldId = $this->params['data']['modifId'];
            $this->models['etudiant']->setId($oldId);
            $this->models['etudiant']->read();
            
            if ($oldId != $newId && $newId != 0 && $oldId != 0)
            {
                // changing id => 1-check if already used; 2-update existing books
                $duplicate = $this->checkDuplicates('', $newId);
                
                if($duplicate)
                {
                    // id already in use
                    $this->models['etudiant']->validationErrors['id'] = 1;
                    $this->render();
                    return;
                }
                
                $this->db->query("UPDATE livres SET codebar=$newId WHERE codebar=$oldId");

                $this->params['data']['id'] = $newId;
            } // $oldId != $newId


            
            $this->params['data']['confirme'] = $this->params['data']['confirme'] == 'on' ? 1 : 0;
            $this->models['etudiant']->save($this->params['data'], false);
            
        }//data
        
        if ($this->params['data']['submit'] == 'Rechercher')
        {
            // recherche
            
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
      }
    }//id
    
    $this->render();
  }

	
}//class

?>
