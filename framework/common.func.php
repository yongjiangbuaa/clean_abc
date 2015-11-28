<?php
/**
 * Get the current time stamp
 * @param $f the format of the date time. if this parameter is not
 * set, the default format is yyyy-mm-dd HH:MM:SS
 *
 * @return Date time
 */
function getTimeStamp($f = null) {
	if(!isset($f)){
		return date ( "Y-m-d H:i:s" );
	}
	else {
		return date($f);
	}
}

/**
 * 从各个外部变量中取值
 *
 * @param string $key 外部变量的key
 * @param string $type
 * int,integer -- 取得的变量作为一个int值返回，默认值是0
 * string      -- 取得的变量作为string返回，默认值是NULL。这是默认的返回方式
 * array       -- 取得的变量作为array返回，默认值是一个空的数组
 * bool        -- 取得的变量作为bool值返回，默认值是false
 *
 * @param string $var 代表需要取值的变量类型
 * R  -  $_REQUEST
 * G  -  $_GET
 * P  -  $_POST
 * C  -  $_COOKIE
 * @return mixed 返回key对应的值
 */
function getGPC($key, $type = 'integer', $var = 'R') {
	switch($var) {
		case 'G': $var = &$_GET; break;
		case 'P': $var = &$_POST; break;
		case 'C': $var = &$_COOKIE; break;
		case 'R': $var = &$_REQUEST; break;
	}
	switch($type) {
		case 'int':
		case 'integer':
			$return = isset($var[$key]) ? intval($var[$key]) : 0;
			break;
		case 'string':
			$return = isset($var[$key]) ? $var[$key] : NULL;
			break;
		case 'array':
			$return = isset($var[$key]) ? $var[$key] : array();
			break;
		case 'bool':
			$return = isset($var[$key]) ? (bool)$var[$key] : false;
			break;
		default:
			$return = isset($var[$key]) ? $var[$key] : NULL;
	}
	return $return;
}
/**
 * 必要的时候给参数加上转义
 *
 * @param mixed $params
 * @return mixed
 */
function elex_addslashes($params){
	if(get_magic_quotes_gpc()){
		return $params;
	}
	if(is_array($params)){
		return array_map('elex_addslashes',$params);
	}else{
		return addslashes($params);
	}
}

function getRandomString($len, $include_special_characters=false)
{
    $chars = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    );
    if ($include_special_characters) {
    	 $chars = array_merge($chars,array(
	    	"~","`","!","@","#","$","%","^","&","*","(",")","-","+","_",
	    	"=","<",">","?","{","}","[","]",":",";",",","."
		 ));
    }
    $charsLen = count($chars) - 1;

    shuffle($chars);    // 将数组打乱
    
    $output = "";
    for ($i=0; $i<$len; $i++)
    {
        $output .= $chars[mt_rand(0, $charsLen)];
    }

    return $output;
}
# /**
#  *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
#  *
#  * @access  public
#  * @param   string       $str         待转换字串
#  *
#  * @return  string       $str         处理后字串
#  */
 function make_semiangle($str)
 {
     $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
                  '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
                  'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
                  'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
                  'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
                  'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
                  'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
                  'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
                  'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
                  'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
                  'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
                  'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
                  'ｙ' => 'y', 'ｚ' => 'z',
                  '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
                  '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
                  '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
                  '》' => '>',
                  '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
                  '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
                  '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
                  '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
                  '　' => ' ');
  
    return strtr($str, $arr);
}
/**
 * 获取客户端访问的ip地址，即使用户隐藏在代理的后面也能获取到相应的ip地址
 */
