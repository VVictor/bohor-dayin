<?php
namespace app\common\model;

use think\Model;

class Guestbook extends Model
{
    public function user()
    {
        return $this->hasOne('User', 'id', 'uid')->field('id, username, name');
    }
    
    public function replay()
    {
        return $this->hasMany('Guestbook', 'gid')->where('status', 1);
    }
}