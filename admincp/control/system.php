<?php
!defined('APP_ROOT') && exit('Access Denied');
class systemcontrol extends base {
	function __construct() {
		parent::__construct();
		$this->load(MODEL_GLOBAL_CONFIG);
	}
	//版本控制
	public function onversion(){
		$type = $this->input('type');
		if ($type == 'list') {
			$page = $_GET['page'];
			if($_GET['rows'] == '') {
				$limit = 10;
			} else {
				$limit = $_GET['rows']; 
			}
			$sidx = $_GET['sidx']; // get index row - i.e. user click to sort 
			$sord = $_GET['sord']; // get the direction 
			if(!$sidx) $sidx =1; // connect to the database 
			$result = $this->load(MODEL_GLOBAL_CONFIG)->getList(GlobalConfigManager::CONFIG_VERSION);
			$count = count($result); 
			if($count >0) { 
				$total_pages = ceil($count/$limit);
			 } else { 
			 	$total_pages = 0; 
			 } 
			 if ($page > $total_pages) $page=$total_pages; 
			 $start = $limit*$page - $limit; // do not put $limit*($page - 1)
			 if($start < 0) $start = 0;
			 $result = $this->load(MODEL_GLOBAL_CONFIG)->getList(GlobalConfigManager::CONFIG_VERSION, $start, $limit);
			 $response = array();
			 $response['page'] = $page; 
			 $response['total'] = $total_pages;
			 $response['records'] = $count; 
			 $i=0; 
			 foreach ($result as $version) {
			 	$arr = explode('_', $version['p_key']);
			 	$key = substr($version['p_key'], strlen(GlobalConfigManager::CONFIG_VERSION));
			 	$url_key = GlobalConfigManager::CONFIG_UPDATE_URL.$key;
			 	$url = $this->load(MODEL_GLOBAL_CONFIG)->getValue($url_key);
			 	$max_version_key = GlobalConfigManager::MAX_CONFIG_VERSION.$key;
			 	$max_version = $this->load(MODEL_GLOBAL_CONFIG)->getValue($max_version_key);
			 	$response['rows'][$i]['cell']=array(
			 		$arr[3], $arr[2], $arr[1],
			 		$version['p_value'], $max_version, $url); 
			 	$i++; 
			 }
			 return $response;
		}
	}
	public function onsave(){
		$operation = $this->input('oper');
		if($operation == 'add' || $operation == 'edit') {
			$channel = $this->input('channel');
			$os = $this->input('os');
			$version = $this->input('version');
			$country = $this->input('country');
			if (!empty($os) && !empty($version)) {
				$key = $os;
				if (!empty($channel)) {
					$key .= '_' . $channel;
				}
				if (!empty($country)) {
					$key .= '_' . $country;
				}
				$version_key = GlobalConfigManager::CONFIG_VERSION.'_' .$key;
				$this->load(MODEL_GLOBAL_CONFIG)->setValue($version_key, $version);
				$url_key = GlobalConfigManager::CONFIG_UPDATE_URL.'_'.$key;
				$url_value = $this->load(MODEL_GLOBAL_CONFIG)->getValue($url_key);
				$update_url = $this->input('update_url');
				if($update_url != $url_value) {
					$this->load(MODEL_GLOBAL_CONFIG)->setValue($url_key, $update_url);
				}
				$max_version_key = GlobalConfigManager::MAX_CONFIG_VERSION.'_'.$key;
				$max_version_value = $this->load(MODEL_GLOBAL_CONFIG)->getValue($max_version_key);
				$max_version = $this->input('max_version');
				if($max_version != $max_version_value) {
					$this->load(MODEL_GLOBAL_CONFIG)->setValue($max_version_key, $max_version);
				}
			}
		}
		if($operation == 'del') {
			$id = $this->input('id');
			$id = rtrim($id, '_');
			if (!empty($id)) {
				$this->load(MODEL_GLOBAL_CONFIG)->remove(GlobalConfigManager::CONFIG_VERSION.'_'.$id);
				$this->load(MODEL_GLOBAL_CONFIG)->remove(GlobalConfigManager::MAX_CONFIG_VERSION.'_'.$id);
				$this->load(MODEL_GLOBAL_CONFIG)->remove(GlobalConfigManager::CONFIG_UPDATE_URL.'_'.$id);
			}
		}
		if($this->input('type') == 'auth') {
			$name = $this->input('name');
			$auth = $this->input('auth');
			$auth = rtrim($auth, ';');
			if(!empty($name) && !empty($auth)) {
				$admins = $this->load(MODEL_ADMIN_USER)->query($name);
				if($auth != $admins['auth']) {
					$res = $this->load(MODEL_ADMIN_USER)->modify(array('name' => $name, 'auth' => $auth));
					if($res) {
						return 1;
					} else {
						return 2;
					}
				} else {
					return 3;
				}
			}
		}
	}
	//查看管理员
	public function onviewadmin() {
		global $actions;
		$admins = $this->load(MODEL_ADMIN_USER)->query();
		$GLOBALS['view_datas'] = array('admins' => $admins);
	}
	//添加管理员
	public function onaddadmin() {
		$type = $this->input('type');
		if($type == 'list') {
			$name = $this->input('name');
			$password = $this->input('password');
			$admins = $this->load(MODEL_ADMIN_USER)->query($name);
			if(!empty($admins['name'])) {
				return 1;
			} else {
				if($this->load(MODEL_ADMIN_USER)->create(array('name' => $name,'password' => $password))) {
					return 2;
				}
			}
			return 3;
		}
	}
	//权限管理
	public function onprivilege() {
		$admins = $this->load(MODEL_ADMIN_USER)->query();
		$GLOBALS['view_datas'] = array('admins' => $admins);
	}
	//修改密码
	public function onpassword() {
		$admins = $this->load(MODEL_ADMIN_USER)->query();
		$GLOBALS['view_datas'] = array('adminname' => $_COOKIE['u'],'admins' => $admins);
		$type = $this->input('type');
		if($type == 'list') {
			$name = $this->input('name');
			$oldpassword = $this->input('oldpassword');
			$newpassword = $this->input('newpassword');
			$admin = $this->load(MODEL_ADMIN_USER)->query(array('name'=>$name,'password'=>$oldpassword));
			if(empty($admin)) {
				return 1;
			}
			if(!empty($newpassword)) {
				if($this->load(MODEL_ADMIN_USER)->modify(array('name'=>$name,'password'=>$newpassword))) {
					return 2;
				} else {
					return 3;
				}
			}
		}
	}
	//重置密码
	public function onresetpwd() {
		$type = $this->input('type');
		if($type == 'list') {
			$name = $this->input('name');
			if(!empty($name)) {
				$admins = $this->load(MODEL_ADMIN_USER)->query($name);
				if(empty($admins)) return 1;
				if($this->load(MODEL_ADMIN_USER)->modify(array('name'=>$name,'password'=>$name))) {
					return 2;
				} else {
					return 3;
				}
			}
		}
	}
	public function onsearch() {
		$type = $this->input('type');
		if($type == 'list') {
			$name = $this->input('name');
			$actions = load(MODEL_ADMIN_USER)->getAllowedActions($name);
			$response = $actions;
			return $response;
		}
	}
	
