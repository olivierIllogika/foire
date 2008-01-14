<?php


define('FPDF_FONTPATH',ROOT.'modules/fpdf152/font/');
require_once ROOT.'modules/fpdf_label.inc.php';

class LivresController extends LivresHelper
{
  var $minStats = 7;

  function index()
  {
    $this->pageTitle .= htmlentities(" - Mes livres");

    if (!$this->sessionCheck()) return;
    $this->loadCache();
/*
    $this->set('data', $this->models['livre']->findAll("codebar = ".$_SESSION['etudiant']['id'],null,'created DESC'));
*/
		// update stats (only done if necessary)
    $this->models['isbnstat']->update_stats($this->minStats);
    
    // actual data for current view
    $this->set('data', $this->models['livre']->findBySql("SELECT * FROM livres AS l LEFT JOIN isbnstats AS s ON l.isbn=s.id AND s.count>={$this->minStats} LEFT JOIN facture_lignes AS fl ON fl.livre_id=l.id WHERE codebar = {$_SESSION['etudiant']['id']} ORDER BY l.created DESC"));

    $this->models['livre']->countLastFind = $this->models['livre']->db->numRows;
    //  OR (l.titre = '' AND l.isbn=c.id) OR (l.titre = '' AND l.isbn=i.id)
  }


  function ajouter_nouveau()
  {
    if (!$this->sessionCheck()) return;
    $this->loadCache();

    if (empty($this->params['data']))
    {
      $this->render();
    }
    else
    {

      if (!empty($this->params['data']['isbntitre']))
      {
        $cleaned = $this->models['livre']->isbnClean($this->params['data']['isbn']);
        
        if ($cleaned > 4)
        {
          $isbn = substr($cleaned, 0, 9);
          $ret = $this->models['isbn']->findAll("id = $isbn");
          $this->params['data']['titre'] = $ret ? $ret[0]['titre'] : $this->params['data']['titre'];
        }
        elseif ($cleaned == 4)
        {
          $ret = $this->models['coopbook']->findAll("id = $cleaned");
          $this->params['data']['titre'] = $ret ? $ret[0]['titre'] : $this->params['data']['titre'];
        }
      }

      $d = $this->params['data'] = $this->prepare_common($this->params['data']);

      if ($this->params['data'])
      {
        /*
echo '<pre>';
print_r($this->params['data']);
echo '</pre>';
*/
        $this->models['evlivre']->logEvent(431, $d['id'], $d['codebar'], "ajout livre [isbn:{$d['isbn']} titre:{$d['titre']}]");
        $this->flash(htmlentities('Livre ajouté'),'/livres');
      }

    }

  } // ajouter_nouveau

  function supprimer($id)
  {
    if (!$this->sessionCheck()) return;

    $ret = $this->models['livre']->findAll("id=$id");
    if (!$ret || $ret[0]['en_consigne'] == 1)
    {
      // hijacked !
      $this->redirect('/livres/index');
      return;
    }
    $d = $ret[0];

    $this->models['evlivre']->logEvent(434, $d['id'], $d['codebar'], "livre supprimé");
    
    if ($this->models['livre']->del($id))
    {
      $this->flash(htmlentities('Livre supprimé'), '/livres');
    }
  }

  function modifier($id=null)
  {
    if (!$this->sessionCheck()) return;
    $this->loadCache();

    if (!$id)
    {
      $this->redirect('/livres/index');
      return;
    }

		$id = preg_replace('/[^0-9]/', '', $id);
    $ret = $this->models['livre']->findAll("id=$id");
    if ( !$ret || $ret[0]['en_consigne'] == 1)
    {
      // hijacked !
      $this->redirect('/livres/index');
      return;
    }
    $d = $ret[0];

    if (empty($this->params['data']))
    {
      $this->models['livre']->setId($id);
      $this->params['data']= $this->models['livre']->read();
      $this->render();
    }
    else
    {

      if ($this->params['data']['isbn'] == '-')
      {
        unset($this->models['livre']->validate['titre']);
        $this->params['data']['isbn'] = 0;
      }
      $this->params['data']['cours'] = preg_replace('/[- ]/','',strtoupper($this->params['data']['cours']));

      $this->models['livre']->setId($id);

//echo '<pre>'.print_r($this->params['data'],true).'</pre>';die();

      $this->models['livre']->save($this->params['data'],false);


      if ($this->params['data'])
      {
        $this->flash(htmlentities('Livre mis à jour'),'/livres');
        $this->models['evlivre']->logEvent(433, $d['id'], $d['codebar'], "modif. livre");
      }
    }
  } // modifier

