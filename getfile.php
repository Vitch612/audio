<?php
include "include.php";

class ResumeDownload {
  private $file;
  private $name;
  private $boundary;
  private $delay = 0;
  private $size = 0;
  private $chunk = 2048;
  private $mime;
  private $path;
  
  function __construct($file, $delay = 0) {
    if (!is_file($file)) {
      header(" HTTP/1.1 400 Invalid Request");
      die("<h3>File Not Found</h3>");
    }
    $this->path = $file;
    $this->size = filesize($file);
    $this->file = fopen($file, "r");
    $this->boundary = md5($file);
    $this->delay = $delay;
    $this->name = basename($file);
    $this->mime = mime_content_type($file);
  }

  public function process() {
    $ranges = NULL;
    $t = 0;
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_SERVER['HTTP_RANGE']) && $range = stristr(trim($_SERVER['HTTP_RANGE']), 'bytes=')) {
      $range = substr($range, 6);
      $ranges = explode(',', $range);
      $t = count($ranges);
    }
    header("Accept-Ranges: bytes");
    header("Content-Type: $this->mime");
    header("Content-Transfer-Encoding: binary");
    if ($this->download)
      header(sprintf('Content-Disposition: attachment; filename="%s"', $this->name));
    if ($t > 0) {
      header("HTTP/1.1 206 Partial content");
      $t === 1 ? $this->pushSingle($range) : $this->pushMulti($ranges);
    } else {
      header("Content-Length: " . $this->size);
      $this->readFile();
    }
    flush();
  }

  private function pushSingle($range) {
    $start = $end = 0;
    $this->getRange($range, $start, $end);
    header("Content-Length: " . ($end - $start + 1));
    header(sprintf("Content-Range: bytes %d-%d/%d", $start, $end, $this->size));
    fseek($this->file, $start);
    $this->readBuffer($end - $start + 1);
    $this->readFile();
  }

  private function pushMulti($ranges) {
    $length = $start = $end = 0;
    $output = "";
    $tl = "Content-type: $this->mime\r\n";
    $formatRange = "Content-range: bytes %d-%d/%d\r\n\r\n";
    foreach ($ranges as $range) {
      $this->getRange($range, $start, $end);
      $length += strlen("\r\n--$this->boundary\r\n");
      $length += strlen($tl);
      $length += strlen(sprintf($formatRange, $start, $end, $this->size));
      $length += $end - $start + 1;
    }
    $length += strlen("\r\n--$this->boundary--\r\n");

    header("Content-Length: $length");
    header("Content-Type: multipart/x-byteranges; boundary=$this->boundary");
    foreach ($ranges as $range) {
      $this->getRange($range, $start, $end);
      echo "\r\n--$this->boundary\r\n";
      echo $tl;
      echo sprintf($formatRange, $start, $end, $this->size);
      fseek($this->file, $start);
      $this->readBuffer($end - $start + 1);
    }
    echo "\r\n--$this->boundary--\r\n";
  }

  private function getRange($range, &$start, &$end) {
    list($start, $end) = explode('-', $range);
    $fileSize = $this->size;
    if ($start == '') {
      $tmp = $end;
      $end = $fileSize - 1;
      $start = $fileSize - $tmp;
      if ($start < 0)
        $start = 0;
    } else {
      if ($end == '' || $end > $fileSize - 1)
        $end = $fileSize - 1;
    }
    if ($start > $end) {
      header("Status: 416 Requested range not satisfiable");
      header("Content-Range: */" . $fileSize);
      exit();
    }
    return array(
      $start,
      $end
    );
  }

  private function readFile() {
    while (!feof($this->file)) {
      echo fread($this->file, $this->chunk);
      flush();
      usleep($this->delay);
    }
  }

  private function readBuffer($bytes) {
    $bytesLeft = $bytes;
    while ($bytesLeft > 0 && !feof($this->file)) {
      $bytesLeft > $this->chunk ? $bytesRead = $this->chunk : $bytesRead = $bytesLeft;
      $bytesLeft -= $bytesRead;
      echo fread($this->file, $bytesRead);
      flush();
      usleep($this->delay);
    }
  }

}

if (!function_exists('mime_content_type')) {
  function mime_content_type($filename) {
    $mime_types = array(
      'txt' => 'text/plain',
      'srt' => 'text/srt',
      'sub' => 'text/plain',
      'vtt' => 'text/vtt',
      'htm' => 'text/html',
      'html' => 'text/html',
      'php' => 'text/html',
      'css' => 'text/css',
      'js' => 'application/javascript',
      'json' => 'application/json',
      'xml' => 'application/xml',
      'swf' => 'application/x-shockwave-flash',
      'flv' => 'video/x-flv',
      // images
      'png' => 'image/png',
      'jpe' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'jpg' => 'image/jpeg',
      'gif' => 'image/gif',
      'bmp' => 'image/bmp',
      'ico' => 'image/vnd.microsoft.icon',
      'tiff' => 'image/tiff',
      'tif' => 'image/tiff',
      'svg' => 'image/svg+xml',
      'svgz' => 'image/svg+xml',
      // archives
      'zip' => 'application/zip',
      'rar' => 'application/x-rar-compressed',
      'exe' => 'application/x-msdownload',
      'msi' => 'application/x-msdownload',
      'cab' => 'application/vnd.ms-cab-compressed',
      // audio/video
      'mp3' => 'audio/mpeg',
      'qt' => 'video/quicktime',
      'mov' => 'video/quicktime',
      'avi' => 'video/avi',
      'mp4' => 'video/mp4',
      'mpeg'=> 'video/mpeg',
      'mid' => 'audio/midi',
      'mod' => 'audio/mod',
      'mkv' => 'video/x-matroska',
      'mpg' => 'audio/mpeg',
      'wma' => 'audio/x-ms-wma',
      'wmv' => 'audio/x-ms-wmv',
      'mp2' => 'audio/mpeg',
      'ogg' => 'audio/ogg',
      'wav' => 'audio/wav',
      'mid' => 'audio/midi',
      'm4a' => 'audio/m4a',
      'rmj' => 'audio/x-pn-realaudio',
      'mpc' => 'audio/x-musepack',

      // adobe
      'pdf' => 'application/pdf',
      'psd' => 'image/vnd.adobe.photoshop',
      'ai' => 'application/postscript',
      'eps' => 'application/postscript',
      'ps' => 'application/postscript',
      // ms office
      'doc' => 'application/msword',
      'rtf' => 'application/rtf',
      'xls' => 'application/vnd.ms-excel',
      'ppt' => 'application/vnd.ms-powerpoint',
      // open office
      'odt' => 'application/vnd.oasis.opendocument.text',
      'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );
    $ext=strtolower(substr($filename,strrpos($filename,'.')+1));
    if (array_key_exists($ext, $mime_types)) {
      return $mime_types[$ext];
    } elseif (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME);
      $mimetype = finfo_file($finfo, $filename);
      finfo_close($finfo);
      return $mimetype;
    } else {
      return 'application/octet-stream';
    }
  }
}

$str=$_SERVER["REQUEST_URI"];
$path = base64_decode(substr($str, strrpos($str, "/") + 1, strrpos($str, ".") - strlen($str)));
$path = str_replace("\\", "/", clean_dirpath($path));
if (check_permission($path)) {
  if (file_exists($path) && is_file($path)) {
    set_time_limit(0);
    $download = new ResumeDownload($path);
    $download->process();
  } else {
    header("HTTP/1.1 400 Invalid Request");
    die("<h3>File Not Found</h3>");
  }
} else {
  header("HTTP/1.1 403 Access Denied");
  die("<h3>Access Denied</h3>");
}