	public function onmenu() {
		$op = $this->input('op');
		if ($op == 'save') {
			global $actions, $invalid;
			$weights = $this->input('weight');
			$module = $this->input('module');
			foreach ($weights as $key => $value) {
				if ($key != $value) {
					$action = $actions[$module][$key];
					unset($actions[$module][$key]);
					array_splice($actions[$module], $value, 0, $action);
				}
			}
			krsort($actions[$module]);
			$actions_arr = array();
			foreach ($actions as $action_module => $module_actions) {
				foreach ($module_actions as $weight => $module_action) {
					$actions_arr[] = $action_module.','.$module_action.','.$weight;
				}
			}
			$actions_str = implode(';', $actions_arr);
			$admin_name = $invalid['username'];
			if (!empty($admin_name)) {
				$admins = $this->load(MODEL_ADMIN_USER)->query($admin_name);
				if($actions_str != $admins['auth']) {
					$res = $this->load(MODEL_ADMIN_USER)->modify(array('name' => $admin_name, 'auth' => $actions_str));
				}
				if ($res) {
					header('location:admincp.php?mod=system&act=menu');
				}
			}
		}
	}
	//删除管理员
	public function ondeleteadmin() {
		$admin_name = $this->input('admin_name');
		if(empty($admin_name)) {
			return 1;
		} else {
			$admins = $this->load(MODEL_ADMIN_USER)->query($admin_name);
			if(empty($admins)) {
				return 2;
			} else {
				$this->load(MODEL_ADMIN_USER)->remove($admin_name);
				return 3;
			}
		}
	}
	

	
	
