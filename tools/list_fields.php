<?php
$table_defines = array(
//	"fbdata",
	"device_data",
);
$config = parse_ini_file('D:\workspace\elexpublish\xpub\etc\config_1.ini',true);
$content = "";
$droped_dbs = array();
$conn = @mysql_connect("localhost:3306", "root", "");
$fields_config = "";
foreach ($table_defines as $table_define) {
	$table_config = getSection($table_define);
	if (empty($table_config)) {
		$parts = explode(".", $table_define);
		if (count($parts) !== 2) {
			echo('not valid table define:'.$table_define."\n");
			continue;
		}
		$deploy_type = 0;
		$db_name = $parts[0];
		$table_name = $parts[1];
	} else {
		$deploy_type = $table_config['deploy'];
		$db_name = $table_config['db_name'];
		$table_name = $table_define;
	}
	if ($deploy_type == 3) {
		$table_name = $table_define."_0";
	}
	if ($deploy_type == 4) {
		$table_name = $table_define."_01";
	}
	if ($deploy_type > 0) {
		@mysql_select_db($db_name."0");
	} else {
		@mysql_select_db($db_name);
	}
	$fields_def = getFields($table_name);
	$fields_config .= "$table_define=";
	foreach ($fields_def as $field_def) {
		$field_name = $field_def['Field'];
		if (strpos($field_name, "reserve") !== false) continue;
		$field_type = "=====".$field_def['Type'];
		$field_default = $field_def['Default'];
		if ($table_define == 'user_employee' && $field_name=='level') {
			$field_default = 'function<>set_default';
		}
		$is_null = $field_def['Null'];
		if (strpos($field_type, "int") > 0) {
			$field_type = "integer";
			if (empty($field_default)) $field_default = 0;
		} elseif (strpos($field_type, "char") > 0) {
			$field_type = "string";
			if (empty($field_default)) $field_default = '';
		} elseif (strpos($field_type, "float") > 0) {
			$field_type = "float";
			if (empty($field_default)) $field_default = 0;
		} elseif (strpos($field_type, "varbinary") > 0) {
			$field_type = "packed";
			if (empty($field_default)) $field_default = '';
		} elseif (strpos($field_type, "decimal") > 0) {
			$field_type = "float";
			if (empty($field_default)) $field_default = 0;
		} elseif (strpos($field_type, "datetime") > 0) {
			$field_type = "string";
			if (empty($field_default)) $field_default = '';
		} elseif (strpos($field_type, "date") > 0) {
			$field_type = "string";
			if (empty($field_default)) $field_default = '';
		} elseif (strpos($field_type, "text") > 0) {
			$field_type = "string";
			if (empty($field_default)) $field_default = '';
		} else {
			die("unknown field type : ".$field_type);
		}
		if ($is_null === 'YES') {
			$is_null = 1;
		} else {
			$is_null = 0;
		}
		if (strpos($field_name,'/') !== false) $field_name = "`$field_name`";
		$fields_config .= "$field_name:$field_type:$field_default+";
	}
	$fields_config .= "\n";
}
echo $fields_config;
function getSection($section_name){
	global $config;
	if(is_null($config) || empty($section_name)){
		return false;
	}
	foreach (array_keys($config) as $tables) {
		$parts = array($tables);
		if (strpos($tables, '|') !== false) {
			$parts = explode(' | ',$tables);
		}
		if (!in_array($section_name,$parts)) continue;
		return $config[$tables];
	}
	return null;
}
function getFields($table) {
	$result = @mysql_query("SHOW COLUMNS FROM $table");
	if (!$result) {
	    echo 'Could not run query: ' . mysql_error();
	    exit;
	}
	$rows = array();
	if (@mysql_num_rows($result) > 0) {
	    while ($row = @mysql_fetch_assoc($result)) {
	        $rows[] = $row;
	    }
	}
	return $rows;
}
?>