  function recherche()
  {
    $this->pageTitle .= htmlentities(" - Recherche");
    //$this->set('totaux', $this->models['livre']->findBySql("SELECT COUNT(*) as total, en_consigne FROM livres WHERE codebar != 29334000487074 GROUP BY en_consigne ORDER BY en_consigne"));
    $total = $this->models['livre']->findBySql("SELECT COUNT(*) as total FROM livres as l join evlivres as evl on l.id=evl.id WHERE l.codebar != 29334000487074 AND evenement=101 AND evl.created > DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND en_consigne=1");
    $this->set('total', $total[0][0]);

//$this->print_pre($total[0][0]);    

    if (!empty($this->params['data']))
    {
      $condition = array();
      
      $isbn =  substr($this->models['livre']->isbnClean($this->params['data']['isbn']), 0, 9);
      if ($isbn)
      {
        $condition[] = "isbn = $isbn";
      }
      
      if ($this->params['data']['titre'])
      {
        $uncleaned = str_replace("'", ' ', trim($this->params['data']['titre']));
        $spaced = str_replace(' ', '  ', $uncleaned);

        $unsplitted = preg_replace('/ [^ ]{1,2} /', ' ',' '.$spaced.' ');

//        $condition[] = "titre like '%".preg_replace('/([ ]+)/', "%' AND titre like '%", $unsplitted)."%'";
        $condition[] = "titre like '%".preg_replace('/([ ]+)/', "%", $unsplitted)."%'";
      }
      
      if ($this->params['data']['cours'])
      {
        $condition[] = "cours like '%".preg_replace('/([ ]+)/', "%' AND cours like '%", trim($this->params['data']['cours']))."%'";
      }

      $condition[] = "evenement = 101";
      $condition[] = "evl.created > DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
      $condition[] = "en_consigne = 1";
      $condition[] = "isnull(livre_id)";
      $conditions = implode(' AND ', $condition);

//      $this->set('data', $this->models['livre']->findAll($conditions));
      if ($conditions)
      {
        $this->set('data', $this->models['livre']->findBySql("SELECT titre, isbn, cours, en_consigne FROM livres AS l LEFT JOIN facture_lignes AS f ON l.id=f.livre_id JOIN evlivres AS evl ON evl.id=l.id WHERE $conditions ORDER BY isbn DESC, titre"));
        $this->models['livre']->countLastFind = $this->models['livre']->db->numRows;
      }

    }
  }

