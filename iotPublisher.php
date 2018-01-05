<?php

include_once("config.php");
include_once("libs/phpMQTT/phpMQTT.php");
include_once("libs/phpMQTT/class.mqttMsgEncDec.php");


//----------------------------------------------------------------------------------//
class publishEngine{
    var $infoMQTT = array();
    var $qryFirst, $qryMax;
    var $opts = array('first' => 0, 'max' => 100, 'device_type' => 'rfserver', 'device_id' => 'rf001');

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

		$clientid = 'd:'.$this->infoMQTT['org_id'].':'.$this->opts['device_type'].':'.$this->opts['device_id'];
        $mqtt = new phpMQTT($this->infoMQTT['host'], $this->infoMQTT['port'], $clientid);
        $bConnect = $mqtt->connect(true,NULL,$this->infoMQTT['username'],$this->infoMQTT['password']);


        if($bConnect){

            $baseTopic = "iot-2/evt/level/fmt/json";
			$level = 40; $lux = 0;
			if(!empty($this->opts['level']))				$level = $this->opts['level'];
			if(!empty($this->opts['lux']))					$lux = $this->opts['lux'];
			$now = date('Y-m-d H:i:s');	
			$msg = '{"ts":"'.$now.'","d":{"light":'.$level.', "lux": '.$lux.'}}';
			$mqtt->publish($baseTopic, $msg, 0);

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

$params = $_GET;
if(isset($argv) && count($argv) > 0){
	$cli = new CLIParameters($argc, $argv);
	$params = $cli->getParams();
}

$mqttPHE = new publishEngine($MQTTConfig, $params);

if(isset($params['first']) && isset($params['max']))
    $mqttPHE->setQryLimit($params['first'], $params['max']);


$mqttPHE->run();

?>