<?php

require_once ROOT.'modules/smtp.php';

class Isbnstat extends AppModel
{
  function update_stats($minCount, $force=false)
  {

    $row = $force ? null :
            $this->find(null, array('created', 'current_timestamp()-created as last_update'));

    if (!$row || $row['last_update'] > 500) // 100 = 1min; 1000=10min; 10000=1h
    {
      $this->refresh_stats($minCount);
    }
  }
  
  function look_for_new($minCount)
  {
//    $minCount -= 1;

    $sql = "SELECT isbn FROM livres AS l LEFT JOIN isbnstats AS s ON s.id=l.isbn WHERE  ISNULL(max) AND isbn!=0 GROUP BY isbn HAVING COUNT(*)>=$minCount";

		if($isbns = $this->findBySql($sql))
		{
      // aviser les nouveaux consignataires
      foreach($isbns as $isbn)
      {

        $courriels = $this->findBySql("SELECT distinct(courriel),titre FROM etudiants AS e JOIN livres AS l ON e.id=l.codebar WHERE isbn={$isbn[0]} ORDER BY l.created LIMIT ".($minCount));

        if ($courriels)
        {
          $liste_courriel = array_map(create_function('$v', 'return $v[0];'), $courriels);
  //echo $isbn[0]. ' : '..'<br />';
  
          $data = addslashes("courriel stats ". preg_replace('/@[^,]+,/',',',implode(',',$liste_courriel)));
          $codebar = isset($_SESSION['etudiant']['id']) ? $_SESSION['etudiant']['id'] : 0;

      		$sql = "INSERT IGNORE INTO evetudiants (id,evenement,data) VALUES ($codebar,462,'$data')";

      		$this->db->query($sql);

//          $liste_courriel = array();

          $this->courrielAvisStats($liste_courriel,$courriels[0]['titre']);
        }
      }
    }
  }

/**
* refresh_stats will create/replace records in table isbnstats. It will
*	cache stats data for books where the isbn appears in livres for $minCount or more
*
*/  
	function refresh_stats($minCount)
  {

		$sql = 
      "REPLACE isbnstats (id, count, min, max, avg, `range`, stddev) ".
      "SELECT isbn, COUNT(*) as c,  MIN(prix) AS min, MAX(prix) AS max, ".
      "AVG(prix) AS avg, MAX(prix)-MIN(prix) AS `range`, STDDEV(prix) AS stddev ".
      "FROM livres WHERE isbn!=0 GROUP BY isbn HAVING c >= $minCount ORDER BY isbn";
 
//    if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1')
//    {
//      $this->look_for_new($minCount);
//    }
//    else
//      echo '<pre>'.print_r($_SERVER,true).'</pre>';


		if($this->db->query($sql))
    {
      return true;
    }
    
    return false;

  }


  function courrielAvisStats($listeCourriel, $titre)
  {
    $listeCourriel[] = 'lope@step.polymtl.ca';

    $body =
    "Vous recevez ce courriel parce qu'un de vos livres inscrit � la Foire poss�de maintenant des statistiques de prix.\n\n".

    "Le livre intitul� \"$titre\" se trouve maintenant en nombre suffisant pour en afficher une moyenne de prix significative.  Les statistiques sont fournis � titre indicatif seulement; m�fiez-vous en!  Quelques informations sont disponibles � ce sujet dans la section <<FAQ>>.\n\n".
    
    "Les statistiques des livres sont une fonctionnalit� nouvellement ajout�e.  Les avis par courriel pourraient �tre consid�r�s abusif.  Svp, soyez indulgent jusqu'� ce que le syst�me soit au point.  Soyez assur� que tous les efforts sont mis en oeuvres pour ne pas surcharger votre bo�te de courrier.\n\n".

    "Merci, et bonne Foire !";

    sendSMTP('Foire aux Livres <foire-noreply@step.polymtl.ca>','',implode(', ',$listeCourriel),'[Foire] Statistiques disponibles', $body, false,'Foire aux Livres <foire-noreply@step.polymtl.ca>');

  }
}

?>
