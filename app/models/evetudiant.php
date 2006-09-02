<?php

class Evetudiant extends AppModel
{
/*  ?5? erreur
*
*   401 enregistrement_
*   402 confirmation_
*   403 désinscription_
*   404 conflit %type%_
*   405 demande de mot de passe_
*   406 login etudiant_
*   407 login redirect %securitylevel%_
*   -408    login admin %securitylevel%
*   409 logout_
*
*   410 modif info personnelles
*   411 nom(%ancien%)=%nouv%;pre(%ancien%)=%nouv%;tel(%ancien%)=%nouv%;mail(%ancien%)=%nouv%;_
*   412 nom(%ancien%)=%nouv%
*   413 prenom(%ancien%)=%nouv%
*   414 telelphone(%ancien%)=%nouv%
*   415 email(%ancien%)=%nouv%
*   416 mot de passe_
*       recu perdu
*       plus de courrier
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
*   301 récup: affichage du dossier ..
*   302 récup: paiement de x$ à ..
*   303 récup: .. sauté [cmd passe]
*   304 récup: pseudo terminé
*
*   351 récup: livres remboursés
*/

  function logEvent($event_id, $codebar, $data)
  {
    $data = $this->db->prepare($data);
    
		$sql = "INSERT IGNORE INTO {$this->table} (id,evenement,data) VALUES ($codebar,$event_id,$data)";

		$this->db->query($sql);

  }
}

?>
