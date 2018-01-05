<?php

include_once("libs/phpMQTT/class.mqttMsgEncDec.php");

class mqttHandler{
  var $msgDec = null;
  var $dbConfig = null;
  var $lastError = "";

  public function mqttHandler($msgInfo, $dbConfig){
      $this->msgDec = $msgInfo;
      $this->dbConfig = $dbConfig;
  }

  public function exec(){
      if($this->msgDec == null){
        $this->lastError = "Msg Info not correct";
        return array();
      }

      $cmdAction = $this->msgDec->getAction();  
      if($cmdAction === ''){
          $this->lastError = "action key not found into Msg Info";
          return array();
      }

      $cmdData = $this->msgDec->getData();
      if(method_exists($this, $cmdAction))
        return $this->$cmdAction($cmdData);

      $this->lastError = "action method '".$cmdAction."' not exist";
      return array();
  }

};

 ?>
