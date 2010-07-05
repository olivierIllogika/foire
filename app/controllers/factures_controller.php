<?php

define('FPDF_FONTPATH',ROOT.'modules/fpdf152/font/');
require_once ROOT.'modules/fpdf_facture.inc.php';
//require_once ROOT.'modules/fpdf_label.inc.php';

class FacturesController extends FacturesHelper
{
  function index()
  {
    $this->pageTitle .= " - Impression de facture";

  }

  function facture()
  {

    if (empty($this->params['data']) || empty($this->params['data']['references']))
    {
      $this->models['facture']->validationErrors['references'] = 1;
      $this->render('index');
      return;
    }


    $references = str_replace(' ', '', $this->params['data']['references']);

    preg_match_all("/([\d]{4})-([\d]{1})/", $references, $matches);

    $total_ref = count($matches[0]);

    if ($total_ref >= 2) {

      $sql = "SELECT fl.parent_id ".
      "FROM facture_lignes AS fl JOIN livres AS l ON fl.livre_id = l.id JOIN factures AS f ON f.id=fl.parent_id, facture_lignes AS flb JOIN livres AS lb ON lb.id=flb.livre_id ".
      "WHERE RIGHT(l.id, 4) = {$matches[1][0]} AND RIGHT(lb.id, 4) = {$matches[1][1]} AND l.genie = {$matches[2][0]} AND lb.genie = {$matches[2][1]} AND fl.parent_id = flb.parent_id AND f.created > DATE_SUB(CURDATE(), INTERVAL 60 DAY)";

    }
    elseif ($total_ref == 1) {

      $sql = "SELECT fl.parent_id, count(flb.livre_id) AS c ".
      "FROM facture_lignes AS fl JOIN livres AS l ON fl.livre_id = l.id JOIN factures AS f ON f.id=fl.parent_id, facture_lignes AS flb ".
      "WHERE RIGHT(l.id, 4) = {$matches[1][0]} AND l.genie = {$matches[2][0]} AND flb.parent_id=fl.parent_id  AND f.created > DATE_SUB(CURDATE(), INTERVAL 30 DAY) ".
      "GROUP BY parent_id HAVING c = 1";

    }
    else
    {
      $this->models['evlivre']->logEvent(652,0,0,"mauvaise entr� facture '$references'");

      $this->models['facture']->validationErrors['references'] = 2;
      $this->render('index');
      return;
    }
// 6001-6;2008-6
    $ret = $this->models['facture']->findBySql($sql);

    if (!$ret)
    {
      $this->models['evlivre']->logEvent(651,0,0,"mauvaises r�f�rences facture '$references'");

      $this->models['facture']->validationErrors['references'] = 3;
      $this->render('index');
      return;
    }

    $this->models['evlivre']->logEvent(601,$ret[0]['parent_id'],0,"facture '$references'");

    $this->set('lignes', $this->models['facture_ligne']->findBySql("SELECT f.created, total,comptant, remis, carte, l.titre, l.prix FROM factures AS f JOIN facture_lignes AS fl ON f.id=fl.parent_id JOIN livres AS l ON fl.livre_id = l.id WHERE f.id={$ret[0]['parent_id']}"));

    $this->set('filename', "facture.pdf");
    $this->set('downloadfile', "facture.pdf");
    $this->set('redirect', "{$this->base}/factures");
    $this->render('facture_pdf','ask_download');
//    $this->render('f_test');
  }

  function f_test()
  {}

}

?>
