<?php
include '../config.inc.php';
include './admins.php';
include FRAMEWORK . '/mvc/Template.class.php';
define('ADMIN_CONTROL', APP_ROOT . '/admincp/control');
define('TEMPLATE_DATA_DIR', APP_ROOT . '/cache/templates');
define('TEMPLATE_MESSAGE_DIR', APP_ROOT . '/language');
define('TEMPLATE_DIR', APP_ROOT . '/admincp/view');
define('TEMPLATE_EXT', '.htm');
$admin_audit_logger = LogFactory::getLogger(array(
    'prefix' => LogFactory::LOG_MODULE_ADMINCP,
    'storage' => 'scribe',
    'log_level' => 1
));
date_default_timezone_set("Asia/Shanghai");
$module = getGPC("mod", "string");
$action = getGPC('act', "string");
//登陆判断
/**if (1) {
    $invalid = invalid();
    if ($invalid === true) {
        $module = 'user';
        $action = 'login';
    }
} else {
    $invalid = invalid2();
}
**/
//默认 tab
if (empty($module) || empty($action)) {
    $module = 'fb';
    $action = 'index';
}
$allow_action = array(
    'index',
    'quit',
    'search',
    'save',
    'login'
);
//$res = load(MODEL_ADMIN_USER) -> addAllowedAction('q1','fb,fbreq;fb,stat;');
if (0) {
    $action = 'limiterror';
    $GLOBALS['TEMPLATE_FILE'] = TEMPLATE_DIR . '/limiterror.htm';
    $error = '您没有权限访问该页面，请及时跟管理员联系。';
    include    renderTemplate($module, $action);
    return;
}
require ADMIN_CONTROL . '/base.php';
$view_datas = array();
$control_file = ADMIN_CONTROL . "/{$module}.php";
if (file_exists($control_file)) {
    require $control_file;
} else {
    $view_file = TEMPLATE_DIR . '/' . $module . '/' . $action . TEMPLATE_EXT;
    if (file_exists($view_file)) {
        include renderTemplate($module, $action);
        return;
    }
}

try {
	if($module=='fb'&&$action=='index'){
		$classname = $module . 'control';
   		 $control = new $classname($request['appid'] );
   		 $method = 'on' . $action;
	}else{
		$classname = $module . 'control';
   		 $control = new $classname();
   		 $method = 'on' . $action;
	}
//    $classname = $module . 'control';
//    $control = new $classname();
//    $method = 'on' . $action;
    if (method_exists($control, $method) && $action{0} != '_') {
        $data = $control -> $method();
    } elseif (method_exists($control, '_call')) {
        $data = $control -> _call('on' . $action, '');
    } else {
        exit('Action not found!');
    }
    if (isset($data)) {
        ob_clean();
        header('Content-Type: application/json; charset=UTF-8');
        $ret = json_encode($data);
    } else {
        header('Content-type: text/html; charset=UTF-8');
    }
} catch (Exception $e) {
    $error_msg = $e -> __toString();
    $ret = json_encode(array(
        'status' => 'ERROR',
        'error_code' => $e -> getCode(),
        'error_msg' => $e -> getMessage()
    ));
}
$callback = getGPC('callback', 'string');
if (!empty($callback))
    $ret = "$callback($ret);";
if (!empty($ret))
    die($ret);
if (!empty($view_datas)) {
    extract($view_datas, EXTR_SKIP);
}

include (renderTemplate($module, $action));
?>