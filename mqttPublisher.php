<?php

include_once("config.php");
include_once("libs/phpMQTT/phpMQTT.php");
include_once("libs/phpMQTT/class.mqttMsgEncDec.php");
include_once("libs/class.RFLedSocket.php");

//----------------------------------------------------------------------------------//

define('LAMPS_TYPE', 'lamps');
define('SENSORS_TYPE', 'sensors');

class IEngineHandler{

      var $ip = '127.0.0.1';
      var $port = 5911;

      public function IEngineHandler($ip = '127.0.0.1', $port = 5911){
          $this->ip = $ip;
          $this->port = $port;
      }

      public function getObjectbyAddress($address, $universe = -1) {}
      public function getObjectbyType($type, $first, $max)    {}
      public function getDatabyDBID($type, $id, $obj)   {}
      public function getDatabyAddress($type, $address, $obj) {}

}

class IRFServerEngineHandler extends IEngineHandler{

      public function IRFServerEngineHandler($ip = '127.0.0.1', $port = 5911){
          parent::IEngineHandler($ip, $port);
      }

      public function getObjectbyAddress($address, $universe = -1){
          $ip = $this->ip;
          $port = $this->port;

          $rf = new RFLedSocket($ip, $port);
          $sql = "select id, type, name, idSensor, idNetwork from Nodes where idSensor = $address order by id asc ";
          $rows = $rf->getRecords($sql);

          $retObj = array();
          foreach ($rows as $value) {
              $retObj = $value;
          }

          return $retObj;
      }

      public function getObjectbyType($type, $first, $max)    {

          $ip = $this->ip;
          $port = $this->port;

          $rf = new RFLedSocket($ip, $port);

          $cond = "where 1 = 1 ";
          if($type == LAMPS_TYPE){
              $cond = $cond . " AND type <> '20' AND type <> '3' AND type <> '10' ";
          }
          else if($type == SENSORS_TYPE){
            $cond = $cond . " AND (type = '3' OR type = '10' OR type = '11')";
          }
          $sql = "select id, type, name, idNetwork from Nodes $cond order by id asc limit $first, $max ";

          $rows = $rf->getRecords($sql);
          $retObj = array();
          foreach ($rows as $value) {
              $retObj[] = $value;
          }
          return $retObj;
      }

      public function getDatabyDBID($type, $id, $obj)   {

        $ip = $this->ip;
        $port = $this->port;

        $rf = new RFLedSocket($ip, $port);
        $resp = $rf->getSlaveNodeStatus($id);
        $ret = array();
        if( count($resp) > 0 ){
            $data = $resp[0];
            if($data['id'] != null){
              if($type == LAMPS_TYPE){
                $ret = array(
                      'id' => $data['id'],
                      'name' => $obj['name'],
                      'address' => $data['idSensor'],
                      'universe' => $obj['idNetwork'],
                      'level' => $data['currLevel'],
                      'lamp_type' => $obj['type'],
                        );
              }
              else if($type == SENSORS_TYPE){

                $lux = $this->getSensorLuxLevel( $data['idSensor'] );

                $ret = array(
                      'id' => $data['id'],
                      'name' => $obj['name'],
                      'address' => $data['idSensor'],
                      'value' => $lux,
                      'type' => 'lux',
                      'unit' => 'lux',
                        );

              }
              else{
                $ret = array(
                      'id' => $data['id'],
                      'name' => $obj['name'],
                      'address' => $data['idSensor'],
                      'universe' => $obj['idNetwork'],
                      'value' => $data['currLevel']
                        );
              }
           }
        }
        return $ret;
      }

