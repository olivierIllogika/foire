<?php

class FoiresController extends FoiresHelper
{
  function index()
  {

  }

	function stats($session = null)
	{
		/*
$year = date('Y');
$month = date('n');
if ($month > 9) $year++;
$session = ($month > 2 && $month < 10 ? 3 : 1);
return intval($year.$session)*1000+1;
		*/
		if (!isset($session))
		{
			$this->render();
			return;
		}
		
		$foireMonth = array( 1, 5, 9);
		
		$annee = substr($session, 0, 4);
		$foire = substr($session, -1, 1);
		
		//if ( $foire == 1 ) $annee--;
		
		$lowerDate = date('Y-m-d', mktime(0,0,0,$foireMonth[$foire - 1]-1, 1, $foire)).' 00:00:00';
		$upperDate = date('Y-m-d', mktime(0,0,0,$foireMonth[$foire - 1]+1, 1, $foire)).' 00:00:00';
		
    $this->set('lowerDate', $lowerDate);
    $this->set('upperDate', $upperDate);
		
/*
evetudiants 
402 confirmation_
351 récup: livres remboursés
*/
		
	}
	
/*
  function view($id)
  {
	if (!isset($session))
	{
		$this->render();
		return;
	}

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
//
//  function delete($id)
//  {
//    if ($this->models['post']->del($id))
//    {
//      $this->flash('The post with id: '.$id.' has been deleted.', '/posts');
//    }
//  }
//
//  function edit($id=null)
//  {
//    if (empty($this->params['data']))
//    {
//      $this->models['post']->setId($id);
//      $this->params['data']= $this->models['post']->read();
//      $this->render();
//    }
//    else
//    {
//      $this->models['post']->set($this->params['data']);
//      if ( $this->models['post']->save())
//      {
//        $this->flash('Your post has been updated.','/posts');
//      }
//      else
//      {
//        $this->set('data', $this->params['data']);
//        $this->validateErrors($this->models['post']);
//        $this->render();
//      }
//    }
//  }
*/
}

?>
