<?php


class IsbnWrapper
{
    var $input;
    var $inputLen;
    
    var $isbn13;
    var $src10;
    var $check10;
    var $src13;
    var $check13;
    var $valid = false;
    var $malformed = false;
    
    static function factory($freeForm)
    {
        $class = School::Get()->IsbnClass();
        return new $class($freeForm);        
    }
    
    function __construct($freeForm)
    {
        $freeForm = preg_replace('/[^0-9X]/', '', strtoupper($freeForm));
        $this->input = $freeForm;
        $len = strlen($freeForm);
        $this->inputLen = $len;
        
        $prefix13 = '978';
        if ($len == 13 || $len == 12)
        { 
            if ($len == 13)
            {
                $this->src13 = $freeForm;
                $prefix13 = substr($freeForm, 0, 3);
                $this->computeCheck13($this->src13);
                $this->valid = substr($this->src13, -1) == $this->check13;
            }
            else
            {
                $this->valid = true;
                
                $this->computeCheck13($freeFrom);
                $this->src13 = $freeForm.$this->check13;
                $prefix13 = substr($freeForm, 0, 3);
            }
            
            $this->isbn13 = substr($this->src13, 0, 12);
            $isbn9 = substr($this->src13, 3, 9);
            $this->computeCheck10($isbn9);
            $this->isbn10 = $this->src10 = $isbn9.$this->check10;            
        }
        elseif ($len == 10)
        {
            $this->src10 = $freeForm;
            $this->computeCheck10($this->src10);
            $this->valid = substr($this->src10, -1) == $this->check10;
            
            $this->isbn13 = $prefix13.substr($this->src10, 0, 9);
            $this->computeCheck13($this->isbn13);
            $this->src13 = $this->isbn13.$this->check13;
        }
        elseif ($len == 9)
        {
            $this->valid = true;

            $this->computeCheck10($freeForm);
            $this->src10 = $freeForm.$this->check10;
            
            $this->isbn13 = $prefix13.substr($this->src10, 0, 9);
            $this->computeCheck13($this->isbn13);
            $this->src13 = $this->isbn13.$this->check13;
        }
        else 
        {
            $this->malformed = true;
        }

        if ($prefix13 != '978' && $prefix13 != '979' )
        {
            $this->malformed = true;
        }
    }
    
    function computeCheck10($isbn)
    {
        $n = substr($isbn, 0, 9);
        
        $checksum = 0;
    
        for ($i = 0; $i < 9; $i++)
          $checksum += ($i+1)*$n[$i];
    
        $checksum %= 11;
    
        $this->check10 = $checksum==10 ? 'X' : $checksum;
        return $this->check10; 
    } 
    
    function computeCheck13($isbn)
    {
        $n = substr($isbn, 0, 12);

        $checksum = 0;
    
        $f = 1;
        for ($i = 0; $i < 12; $i++)
        {
          $checksum += $f*$n[$i];
          $f ^= 2; 
        }
    
        $checksum = 10 - ($checksum % 10);
        $this->check13 = $checksum==10 ? '0' : $checksum;;
        return $this->check13;
    } 
    
    function isValid()
    {
        return $this->valid;
    }
    function isMalformed()
    {
        return $this->malformed;
    }
    
    function getIsbn10($checksum=true,$validVersion=true)
    {
        $data = $validVersion ? substr($this->isbn13, 3, 9) : substr($this->src10, 0, 9);
        $check = $validVersion ? $this->check10 : substr($this->src10, -1);
        return $data.($checksum ? $check : ''); 
    }
    
    function getIsbn13($checksum=true,$validVersion=true)
    {
        $data = substr($validVersion ? $this->isbn13 : $this->src13, 0, 12);
        $check = $validVersion ? $this->check13 : substr($this->src13, -1);
        return $data.($checksum ? $check : ''); 
    }

    function getIsbn10Checkdigit()
    {
        return $this->check10;
    }

    function getIsbn13Checkdigit()
    {
        return $this->check13;
    }
}

class IsbnEts extends IsbnWrapper
{
    function __construct($freeForm)
    {
        parent::__construct($freeForm);
        if ($this->inputLen == 6)
        {
            parent::__construct('6160000'.$this->input);
            // overwrite normal isbn analysis 
            $this->malformed = false;
            $this->isbn13 = $this->src13;
            $this->check13 = substr($this->input, -1);
        }
    }
}

class Isbn extends AppModel
{
  /*
  var $validate = array(
          'question'=>VALID_NOT_EMPTY);
          */
          