      // Legge parametri direttamente dal nodo
      public function getDatabyAddress($type, $address, $obj){

          $ret = array();
          if($type == LAMPS_TYPE){
              $resp =  $this->getLightLevel($address);

              $ret = array(
                    'id' => $obj['id'],
                    'name' => $obj['name'],
                    'address' => $address,
                    'universe' => $obj['idNetwork'],
                    'level' => $resp,
                    'lamp_type' => $obj['type'],
                      );
          }
          if($type == SENSORS_TYPE){
            $lux = $this->getSensorLuxLevel( $address );

            $ret = array(
                  'id' => $obj['id'],
                  'name' => $obj['name'],
                  'address' => $address,
                  'value' => $lux,
                  'type' => 'lux',
                  'unit' => 'lux',
                    );
          }

          return $ret;
      }

      protected function getLightLevel($idSensor, $obj)   {

          $ip = $this->ip;
          $port = $this->port;

          $rf = new RFLedSocket($ip, $port);
          $resp = $rf->getSlaveDAC($idSensor);

          if( count($resp) > 0 ){

              if(isset($resp['output'])){
                  return $resp['state'];
              }
          		else{
          			$resp = $rf->getSlaveDAC($idSensor);
          			if(isset($resp['output'])){
          	        return $resp['state'];
          			}

      			    return -1;
    		    }

          }

          return -1;
      }

      protected function getSensorLuxLevel($idSensor)   {

          $ip = $this->ip;
          $port = $this->port;

          $calibrate = $this->get_calibrate_value($idSensor);

          $rf = new RFLedSocket($ip, $port);
          $resp = $rf->getSlaveAnalogInput($idSensor);
          $ret = array();
          if( count($resp) > 0 ){

              if(isset($resp['input'])){
                  return $this->get_lux_by_calibrate($resp['state'], $calibrate);
              }
          		else{
          			$resp = $rf->getSlaveAnalogInput($idSensor);
          			if(isset($resp['input'])){
          	        return $this->get_lux_by_calibrate($resp['state'], $calibrate);
          			}

      			    return -1;
    		    }

          }

          return -1;
      }

//------------------------------------------------------------------------------------//
      function get_lux_by_calibrate($rawRead, $calibrate){
          $luxRawVal = $calibrate['calibrate']['raw'];
          $luxRealVal = $calibrate['calibrate']['real'];
          $luxRawVal_X0 = $calibrate['calibrateX0Y0']['raw'];
          $luxRealVal_Y0 = $calibrate['calibrateX0Y0']['real'];

          if(($luxRawVal - $luxRawVal_X0) == 0)		$luxRawVal = $luxRawVal_X0 + 1;

          $m_coeff = (($luxRealVal - $luxRealVal_Y0)) / (($luxRawVal - $luxRawVal_X0));
          $q_coeff = $luxRealVal_Y0 - ($m_coeff * $luxRawVal_X0);

          $tmpLux = $m_coeff * $rawRead;
          $lux = round( $tmpLux + $q_coeff );
          if($lux < 0)  $lux = 0;

          return  intval($lux);
      }

      function get_calibrate_value($idSensor){
          $fileCalibr = 'sensor_calibrate.json';
          $jsonCalib = file_get_contents($fileCalibr);
          $calibrate = json_decode($jsonCalib, true);

          $ip = $this->ip;
          $port = $this->port;
          $rf = new RFLedSocket($ip, $port);

          $ret = array(
                'calibrate' => array('raw' => 1, 'real' => 1),
                'calibrateX0Y0' => array('raw' => 0, 'real' => 0),
                );
          if(isset($calibrate[$idSensor]) == false){
                $resp = $rf->getSlaveDLRCalibrate($idSensor);
                if( count($resp) > 0 && isset($resp['input'])){
                    $calibrate[$idSensor]['calibrate']['raw'] = $resp['raw'];
                    $calibrate[$idSensor]['calibrate']['real'] = $resp['real'];

                    // ### Da finire leggere i parametri sul sensore
                    $calibrate[$idSensor]['calibrateX0Y0']['raw'] = 65;
                    $calibrate[$idSensor]['calibrateX0Y0']['real'] = 0;

                    $calibrate[$idSensor]['timestamp'] = strtotime("now");

                    file_put_contents($fileCalibr, json_encode($calibrate));

                    $ret = $calibrate[$idSensor];
                }
          }
          else {
            $ret = $calibrate[$idSensor];
          }

          return $ret;
      }

};

