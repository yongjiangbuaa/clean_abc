<?php
/**
 * 用于生成自增长的id的类。该类依赖于数据库和memcached缓存。
 */
class IDSequence{
	const STORAGE_TYPE_DATABASE = "database";
	const STORAGE_TYPE_CACHE = "cache";
	private $table_name;
	private $id_field;
	private $data_exists = false;
	private $step = 1000;
	private $key_prefix = 'id_sequence_';
	/**
	 * memcache缓存操作类
	 *
	 * @var Cache
	 */
	private $cache;
	/**
	 * 数据库操作类
	 *
	 * @var DBHelper
	 */
	private $dbhelper;
	/**
	 * 日志操作类
	 *
	 * @var ILogger
	 */
	private $logger = null;
	private $storage_type = null;
	private $id_start = null;
	
	public function __construct($table_name,$id_field,$data_exists = false,$app_config = null){
		$this->logger = $GLOBALS['framework_logger'];
		$this->setParam($table_name,$id_field,$data_exists);
		$this->setConfig($app_config);
	}
		
	/**
	 * 设置数据库操作类和缓存操作类
	 * @param array $config
	 * 数据库操作类的key是dbo
	 * 缓存操作类的key是cache
	 */
	private function setConfig($app_config = null){
		if($app_config == null)
			$app_config = get_app_config();
		$this->cache = $app_config->getTableServer("id_sequence")->getCacheInstance();
		$this->dbhelper = $app_config->getTableServer("id_sequence")->getDBHelperInstance();
		if (empty($this->cache) || empty($this->dbhelper)) {
			throw new Exception('config error: dbo or cache not set');
		}
		$this->id_start = $app_config->getSection(AppConfig::ID_SEQUENCE_START);
		$this->storage_type = $app_config->getGlobalConfig("id_sequence_storage");
		if (empty($this->storage_type) || 
			!in_array($this->storage_type, 
				array(self::STORAGE_TYPE_CACHE, self::STORAGE_TYPE_DATABASE))) {
			$this->storage_type = self::STORAGE_TYPE_CACHE;
		}
	}
	
	/**
	 * 设置当缓存失效时候，取得值的递增步长，缓存值增加了步长值之后，会提交到数据库。
	 * 该值是为了防止缓存服务器出现问题的容错处理，防止出现重复的id
	 * @param int $step
	 */
	public function setStep($step = 1000){
		$step = intval($step);
		if(!empty($step)){
			$this->step = $step;
		}
	}
	
	/**
	 * 取得当前的id
	 * @return int
	 */
	public function getCurrentId(){
		if ($this->storage_type == self::STORAGE_TYPE_CACHE) {
			return $this->initSeq($this->id_field);
		} else {
			$key = $this->getMemKey();
			$current_id = $this->cache->get($key);
			if ($current_id === false) {
				$res = $this->dbhelper->fetchOne("SELECT max(id) AS current_id FROM ".$this->key_prefix.$this->table_name);
				if (empty($res)) $current_id = 0;
				$current_id = $res['current_id'];
				$this->cache->set($key, $current_id, 0);
			}
			return $current_id;
		}
	}
	
	/**
	 * 取得下n个id中最大的那个
	 * @return int
	 */
	public function getNextId($count=1){
		if ($this->storage_type == self::STORAGE_TYPE_CACHE) {
			$next_id = $this->updateCache($count);
		} else {
			$this->getCurrentId();
			do {
				$next_id = $this->getNextIdFromDb();
			} while ($next_id == 0);
			$this->cache->increment($this->getMemKey(), $count, 0);
		}
		return $next_id;
	}
	
	private function getNextIdFromDb() {
		try {
			$next_id = $this->dbhelper->executeInsert("INSERT INTO ".$this->key_prefix.$this->table_name." VALUES (null)");
		} catch (DBException $e) {
			$next_id = 0;
			$this->logger->writeError("db exception happens while generate sequence id.".$e->getMessage());
		}
		return $next_id;
	}
	
