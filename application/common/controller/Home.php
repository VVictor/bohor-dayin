<?php
namespace app\common\controller;

use think\Controller;

class Home extends Controller
{
    public function initialize()
    {
        $m_id = session('m_id');
        define('MID', $m_id);   //设置登陆用户ID常量
        
        define('ISPJAX', request()->isPjax());
        define('MODULE_NAME', request()->module());   //全小写
        define('CONTROLLER_NAME', request()->controller());   //首字母大写
        define('ACTION_NAME', request()->action());   //全小写
    }
}