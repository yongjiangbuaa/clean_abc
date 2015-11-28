<?php
!defined('APP_ROOT') && exit('Access Denied');
require_once FRAMEWORK . '/config/GlobalAppConfig.class.php';
class Manager {
	/**
	 * 日志操作类
	 * @var ILogger
	 */
	protected $logger = null;
	protected $table_name = null;
	/**
	 * @var Cache
	 */
	private $cache_handler = null;
	private $app_config = null;
	private $cache_prefix = null;
	private $sid;//服id  serverid
	
	public function __construct($table_name, $app_config = null) {
		if (!empty($table_name)) $this->table_name = $table_name;
		if (!isset($GLOBALS['model_logger'])) {
			$GLOBALS['model_logger'] = LogFactory::getLogger(array(
				'prefix' => LogFactory::LOG_MODULE_MODEL, // 文件名的前缀
				'log_dir' => LOG_DIR, // 文件所在的目录
				'archive' => ILogger::ARCHIVE_YEAR_MONTH, // 文件存档的方式
				'log_level' => 3, 
				'storage' => 'file'
			));
		}
		$this->logger = $GLOBALS['model_logger'];
		$this->sid = getServerID();
		if (isset($app_config)) {
			$this->app_config = $app_config;
		} else {
			$this->app_config = get_app_config($this->sid);
		}
		
		$is_global = $this->app_config->getSection(MODEL_ADMIN_USER);//是否是全服缓存
		if (!$is_global && is_virtualServer($this->sid)){
			$this->cache_prefix = "sid_".$this->sid."_";
		}
	}
	public function getFromCache($key) {
		if ($this->cache_prefix){
			$key = $this->cache_prefix.$key;
		}
        return $this->getCacheHandler()->get($key);
    }
	public function setToCache($key, $value, $expire_time=null) {
		if ($this->cache_prefix){
			$key = $this->cache_prefix.$key;
		}
        $cache_handler = $this->getCacheHandler();
        if ($expire_time === null){
        	$expire_time = $this->getCacheExpireTime();
        }
        return $cache_handler->set($key, $value, $expire_time);
    }
    public function increment($key, $value = 1, $expire_time=null) {
    	if ($this->cache_prefix){
			$key = $this->cache_prefix.$key;
		}
    	$cache_handler = $this->getCacheHandler();
    	if ($expire_time === null){
        	$expire_time = $this->getCacheExpireTime();
        }
    	return $cache_handler->increment($key, $value, $expire_time);
    }
    public function deleteFromCache($key) {
    	if ($this->cache_prefix){
			$key = $this->cache_prefix.$key;
		}
        $cache_handler = $this->getCacheHandler();
        return $cache_handler->delete($key);
    }
    private function getCacheHandler() {
    	if (!isset($this->cache_handler)) {
	    	$this->cache_handler = get_cache_helper_by_config($this->getTableName(), $this->getAppConfig());
    	}
    	return $this->cache_handler;
    }
    private function getCacheExpireTime() {
    	$app_config = $this->getAppConfig();
    	$expire = $app_config->getConfig(
    		$this->getTableName(), AppConfig::TABLE_CACHE_EXPIRE_TIME);
    	if (empty($expire)) {
    		$expire = $app_config->getConfig(
    			AppConfig::TABLE_CACHE_EXPIRE_TIME, $this->getTableName());
    	}
    	return intval($expire);
    }
    
