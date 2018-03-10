<?php

include_once("libs/phpMQTT/class.mqttHandler.php");
include_once("libs/class.dbQueries.php");
include_once("libs/definedbobject.php");
include_once("libs/class.RFLedSocket.php");



class mqttRequestHandler extends mqttHandler{

  public function mqttRequestHandler($msgInfo, $dbConfig){
      parent::mqttHandler($msgInfo, $dbConfig);
  }

  //---------------------------------------------------------//
  // Implementazione function di action
  //   Metodo per query su DB CLight
  //        sql: query sul DB
  protected function db_query($cmdData){
      $dbHE = new dbQueries($this->dbConfig);
      $records= array();
      if(isset($cmdData['sql']))
          $records = $dbHE->selectQry($cmdData['sql']);
      return $records;
  }

  //   Metodo per navigazione su Map CLight
  protected function get_map_info($cmdData){
      $dbHE = new dbQueries($this->dbConfig);

      $ret = array();
      $objid = isset($cmdData['id']) ? $cmdData['id'] : 0;
      if ($objid == 0) {
          $sql = "select *, object_parents.id_parent as id_parent "
                  . "FROM objects "
                  . "LEFT JOIN object_parents on objects.id = object_parents.id_object "
                  . "WHERE object_parents.id_parent = 0";
          $row = $dbHE->selectQry($sql);
          if(count($row) > 0 && isset($row[0]['id_object']))   $objid = $row[0]['id_object'];
          else return $ret;
      }

      //Ricavo record per planimetria area
      $sql = "select * from objects where id = ".$objid;
      $row = $dbHE->selectQry($sql);
      if( count($row) > 0)
          $ret[] = $row[0];

      $sql = "select objects.*, object_parents.id_parent as id_parent FROM objects
              LEFT JOIN object_parents on objects.id = object_parents.id_object
              where object_parents.id_parent = ".$objid;
      $rows = $dbHE->selectQry($sql);

      foreach ($rows as $value) {
          if($value['type'] == 'area'){
              $sql = "SELECT *
              FROM areas
              WHERE areas.object_id = ".$value['id'];
          }else
              $sql = 'select * from '.get_table_by_object_type($value['type']).' where object_id = '.$value['id'];

          $od = $dbHE->selectQry($sql);
          if( count($od) > 0 ){
              $value['object_data'] = $od[0];
              $value['object_data'] = $this->set_specific_object_data($dbHE, $value['type'], $value['object_data']);
          }


          $value['parent_id'] = $value['id_parent'];
          $ret[] = $value;

      }
      return $ret;
  }


//---------------------------------------------------------------------------------------//
  //   Metodo per rfserfer o iotserver	
  protected function rfserver_update_node($cmdData){
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


//---------------------------------------------------------------------------------------//

protected function set_specific_object_data($dbHE, $type, $object_data) {
    //Aggiungo i vari dati di ciascun oggetto in base al tipo

    if($type == OBJ_CTRL_LABEL){
      if($object_data['rules'] != NULL || $object_data['rules'] != ''){
          $rules = json_decode($object_data['rules']);
          foreach ($rules as $rule) {
                if($rule->code === 'php'){
                    if($rule->type == 'query'){
                    $rows = $dbHE->selectSQL($rule->qry);
                      foreach ($rows as $row){
                          foreach ($row as $key => $val){
                              if($val == -1)
                                $val = '--';
                              $object_data['text'] = str_replace('~'.$key.'~', $val, $object_data['text']);
                          }
                      }
                    }
                }
            }
        }
    }


    return $object_data;
}

};



 ?>
