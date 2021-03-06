<?php
namespace app\common\model;

use think\Model;

class User extends Model
{
    protected $readonly = ['username'];
    protected $insert  = ['logins', 'reg_ip', 'last_time', 'last_ip'];
    
    public function userInfo()
    {
        return $this->hasOne('UserInfo', 'uid', 'id');
    }
    
    public function userGroup()
    {
        return $this->hasMany('authGroupAccess', 'uid', 'id');
    }
    
    protected function setPasswordAttr($value)
    {
        return md5($value);
    }
    protected function setLoginsAttr()
    {
        return '0';
    }
    protected function setRegIpAttr()
    {
        return request()->ip();
    }
    protected function setLastTimeAttr()
    {
        return time();
    }
    protected function setLastIpAttr()
    {
        return request()->ip();
    }
}