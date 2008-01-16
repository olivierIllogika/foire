<?php


define('FPDF_FONTPATH',ROOT.'modules/fpdf152/font/');
require_once ROOT.'modules/fpdf_label.inc.php';
require_once ROOT.'modules/fpdf_cheque.inc.php';

class LivresbenController extends LivresbenHelper
{


  function cueillette()
  {
    $this->sessionCheck(SECURITY_LEVEL_PICKER);
  }

  function pickup_scan()
  {

    if (!empty($this->params['data']))
    {


      $input = preg_replace('/[^0-9]/', '', $this->params['data']['scan_box']);
      
      $prix = substr($input,-3);
      $code = substr($input,0,10);
      $ret = $input ? $this->models['livre']->findAll("id = $code AND prix=$prix") : false;

      if ($ret)
      {
        $livre = current($ret);

        $_SESSION['persistent']['dernier_codebar'] = $livre['codebar'];
        
        $class = 'class="'.($livre['en_consigne'] == 1 ? 'rowUsager':'rowConsigne').'"';
        
        $request_content = "<td $class>".preg_replace('/.{5}(.)(.{4})$/','\2-\1',$livre['id'])."</td><td $class>".$this->cutText($livre['titre'],$this->vCutText)."</td><td $class>{$livre['prix']}$</td><td $class>{$livre['isbn']}</td>";

        $livre['en_consigne'] = $livre['en_consigne'] == 1 ? 0 : 1;

        if ($livre['en_consigne'] == 1)
        {
          $this->models['evlivre']->logEvent(101,$livre['id'],$_SESSION['etudiant']['id'],"consigne ({$_SESSION['etudiant']['prenom']})");
        }
        else
        {
          $this->models['evlivre']->logEvent(102,$livre['id'],$_SESSION['etudiant']['id'],"déconsigne ({$_SESSION['etudiant']['prenom']})");
        } // en_consigne

        $this->models['livre']->save($livre);
        
      }
      else // $ret != null
      {
          $this->models['evlivre']->logEvent(151,0,$_SESSION['etudiant']['id'],"échec [{$this->params['data']['scan_box']}] ({$_SESSION['etudiant']['prenom']})");

        $request_content = "<td class=\"rowInvalid\" colspan=\"4\">{$this->params['data']['scan_box']}</td>";
      } // $ret != null

    } // !empty param

    $this->set('request_content', $request_content);
    $this->render('ajax_back','ajax');
  }

  function vente($param=null)
  {
    $this->sessionCheck(SECURITY_LEVEL_SELLER);
    
    if (!empty($param) && $param == 'reset')
    {
      $_SESSION['persistent']['mode_paiement'] = $_SESSION['persistent']['derniere_facture'] = null;
      $this->redirect('/pages/menu_ben');
      return;
    }

    // si une facture existe, affiche le contenu au chargement de la page
    $existing_rows = '';
    $total_price = $total_livres = '0';
    if (!empty($_SESSION['persistent']['derniere_facture']))
    {
      $fid = $_SESSION['persistent']['derniere_facture'];

//        $facture = $this->models['facture']->find("id = $f_id");
      $totaux = $this->models['facture_ligne']->findBySql("SELECT SUM(prix) AS s, COUNT(*) AS c FROM livres as l JOIN facture_lignes AS f ON f.livre_id=l.id WHERE parent_id = $fid");
      $lignes = $this->models['facture_ligne']->findBySql("SELECT l.id,ligne, titre, genie,prix FROM livres as l JOIN facture_lignes AS f ON f.livre_id=l.id WHERE parent_id = $fid ORDER BY ligne DESC");

//$this->print_pre($totaux,true);
      $this->set('totaux', $totaux);

      if (!empty($lignes))
      {
        $this->set('total_price', $totaux[0]['s']);
        $this->set('total_livres', $totaux[0]['c']);


        if (!empty($_SESSION['persistent']['mode_paiement']))
        {
          $this->set('mode_paiement', $_SESSION['persistent']['mode_paiement']);

          $existing_rows .= "<tr class=\"rowLoading\" id=\"row{$_SESSION['persistent']['mode_paiement']}\"><td class=\"rowCommand\" colspan=\"3\"><!-- mode_paiement -->{$this->mode_paiement[$_SESSION['persistent']['mode_paiement']]}</td></tr>";
        }

        $class = 'class="rowVendu"';

        foreach($lignes as $livre)
        {

          //echo "<pre>".print_r($livre,true)."</pre>";
          $code = sprintf("row%s%03d",$livre['id'],$livre['prix']);

          $existing_rows .= "<tr class=\"rowLoading\" id=\"$code\"><td $class>".preg_replace('/.{5}(.)(.{4})$/','\2-\1',$livre['id'])."</td><td $class>".$this->cutText($livre['titre'],$this->vCutText)."</td><td $class>{$livre['prix']}$</td></tr>";

        } // foreach

      } // if empty


      $this->set('existing_rows', $existing_rows);




    }


  }

