<?php


	include_once("phpMQTT/phpMQTT.php");
	include_once("libs/class.dbQueries.php");
	include_once("libs/class.mqttMsgEncDec.php");


  $cloudMQTTConfig = array(
					'host'	=> "m21.cloudmqtt.com",  'port' => 11979,
					'username' => "ucqvnetw", 'password' => "ovYAygrGpLW0",
					'topic' => 'dev');
					
  $dbConfig = array( 'host' => 'localhost', 'user' => 'root', 'password' => '', 'database' => 'clight' );

  $mqtt = new phpMQTT($cloudMQTTConfig['host'], $cloudMQTTConfig['port'], "ClientID".rand());

  if(!$mqtt->connect(true,NULL,$cloudMQTTConfig['username'],$cloudMQTTConfig['password'])){
	exit(1);
  }

  //currently subscribed topics
  $topics[$cloudMQTTConfig['topic']] =
	array("qos"=>0, "function"=>"procmsg");
  $mqtt->subscribe($topics,0);

  while($mqtt->proc()){

  }

  $mqtt->close();
  function procmsg($topic,$msg){
		global $dbConfig, $mqtt;

		echo "Msg Recieved: $msg".PHP_EOL;
		$msgInfo = new mqttMsgDecode($msg);

		if($msgInfo->isRequest()){
			$cmdData = $msgInfo->getData();
			$dbHE = new dbQueries($dbConfig);
			$records= array();
			if($cmdData['action'] == 'query')
				$records = $dbHE->getQry($cmdData['sql']);

		//	var_dump($records);
			if(count($records) > 0){
				foreach($records as $item){
					$msg = new mqttMsgEncode($item);
					$mqtt->publish($topic, $msg->getNotifyMsg($cmdData['action'], $msgInfo->getSession()), 0 );

					sleep(1);
				}
			}
			else{
				$msg = new mqttMsgEncode(array());
				$mqtt->publish($topic, $msg->getNotifyMsg($cmdData['action'], $msgInfo->getSession()), 0 );
			}

		}

  }
  echo "//--------------------------------------------------------------------------------//".PHP_EOL;

?>
