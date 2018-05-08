<?php
class database {
  public $conn=NULL;
  public $error;
  public $insert_id;

  function __construct() {        
  }

  public function isConnected() {
    if ($this->conn===NULL)
      $this->conn=new mysqli("localhost", "audiouser", "password", "audio");
    unset($this->error);
    if ($this->conn->connect_error) {
      return false;
    } else {
      $this->error=$this->conn->error;
      return true;
    }
  }

  public function update($table,$values,$condition) {
    if ($this->conn===NULL)
      $this->conn=new mysqli("localhost", "audiouser", "password", "audio");
    $sql="UPDATE `$table` SET ";
    foreach ($values as $index => $value) {
      if (isset($value)) {
        $sql.="`" . $this->conn->real_escape_string($index) . "`='" . $this->conn->real_escape_string($value) . "',";
      } else {
        $sql.="`" . $this->conn->real_escape_string($index) . "`=NULL,";
      }
    }
    $sql = substr($sql, 0, strlen($sql) - 1);
    $sql.=" WHERE ".$condition;
    unset($this->error);
    $result=$this->conn->query($sql);
    if ($result) {
      return true;
    } else {
      $this->error=$this->conn->error;
      return false;
    }
  }
  
  public function insert($table,$values) {
    if ($this->conn===NULL)
      $this->conn=new mysqli("localhost", "audiouser", "password", "audio");
    $sql="INSERT INTO `$table` (";
    if (count($values)>0) {
      foreach ($values as $index => $value) {
        $sql.="`" . $this->conn->real_escape_string($index) . "`,";
      }
      $sql = substr($sql, 0, strlen($sql) - 1) . ") VALUES (";
      foreach ($values as $value) {
        if (isset($value)) {
          $sql.="'" . $this->conn->real_escape_string($value) . "',";
        } else {
          $sql.="NULL,";
        }
      }
      $sql=substr($sql,0,strlen($sql)-1).")";
    } else {
      $sql.=') VALUES ()';
    }
    unset($this->error);
    $result=$this->conn->query($sql);
    $this->insert_id=$this->conn->insert_id;
    if ($result) {
      return true;
    } else {
      $this->error=$this->conn->error;
      return false;
    }
  }

  public function delete($table,$condition="") {
    if ($this->conn===NULL)
      $this->conn=new mysqli("localhost", "audiouser", "password", "audio");
    $sql="DELETE FROM $table".($condition!=""?" WHERE $condition":"");
    unset($this->error);
    $result=$this->conn->query($sql);
    if ($result) {
      return true;
    } else {
      $this->error=$this->conn->error;
      return false;
    }
  }

  public function select($table,$values,$condition="",$join="",$distinct=NULL) {
    if ($this->conn===NULL)
      $this->conn=new mysqli("localhost", "audiouser", "password", "audio");
    $sql="SELECT ";
    if ($distinct)
      $sql.="DISTINCT ";
    foreach ($values as $value) {
        $sql.=$this->conn->real_escape_string($value).",";
    }
    $sql=substr($sql,0,strlen($sql)-1);
    $sql.=" FROM `$table` $join".($condition!=""?" WHERE $condition":"");
    unset($this->error);
    $result=$this->conn->query($sql);
    $data=array();
    if ($result) {
      if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
          $data[]=$row;
        }
      }
    } else {
      logmsg("SQL ERROR: ".$this->conn->error." Query=".$sql);
      $this->error=$this->conn->error;
    }
    return $data;
  }
}