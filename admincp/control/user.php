<?php
!defined('APP_ROOT') && exit('Access Denied');
class usercontrol extends base {
    public function __construct() {
        parent::__construct();
        $server_id = intval($_COOKIE['sid']);
        if ($server_id != 1 && $server_id > 0) {
            $this -> db = "`stats_{$server_id}`";
        }
        //$this->con = getServerIdCondition();
    }

    public function onindex() {
        $sql = 'select * from admin_user';
        $res = load(MODEL_ADMIN_USER) -> executeQuery($sql);
        $GLOBALS['view_datas'] = array('res' => $res);
    }

    public function ondefault() {

    }

    public function onprofile() {
        $invalid = invalid();
        if ($invalid === true) {
            header("Location:admincp.php");
            exit();
        }
        $res = load(MODEL_ADMIN_USER) -> getAllowedActions('q1');
        var_dump($res);
        $GLOBALS['view_datas'] = array('res' => $res);
    }

    //用户权限配置
    public function onprivilege() {
        $name = $this -> input('name');
        $admins = load(MODEL_ADMIN_USER) -> getAllowedActions($name);
        $GLOBALS['view_datas'] = array('admins' => $admins);
        $res = load(MODEL_ADMIN_USER) -> addAllowedAction('qwe', 'fb,stat');
        $checked_actions = getGPC('actions', 'array', 'P');
        var_dump($checked_actions);
    }

    public function onpost() {
        $invalid = invalid();
        $username = $invalid['username'];
        $type = $this -> input('type');
        $user['name'] = $this -> input('name');
        if (empty($user['name'])) {
            exit('empty name');
        }
        if ($type == 'adduser') {
            $user_status = load(MODEL_ADMIN_USER) -> query(array('name'=>$user['name']));
            if(!empty($user_status)){
                exit('已经有该用户了');
            }
            $user['password'] = $this -> input('password');
            $repassword = $this -> input('repassword');
            $user['group_id'] = $this -> input('group_id');
            if (empty($user['password']) || empty($user['group_id']) || empty($repassword)) {
                exit('用户信息不全');
            }
            if ($user['password'] == $repassword) {
                $status = load(MODEL_ADMIN_USER) -> create($user);
                if ($status == true) {
                    echo "ok";
                }
            } else {
                exit('2次输入的密码不同');
            }
        } elseif ($type == 'deluser') {
            if ($user['name'] == $username) {
                exit('你不能删除自己');
            }
            $status = load(MODEL_ADMIN_USER) -> remove($user);
            if ($status == 1) {
                echo "ok";
            } else {
                echo '删除失败';
            }

        } elseif ($type == 'editpass') {
            if (empty($user['password'])) {
                $user['password'] = $this -> input('password');
                $status = load(MODEL_ADMIN_USER) -> modify($user);
                echo "ok";
            } else {
                exit('密码不能为空');
            }
        }
    }

    public function onlogin() {
        $type = $this -> input('type');
        if ($type == 'list') {
            $username = $this -> input('name');
            $password = $this -> input('password');
            $response = login($username, $password);
            return $response;
        }
    }

    public function onquit() {
        logout();
        outUserId();
        session_destroy();
        header("Location:admincp.php");
    }

}
?>