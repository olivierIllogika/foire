<?php
    require_once 'localconfig.php';

    //define("REAL_NAME","Foire aux Livres");
    define("DEFAULT_CHARSET", 'iso-8859-1');
    
    $host = $_SERVER["HTTP_HOST"];

    if (strpos($host, 'poly') !== false)
    {
        define("SMTP_ADDR", 'smtp.polymtl.ca');
        define("SMTP_PORT", 25);
        define("DOMAIN", 'polymtl.ca');
        define("ORG_EMAIL", 'Foire aux Livres <foire@step.polymtl.ca>');

        $gFoireName = 'Foire aux Livres';
        $gFoireEmail = 'foire@step.polymtl.ca';
        $gNoReplyEmail = 'Foire aux Livres <foire-noreply@step.polymtl.ca>';
        $gStudSufixEmail = 'polymtl.ca';
    }
    elseif (strpos($host, 'ets') !== false)
    {
        define("SMTP_ADDR", 'smtp.gmail.com');
        define("SMTP_PORT", 587);
        define("DOMAIN", 'gmail.com');
        define("ESTMP_USERNAME", 'sender@foire.li');
        define("ORG_EMAIL", 'Foire aux Livres <ets@foire.li>');

        $gFoireName = 'Foire aux Livres';
        $gFoireEmail = 'ets@foire.li';
        $gNoReplyEmail = 'Foire aux Livres <ets-noreply@foire.li>';
        $gStudSufixEmail = 'ens.etsmtl.ca';
    }
    else
    {
        define("SMTP_ADDR", 'smtp.gmail.com');
        define("SMTP_PORT", 587);
        define("DOMAIN", 'gmail.com');
        define("ESTMP_USERNAME", 'sender@foire.li');
        define("ORG_EMAIL", 'Foire aux Livres <ets@foire.li>');

        $gFoireName = 'Foire aux Livres';
        $gFoireEmail = 'ets@foire.li';
        $gNoReplyEmail = 'Foire aux Livres <ets-noreply@foire.li>';
        $gStudSufixEmail = substr($gDevErrorEmail, strpos($gDevErrorEmail, '@') + 1);
    }

?>
