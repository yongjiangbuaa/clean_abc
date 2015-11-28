<?php
require_once FRAMEWORK . '/db/request/TCInsertRequest.class.php';

class TCReplaceRequest extends TCInsertRequest{
	public function __construct(AppConfig $config){
		parent::__construct($config);
		$this->sql_cmd = 'REPLACE';
	}
	protected function updateCache(){
		
	}
}

?>