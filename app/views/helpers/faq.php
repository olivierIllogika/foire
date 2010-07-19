<?php

class FaqHelper
{
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
    
    function keywordMap($match)
    {
        $key = $match[1];
        $foire = $_SESSION['foire'];
        
        $replacements = array(
//                    "site" => "<a href=\"{$this->base}\">{$_SERVER['SERVER_NAME']}{$this->base}</a>",
        			"foireEmail" => "<a href=\"mailto:{$GLOBALS['gFoireEmail']}\">{$GLOBALS['gFoireEmail']}</a>",
                    "code4" => School::Get()->PolycopIdName(School::ShortName),
        			"taux_retard" => "<span class=\"livresPourcent\">{$foire['taux_retard']}%</span>",
                    "taux_comission" => "<span class=\"livresPourcent\">{$foire['taux_comission']}%</span>",
        );
        
        if (substr($key, 0, 1) == '[')
        {
            return $this->makeLink($key);
        }
        
        if (array_key_exists($key, $replacements))
        {
            return $replacements[$key];
        }        
        else
        {
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