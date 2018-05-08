<?php

include 'include.php';

function serverequest() {
  global $filesfolder;
  global $firstrun;
  if (isset($_REQUEST["ID"])) {
    $sessionid = $_REQUEST["ID"];
    $playerid = $_REQUEST["playerid"];
    $requestpos = false;
    $requestsize = 0;
    if (isset($_REQUEST["Get-Bytes"])) {
      $requestsize = $_REQUEST["Get-Bytes"];
    }
    $contenttype = 'audio/webm;codecs="opus"';
    session_start();
    if (isset($_SESSION[$sessionid]["contenttype"])) {
      $contenttype = $_SESSION[$sessionid]["contenttype"];
    } else {
      $session = getsession($sessionid);
      if ($session != NULL)
        if (isset($session["Contenttype"])) {
          $contenttype = $session["Contenttype"];
          $_SESSION[$sessionid]["contenttype"] = $contenttype;
        }
    }
    session_write_close();
    if (isset($_REQUEST["Stream-Position"])) {
      $seek = (int) $_REQUEST["Stream-Position"];
      $requestpos = true;
    } else {
      if (isset($_REQUEST["SB"])) {
        $seek=0;
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_SERVER['HTTP_RANGE']) && $range = stristr(trim($_SERVER['HTTP_RANGE']), 'bytes=')) {
          $range = substr($range, 6);
          $ranges = explode(',', $range);
          if (count($ranges) > 0) {
            $singlerange = explode('-', $ranges[0]);
            $seek = $singlerange[0];
            if (is_numeric($singlerange[1])) {
              $requestsize = $singlerange[1];
            }
          }
          if ($seek>0 && $firstrun) {
            //logmsg("requested at $seek");
          }
        }
      } else {
        session_start();
        if (isset($_SESSION[$sessionid][$playerid]["position"])) {
          $seek = $_SESSION[$sessionid][$playerid]["position"];
        } else {
          $seek = 0;
        }
        session_write_close();
      }
    }
    $extension = "webm";
    if (startWith($contenttype, "audio/ogg")) {
      $extension = "ogg";
    }
    session_start();
    $skippedsize = 0;
    $startsequence = 0;
    if (isset($_SESSION[$sessionid]["chunks"])) {
      foreach ($_SESSION[$sessionid]["chunks"] as $seqid => $chunksize) {
        $startsequence = $seqid;
        if ($skippedsize + $chunksize >= $seek) {
          break;
        }
        $skippedsize += $chunksize;
      }
    }
    $loadedsize = 0;
    $content = "";

    //$files = glob("$filesfolder/$sessionid"."agg_*.$extension");
    //if (count($file)==0) {
    //  logmsg("no agg");
    //}
    //if ($firstsequence>$startsequence) {    
    //}

    timelog("01");
    for ($lastsequence = $startsequence; true; $lastsequence++) {
      if (file_exists("$filesfolder/$sessionid" . "seq_" . $lastsequence . ".$extension")) {
        $fs = filesize("$filesfolder/$sessionid" . "seq_" . $lastsequence . ".$extension");
        if ($fs > 0 && $fs !== false) {
          $_SESSION[$sessionid]["chunks"][$lastsequence] = $fs;
          if (($fh = fopen("$filesfolder/$sessionid" . "seq_" . $lastsequence . ".$extension", "r")) !== false) {
            if (flock($fh, LOCK_EX) !== false) {
              if (($partial = fread($fh, $fs)) !== false) {
                $content .= $partial;
                flock($fh, LOCK_UN);
                fclose($fh);
                $loadedsize += $fs;
                if ($requestsize != 0)
                  if ($seek + $requestsize < $skippedsize + $loadedsize)
                    break;
              } else {
                logmsg("$playerid read file error $lastsequence", "out_$sessionid.txt");
                break;
              }
            } else {
              logmsg("$playerid get file lock error $lastsequence", "out_$sessionid.txt");
              break;
            }
          } else {
            logmsg("$playerid open file error $lastsequence", "out_$sessionid.txt");
            break;
          }
        } else
          break;
      } else
        break;
    }
    timelog("02");
    //$chunks=$_SESSION[$sessionid]["chunks"];
    session_write_close();
    if ($skippedsize + strlen($content) > $seek) {
      if ($requestsize == 0) {
        $data = substr($content, $seek - $skippedsize);
      } else {
        $data = substr($content, $seek - $skippedsize, $requestsize);
      }
      session_start();
      $_SESSION[$sessionid][$playerid]["position"] = $seek + strlen($data);
      session_write_close();
      /*
        $ts=0;
        foreach($chunks as $cs){
        $ts+=$cs;
        } */
      //logmsg("$playerid fid:$startsequence sent:".strlen($data). " from:$seek to:".($seek+strlen($data))." skipped:$skippedsize loaded:$loadedsize total:".$ts." ".($loadedsize+$skippedsize),"out_$sessionid.txt");
      header("Content-Length: " . strlen($data));
      header("Stream-Position: " . $seek);
      header("Content-Type: " . $contenttype);
      if (isset($_REQUEST["SB"])) {        
        header("HTTP/1.1 206 Partial Content");
        //header("Content-Disposition: attachment; filename='$sessionid.$extension'");
        //header("Accept-Ranges: bytes");
        header(sprintf("Content-Range: bytes %d-%d/%d", $seek, $seek + strlen($data), 9223372036854775807));
        logmsg("sent content ".sprintf("Content-Range: bytes %d-%d/%d", $seek, $seek + strlen($data), 9223372036854775807)." $contenttype request\n"); //.print_r(getallheaders(),true));
      }
      //header(sprintf('Content-Disposition: attachment; filename="%s"', "chunk.webm"));
      echo $data;
      flush();
      timelog("03");
      //logmsg("data sent for $sessionid from $startsequence to $lastsequence");  
      die();
    }
    //logmsg("$playerid fid:$startsequence sent:0 from:".$seek." to:$seek skipped:$skippedsize loaded: $loadedsize total:".($loadedsize+$skippedsize),"out_$sessionid.txt");
    if (isset($_REQUEST["SB"])) {
      header("HTTP/1.1 503 Service Temporarily Unavailable");
      if ($firstrun) 
        logmsg("503 $seek ".($skippedsize + strlen($content)));
      return false;
    } else {
      header("HTTP/1.1 204 No Content");
      header("Stream-Position: " . $seek);
      die();
    }
  } else {
    logmsg("invalid request", "out_$sessionid.txt");
    header("HTTP/1.1 400 Invalid Request");
    die("<h3>Requests without an ID are invalid</h3>");
  }
}

$firstrun=true;
serverequest();

/*
$starttime=time();
while (serverequest()===false && (time() - $starttime <5) && isset($_REQUEST["SB"])) {
  $firstrun=false;
}/**/
