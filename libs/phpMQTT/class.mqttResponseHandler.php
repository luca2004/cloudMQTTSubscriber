<?php

include_once("libs/phpMQTT/class.mqttNotifyHandler.php");
include_once("libs/class.dbQueries.php");

class mqttResponseHandler extends mqttNotifyHandler{

  public function mqttResponseHandler($msgInfo, $dbConfig){
      parent::mqttNotifyHandler($msgInfo, $dbConfig);
  }

  //---------------------------------------------------------//
  // Implementazione function di action  
  protected function rfserver_update_node($cmdData){

	  return parent::obj_update($cmdData);	
	  
  }



};



 ?>
