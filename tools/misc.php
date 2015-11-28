<?php
//!defined('APP_ROOT') && exit('Access Denied');
ini_set('memory_limit', '128M');
ini_set('max_execution_time', 0);
include "../config.inc.php";

//xpub json
$result=post_request('https://xpub.337.com/json.php',array('x2'=>123,'s'=>'gyjdxpubmm','c'=>'elex337_38170082','d'=>'234','force'=>'1'));
echo $result;

//publish config
//$appid='274';
//publish_cfg($appid);
//var_export(load(MODEL_FBDATA)->getFromCache(sprintf(CK_XPUB_APPCFG , $appid)));

//改配置
//$img = 'xxx.png';
//for($i=1;$i<=12;$i++){
//	$update_sql="update tasks_def set img={$img} where taskid={$i};";
//	echo $update_sql.'\n';
//}


?>