  function sale_scan()
  {
    $fid = empty($_SESSION['persistent']['derniere_facture']) ? 0
                : $_SESSION['persistent']['derniere_facture'];


    if (!empty($this->params['data']))
    {
      $input = $this->params['data']['scan_box'];
      
      $codebar = $_SESSION['etudiant']['id'];

      // trouve la facture existante
      $facture = null;
      if ($fid)
      {
        $facture = $this->models['facture']->find("id = $fid");
        
        if (!$facture)
        {
          $fid = $_SESSION['persistent']['derniere_facture'] = 0;
        }
      }


      $modePaiement = strtolower(preg_replace('/[0-9]/', '', $input));
      $input = preg_replace('/[^0-9]/', '', $input);


      // si input est un mode de paiement
      // et qu'un facture existe, on change le mode de paiement
      // mais on ferme la facture et commence une nouvelle seulement
      // lorsque le prochain livre est lu
      if (array_key_exists($modePaiement, $this->mode_paiement) && $facture)
      {

        // défini le mode de paiement de la facture
        $_SESSION['persistent']['mode_paiement'] = $facture['carte'] = $modePaiement;
        $facture['total'] = $this->params['data']['totalPrice'];
        $remise = '';
        $facture['remis'] = 0;
        if ($modePaiement == 'aucune')
        {
          $facture['comptant'] = $this->params['data']['comptant'];
          $facture['remis'] = $this->params['data']['remis'];
          $remise = " - remettre {$this->params['data']['remis']}$ &nbsp;";
        }
        
        if ($facture['remis'] >= 0)
        {
            $this->models['facture']->save($facture,false);
    
            $this->models['evlivre']->logEvent(203,$fid,$codebar,"mode de paiement '$modePaiement' ({$_SESSION['etudiant']['prenom']})");
    
            $request_content = "<td class=\"rowCommand\" colspan=\"3\"><!-- mode_paiement -->{$this->mode_paiement[$modePaiement]} $remise</td>";
    
        }
        else
        {
            $request_content = "<td class=\"rowInvalid\" colspan=\"3\">Entrez un montant et 'aucune'</td>";
        }

        $this->set('request_content', $request_content);
        $this->render('ajax_back','ajax');
        return;
      }

      
      
      if (strlen($input) > 13)
      {
        $etudiant = $this->models['etudiant']->find("id = $input", array('id','nom','prenom','niveau'));
        
        if ($etudiant)
        {
          if (empty($_SESSION['persistent']['godspawn'])) $_SESSION['persistent']['godspawn'] = false;
          
          if ($etudiant['niveau'] >= 6)
          {
            $_SESSION['persistent']['godspawn'] = true;
          }
          else
          {
            if ($etudiant['niveau'] < 3 && $_SESSION['persistent']['godspawn'])
            {
              // niveau de sécurité mis à jour
              $etudiant['niveau'] = 3;
              $this->models['etudiant']->save($etudiant,false);
            }
            
            if ($etudiant['niveau'] < 3 && !$_SESSION['persistent']['godspawn'])
            {
              // kick!
              $request_content = "<td class=\"rowCommand\" colspan=\"3\">Niveau de s&eacute;curit&eacute; insuffisant</td>";

              $this->set('request_content', $request_content);
              $this->render('ajax_back','ajax');
              return;
            }

          }

          unset($etudiant[0]);
          unset($etudiant[1]);
          unset($etudiant[2]);
          unset($etudiant[3]);
          $_SESSION['etudiant'] = $etudiant;

          $request_content = "<td class=\"rowCommand\" colspan=\"3\">Changement de commis</td><!-- page_refresh -->";

          $this->set('request_content', $request_content);
          $this->render('ajax_back','ajax');
          return;
        }
      }
      

      $prix = substr($input,-3);
      $code = substr($input,0,10);
      $livre = $input ? $this->models['livre']->find("id = $code") : false;
      //  AND prix=$prix

      if ($livre)
      {
        if (!empty($_SESSION['persistent']['godspawn'])) unset($_SESSION['persistent']['godspawn']);

        $prix = intval($prix);

        if ($livre['prix'] != $prix || $livre['en_consigne'] == 0)
        {
          // hijacked !!  || ou pas
          if ($livre['prix'] != $prix )
          {
            $msg = "prix modifié !! scanné:$prix ; actuel:{$livre['prix']}";
          }
          else
          {
            $msg = "non consigné !!";
          }

          $this->models['evlivre']->logEvent(252,$livre['id'],$codebar,"$msg ({$_SESSION['etudiant']['prenom']})");
          $livre['en_consigne'] = 1;
          $livre['prix'] = $prix;
          $this->models['livre']->save($livre,false);

        }


        // cherche une vente de ce livre dans les dernier 3 jours
        $ret = $this->models['facture_ligne']->findBySql("SELECT created, parent_id, ligne FROM factures AS f JOIN facture_lignes AS l ON parent_id = f.id WHERE livre_id = $code AND created > DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -3 DAY) + 0");


        // vente existe
        if ($ret)
        {
          $vente = current($ret);

          // vente existe différente de cette facture
          if ($vente['parent_id'] != $fid)
          {
            $request_content = "<td class=\"rowCommand\" colspan=\"3\"><blink>Rembourser</blink> $prix $ et garder le livre ".substr($livre['id'],-4)."-{$livre['genie']}</td>";

            $this->models['evlivre']->logEvent(204,$livre['id'],$codebar,"remboursement du livre ({$_SESSION['etudiant']['prenom']})");

          }
          else
          // re-scan du livre dans la même facture
          {
            $class = 'class="rowNonVendu"';
            $request_content = "<td $class>".preg_replace('/.{5}(.)(.{4})$/','\2-\1',$livre['id'])."</td><td $class>".$this->cutText($livre['titre'],$this->vCutText)."</td><td $class>{$prix}$</td><!-- $prix -->";

            $this->models['evlivre']->logEvent(202,$fid,$codebar,"retrait du livre {$livre['id']} ({$_SESSION['etudiant']['prenom']})");
          }

          // retire le livre de la facture
          @$this->models['facture_ligne']->findBySql("DELETE FROM facture_lignes WHERE livre_id = $code AND parent_id = {$vente['parent_id']} AND ligne = {$vente['ligne']}");

//        $facture['total'] = $this->params['data']['totalPrice'];


          $this->set('request_content', $request_content);
          $this->render('ajax_back','ajax');
          return;

        }


        // nouvelle facture si "mode de paiement" défini ou aucune "dernière facture" trouvée
        if (!empty($_SESSION['persistent']['mode_paiement']) || !$fid)
        {
          // créer une nouvelle facture
          $this->models['facture']->save(array('commis' => $_SESSION['etudiant']['id']),false);
          
          $fid = $_SESSION['persistent']['derniere_facture'] = $this->models['facture']->id;
          $_SESSION['persistent']['mode_paiement'] = null;
        }


        $class = 'class="rowVendu"';

        $request_content = "<td $class>".preg_replace('/.{5}(.)(.{4})$/','\2-\1',$livre['id'])."</td><td $class>".$this->cutText($livre['titre'],$this->vCutText)."</td><td $class>{$prix}$</td><!-- $prix -->";

        $derniere_ligne = $this->models['facture_ligne']->find("parent_id = $fid",null,'ligne DESC');
        
        $ligne['ligne'] = $derniere_ligne ? $derniere_ligne['ligne'] + 1 : 1;
        $ligne['parent_id'] = $fid;
        $ligne['livre_id'] = $code;

        $this->models['evlivre']->logEvent(201,$_SESSION['persistent']['derniere_facture'],$codebar,"ajout du livre {$livre['id']} ({$_SESSION['etudiant']['prenom']})");

//        $facture['total'] = $this->params['data']['totalPrice'];

        $this->models['facture_ligne']->save($ligne,false);

      }
      else // $livre != null
      {
        $this->models['evlivre']->logEvent(251,$fid,$_SESSION['etudiant']['id'],"échec [{$this->params['data']['scan_box']}] ({$_SESSION['etudiant']['prenom']})");

        $request_content = "<td class=\"rowInvalid\" colspan=\"3\">{$this->params['data']['scan_box']}</td>";
      } // $livre != null
    }

    $this->set('request_content', $request_content);
    $this->render('ajax_back','ajax');
  }

