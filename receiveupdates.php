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
  $reply="";
  $sessions=getsessions($sessionid);
  array_map('unlink', glob("$filesfolder/".$sessionid."seq_*.webm"));
  array_map('unlink', glob("$filesfolder/".$sessionid."seq_*.ogg"));
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
  array_map('unlink', glob("$filesfolder/".$sessionid."seq_*.$extension"));
}