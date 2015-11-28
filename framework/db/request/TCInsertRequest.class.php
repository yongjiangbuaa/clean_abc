<?php
/**
 * 用来执行insert操作的request
 * @author shusl
 *
 */
class TCInsertRequest extends TCRequest{
	private $ignore = false;
	private $on_duplicate_key_update = false;
	public function execute() {
		if (!$this->no_db) {
			$this->_exec();
			if ($this->logger->isDebugEnabled()) {
				$this->logger->writeDebug($this->affected_rows."rows were affected by insert operation.");
			}
		}
		$this->updateCache();
		return $this->affected_rows;
	}
	
	protected function updateCache(){
		if ($this->getNoCache()) return;
		// 将新的item插入到缓存里边
		$columns = explode(',', str_replace('`', '', $this->getColumns()));
		$primary_key = $this->getPrimaryKey();
		$new_list = array();
		$rows = complete_row($this->table, $this->values, $columns);
		foreach($rows as $row){
			$l = array_intersect_key($row, $primary_key);
			if(!empty($l)){
				$new_list[] = $l;
			}
			$key = $this->getTableMemkey($l);
			$cache = $this->getCacheInstance();
			$cached_row = $cache->get($key);
			if ($cached_row !== false) {
				foreach ($columns as $column) {
					$cached_row[$column] = $row[$column];
				}
				$row = $cached_row;
			}
			
			$this->setRowToCache($row);
		}
		// 如果是key list类型的缓存，则更新key list
		if($this->getCacheType() == self::CACHE_KEY_LIST){
			$this->updateKeyListCache($new_list);
		}
	}
	
	protected function _exec(){
		$sql = $this->getSQL();
		if(empty($sql)){
			return false;
		}
		$db = $this->getTableServer()->getDBHelperInstance();
		$db->executeNonQuery($sql);
		$this->affected_rows = $db->affectedRows();
		return true;
	}
	
	protected function getSQL(){
		if(empty($this->values)) return false;
		$sql = sprintf('INSERT%s INTO %s (%s) values', $this->ignore?' IGNORE':'', $this->getTableServer()->getTableName(), $this->getColumns());
		$columns_as_array = explode(',', str_replace('`', '', $this->getColumns()));
		foreach ($this->values as $values){
			if(is_array($values)){
				$values_sql = '';
				foreach ($values as $key=>$value) {
					if (isset($values_sql[0])) $values_sql .= ',';
					$values_sql .= $this->prepareForSql($columns_as_array[$key], $value);
				}
				$sql .= "($values_sql),";
			}else{
				$sql .= '(' . $this->prepareForSql($columns_as_array[0], $values) . '),';
			}
		}
		$sql = rtrim($sql,',');
		if (is_array($this->on_duplicate_key_update)) {
			$on_duplicate_key_update_sql = '';
			foreach ($columns_as_array as $column) {
				if ($column === 'timestamp') continue;//不要更新创建时间
				if (count($this->on_duplicate_key_update) > 0 && !in_array($column, array_keys($this->on_duplicate_key_update))) continue;
				if (isset($on_duplicate_key_update_sql[0])) $on_duplicate_key_update_sql .= ',';
				if ($this->on_duplicate_key_update[$column] === '+') {
					$on_duplicate_key_update_sql .= "{$column}={$column}+VALUES({$column})";
				} else if ($this->on_duplicate_key_update[$column] === '-') {
					$on_duplicate_key_update_sql .= "{$column}={$column}-VALUES({$column})";
				} else {
					$on_duplicate_key_update_sql .= "{$column}=VALUES({$column})";
				}
			}
			$sql .= ' ON DUPLICATE KEY UPDATE '.$on_duplicate_key_update_sql;
		}
		return $sql;
	}
	
	protected function updateKeyListCache($new_list){
		$list_key = $this->getListCacheKey();
		$cache = $this->getCacheInstance();
		$old_list = $cache->get($list_key);
		// 如果list_cache没有在缓存里，直接返回了。下次直接从数据库取
		// 否则会导致list_cache丢失，只有通过TCGetRequest的请求才能设置list_cache
		if ($old_list === false) return;
		if(!empty($old_list)){
			$new_list = $this->mergeKeyList($old_list, $new_list);
		}
		//有新数据插入才更新缓存
		if (count($new_list) > count($old_list)) {
			// list的缓存保存为不过期
			$cache->set($list_key, $new_list, 0);
		}
	}
	protected function getKeyValue($columns,$value){
		if(!is_array($value)){
			$value = explode(',',$value);
		}
		return array_combine($columns,$value);
	}
	public function addValues($values){
		if(!empty($values)){
			$this->values[] = $values;
		}
	}
	public function setIgnore($ignore = true) {
		$this->ignore = $ignore;
	}
	public function setOnDuplicateUpdate($value = array()) {
		$this->on_duplicate_key_update = $value;
	}
}

?>
