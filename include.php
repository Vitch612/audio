<?php
include 'database.php';
function getfile($filepath) {
  $s=NULL;
  if (file_exists($filepath)) {
    $fsize = getfilesize($filepath);
    if ($fsize !== false) {
      if ($fsize > 0) {
        if (($fh = fopen($filepath, "r")) === false) return "";
        while (flock($fh, LOCK_SH) === false);
        while (($s = fread($fh, $fsize)) === false);
        while (flock($fh, LOCK_UN) === false);
        while (fclose($fh) === false);
        return $s;
      } else {
        return "";
      }
    }
  } else {
    return "";
  }
  return $s;
}
function openfile($filepath,$mode) {
  $fh=false;
  while($fh===false) {
    if ($mode=="w") {
      $fh = fopen($filepath, $mode);
    } else {
      if (file_exists($filepath)) {
        $fh = fopen($filepath, $mode);
      } else {
        return false;
      }
    }
  }
  return $fh;
}
function getfilesize($filepath) {  
  $filesize=-1;
  while($filesize===-1) {
    if (file_exists($filepath)) {
      if (($fs=filesize($filepath))!==false) {
        $filesize=$fs;  
      }
    } else {
      $filesize=false;
    }
  }
  return $filesize;
}
function deletefile($filepath) {  
  $done=false;
  while($done===false) {
    if (file_exists($filepath)) {
      $done=unlink($filepath);
    } else {
      $done=true;
    }
  }
}
function putfile($filepath,$data,$mode="w") {  
  while(($fh=fopen($filepath,$mode))===false);  
  while(flock($fh, LOCK_EX)===false);
  fwrite($fh, $data);
  while(flock($fh, LOCK_UN)===false);
  while(fclose($fh)===false);
}
function canUsePersistent() {
  global $filesfolder;
  return !file_exists("$filesfolder/lockdata");
}
function lockPersistent($lock) {
  global $filesfolder;
  if ($lock===true) {
    putfile("$filesfolder/lockdata","true");
  } else {
    deletefile("$filesfolder/lockdata");
  }
}
function readPersistent($name) {
  global $filesfolder;
  while(canUsePersistent()===false);
  lockPersistent(true);
  if (($s=getfile("$filesfolder/data.sr"))!==NULL)
    if (($a = unserialize($s))!==false)
      if (isset($a[$name])) {
        lockPersistent(false);
        return $a[$name];
      } else {
        lockPersistent(false);
        return NULL;      
      }    
  lockPersistent(false);
  logmsg("failed to get $name ".debug_backtrace()[0]["file"]."(".debug_backtrace()[0]["line"].") s=".$s." a=".print_r($a,true));
  return NULL;
}
function deletePersistent($name) {
  global $filesfolder;
  while(canUsePersistent()===false);
  lockPersistent(true);
  if (($s=getfile("$filesfolder/data.sr"))!=NULL)
    if(($a = unserialize($s))!==false) {
      if (isset($a[$name])) {
        unset($a[$name]);
        $s=serialize($a);          
        putfile("$filesfolder/data.sr", $s);        
      }
      lockPersistent(false);    
      return true;
    }    
  lockPersistent(false);
  logmsg("failed to delete $name ".debug_backtrace()[0]["file"]."(".debug_backtrace()[0]["line"].") s=".$s." a=".print_r($a,true));
  return false;
}
function savePersistent($name, $value) {
  global $filesfolder;
  while(canUsePersistent()===false);
  lockPersistent(true);
  $a = [];
  if (($s = getfile("$filesfolder/data.sr")) !== NULL) {
    if ($s === "") {
      $a[$name] = $value;
      $s = serialize($a);
      putfile("$filesfolder/data.sr", $s);
      lockPersistent(false);
      return true;
    } else {
      if (($a = unserialize($s)) !== false) {
        $a[$name] = $value;
        $s = serialize($a);
        putfile("$filesfolder/data.sr", $s);
        lockPersistent(false);
        return true;
      }
    }
  }    
  lockPersistent(false);
  logmsg("failed to save $name " . debug_backtrace()[0]["file"] . "(" . debug_backtrace()[0]["line"] . ") s=".$s." a=" . print_r($a, true) . "\nvalue=" . print_r($value, true));
  return false;  
}
function logmsg($text,$logfile="logfile.txt") {
  global $filesfolder;
  //file_put_contents("$filesfolder/logfile.txt",date("Y-m-d h:i:sa").": ".$text."\n",FILE_APPEND);
  file_put_contents("$filesfolder/$logfile",$text."\n",FILE_APPEND);
  //putfile("$filesfolder/$logfile",$text."\n","a");
  //putfile("$filesfolder/$logfile",date("Y-m-d h:i:sa").": ".$text."\n","a");
}
function startWith($haystack,$needle,$case=false) {
  if ($case)
    return (strcasecmp(substr($haystack,0,strlen($needle)),$needle)===0);
  else
    return (strcmp(substr($haystack,0,strlen($needle)), $needle)===0);
}
function endWith($haystack,$needle,$case=false) {
	if($case)
		return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
	else
    return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
}
function getsessions($sessionid) {
  global $mysql;
  $results=$mysql->select("sessions",["*"],"`Sessionid`<>'$sessionid'");
  if (count($results)>0)
    return $results;
  else
    return NULL;
}
function getsession($sessionid) {
  global $mysql;
  $result=$mysql->select("sessions",["*"],"`Sessionid`='$sessionid'");
  if (count($result)>0) 
    return $result[0];
  else 
    return NULL;
}
function savesession($sessionid,$values) {
  global $mysql;  
  $existing=getsession($sessionid);
  if ($existing!=NULL) {
    $mysql->update("sessions",$values,"`ID`='".$existing["ID"]."'");
  } else {
    if (!isset($values["Sessionid"])) {
      $values["Sessionid"]=$sessionid;
    }
    $mysql->insert("sessions",$values);
  }   
}
function askforreset($session1,$session2) {
  global $mysql;
  $sid1=getsession($session1);
  $sid2=getsession($session2);
  if ($sid1!=NULL && $sid2!=NULL)
    $mysql->insert("requestreset",["Requestsession"=>$sid1["ID"],"Targetsession"=>$sid2["ID"]]);
}
function getresetrequests($sessionid) {
  global $mysql;  
  $sid=getsession($sessionid);
  if ($sid!=NULL)
    if (count($sid)>0) {
      $results=$mysql->select("requestreset",["`t_sess`.`Sessionid`"],"`Targetsession`='".$sid["ID"]."'","INNER JOIN `sessions` as r_sess ON `requestreset`.`Requestsession` = `r_sess`.`ID` INNER JOIN `sessions` t_sess ON `requestreset`.`Targetsession` = `t_sess`.`ID`");
      if (count($results)>0) 
        return $results;
    }
  return NULL;
}
function deleteresetrequests($sessionid) {
  global $mysql;
  $sid=getsession($sessionid);
  if (count($sid)>0)
    $mysql->delete("requestreset","`Targetsession`='".$sid["ID"]."'");
}
function askforplayerreset($source,$target) {
  global $mysql;
  $sid1=getsession($source);
  $sid2=getsession($target);
  if ($sid1!=NULL && $sid2!=NULL)
    $mysql->insert("resetplayer",["Resetsource"=>$sid1["ID"],"Resettarget"=>$sid2["ID"]]);
}
function getplayerresetrequests($sessionid) {
  global $mysql;  
  $sid=getsession($sessionid);
  if ($sid!=NULL)
    if (count($sid)>0) {
      $results=$mysql->select("resetplayer",["`s_sess`.`Sessionid`"],"`Resettarget`='".$sid["ID"]."'","INNER JOIN `sessions` as s_sess ON `resetplayer`.`Resetsource` = `s_sess`.`ID`");
      if (count($results)>0) 
        return $results;
    }
  return NULL;
}
function deleteplayerresetrequest($source,$target) {
  global $mysql;
  $sid1=getsession($source);
  $sid2=getsession($target);
  if ($sid1!=NULL && $sid2!=NULL)
    $mysql->delete("resetplayer","`Resetsource`='".$sid1["ID"]."' AND `Resettarget`='".$sid2["ID"]."'");
}

