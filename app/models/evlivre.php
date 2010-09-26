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
*   453 etiquette; pas d'�tiquettes

*   101 livre en consigne
*   102 livre d�consigne
*   103 impression recu
*   104 recu par courriel
*
*   151 echec cueillette $input
*   152 m�thode de recu nulle
*   153 aucun �tudiant d�fini pour le recu
*   154 aucun livres � imprimer sur le re�u de $codebar
*   155 m�thode de recu inconnue

*   201 livre ajout� � la facture
*   202 livre retir� de la facture
*   203 mode de paiement
*   204 remboursement du livre
*
*   251 echec vente $input
*   252 modification illegale ou non consign�
*
*   304 r�cup: remise du livre au consignataire
*
*   601 facture
*   651 echec r�f�rences
*   652 echec entr�es
 */
 

  function logEvent($event_id, $livre_id, $codebar, $data)
  {
    $data = $this->db->prepare($data);

		$sql = "INSERT REPLACE INTO {$this->table} (id,evenement,codebar,data) VALUES ($livre_id,$event_id,$codebar,$data)";

		$this->db->query($sql);

  }
}

?>
