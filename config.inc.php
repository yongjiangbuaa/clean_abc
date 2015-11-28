<?php
error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
define('APP_ROOT', realpath(dirname(__FILE__)));
define('LOG_DIR', '/home/weblog/');
define('FRAMEWORK', APP_ROOT . '/framework');
define('MODEL', APP_ROOT . '/model');

$GLOBALS['THRIFT_ROOT'] = FRAMEWORK . '/thrift';

//定义模块类型
define('SERVER_ID',1);
define('MODEL_FBDATA', 'fbdata');
define('MODEL_FBREQ', 'fb_req');
define('MODEL_FBPOST', 'fb_post');
define('MODEL_ADMIN_USER', 'admin_user');
define('MODEL_USER_TASKS', 'user_tasks');
define('MODEL_DEVICE_DATA', 'device_data');
//错误码定义
define('ERROR_MEMCACHE', 21);
define('ERROR_MODULE_NOT_FOUND',22);
//memcache key
define('CK_XPUB_APPCFG','appcfg_%s');//服务器消息版本
define('CK_XPUB_USERCFG','usercfgV_I_P%sV_I_P%sV_I_P%s');//客户端消息版本
define('CK_XPUB_CPB','%d_cpb_%d_%s');//CPB推广点击记录
define('CK_AWARD_CHECK','award_check_%s_%s_%s_%s');//领奖验证。只许一次
define('CK_AWARD_REQ_FROM_ME','award_fromme_%s_%s_%s_%s');//领奖请求发自我。5分钟失效
define('CK_USER_CLIENT_VERSION','user_client_%s_%s');//记录用户的客户端版本
define('CK_USER_TASK_DONE','user_done_%s_%s_%s_%s');//记录玩家任务达成情况  用来判断领奖小红点亮起。以及是否可以修改任务状态
define('CK_USER_PROMOTE_NUM','prom_num_%s_%s');//记录玩家appid_uid带来的人数
define('CK_USER_PROMOTE','prom_%s_%s_%s');//记录玩家appid_uid_roleid是由谁带来 



//客户单请求时减少协议数据量用
$GLOBALS['WRAP_DEFS'] = array(
MODEL_FBDATA => array(
	'a' => 'appid',
	'b' => 'fbid',
	'c' => 'uid',
	'k' => 'accessToken',
	'd' => 'roleid',
	'i' => 'isFan',
	),
MODEL_FBREQ => array(
	'a' => 'appid',
	'b' => 'fbid',
	'r' => 'reqid',
	't' => 'to_fbids'
	),
MODEL_FBPOST => array(
	'a' => 'appid',
	'b' => 'fbid',
	'p' => 'postid',
	),
);

//应用秘钥定义
$GLOBALS['APP_DEFS'] = array(
	'gyjdxpubmm' => '274' ,
	'J52wDbsjz9wx' => '3760' ,
	'IqdepRptjZX3' => '3837' ,
	'5HugGrmD' => '4061',
	'8pSi$ZEy' =>'4104'
);

//应用与名字  后台显示用
$GLOBALS['APP_TO_NAME'] = array(
  '3760' => '怪谈新三国',
  '3837' => '复仇英雄',
  '4104'=>'TitanWars',//
  '4061'=>'BackToWar',
);

//应用与服映射
$GLOBALS['APP_TO_SERVER'] = array(
  '274' => 1 ,//示例1服
  '3760' => 2 ,//怪谈进2服
  '3837'=>1,//复仇1服
);

