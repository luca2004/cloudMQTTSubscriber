<?php
//------------------------------------------------
class mysql {
//------------------------------------------------
/**
 * Autor: Vinicius Gallafrio/BR - vgallafrio@madeinweb.com.br
 * Date : 15/01/2002   - Last Update: 17/03/2007
 * http://www.madeinweb.com.br
 *
 * Description: PHP Class for MySQL Connection
 *
 * EXAMPLES:
 *
 * SELECT
 * include "class.mysql.php";
 * $mysql = New mysql('localhost','root','password','database',0);
 * $mysql->query("SELECT field FROM table");                   ^-- change to 1 for debug mode
 *
 * if ($mysql->num_rows() > 0) {
 * 		while ($mysql->movenext()) {
 * 			echo $mysql->getfield("field");
 * 		}
 * }
 *
 * INSERT
 * include "class.mysql.php";
 * $mysql = New mysql('localhost','root','password','database',0);
 * $mysql->query("INSERT INTO table (field) values ('value')");
 *
 * UPDATE
 * include "class.mysql.php";
 * $mysql = New mysql('localhost','root','password','database',0);
 * $mysql->query("UPDATE table SET field='newvalue' WHERE fieldID=1");
 *
 * DELETE
 * include "class.mysql.php";
 * $mysql = New mysql('localhost','root','password','database',0);
 * $mysql->query("DELETE FROM table WHERE fieldID=1");
 *
 *
 */

	/* public: connection parameters */
	var $debug;
	var $host;
	var $database;
	var $user;
	var $password;

	/* private: connection parameters */
	var $conn;
	var $rstemp;
	var $record;


	/**
	 * mysql::mysql()
	 * Constructor this class - define public connection parameters and
	 * call the connect method
	 *
	 * @param $host
	 * @param $user
	 * @param $password
	 * @param $database
	 */
	function mysql ($host,$user,$password,$database,$debug=0) {

		$this->debug = $debug;
		if ($this->debug) echo "<br>\n\nMySQL Debug On <br>\n";

		$this->host = $host;
    	$this->user = $user;
	    $this->password = $password;
	    $this->database = $database;

		/**
         * open connection
         */
		$this->connect();
	}


    /**
     * mysql::connect()
     * Open connection with the server
     *
     * @return id da conexao
     */
     function connect () {

        /**
    	 * Open connection
         */
         if ($this->debug) echo "Connecting  to $this->host <br>\n";
         $this->conn = mysql_pconnect($this->host,$this->user,$this->password)
            			or die("Connection to $server failed <br>\n");

         /**
          * Select to database
          */
          If ($this->debug) echo "Selecting to $this->database <br>\n";
           	@mysql_select_db($this->database,$this->conn)
              			or die("Error:" . mysql_errno() . " : " . mysql_error() . "<br>\n");

          return $this->conn;
    }

	/**
	 * mysql::query()
	 * Execute SQL
	 *
	 * @param $sql
	 * @return
	 */
	function query($sql) {

		if ($this->debug) echo "Run SQL:  $sql <br>\n\n";
		$this->rstemp = @mysql_query($sql,$this->conn)
		            or die("Error:" . mysql_errno() . " : " . mysql_error() . "<br>\n");

		// If insert statement return ID from inserted record for tables with auto-increment primary key
		if (preg_match ("/insert/",$sql)) {
			 if ($this->debug) echo "Retornando ID do insert: ";
			 $this->rstemp = mysql_insert_id();
			 if ($this->debug) echo "$this->rstemp <br>\n\n";
		}

		return $this->rstemp;

	}

	/**
	 * mysql::num_rows()
	 * return number of records in current select
	 *
	 * @param $rstemp
	 * @return
	 */
	function num_rows() {

		$num = @mysql_num_rows($this->rstemp);
		if ($this->debug) echo "$num records returneds <br>\n\n";

		return $num;
	}


