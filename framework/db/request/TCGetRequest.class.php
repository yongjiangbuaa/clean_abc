<?php
require_once FRAMEWORK . '/db/TCRequest.class.php';

class TCGetRequest extends TCRequest{
	/**
	 * 从缓存中获取一条数据，如果缓存中不存在，则从数据库中取，并把结果保存到缓存中，除非使用了noCache。
	 * @return array 如果执行成功，返回取得的结果，否则返回false
	 */
	public function execute() {
		return $this->_exec('fetchAll');
	}

	protected function _exec($method,array $args = null){
		$result = false;
		$no_cache = $this->getNoCache();
		if (!$no_cache) $result = $this->getFromCache();
		if($result === false){
			$sql = $this->getSQL();
			$db = $this->getDBHelperInstance();
			if($args === null){
				$result = $db->$method($sql);
			}else{
				// 把SQL作为第一个参数
				array_unshift($args, $sql);
				$result = call_user_func_array(array($db, $method), $args);
			}
			if(!$no_cache){
				$this->setToCache($result);
			}
		}
		return $result;
	}
	
	protected function getSQL(){
		$sql = sprintf('SELECT %s from %s ',$this->getColumns(),
			$this->getTableServer()->getTableName());
		$sql .= $this->getWhereExp();
		$sql .= $this->getLimit();
		return $sql;
	}
	
	public function fetchAll(){
		return $this->_exec('fetchAll');
	}
	
	public function fetchOne(){
		return $this->_exec('fetchOne');
	}
	
	protected function getDBHelperInstance(){
		return $this->getTableServer()->getDBReadHelperInstance();
	}
}

?>