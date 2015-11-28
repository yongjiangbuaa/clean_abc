<?php
define ('ELEX_LOG_OFF', 0);
define ('ELEX_LOG_DEBUG', 1);
define ('ELEX_LOG_INFO', 2);
define ('ELEX_LOG_ERROR', 3);
define ('ELEX_LOG_FATAL', 4);

interface ILogger{
	/**
	 * log级别常量
	 */
	const LOG_OFF = 0;
	const LOG_DEBUG = 1;
	const LOG_INFO = 2;
	const LOG_ERROR = 3;
	const LOG_FATAL= 4;
	/**
	 * log文件存档的类型常量
	 */
	const ARCHIVE_NONE = 10;
	const ARCHIVE_WEEK = 11;
	const ARCHIVE_MONTH = 12;
	const ARCHIVE_YEAR = 13;
	const ARCHIVE_YEAR_MONTH = 14;
	const ARCHIVE_YEAR_WEEK = 15;
	
	public function writeInfo($message, $params = null);
	
	public function isDebugEnabled();
	public function writeDebug($message, $params = null);
	
	public function writeError($message, $params = null);
	public function writeFatal($message, $params = null);
	/**
	 * Set the log level.
	 *
	 * @param integer $level one of the LOG_OFF,LOG_DEBUG,LOG_INFO and LOG_ERROR
	 */
	public function setLogLevel($level = ELEX_LOG_ERROR);
	public function getLogLevel();
}

class ElexLogger implements ILogger {
	protected $log_level = self::LOG_ERROR;
	/**
	 * Log storage.
	 *
	 * @var LogStorage
	 */
	protected $storage;
	
	public function __construct(LogStorage $storage) {
		$this->storage = $storage;
	}
	
	private function formatMessage($level,$msgFormat, $params = null){
		$message = "[";
		switch ($level){
			case self::LOG_DEBUG:
				$message .= 'debug';
				break;
			case self::LOG_INFO:
				$message .= 'info';
				break;
			case self::LOG_ERROR:
				$message .= 'error';
				break;
			case self::LOG_FATAL:
				$message .= 'fatal';
				break;
			default:
				return '';
		}
		$parts = explode(' ', microtime());
		$message .= ']['.getTimeStamp()."-".(floatval($parts[0])*100000000).'-'.time().'] ';
		if(is_array($params)){
			$message .= vsprintf($msgFormat,$params);
		}
		elseif(!is_null($params)){
			$message .= sprintf($msgFormat,$params);
		}
		else{
			$message .= $msgFormat;
		}
		return $message;
	}
	
	private function write($level,$msgFormat, $params = null){
		$msg = $this->formatMessage($level,$msgFormat,$params);
		return $this->storage->write($msg);
	}
	
	private function writeLine($level,$msgFormat, $params = null){
		$msg = $this->formatMessage($level,$msgFormat,$params);
		return $this->storage->write($msg . "\n");
	}
	
	public function writeInfo($message, $params = null){
		if($this->log_level == self::LOG_OFF || $this->log_level > self::LOG_INFO){
			return false;
		}
		return $this->writeLine(self::LOG_INFO,$message,$params);
	}
	
	public function isDebugEnabled() {
		if($this->log_level == self::LOG_OFF || $this->log_level > self::LOG_DEBUG){
			return false;
		}
		return true;
	}
	
	
	public function writeDebug($message, $params = null){
		if($this->log_level == self::LOG_OFF || $this->log_level > self::LOG_DEBUG){
			return false;
		}
		return $this->writeLine(self::LOG_DEBUG,$message,$params);
	}
	
	public function writeError($message, $params = null){
		if($this->log_level == self::LOG_OFF || $this->log_level > self::LOG_ERROR){
			return false;
		}
		return $this->writeLine(self::LOG_ERROR,$message,$params);
	}
	public function writeFatal($message, $params = null){
		if($this->log_level == self::LOG_OFF){
			return false;
		}
		return $this->writeLine(self::LOG_FATAL,$message,$params);
	}
	/**
	 * @see ILogger::setLogLevel()
	 *
	 * @param integer $level
	 */
	public function setLogLevel($level = LOG_ERROR) {
		if(!in_array($level, array(self::LOG_OFF, self::LOG_DEBUG, 
				self::LOG_INFO, self::LOG_ERROR, self::LOG_FATAL))){
			return false;
		}
		$l = $this->log_level;
		$this->log_level = $level;
		return $l;
	}
	
	public function getLogLevel(){
		return $this->log_level;
	}
}

?>