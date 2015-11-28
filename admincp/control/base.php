<?php
!defined('APP_ROOT') && exit('Access Denied');
class base {
	private static $VAR_NAMES = array();
	protected $time;
	protected $onlineip;
	private $input = array();
	protected $actions;

	function __construct() {
		$this->init_var();
		$admin_name = $_COOKIE['u'];
		if(!empty($admin_name)){
			$this->actions = load(MODEL_ADMIN_USER)->getAllowedActions($admin_name);
		}
	}
	private function init_var() {
		$this->time = time();
		$this->onlineip = get_ip();
		define('FORMHASH', $this->formhash());
		$this->logger = LogFactory::getLogger(array(
			'prefix' => 'control', // 文件名的前缀
			'log_dir' => LOG_DIR, // 文件所在的目录
			'archive' => ILogger::ARCHIVE_YEAR_MONTH, // 文件存档的方式
			'log_level' => get_app_config()->getLogLevel('control')
		));
		$this->input = array_merge($_GET, $_POST);
		$encrypted_data = getGPC('encrypted_data', 'string');
		if (!empty($encrypted_data)) {
			unset($this->input['encrypted_data']);
			$decrypted_data = $this->authcode($encrypted_data, 'DECODE');
			$this->input = array_merge($this->input, $decrypted_data);
			if (!empty($this->input['time']) && $this->time > $this->input['time']) {
				throw new Exception('request is expired', ERROR_REQUEST_EXPIRED);
			}
		}
		foreach (self::$VAR_NAMES as $var) {
			$this->{$var} = $this->input($var);
		}
	}

	protected function load($model, $release = '') {
		return load($model);
	}

	protected function get_settings($k = array(), $decode = FALSE) {
		$settings = $_ENV['config']->query($k);
		$return = array();
		if(is_array($settings)) {
			foreach($settings as $arr) {
				$return[$arr['p_key']] = $decode ? unserialize($arr['p_value']) : $arr['p_value'];
			}
		}
		return $return;
	}

	protected function set_setting($k, $v, $encode = FALSE) {
		$v = is_array($v) || $encode ? elex_addslashes(serialize($v)) : $v;
		$_ENV['config']->setValue($k, $v);
	}

	protected function formhash() {
		return substr(md5(substr($this->time, 0, -4).AUTH_KEY), 16);
	}

	protected function submitcheck() {
		return @getGPC('formhash', 'string', 'P') == FORMHASH ? true : false;
	}

	protected function date($time, $type = 3) {
		$format[] = $type & 2 ? (!empty($this->settings['dateformat']) ? $this->settings['dateformat'] : 'Y-n-j') : '';
		$format[] = $type & 1 ? (!empty($this->settings['timeformat']) ? $this->settings['timeformat'] : 'H:i') : '';
		return gmdate(implode(' ', $format), $time + $this->settings['timeoffset']);
	}

	protected function implode($arr) {
		return "'".implode("','", (array)$arr)."'";
	}

	protected function input($k = null) {
		if ($k === null) return $this->input;
		return isset($this->input[$k]) ? (is_array($this->input[$k]) ? $this->input[$k] : trim($this->input[$k])) : NULL;
	}
	
    protected function writelog($action, $extra = '', $level = ELEX_LOG_DEBUG) {
    	$log = $this->user['username']."\t".$this->onlineip."\t".$this->makeTime()."\t$action\t$extra";
    	$log = str_replace(array('<?', '?>', '<?php'), '', $log);
		switch ($level) {
			case ELEX_LOG_DEBUG:
	    		$this->logger->writeDebug($log);
	    		break;
	    	case ELEX_LOG_ERROR:
	    		$this->logger->writeError($log);
	    		break;
	    	case ELEX_LOG_FATAL:
	    		$this->logger->writeFatal($log);
	    		break;
	    	case ELEX_LOG_INFO:
	    		$this->logger->writeInfo($log);
	    		break;
		}
	}
	protected function maketime() {
        $selectTime = time();
        $timezone = 8;// 东八区
        $selectTime += 3600 * $timezone;
        return date("Y",$selectTime)."-".date("m",$selectTime)."-".date("d",$selectTime)." ".date("H",$selectTime).":".date("i",$selectTime).":".date("s",$selectTime);
    }

	protected function setcookie($key, $value, $life = 0, $httponly = false) {
		(!defined('APP_COOKIEPATH')) && define('APP_COOKIEPATH', '/');
		(!defined('APP_COOKIEDOMAIN')) && define('APP_COOKIEDOMAIN', '');

		if($value == '' || $life < 0) {
			$value = '';
			$life = -1;
		}
		
		$life = $life > 0 ? $this->time + $life : ($life < 0 ? $this->time - 31536000 : 0);
		$path = $httponly && PHP_VERSION < '5.2.0' ? APP_COOKIEPATH."; HttpOnly" : APP_COOKIEPATH;
		$secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
		if(PHP_VERSION < '5.2.0') {
			setcookie($key, $value, $life, $path, APP_COOKIEDOMAIN, $secure);
		} else {
			setcookie($key, $value, $life, $path, APP_COOKIEDOMAIN, $secure, $httponly);
		}
	}
	
	protected function authcode($datas, $operation = '', $key = '', $expiry = 3600) {
		if ($operation !== 'DECODE' && is_array($datas)) {
			foreach (self::$VAR_NAMES as $k=>$var) {
				if (!empty($this->{$var})) $datas[$k] = $this->{$var};
				if (!empty($datas[$var])) {
					$datas[$k] = $datas[$var];
					unset($datas[$var]);
				}
			}
			$oristr = http_build_query($datas);
		} else if (is_string($datas)) {
			$oristr = $datas;
		}
		$orikey = $key;
		$string = $oristr;
		$key = $orikey;
		$ckey_length = 4;
		$key = md5($key ? $key : AUTH_KEY);
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
		$cryptkey = $keya.md5($keya.$keyc);
		$key_length = strlen($cryptkey);
		if ($operation == 'DECODE') {
			$string = str_replace('.', '+', $string);
			$string = str_replace('-', '/', $string);
			$string = base64_decode(substr($string, $ckey_length));
		} else {
			$string = sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
		}
		$string_length = strlen($string);
		$result = '';
		$box = range(0, 255);
		$rndkey = array();
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}
		if($operation == 'DECODE') {
			if (substr($result, 0, 10) > 0 && substr($result, 0, 10) - time() <= 0) {
				throw new Exception('request expired', ERROR_REQUEST_EXPIRED);
			}
			if(substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
				parse_str(substr($result, 26), $res);
				if (!empty($res)) {
					foreach (self::$VAR_NAMES as $k=>$var) {
						if (empty($res[$var]) && !empty($res[$k])) {
							$res[$var] = $res[$k];
							unset($res[$k]);
						}
					}
					return $res;
				}
				return array();
			} else {
				return array();
			}
		} else {
			$result = str_replace('=', '', base64_encode($result));
			$result = str_replace('+', '.', $result);
			$result = str_replace('/', '-', $result);
			return $keyc.$result;
		}
	}
}
?>