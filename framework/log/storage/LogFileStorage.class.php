<?php
/**
 * Provide file storage implementation of the LogStorage.
 * It store the log in the file system.
 *
 */
class LogFileStorage extends LogStorage {
	/**
	 * Construct
	 *
	 * @param array $options set the options to create log storage handler.
	 * Current supported options as list below:
	 * prefix  : use a string to set the prefix of log file.
	 * log_dir : spesify the log directory. If this directory not exists, it
	 *           will try to create it.
	 */
	public function __construct($options = null){
		if(isset($options['log_dir'])){
			//:is used to resolve windows path
			$this->log_dir = preg_replace('/[^A-Z0-9_\.\/\\\\:-]/i', '',$options['log_dir']);
		}
		if(isset($options['archive'])){
			$this->archive_type = intval($options['archive']);
		}
		if(isset($options['prefix'])){
			$this->setLogFile(
			preg_replace('/[^A-Z0-9_\.-]/i', '',$options['prefix']));
		}
		else{
			$this->setLogFile();
		}
	}
	private $archive_type = null;
	private $log_file;
	private $log_dir;
	/**
	 * @see LogStorage::write()
	 *
	 * @param string $msg
	 */
	public function write($msg) {
		return file_put_contents($this->log_file,$msg, FILE_APPEND | LOCK_EX);
	}
	
	private function setLogFile($prefix = 'stdout'){
		$log_file = '';
		if(isset($this->log_dir[0])){
			if(is_dir($this->log_dir)){
				$log_file .= $this->log_dir;
			}
			else if(mkdir($this->log_dir,0777,true)){
				$log_file .= $this->log_dir;
			}
			$len = strlen($log_file);
			if($len > 0 && $log_file[$len - 1] != DIRECTORY_SEPARATOR &&
			 $log_file[$len - 1] != '/'){
			 	$log_file .= '/';
			}
		}
		$log_file .= $this->getArchiveDir($this->archive_type);
		if(!file_exists($log_file)){
			mkdir($log_file,0777,true);
		}
		$log_file .= $prefix . '_' . date('Y-m-d') . '.log';
		$this->log_file = $log_file;
	}
	
	private function getArchiveDir($archive_type){
		if(empty($archive_type)){
			return '';
		}
		$dir = '';
		switch ($archive_type){
			case ILogger::ARCHIVE_NONE:
				break;
			case ILogger::ARCHIVE_WEEK:
				$dir = date('Y-W/');
				break;
			case ILogger::ARCHIVE_MONTH:
				$dir = date('Y-m/');
				break;
			case ILogger::ARCHIVE_YEAR:
				$dir = date('Y/');
				break;
			case ILogger::ARCHIVE_YEAR_MONTH:
				$dir = date('Y/m/');
				break;
			case ILogger::ARCHIVE_YEAR_WEEK:
				$dir = date('Y/W/');
				break;
		}
		return $dir;
	}
}

?>