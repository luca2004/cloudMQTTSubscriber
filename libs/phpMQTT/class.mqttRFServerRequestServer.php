<?php

include_once("libs/phpMQTT/class.mqttHandler.php");
include_once("libs/class.RFLedSocket.php");

class mqttRFServerRequestHandler extends mqttHandler{

  public function mqttRFServerRequestHandler($msgInfo, $dbConfig){
      parent::mqttHandler($msgInfo, $dbConfig);
  }

  //---------------------------------------------------------------------------------------//
  //   Metodo per aggiornamento nodi da DB
  //        address: id node
  //        ip:     ip address server [facoltativo]
  //        port:   port socket	server [facoltativo] 
  protected function update_node($cmdData){
	  $ret = array();	
  
      $ip = isset($cmdData['port']) ? $cmdData['ip'] : '127.0.0.1';
      $port = isset($cmdData['port']) ? $cmdData['port'] : 5911;
      $rf = new RFLedSocket($ip, $port);

	  $info = $rf->execSQL("select * from nodes where idSensor = ".$cmdData['address']);

	  if(isset($info['result']) && $info['result'] == 'NOK')
		return $ret;
	  if(count($info) > 0)
			$info = $info[0];
	  
      $resp = $rf->getSlaveDAC($cmdData['address']);
      $currlevel = -1;
      if( count($resp) > 0 ){
          if(isset($resp['output'])){
              $currlevel = $resp['state'];
          }
          else{
            $resp = $rf->getSlaveDAC($idSensor);
            if(isset($resp['output'])){
                $currlevel = $resp['state'];
            }
        }
      }

	  
      $ret[] = array(
			'id' => $info['id'],
            'name' => $info['name'],
            'address' => $cmdData['address'],
			'universe' => $info['idNetwork'],
            'level' => $currlevel,
              );

      return $ret;
  }

}