<?php
/*
 base code from Sean Coates
*/


class session
{
  var $id = null;
//  static $db = null;

  var $tablesize = null;
  var $maxsize = null;
  var $indexsize = null;
  var $avgrowsize = null;
  var $rows = null;
  var $datachunks = null;
  var $maxrows = null;
  
  function sess_open($save_path, $session_name)
  {
    if (session::$db == null)
      session::$db = database::instance();

    if (!session::table_exists()))
      session::create_table();

    return true;
  }

  function sess_close()
  { return true; }

  function create_table($data_chunks='')
  {
    // create the table
    $mem_table =
    "create table sessions (".
      "id bigint PRIMARY KEY,".
      "ip char(8) default '' NOT NULL,".
      "ctime timestamp(14) default NULL,".
      "atime timestamp(14) default NULL,".
      "valid ENUM('yes','no') default 'yes' NOT NULL,".
      "skey varchar(255) NOT NULL,".
      "data1 varchar(255) default '' NOT NULL,".
      "data2 varchar(255) default '' NOT NULL,".
      "INDEX(valid,skey),".
      "INDEX(valid,atime)".
    ") TYPE=HEAP  MAX_ROWS=500";

    $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);
    session::$db->request($mem_table,$context,false);

  }

  function expand_row($total_chunks=0)
  {
    $chunks = session::$datachunks;
    
    if ($total_chunks==0)
      $total_chunks = $chunks+1;
    
//      "data1 varchar(255) default '' NOT NULL,".
    $query = '';
    for ($i = $chunks+1; $i <= $total_chunks; $i++)
    {
      $query .= ($query==''?'':',')." ADD data$i varchar(255) default '' NOT NULL AFTER data".($i-1);
    }
    
    $query = "ALTER TABLE sessions ".$query;
    
    $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);
    session::$db->request($mem_table,$context);

    session::$datachunks = $total_chunks;
  }

  function expand_table($max_rows=0)
  {
    if ()
    $query = "ALTER TABLE sessions ";
  }

  function table_exists()
  {

    $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);
    session::$db->request("SHOW TABLE STATUS like 'sessions'",$context,false);

    $info = session::$db->fetchdata('assoc');

    if ($info == '')
    {
      return false;
    }
    else
    {
      // get info from table

      session::$tablesize = $info['Data_length'];
      session::$maxsize = $info['Max_data_length'];
      session::$indexsize = $info['Index_length'];
      session::$avgrowsize = $info['Avg_row_length'];
      session::$rows = $info['Rows'];
      preg_match('/max_rows=([0-9]+)/', $info['Create_options'], $match);
      session::$maxrows = $match[1]; // ] => max_rows=500
      /*
echo "<pre>";
print_r($info);
echo '</pre>';
echo session::$maxrows.'<br />';
*/
    }

    session::$db->request("SHOW FIELDS FROM sessions LIKE 'data%';", $context,false);
    session::$datachunks = session::$db->num_rows();
  }

  function chunk_fields($chunks='')
  {
    if ($chunks == '') $chunks = session::$datachunks;
//  $a = ;
/*
echo "<pre>";
print_r($a);
echo '</pre>';
*/           
// returns "data1, data2, ..." from 1 to $chunks
    return array_map(
           create_function('$i','return "data$i";'),
           array_combine(
             array_keys(
               array_fill(1,$chunks,'')),
             array_keys(
               array_fill(1,$chunks,''))));
  }

  function chunk_values($data)
  {
//    $a = ;
//echo $a.'<br />';    
    return array_map(
           'mysql_real_escape_string',
           explode("\n",
           wordwrap($data,255,"\n",1)));
  }
  
  function create($id)
  {
    ini_set("session.gc_probability", 50);
    ini_set("session.gc_maxlifetime", 60*45);
    //ini_set("session.cache_limiter", 'nocache');
    session_cache_limiter ('nocache');

    session_set_save_handler(
      array("session", "sess_open"),
      array("session", "sess_close"),
      array("session", "sess_read"),
      array("session", "sess_write"),
      array("session", "sess_destroy"),
      array("session", "sess_gc")
    ) or die("Failed to register session handler");

    session_start();
    session::$id = $_SESSION['session_id'] = $id;

  }
  
  function start()
  {
    session_start();
    return (isset($_SESSION['session_id']) ? (session::$id = $_SESSION['session_id']) : false);
  }

  function stop($new_location='')
  {
    session_start();
    session_destroy();
    $_SESSION = array();
    if (!empty($new_location))
    {
      ob_clean();
      header("Location: $new_location");
    }
  }

  function sess_read($id)
  {
    // get session data:
    
    $fields = implode(', ', session::chunk_fields());
    
    $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);

    session::$db->request("SELECT $fields FROM sessions WHERE valid='yes' AND skey='$id'",$context);

    $data = session::$db->fetchdata('num');

    return (is_array($data) ? implode('', $data) : '');

  }

  function sess_write($skey, $sess_data)
  {

    if (session::$id == null)
      trigger_error("Session ID not set",E_USER_ERROR);

//    $dum_values = array_fill(0,session::$datachunks,'');

    // split data into chunks
    $values = session::chunk_values($sess_data);

    $chunks = count($values);

    // check if enough chunks and update table if not!
    if ($chunks > session::$datachunks)
      session::expand_row();
      
echo "encoding ".strlen($sess_data)." bytes on $chunks x255 bytes (max:".session::$datachunks.") size: ".
     ($chunks*255-255+($chunks > 0 ? strlen($values[$chunks-1]) : 0)).'/'.(session::$datachunks * 255)." bytes<br />";

    $values = array_pad($values, session::$datachunks, ''); // apply actual values over dummy ones
    $fields = session::chunk_fields(session::$datachunks);
/*
echo "<pre>";
print_r($values);
echo '</pre>';
*/
    $comb_fc = create_function('$f,$v','return "$f = \'$v\'";'); // already escaped
    $set = implode(', ', array_map($comb_fc, $fields, $values));
    $fields = implode(', ', $fields);
    $values = "'".implode("', '", $values)."'";


    // hexadecimal ip
    $ip_sep = explode('.', $_SERVER['REMOTE_ADDR']);
    $ip = sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);

    // id and session_key
    $id = mysql_escape_string(session::$id);
    $skey = mysql_escape_string($skey);

    $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);
    $query = "SELECT valid,ip,skey FROM sessions WHERE id='$id'";
