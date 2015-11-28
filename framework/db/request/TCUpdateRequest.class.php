<?php
// 从缓存提交到数据库的阈值, %s是表的名字, %s是主键id
define('CK_COMMIT_THRESHOLD', 'frmct%s%s');
/**
 * TCUpdateRequest更新数据库操作，如果设置了no_db，则只更新缓存.
 * 注意，这个类只能更新单条数据，必须要通过addKeyValue来指定相应的主键值.
 *
 */
class TCUpdateRequest extends TCRequest{
	protected $original_value = null;
	protected $modify = null;
	
	public function reset(){
		parent::reset();
		$this->original_value = null;
		$this->modify = null;
	}
	
	public function execute() {
		if(empty($this->modify) || empty($this->keys)){
			return false;
		}
		$update_db = true;
		$cache_handler = $this->getCacheInstance();
		$primary_key = $this->getPrimaryKey();
		$val = array_intersect_key($this->keys, $primary_key);
		if(count($val) > 1){
			ksort($val);
		}
		$cache_commit_threshold_key = sprintf(CK_COMMIT_THRESHOLD, $this->table, implode("_", $val));
		$cache_commit_count = 0;
		if ($this->no_db) {
			$cache_commit_count = $cache_handler->get($cache_commit_threshold_key);
			$commit_threshold = $this->getCommitThreshold();
			if ($cache_commit_count !== false 
				&& intval($cache_commit_count) + 1 <= $commit_threshold) {
				$update_db = false;
			}
		}
		if ($update_db) {
			$this->updateDb();
			if ($this->no_db) {
				$cache_handler->set($cache_commit_threshold_key, 0, $this->getCommitThresholdExpire());
			}
		} else {
			$cache_commit_count++;
			$cache_handler->set($cache_commit_threshold_key, $cache_commit_count, $this->getCommitThresholdExpire());
			// 不提交数据库，需要将缓存的时间设置为不过期
			$this->setCacheExpireTime(0);
		}
		if (!$this->getNoCache()) {
			$this->updateCache();
		}
		return true;
	}
	
	protected function updateDb(){
		$db = $this->getDBHelperInstance();
		// 更新数据库
		$where = substr($this->getWhereExp(),7);
		$table = $this->getTableServer()->getTableName();
		//针对作物的:种地->施肥->浇水
		//如果施肥阶段更新了grownup_time，写入缓存
		//浇水阶段更新了next_water_time，写入数据库，那么这时候施肥阶段的grownup_time也需要更新
		//所以，如果是no_db，需要将缓存里边的数据和modify合并之后再一并更新
		$modify = $this->modify;
		if ($this->no_db) {
			if ($this->logger->isDebugEnabled()) {
				$this->logger->writeDebug("the commit threshold is reached. trying to update db.");
			}
			$modify = $this->getCompleteData();
		}
		if($this->getCacheType() == self::CACHE_FIELD_ASSOC){
			if(empty($this->assoc_field)){
				$this->throwException('assoc key not set');
			}
			foreach($modify as $key => $entry){
				$db->updatetable($table, $entry, $where . " and $this->assoc_field = $key ");
			}
		}else{
			$db->updatetable($table, $modify, $where);
		}
	}
	
	protected function updateCache(){
		$this->setToCache($this->getCompleteData());
	}
	private function getCompleteData() {
		// 如果没有设置原来的值，则取得原来的值
		if(!isset($this->original_value)){
			$this->original_value = $this->getFromCache();
		}
		if($this->getCacheType() == self::CACHE_FIELD_ASSOC){
			$data = $this->original_value;
			$keys = array_keys($this->modify);
			foreach ($keys as $key){
				if(isset($data[$key])){
					$data[$key] = array_merge($data[$key],$this->modify[$key]);
				}else{
					$data[$key] = $this->modify[$key];
				}
			}
		} else {
			// 如果原来的数据是数组,则将原来的数据和现在的数据合并之后，再设置到缓存中
			if(is_array($this->original_value)){
				$data = array_merge($this->original_value,$this->modify);
			}else{
				$data = $this->modify;
			}
		}
		return $data;
	}
	/**
	 * 设置本次需要修改的数据
	 * @param mixed $modify 需要修改的值，通常是一个数组
	 */
	public function setModify($modify) {
		$this->modify = $modify;
	}
	/**
	 * 设置未修改的数据值
	 * @param $value
	 * @return void
	 */
	public function setOldValue($value){
		$this->original_value = $value;
	}
	/**
	 * 获取未修改的原始值
	 * @return void
	 */
	public function getOldValue(){
		return $this->original_value;
	}
}

?>