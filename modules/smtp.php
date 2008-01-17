<?php

/**
 * smtp.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains all the functions needed to send messages through
 * an smtp server or sendmail.
 *
 * $Id: smtp.php,v 1.2 2005/09/04 19:15:43 Administrator Exp $
 */

////////////////////////////////////////////////////////////////////////////////
// définir ORG_EMAIL, REAL_NAME, SMTP_ADDR, SMTP_PORT, DOMAIN -> define("ORG_EMAIL", 'foire@step.polymtl.ca');
////////////////////////////////////////////////////////////////////////////////


define("SMTP_ADDR", 'smtp.polymtl.ca');
define("SMTP_PORT", 25);
define("DOMAIN", 'polymtl.ca');
//define("REAL_NAME","Foire aux Livres");
define("ORG_EMAIL", 'Foire aux Livres <foire@step.polymtl.ca>');
define("DEFAULT_CHARSET", 'iso-8859-1');
$default_charset = 'iso-8859-1';

/**
 * Returns an array of email addresses.
 * Be cautious of "user@host.com"
 */
function parseAddrs($text) {
    if (trim($text) == '')
        return array();
    $text = str_replace(' ', '', $text);
    $text = ereg_replace('"[^"]*"', '', $text);
    $text = ereg_replace('\\([^\\)]*\\)', '', $text);
    $text = str_replace(',', ';', $text);
    $array = explode(';', $text);

    foreach($array as $part) {
        $part = eregi_replace ('^.*[<]', '', $part);
        $part = eregi_replace ('[>].*$', '', $part);

        if($part != '')
            $new_array[] = $part;
    }

    return $new_array;
}

function cornerAddrs ($array) {

    for ($i=0; $i < count($array); $i++) {
        $array[$i] = '<' . $array[$i] . '>';
    }
    return $array;
}


/* Returns true only if this message is multipart */
function isMultipart ($session) {
    global $attachments;

    foreach ($attachments as $info) {
        if ($info['session'] == $session) {
            return true;
        }
    }
    return false;
}

/*
 * Encode a string according to RFC 1522 for use in headers if it
 * contains 8-bit characters or anything that looks like it should
 * be encoded.
 */
function encodeHeader ($string) {
    $default_charset = 'iso-8859-1';

    // Encode only if the string contains 8-bit characters or =?
    $j = strlen( $string  );
    $l = strstr($string, '=?');         // Must be encoded ?
    $ret = '';
    for( $i=0; $i < $j; ++$i) {
        switch( $string{$i} ) {
           case '=':
          $ret .= '=3D';
          break;
        case '?':
          $ret .= '=3F';
          break;
        case '_':
          $ret .= '=5F';
          break;
        case ' ':
          $ret .= '_';
          break;
        default:
          $k = ord( $string{$i} );
          if ( $k > 126 ) {
             $ret .= sprintf("=%02X", $k);
             $l = TRUE;
          } else
             $ret .= $string{$i};
        }
    }

    if ( $l ) {
        $string = "=?$default_charset?Q?$ret?=";
    }

    return( $string );
}

/* Return a nice MIME-boundary
 */
function mimeBoundary () {
    static $mimeBoundaryString;

    if ( !isset( $mimeBoundaryString ) ||
         $mimeBoundaryString == '') {
        $mimeBoundaryString = '----=_' . date( 'YmdHis' ) . '_' .
            mt_rand( 10000, 99999 );
    }

    return $mimeBoundaryString;
}

/* Time offset for correct timezone */
function timezone () {
    global $invert_time;
    
    $diff_second = date('Z');
    if ($invert_time) {
        $diff_second = - $diff_second;
    }
    if ($diff_second > 0) {
        $sign = '+';
    }
    else {
        $sign = '-';
    }

    $diff_second = abs($diff_second);
    
    $diff_hour = floor ($diff_second / 3600);
    $diff_minute = floor (($diff_second-3600*$diff_hour) / 60);
    
    $zonename = '('.strftime('%Z').')';
    $result = sprintf ("%s%02d%02d %s", $sign, $diff_hour, $diff_minute, 
                       $zonename);
    return ($result);
}

