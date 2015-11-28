<?php
require_once FRAMEWORK . '/errors.const.php';
require_once FRAMEWORK . '/db/FieldType.class.php';
require_once FRAMEWORK . '/config/ElexConfig.class.php';
require_once FRAMEWORK . '/cache/Cache.class.php';
require_once FRAMEWORK . '/common.func.php';

class AppConfig extends ElexConfig {
	/**
	 * 部署时候的分库和分表策略常量定义
	 * 0 不分库，不分表
	 * 1 只分库
	 * 2 只分表
	 * 3 既分库，又分表
	 * @var int
	 */
	const DEPLOY_PART_NONE = 0;
	const DEPLOY_PART_DB = 1;
	const DEPLOY_PART_TABLE = 2;
	const DEPLOY_PART_DB_TABLE = 3;
	const DEPLOY_PART_TIME_WEEK = 4;
	const DEPLOY_PART_TIME_MONTH = 5;
	const DEPLOY_PART_TIME_MONTH_DAY = 7;
	const DEPLOY_PART_SERVER = 6;
	
	const DEFAULT_CACHE_SERVER = 'default_cache_server';
	const DEFAULT_CACHE_SUPER_SERVER = 'default_cache_super_server';
	const DEFAULT_DB_SERVER = 'default_db_server';
	const DEFAULT_DB_SLAVE = 'default_db_slave';
	const DEFAULT_DB_WORKLOAD = 'default_db_workload';
	
	const LOCK_CACHE_SERVER = 'lock_cache_server';
	const STATUS_CACHE_SERVER = 'status_cache_server';
	
	const SERVICE_CLOSED = 'service_is_closed';
	
	const CONFIG_GLOBAL = 'global';
	const DEBUG_MODE = 'debug_mode';
	const PERF_TEST_MODE = 'perf_test_mode';
	const MEMCACHED_CLIENT = 'cache_client';
	const SIGNATURE_KEY = 'sig_key';
	const TIMEZONE = "timezone";
	const APP_NAME = "app_name";
	
	const TABLE_MAX_DB_NUM = 'max_db_num';
	const TABLE_MAX_TABLE_NUM = 'max_table_num';
	const TABLE_DB_MACHINE_NUM = 'db_machine_num';
	const TABLE_DB_SERVER_LIST = 'db_server_list';
	const TABLE_DB_SERVER_CONFIG = 'db_server_config';
	const TABLE_CACHE_SERVER = 'cache_server';
	const TABLE_CACHE_SUPER_SERVER = 'cache_super_server';
	const TABLE_DEPLOY = 'deploy';
	const TABLE_DB_NAME = 'db_name';
	const TABLE_DB_SERVER_INDEX = '_index';
	const TABLE_PRIMARY_KEY = 'primary';
	const TABLE_FIELDS = 'fields';
	const TABLE_CACHE_COMMIT_THRESHOLD = 'cache_commit_threshold';
	const TABLE_READONLY = 'readonly';
	const TABLE_CACHE_TYPE = 'cache_type';
	const TABLE_CACHE_EXPIRE_TIME = 'cache_expire_time';
	const TABLE_NO_CACHE = 'no_cache';
	const ID_SEQUENCE_START = 'id_sequence_start';
	
	
	const DB_MASTER_DSN = 'dsn';
	const DB_SLAVE_DSN = 'slave_dsn';
	const DB_WORKLOAD = 'workload';
	
	protected $debug_mode = false;
	protected $perf_test_mode = false;
	protected $service_is_closed = false;
	