  // any code bar input should be striped of their checksum digit
  // before any function call
  
  function any_with_checkdigit2gtin($unknown)
  {
    if (empty($unknown)) return false;
    
    $len = strlen($unknown);
    
    return $this->any_nocheckdigit2gtin( ($len > 9 ? substr($unknown, 0, $len-1) : $unknown) );
  }

  function any_nocheckdigit2gtin($unknown)
  {
    if (empty($unknown)) return false;

    $unknown = preg_replace('/[^0-9]/', '', $unknown);
    

    $len = strlen($unknown);
    
    if ($len > 13)
    { // the max length of an unknow is 13 for a EAN/UCC-14 without chekcsum digit
      trigger_error("EAN/UCC-14 an others must not include checkusm digit in function calls (max length whould be 13 digits)", E_USER_WARNING);
      return false;
    }
    
    if (!is_numeric($unknown))
    {
      trigger_error("EAN/UCC & ISBN without checksum digit should contain only numbers", E_USER_WARNING);
      return false;
    }

    // (withou checkdigit) :
    // EAN/UCC-14     EAN/UCC-13    UCC-12        EAN/UCC-8    ISBN
    if ($len != 13 && $len != 12 && $len != 11 && $len != 7 && $len != 9)
    {
      trigger_error("Input format is of unexpected length", E_USER_WARNING);
      return false;
    }

    return intval(sprintf("%010s", $unknown));
  }

  function gtin2ean_wcheck($gtin, $ean_len=13)
  {
    if (empty($gtin)) return false;

    $ean = substr($gtin, -$ean_len+1);
    return $ean.$this->gtin_checdigit($ean);
  }

  function gtin2isbn($gtin, $with_check=false)
  {
    $cleaned_gtin = ltrim($gtin, '0');
    
    if (empty($cleaned_gtin)) return false;
/*
echo '<pre>';
print_r($cleaned_gtin);
echo '</pre>';
*/
    $first3digits = substr($cleaned_gtin, 0, 3);
    if ($first3digits != '978' && $first3digits != '979' && strlen($cleaned_gtin) > 9 )
    { // should begin with 978 or 979 and be 12 digits in length (without checkdigit)
      trigger_error("Input GTIN is not a valid ISBN container", E_USER_WARNING);
      return false;
    }
    
    $n = $isbn = substr($gtin, -9);
    
    if ($with_check)
    {
      $checksum = 0;

      for ($i = 0; $i < 9; $i++)
        $checksum += ($i+1)*$n[$i];

      $checksum %= 11;

      $checksum = $checksum==10 ? 'X' : $checksum;

      $isbn .= $checksum;
    }
    
    return $isbn;
  }

  function isbn_checkdigit($any_isbn)
  {
    $n = substr($any_isbn, 0,9);

    $checksum = 0;

    for ($i = 0; $i < 9; $i++)
      $checksum += ($i+1)*$n[$i];

    $checksum %= 11;

    $checksum = $checksum==10 ? 'X' : $checksum;

    return $checksum;
  }

  function is_isbn($unknown_with_check)
  {
    // function checks if known isbn format and if valid (checksum)
    
    
    $unknown = preg_replace('/[^0-9X]/', '', strtoupper($unknown_with_check));
    
    if (empty($unknown)) return false;

    if (!preg_match('/[0-9]{9}[0-9X]/', $unknown)) return false;

    $check = $this->isbn_checkdigit($unknown);
    
    if (substr($unknown,-1) == $check)
    {
      return $unknown;
    }
    
    return substr($unknown,0,9);
  }

  function isbn2formated($isbn, $if_blank='')
  {
    $isbn = preg_replace('/[^0-9]/', '', $isbn);
//echo $isbn.'<br />';
    if (empty($isbn) || $isbn === '0')
    {
      return $if_blank;
    }

    if (strlen($isbn) <= 6)
    {
      return $isbn; // code4
    }

    // first 9 characters
    // left pad with 0
    $isbn = substr('00000'.substr($isbn,0,9),-9);
//echo $isbn.'<br />';

    if (strlen($isbn) == 9)
    {
      $isbn .= $this->isbn_checkdigit($isbn);
    }

    return preg_replace('/(.)(.{3})(.{5})(.)/', '\1-\2-\3-\4', $isbn);
  }

  function gtin2formated_isbn($gtin)
  {
    if (empty($gtin)) return false;

    return preg_replace('/(.)(.{4})(.{4})(.)/', '\1-\2-\3-\4',
                        $this->gtin2isbn($gtin, true));
  }

