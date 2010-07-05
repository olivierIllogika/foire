<?php

class JourneesController extends JourneesHelper
{
  
  function index()
  {
    $this->pageTitle .= " - Horaire";

		// active event is the last one entered
    $foire = $this->models['foire']->find(null,'session','session DESC');
    $foire = $foire['session'];
    
    // get event days sorted by date
    $this->set('data', $this->models['journee']->findAll("session=$foire",null,'date'));

		// some string arrays
    $literals = array(1=>'hiver',2=>'été',3=>'automne');
    $month_fr = array('janvier','février','mars','avril','mai','juin',
                    'juillet','août','septembre','octobre','novembre','décembre');
    $this->set('month_fr', $month_fr);

		// extract number from full session number (3 from 20053) and get $literals[]
    $this->set('sess_literal', $literals[substr($foire,-1,1)]);

		// get event year
    $this->set('annee', substr($foire,0,4));

  }

	function modifier()
	{
	  // only managment user are allowed
    if (!$this->sessionCheck(SECURITY_LEVEL_MANAGMENT)) return;

	  $this->index();
	  
		$this->pageTitle .= " - Modification d'horaire";
	}
	
}

?>
