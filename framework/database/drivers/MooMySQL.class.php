<?php
/**
 * 用户访问mysql数据库的dbo类
 * @author shusl
 *
 */
class MooMySQL {
	private $queryCount = 0;
	private $conn;
	private $result;
	private $rsType = MYSQL_ASSOC;
	//note:查询时间
	private $queryTimes = 0;

	private $throwException = true;
	
	/**
	 * 设置是否在发生错误的时候抛出异常，如果设置为false，则产生一个E_USER_ERROR级别的错误
	 * @param $value
	 * @return void
	 */
	public function throwExceptionOnError($value = true){
		$this->throwException = $value ? true : false;
	}
	
	/**
	 * 连接数据库
	 *
	 * @param string $dbHost
	 * @param string $dbName
	 * @param string $dbUser
	 * @param string $dbPass
	 * @param blooean $dbOpenType
	 * @param string $dbCharset
	 * @return void
	 */
	public function connect($dbHost = '', $dbUser = '', $dbPass = '', $dbOpenType = false ,$dbCharset = 'utf8',$newlink = false) {
		if($dbOpenType) {
			if(!$this->conn = mysql_pconnect($dbHost, $dbUser, $dbPass,$newlink)) {
				$this->errorMsg('Can not connect to MySQL server');
			}
		} else {
			if(!$this->conn = mysql_connect($dbHost, $dbUser, $dbPass,$newlink)) {
				$this->errorMsg('Can not connect to MySQL server');
			}
		}
		
		$dbCharset = str_replace('-','',$dbCharset);
		$serverset = $dbCharset ? 'character_set_connection='.$dbCharset.', character_set_results='.$dbCharset.', character_set_client=binary' : '';
		$serverset .=  ((empty($serverset) ? '' : ',').'sql_mode=\'\'');
		mysql_query("SET $serverset", $this->conn);
		return true;
	}
	/**
	 * 选择一个数据库
	 * @param $dbName
	 * @return bool
	 */
	public function selectDB($dbName, $config = null){
		if(isset($dbName[0])){
			if (!mysql_ping($this->conn) && isset($config)) {
				$GLOBALS['database_logger']->writeError("lost connection, tring to reconnect[host=".$config['host'].",db=$dbName].");
				$this->connect(
					$config['host'],
					$config['username'],
					$config['password'],
					($config['pconnect'] == 'false' || !$config['pconnect']) ? false : true,
					$config['charset'],
					$config['newlink']);
			}
			$re = mysql_select_db($dbName, $this->conn);
			if(!$re){
				$this->errorMsg('select db failure', "use $dbName");
			}
		}
		return true;
	}
	
	/**
	 * 关闭数据库连接，当您使用持续连接时该功能失效
	 *
	 * @return boolean
	 */
	public function close() {
		return mysql_close($this->conn);
	}
	
	/**
	 * 发送查询语句
	 *
	 * @param string $sql
	 * @return blooean
	 */
	public function query($sql) {
		$start = microtime(true);
		$this->result = mysql_query($sql, $this->conn);
		$diff_time = microtime(true) - $start;
	    $time_spend = round($diff_time * 1000, 1);
	    if ($time_spend > 500) {
	    	file_put_contents(LOG_DIR.'mysql-slow.log', '['.$time_spend.'ms]'.$sql."\n", FILE_APPEND);
	    }
		++$this->queryCount;
		if(!$this->result) {
			return $this->errorMsg('MySQL Query Error', $sql);
		} else {
			return $this->result;
		}
	}
	/**
	 * 设置取数据的类型
	 * @param $type ASSOC, NUM, BOTH
	 */
	public function setFetchType($type = "ASSOC"){
		$this->rsType = $type != "ASSOC" ? ($type == "NUM" ? MYSQL_NUM : MYSQL_BOTH) : MYSQL_ASSOC;
	}
	
	public function quote($str){
		return mysql_real_escape_string($str,$this->conn);
	}
	
	/**
	 * 数据量比较大的情况下查询
	 *
	 * @param string $sql
	 * @param string $type
	 * @return blooean
	 */
	public function bigQuery($sql, $type = "ASSOC") {
		$this->rsType = $type != "ASSOC" ? ($type == "NUM" ? MYSQL_NUM : MYSQL_BOTH) : MYSQL_ASSOC;
		$this->result = mysql_unbuffered_query($sql, $this->conn);
		++$this->queryCount;
		if(!$this->result) {
			return $this->errorMsg('MySQL Query Error', $sql);
		}
		else {
			return $this->result;
		}
	}
	