/* Print all the needed RFC822 headers */
function write822Header ($fp, $t, $c, $b, $subject, $from=ORG_EMAIL, $rn="\r\n") {
//    global $data_dir, $username, $popuser, $domain, $version, $useSendmail;
//    global $default_charset;//, $identity,
    global $_SERVER;
    global $from_addr;

    $default_charset = 'iso-8859-1';
    
    /* get those globals */

    $SERVER_ADDR = $_SERVER['SERVER_ADDR'];
    $SERVER_NAME = $_SERVER['SERVER_NAME'];
    $SERVER_PORT = $_SERVER['SERVER_PORT'];


        $headerrn = $rn;
        
        // liste(array[]) d'adresses [user@domain.com]
        $to_array = parseAddrs($t);
        $cc_array = parseAddrs($c);
        $bcc_array = parseAddrs($b);

//            $reply_to = getPref($data_dir, $username, 'reply_to');
        
        // $t is already a well formed list : "name" <user@domain.com>, "name" ...
        $to_list = $t;
        $cc_list = $c;
        $bcc_list = $b;

        /* Encoding 8-bit characters and making from line */
        $subject = encodeHeader($subject);
        
//        $from = '"' . encodeHeader(REAL_NAME) . '" <'.$from.'>';
       
        /* This creates an RFC 822 date */
        $date = date("D, j M Y H:i:s ", mktime()) . timezone();
        
        /* Create a message-id */
//        $message_id = '<' . $REMOTE_PORT . '.' . $REMOTE_ADDR . '.';
//        $message_id .= time() . '.squirrel@' . $SERVER_NAME .'>';
        $message_id = '<' . $SERVER_PORT . '.' . $SERVER_ADDR . '.';
        $message_id .= time() . '.'. $from .'>';
        
        /* Make an RFC822 Received: line */
        $received_from = "$SERVER_NAME ([$SERVER_ADDR])";

        $header  = "Received: from $received_from" . $rn;
        $header .= "          by $SERVER_NAME with HTTP;" . $rn;
        $header .= "          $date" . $rn;

// Return-receipt-to
// Bounce-to
        /* Insert the rest of the header fields */
        $header .= "Message-ID: $message_id" . $rn;
        $header .= "Date: $date" . $rn;
        $header .= "Subject: $subject" . $rn;
        $header .= "From: $from" . $rn;
        $header .= "To: $to_list" . $rn;    // Who it's TO

        
        if (!empty($default_charset)) {
          $contentType = 'text/plain; charset='.$default_charset;
        }
        else {
          $contentType = 'text/plain;';
        }

        if ($cc_list) {
            $header .= "Cc: $cc_list" . $rn; // Who the CCs are
        }

        if (!empty($reply_to)) {
            $header .= "Reply-To: $reply_to" . $rn;
        }

        /* Identify SquirrelMail */
//        $header .= "X-Mailer: SquirrelMail (version $version)" . $rn; 
        $header .= "X-Mailer: mitaineMail (version 2.0)" . $rn; 

        //* Do the MIME-stuff
        $header .= "MIME-Version: 1.0" . $rn;

        $header .= 'Content-Type: ' . $contentType . $rn;
        $header .= "Content-Transfer-Encoding: 8bit" . $rn;

        $header .= $rn; // One blank line to separate header and body
        
        $headerlength = strlen($header);

    if ($headerrn != $rn) {
        $header = str_replace($headerrn, $rn, $header);
        $headerlength = strlen($header);
        $headerrn = $rn;
    }
    
    /* Write the header */
    if ($fp) fputs ($fp, $header);
    
    return $headerlength;
}

/* Send the body
 */
