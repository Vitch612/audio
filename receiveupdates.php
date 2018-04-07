<?php
include 'include.php';
if (isset($_REQUEST["update"])) {
  session_start();
  $sessionid=session_id();
  session_write_close();
  $requests=getresetrequests($sessionid);
  $reply = "0/";
  if ($requests!=NULL) {
    if (count($requests)>0) {
      $reply = "1/";
    }
    deleteresetrequests($sessionid);
  }
  if ($reply == "0/") {
    $preset=getplayerresetrequests($sessionid);
    if ($preset!=NULL)
      foreach($preset as $row) {
        $reply == $row["Sessionid"]."/";
        deleteplayerresetrequest($row["Sessionid"],$sessionid);
        break;
      }
  }
  if (($sessions=getsessions($sessionid))!=NULL)  
    foreach($sessions as $row) {
      if ($reply == "1/")
        askforplayerreset($sessionid,$row["Sessionid"]);
      if ($row["Name"]!="")
        $reply.=$row["Sessionid"].",".$row["Name"]."|";    
    }
  echo substr($reply,0,strlen($reply)-1);
}
if (isset($_REQUEST["sendreset"])) {  
  session_start();
  $sessionid=session_id();
  session_write_close();
  askforreset($sessionid,$_REQUEST["sendreset"]);
  header("HTTP/1.1 204 No Content");    
}
if (isset($_REQUEST["start"])) {
  session_start();
  $sessionid=session_id();
  session_unset();
  session_write_close();
  savesession($sessionid,["name"=>$_REQUEST["start"]]);
  if (file_exists("$applicationfolder/files/$sessionid.webm"))
    unlink("$applicationfolder/files/$sessionid.webm");
  if (file_exists("$applicationfolder/files/$sessionid.ogg"))
    unlink("$applicationfolder/files/$sessionid.ogg");
  $reply="";
  $sessions=getsessions($sessionid);
  if ($sessions!=NULL)
    foreach($sessions as $row) {
      if ($row["Name"]!="")
        $reply.=$row["Sessionid"].",".$row["Name"]."|";
    }
  if (strlen($reply)>0)
    echo substr($reply,0,strlen($reply)-1);
}
if (isset($_REQUEST["stop"])) {  
  session_start();
  $sessionid=session_id();
  session_unset();
  $_SESSION["stoprecording"]=true;
  session_write_close();
  savesession($sessionid,["name"=>""]);
}
if (isset($_REQUEST["close"])) {
  session_start();
  if (isset($_SESSION["sequence"]))
    $sequence=$_SESSION["sequence"];
  $sessionid=session_id();
  session_unset();  
  $_SESSION["stoprecording"]=true;
  session_write_close();  
  $extension = "webm";  
  $session=getsession($sessionid);
  if ($session!=NULL) {
    $contenttype=$session["Contenttype"];
    if (startWith($contenttype, "audio/ogg")) {
      $extension = "ogg";
    }          
  }
  savesession($sessionid,["name"=>""]);
  if (file_exists("$applicationfolder/files/$sessionid.$extension")) {
    unlink("$applicationfolder/files/$sessionid.$extension");
  }
  array_map('unlink', glob("$applicationfolder/files/".$sessionid."seq_*.$extension"));
}