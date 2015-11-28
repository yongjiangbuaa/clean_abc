<?php
/**
 * 该类用于从缓存中删除一条key的数据。
 * @author shusl
 *
 */
class TCPurgeRequest extends TCRequest {
	/**
	 *
	 */
	public function execute() {
		return $this->deleteFromCache();
	}
}

?>