  function gtin_checkdigit($gtin)
  {
    if (empty($gtin)) return false;

    $checksum = 0;
    
    $gtin_len = strlen($gtin);
    
    for ($i = $gtin_len; $i >= 0; $i--)
    {
      if ( $i % 2 ) // impair
        $checksum += $n[$i] * 3;
      else
        $checksum += $n[$i];
    }

    return (10 - ($checkdigit % 10));

  }
////////////////////////////////////////////
////////////////////////////////////////////
  function getInfo($isbnWrap, $force=false)
  {

    // test at http://localhost/foire-work/isbns/outil_insertion
    $try_array = array(
                        $force ? '' : 'local_db_fetch',
                        'coop_ets',
                        'coop_poly',
                        'amazon_fr',
                        'amazon_ca',
                        'amazon_com',
                      );

    foreach($try_array as $try)
    {
      if ($try != '')
      {

        $info['titre'] = $info['auteur'] = '';

        $ret = call_user_func_array(array(&$this, $try), array(&$info, $isbnWrap));
        
        $info['titre'] =  html_entity_decode($info['titre'], ENT_QUOTES, 'UTF-8');
        $info['auteur'] =  html_entity_decode($info['auteur'], ENT_QUOTES, 'UTF-8');

        if (!$ret)
        {
          $body = "ISBN wrapper '$try()' might be broken (with {$isbnWrap->getIsbn13()})\n\n";
          $body .= "Check ".__FILE__." \n\n";
          $body .= "Caller was in {$_SERVER['SCRIPT_NAME']} # {$_SERVER['REQUEST_URI']} \n\n";

          //sendSMTP($GLOBALS['gDevErrorEmail'],'','',"RAPPORT D'ERREUR - ISBN Scraper", $body, false,$GLOBALS['gNoReplyEmail']);

          //$this->log_miss("ISBN wrapper '$try()' might be broken (with $isbn10)\r\n");
        }

        $info['id'] = $isbnWrap->getIsbn13();

        if (($info['titre'] == '' && $info['auteur'] == '')  )
        {/*
          // log to file isbn_miss.txt
          if ($try != 'local_db_fetch')
          {
            $this->log_miss("$try could not find $isbn10\r\n");
          }*/
        }
        else
        {
          // data found
          if ($try != 'local_db_fetch')
          {
            $this->local_db_cache($info, $isbnWrap, $force);
          }

          break;

        } //if titre,auteur
      }//if $try
    }//foreach

    return ($info['titre'] ? $info : null);
  }

  function log_miss($msg)
  {

    if (!isset($to_root)) $to_root = '../';

    $miss_file = $to_root.'isbn_miss.txt';
    $handle = fopen($miss_file, 'ab');
    if ($handle)
    {
      fwrite($handle, $msg);
      fclose($handle);
    }
  }

////////////////////////////////////////////////////////
////////  DB SEARCH BELOW  ////////////////////////////
////////////////////////////////////////////////////////

  function local_db_cache($info, $isbnWrap, $wasForced=false)
  {
	$canInsert = true;
	if ($wasForced)
	{
		$result = $this->findAll("id={$isbnWrap->getIsbn10(false)} or id={$isbnWrap->getIsbn13()}", array('titre','auteur','source','link'));
		$canInsert = empty($result);
	}
	if ($canInsert)
	{
		$this->insertWithId($info, false, $isbnWrap->getIsbn13());
	}
  }

  function local_db_fetch(&$info, $isbnWrap, $link='')
  {
    $ret = $this->findAll("id={$isbnWrap->getIsbn13()}", array('titre','auteur','source','link'));

    if (!$ret) return true;

    $info = current($ret);

    return true;
  }

////////////////////////////////////////////////////////
////////  NET SEARCH BELOW  ////////////////////////////
////////////////////////////////////////////////////////

  function amazon_ca(&$info, $isbnWrap, $link='')
  {
    $info['source'] = 'amazon.ca';
    $info['link'] = "http://www.amazon.ca/exec/obidos/ASIN/{$isbnWrap->getIsbn10()}";
    return $this->amazon_1($info, $isbnWrap, $info['link']);
  }

  function amazon_com(&$info, $isbnWrap, $link='')
  {
    $info['source'] = 'amazon.com';
    $info['link'] = "http://www.amazon.com/exec/obidos/ASIN/{$isbnWrap->getIsbn10()}";
    return $this->amazon_2($info, $isbnWrap, $info['link']);
  }

