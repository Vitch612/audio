<?php
include 'include.php';
if (isset($_REQUEST["update"])) {
  timelog("update request start");
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
        $reply = $row["Sessionid"]."/";
        deleteplayerresetrequest($row["Sessionid"],$sessionid);
        break;
      }
  }
  if (($sessions=getsessions($sessionid))!=NULL)  
    foreach($sessions as $row) {
      if ($row["Name"]!="") {
        if ($reply == "1/")
          askforplayerreset($sessionid,$row["Sessionid"]);
        $reply.=$row["Sessionid"].",".$row["Name"]."|";    
      }        
    }
  echo substr($reply,0,strlen($reply)-1);
  timelog("update request done");
}
if (isset($_REQUEST["sendreset"])) {  
  timelog("reset request start");
  session_start();
  $sessionid=session_id();
  session_write_close();
  askforreset($sessionid,$_REQUEST["sendreset"]);
  header("HTTP/1.1 204 No Content");    
  timelog("reset request done");
}
if (isset($_REQUEST["start"])) {
  timelog("start request start");
  session_start();
  $sessionid=session_id();
  session_unset();
  session_write_close();
  savesession($sessionid,["name"=>$_REQUEST["start"]]);
  $reply="";
  $sessions=getsessions($sessionid);
  array_map('unlink', glob("$applicationfolder/files/".$sessionid."seq_*.webm"));
  array_map('unlink', glob("$applicationfolder/files/".$sessionid."seq_*.ogg"));
  if ($sessions!=NULL)
    foreach($sessions as $row) {
      if ($row["Name"]!="")
        $reply.=$row["Sessionid"].",".$row["Name"]."|";
    }
  if (strlen($reply)>0)
    echo substr($reply,0,strlen($reply)-1);
  timelog("start request done");
}
if (isset($_REQUEST["stop"])) {  
  timelog("stop request start");
  session_start();
  $sessionid=session_id();
  session_unset();
  $_SESSION["stoprecording"]=true;
  session_write_close();
  savesession($sessionid,["name"=>""]);
  timelog("stop request done");
}
if (isset($_REQUEST["close"])) {
  timelog("close request start");
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
  array_map('unlink', glob("$applicationfolder/files/".$sessionid."seq_*.$extension"));
  timelog("close request done");
}