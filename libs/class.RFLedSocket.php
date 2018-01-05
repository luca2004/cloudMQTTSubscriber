<?php


include_once ('class.socketclient.php');
include_once ('jsonwrapper/jsonwrapper.php');
/**
 * Classe per la comunicazione socket con l'engine RFLed
 *
 * @author  luca.caflisch@gmail.com
 * @date    June 2013
 */


class RFledMessage{
    protected $msgString = array(
                        'master_version'                => array('cmd' => 'modbusmsg', 'data' => array( '0', '3', '4097', '1' )),
                        'master_network_info'           => array('cmd' => 'modbusmsg', 'data' => array( '0', '3', '2304', '2' )),
                        'master_network_id_write'       => array('cmd' => 'modbusmsg', 'data' => array( '0', '16', '2304', '1', '2', '%value%' )),
                        'master_hop_write'              => array('cmd' => 'modbusmsg', 'data' => array( '0', '16', '2305', '1', '2', '%value%' )),
                        'slave_type'                    => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4096', '1' )),
                        'slave_version'                 => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4097', '1' )),
                        'slave_network_info'            => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '2304', '3' )),
                        'slave_all_status'              => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4098', '3' )),
                        'slave_dig_input_status'        => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4098', '1' )),
                        'slave_anal_input_status'       => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4099', '1' )),
                        'slave_dlr_calibrate'           => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4362', '2' )),
                        'slave_dlr_calibrate_x0_y0'     => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4371', '2' )),
                        'slave_dig_output_status'       => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4100', '1' )),
                        'slave_dig_output_write'        => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '16', '4100', '1', '2', '%value%' )),
                        'slave_dac_status'              => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4352', '1' )),
                        'slave_dac_write'               => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '16', '4352', '1', '2', '%value%' )),
                        'slave_get_groupid'            => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '2306', '1' )),
                        'slave_set_groupid'            => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '16', '2306', '1', '2', '%value%' )),
                        'slave_all_light_info'         => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4352', '6' )),
                        'slave_all_sensor_info'         => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '3', '4608', '4' )),


                        'slave_lightmode_write'       => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '16', '4353', '1', '2', '%value%' )),
                        'slave_timeoutoff_write'      => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '16', '4354', '1', '2', '%value%' )),
                        'slave_activebcklevel_write'  => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '16', '4355', '1', '2', '%value%' )),
                        'slave_fadeinfadeout_write'  => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '16', '4356', '1', '2', '%value%' )),
                        'slave_poweronkeepalive_write'  => array('cmd' => 'modbusmsg', 'data' => array( '%id%', '16', '4357', '1', '2', '%value%' )),

                );

    public function __construct() {

    }

    public function getTXMessage($name, $id, $value){
        if(isset($this->msgString) == false)
            return '';
        $msg = $this->msgString[$name];
        $cmd = $msg['cmd'];
        $msgArray = $msg['data'];

        $msgArray = str_replace(array('%id%', '%value%'), array($id, $value), $msgArray);

        return $this->build_tx_msg($cmd, $msgArray);
    }

    public function parseRXMessage($response, &$retMsg){
        $retMsg = explode(';', $response);

        if(count($retMsg) == 0)            return false;

        return $retMsg[0] == 'OK';
    }

    protected function build_tx_msg($cmd, $msgArray){

        $txt = "cmd=".$cmd."&msg=".implode(';', $msgArray);
        return $txt;
    }


}


class RFLedSocket extends SocketClient {
    protected $ipAddress = "127.0.0.1";
    protected $socketPort = 5911;
    protected $sndTimeout = array("sec" => 3, "usec" => 0);
    protected $rcvTimeout = array("sec" => 3, "usec" => 0);
    protected $pingTimeout = 2;


    public function __construct($address = "", $port ="") {
        if(!empty($address))    $this->ipAddress = $address;
        if(!empty($port))       $this->socketPort = $port;

        parent::__construct();

        $this->verify_connect();
    }

    public function __destruct() {
        parent::__destruct();
    }