//echo $query.'<br />';
    session::$db->request($query,$context,true);
    array_pop($context);

    if (session::$db->num_rows())
    {
      $old_sess = session::$db->fetchdata('assoc');
/*
echo "<pre>";
print_r($old_sess);
echo '</pre>';
*/
      $valid = ($old_sess['valid'] == 'yes' && $old_sess['ip'] == $ip);
    }
    else
      $valid = null;


    if ($valid)
    // found a valid session
    // update
    { /* skey='$skey', ip='$ip',*/
      $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);
      $query = "UPDATE sessions SET $set, ctime=ctime,atime=NULL WHERE id = '$id'";
    }
    elseif ($valid === null)
    // session does not exist
    // create
    {
      $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);
      $query = "INSERT INTO sessions (id, skey, ip, ctime, atime, $fields) VALUES ('$id', '$skey', '$ip', NULL, NULL, $values)";
    }
    else
    // session is not valid
    // recreate
    {
      $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);
      $query = "UPDATE sessions SET $set, valid='yes',skey='$skey', ip='$ip',ctime=NULL,atime=NULL WHERE id = '$id'";
    }

echo $query.'<br />';

    session::$db->request($query,$context,false);

    return true;
  }

  function sess_destroy($skey)
  {
    $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);

    session::$db->request("UPDATE sessions SET valid='no' WHERE skey='$skey'",$context,false);

    return true;
  }

  function sess_gc($maxlifetime)
  {
    // invalidate any sessions older than $maxlifetime seconds old
    $query = "UPDATE sessions SET valid = 'no' WHERE valid = 'yes' AND
        atime < DATE_ADD(now(), INTERVAL -$maxlifetime SECOND)";

    $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);
    session::$db->request($query,$context,false);


    if (!session::$rows) return true;

    $free_rows_percent = 10; //%
    
    $free_rows = session::$maxrows * $free_rows_percent / 100;
    
    if (session::$rows > session::$maxrows-$free_rows)
    {
      $query = "DELETE FROM sessions WHERE valid='no' ORDER BY atime DESC LIMIT $free_rows";

      $context[] = database::traceline(__FILE__,__METHOD__,__LINE__);
      session::$db->request($query,$context);
      
      //**  dynamic max rows increase until absolute_max
    }
    return true;
  }
}



?>