function get_ip() {
    if (_valid_ip($_SERVER["HTTP_CLIENT_IP"])) {
        return $_SERVER["HTTP_CLIENT_IP"];
    }
    foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
        if (_valid_ip(trim($ip))) {
            return $ip;
        }
    }
    if (_valid_ip($_SERVER["HTTP_X_FORWARDED"])) {
        return $_SERVER["HTTP_X_FORWARDED"];
    } elseif (_valid_ip($_SERVER["HTTP_FORWARDED_FOR"])) {
        return $_SERVER["HTTP_FORWARDED_FOR"];
    } elseif (_valid_ip($_SERVER["HTTP_FORWARDED"])) {
        return $_SERVER["HTTP_FORWARDED"];
    } elseif (_valid_ip($_SERVER["HTTP_X_FORWARDED"])) {
        return $_SERVER["HTTP_X_FORWARDED"];
    } else {
        return $_SERVER["REMOTE_ADDR"];
    }
}
function _valid_ip($ip) {
    if (!empty($ip) && ip2long($ip)!=-1) {
        $reserved_ips = array (
	        array('0.0.0.0','2.255.255.255'),
	        array('10.0.0.0','10.255.255.255'),
	        array('127.0.0.0','127.255.255.255'),
	        array('169.254.0.0','169.254.255.255'),
	        array('172.16.0.0','172.31.255.255'),
	        array('192.0.2.0','192.0.2.255'),
	        array('192.168.0.0','192.168.255.255'),
	        array('255.255.255.0','255.255.255.255')
        );
        foreach ($reserved_ips as $r) {
            $min = ip2long($r[0]);
            $max = ip2long($r[1]);
            if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
        }
        return true;
    } else {
        return false;
    }
}
function is_email($mail_addr){
	if (! preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]{2,})+$/i', $mail_addr) ) {
		return false; 
	} else {
		return true;

	}
}
/**
 * 判断在概率probability下，事件是否发生
 *
 * @param 概率 $probability
 * @return 如果发生，则返回true，否则返回false
 */
function can_happen_random_event($probability) {
	list($usec, $sec) = explode(' ', microtime());
    $seed = (float) $sec + ((float) $usec * 100000);
	mt_srand($seed);
	$rand = mt_rand(1, 10000);
	$rand_prob = floor(10000 * $probability);
	return $rand <= $rand_prob;
}
/**
 * 删除数组元素
 */
function array_remove(&$array, $value) {
	if (empty($value)) return;
	if (!is_array($value)) $value = array($value);
	$array = array_diff($array, $value);
}
/**
 * 对字符串进行分割
 */
function parse_string($string, $field_names, $pattern = '/[:|]/', $unit_size = 2) {
	if (empty($string)) return array();
	$parts = preg_split($pattern, $string);
	$ret = array();
	for ($i = 0, $length = count($parts) / $unit_size; $i < $length; $i++) {
		$unit_value = array();
		for ($j = 0; $j < $unit_size; $j++) {
			$unit_value[] = $parts[$i*$unit_size+$j];
		}
		$ret[] = array_combine($field_names, $unit_value);
	}
	return $ret;
}
/**
 * 将数组整合成一个字符串
 */
function implode_string($array, $field_glue = ':', $item_glue = ',') {
	if (empty($array)) return '';
	$ret = '';
	foreach ($array as $item) {
		if (isset($ret[0])) $ret .= $item_glue;
		if (is_array($item)) $ret .= implode($field_glue, $item);
		else $ret .= strval($item);
	}
	return $ret;
}

/**
 * retrieve the database configuration by parsing dsn.
 * @param string $dsn the dsn tring to be parsed. the format of dsn is : driver://username:password@host/database?option=opt_value
 * @return array the configuration of database.it includes host,username,password,charset,newlink,pconnect, etc.
 */