	/**
	 * 修改一条数据
	 *
	 * @param array $new_value，要修改的数据内容，字段名称为key，值为value
	 * @return boolean
	 */
	public function modify($new_value){
		if (empty($new_value)) return false;
		$app_config = $this->getAppConfig();
		$partition_field = $app_config->getPartitionField($this->getTableName());
		if (!empty($partition_field)) {
			$partition_value = $new_value[$partition_field];
			if (empty($partition_value)) throw new Exception('You have to specify partition value to execute sql, or the table information can not be retrieved');
		}
		$fields = $this->getFields();
		if (is_array($fields) && in_array('last_update', $fields) && !isset($new_value['last_update'])) {
			$new_value['last_update'] = time();
		}
		$primary = $this->getPrimary();
		$primary = array_shift($primary);
		if (empty($primary) || empty($new_value[$primary])) {
			$this->logger->writeError('no primary field for table'.$this->getTableName().' will not update table');
			return false;
		}
		if (!empty($partition_value)) {
			$partition_value = $this->preparePartitionValue($partition_field, $partition_value);
		}
		$req=RequestFactory::createUpdateRequest($app_config);
		if (!empty($partition_field)) {
			$req->setKey($partition_field, $partition_value);
			if ($partition_field !== $primary) {
				unset($new_value[$partition_field]);
			}
		}
		$req->addKeyValue($primary,$new_value[$primary]);
		unset($new_value[$primary]);
		$req->setTable($this->getTableName());
		$req->setModify($new_value);
		$req->setCacheType(TCRequest::CACHE_PRIMARY_KEY);
		$req->execute();
		return true;
	}
	/**
	 * 向数据库中插入一条数据
	 *
	 * @param array $change 要插入数据库的一条数据
	 * @return boolean
	 */
	public function create($change, $ignore=false, $on_duplicate_key_update=false){
		if (empty($change))	return false;
		$app_config = $this->getAppConfig();
		$partition_field = $app_config->getPartitionField($this->getTableName());
		if (!empty($partition_field)) {
			$partition_value = $change[$partition_field];
			if (empty($partition_value)) throw new Exception('You have to specify partition value to execute sql, or the table information can not be retrieved');
		}
		$fields = $this->getFields();
		if (is_array($fields) 
				&& in_array('server_id',$fields) 
				&& !isset($change['server_id'])) {
			$change['server_id'] = $this->sid;
		}
		if (is_array($fields) 
				&& in_array('last_update',$fields) 
				&& !isset($change['last_update'])) {
			$change['last_update'] = time();
		}
		if (is_array($fields) 
				&& in_array('timestamp',$fields) 
				&& !isset($change['timestamp'])) {
			$change['timestamp'] = time();
		}
		if (is_array($fields) 
				&& in_array('create_time',$fields) 
				&& !isset($change['create_time'])) {
			$change['create_time'] = time();
		}
		
		$columns=array();
		$values=array();
		foreach ($change as $key=>$value){
			$columns[]=$key;
			$values[]=$value;
		}
		if (!empty($partition_value)) {
			$partition_value = $this->preparePartitionValue($partition_field, $partition_value);
		}
		$req=RequestFactory::createInsertRequest($app_config);
		if (!empty($partition_value)) $req->setKey($partition_field, $partition_value);
		$req->setTable($this->getTableName());
		$req->setColumns('`'.implode('`,`',$columns).'`');
		$req->addValues($values);
		$req->setIgnore($ignore);
		if ($on_duplicate_key_update) {
			if (!is_array($on_duplicate_key_update)) {
				$on_duplicate_key_update = array();
			}
			$req->setOnDuplicateUpdate($on_duplicate_key_update);
		}
		$req->execute();
		return true;
	}
	public function batchModify($values, $fields = null, $partition_value = null, $no_db = false) {
		if (empty($values)) return false;
		if (!is_array($values) || !is_array($values[0])) return false;
		
		if (empty($fields)) {
			throw new Exception('batch modify must specify fields');
		}
		
		$all_fields = $this->getFields();
		
		if (is_array($all_fields) 
				&& in_array('last_update', $all_fields) 
				&& !in_array('last_update', $fields)) {
			foreach (array_keys($values) as $key) {
				$values[$key][] = time();
			}
			$fields[] = 'last_update';
		}
		
		$partition_field = $this->getAppConfig()->getPartitionField($this->getTableName());
		if (!empty($partition_field) && empty($partition_value)) {
			 throw new Exception('You have to specify partition value to execute sql');
		}
		if (!empty($partition_value)) {
			$partition_value = $this->preparePartitionValue($partition_field, $partition_value);
		}
		$req=RequestFactory::createInsertRequest($this->getAppConfig());
		if (!empty($partition_field)) $req->setKey($partition_field, $partition_value);
		$req->setTable($this->getTableName());
		$req->setNoDb($no_db);
		$req->setColumns('`'.implode('`,`', $fields).'`');
		foreach ($values as $value) {
			$req->addValues($value);
		}
		$req->setOnDuplicateUpdate(array());
		$req->execute();
		return true;
	}
	public function batchInsert($values, $fields = null, $partition_value = null, $on_duplicate_key_update=array(), $ignore=false) {
		if (empty($values)) return false;
		if (!is_array($values) || !is_array($values[0])) return false;
		
		if (empty($fields)) {
			throw new Exception('batch insert must specify fields');
		}
		
		$all_fields = $this->getFields();
		if (is_array($all_fields) 
				&& in_array('server_id', $all_fields) 
				&& !in_array('server_id', $fields)) {
			foreach (array_keys($values) as $key) {
				$values[$key][] = $this->sid;
			}
			$fields[] = 'server_id';
		}
		if (is_array($all_fields) 
				&& in_array('last_update', $all_fields) 
				&& !in_array('last_update', $fields)) {
			foreach (array_keys($values) as $key) {
				$values[$key][] = time();
			}
			$fields[] = 'last_update';
		}
		if (is_array($all_fields) 
				&& in_array('timestamp', $all_fields) 
				&& !in_array('timestamp', $fields)) {
			foreach (array_keys($values) as $key) {
				$values[$key][] = time();
			}
			$fields[] = 'timestamp';
		}
		
		$partition_field = $this->getAppConfig()->getPartitionField($this->getTableName());
		if (!empty($partition_field) && empty($partition_value)) {
			 throw new Exception('You have to specify partition value to execute sql');
		}
		if (!empty($partition_value)) {
			$partition_value = $this->preparePartitionValue($partition_field, $partition_value);
		}
		$req=RequestFactory::createInsertRequest($this->getAppConfig());
		if (!empty($partition_field)) $req->setKey($partition_field, $partition_value);
		$req->setTable($this->getTableName());
		$req->setColumns('`'.implode('`,`', $fields).'`');
		foreach ($values as $value) {
			$req->addValues($value);
		}
		$req->setIgnore($ignore);
		$req->setOnDuplicateUpdate($on_duplicate_key_update);
		$req->execute();
		return true;
	}
	public function queryAssoc($key_values = null, $assoc_field = null, $order_by = null) {
		$datas = $this->query($key_values, $order_by);
		$ret = array();
		if (empty($assoc_field)) {
			$primary = $this->getPrimary();
			if (is_array($primary) && count($primary) > 0) {
				$primary = array_shift($primary);
			}
			if (empty($primary)) $primary = "id";
			$assoc_field = $primary;
		}
		foreach ($datas as $data) {
			$ret[$data[$assoc_field]] = $data;
		}
		return $ret;
	}
	/**
	 * 从数据库获取信息
	 * @param mixed $key_values	名称做key，数值做value
	 */
 	public function query($key_values = null, $partition_value = null){
 		if (isset($key_values) && empty($key_values)) {
 			return false;
 		}
 		$app_config = $this->getAppConfig();
		$primary = $this->getPrimary();
		$primary = array_shift($primary);
		$partition_field = $app_config->getPartitionField($this->getTableName());
		if (!empty($partition_field) && empty($partition_value)) {
			if(is_array($key_values)){
				$partition_value = $key_values[$partition_field];
			} else if ($partition_field === $primary) {
				$partition_value = $key_values;
			}
		}
		if (!empty($partition_field) && empty($partition_value)) {
			throw new Exception('partition value not found, can not execute query:table='.$this->getTableName().',key_values='.var_export($key_values,true));
		}
		if (!empty($partition_value)) {
			$partition_value = $this->preparePartitionValue($partition_field, $partition_value);
		}
    	$req = RequestFactory::createGetRequest($app_config);
    	if (!empty($partition_value)) $req->setKey($partition_field, $partition_value);
		$req->setTable($this->getTableName());
		if (!isset($key_values))	{
			return $req->fetchAll();
		}
		
		if (is_array($key_values)) {
			foreach ($key_values as $key=>$value) {
				$key_field = $key;
				if (is_int($key)) {
					if (empty($primary)) {
						$this->logger->writeError('can not retrive primay entry from database['.$this->getTableName().'], it is empty in config.ini');
						return false;
					}
					$key_field = $primary;
				}
				if (is_array($value)) {
					foreach ($value as $v) {
						$req->addKeyValue($key_field, $v);
					}
				} else {
					$req->addKeyValue($key_field, $value);
				}
			}
			//如果是查找主键，或者提供的查询条件大于1个，类似:user_id=xxx and type=xxx
			//这样的查询是不能设置cache_list的，否则会导致后面的查询结果错误
			$keys = array_keys($key_values);
			if (is_int($keys[0])) {
				$req->setCacheType(TCRequest::CACHE_IN_LIST);
			} else if (count($key_values) > 1){
				$req->setCacheType(TCRequest::CACHE_AND_LIST);
			}
			$result_datas=$req->fetchAll();
            return $result_datas;
		}
 		if (empty($primary)) {
			$this->logger->writeError('can not retrive primay entry from database['.$this->getTableName().'], it is empty in config.ini');
			return false;
		}
		$req->addKeyValue($primary, $key_values);
		$req->setCacheType(TCRequest::CACHE_PRIMARY_KEY);
		$result_datas=$req->fetchOne();
        return $result_datas;
    }

