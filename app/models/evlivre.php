<?php

class Evlivre extends AppModel
{
  /*
*   430 livres
*   431 ajout du livre par champs %refgenie%-%reflivre%_
*   432 ajout du livre par ancienne foire %refgenie%-%reflivre%_
*   433 modif livre (%ancienrefgenie%-%ancienreflivre%) -> %refgenie%-%reflivre%_
*   434 supprimer livre %refgenie%-%reflivre%_
*
*   421 impression etiquettes
*   451 etiquette; non-inscrit
*   452 etiquette; non-confirme
*   453 etiquette; pas d'étiquettes

*   101 livre en consigne
*   102 livre déconsigne
*   103 impression recu
*   104 recu par courriel
*
*   151 echec cueillette $input
*   152 méthode de recu nulle
*   153 aucun étudiant défini pour le recu
*   154 aucun livres à imprimer sur le reçu de $codebar
*   155 méthode de recu inconnue

*   201 livre ajouté à la facture
*   202 livre retiré de la facture
*   203 mode de paiement
*   204 remboursement du livre
*
*   251 echec vente $input
*   252 modification illegale ou non consigné
*
*   304 récup: remise du livre au consignataire
*
*   601 facture
*   651 echec références
*   652 echec entrées
 */
 

  function logEvent($event_id, $livre_id, $codebar, $data)
  {
    $data = $this->db->prepare($data);

		$sql = "INSERT IGNORE INTO {$this->table} (id,evenement,codebar,data) VALUES ($livre_id,$event_id,$codebar,$data)";

		$this->db->query($sql);

  }
}

?>