	protected function init($options){
		parent::init($options);
		// 处理包含"|"的section，将其扩展为多个section
		if(is_array($this->config) && !empty($this->config)){
			foreach ($this->config as $section => $values){
				if(strpos($section,'|') !== false){
					$sections = explode('|',$section);
					foreach ($sections as $sec){
						$this->config[trim($sec)] = $values;
					}
					unset($this->config[$section]);
				}
			}
		}
		// 针对fields进行特殊处理，因为fields字段属于比较费时间来进行解析的字段，所以最好将其缓存到APC缓存中去
		$fields_defs_as_str = $this->config[self::TABLE_FIELDS];
		if (isset($fields_defs_as_str)) {
			$fields_defs = array();
			foreach ($fields_defs_as_str as $table_name=>$fields_def_as_str) {
				$fields_def = preg_split('/[:+]/', $fields_def_as_str);
				for ($i = 0, $length=count($fields_def)-1; $i < $length; $i=$i+3) {
					$field_name = $fields_def[$i];
					$field_type = $fields_def[$i+1];
					$default_value = $fields_def[$i+2];
					if (!in_array($field_type, 
							array(FieldType::TYPE_INTEGER,
								FieldType::TYPE_STRING,
								FieldType::TYPE_FLOAT))) {
						$this->throwException("fields definition error:unknown type[".$fields_def[$i+1]."]", FRAMEWORK_ERROR_WRONG_CONFIG);
					}
					$field_props = array("name"=>$field_name, "type"=>$field_type);
					if (strpos($default_value, "function<>") !== false) {
						$arr = explode("<>", $default_value);
						$field_props['default'] = "function";
						$field_props['function'] = $arr[1];
					} else {
						$field_props['default'] = $default_value;
					}
					$fields_defs[$table_name][$field_props['name']] = $field_props;
				}
			}
			$this->config[self::TABLE_FIELDS] = $fields_defs;
		}
		
		// 缓存提交数据库阈值cache_commit_threshold
		$cache_commit_thresholds_as_str = $this->config[self::TABLE_CACHE_COMMIT_THRESHOLD];
		if (isset($cache_commit_thresholds_as_str)) {
			$cache_commit_threshold_defs = array();
			foreach ($cache_commit_thresholds_as_str as $table_name=>$cache_commit_threshold_as_str) {
				$parts = explode(":", $cache_commit_threshold_as_str);
				$cache_commit_threshold_defs[$table_name]['threshold'] = isset($parts[0])?intval($parts[0]):0;
				$cache_commit_threshold_defs[$table_name]['expire'] = isset($parts[1])?intval($parts[1]):1800;
			}
			$this->config[self::TABLE_CACHE_COMMIT_THRESHOLD] = $cache_commit_threshold_defs;
		}
		
		$global_config = $this->config[self::CONFIG_GLOBAL];
		$this->service_is_closed = $this->getValue($global_config,self::SERVICE_CLOSED,false,'bool');
		$this->debug_mode = $this->getValue($global_config,self::DEBUG_MODE,false,'bool');
		$this->perf_test_mode = $this->getValue($global_config,self::PERF_TEST_MODE,false,'bool');
	}
	
	/**
	 * 创建一个AppConfig对象实例
	 * @param $config_file
	 * @param $options
	 * @return AppConfig
	 */
	private static $instances = array();
	public static function getInstance($config_file){
		if(is_null(self::$instances[$config_file])){
			// 验证配置文件路径的正确性
			self::checkConfigPath($config_file);
			// 先尝试从缓存中反序列化
			self::$instances[$config_file] = self::getConfigFromCache($config_file);
			if(empty(self::$instances[$config_file])){
				// 创建一个新的AppConfig对象
				self::$instances[$config_file] = new self($config_file);
				// 将对象序列化后写入缓存
				self::writeConfigToCache($config_file, self::$instances[$config_file]);
			}
		}
		return self::$instances[$config_file];
	}
	
	/**
	 * 是否运行在debug模式下
	 * @return boolean
	 */
	public function isDebugMode(){
		return $this->debug_mode;
	}
	
