<?php

$systemname = 'dev';

/*  Credenziali cloudMQTT service [https://api.cloudmqtt.com/console/9695140/details] */
$cloudMQTTConfig = array(
        'host'	=> "m21.cloudmqtt.com",  'port' => 11979,
        'username' => "ucqvnetw", 'password' => "ovYAygrGpLW0",
        'topic' => $systemname.'/#', 'debug' => false);

		
/*  Configurazione IBM IOT Watson */
$IBMIOTMQTTConfig = array(
		'org_id' => 'y5pu6p', 
        'host'	=> "y5pu6p.messaging.internetofthings.ibmcloud.com",  'port' => 1883,
        'username' => "use-token-auth", 'password' => "xtFhW7HKsMDU2FT-VM",
        'topic' => $systemname.'/#', 'debug' => false);		
		
// Configurazione mqtt per comunicazione interna al sistema */
$localMQTTConfig = array( 'host'	=> "localhost",  'port' => 1883,
                          'username' => "", 'password' => "",
                          'topic' => $systemname.'/#', 'debug' => false);

$dbConfig = array( 'host' => 'localhost', 'user' => 'root', 'password' => '', 'database' => 'clight_malpensa' );

$publisherIp = "192.168.100.100";

$responseSleepSec = 0;
$MQTTConfig = $IBMIOTMQTTConfig;
 ?>
