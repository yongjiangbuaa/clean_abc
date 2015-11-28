<?php
class DBHelper {
	private $dsn;
	private $config;
	/**
	 * 数据库连接
	 * @var MooMySQL
	 */
	private $conn = null;
	private $autoclose = false;
	private $errhandler = null;
	/**
	 * 日志操作对象
	 *
	 * @var ILogger
	 */
	private $logger = null;
	
	public function __construct($config = null){
		if(!empty($config)){
			$this->setConfig($config);
		}
		$this->logger = $GLOBALS['database_logger'];
		if ($this->logger->isDebugEnabled()) {
			$object_id = spl_object_hash($this);
			if (!isset($GLOBALS['initialized_cache_helpers'])) $GLOBALS['initialized_cache_helpers'] = array();
			if (!in_array($object_id, $GLOBALS['initialized_cache_helpers'])) {
				$GLOBALS['initialized_cache_helpers'][] = $object_id;
			} else {
				$this->logger->writeDebug("the db helper:$object_id is initialized twice. check it.");
			}
		}
	}
	
	/**
	 * set the error handler
	 * @param $handler callback 该函数支持三个参数,根据参数顺序是
	 * 1. error message
	 * 2. error code
	 * 3. additional info , sql etc.
	 */
	public function setErrorHandler($handler){
		$old_handler = $this->errhandler;
		if(is_callable($handler)){
			$this->errhandler = $handler;
			return $old_handler;
		}
		return false;
	}
	
	/**
	 * 关闭数据库连接
	 */
	public function close() {
		if($this->conn){
			$this->conn->close();
			$this->conn = null;
		}
	}
	
	/**
	 * 设置数据库的设置
	 * @param $config mixed a pear style dsn or an array
	 * a dsn is like driver://username:password@localhost/dbname?option=a
	 * current support driver 'mysql','mssql','mssqlnt'
	 */
	public function setConfig($config){
		if(is_array($config)){
			$this->config = $config;
		}
		elseif(!empty($config)){
			$this->dsn = $config;
			$this->config = parse_dsn($this->dsn);
		}
		if(isset($this->config['autoclose'])){
			$this->autoclose = $this->config['autoclose'];
		}
	}
	/**
	 * 是executeNonQuery的别名
	 * @param $sql
	 * @param $params
	 * @return int
	 */
	public function execute($sql,$params = null){
		return $this->executeNonQuery($sql,$params);
	}
	/**
	 * 开始一个事务
	 * @return bool
	 */
	public function startTransaction(){
		return $this->getConnection()->query('START TRANSACTION');
	}
	/**
	 * 提交事务
	 * @return bool
	 */
	public function commit(){
		return $this->getConnection()->query('COMMIT');
	}
	/**
	 * 回滚事务
	 * @return bool
	 */
	public function rollback(){
		return $this->getConnection()->query('ROLLBACK');
	}
	
	/**
	 * 执行SQL
	 * @param $sql string
	 * @param $params mixed
	 * @return mixed
	 */
	public function query($sql,$params = null){
		$sql = $this->getSQL($sql,$params);
		return $this->getConnection()->query($sql);
	}
	/**
	 * 执行update,insert,delete等非查询的SQL，返回受影响的行数。
	 * @param $sql string
	 * @param $params mixed
	 * @return int 受影响的行数
	 *
	 */
	public function executeNonQuery($sql,$params = null){
		$sql = $this->getSQL($sql,$params);
		return $this->getConnection()->query($sql);
	}
	/**
	 * 获取执行executeNonQuery之后影响的行数
	 * @return int
	 */
	public function affectedRows(){
		return $this->getConnection(false)->affectedRows();
	}
	/**
	 * 获取查询的所有结果行。
	 * @param $sql string
	 * @param $params mixed
	 * @return array 查询的结果
	 */
	public function fetchAll($sql,$params = null){
		$sql = $this->getSQL($sql,$params);
		$rs = $this->getConnection()->getAll($sql);
		return $rs;
	}
	