	/**
	 * 是否运行在性能测试模式下
	 * @return boolean
	 */
	public function isPerfTestMode(){
		return $this->perf_test_mode;
	}
	/**
	 * 判断整个站点的服务是否已经关闭了
	 * @return bool
	 */
	public function isServiceClosed(){
		return $this->service_is_closed;
	}
	/**
	 * 获取一个section的配置
	 * @param $section_name
	 * @return array 如果不存在该section，则返回null
	 */
	public function getSection($section_name){
		if($section_name == MODEL_PLATFORM_USER){//为了虚拟服做的特殊修复
			if(is_virtualServer()){
				$section_name = MODEL_T_PLATFORM_USER;
			}
		}
		if(is_null($this->config) || empty($section_name)){
			return false;
		}
		if(array_key_exists($section_name,$this->config)){
			return $this->config[$section_name];
		}
		return null;
	}
	/**
	 * 返回一条指定section和key的配置
	 * @param $section
	 * @param $config_key
	 * @return mixed
	 */
	public function getConfig($section, $config_key){
		if(empty($config_key)){
			return false;
		}
		if(!empty($section)){
			$sec_val = $this->getSection($section);
			if(empty($sec_val)){
				return false;
			}
			if(array_key_exists($config_key, $sec_val)){
				return $sec_val[$config_key];
			}
		} elseif (array_key_exists($config_key, $this->config)){
			return $this->config[$config_key];
		}
		return null;
	}
	/**
	 * 根据指定key，获取全局的配置项
	 * @param $key
	 * @return string
	 */
	private $global = null;
	public function getGlobalConfig($key) {
		if(is_null($this->global)){
			$this->global = $this->getSection(self::CONFIG_GLOBAL);
		}
		if(array_key_exists($key, $this->global)){
			return $this->global[$key];
		}
		return false;
	}
	
	private $cache = null;
	public function getDefaultCacheInstance(){
		if(is_null($this->cache)){
			$this->cache = get_cache_helper_by_config(self::DEFAULT_CACHE_SERVER, $this);
		}
		return $this->cache;
	}
	/**
	 * 返回默认的数据库操作句柄
	 *
	 * @return DBHelper
	 */
	private $db_helper = null;
	public function getDefaultDbInstance(){
		if(is_null($this->db_helper)){
			require_once FRAMEWORK . '/database/DBHelper.class.php';
			$db_config = $this->getGlobalConfig(AppConfig::DEFAULT_DB_SERVER);
			$this->db_helper = new DBHelper($db_config);
		}
		return $this->db_helper;
	}
	
