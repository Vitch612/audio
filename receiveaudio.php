<?php
include 'include.php';
$headers = getallheaders();
$stoprecording=false;
session_start();
$sessionid = session_id();
if (isset($_SESSION["stoprecording"]))
  $stoprecording=true;
session_write_close();
$contenttype = $headers["Content-Type"];
$body = file_get_contents('php://input');
if (strlen($body) > 0 || $stoprecording) {
  $bodysize = strlen($body);
  $outofsequence = false;
  if ($bodysize == $headers["Content-Length"]) {
    $cursequence = $headers["sequenceid"];
    session_start();
    if (isset($_SESSION["sequence"])) {
      $prevsequence = $_SESSION["sequence"];
      if ($prevsequence + 1 != $cursequence) {
        $outofsequence = true;
      } else {
        $_SESSION["sequence"] = $headers["sequenceid"];
      }
    } else {      
      $prevsequence = $cursequence - 1;          
    }    
    if (!isset($_SESSION["contenttype"])) {
      savesession($sessionid,["Contenttype"=>$contenttype]);
      $_SESSION["contenttype"]=$contenttype;
    }    
    session_write_close();
    $extension = "webm";
    if (startWith($contenttype, "audio/ogg")) {
      $extension = "ogg";
    }
    if ($outofsequence) {
      $catchedup = true;
      for ($i = $prevsequence + 1; $i < $cursequence; $i++) {
        if (file_exists("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension")) {
          $fs=getfilesize("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension");
          if ($fs > 0 && $fs!==false) {
            if (($fh = openfile("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension", "r")) === false) break;            
            while (flock($fh, LOCK_EX) === false);
            while (($partial = fread($fh, $fs)) === false);
            flock($fh, LOCK_UN);
            while (fclose($fh) === false);            
            if (($fh = openfile("$applicationfolder/files/$sessionid.$extension", "a")) === false) break;            
            while (flock($fh, LOCK_EX) === false);            
            fwrite($fh, $partial);
            flock($fh, LOCK_UN);
            while (fclose($fh) === false);
            deletefile("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension");
            session_start();
            $_SESSION["sequence"] = $i;
            session_write_close();
          }
        } else {
          $catchedup = false;
          break;
        }
      }
      if ($catchedup) {
        $outofsequence = false;
        session_start();
        $_SESSION["sequence"] = $cursequence;
        session_write_close();
      }
    }
    if ($outofsequence) {
      while (($fh = fopen("$applicationfolder/files/$sessionid" . "seq_" . $headers["sequenceid"] . ".$extension", "a")) === false);
      while (flock($fh, LOCK_EX) === false);
      fwrite($fh, $body);
      flock($fh, LOCK_UN);
      while (fclose($fh) === false);
    } else {
      while (($fh = fopen("$applicationfolder/files/$sessionid.$extension", "a")) === false);
      while (flock($fh, LOCK_EX) === false);
      fwrite($fh, $body);
      flock($fh, LOCK_UN);
      session_start();
      $_SESSION["sequence"] = $cursequence;
      session_write_close();
      while (fclose($fh) === false);
      for ($i = $cursequence + 1; true; $i++) {
        if (file_exists("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension")) {
          $fs=getfilesize("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension");
          if ($fs > 0 && $fs!==false) {
            if (($fh = openfile("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension", "r")) === false) break;
            while (flock($fh, LOCK_EX) === false);
            while (($partial = fread($fh, $fs)) === false);
            flock($fh, LOCK_UN);
            while (fclose($fh) === false);            
            if (($fh = openfile("$applicationfolder/files/$sessionid.$extension", "a")) === false) break;            
            while (flock($fh, LOCK_EX) === false);
            fwrite($fh, $partial);
            flock($fh, LOCK_UN);
            while (fclose($fh) === false);
            deletefile("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension");
            session_start();
            $_SESSION["sequence"] = $i;
            session_write_close();
          }
        } else {
          break;
        }
      }
    }
    session_start();
    if ($outofsequence) {
      if (isset($_SESSION["outofsync"])) {
        $_SESSION["outofsync"] +=1;
      } else {
        $_SESSION["outofsync"] = 1;
      }
      if ($_SESSION["outofsync"]>10) {
        askforreset($sessionid,$sessionid);
      }      
    } else {
      $_SESSION["outofsync"] = 0;
    }
    session_write_close();
    /*
    if ($outofsequence) {
      logmsg("receiveaudio: ".str_pad($headers["sequenceid"],6,"0",STR_PAD_LEFT)." outofsequence $sessionid");
    } else {
      logmsg("receiveaudio: ".str_pad($headers["sequenceid"],6,"0",STR_PAD_LEFT)." $sessionid");
    } 
    */
    header("HTTP/1.1 204 No Content");    
    header("GotData: " . $headers["sequenceid"]);    
    die();
  } else {
    logmsg("receiveaudio:inconsistent body size ".$headers["sequenceid"]." $sessionid actualsize:" .$bodysize." declared:".$headers["Content-Length"]);
    header("HTTP/1.1 400 Invalid Request");
    die("<h3>Received data inconsistent size</h3>");
  }
} else {
  logmsg("receiveaudio:empty body or stop recording ".$headers["sequenceid"]." $sessionid");
}
header("HTTP/1.1 204 No Content");
