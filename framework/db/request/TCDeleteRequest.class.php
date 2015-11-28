<?php
/**
 * 目前TCDeleteRequest只支持
 * DELETE FROM table WHERE user_id=%d
 * 这时候需要设置cache_type为CACHE_KEY_LIST
 * 
 * DELETE FROM table WHERE primary_key=?
 * 
 * DELETE FROM table WHERE primary_key IN (?)
 *
 */
class TCDeleteRequest extends TCRequest{
	/**
	 * 执行删除操作，返回影响的行数。
	 */
	public function execute() {
		$sql = $this->getSQL();
		if(empty($sql)){
			return false;
		}
		$db = $this->getTableServer()->getDBHelperInstance();
		$db->executeNonQuery($sql);
		$this->affected_rows = $db->affectedRows();
		if (!$this->getNoCache()) {
			$this->deleteFromCache();
		}
		return $this->affected_rows;
	}
	
	protected function getSQL(){
		$sql = 'delete from ' . $this->getTableServer()->getTableName();
		$sql .= $this->getWhereExp();
		$sql .= $this->getLimit();
		return $sql;
	}
}

?>