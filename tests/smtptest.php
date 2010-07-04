<?php
    require_once '../modules/smtp.php';

    $from = "Foire aux Livres <$gNoReplyEmail>";
    echo $from;
    $mailed = sendSMTP($gDevErrorEmail,'','',"smtp test", "smtp test", false, $from);
?>