  	/**
  	 * mysql::movenext()
	 * fetch next record in result
  	 *
  	 * @return
  	 */
  	function movenext(){

		if ($this->debug) echo "Fetching next record  ... ";
	    $this->record = @mysql_fetch_array($this->rstemp);
	    $status = is_array($this->record);

		if ($this->debug && $status) echo "OK <br>\n\n";
		elseif ($this->debug) echo "EOF <br>\n\n";

	    return($status);
	}


	/**
	 * mysql::getfield()
	 * get field value from the current record
	 *
	 * @param $field
	 * @return
	 */
	function getfield($field){

		if ($this->debug) {
			echo "Getting $field ... ";
			//this resource require PHP 4.1 or righter
			if (phpversion() >= 4.1) {
				if (array_key_exists($field,$this->record)) echo "OK <br>\n\n";
				else echo "Not found <br>\n\n";
			} else echo " <br>\n\n";
		}

		return($this->record[$field]);
	}

	/**
	 * mysql::datafilter()
	 * prepare string before run sql query
	 *
	 * @param $str
	 * @return
	 */
	function dataFilter($str){

		if ($debug) echo "Checking secure string value for $str ... OK <br>\n\n";
		return mysql_escape_string($str);
	}


	/**
	 * mysql::cDateMySQL2Br()
	 * convert date from YYYY/MM/DD to DD/MM/YYYY
	 *
	 * @param $dt
	 * @return
	 */
	function cDateMySQL2Br($dt) {

		if (strlen($dt) > 10) $dt = substr($dt,0,10); //despreza a hora

		if (strlen($dt) == 10) {
			if ($debug) echo "Converting date: MySQL[$dt] for ";
			$nYear = substr($dt,0,4); $nMonth = substr($dt,5,2); $nDay = substr($dt,8,2);
			if ($debug) echo "Portuguese[$nDay/$nMonth/$nYear] <br>\n\n";

			if (checkdate($nMonth, $nDay, $nYear)) $sDate = "$nDay/$nMonth/$nYear";
		} else {
			if ($debug) echo "Date format inválid. Use dd/mm/YYYY <br>\n\n";
		}

		return $sDate;

	}

	/**
	 * mysql::cDateTimeMySQL2Br()
	 * convert date time from YYYY/MM/DD HH:MM:SS to DD/MM/YYYY HH:MM:SS
	 *
	 * @param $dt
	 * @return
	 */
	function cDateTimeMySQL2Br($dt) {

		if (strlen($dt) > 10) {
			$tm = substr($dt,11, strlen($dt)-11);
			$dt = substr($dt,0,10);
		}

		if (strlen($dt) == 10) {
			if ($debug) echo "Converting date: MySQL[$dt] for ";
			$nYear = substr($dt,0,4); $nMonth = substr($dt,5,2); $nDay = substr($dt,8,2);
			if ($debug) echo "Portuguese[$nDay/$nMonth/$nYear] <br>\n\n";

			if (checkdate($nMonth, $nDay, $nYear)) $sDate = "$nDay/$nMonth/$nYear";
		} else {
			if ($debug) echo "Date format inválid. Use dd/mm/YYYY <br>\n\n";
		}

		if ($tm != "" && $tm != "00:00:00") $sDate = "$sDate $tm";

		return $sDate;

	}

	/**
	 * mysql::cDateBr2MySQL()
	 * convert date from DD/MM/YYYY to YYYY/MM/DD
	 *
	 * @param $dt
	 * @return
	 */
	function cDateBr2MySQL($dt) {

		if (strtoupper($dt) == "NULL") $sDate = $dt;
		if (strlen($dt) > 10) $dt = substr($dt,0,10); //despreza a hora

		if (strlen($dt) == 10) {
			if ($debug) echo "Converting date: Portuguese[$dt] for ";
			$nYear = substr($dt,6,4); $nMonth = substr($dt,3,2); $nDay = substr($dt,0,2);
			if ($debug) echo "MySQL[$nYear/$nMonth/$nDay] <br>\n\n";

			//if (checkdate($nMonth, $nDay, $nYear))
			$sDate = "$nYear/$nMonth/$nDay";
		} else {
			if ($debug) echo "Date format inválid [$dt]. Use dd/mm/YYYY <br>\n\n";
		}

		return $sDate;
	}

