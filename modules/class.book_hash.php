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
class BookHash
{
  function namePair2Hundreds($lastName, $firstName)
  {
    $lastLetter = strtoupper(substr($lastName, 0, 1));
    if ($lastLetter == 'L')
    {
      $lastLetter = (strtoupper(substr($lastName, 1, 1)) == 'A' ? 'L1' : 'L2');
    }
    $firstLetter = strtoupper(substr($firstName, 0, 1));

    $lastMap = array(
                    'A' => 1,
                    'B' => 1,
                    'C' => 1,
                    'D' => 1,
                    'E' => 2,
                    'F' => 2,
                    'G' => 2,
                    'H' => 3,
                    'I' => 3,
                    'J' => 3,
                    'K' => 3,
                    'L1' => 4,
                    'L2' => 5,
                    'M' => 6,
                    'N' => 7,
                    'O' => 7,
                    'P' => 7,
                    'Q' => 7,
                    'R' => 8,
                    'S' => 8,
                    'T' => 9,
                    'U' => 9,
                    'V' => 9,
                    'W' => 9,
                    'X' => 9,
                    'Y' => 9,
                    'Z' => 9);

    $firstMap = array(
                    'A' => 0,
                    'B' => 0,
                    'C' => 0,
                    'D' => 0,
                    'E' => 1,
                    'F' => 1,
                    'G' => 2,
                    'H' => 2,
                    'I' => 2,
                    'J' => 3,
                    'K' => 4,
                    'L' => 4,
                    'M' => 5,
                    'N' => 6,
                    'O' => 6,
                    'P' => 6,
                    'Q' => 7,
                    'R' => 7,
                    'S' => 7,
                    'T' => 8,
                    'U' => 8,
                    'V' => 8,
                    'W' => 9,
                    'X' => 9,
                    'Y' => 9,
                    'Z' => 9);

    if (empty($lastMap[$lastLetter]))
      $thousand = 1;
    else
      $thousand = $lastMap[$lastLetter];

    if (empty($firstMap[$firstLetter]))
      $hundred = 0;
    else
      $hundred = $firstMap[$firstLetter];

    return ($thousand*1000+$hundred*100);
  }

  function findSequence($genie,$suggID)
  {
    $sqlQuery = "SELECT l1.id AS id1,l3.id AS id2 ";
    $sqlQuery .= "FROM livres AS l1 ";
    $sqlQuery .= "LEFT JOIN livres AS l2 ON l1.id+1=l2.id ";
    $sqlQuery .= "LEFT JOIN livres AS l3 ON l1.id-1=l3.id ";
    $sqlQuery .= "WHERE  (ISNULL(l2.id) OR ISNULL(l3.id)) ";
    $sqlQuery .= "AND l1.id>=$suggID LIMIT 2";
/*l1.genie=$genie AND*/
/*
SELECT l1.id AS id1,l3.id AS id2 FROM livres AS l1 LEFT JOIN livres AS l2 ON l1.id+1=l2.id LEFT JOIN livres AS l3 ON l1.id-1=l3.id WHERE  (ISNULL(l2.id) OR ISNULL(l3.id)) AND l1.id>=$suggID LIMIT 2
*/
    $req = mysql_query($sqlQuery) or die('Erreur SQL !<br>findSequence()<br>'.mysql_error());

    $seq = array();
    while ($row = mysql_fetch_array($req,MYSQL_NUM))
    {
      $seq[] = $row;
    }
    return $seq;
  }

  function debut_seq($ids_1x2)
  {
    return $ids_1x2 == null || ($ids_1x2[0] && !$ids_1x2[1]);
  }

  function fin_seq($ids_1x2)
  {
          // pseudo-fin de séquence où 2 valeurs nulles représentes "rien après"
    return $ids_1x2 == null ||
          ($ids_1x2[0] && $ids_1x2[1]);
  }

  function nextSequenceID($suggID, $sequences_2x2)
  {
    if (!$sequences_2x2)
    // aucunes séquences, fin des id
    {
      return $suggID;
    }

    $s1 = $sequences_2x2[0];
    $s2 = count($sequences_2x2) > 1 ? $sequences_2x2[1] : null;
/*
echo '<pre>';
print_r($suggID);
echo '<br />';
print_r($s1);
print_r($s2);
echo '</pre>';
*/
    if (BookHash::debut_seq($s1) && $s1[0] > $suggID)
    // début de séquence, mais plus loin;
    // la suggestion est bonne
    {
      return $suggID;
    }
    
    if (BookHash::debut_seq($s1) && BookHash::debut_seq($s2))
    // 2 séquences disjointent (la première de 1 élément);
    // on continue la 1iere séquence
    {
      return $s1[0]+1;
    }

    if (BookHash::debut_seq($s1) && BookHash::fin_seq($s2))
    // dans une séquence, on la continue
    {
      return $s2[0]+1;
    }

    if (BookHash::fin_seq($s1))
    // fin de séquence, on la continue
    {
      return $s1[0]+1;
    }
    
    //erreur
    return $suggID;
  }

  function getNewID($genie, $session=20053, $lastName='-', $firstName='-')
  {
    $genie = intval($genie);
    
    $suggID = (($session*10+$genie)*10000) +
            BookHash::namePair2Hundreds($lastName, $firstName);
/*
echo $suggID.'<br />';
    $tests = array(1100,2100,2101,2102,6600,6602);
    foreach($tests as $test)
    {
      $test = (($session*10+$genie)*10000)+$test;
      $sequences = BookHash::findSequence(0,$test);
      echo 'Test: '.$test. ' => '.BookHash::nextSequenceID($test,$sequences).'<br />';
    }
die();
*/
    // identifications des séquences
    $sequences = BookHash::findSequence($genie,$suggID);

    return BookHash::nextSequenceID($suggID,$sequences);

  }
  
  
}
?>