	/**
	 * 获取全部数据
	 *
	 * @param string $sql
	 * @param blooean $nocache
	 * @return array
	 */
	public function getAll($sql, $noCache = false) {
		$noCache ? $this->bigQuery($sql) : $this->query($sql);
		$rows = array();
		while($row = mysql_fetch_array($this->result, $this->rsType)) {
			$rows[] = $row;
		}
		return $rows;
	}
	/**
	 * 根据指定的字段，从查询结果中取得一个pair关联数组
	 * @param string $sql
	 * @param string $key_field 用作数组key的字段
	 * @param string $value_field 用作value的字段
	 * @param bool $noCache 是否使用缓存查询
	 * @return array
	 */
	public function getPairs($sql,$key_field,$value_field,$noCache = false){
		$noCache ? $this->bigQuery($sql) : $this->query($sql);
		$rows = array();
		while($row = mysql_fetch_array($this->result, $this->rsType)) {
			$rows[$row[$key_field]] = $row[$value_field];
		}
		return $rows;
	}
	/**
	 * 获取全部数据,结果是以数据行中指定的字段为key的关联数组
	 *
	 * @param string $sql
	 * @param string $key 作为key的字段
	 * @param blooean $nocache
	 * @return array
	 */
	public function getAssocAll($sql,$key,$noCache = false) {
		$noCache ? $this->bigQuery($sql) : $this->query($sql);
		$rows = array();
		while($row = mysql_fetch_array($this->result, $this->rsType)) {
			$rows[$row[$key]] = $row;
		}
		return $rows;
	}
	/**
	 * 获取全部数据,组成以数据行中指定的字段和分界字符的字符串
	 *
	 * @param string $sql
	 * @param string $key 作为key的字段
	 * @param string $delimiter
	 * @param blooean $nocache
	 * @return string
	 */
	public function getAndJoinAll($sql,$key,$delimiter = ',', $noCache = false) {
		$noCache ? $this->bigQuery($sql) : $this->query($sql);
		$rows = array();
		$rs = '';
		while($row = mysql_fetch_array($this->result, $this->rsType)) {
			$rs .= $row[$key] . $delimiter;
		}
		return rtrim($rs,$delimiter);
	}
	/**
	 * 获取单行数据
	 *
	 * @param string $sql
	 * @return array
	 */
	public function getOne($sql) {
		$this->query($sql);
		$rows = mysql_fetch_array($this->result, $this->rsType);
		return $rows;
	}
	
	/**
	 * 从结果集中取得一行作为关联数组，或数字数组
	 *
	 * @param resource $query
	 * @return array
	 */
	public function fetchArray($query) {
		return mysql_fetch_array($query, $this->rsType);
	}

	/**
	 * 取得结果数据
	 *
	 * @param resource $query
	 * @return string
	 */
	public function result($query, $row,$field = 0) {
		$query = mysql_result($query, $row, $field);
		return $query;
	}

	/**
	 * 取得上一步 INSERT 操作产生的 ID
	 *
	 * @return integer
	 */

	public function insertId() {
		return ($id = mysql_insert_id($this->conn)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
	}
	
	/**
	 * 取得行的数目
	 *
	 * @param resource $query
	 * @return integer
	 */
	public function numRows($query) {
		return mysql_num_rows($query);
	}
	
	/**
	 * 取得结果集中字段的数目
	 *
	 * @param resource $query
	 * @return integer
	 */
	public function numFields($query) {
		return mysql_num_fields($query);
	}
	
	/**
	 * 取得前一次 MySQL 操作所影响的记录行数
	 *
	 * @return integer
	 */
	public function affectedRows() {
		return mysql_affected_rows($this->conn);
	}

	/**
	 * 取得结果中指定字段的字段名
	 *
	 * @param string $data
	 * @param string $table
	 * @return array
	 */
	function listFields($data, $table) {
		$row = mysql_list_fields($data, $table, $this->conn);
		$count = mysql_num_fields($row);
		for($i = 0; $i < $count; $i++) {
			$rows[] = mysql_field_name($row, $i);
		}
		return $rows;
	}
	
	/**
	 * 列出数据库中的表
	 *
	 * @param string $data
	 * @return array
	 */
	function listTables($data) {
		$query = mysql_list_tables($data);
		$rows = array();
		while($row = mysql_fetch_array($query)) {
			$rows[] = $row[0];
		}
		mysql_free_result($query);
		return $rows;
	}
	
	/**
	 * 取得表名
	 *
	 * @param string $table_list
	 * @param integer $i
	 * @return string
	 */
	function tableName($table_list, $i) {
		return mysql_tablename($table_list, $i);
	}
	
	/**
	 * 转义字符串用于查询
	 *
	 * @param string $char
	 * @return string
	 */
	function escapeString($char) {
		return mysql_escape_string($char);
	}
	
	/**
	 * 取得数据库版本信息
	 *
	 * @return string
	 */
	function getVersion() {
		return mysql_get_server_info();
	}

	/**
	 * 错误处理
	 *
	 * @param string $msg
	 * @param string $sql
	 * @return void
	 */
	function errorMsg($msg = '', $sql = '') {
		$error_data = array();
		if(!empty($msg)) {
			$error_data['msg'] = $msg;
		}
		$raw_msg = mysql_error($this->conn);
		if (!empty($raw_msg)) {
			$error_data['raw_msg'] = $raw_msg;
		}
		if (!empty($sql)) {
			$error_data['sql'] = $sql;
		}
		$error_no = mysql_errno($this->conn);
		if ($error_no > 0) {
			$error_data['raw_code'] = $error_no;
		}
		$message = json_encode($error_data);
		if($this->throwException){
			require_once FRAMEWORK . '/database/DBException.class.php';
			throw new DBException($message, mysql_errno($this->conn), $sql);
		}
		trigger_error($message,E_USER_ERROR);
	}
}