	/**
	 * 递增id的值
	 * @param int $inc 递增的量
	 * @return int 增加后的id值
	 */
	public function increaseId($inc = 1){
		if ($this->storage_type == self::STORAGE_TYPE_CACHE) {
			return $this->updateCache($inc);
		} else {
			$this->throwException("the increase id by $inc is not supprot for database storage type. try to use for iteration.", FRAMEWORK_ERROR_ID_SEQUENCE_OPERATION_NOT_SUPPORT);
		}
	}
	/**
	 * 初始化一个序列，如果已经初始化，则什么也不做
	 *
	 * @param string $id_field 用于初始化的table中的id字段
	 * @return int
	 */
	public function initSeq($id_field){
		$key = $this->getMemKey();
		$seq = $this->cache->get($key);
		if($seq === false){
			// 如果不存在数据则从id sequence表中取下一次开始的值
			$sql = sprintf("select next_value from id_sequence where id_key='%s'",$this->table_name);
			$value = $this->dbhelper->resultFirst($sql);
			if(empty($value)){
				if($this->data_exists){
					// 如果数据存在，则从数据库中取得最大值
					$sql = sprintf(
						"select ifnull(max(%s),0) max_value from %s", $id_field, $this->table_name);
					$value = $this->dbhelper->resultFirst($sql);
				}
				else{
					// 如果没有初始化id sequence表，则设置开始值
					$value = 0;
					if (isset($this->id_start[$this->table_name])) {
						$value = intval($this->id_start[$this->table_name]);
					}
				}
			}
			$sql = "insert into id_sequence (id_key,current_value,next_value) values('%s',%d,%d) "
				."on duplicate key update current_value=%d,next_value=%d";
			$params = array(
				$this->table_name, $value, $value + $this->step,
				$value, $value + $this->step);
			$this->dbhelper->execute($sql, $params);
			$res = $this->cache->set($key, $value, 0);
			if ($res === false) {
				$this->throwException("id sequence set sequence value failure[value=$value].", FRAMEWORK_ERROR_ID_SEQUENCE_SET_MEMCACHE_FAILED);
			}
			$res = $this->cache->set($key . '_next_value', $value + $this->step, 0);
			if ($res === false) {
				$this->throwException("id sequence set next value failure[next_value=".($value+$this->step)."].", FRAMEWORK_ERROR_ID_SEQUENCE_SET_MEMCACHE_FAILED);
			}
			return $value;
		}
		return $seq;
	}
	
	private function setParam($table_name,$id_field,$data_exists = false){
		if(empty($table_name)){
			throw new Exception("Table name can't be empty.",1);
		}
		$this->table_name = $table_name;
		$this->id_field = $id_field;
		$this->data_exists = $data_exists;
	}
	
	private function updateCache($inc = 1){
		$key = $this->getMemKey();
		$key_next = $key . '_next_value';
		$this->initSeq($this->id_field);
		$seq = $this->cache->increment($key, $inc, 0);
		if ($seq === false) {
			$this->throwException('increase sequence value failure.', FRAMEWORK_ERROR_ID_SEQUENCE_SET_MEMCACHE_FAILED);
		}
		$next_value = $this->cache->get($key_next);
		if ($next_value === false || $seq >= $next_value) {
			$this->commitData($seq);
			$res = $this->cache->increment($key_next, $this->step, 0);
			if ($res === false) {
				$this->throwException('failed to increase next value by step['.$this->step.'].', FRAMEWORK_ERROR_ID_SEQUENCE_SET_MEMCACHE_FAILED);
			}
		}
		
		return $seq;
	}
	
	private function commitData($value){
		$sql = "update id_sequence set current_value=%d,next_value=next_value+%d where id_key='%s'";
		$this->dbhelper->execute($sql,
			array($value, $this->step, $this->table_name));
	}
	private function getMemKey(){
		return $this->key_prefix . $this->table_name;
	}
	private function throwException($message,$code){
		throw new Exception($message,$code);
	}
}

?>