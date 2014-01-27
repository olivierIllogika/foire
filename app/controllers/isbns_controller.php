<?php

class IsbnsController extends IsbnsHelper
{
  function index()
  {

  }

  function outil_insertion()
  {
    $this->sessionCheck(SECURITY_LEVEL_HIGHER_USER);

    if (empty($this->params['data']))
    {
      $this->render();
    }
    else
    {
      $isbn = IsbnWrapper::factory($this->params['data']['id']);
      
      if ($isbn->isMalformed())
      {
        $this->models['isbn']->validationErrors['id'] = 1;
        $this->render();
      }
      else
      {
          $info = $this->models['isbn']->getInfo($isbn, $force=true);
          $this->set('info', $info);
          $this->render();
      }//valid isbn
      
    }//empty data
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