	/**
	 * 获取一个表的配置服务器信息
	 * @param $table 表的名称
	 * @param $key_value 主键的值,如果不需要分库分表，可以不设置该值
	 * @throws InvalidArgumentException if $table is empty
	 * @return TableServer
	 */
	public function getTableServer($table,$key_value = null){
		if($table == MODEL_PLATFORM_USER){//为了虚拟服做的特殊修复
			if(is_virtualServer()){
				$table = MODEL_T_PLATFORM_USER;
			}
		}
		if(!isset($table[0])){
			throw new InvalidArgumentException('table value empty');
		}
		if(array_key_exists($table, $this->config)){
			return $this->getConfigExistTableServer($table,$key_value);
		}else{
			// 返回默认的配置
			return $this->getDefaultTableServer($table);
		}
	}
	/**
	 * 取得存在配置节的表的TableServer实例
	 * @param $table
	 * @param $key_value
	 * @return TableServer
	 */
	private $table_servers = array();
	protected function getConfigExistTableServer($table,$key_value){
		$table_config = $this->getSection($table);
		$db_table_name = $this->getTableName($table,$table_config,$key_value);
		// 将表的配置缓存起来
		if(isset($this->table_servers[$db_table_name])){
			return $this->table_servers[$db_table_name];
		}
		// 获取数据库配置
		$db_config_key = $table_config[self::TABLE_DB_SERVER_CONFIG];
		if (empty($db_config_key)) {
			$db_index = $this->getDbIndex($table_config,$key_value);
			$db_config_key = $this->getDbConfigKeyByIndex($table_config,$db_index);
		}
		if(empty($db_config_key)){
			$db_config = $this->getDefaultDbConfig();
		}else{
			$db_config = $this->getSection($db_config_key);
		}
		// 将数据库名字添加到配置项中
		$db_name = "";
		$table_name = $db_table_name;
		$arr = explode(".", $db_table_name);
		if (count($arr) == 2) {
			$db_name = $arr[0];
			$table_name = $arr[1];
		}
		$db_config[AppConfig::DB_MASTER_DSN] = sprintf($db_config[AppConfig::DB_MASTER_DSN], $db_name);
		$db_config[AppConfig::DB_SLAVE_DSN] = sprintf($db_config[AppConfig::DB_SLAVE_DSN], $db_name);
		require_once FRAMEWORK . '/db/TableServer.class.php';
		if (empty($table_config[AppConfig::TABLE_CACHE_SERVER])) {
			$table_config[AppConfig::TABLE_CACHE_SERVER] = $this->getGlobalConfig(AppConfig::DEFAULT_CACHE_SERVER);
		}
		if (empty($table_config[AppConfig::TABLE_CACHE_SUPER_SERVER])) {
			$default_super_cache_server = $this->getGlobalConfig(AppConfig::DEFAULT_CACHE_SUPER_SERVER);
			if (!empty($default_super_cache_server)) {
				$table_config[AppConfig::TABLE_CACHE_SUPER_SERVER] = $default_super_cache_server;
			}
		}
		$svr = new TableServer(
			$db_config[AppConfig::DB_MASTER_DSN],
			$table_config[AppConfig::TABLE_CACHE_SERVER],
			$db_name,
			$table_name,
			$db_config[AppConfig::DB_SLAVE_DSN],
			$table_config[AppConfig::TABLE_CACHE_SUPER_SERVER]);
			$svr->setWorkload($db_config[AppConfig::DB_WORKLOAD]);
		if ($GLOBALS['database_logger']->isDebugEnabled()) {
			$GLOBALS['database_logger']->writeDebug("initialized table server $db_table_name");
		}
		// 设置表的主键
		$primary_keys = $this->getSection(AppConfig::TABLE_PRIMARY_KEY);
		if(isset($primary_keys[$table])){
			$svr->setPrimary($primary_keys[$table]);
		}
		// 设置表是否只读
		if(isset($table_config[AppConfig::TABLE_READONLY])){
			$svr->setReadonly($this->getValue($table_config,AppConfig::TABLE_READONLY,false,'bool'));
		}
		//如果没有显式设置不需要缓存，则默认缓存，需要设置缓存类型和过期时间
		if($table_config[AppConfig::TABLE_NO_CACHE] != 1) {
			// 设置表的缓存类型
			if(isset($table_config[AppConfig::TABLE_CACHE_TYPE])){
				$svr->setCacheType($table_config[AppConfig::TABLE_CACHE_TYPE]);
			} else {
				$svr->setCacheType(TCRequest::CACHE_PRIMARY_KEY);
			}
			// 设置表的缓存时间
			$cache_expire_time = $table_config[AppConfig::TABLE_CACHE_EXPIRE_TIME];
			if (empty($cache_expire_time)) {
				$cache_expire_time = $this->getSection(AppConfig::TABLE_CACHE_EXPIRE_TIME);
				$cache_expire_time = $cache_expire_time[$table];
			}
			$svr->setCacheExpireTime(intval($cache_expire_time));
		} else {
			$svr->setNoCache();
		}
		// 设置表的字段
		$fields_defs = $this->getSection(AppConfig::TABLE_FIELDS);
		if(isset($fields_defs[$table])){
			$svr->setFields($fields_defs[$table]);
		}
		// 设置提交阈值
		$cache_commit_threshold = $this->getSection(AppConfig::TABLE_CACHE_COMMIT_THRESHOLD);
		if (isset($cache_commit_threshold[$table])) {
			$svr->setCommitThreshold($cache_commit_threshold[$table]['threshold']);
			$svr->setCommitThresholdExpire($cache_commit_threshold[$table]['expire']);
		}
		$this->table_servers[$db_table_name] = $svr;
		return $svr;
	}
	/**
	 * 取得默认的配置信息
	 * @param $table
	 * @return TableServer
	 */
	private $default_table_server;
	protected function getDefaultTableServer($table){
		if(!isset($this->default_table_server)) {
			$global = $this->getSection(AppConfig::CONFIG_GLOBAL);
			require_once FRAMEWORK . '/db/TableServer.class.php';
			$this->default_table_server = new TableServer(
				$global[AppConfig::DEFAULT_DB_SERVER], // 默认的数据库服务器dsn
				$global[AppConfig::DEFAULT_CACHE_SERVER], // 默认的缓存服务器
				'',//数据库名称
				$table,	// 表名称
				$global[AppConfig::DEFAULT_DB_SLAVE], // 默认的从数据库服务器
				// 迁移或者扩容时候，当前缓存服务器的源缓存服务器组
				$global[AppConfig::DEFAULT_CACHE_SUPER_SERVER]
			);
			$this->default_table_server->setWorkload($global[AppConfig::DEFAULT_DB_WORKLOAD]);
		}
		return $this->default_table_server;
	}
	
