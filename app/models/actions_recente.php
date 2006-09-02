<?php

class Actions_recente extends AppModel
{
  function logEvent($commis_id, $commis_nom, $codebar)
  {
		$this->db->query("REPLACE INTO {$this->table} SET id=$commis_id, nom='$commis_nom', codebar=$codebar");
  }

}

?>
