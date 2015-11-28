<?php
class LogScribeStorage extends LogStorage {
	private $category = null;
	public function __construct($options = null){
		if (empty($options['prefix'])) {
			$this->category = 'other_log';
		} else {
			$this->category = $options['prefix'];
		}
	}
	/**
	 * @see LogStorage::write()
	 *
	 * @param string $msg
	 */
	public function write($msg) {
		send_scribe_logs(array(
			'category'=>$this->category, 
			'message'=>$msg
		));
	}
}
?>