	protected function getDefaultDbConfig(){
		$global = $this->getSection(self::CONFIG_GLOBAL);
		$config = array();
		$config[AppConfig::DB_MASTER_DSN] = $global[AppConfig::DEFAULT_DB_SERVER];
		$config[AppConfig::DB_SLAVE_DSN] = $global[AppConfig::DEFAULT_DB_SLAVE];
		$config[AppConfig::DB_WORKLOAD] = $global[AppConfig::DEFAULT_DB_WORKLOAD];
		return $config;
	}
	
	/**
	 * 根据表的配置文件，确定需要访问的数据库配置的key
	 * @param $table_config
	 * @param $index
	 * @return string
	 */
	protected function getDbConfigKeyByIndex($table_config,$index){
		if(is_null($index)){
			return null;
		}
		$svr_list_str  = $table_config[AppConfig::TABLE_DB_SERVER_LIST];
		$svr_list = explode(',',$svr_list_str);
		foreach($svr_list as $svr){
			$svr = trim($svr);
			$nums = expand_num_list($table_config[$svr . AppConfig::TABLE_DB_SERVER_INDEX]);
			if(in_array($index,$nums)){
				return $svr;
			}
		}
		$this->throwException('db config not found');
		return null;
	}
	
	/**
	 * 根据配置文件中的分库，分表策略，确定要执行的表的名称
	 * @param $table
	 * @param $table_config
	 * @param $key_value
	 * @return string
	 */
	protected function getTableName($table,$table_config,$key_value = null){
		$db_name = $table_config[AppConfig::TABLE_DB_NAME];
		$db_max_num = intval($table_config[AppConfig::TABLE_MAX_DB_NUM]);
		if($db_max_num < 1){
			$db_max_num = 1;
		}
		$table_max_num = intval($table_config[AppConfig::TABLE_MAX_TABLE_NUM]);
		if($table_max_num < 1){
			$table_max_num = 1;
		}
		if (is_int($key_value)) {
			$tmp = $key_value;
		} else if (!empty($key_value)) {
			$tmp = abs(crc32(strval($key_value)));
		}
		switch ($table_config[AppConfig::TABLE_DEPLOY]){
			case AppConfig::DEPLOY_PART_DB:
				// 只分库
				$idx = intval($tmp / $table_max_num) % $db_max_num;
				$table_name =  sprintf('%s%d.%s',$db_name,$idx,$table);
				break;
			case AppConfig::DEPLOY_PART_TABLE:
				// 只分表
				$idx = intval($tmp % $table_max_num);
				$table_name =  sprintf('%s.%s_%d',$db_name,$table,$idx);
				break;
			case AppConfig::DEPLOY_PART_DB_TABLE:
				// 既分库，又分表
				$db_idx = intval($tmp / $table_max_num) % $db_max_num;
				$table_idx = intval($tmp % $table_max_num);
				$table_name =  sprintf('%s%d.%s_%d',$db_name,$db_idx,$table,$table_idx);
				break;
			case AppConfig::DEPLOY_PART_TIME_WEEK:
				// 按照周进行分表
				$table_name =  sprintf('%s.%s_%s',$db_name,$table,date('W'));
				break;
			case AppConfig::DEPLOY_PART_TIME_MONTH:
				// 按照月进行分表
				$tmp = $key_value;
				if (empty($tmp)) {
					$tmp = time();
				}
				$tmp = date("m", $tmp);
				$tmp = '2014_'.$tmp;//月表为12个，为兼容老服历史数据，这里做特殊处理
				$table_name =  sprintf('%s.%s_%s', $db_name, $table, $tmp);
				break;
			case AppConfig::DEPLOY_PART_TIME_MONTH_DAY:
				// 按照月内进行分表，1个月内第n天
				$tmp = $key_value;
				if (!empty($tmp)) {
					$tmp = date("j", $tmp);
					$table_name =  sprintf('%s.%s_%s',$db_name,$table,$tmp);
				}else{//兼容老版本的数据回放
					$table_name = sprintf('%s.%s',$db_name,$table);
				}
				break;
			case AppConfig::DEPLOY_PART_SERVER:
				// 按照服进行分表
				$tmp_serverid = getServerID();
				if(is_virtualServer($tmp_serverid)){
					$table_name =  sprintf('%s.%s_%d', $db_name, $table, $tmp_serverid);
					break;
				}
			default:
				// 既不分库，也不分表
				$table_name = sprintf('%s.%s',$db_name,$table);
				break;
		}
		return $table_name;
	}
	