  function rech_isbn()
  {
    if (!$this->sessionCheck(0)) return;
    $this->loadCache();

    if (empty($this->params['data']))
    {
      $this->render('ajouter_nouveau');
    }
    else
    {
      $isbn_code4 = preg_replace('/[^0-9X]/', '', strtoupper($this->params['data']['isbn_rech']));
/*
preg_match('/^([0-9]{4}$)|([0-9]{9}[0-9X]$)/', $isbn_code4,$matches);
echo '<pre>';
print_r($matches);
echo '</pre>';
die();
*/
      // seulement 4 ou 10 caractères
      if (!preg_match('/^([0-9]{4}$)|([0-9]{9}[0-9X]$)/', $isbn_code4))
      {
        $this->models['livre']->validationErrors['isbn_rech'] = 1;
        $this->render('ajouter_nouveau');
        return;
      }

      if (strlen($isbn_code4) == 4)
      {
        // code4
//        $ret = $this->models['coopbook']->findAll("id = $isbn_code4");

        $ret = $this->models['livre']->findBySql("SELECT * FROM coopbooks AS l LEFT JOIN isbnstats AS s ON l.id=s.id WHERE l.id = $isbn_code4 ");

        $this->models['evlivre']->logEvent(437, 0, $_SESSION['etudiant']['id'], "rech. coop $isbn_code4");

        $this->set('info', ( $ret ? current($ret) : null ));

      }
      else
      {
        // isbn
        $valid_isbn = @$this->models['isbn']->is_isbn($isbn_code4);
//echo $valid_isbn.'<br />';
        if (!$valid_isbn)
        {
          // ne peut pas extraire d'ISBN (9 ou 10 caractères)
          // (impossible car déjà validé)
          $this->models['livre']->validationErrors['isbn_rech'] = 1;
        }
        else
        {
          $this->models['evlivre']->logEvent(437, 0, $_SESSION['etudiant']['id'], "rech. isbn $valid_isbn");

          if (strlen($valid_isbn) == 9)
          {
            // checksum non valide, recalcul avec les 9ier caractères et
            // suggère cette information titre
            $isbn10 = $this->models['isbn']->gtin2isbn($valid_isbn, true);

            $info = $this->models['isbn']->getInfo($isbn10);

            $ret = $this->models['isbnstat']->find("count >= {$this->minStats} AND id = $valid_isbn");
            if ($ret)
            {
              $info = array_merge($info,$ret);
            }

            $this->set('sugg', $info);

            $this->set('info', null);
          }
          else
          {
            $info = $this->models['isbn']->getInfo($valid_isbn);

            $ret = $this->models['isbnstat']->find("count >= {$this->minStats} AND id = ".substr($valid_isbn,0,9));
            if ($ret)
            {
              $info = array_merge($info,$ret);
            }

            $this->set('info', $info);
/*
           echo '<pre>';
print_r($info);
echo '</pre>';
*/
          }

        }//valid isbn
      } // isbn/code4
      $this->render('ajouter_nouveau');
    }
  }
/*
  function cueillette()
  {
    $this->sessionCheck(2);

  }

  function pickup_scan()
  {
//    $request_content = '<pre>'.print_r($this->params['data'],true).'</pre>';

    $special_commands = array('vider');

    if (!empty($this->params['data']))
    {
      if (in_array($this->params['data']['scan_box'], $special_commands))
      {
        $cmd = $this->params['data']['scan_box'];
        switch ($cmd)
        {
          case 'vider':
            $request_content = "<td class=\"rowCommand\" colspan=\"4\">$cmd<script type=\"text/javascript\">window.location.reload();</script></td>";
            break;

        }
      }
      else
      {
        $input = preg_replace('/[^0-9]/', '', $this->params['data']['scan_box']);
        
        $ret = $input ? $this->models['livre']->findAll("id = $input") : false;

        if ($ret)
        {
          $livre = current($ret);
          
          $class = 'class="'.($livre['en_consigne'] == 1 ? 'rowUsager':'rowConsigne').'"';
          
          $request_content = "<td $class>".preg_replace('/.{5}(.)(.{4})$/','\1-\2',$livre['id'])."</td><td $class>".substr($livre['titre'],0,35)."...</td><td $class>{$livre['prix']}$</td><td $class>{$livre['isbn']}</td>";

          $livre['en_consigne'] = $livre['en_consigne'] == 1 ? 0 : 1;
          $this->models['livre']->save($livre);
        }
        else
        {
          $request_content = "<td class=\"rowInvalid\" colspan=\"4\">{$this->params['data']['scan_box']}</td>";
        }
      }
//      $request_content .= '<pre>'.print_r($ret,true).'</pre>';

    }

    $this->set('request_content', $request_content);
    $this->render('pickup_back','ajax');
  }

  function vente()
  {
    $this->sessionCheck(3);

  }

  function recuperation()
  {
    $this->sessionCheck(4);

  }
  
  function etiquettes($codebar=null)
  {
    if (!$codebar)
    {
      $this->render('etiquettes_prompt');
      return;
    }
    
    $this->loadCache();

    $this->set('data', $this->models['livre']->findAll("codebar = $codebar",null,'created DESC'));

    $this->set('filename', "../pdf-down/etiquettes_$codebar.pdf");
    $this->render('etiquettes_pdf','download');
  }

*/

}

?>
