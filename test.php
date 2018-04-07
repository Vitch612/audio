<?php
include 'include.php';

//askforreset("$sessionid","atnoher");




//function logmsg($text) {
//  global $applicationfolder;
//  file_put_contents("$applicationfolder/files/logfile.txt",date("Y-m-d h:i:sa").": ".$text."\n",FILE_APPEND);
//  //putfile("files/logfile.txt",date("Y-m-d h:i:sa").": ".$text."\n","a");
//}
//
//function script_end() {
//  global $starttime;
//  logmsg("Served in ".(microtime(true)-$starttime).":\n".print_r(["URL"=>$_SERVER["REQUEST_URI"],"HEADERS"=>["REQUEST"=>getallheaders(),"RESPONSE"=>headers_list()],"ENDSCRIPT"=>connection_aborted()?"Connection Aborted":"Normal End","LASTERROR"=>error_get_last()],true));
//}
//
//$starttime= microtime(true);
//$applicationfolder=substr($_SERVER["SCRIPT_FILENAME"],0,strrpos($_SERVER["SCRIPT_FILENAME"],"/"));
//register_shutdown_function("script_end");
//


//$filepath="files/logfile.txt";
//$data="test";
//  $fh=fopen($filepath,"w");
//  flock($fh, LOCK_UN);
//  flock($fh, LOCK_EX);
//  fwrite($fh, $data);  
//  flock($fh, LOCK_UN);
//  fclose($fh);

/*
  $filepath="C:/Program Files/Apache24/logs/error.log";
  $fh=fopen($filepath,"r");

  $chunk=10240;
  
  fseek($fh,filesize($filepath)-$chunk);  
  $s=fread($fh,$chunk);
  $s=str_replace("\n","<BR>",$s);
  echo $s;
  fclose($fh);
*/  
  //echo "<hr>".$retval."<hr>";

//while(($fh=fopen("file.webm","r"))===false);
//$fsize=filesize("file.webm");
//$chunk=3;
//$data=fread($fh,4*$chunk);
//for ($i=0;$i<strlen($data);$i++) {
//  echo ord($data[$i])." ";
//}
//echo "<BR>";
//fseek($fh,0);
//$data=fread($fh,$chunk);
//echo ord($data[0])." ".ord($data[strlen($data)-1])."<BR>";
//fseek($fh,$chunk);
//$data=fread($fh,$chunk);
//echo ord($data[0])." ".ord($data[strlen($data)-1])."<BR>";
//fseek($fh,2*$chunk);
//$data=fread($fh,$chunk);
//echo ord($data[0])." ".ord($data[strlen($data)-1])."<BR>";
//fseek($fh,3*$chunk);
//$data=fread($fh,$chunk);
//echo ord($data[0])." ".ord($data[strlen($data)-1])."<BR>";
//fclose($fh);


//for ($i=0;$i<strlen($data);$i++) {
//  echo dechex(ord($data[$i]))." ";
//  //echo decbin(ord($data[$i]))." ";
//}
////echo ord($data[0])." ".ord($data[strlen($data)-1]);