	/**
	 * mysql::cDateTimeBr2MySQL()
	 * convert date time from DD/MM/YYYY HH:MM:SS to YYYY/MM/DD HH:MM:SS
	 *
	 * @param $dt
	 * @return
	 */
	function cDateTimeBr2MySQL($dt) {

		if (strtoupper($dt) == "NULL") $sDate = $dt;
		if (strlen($dt) > 10) {
			$tm = substr($dt,11,strlen($dt)-11);
			$dt = substr($dt,0,10);
		}

		if (strlen($dt) == 10) {
			if ($debug) echo "Converting date: Portuguese[$dt] for ";
			$nYear = substr($dt,6,4); $nMonth = substr($dt,3,2); $nDay = substr($dt,0,2);
			if ($debug) echo "MySQL[$nYear/$nMonth/$nDay] <br>\n\n";

			//if (checkdate($nMonth, $nDay, $nYear))
			$sDate = "$nYear/$nMonth/$nDay";
		} else {
			if ($debug) echo "Date format inválid [$dt]. Use dd/mm/YYYY <br>\n\n";
		}

		if ($tm != "" && $tm != "00:00:00") $sDate = "$sDate $tm";

		return $sDate;
	}

	/**
	 * mysql::cValorMySQL2Br()
	 * convert point to comma
	 *
	 * @param $valor
	 * @return double
	 */
	function cValorMySQL2Br($valor) {

		if ($debug) echo "formating number: MySQL[$valor] for ";
		if (is_numeric($valor)) {
			$valor = str_replace(",","",$valor); //retira separado de milhar
			$valor = number_format($valor,2,"."," ");
			$valor = str_replace(".",",",$valor);
			$valor = str_replace(" ",".",$valor);
		}
	    if ($debug) echo "Portuguese[$valor] <br>\n\n";

		return $valor;

	}

	/**
	 * mysql::cValorBr2MySQL()
	 * convert comma to point
	 * 
	 * @param $valor
	 * @return double
	 */
	function cValorBr2MySQL($valor) {

	    if ($debug) echo "formating number: Portuguese[$valor] for ";
		if ($valor != "") {
			$valor = str_replace(".","",$valor); //retira separado de milhar
			$valor = str_replace(",",".",$valor);
			$valor = number_format($valor,2,".","");
		}
        if ($debug) echo "MySQL[$valor] <br>\n\n";

		return $valor;

	}

	/**
	 * mysql::cBooleanBr2MySQL()
	 * convert 1 for "Sim" and 0 for "Não"
	 *
	 * @param $valor
	 * @return int
	 */
	function cBooleanBr2MySQL($valor) {
        if ($debug) echo "formating boolean: Portuguese[$valor] for ";

		if ($valor != "") {
			if (strtolower($valor) == "sim")
				$valor = 1;
			else
				$valor = 0;
		}
        if ($debug) echo "MySQL[$valor] <br>\n\n";

		return $valor;

	}

	/**
	 * mysql::cBooleanBr2MySQL()
	 * convert "Sim" for 1 and "Não"for 0
	 *
	 * @param $valor
	 * @return string
	 */
	function cBooleanMySQL2Br($valor) {
        if ($debug) echo "formating boolean: MySQL[$valor] for ";

		if ($valor != "") {
    		if ($valor)
    			$valor = "Sim";
    		else
    			$valor = "Não";
		}
        if ($debug) echo "Portuguese[$valor] <br>\n\n";

		return $valor;

	}
}
?>