  function amazon_fr(&$info, $isbnWrap, $link='')
  {
    $info['source'] = 'amazon.fr';
    $info['link'] = "http://www.amazon.fr/exec/obidos/ASIN/{$isbnWrap->getIsbn10()}";
    return $this->amazon_1($info, $isbnWrap, $info['link']);
  }

  function amazon_getDescription($link)
  {
    $contents = $this->getWebContent($link, '<'.'body');

    $meta_description = $this->getMetaContent($contents,'description');

    return $meta_description;
  }
    
  function amazon_1(&$info, $isbnWrap, $link='')
  {
    $desc = $this->amazon_getDescription($link);
    
    if ($desc)
    {
        $src = $info['source'];
        $divider = ": $src: ";
    
        $pos = strpos(strtolower($desc), $divider);
        
        $info['titre'] = substr($desc, 0, $pos);
        $authStart = $pos + strlen($divider);
        $info['auteur'] = substr($desc, $authStart, strrpos($desc, ":") - $authStart);
    
        if ($info['titre'] == '' || $info['auteur'] == '')
        {
          $info['desc'] = $desc;
          return false; // return false if possibly broken
        }
    }
    return true;
  }

  function amazon_2(&$info, $isbnWrap, $link='')
  {
    $desc = $this->amazon_getDescription($link);

    if ($desc)
    {
        $isbn9 = substr($isbnWrap->getIsbn10(), 0, 9);
        $pattern = "/ \(\d+$isbn9\d\): /";
        $parts = preg_split($pattern, $desc);
        
        $info['titre'] = substr($parts[0], strpos($desc, ":") + 1);
        if (count($parts) > 1)
        {
            $info['auteur'] = substr($parts[1], 0, strrpos($parts[1], ":"));;
        }
    
        if ($info['titre'] == '' || $info['auteur'] == '')
        {
          $info['desc'] = $desc;
          return false; // return false if possibly broken
        }
    }
    return true;
  }
    
  function coop_poly(&$info, $isbnWrap, $link='')
  {
    $info['source'] = 'coopets.ca';
    $info['link'] = "http://www.coopoly.ca/recherche.php?isbn={$isbnWrap->getIsbn13()}&action=chercher";

    $contents = $this->getWebContent($info['link'], '</'.'body');

    preg_match('#"padding-bottom: 10px"><strong>\s*([^<]+)\s*</strong>\s*\<br />\s*([^<]+)</div>#si', $contents, $match );

    if (count($match) == 3)
    {
        $info['titre'] = iconv('iso-8859-1', 'utf-8', $match[1]);
        $info['auteur'] = iconv('iso-8859-1', 'utf-8', $match[2]);
        return true;
    }
    return false;    
  }
  
  function coop_ets(&$info, $isbnWrap, $link='')
  {
    $info['source'] = 'coopets.ca';
    $info['link'] = "http://www.coopets.ca/webconcepteur/web/Coopsco/fr/ets/service.prt?advancedSearch=true&svcid=CO_CATALOG18&page=productsList.jsp&producttype=librairie&isbn13={$isbnWrap->getIsbn13()}";

    $contents = $this->getWebContent($info['link'], '</'.'body');
    
    preg_match('#>([^<]+)</a>\s*<dl>\s*<dt>Auteur :</dt>\s*<dd>([^<]+)</dd>#si', $contents, $match );

    if (count($match) == 3)
    {
        $info['titre'] = $match[1];
        $info['auteur'] = $match[2];
        return true;
    }
    return false;    
  }
    

  function getWebContent($link, $stop_on='')
  {
    $handle = @fopen($link, "r");

    if ($handle === false) return false;

    $contents = '';
    while (!feof($handle)) {
      $buffer = fread($handle, 2048);
      $contents .= $buffer;

      if ($stop_on != '' && strstr($buffer, $stop_on)) break;
    }
    fclose($handle);

    return $contents;
  }

  function getMetaContent($file_content, $meta_name='description')
  {
    if (!preg_match('/meta.+name="'.$meta_name.'".+content="([^\"]+)/i', $file_content, $match )) return '';
    return $match[1];
  }

  function getPregMatch($file_content, $pattern='/?/i')
  {
    if ($pattern == '/?/i')
      trigger_error('$pattern not set !',E_USER_ERROR);

    if (!preg_match($pattern, $file_content, $match )) return '';
    return $match[1];
  }
}

?>