function timelog($text) {
  global $timelog;
  global $starttime;
  $timelog[$text]=(microtime(true)-$starttime);
}

function script_end() {
  global $filesfolder;
  global $starttime;
  global $timelog;
  timelog("EOS");
  if (microtime(true)-$starttime>0.5) {
    $points="[";
    $previous=$timelog["BOS"];
    foreach($timelog as $text=>$time) {      
      if ($text!="EOS" && $text!="BOS" && $time-$previous!=0)
        $points.=$text."=".number_format($time-$previous,4).";";
      $previous=$time;
    }
    if ($points!=="[")
     $points=substr($points,0,strlen($points)-1)."]";
    else
     $points="";
    logmsg(number_format($timelog["EOS"],4)." ".$_SERVER["REQUEST_URI"]." $points");
  }
  if (isset(error_get_last()["message"])) {
    if (startWith(error_get_last()["message"],"Maximum execution time")) {
      logmsg("Server Abort ".(microtime(true)-$starttime).":\n".print_r(["URL"=>$_SERVER["REQUEST_URI"],"HEADERS"=>["REQUEST"=>getallheaders(),"RESPONSE"=>headers_list()],"ENDSCRIPT"=>connection_aborted()?"Connection Aborted":"Normal End","LASTERROR"=>error_get_last()],true));
      //deletefile("$filesfolder/lockdata");
    }  
  }
}
error_reporting(E_ALL ^ E_WARNING);
$starttime= microtime(true);
$timelog=[];
timelog("BOS");
$applicationfolder=substr($_SERVER["SCRIPT_FILENAME"],0,strrpos($_SERVER["SCRIPT_FILENAME"],"/"));
$filesfolder="Z:/files";//$filesfolder="$applicationfolder/files";
$mysql=new database();
timelog("00");

register_shutdown_function("script_end");