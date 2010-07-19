<?php

class FaqsHelper extends AppController
{
  function load_sections()
  {
    $sections = $this->models['faq']->findBySql("SELECT DISTINCT section FROM faqs");
    $html_sections = array_map(create_function('$v', 'return substr($v[0],1);'),$sections);
    $sql_sections = array_map(create_function('$v', 'return $v[0];'),$sections);
    $this->set('sections', $html_sections);

    return array('html' => $html_sections, 'sql' => $sql_sections);
  }
  

  function loadCache()
  {
    if (empty($_SESSION['foire']))
    {
      $_SESSION['foire'] = $this->models['foire']->find(null,null,'session DESC');
    }
    $this->set('foire', $_SESSION['foire']);
  }
    
  function courrielQuestion($question, $provenance, $destination)
  {

    $body = "Voici la question posée:\n\n".$question;

    sendSMTP($destination,'','','[Foire] Question', $body, false,$provenance);

  }
}

?>
