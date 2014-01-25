<?php

class Evetudiant extends AppModel
{
/*  ?5? erreur
*
*   401 enregistrement_
*   402 confirmation_
*   403 d�sinscription_
*   404 conflit %type%_
*   405 demande de mot de passe_
*   406 login etudiant_
*   407 login redirect %securitylevel%_
*   -408    login admin %securitylevel%
*   409 logout_
*
*   410 modif info personnelles
*   411 %id -ancien changement id
*   412 mot de passe
*
*
*   440 factures
*   441 demande de facture
*   442 affichage d'une facture
*
*   461 afficher stats
*   462 courriel stats
*
*   471 kicked!
*
*   301 r�cup: affichage du dossier ..
*   302 r�cup: paiement de x$ � ..
*   303 r�cup: .. saut� [cmd passe]
*   304 r�cup: pseudo termin�
*   305 r�cup: ch�que #
*   306 r�cup: correction ch�que #
*   307 r�cup: r�impression - nouveau cheque #
*
*   351 r�cup: livres rembours�s
*/

  function logEvent($event_id, $codebar, $data)
  {
    $data = $this->db->prepare($data);
    
		$sql = "INSERT REPLACE INTO {$this->table} (id,evenement,data) VALUES ($codebar,$event_id,$data)";

		$this->db->query($sql);

  }
}

?>