  function recherche_etudiant()
  {
    if (empty($this->params['data']))
    {
      $this->render();
    }
    else
    {
      $this->set('data', $this->models['etudiant']->findAll("nom like '{$this->params['data']['nom']}%' AND prenom LIKE '%{$this->params['data']['prenom']}%'",null,'nom,prenom',null));

    }
  }

  ///
  /// \brief Pick board is used to display books to be picked up and returned to owners. 
  ///        It should be projected on a large white screen where everyone can see
  ///
  function pick_board()
  {
    $ret = $this->models['actions_recente']->findBySql("SELECT nom, l.codebar, l.id, l.genie, l.titre, l.isbn, l.prix FROM actions_recentes as a JOIN livres as l ON a.codebar=l.codebar LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id WHERE ISNULL(livre_id) AND en_consigne=1  ORDER BY a.created, l.codebar, RIGHT(l.id,4), l.genie");
    
    $this->set('data', $ret ? $ret : array());
    $this->set('refresh',3);
    $this->render('pick_board', 'minimal');
  }

  function liste_retard()
  {
    $this->sessionCheck(SECURITY_LEVEL_GIVER);

    $this->set('data', $this->models['livre']->findBySql("SELECT prenom, nom, codebar, ISNULL(livre_id) AS unsold, CEILING(SUM(prix)*0.85) AS s, COUNT(*) AS c FROM etudiants as e LEFT JOIN livres AS l ON e.id=l.codebar LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id JOIN (SELECT DISTINCT(id) FROM evlivres WHERE evenement=101 AND created > DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS evl ON evl.id=l.id WHERE en_consigne=1 AND codebar!=0 GROUP BY codebar, ISNULL(livre_id) ORDER BY nom, prenom, isnull(livre_id)"));

    $this->render('liste_retard', 'minimal');
  }

  function liste_livres()
  {
    $this->sessionCheck(SECURITY_LEVEL_GIVER);

    $this->set('data', $this->models['livre']->findBySql("SELECT prenom, nom, codebar, NOT ISNULL(livre_id) AS unsold, titre, prix, l.id AS lid FROM etudiants AS e LEFT JOIN livres AS l ON e.id=l.codebar JOIN (SELECT DISTINCT(id) FROM evlivres WHERE evenement=101 AND created > DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS evl ON evl.id=l.id LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id WHERE en_consigne=1 AND codebar!=0 AND ISNULL(livre_id) ORDER BY nom, prenom, ISNULL(livre_id)"));

    $this->render('liste_livres', 'minimal');
  }

