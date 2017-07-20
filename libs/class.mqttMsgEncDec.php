<?php

define('MQTT_CMD_NOTIFY', 'notify');
define('MQTT_CMD_REQUEST', 'request');

	class mqttMsgEncode{
		var $data = array();
		
		public function mqttMsgEncode($data){
			$this->data = $data;
		}		
		
		public function getNotifyMsg($action, $session = ''){
			$ret = array( 'cmd'	=> MQTT_CMD_NOTIFY, 'action' => $action, 'session' => $session, 'data' => $this->data );
			return json_encode( $ret );
		}
	};
	
	
	class mqttMsgDecode{
		var $msgInfo = array();
		
		public function mqttMsgDecode($msg){
			$this->msgInfo = json_decode($msg, true);
			
		}
		
		public function isNotify()	{	
			if(isset($this->msgInfo['cmd']) && $this->msgInfo['cmd'] == MQTT_CMD_NOTIFY)
				return true;
			return false;
		}
		public function isRequest()	{	
			if(isset($this->msgInfo['cmd']) && $this->msgInfo['cmd'] == MQTT_CMD_REQUEST)
				return true;
			return false;
		}
		
		public function getSession(){
			if(isset($this->msgInfo['session']))	return $this->msgInfo['session'];
			return '';
		}
		public function getData(){
			if(isset($this->msgInfo['data']))	return $this->msgInfo['data'];
			return array();
		}
		
	};

?>