<?php

class FaqHelper
{
    
    function __construct() {
        $foire = $_SESSION['foire'];
        
        $this->replacements = array(
//                    "site" => "<a href=\"{$this->base}\">{$_SERVER['SERVER_NAME']}{$this->base}</a>",
        			"foireEmail" => $this->makeMailLink($GLOBALS['gFoireEmail']),
                    "code4" => School::Get()->PolycopIdName(School::ShortName),
        			"taux_retard" => "<span class=\"livresPourcent\">{$foire['taux_retard']}%</span>",
                    "taux_comission" => "<span class=\"livresPourcent\">{$foire['taux_comission']}%</span>",
        );
    }
    
    function makeLink($keyText)
    {
        $sepPos = strpos($keyText, ' ');
        $href = substr($keyText, 1, $sepPos - 1);
        $text = trim(substr($keyText, $sepPos), ']');
        
        if (substr($keyText, 1, 4) == 'http')
        {
            // absolute
            $link = "<a href=\"$href\">$text</a>";
        }
        else
        {
            // relative
            $link = "<a href=\"{$this->base}$href\">$text</a>";
        }
        return $link;
    }
    
    function makeMailLink($mail)
    {
		return "<a href=\"mailto:$mail\">$mail</a>";                
    }
    
    function keywordMap($match)
    {
        $key = $match[1];
        
        
        if (substr($key, 0, 1) == '[')
        {
            // wiki style link
            return $this->makeLink($key);
        }
        
        if (strpos($key, '@'))
        {
            // email
            return $this->makeMailLink($key);
        }
        
        if (array_key_exists($key, $this->replacements))
        {
            // keyword
            return $this->replacements[$key];
        }        
        else
        {
            // unhandled key
            return "**$key**";
        }
    }
    
    function processKeywords($inputText)
    {
        $replacedText = preg_replace_callback('/\$\(([^)]*)\)/', array( &$this, 'keywordMap'), $inputText);
        return $replacedText;
    }
    
}    
?>