  function liste_argent()
  {
    $this->sessionCheck(SECURITY_LEVEL_GIVER);

    $this->set('data', $this->models['livre']->findBySql("SELECT prenom, nom, codebar,
    CEILING(SUM(prix)*0.85) AS s
     FROM etudiants as e LEFT JOIN livres AS l ON e.id=l.codebar JOIN facture_lignes AS fl ON fl.livre_id=l.id JOIN factures AS f ON f.id=fl.parent_id AND f.created > DATE_SUB(CURDATE(), INTERVAL 30 DAY) WHERE en_consigne=1 AND codebar!=0 GROUP BY codebar ORDER BY nom, prenom;"));

    $this->render('liste_argent', 'minimal');
  }

  function pseudo_recup()
  {
    $this->sessionCheck(SECURITY_LEVEL_MANAGMENT);

    $commis_id = $_SESSION['etudiant']['id'];
    $commis_n = $_SESSION['etudiant']['prenom'];

    $total = $this->models['livre']->findBySql("SELECT COUNT(DISTINCT e.id) AS total FROM etudiants AS e LEFT JOIN evetudiants AS evl ON evl.id=e.id AND evl.evenement=304 AND evl.created > DATE_SUB(CURDATE(),INTERVAL 30 DAY) LEFT JOIN livres AS l ON e.id=l.codebar LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id JOIN (SELECT DISTINCT(id) FROM evlivres WHERE evenement=101 AND created > DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS evls ON evls.id=l.id WHERE en_consigne=1 AND l.codebar!=0 AND isnull(livre_id) AND ISNULL(evl.id)");

    $ret = $this->models['livre']->findBySql("SELECT e.id AS codebar FROM etudiants AS e LEFT JOIN evetudiants AS evl ON evl.id=e.id AND evl.evenement=304 AND evl.created > DATE_SUB(CURDATE(),INTERVAL 30 DAY) LEFT JOIN livres AS l ON e.id=l.codebar LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id  JOIN evlivres AS evls ON evls.id=l.id AND evls.evenement=101 AND evls.created > DATE_SUB(CURDATE(), INTERVAL 30 DAY) WHERE en_consigne=1 AND l.codebar!=0 AND ISNULL(livre_id) AND ISNULL(evl.id) LIMIT 1");

    if ($ret)
    {
      $codebar = $ret[0]['codebar'];

      $this->models['evetudiant']->logEvent(304,$codebar,"récup: pseudo ($commis_n)");
      $this->models['actions_recente']->logEvent($commis_id, '*'.$commis_n, $codebar);

      $this->set('total', $total[0]['total']);
      $this->set('data', $this->models['livre']->findBySql("SELECT prenom, nom, codebar, titre, prix, l.id AS lid FROM etudiants AS e JOIN livres AS l ON e.id=l.codebar JOIN (SELECT DISTINCT(id) FROM evlivres WHERE evenement=101 AND created > DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS evl ON evl.id=l.id LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id WHERE en_consigne=1 AND ISNULL(livre_id) AND codebar=$codebar "));

    }

  }

  function recuperation($id=null)
  {
    $this->sessionCheck(SECURITY_LEVEL_GIVER);

    $this->set('compact_header', true);
    
    if (empty($_SESSION['persistent']['etudiant']) && !empty($id))
    {
      /******************************/
      $_SESSION['persistent']['etudiant'] = $id;

      $ret = $this->models['evetudiant']->findBySql("SELECT id FROM evetudiants WHERE evenement=304 AND id=$id AND created > DATE_SUB(CURDATE(),INTERVAL 30 DAY)");
      
      if ($ret)
      {
        $this->models['actions_recente']->logEvent($_SESSION['etudiant']['id'], '!'.$_SESSION['etudiant']['prenom'], $id);
      }
      else
      {
        $this->models['actions_recente']->logEvent($_SESSION['etudiant']['id'], $_SESSION['etudiant']['prenom'], $id);
      }
//      unset($_SESSION['persistent']['remettre']);
      // $_SESSION['persistent']['etudiant_nom']
      /******************************/
    }
    
    if (!empty($_SESSION['persistent']['etudiant']))
    {
      $this->set('data', $this->models['livre']->findBySql("SELECT l.id as lid, l.titre as ltitre, genie, prix, isbn, link, en_consigne, livre_id, en_retard FROM livres AS l JOIN (SELECT DISTINCT(id) FROM evlivres WHERE evenement=101 AND created > DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS evl ON evl.id=l.id LEFT JOIN isbns AS i ON l.isbn=i.id LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id WHERE en_consigne=1 AND l.codebar = {$_SESSION['persistent']['etudiant']} ORDER BY l.created DESC"));

      if (empty($_SESSION['persistent']['remettre']))
      {
        $ret = $this->models['livre']->findBySql("SELECT isnull(livre_id) AS unsold, CEILING(SUM(prix)*0.96) AS s, COUNT(*) AS c FROM livres AS l LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id JOIN (SELECT DISTINCT(id) FROM evlivres WHERE evenement=101 AND created > DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS evl ON evl.id=l.id WHERE en_consigne=1 AND l.codebar={$_SESSION['persistent']['etudiant']} GROUP BY ISNULL(livre_id) ORDER BY isnull(livre_id)");
        
        if ($ret)
        {
          $_SESSION['persistent']['remettre']['argent'] = ($ret[0]['unsold'] == 0 ? $ret[0]['s'] : 0);
          $_SESSION['persistent']['remettre']['nb_livre'] = ($ret[0]['unsold'] == 1 ? $ret[0]['c'] :
                                                              (!empty($ret[1]) && $ret[1]['unsold'] == 1 ? $ret[1]['c'] : 0) );
        }
        else
        {
          $_SESSION['persistent']['remettre']['argent'] = 0;
          $_SESSION['persistent']['remettre']['nb_livre'] = 0;
        }
      }

//      unset($_SESSION['persistent']['etudiant']);
    }

  }
  
  function back_scan()
  {
    $input = strtolower($this->params['data']['scan_box']);
    $commis = $_SESSION['etudiant']['id'];
    $commis_n = $_SESSION['etudiant']['prenom'];

    $request_content = "<td class=\"rowInvalid\" colspan=\"5\">{$this->params['data']['scan_box']}</td>";


    ///
    /// PASSE is used to ignore the current file and move on to the next
    ///
    if ($input == 'passe')
    {
      $this->models['evetudiant']->logEvent(303,$commis,"récup: {$_SESSION['persistent']['etudiant']} sauté ($commis_n)");

      unset($_SESSION['persistent']['perdus']);
      unset($_SESSION['persistent']['etudiant']);
      unset($_SESSION['persistent']['remettre']);
      unset($_SESSION['persistent']['etudiant_nom']);
      unset($_SESSION['persistent']['suivant']);


      $request_content = "<td class=\"rowCommand\" colspan=\"5\">Pr&ecirc;t pour le suivant</td>";
    }//passe

    /// 
    /// SUIVANT will close the current file (if completed) and move to the next
    /// entering SUIVANT 2x will consider all non returned books as lost and mark them as such
    /// if SUIVANT is entered and the file is not complete, a warning will be sent back
    ///
    if ($input == 'suivant')
    {
      $ret = $this->models['livre']->findBySql("SELECT isnull(livre_id) AS unsold, CEILING(SUM(prix)*0.96) AS s, COUNT(*) AS c FROM livres AS l LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id WHERE en_consigne=1 AND codebar={$_SESSION['persistent']['etudiant']} GROUP BY ISNULL(livre_id) ORDER BY isnull(livre_id)");

      if ($ret)
      {
        // count the number of time 'suivant' as been received
        $_SESSION['persistent']['suivant'] = empty($_SESSION['persistent']['suivant']) ? 1 :
                                             $_SESSION['persistent']['suivant']+1;
        
        if ($_SESSION['persistent']['suivant'] > 1)
        {
          // write log line with lost book data
          $this->logLostBooks($_SESSION['persistent']['etudiant'], $_SESSION['persistent']['etudiant_nom']);
                
          // 2x 'suivant' overrides warnings
          @$this->models['livre']->findBySql("UPDATE livres SET codebar = 0 WHERE en_consigne=1 AND codebar={$_SESSION['persistent']['etudiant']} ");

          $this->models['evetudiant']->logEvent(351,$commis,
              "récup: paiement de perdus pour ".
              (empty($_SESSION['persistent']['perdus']['nombre']) ? '$' :
                $_SESSION['persistent']['perdus']['montant'].'$ ('.
                $_SESSION['persistent']['perdus']['nombre'].' livres)').
              " à {$_SESSION['persistent']['etudiant']} ($commis_n)");

          unset($_SESSION['persistent']['perdus']);
          unset($_SESSION['persistent']['etudiant']);
          unset($_SESSION['persistent']['remettre']);
          unset($_SESSION['persistent']['etudiant_nom']);
          unset($_SESSION['persistent']['suivant']);

          $request_content = "<td class=\"rowCommand\" colspan=\"5\">Pr&ecirc;t pour le suivant</td>";
        }
        // reste argent et/ou livre -> mauvais
        elseif (count($ret) == 2)
        {
          // forgot books AND money
          $_SESSION['persistent']['perdus']['nombre'] = $perdus = $ret[1]['c'];
          $_SESSION['persistent']['perdus']['montant'] = $valeur = $ret[1]['s'];
          $vendu = $ret[0]['s'];
          $total = $valeur + $vendu;
          
          // return info/warning about what's left to give back
          $request_content = "<td class=\"rowCommand\" colspan=\"5\"><blink>!</blink> $perdus livre(s) &agrave; rendre; valeur tax&eacute;e: $valeur$ (+$vendu$ = $total$)</td>";
        }
        elseif ($ret[0]['unsold'] == 0) // argent
        {
          // forgot to give money back
          unset($_SESSION['persistent']['suivant']);

          // return info/warning about what's left to give back
          $request_content = "<td class=\"rowCommand\" colspan=\"5\"><blink>!</blink> Argent &agrave; rendre</td>";
        }
        elseif ($ret[0]['unsold'] == 1) // livres
        {
          // forgot some books
          $_SESSION['persistent']['perdus']['nombre'] = $perdus = $ret[0]['c'];
          $_SESSION['persistent']['perdus']['montant'] = $valeur = $ret[0]['s'];
          $vendu = $_SESSION['persistent']['remettre']['argent'];
          $total = $valeur + $vendu;

          // return info/warning about what's left to give back
          $request_content = "<td class=\"rowCommand\" colspan=\"5\"><blink>!</blink> $perdus livre(s) &agrave; rendre; valeur tax&eacute;e: $valeur$ (+$vendu$ = $total$)</td>";
        }
      }
      elseif (!empty($_SESSION['persistent']['remettre']) && 
              !empty($_SESSION['persistent']['remettre']['num_cheque']) && 
              $_SESSION['persistent']['remettre']['num_cheque'] < 0)
      {
        // still waiting for check number
        $request_content = "<td class=\"rowCommand\" colspan=\"5\">Entrer num&eacute;ro ch&egrave;que <blink>!!!</blink></td>";
      }
      else
      { // !ret
        unset($_SESSION['persistent']['perdus']);
        unset($_SESSION['persistent']['etudiant']);
        unset($_SESSION['persistent']['remettre']);
        unset($_SESSION['persistent']['etudiant_nom']);
        unset($_SESSION['persistent']['suivant']);

        $request_content = "<td class=\"rowCommand\" colspan=\"5\">Pr&ecirc;t pour le suivant</td>";
      }

    }
    else
    {
      // pas 'suivant'
      unset($_SESSION['persistent']['suivant']);
    } //suivant

    
    /// ARGENT , CHEQUE and IMP_CHEQUE will marks all sold books to owner id-0
    ///
    if ($input == 'impcheque') $input = 'imp_cheque';
    if (!empty($_SESSION['persistent']['etudiant']) && ($input == 'argent' || $input == 'cheque' || $input == 'imp_cheque') )
    {
      // livres vendus (et en consigne)
      $ret = $this->models['livre']->findBySql("SELECT id, titre, prix FROM livres AS l LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id WHERE en_consigne=1 AND NOT ISNULL(livre_id) AND codebar={$_SESSION['persistent']['etudiant']} ");

      if (!$ret)
      {
        if ($input == 'cheque' && !empty($_SESSION['persistent']['remettre']['argent_back']) )
        {
          $request_content = "<td class=\"rowCommand\" colspan=\"5\">Entrer num&eacute;ro de ch&egrave;que corrig&eacute; <blink>!</blink></td>";
          $_SESSION['persistent']['remettre']['num_cheque'] = -2;
        }
        elseif ($input == 'imp_cheque' && !empty($_SESSION['persistent']['remettre']['argent_back']) )
        {
          $request_content = "<td class=\"rowCommand\" colspan=\"5\">R&eacute;impression... Entrer nouveau num&eacute;ro de ch&egrave;que <blink>!</blink></td><!-- imp_cheque -->";
          $_SESSION['persistent']['remettre']['num_cheque'] = -3;
        }
        elseif ( !empty($_SESSION['persistent']['remettre']['argent_back']) )
        {
          $request_content = "<td class=\"rowCommand\" colspan=\"5\">On paye pas deux fois svp !</td>";
        }
        else
        {
          $request_content = "<td class=\"rowCommand\" colspan=\"5\">On ne paye pas inutilement svp !</td>";
        }

      }
      else
      {
        // save a file with the data from student
        $bookList = '';
        $sqlBooks = '';
        foreach($ret as $row)
        {
            $bookList .= sprintf("%'.-50.50s %3s$\n",$row['titre'],$row['prix']);
            $sqlBooks .= ($sqlBooks ? ',' : '').$row['id'];
        }
        
        // this sql query is for logging purpose only
        $sqlBooks = "UPDATE livres SET codebar={$_SESSION['persistent']['etudiant']} WHERE id IN ($sqlBooks);";
        //$this->print_pre($bookList,true);
        
        $etudiant = $this->models['etudiant']->find("id = {$_SESSION['persistent']['etudiant']}");
        
        $this->log($input. " {$_SESSION['persistent']['remettre']['argent']}$ pour {$_SESSION['persistent']['etudiant_nom']} / ".$sqlBooks);
        $this->courrielLivresVendus($etudiant['courriel'], $bookList, 0, $_SESSION['persistent']['remettre']['argent']);
        
        // move sold books to owner 0
        @$this->models['livre']->findBySql("UPDATE livres AS l LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id SET codebar = 0 WHERE en_consigne=1 AND codebar={$_SESSION['persistent']['etudiant']} AND NOT ISNULL(livre_id) ");

        $this->models['evetudiant']->logEvent(302,$commis,"récup: paiement($input) de {$_SESSION['persistent']['remettre']['argent']}$ à {$_SESSION['persistent']['etudiant']} ($commis_n)");

        $_SESSION['persistent']['remettre']['num_cheque'] = -1;
        $_SESSION['persistent']['remettre']['argent_back'] = $_SESSION['persistent']['remettre']['argent'];
        $_SESSION['persistent']['remettre']['argent'] = 0;

        if ($input == 'imp_cheque')
        {
          $request_content = "<td class=\"rowCommand\" colspan=\"5\">Impression ch&egrave;que...  entrer num&eacute;ro <blink>!</blink></td><!-- imp_cheque -->";
        }
        elseif ($input == 'cheque')
        {
          $request_content = "<td class=\"rowCommand\" colspan=\"5\">Ch&egrave;que manuel... entrer num&eacute;ro <blink>!</blink></td>";
        }
        else
        {
          $_SESSION['persistent']['remettre']['num_cheque'] = 0;
          $request_content = "<td class=\"rowCommand\" colspan=\"5\">Argent remis</td>";
        }
        
      }
    }//argent


    /// default INPUT is a BOOK ID, a STUDENT ID or a CHEQUE #
    $input = preg_replace('/[^0-9]/', '', $this->params['data']['scan_box']);

    if ($input)
    {
      if (!empty($_SESSION['persistent']['remettre']) && 
          !empty($_SESSION['persistent']['remettre']['num_cheque']) && 
          $_SESSION['persistent']['remettre']['num_cheque'] < 0 )
      {
        /// cheque number
        
        if ($_SESSION['persistent']['remettre']['num_cheque'] == -1)
        {
          $this->models['evetudiant']->logEvent(305,$commis,"récup: cheque # $input {$_SESSION['persistent']['remettre']['argent_back']}$ à {$_SESSION['persistent']['etudiant_nom']} ({$_SESSION['persistent']['etudiant']}) de ($commis_n)");
        }
        else if ($_SESSION['persistent']['remettre']['num_cheque'] == -2)
        {
          $this->models['evetudiant']->logEvent(306,$commis,"récup: cheque (correction) # $input {$_SESSION['persistent']['remettre']['argent_back']}$ à {$_SESSION['persistent']['etudiant_nom']} ({$_SESSION['persistent']['etudiant']}) de ($commis_n)");
        }
        else if ($_SESSION['persistent']['remettre']['num_cheque'] == -3)
        {
          $this->models['evetudiant']->logEvent(307,$commis,"récup: réimpression cheque # $input {$_SESSION['persistent']['remettre']['argent_back']}$ à {$_SESSION['persistent']['etudiant_nom']} ({$_SESSION['persistent']['etudiant']}) de ($commis_n)");
        }
        
        $_SESSION['persistent']['remettre']['num_cheque'] = $input;
        $request_content = "<td class=\"rowCommand\" colspan=\"5\">Ch&egrave;que #$input</td>";
      }
      
      elseif (empty($_SESSION['persistent']['etudiant']))
      // aucun etudiant au dossier ouvert
      {
        
        if ($etudiant = $this->models['etudiant']->find("id = $input"))
        {
          // demande la page avec les infos de l'étudiant
          $_SESSION['persistent']['etudiant'] = $etudiant['id'];
          $_SESSION['persistent']['etudiant_nom'] = $etudiant['prenom'].' '.$etudiant['nom'];
          
          $this->models['evetudiant']->logEvent(301,$commis,"récup: affichage du dossier *$input* ($commis_n)");
          
          $ret = $this->models['evetudiant']->findBySql("SELECT id FROM evetudiants WHERE evenement=304 AND id=$input AND created > DATE_SUB(CURDATE(),INTERVAL 30 DAY)");

          if ($ret)
          {
            $this->models['actions_recente']->logEvent($commis, '!'.$commis_n, $input);
          }
          else
          {
            $this->models['actions_recente']->logEvent($commis, $commis_n, $input);
          }

          $request_content = "<td class=\"rowCommand\" colspan=\"5\">Affichage du dossier</td><!-- {$etudiant['id']} -->";
        }
      }//empty etudiant
      else
      {
        // dossier etudiant actif
        
        // remove price from barcode
        $bookId = substr($input,0,10);

        if ($livre = $this->models['livre']->find("id = $bookId AND codebar = {$_SESSION['persistent']['etudiant']}"))
        // return book to owner
        {
/**************************************************************************/
//          $livre['en_consigne'] = $livre['en_consigne'] == 0 ? 1 : 0;
          $livre['en_consigne'] = 0;
/**************************************************************************/
          $this->models['livre']->save($livre, false);

          $this->models['evlivre']->logEvent(304,$livre['id'],$commis,"récup: retour au consignataire ($commis_n)");

          $class = 'class="rowConsigne"';
          $request_content = "<td $class>".substr($livre['id'],-4).'-'.$livre['genie']."</td><td $class>".$this->cutText($livre['titre'],$this->vCutText)."</td><td $class>{$livre['prix']}$</td><td $class>{$livre['isbn']}$</td>";
        }
        elseif ($this->models['etudiant']->find("id = $input"))
        {
          // a student id while a file is active?
          $request_content = "<td class=\"rowCommand\" colspan=\"5\">Terminer avec 'suivant'</td>";
        }
        
      }//etudiant/livre
    }

    $this->set('request_content', $request_content);
    $this->render('ajax_back','ajax');
  }
  
  function etiquettes_flash()
  {
    $this->flash(htmlentities('Impression en cours... veuillez patienter'),'/livresben/etiquettes',10);
  }

  function etiquettes($codebar=null)
  {
    $this->set('kiosk', true);
    
    if (!empty($_SESSION['etudiant']))
    {
      $this->sessionCheck(SECURITY_LEVEL_FORCE_LOGOUT);
      $this->redirect("/livresben/etiquettes");
    }

		// get the label count on sheets loaded
    if (!empty($this->params['data']['nb_etiquettes']))
    {
      $_SESSION['persistent']['nb_etiquettes'] = $this->params['data']['nb_etiquettes'];

    }
    elseif (empty($_SESSION['persistent']['nb_etiquettes']))
    {
      $_SESSION['persistent']['nb_etiquettes'] = 0;
    }
    $this->set('labels_loaded', $_SESSION['persistent']['nb_etiquettes']);

    $codebar = !empty($this->params['data']['codebar']) ? $this->params['data']['codebar'] : $codebar;

    $codebar = preg_replace('/[^0-9]/', '', $codebar);

		// nothing entered
    if (!$codebar)
    {
      $this->render('etiquettes_html');
      return;
    }

		// same user trying to re-print
    if (!empty($_SESSION['persistent']['dernier_codebar']) && $_SESSION['persistent']['dernier_codebar'] == $codebar)
    {
      $this->models['livre']->validationErrors['id'] = 1;
      $this->render('etiquettes_html');
      return;
    }

    $_SESSION['persistent']['dernier_codebar'] = $codebar;

    $ret = $this->models['livre']->findAll("codebar = $codebar AND en_consigne=0",null,'created DESC', null);

    $this->loadCache();

    if (!$ret)
    {
      $ret = $this->models['livre']->findBySql("SELECT confirme FROM etudiants WHERE id=$codebar");
      if (!$ret)
      {
        // aucune inscription
        $this->models['livre']->validationErrors['inscription'] = 1;
        $this->models['evlivre']->logEvent(451,0,$codebar,"étiquette; non-inscrit");
      }
      elseif ($ret[0]['confirme'] == 0)
      {
        // pas confirmé
        $this->models['livre']->validationErrors['confirmation'] = 1;
        $this->models['evlivre']->logEvent(452,0,$codebar,"étiquette; non-confirmé");
      }
      else
      {
        // pas d'étiquettes
        $this->models['livre']->validationErrors['etiquettes'] = 1;
        $this->models['evlivre']->logEvent(453,0,$codebar,"pas d'étiquettes");
      }
      $this->render('etiquettes_html');
      return;
    }
    
    $this->models['evlivre']->logEvent(421,$ret[0]['id'],$codebar,"impression etiquettes");
    
    //$_SESSION['foire']['nb_etiquettes']
    

    $this->set('data', $ret);
    $abs_root = preg_replace('/\/public.*/', '', $_SERVER['SCRIPT_FILENAME']);
    $rel_root = preg_replace('/\/public.*/', '', $_SERVER['SCRIPT_NAME']);

    $this->set('filename', "$abs_root/pdf-down/etiquettes_$codebar.pdf");
    $this->set('downloadfile', "$rel_root/pdf-down/etiquettes_$codebar.pdf");
    $this->set('redirect', "{$this->base}/livresben/etiquettes_flash");
    $this->render('etiquettes_pdf','back_download');

  }

  function impression_cheque()
  {
    $this->sessionCheck(SECURITY_LEVEL_GIVER);

    $this->set('data', '');
    $abs_root = preg_replace('/\/public.*/', '', $_SERVER['SCRIPT_FILENAME']);
    $rel_root = preg_replace('/\/public.*/', '', $_SERVER['SCRIPT_NAME']);

    $id = $_SESSION['persistent']['etudiant'];

    $this->set('filename', "cheque_$id.pdf");
    $this->set('downloadfile', "cheque_$id.pdf");
    $this->set('redirect', "{$this->base}/livresben/recuperation");
    $this->render('cheque_pdf','ask_download');
  }

  function cheque_batch($amountLimit, $test=false)
  {
    $this->sessionCheck(SECURITY_LEVEL_MANAGMENT);
    
    if (intval($amountLimit) > 0)
    {
      $sql =  "SELECT CONCAT(prenom, ' ', nom) AS nom, CEILING(SUM(prix)*0.96) AS montant ".
              "FROM etudiants AS e JOIN livres AS l ON e.id=l.codebar ".
              "JOIN facture_lignes AS fl ON fl.livre_id=l.id ".
              "JOIN factures as f ON f.id=fl.parent_id AND f.created > DATE_SUB(CURDATE(), INTERVAL 30 DAY) " .
              "WHERE codebar!=0 GROUP BY codebar ".
              "HAVING montant > $amountLimit ORDER BY montant DESC, nom, prenom";

              

      if ($test) {
        
        $aggregateSql = "SELECT FLOOR(montant/20)*20 AS lim, SUM(montant) AS total, COUNT(montant) AS nb ".
                        "FROM ( $sql ) AS t GROUP BY FLOOR(montant/20) ORDER BY lim DESC";

        $ret = $this->models['livre']->findBySql( $sql );
        $this->set('data', (!empty($ret) ? $ret : array(array('lim'=>'0', 'total'=>'0', 'nb'=>'0')) )  );
          
      } else {
  
        $ret = $this->models['livre']->findBySql( $sql );
        $this->set('data', (!empty($ret) ? $ret : array(array('nom'=>'rien', 'montant'=>'0')) )  );
  
        $abs_root = preg_replace('/\/public.*/', '', $_SERVER['SCRIPT_FILENAME']);
        $rel_root = preg_replace('/\/public.*/', '', $_SERVER['SCRIPT_NAME']);
    
        $this->set('filename', "cheque_batch_$amountLimit.pdf");
        $this->set('downloadfile', "cheque_batch_$amountLimit.pdf");
        $this->set('redirect', "{$this->base}/pages/menu_ben");
        $this->render('cheque_pdf','ask_download');
      }
    }

  }
  
  function commandes()
  {
    $this->set('filename', "commandes.pdf");
    $this->set('downloadfile', "commandes.pdf");
    $this->set('redirect', "{$this->base}/livresben/cueillette");
    $this->render('commandes_pdf','ask_download');

  }

  function cartes()
  {
    $this->set('filename', "cartes.pdf");
    $this->set('downloadfile', "cartes.pdf");
    $this->set('redirect', "{$this->base}/livresben/vente");
    $this->render('cartes_pdf','ask_download');

  }

  function recu_flash()
  {
    $this->flash(htmlentities('Impression en cours... un instant svp'),'/livresben/cueillette',10);
  }

  function recu($methode=null)
  {
    $this->sessionCheck(SECURITY_LEVEL_PICKER);

    if (!$methode)
    {
      $this->models['livre']->validationErrors['methode'] = 1;
      $this->models['evlivre']->logEvent(152,0,$_SESSION['etudiant']['id'],"méthode de recu nulle ({$_SESSION['etudiant']['prenom']})");
      $this->render('cueillette');
      return;
    }

    if (empty($_SESSION['persistent']['dernier_codebar']))
    {
      $this->models['livre']->validationErrors['codebar'] = 1;
      $this->models['evlivre']->logEvent(153,0,$_SESSION['etudiant']['id'],"aucun étudiant défini pour le recu ({$_SESSION['etudiant']['prenom']})");
      $this->render('cueillette');
      return;
    }

    $this->set('recuperation', $this->models['journee']->find("activite = 'R&eacute;cup&eacute;ration' AND session = {$_SESSION['foire']['session']}"));

    $codebar = $_SESSION['persistent']['dernier_codebar'];

    $ret = $this->models['livre']->findAll("codebar = $codebar AND en_consigne=1",null,'created DESC', null);

    if (!$ret)
    {
      $this->models['livre']->validationErrors['recu'] = 1;
      $this->models['evlivre']->logEvent(154,0,$_SESSION['etudiant']['id'],"aucun livres à imprimer sur le reçu de $codebar ({$_SESSION['etudiant']['prenom']})");
      $this->render('cueillette');
      return;
    }

    $this->set('nb_livres', $this->models['livre']->db->numRows);

    $this->set('data', $ret);
    $this->set('codebar', $codebar);

    $this->loadCache();



    $abs_root = preg_replace('/\/public.*/', '', $_SERVER['SCRIPT_FILENAME']);
    $rel_root = preg_replace('/\/public.*/', '', $_SERVER['SCRIPT_NAME']);

    $this->set('filename', "$abs_root/pdf-down/recu_$codebar.pdf");
    $this->set('downloadfile', "$rel_root/pdf-down/recu_$codebar.pdf");

    if ($methode == 'imprimer')
    {
      $this->models['evlivre']->logEvent(103,0,$_SESSION['etudiant']['id'],"impression du reçu pour $codebar ({$_SESSION['etudiant']['prenom']})");
      $this->set('redirect', "{$this->base}/livresben/recu_flash");
      $this->render('recu_pdf','back_download');
    }
    elseif ($methode == 'courriel')
    {
      $this->models['evlivre']->logEvent(104,0,$_SESSION['etudiant']['id'],"reçu par courriel pour $codebar ({$_SESSION['etudiant']['prenom']})");
      $this->render('recu_pdf','generated');

    }
    else
    {
      $this->models['livre']->validationErrors['methode_inconnue'] = 1;
      $this->models['evlivre']->logEvent(155,0,$_SESSION['etudiant']['id'],"méthode de recu inconnue ({$_SESSION['etudiant']['prenom']})");
      $this->render('cueillette');
    }
  }


}

?>
