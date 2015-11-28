<?php
require_once FRAMEWORK . '/db/request/TCInsertRequest.class.php';

class TCInsertUpdateRequest extends TCInsertRequest {
	const UPDATE_TYPE_SET = 1;
	const UPDATE_TYPE_ADD = 2;
	const UPDATE_TYPE_SUB = 3;
	
	private $add_update = array();
	private $set_update = array();
	private $sub_update = array();
	
	protected function getSQL(){
		$sql = parent::getSQL();
		if ($sql === false) return false;
		$sql .= $this->getUpdateSql();
		return $sql;
	}
	
	protected function getUpdateSql(){
		$sql = '';
		if(!empty($this->add_update)){
			foreach($this->add_update as $field){
				$sql .= "$field = $field + values($field),";
			}
		}
		if(!empty($this->set_update)){
			foreach($this->set_update as $field){
				$sql .= "$field = values($field),";
			}
		}
		if(!empty($this->sub_update)){
			foreach($this->sub_update as $field){
				$sql .= "$field = $field - values($field),";
			}
		}
		if(empty($sql)){
			return $sql;
		}else{
			return ' ON DUPLICATE KEY UPDATE ' . trim($sql,',');
		}
	}
	
	/**
	 * 设置如果主键存在冲突的情况，需要更新的字段以及更新的类型
	 * @param mixed $field 可以是一个字符串指定一个字段，也可以用一个数组指定一组字段
	 * @param int $type 更新的类型，有以下三类
	 * TCInsertUpdateRequest::UPDATE_TYPE_SET 将原来的值设置为新的值
	 * TCInsertUpdateRequest::UPDATE_TYPE_ADD 将新的值加到原来的值上
	 * TCInsertUpdateRequest::UPDATE_TYPE_SUB 从原来的值减去新的值
	 * @return unknown_type
	 */
	public function addUpdateField($field,$type){
		if(empty($field)){
			return false;
		}
		switch ($type){
			case self::UPDATE_TYPE_ADD:
				if(is_array($field)){
					$this->add_update = array_merge($this->add_update,$field);
				}else{
					$this->add_update[] = $field;
				}
				break;
			case self::UPDATE_TYPE_SUB:
				if(is_array($field)){
					$this->sub_update = array_merge($this->sub_update,$field);
				}else{
					$this->sub_update[] = $field;
				}
				break;
			case self::UPDATE_TYPE_SET:
			default:
				if(is_array($field)){
					$this->set_update = array_merge($this->set_update,$field);
				}else{
					$this->set_update[] = $field;
				}
				break;
		}
		return true;
	}
}

?>