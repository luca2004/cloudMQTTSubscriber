<?php

	include_once("libs/class.mysql.php");

	class dbQueries{

		var $infoDB = array();

		public function dbQueries($infodb){
			$this->infoDB = $infodb;

		}

		public function selectQry($sqlqry){
			$mysql = New mysql($this->infoDB['host'],
							$this->infoDB['user'],$this->infoDB['password'],
							$this->infoDB['database'],0);


			$sql = $sqlqry;   //"select * from events where epoch LIKE '$datetime%'";
			/*$fields = array( 'id', 'object_id', 'name', 'log_description', 'status', 'type',
								'epoch', 'priority', 'extradata');*/

			$mysqlQry = $mysql->query($sql);
			$data = array();
			if ($mysql->num_rows() > 0) {
				while ($mysql->movenext()) {
					$rows = array();
					foreach($mysql->record as $key => $val){
						if(is_numeric($key) == false)
							$rows[$key] = $mysql->record[$key];

					}
					$data[] = $rows;
				}
			}

			$mysql->destroy();
			return $data;
		}

		public function updateQry($table, $values, $cond ){

				$arrValues = array();
				foreach($values as $key => $val){
						$arrValues[] = "$key='$val'";
				}

				$sql = "UPDATE $table SET ".implode($arrValues, ',')." WHERE ".$cond;
				$mysql = New mysql($this->infoDB['host'],
								$this->infoDB['user'],$this->infoDB['password'],
								$this->infoDB['database'],0);

				$mysql->query($sql);
				$mysql->destroy();
		}

		public function insertQry($table, $values){
				$arrValues = array();
				foreach($values as $key => $val){
						$arrValues[] = "$key='$val'";
				}

				$sql = "INSERT INTO $table SET ".implode($arrValues, ',');
				$mysql = New mysql($this->infoDB['host'],
								$this->infoDB['user'],$this->infoDB['password'],
								$this->infoDB['database'],0);

				$mysql->query($sql);

				$mysql->query("select id from $table order by id desc limit 0,1");
				$lastID = 0;
				if($mysql->num_rows() > 0){
						if($mysql->movenext()){
								$lastID = $mysql->getfield("id");
						}

				}

				$mysql->destroy();
				return $lastID;
		}

	};

?>
