<?php

/*
* 
*  fichier de fonction permettant de déterminer
*  le numéro d'ordre d'un livre en fonction du nom et prénom
*  d'un usager.
* 
* la distribution devrait être ajustée en traçant la 'cloche'
*  des nombres attribués par tranche de centaine
* 
* 
*/
class Num2Str
{
  protected $mNumeral = 0;
  protected $mLiteral = '';
  
  function __construct($numeral = 0) 
  {
    $this->mNumeral = $numeral;
    $this->mLiteral = $this->literal($numeral);
  }
  
  function numeral()
  {
    return $this->mNumeral;
  }
  
  function literal($numeral = 0)
  {
    $prefix = '';

    if ($numeral)
    {
      $this->mLiteral = '';
      
      
      $arr_ones = array("", "one", "two", "three", "four", "five", "six",
                        "seven", "eight", "nine", "ten", "eleven", "twelve",
                        "thirteen", "fourteen", "fifteen", "sixteen", 
                        "seventeen", "eightteen", "nineteen");
                        
        
      $arr_tens = array("", "", "twenty", "thirty", "fourty", "fifty",
                        "sixty", "seventy", "eigthy", "ninety");            
                
      $ones = $numeral % 10;
      $tens = $numeral % 100 - $ones;
      $huns = floor($numeral / 100);
      
      if ($huns > 99)
      {
        $this->mNumeral = 0;
        return '';
      }
      
      if ($huns){
          $this->mLiteral = $this->literal($huns) . ' hundred';
      }

      if ($tens > 10)
      {
          $this->mLiteral .= ($this->mLiteral ? ' ' : '') . $arr_tens[$tens / 10];
      }
      elseif ($tens)
      {
          $this->mLiteral .= ($this->mLiteral ? ' ' : '') . $arr_ones[$tens + $ones];
          $ones = '';
      }

      if ($ones)
      {
          $this->mLiteral .= ($this->mLiteral ? ($tens >= 20 && $tens <= 90 ? '-':' ') : '') . $arr_ones[$ones];
      }

      $this->mNumeral = intval($numeral);
     
    }
    
    return $this->mLiteral;
  }

  
}
?>