	/**
	 * 获取查询的所有结果行。
	 * @param $sql string
	 * @param $params mixed
	 * @return array 查询的结果
	 */
	public function getAll($sql,$params = null){
		return $this->fetchAll($sql,$params);
	}
	/**
	 * 根据指定的字段，从查询结果中取得一个pair关联数组
	 * @param string $sql
	 * @param string $key_field 用作数组key的字段
	 * @param string $value_field 用作value的字段
	 * @param array $params 查询使用的参数
	 * @return array
	 */
	public function getPairs($sql,$key_field,$value_field,$params = null){
		$sql = $this->getSQL($sql,$params);
		return $this->getConnection()->getPairs($sql,$key_field,$value_field);
	}
	/**
	 * 将查询的结果通过object数组的形式返回
	 * @param $sql
	 * @param $class_name 用于返回的类名
	 * @param $params sql查询参数
	 * @param $obj_args 用于创建对象的参数
	 * @return array
	 */
	public function getObjectAll($sql,$class_name = 'stdClass',$params = null,$obj_args = null){
		$sql = $this->getSQL($sql,$params);
		return $this->getConnection()->getObjectAll($sql,$class_name,$obj_args);
	}
	
	/**
	 * 获取全部数据,结果是以数据行中指定的字段为key的关联数组
	 *
	 * @param string $sql
	 * @param string $key 作为key的字段
	 * @param array $params
	 * @return array
	 */
	public function getAssocAll($sql,$key,$params = null){
		$sql = $this->getSQL($sql,$params);
		return $this->getConnection()->getAssocAll($sql,$key);
	}
	/**
	 * 获取全部数据,组成以数据行中指定的字段和分界字符的字符串
	 *
	 * @param string $sql
	 * @param string $key 作为key的字段
	 * @param array $params
	 * @param string $delimiter
	 * @return string
	 */
	public function getAndJoinAll($sql,$key,$params = null,$delimiter = ','){
		$sql = $this->getSQL($sql,$params);
		return $this->getConnection()->getAndJoinAll($sql,$key,$delimiter);
	}
	/**
	 * 获取执行错误的错误代码
	 * @return int
	 */
	public function getCode(){
		return $this->getConnection()->getErrorCode();
	}
	/**
	 * 获取执行错误的错误消息
	 * @return string
	 */
	public function getMessage(){
		return $this->getConnection()->getErrorMessage();
	}
	
	/**
	 * 获取查询结果中特定行数。
	 * @param $sql string
	 * @param $params mixed
	 * @return array 查询结果
	 */
	public function fetchLimit($sql,$params = null,$limit = 0,$start=0){
		$sql = $this->getSQL($sql,$params);
		if($limit > 0){
			if(stripos($sql,'limit') === false){
				$sql .= sprintf(" LIMIT %d,%d",intval($start),intval($limit));
			}
		}
		return $this->getConnection()->getAll($sql);
	}
	/**
	 * 或者查询结果中的第一行。
	 * @param $sql string
	 * @param $params mixed
	 * @return array 查询结果的第一行
	 */
	public function fetchOne($sql,$params = null){
		$sql = $this->getSQL($sql,$params);
		return $this->getConnection()->getOne($sql);
	}
	
	/**
	 * 或者查询结果中的第一行。
	 * @param $sql string
	 * @param $params mixed
	 * @return array 查询结果的第一行
	 */
	public function getOne($sql,$params = null){
		return $this->fetchOne($sql,$params);
	}

	/**
	 * 根据查询获取一个向量值。如果查询中指定了多列，则返回第一列的值。
	 * @param $sql string
	 * @param $params mixed
	 * @return object 查询结果的向量值
	 */
	public function fetchScalar($sql,$params = null){
		$sql = $this->getSQL($sql,$params);
		$conn = $this->getConnection();
		$resource= $conn->query($sql);
		return $conn->result($resource,0);
	}
	
	public function resultFirst($sql,$params = null){
		return $this->fetchScalar($sql,$params);
	}
	
