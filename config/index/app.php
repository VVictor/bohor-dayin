<?php
return [
    //系统默认头像地址
    'default_avatar' => '/static/global/image/avatar.png',
    
    //QQ授权登录配置
    'qqconnect' => [
        'appid' => '101452492',
        'appkey' => 'f4d988a606b5e04190bcc2a37c9370b0',
        'callback' => 'http://www.sxxblog.com/reg/qqconnectback',
        'scope' => 'get_user_info,add_share,list_album,add_album,upload_pic,add_topic,add_one_blog,add_weibo,check_page_fans,add_t,add_pic_t,del_t,get_repost_list,get_info,get_other_info,get_fanslist,get_idolist,add_idol,del_idol,get_tenpay_addr',
        'errorReport' => true
    ],
];
