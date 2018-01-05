<?php

	include_once("config.php");
	include_once("libs/phpMQTT/phpMQTT.php");
	include_once("libs/phpMQTT/class.mqttRequestHandler.php");
	include_once("libs/phpMQTT/class.mqttNotifyHandler.php");

	define('FILE_LOG', 'mqttSub.log');
	define('FILE_LOG_ENABLE', 1);

  $mqtt = new phpMQTT($MQTTConfig['host'], $MQTTConfig['port'], "ClientID".rand());
	$mqtt->debug = $MQTTConfig['debug'];
  if(!$mqtt->connect(true,NULL,$MQTTConfig['username'],$MQTTConfig['password'])){
		exit(1);
  }

  //currently subscribed topics

  $topics[$MQTTConfig['topic']] =
			array("qos"=>0, "function"=>"procmsg");
  $mqtt->subscribe($topics,0);

  while($mqtt->proc()){

  }

  $mqtt->close();

	function logging($msg){
			if(FILE_LOG_ENABLE == 0)
					return;

			$log = date('Y-m-d H:i:s ').$msg;
			file_put_contents(FILE_LOG, $log, FILE_APPEND);
	}

  function procmsg($topic,$msg){
		global $dbConfig, $mqtt, $responseSleepSec;

		echo "Topic Recieved: $topic".PHP_EOL;
		echo "Msg Recieved: $msg".PHP_EOL;
		$msgInfo = new mqttMsgDecode($topic, $msg);

		if($msgInfo->isRequest()){
			$RH = new mqttRequestHandler($msgInfo, $dbConfig);
			$response = $RH->exec();

			if(count($response) > 0){
				foreach($response as $item){
					$msg = new mqttMsgEncode($item);
					$mqtt->publish($topic, $msg->getResponseMsg($msgInfo->getAction(), $msgInfo->getSession()), 0 );

					if($responseSleepSec > 0)
						sleep($responseSleepSec);
				}
			}
			else{
				echo "No response: $RH->lastError".PHP_EOL;
			}
		}
		else if($msgInfo->isNotify()){

			  logging($topic.' - '.$msg);

				$NH = new mqttNotifyHandler($msgInfo, $dbConfig);
				$response = $NH->exec();

				if(count($response) == 0){
						echo "No notify: $NH->lastError".PHP_EOL;
				}
		}
		else{
			/*$msg = new mqttMsgEncode(array());
			$mqtt->publish($topic, $msg->getNotifyMsg($cmdData['action'], $msgInfo->getSession()), 0 );*/
		}



  }
  echo "//--------------------------------------------------------------------------------//".PHP_EOL;

?>
