<?php

require_once ROOT.'modules/smtp.php';

class FaqsController extends FaqsHelper
{
  var $helpers = array('html', 'ajax', 'Faq');
    
  function index()
  {
    $this->pageTitle .= " - Foire aux questions";

    $this->set('sections', $this->models['faq']->findBySql(
          "SELECT DISTINCT(section) FROM ".$this->models['faq']->tablePrefix."faqs WHERE afficher=1 ORDER BY section"));
          
    $req = $this->models['faq']->findAll("", array('id','section', 'question','reponse','afficher'), 'afficher DESC,section,id');
    $this->set('data', $req);

    if (isset($_SESSION['etudiant']['niveau']))
    {
      $this->set('canMod', ($_SESSION['etudiant']['niveau'] > 8));
    }
    $this->loadCache();    
  }

  function poser()
  {
    $this->pageTitle .= " - Poser votre question";

    $sections = $this->load_sections();

    if (empty($this->params['data']) || empty($this->params['data']['question']))
    {
      $this->render();
    }
    else
    {
      if (empty($this->params['data']['sujet']))
      {
        $this->models['faq']->validationErrors['sujet'] = 1;
        $this->render();
        return;

      }

      if (!empty($_SESSION['etudiant']['id']))
      {
        $req = $this->models['faq']->findBySql("SELECT courriel,nom,prenom FROM etudiants WHERE id = {$_SESSION['etudiant']['id']}");

        $data = current($req);
        
        $courriel = $data['prenom'].' '.$data['nom'].' <'.$data['courriel'].'>';
      }
      else
      {
        if (empty($this->params['data']['courriel']))
        {
          $this->models['faq']->validationErrors['courriel'] = 1;
          $this->render();
          return;
        }
        
        $courriel = $this->params['data']['courriel'];
      }

      $destination = ($this->params['data']['sujet']=='site' ? $GLOBALS['gDevErrorEmail'] : $GLOBALS['gFoireEmail']);
      
      $this->courrielQuestion($this->params['data']['question'], $courriel, $destination);
      $this->flash('Question envoyée','/faqs');

    }
  }

  function repondre()
  {

  }

  function creer()
  {
    $this->sessionCheck(SECURITY_LEVEL_ADMIN);
    
    $sections = $this->load_sections();

    if (empty($this->params['data']))
    {
      $this->render();
    }
    else
    {

      $e_section = $this->params['data']['e_section'];
      $e_section = $sections['sql'][$e_section];

      if ($this->params['data']['n_section'])
      {
        $this->params['data']['section'] = (intval(substr($e_section,0,1))+1).$this->params['data']['n_section'];
      }
      else
        $this->params['data']['section'] = $e_section;


      $this->params['data']['afficher'] = empty($this->params['data']['afficher']) ? '0' : '1';
      $this->models['faq']->save($this->params['data']);
      $this->flash('Question ajoutée','/faqs');

//      $this->print_pre($this->params['data']);
    }
  }

  function supprimer($id)
  {
    $this->sessionCheck(SECURITY_LEVEL_ADMIN);

    if ($this->models['faq']->del($id))
    {
      $this->flash('Question supprimée','/faqs');
    }

  }

  function modifier($id)
  {

    $this->sessionCheck(SECURITY_LEVEL_ADMIN);

    $sections = $this->load_sections();

    if (empty($this->params['data']))
    {

      $faq = $this->models['faq']->findAll("id=$id");
      $faq = $faq ? current($faq) : null;
      $faq['e_section'] = array_search(substr($faq['section'],1), $sections['html']);
      $this->params['data'] = $faq;

      $this->render();
    }
    else
    {

      $e_section = $this->params['data']['e_section'];
      $e_section = $sections['sql'][$e_section];

      if ($this->params['data']['n_section'])
      {
        $this->params['data']['section'] = (intval(substr($e_section,0,1))+1).$this->params['data']['n_section'];
      }
      else
        $this->params['data']['section'] = $e_section;
      
      $this->params['data']['afficher'] = empty($this->params['data']['afficher']) ? '0' : '1';
//      $this->print_pre($this->params['data'],true);

      $this->models['faq']->setId($id);
      $this->models['faq']->save($this->params['data']);
      $this->flash('Question modifiée','/faqs');

    }
  }

/*
  function view($id)
  {
    $this->models['post']->setId($id);
    $this->set('data', $this->models['post']->read());
  }

  function add_new()
  {
    if (empty($this->params['data']))
    {
            $this->render();
    }
    else
    {
      if ($this->models['post']->save($this->params['data']))
      {
        $this->flash('Your post has been saved.','/posts');
      }
      else
      {
        $this->set('data', $this->params['data']);
        $this->validateErrors($this->models['post']);
        $this->render();
      }
    }
  }

  function delete($id)
  {
    if ($this->models['post']->del($id))
    {
      $this->flash('The post with id: '.$id.' has been deleted.', '/posts');
    }
  }

  function edit($id=null)
  {
    if (empty($this->params['data']))
    {
      $this->models['post']->setId($id);
      $this->params['data']= $this->models['post']->read();
      $this->render();
    }
    else
    {
      $this->models['post']->set($this->params['data']);
      if ( $this->models['post']->save())
      {
        $this->flash('Your post has been updated.','/posts');
      }
      else
      {
        $this->set('data', $this->params['data']);
        $this->validateErrors($this->models['post']);
        $this->render();
      }
    }
  }
*/
}

?>
