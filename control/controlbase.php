<?php
!defined('APP_ROOT') && exit('Access Denied');
class  base {
	function __construct($input) {
		if (!empty($input)) $this->input = $input;
		$this->appid = $input['a'];
		$this->fbid = $input['b'];
		$this->uid = $input['c'];
		$this->roleid = $input['d'];

	}
		/**
	 * load manager
	 * @param string $model
	 * @return Manager
	 */
	protected function load($model, $params = null) {
		return load($model, $params);
	}

}
?>