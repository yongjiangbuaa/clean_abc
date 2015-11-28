<?php

class ElexConfig {

	protected $config_file;
	
	protected $config = null;
	/**
	 * 设置为私有的克隆函数，防止因为使用clone而产生另一个实例
	 * @return void
	 */
	private function __clone(){}
	
	protected function __construct($config_file,$options = null) {
		$this->config_file = $config_file;
		$this->init($options);
	}
	
	protected function init($options){
		if(is_array($options) && array_key_exists('process_sections',$options)){
			$this->config = parse_ini_file($this->config_file,$options['process_sections']);
		}else{
			$this->config = parse_ini_file($this->config_file,true);
		}
	}
	
	/**
	 * 抛出一个ConfigException实例
	 * @param $msg
	 * @param $code
	 * @return void
	 */
	protected static function throwException($msg,$code = 1){
		throw new ConfigException($msg,$code);
	}

	protected static function checkConfigPath($path){
		if (preg_match('/[^a-z0-9\\/\\\\_.: -]/i', $path)) {
            self::throwException('Security check: Illegal character in filename');
        }
        if(!file_exists($path)){
        	self::throwException('config path error:' . $path);
		}
	}
	/**
	 * 获取config的值
	 * @param array $config config数组
	 * @param $key
	 * @param $default
	 * @param $type
	 * @return mixed
	 */
	protected function getValue(array $config,$key,$default,$type = 'int'){
		if(isset($config[$key])){
			if($type == 'bool'){
				if(!isset($config[$key]) || $config[$key] == 'false' || !$config[$key]){
					return false;
				}
				return true;
			}elseif($type == 'array'){
				return (array)$config[$key];
			}
			$num = intval($config[$key]);
			if($num < 1){
				$num = $default;
			}
			return $num;
		}
		return $default;
	}
	
	/**
	 * 尝试从缓存中反序列化得到配置实例
	 * @param $cache_file 序列化的缓存文件
	 * @return 相关的配置信息
	 */
	protected static function getConfigFromCache($config_file) {
		$cache_file = $config_file . '.dat';
		// 如果缓存文件存在，并且修改时间比配置文件的修改时间晚
		if(file_exists($cache_file) &&
			filemtime($cache_file) > filemtime($config_file)) {
			// 从文件缓存取得序列化的对象
			$sc = file_get_contents($cache_file);
			if($sc !== false){
				return unserialize($sc);
			}
		}
		return null;
	}
	/**
	 * 将序列化的结果写入到文件和apc缓存中
	 * @param $config_file
	 * @param $config
	 * @return void
	 */
	protected static function writeConfigToCache($config_file, $config) {
		$cache_file = $config_file . '.dat';
		$sc = serialize($config);
		// 把该对象序列化后保存到缓存文件中
		if(file_put_contents($cache_file, $sc, LOCK_EX) === false) {
			trigger_error('Write config cache file failure.',E_USER_WARNING);
		}
	}
}

class ConfigException extends Exception{
	
}
?>