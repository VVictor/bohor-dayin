<?php
namespace app\common\validate;

use think\Validate;

class ArcComment extends Validate
{
    protected $rule = [
        'content' => 'require',
        'aid' => 'require|gt:0',
        'uid' => 'require|gt:0',
        'status' => 'require|in:0,1',
    ];

    protected $message = [
        'content' => '评论内容不能为空',
        'aid' => '文档ID必须大于0',
        'uid' => '请登录后在评论',
        'status' => '{%status_val}',
    ];

    protected $scene = [
        'add'   => ['content', 'aid', 'uid'],
        'edit'  => ['status'],
        'status' => ['status'],
    ];
}