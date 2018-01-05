<?php
include_once ('libs/jsonwrapper/jsonwrapper.php');

define('MQTT_CMD_NOTIFY', 'notify');
define('MQTT_CMD_REQUEST', 'request');
define('MQTT_CMD_RESPONSE', 'response');

	class mqttMsgEncode{
		var $data = array();

		public function mqttMsgEncode($data){
			$this->data = $data;
		}

		public function getNotifyMsg($action, $session = ''){
			$ret = array( 'cmd'	=> MQTT_CMD_NOTIFY, 'action' => $action, 'session' => $session, 'data' => $this->data );
			return json_encode( $ret );
		}
		public function getResponseMsg($action, $session = ''){
			$ret = array( 'cmd'	=> MQTT_CMD_RESPONSE, 'action' => $action, 'session' => $session, 'data' => $this->data );
			return json_encode( $ret );
		}
	};


	class mqttMsgDecode{
		var $msgInfo = array();
		var $topicPath = array();

		public function mqttMsgDecode($topic, $msg){
			$this->msgInfo = json_decode($msg, true);
			$this->topicPath = explode('/', $topic);
		}

//--- Message Decode ----//
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
		public function isResponse()	{
			if(isset($this->msgInfo['cmd']) && $this->msgInfo['cmd'] == MQTT_CMD_RESPONSE)
				return true;
			return false;
		}

		public function getSession(){
			if(isset($this->msgInfo['session']))	return $this->msgInfo['session'];
			return '';
		}
		public function getInterface(){
			if(isset($this->msgInfo['interface']))	return $this->msgInfo['interface'];
			return '';
		}
		public function getAction(){
			if(isset($this->msgInfo['action']))	return $this->msgInfo['action'];
			return '';
		}
		public function getData(){
			if(isset($this->msgInfo['data']))	return $this->msgInfo['data'];
			return array();
		}

//--- Topic Decode ----//
		public function getSystem(){
				if( count( $this->topicPath ) >= 1){
					$val = $this->topicPath[0];
					return $val;
				}
				return '';
		}

	  public function getDeviceIP(){
				if( count( $this->topicPath ) >= 2){
					$val = $this->topicPath[1];
					if( strpos($val, ':') > 0 ){
							$p = explode(':', $val);
							return $p[0];
					}
					return $val;
				}
				return '';
		}

		public function getObjectType(){
				if( count( $this->topicPath ) >= 3){
					$val = $this->topicPath[2];
					return $val;
				}
				return '';
		}

		public function getObjectID(){
				if( count( $this->topicPath ) >= 4){
					$val = $this->topicPath[3];
					return intval($val);
				}
				return 0;
		}

	};

?>
