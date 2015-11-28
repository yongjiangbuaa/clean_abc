<?php
!defined('APP_ROOT') && exit('Access Denied');

function query_slave($sql, $fetch_one=false,$app_config = null) {
	if($app_config == null) 
		$app_config = get_app_config();
	$db_handler = $app_config->getDbInstance('slave_db');
	if ($fetch_one) return $db_handler->fetchOne($sql);
	return $db_handler->fetchAll($sql);
}
function query_stats($sql, $fetch_one=false,$app_config = null) {
	if($app_config == null) 
		$app_config = get_app_config();
	$db_handler = $app_config->getDbInstance('stats_db');
	if ($fetch_one) return $db_handler->fetchOne($sql);
	return $db_handler->fetchAll($sql);
}
function query($sql, $fetch_one=false,$app_config = null) {
	if($app_config == null) 
		$app_config = get_app_config();
	$db_handler = $app_config->getDefaultDbInstance();
	if ($fetch_one) return $db_handler->fetchOne($sql);
	return $db_handler->fetchAll($sql);
}
function get_db_table_name($table) {
	$table_server = get_app_config()->getTableServer($table);
	return $table_server->getDbName().'.'.$table_server->getTableName();
}
/**
 * post数据到$url
 *
 * @param string $url
 * @param array $params
 * @param array $headers
 * @param int $timeout
 * @param boolean $curlopt_header
 * @return string
 */