    /* Comandi per lettura / scrittura da modbus */

    public function getMasterVersion(){
        $ret = array( 'firmware' => 0, 'radioversion' => 0, 'modbusversion' =>  0);
        if($this->verify_connect() == false)            return $ret;

        $msgprotocol = new RFledMessage();
        if($this->write( $msgprotocol->getTXMessage('master_version', '', '')  )  == false)
            return $ret;

        $resp = $this->read();
        if($resp != ''){
            $rxMsg = array();
            if($msgprotocol->parseRXMessage($resp, $rxMsg)){
                $res = $rxMsg[5];
                $versFirm = ($res & 255);
                $beta = '';
                if($versFirm > 127){
                    $versFirm = ($res & 127);
                    $beta = " beta";
                }
                $ret = array( 'firmware' => $versFirm, 'radioversion' => ($res >> 8) & 255, 'modbusversion' =>  0, 'betaversion' => $beta);

            }
        }

        return $ret;
    }

    public function getMasterInfoRete(){
        $ret = array( 'networkaddress' => -1, 'radiohop' => -1);
        if($this->verify_connect() == false)            return $ret;

        $msgprotocol = new RFledMessage();
        if($this->write( $msgprotocol->getTXMessage('master_network_info', '', '')  )  == false)
            return $ret;

        $resp = $this->read();
        if($resp != ''){
            $rxMsg = array();
            if($msgprotocol->parseRXMessage($resp, $rxMsg)){
                $res = $rxMsg[5];
                $ret = array(   'networkaddress' => $rxMsg[5],
                                'radiohop'  => $rxMsg[6],
                         );
            }
        }

        return $ret;
    }