    /**
	 * 从数据库删除一条数据
	 * @param mixed $key_values	名称做key，数值做value
	 */
    public function remove($key_values, $partition_value = null){
    	if (empty($key_values))	return false;
    	$app_config = $this->getAppConfig();
    	
    	$primary = $this->getPrimary();
		$primary = array_shift($primary);
		$partition_field = $app_config->getPartitionField($this->getTableName());
		if (!empty($partition_field) && empty($partition_value)) {
			if (!is_array($key_values) && $primary == $partition_field) {
				$partition_value = $key_values;
			}
			if (empty($partition_value)) {
				throw new Exception('You have to specify partition value to execute sql');
			}
		}
    	if (!empty($partition_value)) {
			$partition_value = $this->preparePartitionValue($partition_field, $partition_value);
		}
    	$req=RequestFactory::createDeleteRequest($app_config);
    	if (!empty($partition_field)) $req->setKey($partition_field, $partition_value);
		$req->setTable($this->getTableName());
		
    	if (is_array($key_values)) {
    		$fields = $this->getFields();
			foreach ($key_values as $key=>$value) {
				if (!in_array($key, $fields, true)) {
					$key = $primary;
				}
				$req->addKeyValue($key,$value);
			}
		} else {
			if (empty($primary)) {
				$this->logger->writeError('can not retrive primay entry from database, it is empty in config.ini');
				return false;
			}
			$req->addKeyValue($primary, $key_values);
		}
		$req->setCacheType(TCRequest::CACHE_IN_LIST);//不能删除list
		$req->execute();
		return true;
    }
    public function executeQuery($sql, $partition_value = null, $args = null) {
    	return $this->execute($sql, $partition_value, $args, true);
    }
    public function execute(
    		$sql, $partition_value = null, 
    		$args, $is_query = false, $fetch_one = false) {
    	$app_config = $this->getAppConfig();
		$partition_field = $app_config->getPartitionField($this->getTableName());
		if (!empty($partition_field) && empty($partition_value)) {
			throw new Exception('You have to specify partition value to execute sql');
		}
    	if (!empty($partition_value) && !empty($partition_field)) {
			$partition_value = $this->preparePartitionValue($partition_field, $partition_value);
		}
		$table_server = $app_config->getTableServer($this->getTableName(), $partition_value);
		$db_helper = $table_server->getDBHelperInstance();
		if (!empty($args)) {
			$field_defs = $table_server->getFields();
			foreach ($args as $field => $value) {
				$args[$field] = prepare_db_value($field_defs, $field, $value);
			}
			$sql = vsprintf($sql, array_values($args));
		}
		$sql = str_replace('{table_name}', $table_server->getTableName(), $sql);
		if ($is_query) {
			if ($fetch_one) {
				return $db_helper->fetchOne($sql);
			} else {
				return $db_helper->fetchAll($sql);
			}
		} else {
			$db_helper->execute($sql);
		}
    }
    public function getFields($exclude=null) {
    	$app_config = $this->getAppConfig();
    	$fields = $app_config->getConfig(AppConfig::TABLE_FIELDS, $this->table_name);
    	if (empty($fields)) return false;
    	$fields = array_keys($fields);
		if (!empty($exclude)) $fields = array_diff($fields, $exclude);
		return $fields;
    }
    public function getPrimary() {
    	$app_config = $this->getAppConfig();
    	$fields = $app_config->getConfig(AppConfig::TABLE_PRIMARY_KEY, $this->table_name);
    	if (empty($fields)) return false;
    	$fields = explode(",", $fields);
		return $fields;
    }
    private function preparePartitionValue($partition_field, $partition_value) {
    	$app_config = $this->getAppConfig();
    	$fields = $app_config->getConfig(AppConfig::TABLE_FIELDS, $this->table_name);
    	$field_def = $fields[$partition_field];
    	if ($field_def['type'] == FieldType::TYPE_INTEGER) {
    		return intval($partition_value);
    	}
    	return strval($partition_value);
    }
    protected function getTableName() {
    	return $this->table_name;
    }
    
