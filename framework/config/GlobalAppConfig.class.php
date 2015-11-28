<?php 
require_once FRAMEWORK . '/config/AppConfig.class.php';
class GlobalAppConfig extends AppConfig {
	
	private static $instance = null;
	
	public static function getInstance() {
		if (is_null(self::$instance)) {
			$config_file = APP_ROOT.'/etc/config.ini';
			// 验证配置文件路径的正确性
			self::checkConfigPath($config_file);
			// 先尝试从缓存中反序列化
			self::$instance = self::getConfigFromCache($config_file);
			if(empty(self::$instance)){
				// 创建一个新的AppConfig对象
				self::$instance = new self($config_file);
				// 将对象序列化后写入缓存
				self::writeConfigToCache($config_file, self::$instance);
			}
		}
		return self::$instance;
	}
}