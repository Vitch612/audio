<?php
include 'include.php';
$headers = getallheaders();
$stoprecording = false;
session_start();
$sessionid = session_id();
if (isset($_SESSION["stoprecording"]))
  $stoprecording = true;
session_write_close();
$contenttype = $headers["Content-Type"];
timelog("start reading input");
$body = file_get_contents('php://input');
timelog("got request body");
if (strlen($body) > 0 || $stoprecording) {
  $bodysize = strlen($body);
  $outofsequence = false;
  if ($bodysize == $headers["Content-Length"]) {
    session_start();
    if (!isset($_SESSION["contenttype"])) {
      savesession($sessionid, ["Contenttype" => $contenttype]);
      $_SESSION["contenttype"] = $contenttype;
    }
    session_write_close();
    $extension = "webm";
    if (startWith($contenttype, "audio/ogg")) {
      $extension = "ogg";
    }
    timelog("start saving to file");
    $fh = fopen("$applicationfolder/files/$sessionid" . "seq_" . $headers["sequenceid"] . ".$extension", "a");
    if ($fh !== false) {
      if (flock($fh, LOCK_EX) !== false) {
        fwrite($fh, $body);
        flock($fh, LOCK_UN);
        fclose($fh);
        //logmsg("received " . strlen($body) . " " . $headers["sequenceid"] . " firsttry", "in_$sessionid.txt");
        header("HTTP/1.1 204 No Content");
        header("GotData: " . $headers["sequenceid"]);
        timelog("done saving to file");
        die();
      }
    }
    logmsg("failed to save " . strlen($body) . " " . $headers["sequenceid"], "in_$sessionid.txt");
    header("HTTP/1.1 500 Internal Error");
    timelog("failed saving to file");
    die();
  } else {
    logmsg("receiveaudio:inconsistent body size " . $headers["sequenceid"] . " $sessionid actualsize:" . $bodysize . " declared:" . $headers["Content-Length"], "in_$sessionid.txt");
    header("HTTP/1.1 400 Invalid Request");
    timelog("request data inconsistend");
    die("<h3>Received data inconsistent size</h3>");
  }
} else {
  timelog("empty body or stop recording");
  logmsg("receiveaudio:empty body or stop recording " . $headers["sequenceid"] . " $sessionid", "in_$sessionid.txt");
  header("HTTP/1.1 204 No Content");
}
timelog("no way this is logged");


