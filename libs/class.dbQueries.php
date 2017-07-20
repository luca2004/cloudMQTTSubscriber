<?php

	include_once("libs/class.mysql.php");

	class dbQueries{
		
		var $infoDB = array();

		public function dbQueries($infodb){
			$this->infoDB = $infodb;

		}
		
		public function getQry($sqlqry){
			$mysql = New mysql($this->infoDB['host'],
							$this->infoDB['user'],$this->infoDB['password'],
							$this->infoDB['database'],0);
							
							
			$sql = $sqlqry;   //"select * from events where epoch LIKE '$datetime%'";
			$fields = array( 'id', 'object_id', 'name', 'log_description', 'status', 'type',
								'epoch', 'priority', 'extradata');
			
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
						

			return $data;
		}
	};
	
?>