function parse_dsn($dsn){
	if(empty($dsn)) return false;
	$dsn = parse_url($dsn);
	if(empty($dsn)) return false;
	$db_config = array();
	$db_config['driver'] = strtolower($dsn['scheme']);
	$db_config['host'] = $dsn['host'];
	if(!empty($dsn['port'])){
		$db_config['host'] .= ':' . intval($dsn['port']);
	}
	$db_config['password'] = $dsn['pass'];
	$db_config['username'] = $dsn['user'];
	if(isset($dsn['path']{1})){
		$db_config['database'] = substr($dsn['path'],1);
	}
	$db_config['charset'] = 'utf8';
	$db_config['newlink'] = false;
	$db_config['pconnect'] = false;
	if(isset($dsn['query'])){
		$opt = array();
		parse_str($dsn['query'],$opt);
		$db_config = array_merge($db_config, $opt);
	}
	return $db_config;
}
/**
 * generate the sequence id
 *
 * @param string $table_name
 * @param string $id_field
 * @param boolean $data_exists
 * @return int
 */
function generate_next_id($table_name, $id_field = null, $data_exists = false, $app_config = null,$count=1) {
	require_once FRAMEWORK.'/database/IDSequence.class.php';
    $sequence_handler = new IDSequence($table_name, $id_field, $data_exists, $app_config);
    $next = $sequence_handler->getNextId($count);
    return $next;
}
function calculate_sig($sig_key = null, $params = null) {
	if (empty($sig_key)) $sig_key = get_app_config()->getGlobalConfig(AppConfig::SIGNATURE_KEY);
	if (empty($params)) $params = array_merge($_GET,$_POST);
	if(isset($params['sig'])) unset($params['sig']);
	$s = _get_sign_string($params);
	return md5($s);
}
function _get_sign_string($params) {
	$s = '';
	ksort($params, SORT_STRING);
	foreach ($params as $k => $val) {
		if(is_array($val) || is_object($val)){
			$s .= $k . _get_sign_string($val);
		} else {
			$s .= $k . $val;
		}
	}
	return $s;
}
function prepare_db_value($field_defs, $field, $value) {
	$field_info = $field_defs[$field];
	$str_value = strval($value);
	if (!isset($str_value[0])) $value = $field_info['default'];
	if (empty($field_info)) {
		if (is_array($value)) {
			if (is_string($value[0])) {
				return "'".implode("','", array_map("elex_addslashes", $value))."'";
			} else {
				return implode(",", array_map("elex_addslashes", $value));
			}
		} else {
			if (is_string($value)) {
				return "'".elex_addslashes($value)."'";
			} else {
				return elex_addslashes($value);
			}
		}
	}
	switch ($field_info['type']) {
		case FieldType::TYPE_STRING:
			if (is_array($value)) {
				return "'".implode("','", array_map("elex_addslashes", $value))."'";
			}
			return "'".elex_addslashes($value)."'";
		default:
			if (is_array($value)) {
				return implode(",", array_map("elex_addslashes", $value));
			}
			return $value;
	}
}
function build_where($and_conds) {
	if (!is_array($and_conds) || count($and_conds) == 0) return '';
	$where = '';
	if (count($and_conds) > 0) {
		foreach ($and_conds as $and_cond) {
			if (isset($where[0])) $where .= ' AND ';
			$where .= $and_cond;
		}
		$where = ' WHERE '.$where;
	}
	return $where;
}
/**
 * 从文件中读取指定的行数
 *
 * @param string $file 文件路径
 * @param int $offset 开始行的行号
 * @param int $line_count 读取的行数
 * @return array 从行号$offset开始(包含)，$line_count行的内容，每一行对应数组的一个元素
 */
function read_lines($file, $offset, $line_count) {
	$handle = @fopen($file, "r");
	$i = 1;
	$ret = array();
	$read_count = 0;
	if ($handle) {
	    while (($buffer = fgets($handle, 4096)) !== false) {
	        if ($i < $offset) {
	        	$i++;
	        	continue;
	        }
	        $ret[] = trim($buffer);
	        $read_count++;
	        if ($read_count === intval($line_count)) break;
	    }
	    fclose($handle);
	}
	return $ret;
}
/**
 * 从config.ini中获取配置项
 *
 * @param string $key
 * @return string 键值$key所对应的配置项
 */