	protected function getDbIndex($table_config,$key_value){
		$idx = null;
		$db_max_num = intval($table_config[AppConfig::TABLE_MAX_DB_NUM]);
		if($db_max_num < 1){
			$db_max_num = 1;
		}
		$table_max_num = intval($table_config[AppConfig::TABLE_MAX_TABLE_NUM]);
		if($table_max_num < 1){
			$table_max_num = 1;
		}
		switch ($table_config[AppConfig::TABLE_DEPLOY]){
			case AppConfig::DEPLOY_PART_DB:
			case AppConfig::DEPLOY_PART_DB_TABLE:
			case AppConfig::DEPLOY_PART_TIME_WEEK:
				$idx = intval($key_value / $table_max_num) % $db_max_num;
				break;
		}
		return $idx;
	}
	
	/**
	 * 根据指定的模块，获取日志的级别
	 * 可以记录log日志的模块有：amf_entry, model, actions, web_entry, framework
	 * @param $key
	 * @return string
	 */
	public function getLogLevel($mod) {
		$log_level = $this->getGlobalConfig("log_level_$mod");
		if (empty($log_level)) $log_level = ELEX_LOG_OFF;
		return $log_level;
	}
	
	/**
	 * 获取配置的时区
	 * @return string
	 */
	public function getTimeZone(){
		$tz = $this->getConfig(AppConfig::CONFIG_GLOBAL,self::TIMEZONE);
		return $tz ? $tz : 'Asia/Shanghai';
	}
	
	/**
	 * 获取应用名
	 * @return string
	 */
	public function getAppName(){
		$app_name = $this->getConfig(AppConfig::CONFIG_GLOBAL,self::APP_NAME);
		return $app_name;
	}
	
	/**
	 * 返回默认的数据库操作句柄
	 *
	 * @return DBHelper
	 */
	private $db_instances = array();
	public function getDbInstance($config_section){
		$ret = $this->db_instances[$config_section];
		if (empty($ret)) {
			require_once FRAMEWORK . '/database/DBHelper.class.php';
			$db_config = $this->getSection($config_section);
			$ret = new DBHelper($db_config[AppConfig::DB_MASTER_DSN]);
			$this->db_instances[$config_section] = $ret;
		}
		return $ret;
	}
	
	/**
	 * 返回表的分库分表字段
	 * @return mixed
	 */
	public function getPartitionField($table_name) {
		$partition_field_defs = $this->getSection('partition_field');
		if(isset($partition_field_defs[$table_name])){
			return $partition_field_defs[$table_name];
		}
		return false;
	}
}
?>