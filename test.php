<?php
ini_set('memory_limit',1073741824);
ini_set('max_execution_time', 0);
include 'include.php';

// <editor-fold defaultstate="collapsed" desc="transform hex file from chrome cache into downloadable bytes">
function turnhextextintobytes($filepath) { 
  $data=file_get_contents($filepath);
  $datalines=explode("\n",$data);
  $file="";
  foreach($datalines as $line) {
    $bytes=explode(" ",substr($line,10,47));
    foreach($bytes as $key=>$byte) {
      $file.=chr(hexdec($byte));    
    }
  }
  header("Content-Type: image/gif");
  header("Content-Length: ".strlen($file));
  echo $file;  
}
//turnhextextintobytes("progress.gif");
//</editor-fold>

// <editor-fold defaultstate="collapsed" desc="compare two folders and point out files that differ in name and size">
function dirscan($dirpath,$results,$basepath="") {
  if ($basepath=="") {
    $basepath=$dirpath;
  }
  $dir_handle = @opendir($dirpath) or die;
  while ($file = readdir($dir_handle)) {
    if ($file == "." || $file == "..")
      continue;
    $entry = $dirpath . "/" . $file;
    $relpath=substr($entry,strpos($entry,$basepath)+strlen($basepath));
    if (is_dir($entry)) {
      if (isset($results[$relpath])) {
        $results[$relpath][2]=["base"=>$basepath,"modtime"=>filemtime($basepath.$relpath)];
        $results[$relpath]["same"]="true";          
      } else {
        $results[$relpath]=["type"=>"file","same"=>"false",1=>["base"=>$basepath,"modtime"=>filemtime($basepath.$relpath)]];
      }      
      $results=dirscan($entry,$results,$basepath);
    } else {
      if (isset($results[$relpath])) {
        $results[$relpath][2]=["base"=>$basepath,"modtime"=>filemtime($basepath.$relpath),"size"=>filesize($basepath.$relpath)];        
        if ($results[$relpath][1]["size"]==$results[$relpath][2]["size"])
          $results[$relpath]["same"]="true";
      } else {        
        $results[$relpath]=["type"=>"dir","same"=>"false",1=>["base"=>$basepath,"modtime"=>filemtime($basepath.$relpath),"size"=>filesize($basepath.$relpath)]];
      }      
    }    
  }
  closedir($dir_handle);
  return $results;
}
function comparedirs($dir1,$dir2) {
  $results=[];
  $results=dirscan($dir1,$results);
  $results=dirscan($dir2,$results);
  echo "<pre>";
  $founddiff=false;
  foreach($results as $relpath=>$values) {  
    if ($values["same"]!=="true") {
      $founddiff=true;
      echo $relpath."<BR>";
      print_r($values);
    }
  }
  if (!$founddiff) echo "No differences found";
  echo "</pre>";  
}
//comparedirs("C:/openssh","C:/opensshtmp");
//</editor-fold>

//echo "nothing to send";