    public function setMasterNetworkID($networkid){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('master_network_id_write', 0, $networkid);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'networkid', 'value' => $networkid);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    public function setMasterHop($hop){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('master_hop_write', 0, $hop);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'hop', 'value' => $hop);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }


    public function getSlaveVersion($id){
        $ret = array();

        $rxMsg = $this->send_receive_slave_message('slave_version', $id, '');
        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $versFirm = ($res & 255);
                if($versFirm > 127)     $versFirm = ($res & 127)." beta";
                $ret = array( 'firmware' => $versFirm, 'radioversion' => ($res >> 8) & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }

    public function getSlaveInfoRete($id){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_network_info', $id, '');

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];

                $ret = array(   'networkaddress' => $rxMsg[5],
                                'groupselect'      => $rxMsg[6] == 1 ? 'dipswitch' : 'software',
                                'groupid'  => $rxMsg[7],
                         );
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }

    public function getSlaveAllStatus($id){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_all_status', $id, '');

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];

                $ret = array(   'digital' => $rxMsg[5],
                                'analog'  => $rxMsg[6],
                                'output'  => $rxMsg[7],
                                //'dac'     => $rxMsg[8],
                         );
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }

    public function getSlaveDigitalInput($id, $inputID){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_dig_input_status', $id, '');

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $state = ($res >> $inputID) & 1;
                $ret = array( 'input' => 'digital', 'inputID' => $inputID, 'state' => $state);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }

    public function getSlaveAnalogInput($id){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_anal_input_status', $id, '');

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];

                $ret = array( 'input' => 'analog', 'state' => $res & 1023);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }

    public function getSlaveDigitalOutput($id, $outpuID = 0){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_dig_output_status', $id, '');

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $state = ($res >> $outpuID) & 1;
                $ret = array( 'output' => 'digital', 'outputID' => $outpuID, 'state' => $state);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }

    public function getSlaveDAC($id){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_dac_status', $id, '');

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'dac', 'state' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }

    public function getSlaveAllLightInfo($id){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_all_light_info', $id, '');

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];

                $ret = array(   'level'             => $rxMsg[5],
                                'mode'              => $rxMsg[6],
                                'timeoutoff'        => $rxMsg[7],
                                'backgroundlevel'   => ($rxMsg[8] & 255),
                                'activelevel'       => ($rxMsg[8] >> 8) & 255,
                                'fadein'            => ($rxMsg[9] & 255),
                                'fadeout'           => ($rxMsg[9] >> 8) & 255,
                                'poweronlevel'      => ($rxMsg[10] & 255),
                                'keepalive'         => ($rxMsg[10] >> 8) & 255,
                                //'dac'     => $rxMsg[8],
                         );
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }

    public function getSlaveAllSensorInfo($id){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_all_sensor_info', $id, '');

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];

                $ret = array(   'automode'          => $rxMsg[5],
                                'motionpolling'     => $rxMsg[6],
                                'timeoutoff'        => $rxMsg[7],
                                'activemsg'         => ($rxMsg[8] & 255),
                                'motionhop'         => ($rxMsg[8] >> 8) & 255,
                                //'dac'     => $rxMsg[8],
                         );
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }

    //--------------------------------------------------------------------------------------------------//
    public function setSlaveDAC($id, $value){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_dac_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'dac', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    public function setSlaveDigitalOutput($id, $value){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_dig_output_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'digital_output', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    public function setSlaveTimeoutOff($id, $value){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_timeoutoff_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'timeoutoff', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    public function setSlaveLightMode($id, $value) {
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_lightmode_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'lightmode', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    public function setSlaveFade($id, $fadein, $fadeout) {
        $ret = array();
        $value = ($fadeout << 8) | $fadein;
        $rxMsg = $this->send_receive_slave_message('slave_fadeinfadeout_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'fadeinfadeout', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    public function setSlaveActiveBckgroundLevel($id, $action, $bckground) {
        $ret = array();
        $value = ($action << 8) | $bckground;
        $rxMsg = $this->send_receive_slave_message('slave_activebcklevel_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'activebcklevel', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }


    //--------------------------------------------------------------------------------------------------//
    public function getSlaveNodeStatus($id){
        $ret = array( 'result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;

        //cmd=statusmsg&object=node&objectdblist=1
        $msg = "cmd=statusmsg&object=node&objectdblist=".$id;
        if($this->write( $msg  )  == false)            return $ret;
        $resp = $this->read();
        return json_decode($resp, true);
    }

    public function getSlaveNodeListStatus($ids){
        $ret = array('result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;

        //cmd=statusmsg&object=node&objectdblist=1;2;3;5

        $idsArray = explode(';', $ids);
        $ret = array(); $nbNode = 0; $nbNodeLoop = 20;
        while($nbNode < count($idsArray)){
            $newIds = array();
            for($i = 0; $i < $nbNodeLoop; $i++)  $newIds[] = $idsArray[$nbNode + $i];
            $msg = "cmd=statusmsg&object=node&objectdblist=".implode(';', $newIds);
            if($this->write( $msg  )  == false)            return $ret;
            $resp = $this->read();
            $ret = array_merge($ret, json_decode($resp, true));
            $nbNode = $nbNode + $nbNodeLoop;
        }
        return $ret;

    }


    public function setSlaveGroupID($id, $value){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_set_groupid', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'groupid', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    public function getSlaveGroupID($id){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_get_groupid', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'dac', 'state' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    // Metodi per comandi per gruppi (multicast)
    protected function get_multicast_id($groupid){
        // 0XFFFF000  ==> 4294901760
        $id = (4294901760) |  $groupid;  //(1 << ($groupid - 1));
        return $id;
    }


    public function setSlaveGroupDigitalOutput($groupid, $value){
        $ret = array();
        if($groupid == 0)   $groupid = 1;
        $rxMsg = $this->send_receive_slave_message('slave_dig_output_write', $this->get_multicast_id($groupid), $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'groupid_digital_output', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    public function setSlaveGroupLevel($groupid, $value){
        $ret = array();
        if($groupid == 0)   $groupid = 1;

        $id = $this->get_multicast_id($groupid);
        $rxMsg = $this->send_receive_slave_message('slave_dac_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'groupid_level', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    public function setSlaveGroupTimeoutOff($groupid, $value){
        $ret = array();
        if($groupid == 0)   $groupid = 1;

        $id = $this->get_multicast_id($groupid);
        $rxMsg = $this->send_receive_slave_message('slave_timeoutoff_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'groupid_level', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }
    public function setSlaveGroupLightMode($id, $value) {
        $ret = array();
        if($groupid == 0)   $groupid = 1;

        $id = $this->get_multicast_id($groupid);
        $rxMsg = $this->send_receive_slave_message('slave_lightmode_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'lightmode', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }
    public function setSlaveGroupFade($id, $fadein, $fadeout) {
        $ret = array();
        if($groupid == 0)   $groupid = 1;

        $id = $this->get_multicast_id($groupid);
        $value = ($fadeout << 8) | $fadein;
        $rxMsg = $this->send_receive_slave_message('slave_fadeinfadeout_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'fadeinfadeout', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }
    public function setSlaveGroupActiveBckgroundLevel($id, $action, $bckground) {
        $ret = array();
        if($groupid == 0)   $groupid = 1;

        $id = $this->get_multicast_id($groupid);
        $value = ($action << 8) | $bckground;
        $rxMsg = $this->send_receive_slave_message('slave_activebcklevel_write', $id, $value);

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];
                $ret = array( 'output' => 'activebcklevel', 'set' => $res & 255);
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'errorstring' => '');
            }
        }
        return $ret;
    }

    public function getSlaveDLRCalibrate($id){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_dlr_calibrate', $id, '');

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];

                $ret = array(
                    'input' => 'calibrate',
                    'raw' => $rxMsg[5],
                    'real' => $rxMsg[6]
                  );
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }

    public function getSlaveDLRCalibrateX0Y0($id){
        $ret = array();
        $rxMsg = $this->send_receive_slave_message('slave_dlr_calibrate_x0_y0', $id, '');

        if(count($rxMsg) > 0){
            if($rxMsg[0] == 'OK'){
                $res = $rxMsg[5];

                $ret = array(
                    'input' => 'calibrate_x0y0',
                    'rawX0' => $rxMsg[5],
                    'rawY0' => $rxMsg[6]
                  );
            }
            else{
                $ret = array( 'error' => $rxMsg[3], 'string' => '');
            }
        }
        return $ret;
    }


    //--------------------------------------------------------------------------------------------------//
    /* Ricerca nodi RF
     *
     * $mode:   1: nodi non associati
     *          2: nodi associati
     *          3. tutti i nodi
     */
    public function StartScanSlaves($mode){
        $ret = array('result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;

        $msg = "cmd=modbusmsg&msg=0;65;".$mode;
        if($this->write( $msg  )  == false)            return $ret;
        $resp = $this->read();

        return $resp;
    }

    public function StopScanSlaves(){
        $ret = array('result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;

        $msg = "cmd=modbusmsg&msg=0;65;255";
        if($this->write( $msg  )  == false)            return $ret;
        $resp = $this->read();

        return $resp;
    }

    public function BlinkSlave($id){
        $ret = array('result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;

        $msg = "cmd=modbusmsg&msg=0;66;0;".$id;
        if($this->write( $msg  )  == false)            return $ret;
        $resp = $this->read();

        return $resp;
    }

    public function JoinGroupSlave($id){
        $ret = array('result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;

        $msg = "cmd=modbusmsg&msg=0;66;1;".$id;
        if($this->write( $msg  )  == false)            return $ret;
        $resp = $this->read();

        return $resp;
    }

    public function LeaveGroupSlave($id){
        $ret = array('result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;

        $msg = "cmd=modbusmsg&msg=0;66;2;".$id;
        if($this->write( $msg  )  == false)            return $ret;
        $resp = $this->read();

        return $resp;
    }

    //--------------------------------------------------------------------------------------------------//
    /* Comandi Dali dallo slave [0x44]
     *
     * $opts:   0: nessuna opzione
     *          1: Doppio invio
     *          2: Impegna linea Dali
     *          3: Disimpegna linea Dali
     *
     * $addr:   indirizzo dali
     * $idCmd:  ID comando/dati
     *
     */
    public function SlaveDaliCmd($id, $opts, $addr, $idCmd){
        $ret = array('result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;
        $addr_cmd = ( intval($addr) << 8) | intval($idCmd);
        $msg = "cmd=modbusmsg&msg=".$id.";68;".$opts.";".$addr_cmd;
        if($this->write( $msg  )  == false)            return $ret;
        $resp = $this->read();

        $msgprotocol = new RFledMessage();
        $rxMsg = array();
        $ret = array( 'dali_response' => -1, 'dali_data' => -1 );
        if($msgprotocol->parseRXMessage($resp, $rxMsg)){
            $res = $rxMsg[5];
            $ret = array( 'dali_response' => ($res >> 8) & 255, 'dali_data' => ($res & 255) );

        }

        return $ret;
    }

    //---------------------------------------------------------------------------//
    // DATABASE Operation
    public function execSQL($sql){
        $ret = array('result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false){
            return $ret;
        }
        $msg = "action=query&sql=".$sql;

//        echo $msg;

        if($this->write($msg) == false){
            return $ret;
        }
        $resp = $this->read();

        //TODO gestire risposta dal socket; se è null definire una risposta al chiamante.
//        print_r(json_decode($resp, true));

        return json_decode($resp, true);
    }
    public function getRecords($sql){
        $ret = $this->execSQL($sql);
        return $ret;
    }

    // ------------------------------------------------------------------------ //
    public function RestartProcess(){
        $ret = array('action' => $action, 'result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;

        $msg = "cmd=restartproc";
        if($this->write( $msg  )  == false)            return $ret;
        $resp = $this->read();

        return $resp;

    }

    //---------------------------------------------------------------------------//
    // Funzioni di manutenzione
    public function GetFileConf(){
        $ret = array('action' => $action, 'result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;

        $msg = "cmd=getfileconf";
        if($this->write( $msg  )  == false)            return $ret;
        $resp = $this->read();

        return $resp;
    }


    /* Comandi per lettura / scrittura da db */

    public function dbOperation($action, $group, $params){
        $ret = array('action' => $action, 'result' => 'NOK', 'error' => 'connect to server failed');
        if($this->verify_connect() == false)            return $ret;

        $this->setTimeout(array("sec" => 30, "usec" => 0), array("sec" => 30, "usec" => 0));
        $msg = "action=".$action."&group=".$group."&".implode("&", $params);
        if($group == '')
            $msg = "action=".$action."&".implode("&", $params);
        //echo 'passa '.$msg.' '; die;
        if($this->write( $msg  )  == false)            return $ret;
        $resp = $this->read(150000);
        return json_decode($resp, true);
    }


    public function dbAttribute($group){

    }

    //-----------------------------------------------------------------------------//

    //-----------------------------------------------------------------------------//
    protected function send_receive_slave_message($msgName, $slaveID, $value){
        if($this->verify_connect() == false)            return $ret;

        $msgprotocol = new RFledMessage();
        if($this->write( $msgprotocol->getTXMessage($msgName, $slaveID, $value)  )  == false)
            return $ret;

        $rxMsg = array();
        $resp = $this->read();
        if($resp != ''){
            $rxMsg = array();
            $msgprotocol->parseRXMessage($resp, $rxMsg);
        }

        return $rxMsg;
    }


    protected function verify_connect(){
        if($this->isConnected())            return true;
//        echo $this->ipAddress.' '.$this->socketPort;

        if($this->ping($this->ipAddress, $this->socketPort, $this->pingTimeout) == false)
            return false;

        $bret = $this->connect($this->ipAddress, $this->socketPort);
        $this->setTimeout($this->sndTimeout, $this->rcvTimeout);
        return $bret;
    }

    protected function ping($host, $port, $timeout) {
          if($host == "127.0.0.1" || $host == "localhost")
              return true;
          $tB = microtime(true);
          $fP = fsockopen ($host, $port, $errno, $errstr, $timeout);
          if (!$fP) {  return false;  }
          $tA = microtime(true);
          return true;
    }





}
?>
