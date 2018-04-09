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
  session_start();
  $skippedsize=0;
  $startsequence=0;
  if (isset($_SESSION[$sessionid]["chunks"])) {
    foreach($_SESSION[$sessionid]["chunks"] as $seqid=>$chunksize) {      
      $startsequence=$seqid;
      if ($skippedsize+$chunksize>=$seek) {
        break;
      }            
      $skippedsize+=$chunksize;
    }
  }
  $loadedsize=0;
  $content="";
  timelog("start assembling data");
  for ($i = $startsequence; true; $i++) {
    if (file_exists("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension")) {
      $fs=filesize("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension");
      //$fs = getfilesize("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension");
      if ($fs > 0 && $fs !== false) {
        $_SESSION[$sessionid]["chunks"][$i] = $fs;
        if (($fh = fopen("$applicationfolder/files/$sessionid" . "seq_" . $i . ".$extension", "r")) !== false) {
          if (flock($fh, LOCK_SH) !== false) {
            if (($partial = fread($fh, $fs)) !== false) {
              $content .= $partial;
              flock($fh, LOCK_UN);
              fclose($fh);
              $loadedsize += $fs;
              if ($requestsize != 0)
                if ($seek + $requestsize < $skippedsize + $loadedsize)
                  break;
            } else {
              logmsg("$playerid read file error $i", "out_$sessionid.txt");
              break;
            }
          } else {
            logmsg("$playerid get file lock error $i", "out_$sessionid.txt");
            break;            
          }
        } else {
          logmsg("$playerid open file error $i", "out_$sessionid.txt");
          break;
        }
      } else
        break;
    } else break;
  }
  timelog("done assembling data");
  //$chunks=$_SESSION[$sessionid]["chunks"];
  session_write_close();
  if ($skippedsize+strlen($content)>$seek) {
    if ($requestsize==0) {
      $data=substr($content,$seek-$skippedsize);
    } else {
      $data=substr($content,$seek-$skippedsize,$requestsize);
    }
    session_start();
    $_SESSION[$sessionid][$playerid]["position"]=$seek+strlen($data);
    session_write_close();
    /*
    $ts=0;
    foreach($chunks as $cs){
      $ts+=$cs;
    }*/
    //logmsg("$playerid fid:$startsequence sent:".strlen($data). " from:$seek to:".($seek+strlen($data))." skipped:$skippedsize loaded:$loadedsize total:".$ts." ".($loadedsize+$skippedsize),"out_$sessionid.txt");
    header("Content-Length: ".strlen($data));
    header("Stream-Position: ".$seek);
    header("Content-Type: ".$contenttype);
    //header(sprintf('Content-Disposition: attachment; filename="%s"', "chunk.webm"));
    echo $data;
    flush();
    timelog("data sent");
    die();
  }
  //logmsg("$playerid fid:$startsequence sent:0 from:".$seek." to:$seek skipped:$skippedsize loaded: $loadedsize total:".($loadedsize+$skippedsize),"out_$sessionid.txt");
  timelog("nothing to send");
  header("HTTP/1.1 204 No Content");
  header("Stream-Position: ".$seek);
} else {
  timelog("invalid request");
  logmsg("invalid request","out_$sessionid.txt");
  header("HTTP/1.1 400 Invalid Request");
  die("<h3>Requests without an ID are invalid</h3>");
}