//----------------------------------------------------------------------------------//
class publishEngine{
    var $infoMQTT = array();
    var $qryFirst, $qryMax;
    var $opts = array('first' => 0, 'max' => 100);

    public function publishEngine( $infomqtt, $opts){
        $this->infoMQTT = $infomqtt;
        $this->opts = array_merge($this->opts, $opts);
        $this->setQryLimit($this->opts['first'], $this->opts['max']);
    }

    public function setQryLimit($first, $max){
        $this->qryFirst = $first;
        $this->qryMax = $max;
    }

    public function run(){
        global $systemname, $publisherIp;

        $mqtt = new phpMQTT($this->infoMQTT['host'], $this->infoMQTT['port'], "ClientID".rand());
        $bConnect = $mqtt->connect(true,NULL,$this->infoMQTT['username'],$this->infoMQTT['password']);

        $ip = '127.0.0.1';
        $port = 5911;
        if(isset($this->opts['ip']))    $ip = $this->opts['ip'];
        if(isset($this->opts['port']))  $port = $this->opts['port'];

        $EH = new IRFServerEngineHandler($ip, $port);
        if($bConnect){

            if(isset($this->opts['idSensor'])){
                $this->publish_object($mqtt, $this->opts['idSensor'], $EH, 'lamps');
                $this->publish_object($mqtt, $this->opts['idSensor'], $EH, 'sensors');
            }
            else{
                $this->publish_objects($mqtt, $EH, 'lamps');
                $this->publish_objects($mqtt, $EH, 'sensors');
            }

            $mqtt->close();
        }
    }

    protected function publish_objects($mqtt, $EH, $type){
        global $systemname, $publisherIp;

        $baseTopic = $systemname."/$publisherIp";

        $objects = $EH->getObjectbyType($type, $this->qryFirst, $this->qryMax);

        foreach ($objects as $value) {
            $data = $EH->getDatabyDBID($type, $value['id'], $value);

            if(count($data) > 0){
                $msg = new mqttMsgEncode($data);
                echo $baseTopic."/$type/".$data['id'].' - ';
                $mqtt->publish($baseTopic."/$type/".$data['id'], $msg->getNotifyMsg('obj_update'), 0);
            }

        }

        if(count($objects) == 0)
          echo "No objects ".$type;
    }

    protected function publish_object($mqtt, $idSensor, $EH, $type){
        global $systemname, $publisherIp;

        $baseTopic = $systemname."/$publisherIp";

        $object = $EH->getObjectbyAddress($idSensor);

        if (count($object) > 0) {
            $data = $EH->getDatabyAddress($type, $idSensor, $object);

            if(count($data) > 0){
                $msg = new mqttMsgEncode($data);
                echo $baseTopic."/$type/".$object['id'].' - ';
                $mqtt->publish($baseTopic."/$type/".$object['id'], $msg->getNotifyMsg('obj_update'), 0);
            }

        }

        if(count($object) == 0)
          echo "No objects ".$type;
    }



};


class CLIParameters
{
  var $params = array();
  public function __construct($argc, $argv)
  {
      if($argc != null && $argc > 0){
          $this->parse($argv);
      }
  }

  public function getParams(){
      return $this->params;
  }

  protected function parse($argv){
      if(count($argv) > 1){
            for($i = 1; $i < count($argv); $i++){
                $str = $argv[$i];
                $arr = explode("=", $str);
                if(count($arr) > 1){
                    $this->params[$arr[0]] = $arr[1];
                }

            }

      }
  }

}



$mqttPHE = new publishEngine($MQTTConfig, $_GET);

if(isset($_GET['first']) && isset($_GET['max']))
    $mqttPHE->setQryLimit($_GET['first'], $_GET['max']);


$mqttPHE->run();


 ?>