	/**
	 * 根据特定条件查询一个表
	 * @param $sql string
	 * @param $params mixed
	 * @return array 查询结果
	 */
	public function fetchTable($tablename,$wheresqlarr,$columns = null){
		if(empty($wheresqlarr)) {
			$where = '1=1';
		} elseif(is_array($wheresqlarr)) {
			$where = $this->joinPairs($wheresqlarr,' AND ');
		} else {
			$where = $wheresqlarr;
		}
		if(empty($columns)){
			$columns = '*';
		}
		return $this->fetchAll("SELECT $columns FROM $tablename WHERE " . $where);
	}
	/**
	 * 根据特定条件更新表。
	 * @param $tablename string 表名称
	 * @param $setsqlarr array 更新的数据字段为key，内容为value
	 * @param $wheresqlarr array where条件的数组
	 * @param $silent boolean
	 */
	public function updatetable($tablename, $setsqlarr, $wheresqlarr) {
		$setsql = $this->joinPairs($setsqlarr);
		if(empty($wheresqlarr)) {
			$where = '1=1';
		} elseif(is_array($wheresqlarr)) {
			$where = $this->joinPairs($wheresqlarr,' AND ');
		} else {
			$where = $wheresqlarr;
		}
		$sql = 'UPDATE '. $tablename.' SET '.$setsql.' WHERE '.$where;
		$this->query($sql);
	}
	/**
	 * 往表中插入一条数据
	 * @param $tablename string 表名称
	 * @param $insertsqlarr array 插入的数据的字段为key，内容为value
	 * @param $returnid boolean 使用返回插入的id
	 * @param $replace boolean 是否使用replace
	 * @param $silent boolean
	 */
	public function inserttable($tablename, $insertsqlarr, $returnid = 0, $replace = false) {
		$insertkeysql = $insertvaluesql = $comma = '';
		foreach ( $insertsqlarr as $insert_key => $insert_value ) {
			$insertkeysql .= $comma . '`' . $insert_key . '`';
			$insertvaluesql .= $comma . '\'' . $insert_value . '\'';
			$comma = ', ';
		}
		
		$method = $replace ? 'REPLACE' : 'INSERT';
		$sql = $method . ' INTO ' . $tablename . ' ('
			. $insertkeysql . ') VALUES (' . $insertvaluesql . ') ';
		$this->query($sql);
		if ($returnid && ! $replace) {
			$insert_id = $this->getConnection()->insertId ();
		}
		else{
			$insert_id = false;
		}
		return $insert_id;
	}
	
	/**
	 * 执行一个insert语句，返回insert的自动增长列的id
	 *
	 * @param string $sql
	 * @param array $params
	 * @return int 自动增长列的插入id
	 */
	public function executeInsert($sql,$params = null){
		$sql = $this->getSQL($sql,$params);
		$this->getConnection()->query($sql);
		return $this->getConnection()->insertId();
	}

	public static function joinPairs($pair_arr,$delemeter = ','){
		if(empty($pair_arr)){
			return '';
		}
		$str = '';
		$comma = '';
		foreach ($pair_arr as $key => $value) {
			$str .= $comma . $key . '=';
			if(is_int($value)){
				$str .= $value;
			}
			else{
				$str .= '\'' . elex_addslashes($value) . '\'';
			}
			$comma = $delemeter;
		}
		return $str;
	}
	/**
	 * 获取内部使用的连接
	 * @return MooMySQL
	 */
	private function getConnection($re_select_db=true){
		if(!isset($this->conn)){
			$this->conn = $this->getDriver();
			if($this->config['pconnect'] == 'false' || !$this->config['pconnect']){
				$pconnect = false;
			}else{
				$pconnect = true;
			}
			$this->conn->connect(
				$this->config['host'],
				$this->config['username'],
				$this->config['password'],
				$pconnect,
				$this->config['charset'],
				$this->config['newlink']);
			if ($this->logger->isDebugEnabled()) {
				$this->logger->writeDebug(
					"[db_helper=%s]trying to connect to database[host=%s,username=%s,database=%s,pconnect=%s,charset=%s,newlink=%s]",
					array(
						spl_object_hash($this),$this->config['host'],
						$this->config['username'],$this->config['database'],
						$pconnect,$this->config['charset'],$this->config['newlink']));
			}
		}
		if ($re_select_db && $this->config['database']) {
			$this->conn->selectDB($this->config['database'], $this->config);
		}
		return $this->conn;
	}
	
	protected function getDriver(){
		require_once FRAMEWORK .'/database/drivers/MooMySQL.class.php';
		return new MooMySQL();
	}
	
	protected function getSQL($sql,$params = null){
		$format_sql = $sql;
		if(is_array($params)){
			$format_sql = vsprintf($sql,$params);
		}
		elseif(!is_null($params)){
			$format_sql = sprintf($sql,$params);
		}
		if ($format_sql === false) {
			$this->logger->writeError("the composit sql is false, check it:[sql=$sql, param=$params]");
		}
		if ($this->logger->isDebugEnabled()) {
			$this->logger->writeDebug("trying to exectue sql[db_helper=%s,host=%s,db=%s] : %s", 
				array(spl_object_hash($this),$this->config['host'], 
				$this->config['database'], $format_sql));
		}
		return $format_sql;
	}
}

?>