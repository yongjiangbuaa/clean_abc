<?php
!defined('APP_ROOT') && exit('Access Denied');
		
function login($user,$password){
	$admin = load(MODEL_ADMIN_USER)->query($user);
	if(!isset($admin['name'])) {
		return 1;
	}
	if($password != $admin['password']) {
		return 2;
	}
	
	$userMd5 = md5($user);
	setcookie('u',$user, time()+5*3600);
	setcookie('l', $admin['language'], time() + 5*3600);
	setcookie('a1',$userMd5, time()+5*3600);
	setcookie('b2',md5($userMd5.$password.'zoom'), time()+5*3600);
	return 3;
}
function saveUserId($userId, $username, $createtime){
	setcookie('uid', $userId, time()+3600);
	setcookie('uname', $username, time()+3600);
	setcookie('createtime', $createtime, time()+3600);
}
function outUserId(){
	setcookie('uid',"",time()-3600);
	setcookie('uname',"",time()-3600);
}
function userIdValid(){
	if (!isset($_COOKIE['uid'])){
		return false;
	}
	$data['userid'] = $_COOKIE['uid'];
	$data['username'] = $_COOKIE['uname'];
	$data['createtime'] = $_COOKIE['createtime'];
	return $data;
}
function logout(){
	setcookie('u',"", time() - 3600);
	setcookie('l', '', time() - 3600);
	setcookie('a1',"", time() - 3600);
	setcookie('b2',"", time() - 3600);
}
function invalid(){
	if(!isset($_COOKIE['u']) || !isset($_COOKIE['a1']) || !isset($_COOKIE['b2'])){
		//die(500);
		return true;
	}
	$admin_name = $_COOKIE['u'];
	$admin_language = $_COOKIE['l'];
	$admins = load(MODEL_ADMIN_USER)->query($admin_name);
	if(!isset($admins['name'])) {
		return true;
	}
	$userMd5 = md5($admin_name);
	$a1 = $_COOKIE['a1'];
	if($userMd5!=$a1){
		return true;
	}
	$b2 = $_COOKIE['b2'];
	$b2Auth = md5($userMd5.$admins['password'].'zoom');
	
	if($b2!=$b2Auth){
		return true;
	}
	$data['username'] = $admin_name;
	$data['language'] = $admin_language;
	return $data;
}

function invalid2() {
	if(!isset($_GET['u']) && !isset($_GET['p'])) {
		//die(500);
		return true;
	}
	
	$admin_name = $_GET['u'];
	$password = $_GET['p'];
	$admins = load(MODEL_ADMIN_USER)->query($admin_name);
	if(!isset($admins['name'])) {
		return true;
	} elseif ($admins['password'] != $password) {
		return true;
	} else {
		$data['username'] = $admin_name;
		$data['language'] = 'zh_CN';
		return $data;
	}
}

function pre($var, $exit = true)
{
	echo '<pre>';
	if (is_null($var)) var_dump($var);
	elseif (is_bool($var)) var_dump($var);
	elseif (is_int($var)) var_dump($var);
	elseif (is_string($var)) var_dump($var);
	elseif (is_array($var)) print_r($var);
	elseif (is_object($var)) print_r($var);
	echo '</pre><br />'; 
	if ($exit) exit();
}