<?php
require_once FRAMEWORK . '/log/Logger.class.php';
require_once FRAMEWORK . '/log/LogStorage.class.php';
/**
 * Factory to create a logger.
 *
 */
class LogFactory {
	//定义可以记录log日志的模块
	const LOG_MODULE_MODEL = 'model';
	const LOG_MODULE_FRAMEWORK = 'framework';
	const LOG_MODULE_DATABASE = 'database';
	const LOG_MODULE_CACHE = 'cache';
	const LOG_MODULE_ADMINCP = 'admincp';
	const LOG_MODULE_OTHER = 'other';
	/**
	 * Create an ILogger implementation instance.
	 *
	 * @param array $options the options for create logger
	 * current supported options list as below:
	 * storage : the storage handler for log( file, DB , etc.). the default
	 *           storage is file.
	 * log_level : the log level. one of the const LOG_OFF,LOG_DEBUG,LOG_INFO,LOG_ERROR.
	 *           The default log level is LOG_ERROR
	 * @return ILogger
	 */
	public static function getLogger($options = null,$app_config=null){
		if(isset($options['storage'])){
			$storage = strtolower(preg_replace('/[^A-Z0-9_\.-]/i', '',$options['storage']));
		}
		else{
			if($app_config == null) $app_config = get_app_config();
			$storage = $app_config->getGlobalConfig('log_storage');
			if ($storage === false) $storage = 'file';
		}
		if(isset($options['log_level'])){
			// set the log level
			$log_level = intval($options['log_level']);
		}
		else{
			// default log level is log error message
			$log_level = ELEX_LOG_ERROR;
		}

		$storageClass = 'Log' . ucfirst($storage) . 'Storage';
		if(!class_exists($storageClass,false)){
			$path = FRAMEWORK . '/log/storage/' . $storageClass . '.class.php';
			if(file_exists($path)){
				require_once $path;
			}
			else{
				// user the file storage
				$storageClass = 'LogFileStorage';
				require_once FRAMEWORK . '/log/storage/LogFileStorage' . '.class.php';
			}
		}
		$storageInstance = new $storageClass($options);

		$instance = new ElexLogger($storageInstance);
		$instance->setLogLevel($log_level);
		return $instance;
	}
}
$GLOBALS['framework_logger'] = LogFactory::getLogger(array(
	'prefix' => LogFactory::LOG_MODULE_FRAMEWORK,
	'log_dir' => LOG_DIR, // 文件所在的目录
	'archive' => ILogger::ARCHIVE_YEAR_MONTH, // 文件存档的方式
	'log_level' => 3,
	'storage' => 'file'
));
$GLOBALS['logger'] = LogFactory::getLogger(array(
	'prefix' => LogFactory::LOG_MODULE_OTHER,
	'log_dir' => LOG_DIR, // 文件所在的目录
	'archive' => ILogger::ARCHIVE_YEAR_MONTH, // 文件存档的方式
	'log_level' => 3,
	'storage' => 'file'
));
$GLOBALS['database_logger'] = LogFactory::getLogger(array(
	'prefix' => LogFactory::LOG_MODULE_DATABASE,
	'log_dir' => LOG_DIR, // 文件所在的目录
	'archive' => ILogger::ARCHIVE_YEAR_MONTH, // 文件存档的方式
	'log_level' => 3,
	'storage' => 'file'
));
$GLOBALS['cache_logger'] = LogFactory::getLogger(array(
	'prefix' => LogFactory::LOG_MODULE_CACHE,
	'log_dir' => LOG_DIR, // 文件所在的目录
	'archive' => ILogger::ARCHIVE_YEAR_MONTH, // 文件存档的方式
	'log_level' => 3,
	'storage' => 'file'
));


?>