function get_config($key) {
	return get_app_config()->getGlobalConfig($key);
}
//产生一个17位的随机字符串
function generate_string_id() {
	$id = uniqid();
	$datas = array_merge($_SERVER, $_REQUEST);
	$datas = array_merge($datas, array('micro_time'=>microtime(true)));
	$seed = crc32(http_build_query($datas));
	mt_srand($seed);
	$rand = substr(md5(mt_rand()), 0, 4);
	return strtoupper($rand.$id);
}
/**
 * 将一个描述数字的字符串扩展为一个数组
 * @param string $num_range 一个字符串，格式：1,2,3,4-8,20
 * @return array
 * @example $s = 1,2,3,4-8,20; $range = expandNumList($s);
 */
function expand_num_list($num_range){
	$num_range = ',' . trim($num_range,', ');
	preg_match_all('/[0-9]+-[0-9]+/',$num_range,$out);
	$range = array();
	if(!empty($out)){
		foreach($out[0] as $m){
			$num_range = str_replace(',' . $m,'',$num_range);
			$r = explode('-',$m);
			$range = array_merge($range,range($r[0],$r[1]));
		}
	}
	if(!empty($num_range)){
		$num_range = trim($num_range,',');
		$range = array_merge($range,explode(',',$num_range));
	}
	return $range;
}
/**
 * 通过values和$fields将数据库记录补全，例如user表有三个字段name,age,sex，数据库默认值分别为:'',0,1
 * 那么调用complete_row(
 * 			'user', 
 * 			array(
 * 				array('wang', 18), 
 * 				array('song',17)
 * 			), 
 * 			array('name','age'));
 * 将会返回array(array('name'=>'wang', 'age'=>18, 'sex'=>1), array('name'=>'song', 'age'=>17, 'sex'=>1))
 * 
 * @param string $table 数据库表配置名称，并不是真实的表名
 * @param array $values 
 * @param array $fields
 */
function complete_row($table, $values, $fields) {
	$rows = array();
   	$fields_def = get_app_config()->getConfig(AppConfig::TABLE_FIELDS, $table);
	foreach ($values as $value) {
		$row = array_combine($fields, $value);
		foreach ($fields_def as $field_def) {
			if (isset($row[$field_def['name']])) continue;
			$default_value = $field_def['default'];
			$row[$field_def['name']] = $default_value;
		}
		$rows[] = $row;
	}
	return $rows;
}
function sort_on(array &$array, $field, $field_type = 1, $order = 'DESC') {
	if (empty($array)) return;
	$GLOBALS['sort_on_field'] = $field;
	$GLOBALS['sort_on_options'] = array(
		'field_type' => $field_type,
		'order' => $order
	);
	usort($array, '_sort_on_func');
	unset($GLOBALS['sort_on_field'], $GLOBALS['sort_on_options']);
}
function _sort_on_func($o1, $o2) {
	global $sort_on_field, $sort_on_options;
	$field_type = $sort_on_options['field_type'];
	$order = $sort_on_options['order'];
	if (empty($order)) {
		$order = 'DESC';
	}
	$v1 = $o1[$sort_on_field];
	$v2 = $o2[$sort_on_field];
	if ($order == 'ASC') {
		if ($field_type == SORT_STRING) {
			return strcmp($v1, $v2);
		} else {
			$f1 = floatval($v1);
			$f2 = floatval($v2);
			if ($f1 > $f2) {
				return 1;
			} else if ($f1 < $f2) {
				return -1;
			} else {
				return 0;
			}
		}
	} else {
		if ($field_type == SORT_STRING) {
			return strcmp($v2, $v1);
		} else {
			$f1 = floatval($v1);
			$f2 = floatval($v2);
			if ($f1 > $f2) {
				return -1;
			} else if ($f1 < $f2) {
				return 1;
			} else {
				return 0;
			}
		}
	}
}
?>