function post_request($url, $params,
		$headers = array("Content-Type: application/x-www-form-urlencoded"),
		$timeout = 30, $curlopt_header = false) {
    $ch = curl_init();
	if (strpos($url, 'https') === 0) {
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	if (is_array($params) && count($params) > 0) {
		$is_multipart = false;
		foreach ($params as $v) {
			if (strpos($v, '@') !== 0) continue;
			$is_multipart = true;
			break;
		}
		if (!$is_multipart) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		} else {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
	} else {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}
	if ($curlopt_header) curl_setopt($ch, CURLOPT_HEADER, true);
	if (is_array($headers) && count($headers) > 0) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
    $result = curl_exec($ch);
    if ($result === false) {
    	$GLOBALS['framework_logger']->writeError("post to url[$url] failed, params=".json_encode($params).", headers=".json_encode($headers).", error=".curl_error($ch));
    }
    curl_close($ch);
    return $result;
}
/**
 * 发送http到$url获取数据
 *
 * @param string $url
 * @param array $params
 * @param array $headers
 * @param int $timeout
 * @param boolean $curlopt_header
 * @return string
 */
function get_request($url, $params=null, $headers=null,
		$timeout = 30, $curlopt_header = false) {
	if (is_array($params) && count($params) > 0) {
		if (strpos($url, '?') !== false) {
			$url .= '&';
		} else {
			$url .= '?';
		}
		$url .= http_build_query($params);
	}
    $ch = curl_init();
    if (strpos($url, 'https') === 0) {
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if ($curlopt_header) curl_setopt($ch, CURLOPT_HEADER, true);
	if (is_array($headers) && count($headers) > 0) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
    $result = curl_exec($ch);
	if ($result === false) {
    	$GLOBALS['framework_logger']->writeError("get from url[$url] failed, params=".json_encode($params).", headers=".json_encode($headers).", error=".curl_error($ch));
    }
    curl_close($ch);
    return $result;
}
/**
 * 通过http下载远程文件
 *
 * @param string $url
 * @param array $params
 * @param array $headers
 * @param int $timeout
 * @return true on success, false on failure
 */
function download_remote_file($url, $local_file, $params=null, $headers=null, $timeout = 30) {
	if (is_array($params) && count($params) > 0) $url .= "?".http_build_query($params);

	$fp = fopen($local_file, 'w');

    $ch = curl_init();
    if (strpos($url, 'https') === 0) {
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	if (is_array($headers) && count($headers) > 0) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
    $result = curl_exec($ch);
	if ($result === false) {
    	$GLOBALS['framework_logger']->writeError("download file from url[$url] failed, params=".json_encode($params).", headers=".json_encode($headers).", error=".curl_error($ch));
    	return false;
    }
    curl_close($ch);
    fclose($fp);
    return true;
}

function list_files($dir_path) {
	if (!is_dir($dir_path)) return false;
	$dir_path = rtrim($dir_path, '/\\');
	$handle = opendir($dir_path);
	$ret = array();
	if ($handle) {
	   	while (false !== ($file = readdir($handle))) {
	   		if ($file == '.' || $file == '..') continue;
	   		$ret[] = $file;
	   	}
	    closedir($handle);
	}
	return $ret;
}
function list_files_absolute_recursive($dir_path, &$ret,$absolute_path=true,$recursive=true) {
	if (!is_dir($dir_path)) return false;
	$dir_path = rtrim($dir_path, '/\\');
	$handle = opendir($dir_path);
	if ($handle) {
	   	while (false !== ($file = readdir($handle))) {
	   		if ($file == '.' || $file == '..') continue;
	   		$ab_file_path=realpath($dir_path).'/'.$file;
	   		if($absolute_path === true) {
	   			$ret[] = realpath($dir_path).'/'.$file;
	   		} else {
	   			$ret[] = $file;
	   		}
	   		if(!is_dir($ab_file_path))continue;
	   		if(is_dir($ab_file_path)&& $recursive === true) list_files_absolute_recursive(realpath($dir_path).'/'.$file,$ret,$absolute_path,$recursive);
	   	}
	    closedir($handle);
	}
	return true;
}
function process_file($file, $process_function, $is_last = false) {
	$handle = @fopen($file, "r");
	if ($handle) {
	    while (($buffer = fgets($handle, 4096)) !== false) {
	    	$buffer = trim($buffer);
	    	if (empty($buffer)) continue;
	    	call_user_func($process_function, $buffer);
	    }
	    fclose($handle);
	}
	if ($is_last) {
		call_user_func($process_function, '', true);
	}
}
function process_gzip_file($file, $process_function) {
	$handle = gzopen($file, "rb");
	if ($handle) {
		while (($buffer = gzgets($handle, 4096)) !== false) {
			$buffer = trim($buffer);
			if (empty($buffer)) continue;
		    call_user_func($process_function, $buffer);
		}
		gzclose($handle);
	}
}
function process_bzip_file($file, $process_function) {
	$handle = bzopen($file, "r");
	if ($handle) {
		while (!feof($handle)) {
			$buffer .= bzread($handle, 4096);
			if (empty($buffer)) continue;
			$lines = preg_split('/[\r\n]/', $buffer);
			$s = count($lines);
			for ($i = 0; $i < $s - 1; $i++) {
				$line = trim($lines[$i]);
				if (empty($line)) continue;
				call_user_func($process_function, $lines[$i]);
			}
			$buffer = $lines[$s - 1];
		}
		$buffer = trim($buffer);
		if (!empty($buffer)) call_user_func($process_function, $buffer);
		gzclose($handle);
	}
}
function elex_is_integer($var) {
	if (is_int($var)) return true;
	$int_var = intval($var);
	if ($var === strval($int_var)) return true;
	return false;
}
/**
 * 取得应用程序的配置
 * @return AppConfig
 */
function get_app_config($server_id = null){
	if (isset($server_id)) {
		if(is_virtualServer($server_id)){
			$server_id = 's';
		}
		return AppConfig::getInstance(APP_ROOT.'/etc/config_'.$server_id.'.ini');
	}
	if (!defined('SERVER_ID')) {
		$sid = DEFAULT_SERVER_ID;
		if (!empty($GLOBALS['server_id'])) {
			$sid = intval($GLOBALS['server_id']);
		} else if (!empty($GLOBALS['platform_user_id'])){
			$server_user = load(MODEL_SERVER)->query($GLOBALS['platform_user_id']);
			if (!empty($server_user)) {
				$sid = intval($server_user['latest_server_id']);
			}
		}
		$s_info = $sid;
		if(is_virtualServer($sid)){
			$s_info = 's';
		}
		$config_file = APP_ROOT.'/etc/config_'.$s_info.'.ini';
		if (!file_exists($config_file)) {
			$sid = DEFAULT_SERVER_ID;
			$s_info = $sid;
		}
		define('SERVER_ID', $sid);
	}
	$s_info = SERVER_ID;
	if(is_virtualServer()){
		$s_info = 's';
	}
	return AppConfig::getInstance(APP_ROOT.'/etc/config_'.$s_info.'.ini');
}
function get_language_by_country($country) {
	global $LANGUAGE_COUNTRIES;
	foreach ($LANGUAGE_COUNTRIES as $language => $countries) {
		if (in_array(strtoupper($country), $countries)) return $language;
	}
	return false;
}
function get_country_by_language($language) {
	if (empty($language)) return false;
	global $LANGUAGE_COUNTRIES;
	if (empty($LANGUAGE_COUNTRIES[$language])) return strtolower($language);
	return strtolower($LANGUAGE_COUNTRIES[$language][0]);
}
function get_exchange($from_currency, $date = null) {
	$from_currency = strtoupper($from_currency);
	if ($from_currency === 'USD') return 1;
	global $logger;
	$cache_helper = load(MODEL_DEFAULT);
	if (empty($date)) $date = date('Y-m-d');
	$key = "ex_rate_{$from_currency}USD{$date}";
	$failed_key = "ex_rate_f_{$from_currency}USD{$date}";
	$exchange_rate = $cache_helper->getFromCache($key);
	if (empty($exchange_rate)) {
		$failed_count = $cache_helper->getFromCache($failed_key);
		if ($failed_count > 5) {
			$logger->writeError('failed to get exchange rate after 5 times:USD=>'.$from_currency);
			return 0;
		}
		$apis = array(
			array(
				'url'=>'http://finance.yahoo.com/q',
				'params'=> array('s'=>"USD{$from_currency}=X"),
				'regex'=>'@<span id="yfs_l10_.*>(.*?)</span>@'
			),
			array(
				'url'=>'http://cn.reuters.com/investing/currencies/quote',
				'params'=> array('srcAmt'=>1, 'srcCurr'=>'USD', 'destCurr'=>$from_currency),
				'regex'=>'@<div class="quoteLast">.*?([\d\.,]+)</div>@ism'
			),
			array(
				'url'=>'http://www.google.com/finance/converter',
				'params'=> array('a'=>1, 'from'=>'USD', 'to'=>$from_currency),
				'regex'=>'@<span class=(?:"|\')?bld(?:"|\')?>([0-9,\.]+).*</span>@'
			),
		);
		$matches = array();
		foreach ($apis as $api) {
			if ($exchange_rate > 0) break;
			$res = get_request($api['url'], $api['params']);
			if (!empty($res)) {
				$res = preg_match($api['regex'], $res, $matches);
				if (!empty($matches[1])) {
					$exchange_rate = floatval(str_replace(',', '', trim($matches[1])));
				}
			}
		}
		if ($exchange_rate > 0) {
			$cache_helper->setToCache($key, $exchange_rate, 0);
		} else {
			$cache_helper->setToCache($failed_key, intval($failed_count)+1, 0);
		}
	}
	return floatval($exchange_rate);
}
function wrap_data($model, $db_value) {
	$wrap_defs = $GLOBALS['WRAP_DEFS'][$model];
	if (empty($wrap_defs)) return $db_value;
	$wrapped = array();
	foreach ($wrap_defs as $wrap_field => $wrap_def) {
		$db_field = $wrap_def[0];
		if (!isset($db_value[$db_field])) continue;
		if (is_int($wrap_def[1])
				&& intval($db_value[$db_field]) > intval($wrap_def[1])) {
			$wrapped[$wrap_field] = intval($db_value[$db_field]);
		} elseif ($wrap_def[1] === '=') {
			if (!empty($wrap_def[2])) {
				if ($wrap_def[2] == 'json_decode') {
					if (!empty($db_value[$db_field])) {
						$wrapped[$wrap_field] = json_decode($db_value[$db_field], true);
					}
				} elseif ($wrap_def[2] == 'not_empty') {
					if (!empty($db_value[$db_field])) {
						$wrapped[$wrap_field] = $db_value[$db_field];
					}
				} else {
					$wrapped[$wrap_field] = $wrap_def[2]($db_value[$db_field]);
				}
			} else {
				$wrapped[$wrap_field] = $db_value[$db_field];
			}
		} elseif($wrap_def[1] === '!='){
			if (is_int($wrap_def[2])
					&& intval($db_value[$db_field]) !== $wrap_def[2]) {
				$wrapped[$wrap_field] = intval($db_value[$db_field]);
			}
		} elseif (is_bool($wrap_def[1])
				&& intval($db_value[$db_field]) === intval($wrap_def[1])) {
			$wrapped[$wrap_field] = intval($db_value[$db_field]);
		}
	}
	return $wrapped;
}
function deep_ksort(&$arr) {
    ksort($arr);
    foreach ($arr as &$a) {
        if (is_array($a) && !empty($a)) {
            deep_ksort($a);
        }
    }
}
/**
 *
 * 获得缓存操作句柄
 * @param string $config_key
 *
 * @return Cache
 */
function get_cache_helper($config, $super_config = null) {
	static $cache_helpers = array();
	$cache_helper = $cache_helpers[$config];
	if (empty($cache_helper)) {
		$cache_helper = new Cache($config);
		if (!empty($super_config)) {
			$cache_helper->setSuperServer(get_cache_helper($super_config));
		}
		$cache_helpers[$config] = $cache_helper;
	}
	return $cache_helper;
}
function get_cache_helper_by_config($config_key, $app_config = null) {
	if (!isset($app_config)) $app_config = get_app_config();
	if ($config_key === AppConfig::DEFAULT_CACHE_SERVER) {
		$config = $app_config->getGlobalConfig(AppConfig::DEFAULT_CACHE_SERVER);
	} else {
		$config = $app_config->getConfig($config_key, AppConfig::TABLE_CACHE_SERVER);
		if (empty($config)) {
			$config = $app_config->getGlobalConfig($config_key);
		}
		if (empty($config)) {
			$config = $app_config->getGlobalConfig(AppConfig::DEFAULT_CACHE_SERVER);
		}
		if (empty($config)) {
			throw new Exception('can not find cache server:'.$config_key, ERROR_MEMCACHE);
		}
	}
	if (empty($config)) return false;
	if ($config_key === AppConfig::DEFAULT_CACHE_SERVER) {
		$super_config = $app_config->getGlobalConfig(AppConfig::DEFAULT_CACHE_SUPER_SERVER);
	} else {
		$super_config = $app_config->getConfig($config_key, AppConfig::TABLE_CACHE_SUPER_SERVER);
		if (empty($super_config)) {
			$super_config = $app_config->getGlobalConfig(AppConfig::DEFAULT_CACHE_SUPER_SERVER);
		}
	}
	return get_cache_helper($config, $super_config);
}


function load($model, $params = null, $cache = true) {
	$sid = getServerID();
	$model_server = $model.$sid;
	if(empty($_ENV[$model_server]) || !$cache) {
		$modela = ucwords($model);
		if (strpos($model, '_') !== false) {
			$parts = explode('_', $model);
			$parts = array_map('ucwords', $parts);
			$modela = implode('', $parts);
		}

		if (file_exists(MODEL."/{$modela}Manager.class.php")) {
			require_once MODEL."/{$modela}Manager.class.php";
			eval('$_ENV[$model_server] = new '.$modela.'Manager($params);');
		} else if($model == MODEL_ADMIN_USER) {
			require_once MODEL.'/UserPrivilegeManager.class.php';
			$_ENV[$model_server] = new UserPrivilegeManager();
		}else {
			require_once MODEL."/Manager.class.php";
			$_ENV[$model_server] = new Manager($model, $params);
		}
		if (!$cache) {
			$ret = $_ENV[$model_server];
			unset($_ENV[$model_server]);
			return $ret;
		}
	}
	return $_ENV[$model_server];
}

/**
 * Indents a flat JSON string to make it more human-readable.
 *
 * @param string $json The original JSON string to process.
 *
 * @return string Indented version of the original JSON string.
 */
function json_format($json) {
    $result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '    ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {
        $char = substr($json, $i, 1);

        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;

        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }

        $result .= $char;
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
        $prevChar = $char;
    }
    return $result;
}


/**
 * 获取服信息
 * Enter description here ...
 */
function getServerID(){
	if(!defined('SERVER_ID')){
		$sid = '';
	}else{
		$sid = SERVER_ID;
	}
	if ($GLOBALS['sid'] > 0 && $GLOBALS['sid'] != SERVER_ID){
		$sid = $GLOBALS['sid'];
	}
	return $sid;
}

/**
 * 判断是否为逻辑服,及其操作处理的返回
 * Enter description here ...
 */
function is_virtualServer($s=null,$sign=0){
	return false;
}

//过滤屏蔽词
function filter_words($words){
	$bad_words = json_decode(load(MODEL_HEROES)->get_config_file('keywords'));
	foreach($bad_words as $bad_word)
	{
		$words=str_replace($bad_word, 'xxx', $words);
	}
	return $words;
}






function get_file_content_without_bom($filename) {
	$contents = file_get_contents ( $filename );
	$charset [1] = substr ( $contents, 0, 1 );
	$charset [2] = substr ( $contents, 1, 1 );
	$charset [3] = substr ( $contents, 2, 1 );
	if (ord ( $charset [1] ) == 239 && ord ( $charset [2] ) == 187 && ord ( $charset [3] ) == 191) {
		$rest = substr ( $contents, 3 );
		return $rest;
	} else {
		return $contents;
	}
}


function get_max_server_id()
{
    $servers = load(MODEL_SERVER)->getServers('zh-CN');
    $max_server_id = 1;
    foreach($servers as $k => $v)
    {
        if($k > $max_server_id)
            $max_server_id = $k;
    }
    
    return $max_server_id;
}
function curl_get_contents($url) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}
function who_invite($user_token) {
	$url_base = 'https://graph.facebook.com/me/apprequests?access_token=';
	$res = curl_get_contents($url_base . $user_token);
	$req_json = json_decode($res, TRUE);
	if ($req_json['data']) {
		$data = $req_json['data'];
		$data = $data[0];
		$from_id = $data['from']['id'];
		$to_id = $data['to']['id'];
        load(MODEL_FBDATA) -> modify(array('fbid'=>$to_id,'invitedBy'=>$from_id));
		$value = $data['id'];
		$arr = split('_', $value);
		$reqid = $arr[0];
		$to_fbid = $arr[1];
        $fb_reqs = load(MODEL_FBREQ) -> query(array('reqid'=>$reqid));
		if (empty($fb_reqs)) {
			#echo 'reqid not found';
			return FALSE;
		}
		foreach ($fb_reqs as $value) {
			$to_fbids = $value['to_fbids'];
			#echo $to_fbids;
			if (empty($to_fbids)) {
				$to_fbids = array();
			} else {
				$to_fbids = json_decode($value['to_fbids'], TRUE);
				if (in_array($to_fbid, $to_fbids)) {
					#echo "already have";
					return TRUE;
				}
			}
			array_push($to_fbids, $to_fbid);
			$json_fbids = json_encode($to_fbids);
			load(MODEL_FBREQ) -> modify(array('reqid'=>$reqid,'to_fbids'=>$json_fbids));
			#echo "success";
			return TRUE;
		}
	} else {
		#echo "user_token err";
		return FALSE;
	}
}