	public function onactivities() {
		$op = $this->input('op');
//		$cache_helper = GlobalAppConfig::getInstance()->getDefaultCacheInstance();
		$cache_helper = load(MODEL_ADMIN_USER);
		if ($op == 'add') {
			$name = $this->input('name');
			$code = $this->input('name');
			$pop_1 = $this->input('pop_1');
			if (!empty($pop_1)) {
				$pop_1 = 1;
			} else {
				$pop_1 = 0;
			}
			$pop_2 = $this->input('pop_2');
			if ($pop_1) {
				$pop_2 = 0;
			}
			if (empty($pop_2)) {
				$pop_2 = 0;
			}
			$new = $this->input('new');
			$new = !empty($new) ? 1 : 0;
			$open = $this->input('open');
			$open = !empty($open) ? 1 : 0;
			$sql = "insert into `ra`.`activity_center_config` (`name`, `code`, `pop_1`, `pop_2`, `new`, `open`) 
					values ('$name', '$code', $pop_1, $pop_2, $new, $open)";
			query($sql);
		} elseif ($op == 'delete') {
			$id = $this->input('id');
			query("delete from `ra`.`activity_center_config` where `id` = $id");
		} elseif ($op == 'mdelete') {
			$ids = $this->input('id');
			if (!empty($ids)) {
				foreach ($ids as $id) {
					query("delete from `ra`.`activity_center_config` where `id` = $id");
				}
			}
		} elseif ($op == 'modify') {
			
		} elseif ($op == 'deploy') {
			$data = query_slave("select * from `ra`.`activity_center_config`");
			$res = array();
			foreach ($data as $row) {
				$res[$row['name']] = $row;
			}
			$cache_helper->setToCache(CK_ACTIVIY_CENTER, $res, 0);
		} elseif ($op == 'recover') {
			$sql = "truncate table `ra`.`activity_center_config`";
			query($sql);
			$data = $cache_helper->getFromCache(CK_ACTIVIY_CENTER);
			$sql = "insert into `ra`.`activity_center_config` (`name`, `code`, `pop_1`, `pop_2`, `open`, `new`) values ";
			$values = array();
			foreach ($data as $row) {
				$values[] = "('{$row['name']}', '{$row['code']}', {$row['pop_1']}, {$row['pop_2']}, {$row['open']}, {$row['new']})";
			}
			$values = implode(', ', $values);
			$sql .= $values;
// 			$sql .= " on duplicate key update 
// 					`name` = values(`name`), 
// 					`code` = values(`code`), 
// 					`pop_1` = values(`pop_1`),
// 					`pop_2` = values(`pop_2`), 
// 					`open` = values(`open`), 
// 					`new` = values(`new`)";
			query($sql);
		}
		$GLOBALS['view_datas']['result_1'] = query_slave("select * from `ra`.`activity_center_config`");
		$GLOBALS['view_datas']['result_2'] = $cache_helper->getFromCache(CK_ACTIVIY_CENTER);
	}
	

}