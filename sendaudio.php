<?php
include 'include.php';
if (isset($_REQUEST["ID"])) {
  $sessionid=$_REQUEST["ID"];
  $playerid=$_REQUEST["playerid"];
  $requestpos=false;
  $requestsize=0;  
  if (isset($_REQUEST["Get-Bytes"])) {
    $requestsize=$_REQUEST["Get-Bytes"];
  }  
  $contenttype = 'audio/webm;codecs="opus"';
  session_start();
  if (isset($_SESSION[$sessionid]["contenttype"])) {
    $contenttype=$_SESSION[$sessionid]["contenttype"];
  } else {
    $session=getsession($sessionid);
    if ($session!=NULL)
      if (isset($session["Contenttype"])) {
        $contenttype=$session["Contenttype"];
        $_SESSION[$sessionid]["contenttype"]=$contenttype;
      }   
  }
  session_write_close();
  if (isset($_REQUEST["Stream-Position"])) {
    $seek=(int)$_REQUEST["Stream-Position"];
    $requestpos=true;
  } else {
    session_start();
    if (isset($_SESSION[$sessionid][$playerid]["position"])) {
      $seek=$_SESSION[$sessionid][$playerid]["position"];
    } else {
      $seek=0;
    }
    session_write_close();
  }  
  $extension = "webm";
  if (startWith($contenttype, "audio/ogg")) {
    $extension = "ogg";
  }  
  if (file_exists("$applicationfolder/files/$sessionid.$extension")) {
    $fsize = getfilesize("$applicationfolder/files/$sessionid.$extension");
    if ($fsize!==false)
      if ($fsize-$seek-$requestsize>0) {
        while(($fh=fopen("$applicationfolder/files/$sessionid.$extension","r"))===false);
        while(flock($fh, LOCK_SH)===false);
        fseek($fh,$seek);
        if ($requestsize>0)
          while(($data=fread($fh,$requestsize))===false);
        else
          while(($data=fread($fh,$fsize-$seek))===false);
        flock($fh, LOCK_UN);
        while(fclose($fh)===false);        
        if (strlen($data)>0) {
          session_start();
          $_SESSION[$sessionid][$playerid]["position"]=$fsize;
          session_write_close();
          header("Content-Length: ".strlen($data));
          header("Stream-Position: ".$seek);
          header("Content-Type: ".$contenttype);
          echo $data;
          flush();            
          //logmsg("snd ".strlen($data)." from $sessionid at $seek for $playerid");
          die();
          /*
          if ($requestpos)
            logmsg("snd ".strlen($data)." at $seek reqpos to $sessionid");
          else
            logmsg("snd ".strlen($data)." at $seek to $sessionid");
          */
        }
      }
  }
  header("HTTP/1.1 204 No Content");
  header("Stream-Position: ".$seek);
} else {
  header("HTTP/1.1 400 Invalid Request");
  die("<h3>Requests without an ID are invalid</h3>");
}