//更新xpub消息配置
function publish_cfg($appid){
		//服务端版本号加1
		//内容从配置文件推送到memcache
		$pubCfg=file_get_contents(APP_ROOT . '/etc/app_' . $appid . '.json');
		if($pubCfg === false) {throw new Exception("no config for {$appid} !!!", 1001);return;}
		//TODO去掉配置文件的空格和换行
		$jsonArray = json_decode($pubCfg,true);
		if($jsonArray == null) { throw new Exception("invliad json config for {$appid}!!!", 1000);	return;} 
		$jsonStr = json_encode($jsonArray);
		
		$app_cfg=load(MODEL_FBDATA)->getFromCache(sprintf(CK_XPUB_APPCFG , $appid));
		if(!$app_cfg || empty($app_cfg)){
			$app_cfg=array(
				'appid' => $appid,
				'sv' => 1,
				'msg'=>$pubCfg,
			);
		}else{
			$app_cfg['msg'] = $pubCfg;
			$app_cfg[sv] += 1;
		}
		load(MODEL_FBDATA)->setToCache(sprintf(CK_XPUB_APPCFG , $appid),$app_cfg);
		$GLOBALS['xpub_logger']->writeinfo(json_encode($app_cfg));
}

//调用cp接口发奖
function call_cp_award($appid,$uid,$roleid,$taskid){
	$award_url =$GLOBALS['AWARD_URL'][$appid];
	if(!isset($award_url)){//发奖地址未配置  领奖功能还未上线
		$GLOBALS['xpub_logger']->writeinfo("award url for $appid invalid.");
		//$result = '{"status":0,"msg":"抱歉，領獎功能暫時還未上線。在此之前，獎品由GM統一發放。"}';
		if($appid=='4104'||$appid=='4061'){
			$result=array('status' => '0', 'msg' => 'Sorry,reward will open soon...','msg1'=>'Sorry,reward will open soon...');
		}else{
		$result = array('status' => '0', 'msg' => '抱歉，領獎功能暫時還未上線。在此之前，獎品由GM統一發放。','msg1'=>'Sorry,reward will open soon...');
		}
		return $result;
	}

	$params['mod'] = 'award';
	$params['act'] = 'mock';
	$params['appid'] = $appid;
	$params['uid'] = $uid;
	$params['roleid'] = $roleid;
	$params['taskid'] = $taskid;
	try{
		$ck_me=sprintf(CK_AWARD_REQ_FROM_ME,$appid,$uid,$roleid,$taskid);
		load(MODEL_DEVICE_DATA)->setToCache($ck_me,1,300);
		
		$response = post_request($award_url,$params);
		//解析cp发奖结果 两结果 1. 发成功   2.发失败
		$GLOBALS['xpub_logger']->writeinfo(" post to award url: $award_url  response = $response  params=".json_encode($params));
		if($response === 'success') return true;
		else return false;
	}catch(Exception $e){
		$GLOBALS['xpub_logger']->writeError("post to award url: $award_url  errorMsg:".$e->getMessage() );
		return false;
	}


}

//版本号大于
function isVersionUpper($verA ,$verB){
	$verA_ary = explode('.',$verA);
	$verB_ary = explode('.',$verB);
	$v_nums = max(count($verA_ary),count($verB_ary));
	for ($i=0;$i<$v_nums;$i++){
		if($verA_ary[$i] > $verB_ary[$i]) return true;
	}
	return false;
}

//版本号大于等于
function isVersionAndLager($verA ,$verB){
	if($verA === $verB) return true;
	return isVersionUpper($verA,$verB);
}