<?php 

abstract class School
{
    
    const Poly = 0;
    const ETS = 1;
    const Dev = 2;
    
    const ShortName = 0;
    const MediumName = 1;
    const LongName = 2;
    
    public static $instance = null;
    public static function Get() { return School::$instance; }
    
    abstract public function Name($size, $article=false);
    abstract public function GetBarCodeImg();
    abstract public function CoopImg(&$alt, &$link);
    abstract public function TagLine($html, $kiosk);

    abstract public function BarCodePrefix();
    abstract public function BarCodeDataSize();
    abstract public function BarCodeFieldSize();
    abstract public function BarCodeTitle();
    abstract public function BarCodeError();
    abstract public function BarCodeValidate();
}

class SchoolPoly extends School
{
    public function TagLine($html, $kiosk)
    {
        $AEP = (empty($kiosk) || !$kiosk ? $html->linkOut("AEP", "http://www.aep.polymtl.ca", array('title'=>'Association des Étudiants de Polytechnique')) : 'AEP' );
        return "Un service de l'$AEP, votre association étudiante";
    }
    
    public function Name($size, $article=false)
    {
        $articles = array('','',"l'");
        $names = array('Poly', 'Polytechnique', 'École Polytechnique de Montréal');
        return ($article ? $articles[$size] : '').$names[$size];
    }
    public function GetBarCodeImg()
    {
        return 'code-barre.gif';        
    }
    public function CoopImg(&$alt, &$link)
    {
        $link = "http://www.coopoly.ca";
        $alt = "Coopoly, votre librairie de génie";
        return 'coopoly_small.gif';        
    }

   
    public function BarCodePrefix() { return '2 9334 '; }
    public function BarCodeDataSize() { return 14; }
    public function BarCodeFieldSize() { return 17; }
    public function BarCodeTitle()
    {
        return "Code barre de la carte de ".$this->Name(School::ShortName, true);
    }
    public function BarCodeError()
    {
        
    }
    public function BarCodeValidate()
    {
    }
}

class SchoolETS extends School
{
    public function TagLine($html, $kiosk)
    {
        $TribuTerre = (empty($kiosk) || !$kiosk ? $html->linkOut("TribuTerre", "http://tributerre.aeets.com", array('title'=>'TribuTerre')) : 'TribuTerre' );
        $Coop = (empty($kiosk) || !$kiosk ? $html->linkOut("Coop ETS", "http://www.coopets.ca", array('title'=>'Librairie Coop ETS')) : 'Coop ETS' );
        $AEP = (empty($kiosk) || !$kiosk ? $html->linkOut("Poly", "http://www.aep.polymtl.ca/servicesEnLigne.php", array('title'=>'Association des Étudiants de Polytechnique')) : 'Poly' );
        return "Une collaboration de $TribuTerre, $AEP et la $Coop";
    }
    
    public function Name($size, $article=false)
    {
        $articles = array("l'","l'","l'");
        $names = array("ETS", "ETS", "École de technologie supérieure");
        return ($article ? $articles[$size] : '').$names[$size];
    }
    public function GetBarCodeImg()
    {
        return 'carte-ets.jpg';        
    }    
    public function CoopImg(&$alt, &$link)
    {
        $link = "http://www.coopets.ca";
        $alt = "Librairie Coop ÉTS, La référence en génie";
        return 'coop_ets.gif';        
    }

    public function BarCodePrefix() { return ''; }
    public function BarCodeDataSize() { return 8; }
    public function BarCodeFieldSize() { return 10; }
    public function BarCodeTitle()
    {
        return "Code barre de la carte de ".$this->Name(School::ShortName, true);
    }
    public function BarCodeError()
    {
        
    }
    public function BarCodeValidate()
    {
    }
    
}

?>
