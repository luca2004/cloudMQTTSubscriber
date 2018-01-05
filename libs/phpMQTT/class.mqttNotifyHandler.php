<?php

include_once("libs/phpMQTT/class.mqttHandler.php");
include_once("libs/class.dbQueries.php");

class mqttNotifyHandler extends mqttHandler{

  public function mqttNotifyHandler($msgInfo, $dbConfig){
      parent::mqttHandler($msgInfo, $dbConfig);
  }

  //---------------------------------------------------------//
  // Implementazione function di action
  protected function obj_update($cmdData){

      $ip = $this->msgDec->getDeviceIP();
      $objtype = $this->msgDec->getObjectType();

      //echo $ip.' - '.$objtype;
      $bRet = false;
      if($objtype == 'lamps'){
          $bRet = $this->lamp_update($ip, $cmdData);
      }
      if($objtype == 'sensors'){
          $bRet = $this->sensor_update($ip, $cmdData);
      }

      return array('action' => $this->msgDec->getAction(), 'result' => $bRet ? 'OK' : 'KO');
  }



  //-------------------------------------------------------------------------------------------//
  //--- Bisogna derivare per update custom  ----//
  protected function lamp_update($deviceIP, $cmdData){
      $dbHE = new dbQueries($this->dbConfig);

      $devices = $dbHE->selectQry("select id from devices where ip = '".$deviceIP."'");

      if(count($devices) > 0){
          $device_id = $devices[0]['id'];

          $addr = $cmdData['address'];
          $univ = $cmdData['universe'];
          // Verifico se presente la lampada
          $lamp = $dbHE->selectQry("select id from lamps where address = $addr AND universe = $univ AND device_id = $device_id");

          if(count($lamp) > 0){
            $values = $this->my_array_slice($cmdData, 'id');
            if(isset($values['level']) && $values['level'] >= 0)
				        $values['level'] = intval($values['level'] / 255 * 100);
            $dbHE->updateQry('lamps', $values, " id = ".$lamp[0]['id']);

          }
          else{
              $objVals = array('type' => 'lamp' );
              $objid = $dbHE->insertQry('objects', $objVals);
              if($objid > 0){
                  $values = $this->my_array_slice($cmdData, 'id');
                  $values['object_id'] = $objid;
                  $values['device_id'] = $device_id;
                  if(isset($values['level']) && $values['level'] >= 0)
			                $values['level'] = intval($values['level'] / 255 * 100);
                  $lampid = $dbHE->insertQry('lamps', $values);
                  echo 'Inserita lamps con id: '.$lampid;
              }
              else{
                  echo 'Fallito inserimento in object table';
                  return false;
              }

          }

      }

      return true;
  }

  protected function sensor_update($deviceIP, $cmdData){
      $dbHE = new dbQueries($this->dbConfig);

      $devices = $dbHE->selectQry("select id from devices where ip = '".$deviceIP."'");

      if(count($devices) > 0){
          $device_id = $devices[0]['id'];

          $addr = $cmdData['address'];
          // Verifico se presente la lampada
          $sensor = $dbHE->selectQry("select id from sensors where address = $addr AND device_id = $device_id");

          if(count($sensor) > 0){
            $values = $this->my_array_slice($cmdData, 'id');
            $dbHE->updateQry('sensors', $values, " id = ".$sensor[0]['id']);
            return true;
          }
          else{
              $objVals = array('type' => 'sensor' );
              $objid = $dbHE->insertQry('objects', $objVals);
              if($objid > 0){
                  $values = $this->my_array_slice($cmdData, 'id');
                  $values['object_id'] = $objid;
                  $values['device_id'] = $device_id;
                  $lampid = $dbHE->insertQry('sensors', $values);
                  echo 'Inserita sensors con id: '.$lampid;
              }
              else{
                  echo 'Fallito inserimento in object table';
              }

          }

      }

      return false;
  }


  private function my_array_slice($arr, $key){
      $ret = array();
      foreach ($arr as $k => $v) {
          if($k != $key)
            $ret[$k] = $v;
      }
      return $ret;
  }

};



 ?>
