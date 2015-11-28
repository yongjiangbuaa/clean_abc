<?php
include_once '../config.inc.php';
include_once FRAMEWORK . '/config/GlobalAppConfig.class.php';
set_time_limit(0) ;

$request = $_REQUEST;
$sid = $GLOBALS['APP_TO_SERVER'][$request['appid']];//首先区分服

$GLOBALS['xpub_logger']->writeinfo('api called .request:'.json_encode($request));
if($request['mod'] == 'award' && $request['act'] == 'check' ){


	// 验证我5分钟内我发出过这样的领奖请求 1.我发出过  2.最近5分钟发的
	$ck_me=sprintf(CK_AWARD_REQ_FROM_ME,$request['appid'],$request['uid'],$request['roleid'],$request['taskid']);
	$isFromMeIn5Min=load(MODEL_DEVICE_DATA)->getFromCache($ck_me);
	if($isFromMeIn5Min === false){
		$ret['status'] = '-5';
		$ret['msg'] = 'I have not sent this,in 5 min!!!'.json_encode($request);
		$GLOBALS['xpub_logger']->writeinfo(json_encode($request).'	'.json_encode($ret));
		echo json_encode($ret);
		exit;
	}else{
		$GLOBALS['xpub_logger']->writeinfo('Yes ! I sent this In 5 Minites!!!'.json_encode($request));
	}


	// 防刷：只许验证通过1次（废弃，下方已注释）。  游戏依赖我们判定发奖，验证通过就认为对方一定会发奖 
	$ck=sprintf(CK_AWARD_CHECK,$request['appid'],$request['uid'],$request['roleid'],$request['taskid']);
	$isCheckTwice=load(MODEL_DEVICE_DATA)->getFromCache($ck);
/**	if($isCheckTwice !== false){
		$ret['status'] = '-4';
		$ret['msg'] = 'award check more than once !!'.json_encode($request);
		$GLOBALS['xpub_logger']->writeinfo(json_encode($request).'	'.json_encode($ret));
		echo json_encode($ret);
		exit;
	}
**/

	//每日任务，数据库里以及memcache用的任务id是daily_share_xxxxx来进行日期控制.但对客户端和游戏通信都用数字编号
	if($request['taskid'] == $GLOBALS['TASKS_DEF']['daily_share']['taskid'] ) $request['taskid'] = "daily_share_".date('Ymd');

	$user_task=load(MODEL_USER_TASKS)->query(array('appid'=>$request['appid'],'uid'=>$request['uid'],'roleid'=>$request['roleid'],'taskid'=>$request['taskid']));
	if(!$user_task[0]) {
		$ret['status'] = '-1';
		$ret['msg'] = 'task not accompleshed！ data:'.json_encode($user_task);
		$GLOBALS['xpub_logger']->writeinfo(json_encode($request).'	'.json_encode($ret));
		echo json_encode($ret);
		exit;
	}
	$user_task = $user_task[0];

	unset($user_task['timestamp']);
	unset($user_task['last_update']);

	switch ($user_task['award']) {
		case '0':
			$ret['status'] = '-1';
			$ret['msg'] = 'not  accomplished！ data:'.json_encode($user_task);
			break;
		case '1':
			$ret['status'] = '0';
			$ret['msg'] = 'ok,can award！data:'.json_encode($user_task);
			load(MODEL_DEVICE_DATA)->setToCache($ck,1,0);
			break;

		case '2':
			$ret['status'] = '-2';
			$ret['msg'] = 'already get adward before！data:'.json_encode($user_task);
			break;
		default:
			$ret['status'] = '-3';
			$ret['msg'] = 'task data exception！ data:'.json_encode($user_task);
			break;
	}
	$GLOBALS['xpub_logger']->writeinfo(json_encode($request).'	'.json_encode($ret));
	echo json_encode($ret);
	exit;
}else if($request['mod'] == 'user' && $request['act'] == 'firstpay' ){
	$params['appid'] = $request['appid'];
	$params['uid'] = $request['uid'];
	$params['roleid'] = $request['roleid'];
	$params['channel'] = $request['channel'];
	$response = post_request($GLOBALS['FIRSTPAY_VERIFY_URL'][$request['appid']],$params);	
	if($response !== 'success'){
		$GLOBALS['xpub_logger']->writeError('callback verify failed!'.json_encode($request));
		echo 'fail';
		exit;
	};
	//首储任务完成
	switch ($request['channel']) {
		case '1':
			$task = $GLOBALS['TASKS_DEF']['first_pay_offline'] ;
			break;
		case '2':
			$task = $GLOBALS['TASKS_DEF']['first_pay_webatm'] ;
			break;
		default:
			break;
	}
	try{
		$records = load(MODEL_DEVICE_DATA)->query(array('appid'=>$request['appid'],'uid'=>$request['uid'],'roleid'=>$request['roleid']));
		if(!$records[0]){ 	//检查 appid_uid_roleid存在
			$GLOBALS['xpub_logger']->writeError('user device data not exist !'.json_encode($request));
			echo 'fail';
			exit;
		}
		$records = load(MODEL_USER_TASKS)->query(array('appid'=>$request['appid'],'uid'=>$request['uid'],'roleid'=>$request['roleid'],'taskid'=>$task['taskid']));
		if($records[0]){ 	//该任已务完成过
			$GLOBALS['xpub_logger']->writeError('user task already exist!'.json_encode($records[0])."\t".json_encode($request));
			echo 'fail';
			exit;
		}
		$user_task = array('appid'=>$request['appid'],'uid'=>$request['uid'],'roleid'=>$request['roleid'],'taskid'=>$task['taskid'],'goal'=>1,'progress'=>1,'award'=>1);
		load(MODEL_USER_TASKS)->create($user_task);
		$GLOBALS['xpub_logger']->writeinfo(json_encode($request).'	   success');
		echo 'success';
	}catch(Exception $e){
		$GLOBALS['xpub_logger']->writeError($e->getMessage().'  '.json_encode($request));
		echo 'fail';
	}
	exit;
}else if($request['mod'] == 'award' && $request['act'] == 'mock'){//模拟发奖

	//调用回调
	/**$params['mod'] = 'award';
	$params['act'] = 'check';
	$params['appid'] = $appid;
	$params['uid'] = $uid;
	$params['roleid'] = $roleid;
	$params['taskid'] = $taskid;
	$response = post_request('https://xpub.337.com/api.php',$award_params);

	$response_array = json_decode($response,true);
	if($response_array['status'] === 0)	{
		//发奖
		echo 'success';
	}else{
		//伪造请求，不发奖
		echo 'fail';	
	} **/
	echo 'success';
	exit;
}
?>