function writeBody ($fp, $passedBody, $rn="\r\n", $checkdot = false) {
    global $default_charset;

    $attachmentlength = 0;

        $body = $passedBody . $rn;
        if ($fp) fputs ($fp, $body);
        $postbody = $rn;
        if ($fp) fputs ($fp, $postbody);

        
    return (strlen($body) + strlen($postbody) + $attachmentlength);
}


function smtpReadData($smtpConnection) {
    $read = fgets($smtpConnection, 1024);
    $counter = 0;
    while ($read) {
        echo $read . '<BR>';
        $data[$counter] = $read;
        $read = fgets($smtpConnection, 1024);
        $counter++;
    }
}

function sendSMTP($t, $c, $b, $subject, $body, $verbose = false, $from=ORG_EMAIL) {

    if (($body{0} == '.')) {
        $body = '.' . $body;
    }
    $body = str_replace("\n.","\n..",$body);
    
    // array[] of <user@domain.com>
    $to = cornerAddrs(parseAddrs($t));
    $cc = cornerAddrs(parseAddrs($c));
    $bcc = cornerAddrs(parseAddrs($b));

    $from_addr = $from;

    $smtpServerAddress = SMTP_ADDR;
    $smtpPort = SMTP_PORT;
    $domain = DOMAIN;
    
    $smtpConnection = fsockopen($smtpServerAddress, $smtpPort, 
                                $errorNumber, $errorString);
    if (!$smtpConnection) {

        if ($errorNumber != 0) {
            $msg = "\nError connecting to SMTP Server.<br>";
            $msg = $msg . "\n$errorNumber : $errorString<br><br>";

            echo '<br /><br />'.$msg;
        }
        return(0);
    }
    $tmp = fgets($smtpConnection, 1024);
    if (errorCheck($tmp, $smtpConnection)!=5) {
        return(0);
    }
    
    $to_list = $t;
    $cc_list = $c;
    
    /* Lets introduce ourselves */
    
//    if (! isset ($use_authenticated_smtp) 
//        || $use_authenticated_smtp == false) {
        fputs($smtpConnection, "HELO $domain\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) return(0);
/*    }  else {
        fputs($smtpConnection, "EHLO $domain\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) return(0);
        
        fputs($smtpConnection, "AUTH LOGIN\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }

        fputs($smtpConnection, base64_encode ($username) . "\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }
        
        fputs($smtpConnection, base64_encode 
              (OneTimePadDecrypt($key, $onetimepad)) . "\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }
    }*/
    
    /* Ok, who is sending the message? */
    fputs($smtpConnection, "MAIL FROM: <$from_addr>\r\n");
    $tmp = fgets($smtpConnection, 1024);
    if (errorCheck($tmp, $smtpConnection)!=5) {
        return(0);
    }
    
    /* send who the recipients are */
    for ($i = 0; $i < count($to); $i++) {
        fputs($smtpConnection, "RCPT TO: $to[$i]\r\n");
        $tmp = fgets($smtpConnection, 1024);
//        if (errorCheck($tmp, $smtpConnection)!=5) {
//            return(0);
//        }
        $num = errorCheck($tmp, $smtpConnection, $verbose);
        if ($num !=5 && ($num < 200 || $num > 299)) {
            return(0);
        }
    }
    for ($i = 0; $i < count($cc); $i++) {
        fputs($smtpConnection, "RCPT TO: $cc[$i]\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }
    }
    for ($i = 0; $i < count($bcc); $i++) {
        fputs($smtpConnection, "RCPT TO: $bcc[$i]\r\n");
        $tmp = fgets($smtpConnection, 1024);
        if (errorCheck($tmp, $smtpConnection)!=5) {
            return(0);
        }
    }

    /* Lets start sending the actual message */
    fputs($smtpConnection, "DATA\r\n");
    $tmp = fgets($smtpConnection, 1024);
    if (errorCheck($tmp, $smtpConnection)!=5) {
        return(0);
    }

    /* Send the message */
    $headerlength = write822Header ($smtpConnection, $t, $c, $b, $subject, $from_addr);
    $bodylength = writeBody($smtpConnection, $body, "\r\n", true);
    
    fputs($smtpConnection, ".\r\n"); /* end the DATA part */
    $tmp = fgets($smtpConnection, 1024);
    $num = errorCheck($tmp, $smtpConnection, true);
    if ($num != 250) {
        return(0);
    }
    
    fputs($smtpConnection, "QUIT\r\n"); /* log off */
    
    fclose($smtpConnection);
    
    return ($headerlength + $bodylength);
}


function errorCheck($line, $smtpConnection, $verbose = false) {
    global $color, $compose_new_win;
    
    /* Read new lines on a multiline response */
    $lines = $line;
    while(ereg("^[0-9]+-", $line)) {
        $line = fgets($smtpConnection, 1024);
        $lines .= $line;
    }
    
    /* Status:  0 = fatal
     *          5 = ok
     */
    $err_num = substr($line, 0, strpos($line, " "));
    switch ($err_num) {
    case 500:   $message = 'Syntax error; command not recognized';
        $status = 0;
        break;
    case 501:   $message = 'Syntax error in parameters or arguments';
        $status = 0;
        break;
    case 502:   $message = 'Command not implemented';
        $status = 0;
        break;
    case 503:   $message = 'Bad sequence of commands';
        $status = 0;
        break;
    case 504:   $message = 'Command parameter not implemented';
        $status = 0;
        break;    
        
    case 211:   $message = 'System status, or system help reply';
        $status = 5;
        break;
    case 214:   $message = 'Help message';
        $status = 5;
        break;
        
    case 220:   $message = 'Service ready';
        $status = 5;
        break;
    case 221:   $message = 'Service closing transmission channel';
        $status = 5;
        break;

    case 421:   $message = 'Service not available, closing chanel';
        $status = 0;
        break;
        
    case 235:   return(5); 
        break;
    case 250:   $message = 'Requested mail action okay, completed';
        $status = 5;
        break;
    case 251:   $message = 'User not local; will forward';
        $status = 5;
        break;
    case 334:   return(5); break;
    case 450:   $message = 'Requested mail action not taken:  mailbox unavailable';
        $status = 0;
        break;
    case 550:   $message = 'Requested action not taken:  mailbox unavailable';
        $status = 0;
        break;
    case 451:   $message = 'Requested action aborted:  error in processing';
        $status = 0;
        break;
    case 551:   $message = 'User not local; please try forwarding';
        $status = 0;
        break;
    case 452:   $message = 'Requested action not taken:  insufficient system storage';
        $status = 0;
        break;
    case 552:   $message = 'Requested mail action aborted:  exceeding storage allocation';
        $status = 0;
        break;
    case 553:   $message = 'Requested action not taken: mailbox name not allowed';
        $status = 0;
        break;
    case 354:   $message = 'Start mail input; end with .';
        $status = 5;
        break;
    case 554:   $message = 'Transaction failed';
        $status = 0;
        break;
    default:    $message = 'Unknown response: '. nl2br(htmlspecialchars($lines));
        $status = 0;
        $error_num = '001';
        break;
    }

    if ($status == 0 && $verbose) {

        $lines = str_replace('\n', '<br />', htmlspecialchars($lines));
        $msg  = $message . "<br>\nServer replied: $lines";

        echo '<br /><br />'.$msg;
    }
    if (! $verbose) return $status;
    return $err_num;
}

function createPriorityHeaders($prio) {
    $prio_headers = Array();
    $prio_headers['X-Priority'] = $prio;

    switch($prio) {
    case 1: 
        $prio_headers['Importance'] = 'High';
        break;

    case 3: 
        $prio_headers['Importance'] = 'Normal';
        break;

    case 5:
        $prio_headers['Importance'] = 'Low';
        break;
    }
    return  $prio_headers;
}


?>
