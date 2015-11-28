<?php
!defined('APP_ROOT') && exit('Access Denied');
require_once 'Manager.class.php';

class UserPrivilegeManager extends Manager {
	function __construct(){
		parent::__construct(MODEL_ADMIN_USER, GlobalAppConfig::getInstance());
	}
	public function addAllowedAction($uid, $action) {
		if (empty($action)) return;
		$data = $this->query($uid);
		$actions = array();
		if (!empty($data['actions'])) {
			$actions = explode(';',$data['actions']);
		}
		$actions[] = $action;
		$this->setAllowedActions($uid, $actions);
	}
	public function removeAllowedAction($uid, $action) {
		if (empty($action)) return;
		$data = $this->query($uid);
		$actions = array();
		if (!empty($data['actions'])) {
			$actions = explode(';',$data['actions']);
		}
		array_remove($actions, $action);
		$this->setAllowedActions($uid, $actions);
	}
	public function setAllowedActions($uid, $actions) {
		//过滤用户能够设置的权限
		global $login_user;
		$datas = $this->getAllowedActions($login_user->uid);
		$allowed_actions = array();
		foreach ($datas as $module=>$acts) {
			foreach ($acts as $act) {
				$allowed_actions[] = $module.','.$act;
			}
		}
		$actions = array_intersect($actions, $allowed_actions);
		$actions = array_unique($actions);
		$this->create(array('uid'=>$uid, 'actions'=>implode(';', $actions)), false, true);
	}
	public function getAllowedActions($admin_name) {
		global $lang;
		$admins = $this->query(array('name' => $admin_name));
		$ret = array();
		if (!empty($admins['auth'])) {
			$admins['auth'] = trim($admins['auth'], ';');
			$parts = explode(';', $admins['auth']);
			foreach ($parts as $item) {
				$item_arr = explode(',', $item);
				if (count($item_arr) > 2) {
					$module = array_shift($item_arr);
					$action = array_shift($item_arr);
					$weight = array_shift($item_arr);
					$weight = intval($weight);
					if (!isset($lang[$module . '_' . $action])) {
						$weight = 9999;
					}
					if (!isset($ret[$module])) {
						$ret[$module] = array();
					}
					if (!in_array($action, $ret[$module])) {
						array_splice($ret[$module], $weight, 0, $action);
					}
				} else {
					$module = array_shift($item_arr);
					$action = array_shift($item_arr);
					if (!in_array($action, $ret[$module])) {
						$ret[$module][] = $action;
					}
				}
			}
		}
		return $ret;
	}
}
?>