    protected function getAppConfig() {
    	return $this->app_config;
    }
    
    //配置文件更改，重新加载文件
	public function get_config_file_json($filename){
		$path = APP_ROOT . '/etc/' . $filename . '.json';
		$filetime = filemtime($path);
		$ck = sprintf(CK_HERO_CONFIG_FILE_TIME, $filename);
		$ck2 = sprintf(CK_HERO_CONFIG_FILE, $filename);
		$cache_filetime = load(MODEL_USER)->getFromCache($ck);
		
		$config = load(MODEL_USER)->getFromCache($ck2);
		
		if($filetime != $cache_filetime || empty($config)){
			$config = file_get_contents($path);
			$config = json_decode($config,true);
			load(MODEL_USER)->setToCache($ck, $filetime, 0);
			load(MODEL_USER)->setToCache($ck2, $config, 0);
		}
		return $config;
	}
	
	//判断配置文件是否做了更改，是否需要重新加载文件
	public function config_file_ischange($filename){
		$path = APP_ROOT . '/etc/' . $filename . '.json';
		$filetime = filemtime($path);
		$ck = sprintf(CK_HERO_CONFIG_FILE_TIME, $filename);
		$cache_filetime = load(MODEL_USER)->getFromCache($ck);
		if($filetime != $cache_filetime){
			return true;
		}
		return false;
	}
}
?>