//注意：1.该配置返回客户端时必须为数组  2.客户端3.0.4之前必须要pos连续，否则崩断
$GLOBALS['TASKS_DEF'] =array (
  'fb_invite_1' => 
  array (
    'taskid' => 1,
    'pos' => 1,
    'name' => 'fb_invite_1',
    'taskdesc' => '邀請1個FB好友',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 1,
  ),
  'fb_invite_5' => 
  array (
    'taskid' => 2,
    'pos' => 2,
    'name' => 'fb_invite_5',
    'taskdesc' => '邀請5個FB好友',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 5,
  ),
  'fb_invite_10' => 
  array (
    'taskid' => 3,
    'pos' => 3,
    'name' => 'fb_invite_10',
    'taskdesc' => '邀請10個FB好友',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 10,
  ),
  'fb_invite_30' => 
  array (
    'taskid' => 4,
    'pos' => 4,
    'name' => 'fb_invite_30',
    'taskdesc' => '邀請30個FB好友',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 30,
  ),
  'friends_join_1' => 
  array (
    'taskid' => 5,
    'pos' => 5,
    'name' => 'friends_join_1',
    'taskdesc' => '1個FB好友成功加入',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 1,
  ),
  'friends_join_3' => 
  array (
    'taskid' => 6,
    'pos' => 6,
    'name' => 'friends_join_3',
    'taskdesc' => '3個FB好友成功加入',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 3,
  ),
  'friends_join_10' => 
  array (
    'taskid' => 7,
    'pos' => 7,
    'name' => 'friends_join_10',
    'taskdesc' => '10個FB好友成功加入',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 10,
  ),
  'friends_join_30' => 
  array (
    'taskid' => 8,
    'pos' => 8,
    'name' => 'friends_join_30',
    'taskdesc' => '30個FB好友成功加入',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 30,
  ),
  'fb_like' => 
  array (
    'taskid' => 10,
    'pos' => 9,
    'name' => 'fb_like',
    'taskdesc' => '粉絲團點讚',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fblike',
    'goal' => 1,
  ),
  'first_pay_offline' => 
  array (
    'taskid' => 11,
    'pos' => 10,
    'name' => 'first_pay_offline',
    'taskdesc' => '首次使用線下儲值',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'https://www.facebook.com/xggsg',
    'goal' => 1,
  ),
  'first_pay_webatm' => 
  array (
    'taskid' => 12,
    'pos' => 11,
    'name' => 'first_pay_webatm',
    'taskdesc' => '首次使用WebATM儲值',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'https://www.facebook.com/xggsg',
    'goal' => 1,
  ),
  'app_start' => 
  array (
    'taskid' => 9,
    'pos' => 12,
    'name' => 'app_start',
    'taskdesc' => '下載並啟動盤古戰神',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 1,
    'from_apps' => array(3760,274) ,
    'to_app' => 3837,
  ),
  'daily_share' => 
  array (
    'taskid' => 13,
    'pos' => 13,
    'name' => 'daily_share',
    'taskdesc' => '每日分享',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://share',
    'goal' => 1,
  ),
  'promo_join_1' => 
  array (
    'taskid' => 14,
    'pos' => 14,
    'name' => 'promo_join_1',
    'taskdesc' => '1人通过分享链接加入',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://share',
    'goal' => 1,
  ),
  'promo_join_3' => 
  array (
    'taskid' => 15,
    'pos' => 15,
    'name' => 'promo_join_3',
    'taskdesc' => '3人通过分享链接加入',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://share',
    'goal' => 3,
  ),
  'promo_join_10' => 
  array (
    'taskid' => 16,
    'pos' => 16,
    'name' => 'promo_join_10',
    'taskdesc' => '10人通过分享链接加入',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://share',
    'goal' => 10,
  ),
  'promo_join_30' => 
  array (
    'taskid' => 17,
    'pos' => 17,
    'name' => 'promo_join_30',
    'taskdesc' => '30人通过分享链接加入',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://share',
    'goal' => 30,
  ),
);
//wx  english quest  
$GLOBALS['TASKS_DEF1'] =array (
  'fb_invite_1' => 
  array (
    'taskid' => 1,
    'pos' => 1,
    'name' => 'fb_invite_1',
    'taskdesc' => 'invite 1 fb friend',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 1,
  ),
  'fb_invite_5' => 
  array (
    'taskid' => 2,
    'pos' => 2,
    'name' => 'fb_invite_5',
    'taskdesc' => 'invite 5 fb friends',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 5,
  ),
  'fb_invite_10' => 
  array (
    'taskid' => 3,
    'pos' => 3,
    'name' => 'fb_invite_10',
    'taskdesc' => 'invite 10 fb friends',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 10,
  ),
  'fb_invite_30' => 
  array (
    'taskid' => 4,
    'pos' => 4,
    'name' => 'fb_invite_30',
    'taskdesc' => 'invite 30 fb friends',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 30,
  ),
  'friends_join_1' => 
  array (
    'taskid' => 5,
    'pos' => 5,
    'name' => 'friends_join_1',
    'taskdesc' => '1 fb friend enter game',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 1,
  ),
  'friends_join_3' => 
  array (
    'taskid' => 6,
    'pos' => 6,
    'name' => 'friends_join_3',
    'taskdesc' => '3 fb friend enter game',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 3,
  ),
  'friends_join_10' => 
  array (
    'taskid' => 7,
    'pos' => 7,
    'name' => 'friends_join_10',
    'taskdesc' => '10 fb friend enter game',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 10,
  ),
  'friends_join_30' => 
  array (
    'taskid' => 8,
    'pos' => 8,
    'name' => 'friends_join_30',
    'taskdesc' => '30 fb friend enter game',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 30,
  ),
  'fb_like' => 
  array (
    'taskid' => 10,
    'pos' => 9,
    'name' => 'fb_like',
    'taskdesc' => 'fb like',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fblike',
    'goal' => 1,
  ),
  'first_pay_offline' => 
  array (
    'taskid' => 11,
    'pos' => 10,
    'name' => 'first_pay_offline',
    'taskdesc' => 'first rechareged',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'https://www.facebook.com/xggsg',
    'goal' => 1,
  ),
  'first_pay_webatm' => 
  array (
    'taskid' => 12,
    'pos' => 11,
    'name' => 'first_pay_webatm',
    'taskdesc' => 'first WebATM rechareged',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'https://www.facebook.com/xggsg',
    'goal' => 1,
  ),
  'app_start' => 
  array (
    'taskid' => 9,
    'pos' => 12,
    'name' => 'app_start',
    'taskdesc' => 'download and start pangu',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://fbinvite',
    'goal' => 1,
    'from_apps' => array(3760,274) ,
    'to_app' => 3837,
  ),
  'daily_share' => 
  array (
    'taskid' => 13,
    'pos' => 13,
    'name' => 'daily_share',
    'taskdesc' => 'daily share',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://share',
    'goal' => 1,
  ),
  'promo_join_1' => 
  array (
    'taskid' => 14,
    'pos' => 14,
    'name' => 'promo_join_1',
    'taskdesc' => '1 friend enter game via share link',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://share',
    'goal' => 1,
  ),
  'promo_join_3' => 
  array (
    'taskid' => 15,
    'pos' => 15,
    'name' => 'promo_join_3',
    'taskdesc' => '3 friends enter game via share link',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://share',
    'goal' => 3,
  ),
  'promo_join_10' => 
  array (
    'taskid' => 16,
    'pos' => 16,
    'name' => 'promo_join_10',
    'taskdesc' => '10 friends enter game via share link',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://share',
    'goal' => 10,
  ),
  'promo_join_30' => 
  array (
    'taskid' => 17,
    'pos' => 17,
    'name' => 'promo_join_30',
    'taskdesc' => '30 friends enter game via share link',
    'img' => 'http://payment.eleximg.com/payment/xpub/taskicon_henping.png',
    'action' => 'xpub://share',
    'goal' => 30,
  ),
);


//各游戏发奖地址
$GLOBALS['AWARD_URL']=array(
3760 => 'http://gtxsg2.elexapp.com/sanguo/api/elex/popup/award',
274 => 'https://xpub.337.com/api.php',
4104 => 'http://us.papasg.com/fb_reward.php',
4061 => 'http://121.40.238.226/p13/pay/award_337.php',
);

//各游戏首储通知回调地址
$GLOBALS['FIRSTPAY_VERIFY_URL']=array(
3760 => 'http://gtxsg2.elexapp.com/sanguo/api/elex/popup/verify',
//3760 => 'http://localhost/api.php',
274 => 'https://xpub.337.com/api.php',
4061 => 'http://121.40.238.226/p13/pay/firstpay_337_check.php',
);

//业务逻辑开始
require_once FRAMEWORK. '/common.func.php';
require_once APP_ROOT. '/common.func.php';
require_once FRAMEWORK . '/config/AppConfig.class.php';
require_once FRAMEWORK . '/log/LogFactory.class.php';
require_once FRAMEWORK . '/db/RequestFactory.class.php';
?>