<?php

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

    if (strlen($isbn) < 5)
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
  function getInfo($isbn10)
  {

    $try_array = array(
                        'local_db_fetch',
                        'amazone_fr',
                        'amazone_ca',
                        'amazone_com',
                      );

    foreach($try_array as $try)
    {
      if ($try != '')
      {
//echo 'trying '.str_replace('_','.',$try).'<br />';

        $info['titre'] = $info['auteur'] = '';

        $ret = call_user_func(array(&$this, $try), &$info, $isbn10);
/*
        if (!$ret && class_exists('mailmsg'))
        {
          // mail report (http get error)
          $email = new mailmsg();

          $email->body = "ISBN wrapper '$try()' might be broken (with $isbn10)\n\n";
          $email->body .= "Check ".__FILE__." \n\n";
          $email->body .= "Caller was in {$_SERVER['SCRIPT_NAME']} \n\n";

          if (!$email->send())
            $this->log_miss("ISBN wrapper '$try()' might be broken (with $isbn10)\r\n");
        }
        */
        if (!$ret)
        {
          $body = "ISBN wrapper '$try()' might be broken (with $isbn10)\n\n";
          $body .= "Check ".__FILE__." \n\n";
          $body .= "Caller was in {$_SERVER['SCRIPT_NAME']} \n\n";

          @mail('lope@step.polymtl.ca', 'RAPPORT D\'ERREUR', $body);

          $this->log_miss("ISBN wrapper '$try()' might be broken (with $isbn10)\r\n");
        }

        $info['id'] = substr($isbn10, 0, 9);
/*
        $this->_titre = $info['titre'];
        $this->_auteur = $info['auteur'];
*/

        if (($info['titre'] == '' && $info['auteur'] == '')  )
        {
          // log to file isbn_miss.txt
          if ($try != 'local_db_fetch')
          {
            $this->log_miss("$try could not find $isbn10\r\n");
          }
        }
        else
        {
//echo "found @ $try<br />\n";
          // data found
          if ($try != 'local_db_fetch')
          {
            $this->local_db_cache($info);
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

  function local_db_cache($info)
  {
    return $this->insertWithId($info, false, $info['id']);
  }

  function local_db_fetch(&$info, $isbn, $link='')
  {
    $isbn = substr($isbn, 0, 9);

    $ret = $this->findAll("id=$isbn", array('titre','auteur','source','link'));

    if (!$ret) return true;

    $info = current($ret);

    return true;
  }

////////////////////////////////////////////////////////
////////  NET SEARCH BELOW  ////////////////////////////
////////////////////////////////////////////////////////

  function amazone_ca(&$info, $isbn, $link='')
  {
    $info['source'] = 'amazone.ca';
    $info['link'] = "http://www.amazon.ca/exec/obidos/ASIN/$isbn";
    return $this->amazone_fr($info,$info['link']);
  }

  function amazone_fr(&$info, $isbn, $link='')
  {
    // this hack allows for content layout wrapper (like amazone_ca)
    if ($link == '')
    {
      $info['link'] = "http://www.amazon.fr/exec/obidos/ASIN/$isbn";
      $info['source'] = 'amazone.fr';
    }
    else
      $info['link'] = $link;

    $contents = $this->getWebContent($info['link'], '<'.'body');

    $meta_description = $this->getMetaContent($contents,'description');
    $meta_d_array = explode(', ', $meta_description);

    $meta_keywords = $this->getMetaContent($contents,'keywords');
    $meta_k_array = explode(', ', $meta_keywords);


    for ($i = 0; $i < count($meta_d_array); $i++)
      if ($meta_d_array[$i] == $meta_k_array[$i])
        $pos = $i+1;
      else
        break;

    $info['titre'] = implode(', ', array_slice($meta_d_array,0,$pos));
    $info['auteur'] = implode(', ', array_slice($meta_d_array,$pos));

    return true;
  }

// http://www.amazon.com/exec/obidos/ASIN/2744014850

  function amazone_com(&$info, $isbn, $link='')
  {
    // this hack allows for content layout wrapper
    if ($link == '')
    {
      $info['link'] = "http://www.amazon.com/exec/obidos/ASIN/$isbn";
      $info['source'] = 'amazone.com';
    }
    else
      $info['link'] = $link;

    $contents = $this->getWebContent($info['link'], '<'.'body');

    $meta_description = $this->getMetaContent($contents,'description');
    $meta_keywords = $this->getMetaContent($contents,'keywords');

    if ($meta_description == '') return true; // not found but not broken
/*
<meta name="description" content="Amazon.com: Books: Open GL 1.2 : Guide officiel by Mason Woo,Jackie Neider,Tom Davis,Dave Shreiner" />
<meta name="keywords" content="Open GL 1.2 : Guide officiel,Mason Woo,Jackie Neider,Tom Davis,Dave Shreiner,2744014850" />
*/
    // found a parsing error in the wrapper
    if (!strstr($meta_description, 'Books:')) return false;

    $cleaned_desc = preg_replace('/[ ]*Amazon\.com:[ ]*Books:[ ]*/', '', $meta_description);

    $meta_d_array = explode(' by ', $cleaned_desc);

    $author_index = count($meta_d_array)-1;

    $info['titre'] = implode(' by ', array_slice($meta_d_array,0,$author_index));
    $info['auteur'] = $meta_d_array[$author_index];

    return true;
  }

  function getWebContent($link, $stop_on='')
  {
    $handle = @fopen($link, "r");

    if ($handle === false) return false;

    $contents = '';
    while (!feof($handle)) {
      $buffer = fread($handle, 1024);
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
